<?php

namespace App\Services;

use App\Models\InstalledTheme;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/*
 * نصب قالب‌های خریداری‌شده از بازار — فاز ۴۴
 *
 * جریان: دانلود ZIP (توکن یک‌بارمصرف یا download-url) → بررسی SHA-256 →
 * اسکن امنیتی Zip-Slip → تشخیص نام قالب از ساختار ZIP → استخراج امن به
 * themes/<Name> → ثبت در installed_themes → dump-autoload.
 *
 * الگوی امنیتی عیناً از ThemeIndex::store (Zip-Slip) و الگوی دانلود/بکاپ
 * عیناً از PackageInstallerService گرفته شده است.
 */
class ThemeInstallerService
{
    public function __construct(private PackageApiService $api)
    {
    }

    /**
     * نصب قالب از ZIP.
     *
     * @param  string  $slug  slug قالب در پنل مرکزی
     * @param  string|null  $downloadToken  توکن یک‌بارمصرف (از پاسخ verify/خرید مجدد)
     * @param  string|null  $expectedHash  هش SHA-256 فایل ZIP (در صورت وجود)
     * @param  string  $purchaseType  normal | exclusive
     * @param  int|null  $adminId  مدیر درخواست‌کننده
     * @return InstalledTheme قالب نصب‌شده
     *
     * @throws RuntimeException هر خطای دانلود/استخراج/امنیت
     */
    public function install(
        string $slug,
        ?string $downloadToken = null,
        ?string $expectedHash = null,
        string $purchaseType = 'normal',
        ?int $adminId = null,
    ): InstalledTheme {
        $installed = InstalledTheme::markPending(
            $slug,
            InstalledTheme::where('slug', $slug)->value('name') ?: $slug,
            InstalledTheme::where('slug', $slug)->value('version'),
            $purchaseType,
            $adminId
        );

        try {
            // ─── ۱) دانلود ───
            $zipPath = $this->downloadZip($slug, $downloadToken, $expectedHash);

            try {
                // ─── ۲) اسکن امنیتی + استخراج ───
                $themeDirName = $this->extractToThemes($zipPath);
            } finally {
                File::delete($zipPath);
            }

            // ─── ۳) ثبت + به‌روزرسانی ظاهر ───
            $version = $this->readThemeVersion($themeDirName);

            $installed->update([
                'name' => $themeDirName,
                'version' => $version,
            ]);
            $installed->markAsInstalled($version);

            $this->refreshFramework($themeDirName);

            Log::info('Theme installed', ['slug' => $slug, 'dir' => $themeDirName, 'purchase_type' => $purchaseType]);

            return $installed->refresh();
        } catch (Throwable $e) {
            $installed->markAsFailed($e->getMessage());

            throw $e instanceof RuntimeException ? $e : new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * ریشه‌ی قالب‌ها — قابل بازنویسی با config برای تست‌ها.
     */
    public function themesPath(): string
    {
        return rtrim((string) config('packages.themes.path', base_path('themes')), '/');
    }

    /** فعال‌سازی قالب — همان زنجیره‌ی شناخته‌شده‌ی ThemeIndex (symlink + env + publish + shop:link) */
    public function activate(string $themeDirName): void
    {
        if (! is_safe_extension_name($themeDirName)) {
            throw new RuntimeException('نام قالب معتبر نیست.');
        }

        if (! is_dir($this->themesPath().'/'.$themeDirName)) {
            throw new RuntimeException('قالب پیدا نشد.');
        }

        $targetPath = $this->themesPath()."/{$themeDirName}/src/resources/assets";
        $linkPath   = public_path("themes/{$themeDirName}");

        if (is_dir($targetPath) && ! File::exists($linkPath)) {
            if (! File::exists(public_path('themes'))) {
                File::makeDirectory(public_path('themes'), 0755, true);
            }

            File::link($targetPath, $linkPath);
        }

        change_env('CURRENT_THEME', $themeDirName);
        Artisan::call('config:clear');
    }

    /** حذف قالب غیرفعال (قالب فعال اجازه حذف ندارد) */
    public function uninstall(string $slug): bool
    {
        $installed = InstalledTheme::where('slug', $slug)->first();

        if (! $installed) {
            throw new RuntimeException('این قالب نصب نشده است.');
        }

        if ($installed->isActive()) {
            throw new RuntimeException('قالب فعال قابل حذف نیست؛ ابتدا قالب دیگری را فعال کنید.');
        }

        if (is_dir($this->themesPath().'/'.$installed->name)) {
            $config = customConfig($this->themesPath().'/'.$installed->name.'/config/general.php');

            (new \Illuminate\Filesystem\Filesystem)->deleteDirectory($this->themesPath().'/'.$installed->name);

            // پاک‌سازی دارایی‌های منتشرشده در public (با گارد مسیر)
            $assetPath = is_array($config) ? ($config['asset_path'] ?? '') : '';
            if (is_string($assetPath) && $assetPath !== '' && ! str_contains($assetPath, '..')) {
                Storage::disk('public')->deleteDirectory($assetPath);
            }
        }

        $installed->delete();

        Log::info('Theme uninstalled', ['slug' => $slug]);

        return true;
    }

    /* ================= internals ================= */

    /**
     * دانلود ZIP قالب به مسیر موقت + بررسی هش.
     *
     * @return string مسیر مطلق فایل zip موقت
     */
    private function downloadZip(string $slug, ?string $downloadToken, ?string $expectedHash): string
    {
        // توکن یک‌بارمصرف؟ → دانلود مستقیم با URL توکن
        // در غیر این صورت ابتدا download-url گرفته می‌شود (خرید مجددها همیشه token می‌دهند)
        if (empty($downloadToken)) {
            $issued = $this->api->createTemplateDownloadUrl($slug);

            $downloadUrl   = $issued['download_url'] ?? null;
            $expectedHash  = $expectedHash ?: ($issued['file_hash'] ?? null);

            if (empty($downloadUrl)) {
                throw new RuntimeException('توکن دانلود از سرور قالب‌ها دریافت نشد.');
            }
        } else {
            $downloadUrl = $this->api->getTemplateDownloadUrl($downloadToken);
        }

        $response = Http::timeout((int) config('packages.download.timeout', 300))
            ->connectTimeout((int) config('packages.download.connect_timeout', 30))
            ->withToken((string) config('packages.api.token'))
            ->withHeaders([
                'Accept'        => 'application/json',
                'X-Project-Key' => (string) config('packages.api.project_key'),
                'X-Project-Url' => request()->root(),
            ])
            ->get($downloadUrl);

        if (! $response->successful()) {
            Log::error('Template download failed', ['slug' => $slug, 'status' => $response->status()]);

            throw new RuntimeException('دانلود فایل قالب ناموفق بود (کد '.$response->status().').');
        }

        $tempPath = config('packages.download.temp_path', 'packages/temp').'/'.Str::uuid().'.zip';
        Storage::disk('local')->put($tempPath, $response->body());
        $zipPath = Storage::disk('local')->path($tempPath);

        // ─── تأیید یکپارچگی SHA-256 (الزام مستند API §8.3) ───
        if (! empty($expectedHash)
            && config('packages.security.verify_signature', true)
            && ! hash_equals(strtolower($expectedHash), (string) hash_file('sha256', $zipPath))) {
            File::delete($zipPath);

            throw new RuntimeException('بررسی یکپارچگی فایل قالب ناموفق بود (هش مطابقت ندارد).');
        }

        return $zipPath;
    }

    /**
     * اسکن امنیتی Zip-Slip + استخراج امن به themes/<Name>.
     *
     * @return string نام پوشه قالب داخل themes/
     */
    private function extractToThemes(string $zipPath): string
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('فایل ZIP قالب باز نشد.');
        }

        try {
            // ۱) اسکن امنیتی همه‌ی ورودی‌ها (Zip-Slip / مسیر مطلق / symlink)
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = (string) $zip->getNameIndex($i);
                $stat      = $zip->statIndex($i);

                $isUnsafe = $entryName === ''
                    || str_contains($entryName, '..')
                    || str_starts_with($entryName, '/')
                    || preg_match('#^[A-Za-z]:[\\\\/]#', $entryName)
                    || (isset($stat['attr']) && ((($stat['attr'] >> 16) & 0xF000) === 0xA000));

                if ($isUnsafe) {
                    throw new RuntimeException('فایل zip حاوی ورودی‌های غیرمجاز است.');
                }
            }

            // ۲) تشخیص نام قالب: پوشه‌ی ریشه‌ای که config/general.php دارد (یا تنها پوشه‌ی ریشه)
            $rootDir = $this->detectRootDir($zip);

            if ($rootDir === null) {
                throw new RuntimeException('ساختار فایل قالب معتبر نیست (پوشه‌ی اصلی یافت نشد).');
            }

            if (! is_safe_extension_name($rootDir)) {
                throw new RuntimeException('نام قالب داخل zip معتبر نیست.');
            }

            // ۳) بکاپ نسخه‌ی قبلی (مثل PackageInstallerService)
            $themesPath = $this->themesPath();
            $targetPath = $themesPath.'/'.$rootDir;

            if (! is_dir($themesPath)) {
                @mkdir($themesPath, 0755, true);
            }

            if (File::exists($targetPath)) {
                $backupPath = $themesPath.'/.backups/'.$rootDir.'_'.now()->format('Ymd_His');

                @mkdir(dirname($backupPath), 0755, true);

                if (! File::moveDirectory($targetPath, $backupPath)) {
                    throw new RuntimeException('بکاپ نسخه‌ی قبلی قالب ناموفق بود.');
                }
            }

            // ۴) استخراج امن به مسیر موقت + انتقال به themes/
            $tmpPath = storage_path('app/themes-extract-'.uniqid());

            @mkdir($tmpPath, 0755, true);

            if (! $zip->extractTo($tmpPath)) {
                File::deleteDirectory($tmpPath);

                throw new RuntimeException('استخراج فایل قالب ناموفق بود.');
            }

            $source = $tmpPath.'/'.$rootDir;

            if (! is_dir($source)) {
                // ZIP بدون پوشه‌ی ریشه — کل محتوا = قالب
                $source = $tmpPath;
            }

            if (! File::moveDirectory($source, $targetPath)) {
                File::deleteDirectory($tmpPath);

                throw new RuntimeException('انتقال قالب به پوشه themes ناموفق بود.');
            }

            if (realpath($tmpPath) !== realpath($targetPath)) {
                File::deleteDirectory($tmpPath);
            }

            return $rootDir;
        } finally {
            $zip->close();
        }
    }

    /** پوشه‌ی ریشه‌ی قالب داخل ZIP: ترجیحاً پوشه‌ای که config/general.php دارد */
    private function detectRootDir(ZipArchive $zip): ?string
    {
        $generalPhpIndex = null;
        $rootCandidates  = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);

            if (preg_match('#^([^/]+)/config/general\.php$#', $entry, $m)) {
                $generalPhpIndex = $m[1];
                break;
            }

            if (preg_match('#^([^/]+)/#', $entry, $m)) {
                $rootCandidates[$m[1]] = true;
            }
        }

        if ($generalPhpIndex !== null) {
            return $generalPhpIndex;
        }

        if (count($rootCandidates) === 1) {
            return array_key_first($rootCandidates);
        }

        return null;
    }

    /** نسخه قالب — از config/general.php اگر version/theme_name داشت، وگرنه null */
    private function readThemeVersion(string $themeDirName): ?string
    {
        $config = customConfig($this->themesPath().'/'.$themeDirName.'/config/general.php');

        if (is_array($config)) {
            return isset($config['version']) ? (string) $config['version'] : null;
        }

        return null;
    }

    /** پس از نصب: autoload + publish لینک دارایی + shop:link (الگوی ThemeIndex::store) */
    private function refreshFramework(string $themeDirName): void
    {
        if (! config('packages.themes.refresh_after_install', true)) {
            return; // در تست‌ها خاموش می‌شود تا سریع و ایزوله باشد
        }

        try {
            Artisan::call('dump-autoload');

            if (is_safe_extension_name($themeDirName)) {
                Artisan::call('vendor:publish --tag="'.escapeshellcmd($themeDirName).'" --force');
            }

            Artisan::call('shop:link');
        } catch (Throwable $e) {
            // شکست این مرحله نصب را نامعتبر نمی‌کند — فقط لاگ
            Log::warning('Theme refresh framework step failed', ['theme' => $themeDirName, 'error' => $e->getMessage()]);
        }
    }
}

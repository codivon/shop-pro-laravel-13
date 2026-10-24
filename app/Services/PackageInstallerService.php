<?php

namespace App\Services;

use App\Models\InstalledModule;
use App\Models\InstalledTheme;
use App\Models\ModuleInstallLog;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class PackageInstallerService
{
    private array $steps = [];

    private ?string $currentModuleName = null;

    /*
     * فاز ۴۵ — سرویس واحد بازار: پکیج + قالب در یک کلاس.
     * پشتوانه‌ی این تصمیم: ارائه‌دهنده‌ی هر دو محصول یک سرور مرکزی واحد است
     * (همان PackageApiService)؛ پس منطق نصبشان هم یک‌جا جمع شده است —
     * بخش پکیج‌ها → Modules/ و بخش قالب‌ها → themes/ (پایین همین فایل).
     * ThemeInstallerService جداگانه حذف شد.
     */

    public function __construct(
        private PackageApiService $api,
        private Filesystem $files
    ) {}

    /* ===================================================================
     *  نصب پکیج
     * =================================================================== */
    public function install(
        string $slug,
        string $licenseKey,
        ?int $adminId = null,
        ?string $downloadToken = null
    ): InstalledModule {
        $this->steps = [];
        $log = $this->startLog(ModuleInstallLog::ACTION_INSTALL, $slug, $adminId);
        $this->currentModuleName = null;

        try {
            // 1) دریافت download_token از API
            $verify = [];

            if (! $downloadToken) {
                $this->step('verify_license', 'بررسی لایسنس');
                $verify = $this->api->verifyLicense($slug, $licenseKey);

                if (! ($verify['valid'] ?? false)) {
                    throw new RuntimeException('لایسنس نامعتبر است.');
                }

                $downloadToken = $verify['download_token']
                    ?? throw new RuntimeException('token دانلود دریافت نشد.');
            }

            // 2) دانلود ZIP
            $this->step('download', 'دانلود فایل پکیج');

            try {
                $zipPath = $this->downloadZip($downloadToken);
            } catch (RuntimeException $downloadError) {
                /*
                 * فاز ۲۸ — رفع خطای «دانلود فایل پکیج ناموفق بود (کد: 404)»:
                 * توکن دانلودی که قبل از dispatch گرفته شده (createPurchase /
                 * verifyPayment / verifyLicense) single-use یا کوتاه‌عمر است؛ اگر
                 * صف با تأخیر بردارد یا توکن قبلاً مصرف شده باشد، دانلود 404 می‌خورد.
                 * مثل رفتار واقعی «توکن از داخل job گرفته می‌شود» در پنل قدیمی،
                 * اینجا یک بار لایسنس از نو بررسی و با توکن تازه دانلود تکرار می‌شود.
                 */
                if (empty($licenseKey)) {
                    throw $downloadError;
                }

                Log::warning('Package download failed with provided token — retrying with a fresh token', [
                    'slug' => $slug,
                    'error' => $downloadError->getMessage(),
                ]);

                $fresh = $this->api->verifyLicense($slug, $licenseKey);

                if (! ($fresh['valid'] ?? false) || empty($fresh['download_token'])) {
                    throw $downloadError;
                }

                $verify = $fresh;
                $downloadToken = $fresh['download_token'];

                $zipPath = $this->downloadZip($downloadToken);
            }

            // 3) تأیید امضای فایل
            $expectedHash = $verify['signature'] ?? null;
            if (config('packages.security.verify_signature') && $expectedHash) {
                $this->step('verify_signature', 'تأیید یکپارچگی فایل');
                $this->verifySignature($zipPath, $expectedHash);
            }

            // 4) استخراج ZIP
            $this->step('extract', 'استخراج فایل‌های پکیج');
            $moduleName = $this->extractZip($zipPath, $slug);
            $this->currentModuleName = $moduleName;

            // 5) ثبت autoloader موقت
            $this->registerModuleAutoloader($moduleName);

            // 6) ثبت در دیتابیس
            $installed = $this->registerInstall($slug, $moduleName, $licenseKey, $verify ?? []);

            // 7) اجرای migrationها
            $this->step('migrate', 'اجرای migrationهای ماژول');
            $this->runMigrations($moduleName);

            // 8) نصب پرمیژن‌ها
            $this->installModulePermissions($moduleName);

            // 9) اجرای seederها
            $this->runModuleSeeders($moduleName);

            // 10) انتشار assetها
            $this->publishModuleAssets($moduleName);

            // 11) پاکسازی فایل موقت
            $this->cleanupTemp($zipPath);

            $installed->markAsInstalled($verify['version'] ?? $this->readModuleVersion($moduleName));

            $this->finishLog($log, ModuleInstallLog::STATUS_SUCCESS, $installed);

            // 12) رفرش کش‌ها
            $this->step('cache_refresh', 'بروزرسانی کش‌ها');
            $this->refreshCaches($moduleName);

            // 13) restart queue worker
            $this->restartQueueWorker();

            // 14) فعال‌سازی ماژول
            $this->toggleActivation($slug);

            // 15) ایجاد modules_statuses.json
            $this->step('update_statuses', 'به‌روزرسانی وضعیت ماژول‌ها');
            $this->ensureModulesStatusesFile();

            return $installed;
        } catch (Exception $e) {
            $this->failLog($log, $e, $slug);
            throw $e;
        }
    }

    /* ===================================================================
     *  آپدیت پکیج
     * =================================================================== */
    public function update(string $slug, ?int $adminId = null): InstalledModule
    {
        $installed = InstalledModule::where('slug', $slug)->firstOrFail();
        $oldVersion = $installed->version;

        $installed->markAsUpdating();

        $log = $this->startLog(
            ModuleInstallLog::ACTION_UPDATE,
            $slug,
            $adminId,
            $oldVersion
        );

        try {
            $verify = $this->api->verifyLicense($slug, $installed->license_key);

            if (! ($verify['valid'] ?? false)) {
                throw new RuntimeException($verify['message'] ?? 'لایسنس منقضی یا نامعتبر است.');
            }

            // ★ آپدیت فقط وقتی لایسنس زمان داشته باشد — منقضی = ممنوع
            $expiresAt = $verify['expires_at'] ?? optional($installed->license_expires_at)->toDateTimeString();

            if (! $this->licenseHasRemainingTime($expiresAt)) {
                throw new RuntimeException(
                    'لایسنس شما منقضی شده است. برای دریافت آپدیت، ابتدا لایسنس را تمدید کنید.'
                );
            }
            if (! ($verify['valid'] ?? false)) {
                throw new RuntimeException(
                    $verify['message'] ?? 'لایسنس منقضی یا نامعتبر است.'
                );
            }

            $newVersion = $verify['version'] ?? null;
            if (! $newVersion || version_compare($newVersion, $oldVersion, '<=')) {
                throw new RuntimeException('نسخه جدیدی برای نصب وجود ندارد.');
            }

            $this->install($slug, $installed->license_key, $adminId, $verify['download_token'] ?? null);

            $installed->refresh();
            $log->update([
                'to_version' => $installed->version,
                'status' => ModuleInstallLog::STATUS_SUCCESS,
            ]);

            return $installed;
        } catch (Exception $e) {
            $this->failLog($log, $e, $slug);
            throw $e;
        }
    }

    /* ===================================================================
     *  حذف ماژول
     * =================================================================== */
    public function uninstall(string $slug, ?int $adminId = null): bool
    {
        $installed = InstalledModule::where('slug', $slug)->firstOrFail();

        $log = $this->startLog(
            ModuleInstallLog::ACTION_UNINSTALL,
            $slug,
            $adminId,
            $installed->version
        );

        try {
            $moduleName = $installed->name;

            // حذف پرمیژن‌ها
            $this->removeModulePermissions($moduleName);

            // رول‌بک migrationها
            $this->step('rollback_migrations', 'حذف جداول ماژول');
            $this->rollbackMigrations($moduleName);

            // حذف assetها
            $this->step('remove_assets', 'حذف فایل‌های استاتیک ماژول');
            $this->removePublishedAssets($moduleName);

            // حذف پوشه ماژول
            $this->step('remove_files', 'حذف فایل‌های ماژول');
            $modulePath = config('packages.modules.path').'/'.$moduleName;
            if (File::exists($modulePath)) {
                File::deleteDirectory($modulePath);
            }

            // رفرش کش
            $this->refreshCaches();

            // حذف رکورد
            $installed->delete();

            $this->finishLog($log, ModuleInstallLog::STATUS_SUCCESS, null, 'ماژول با موفقیت حذف شد');

            $this->restartQueueWorker();

            return true;
        } catch (Exception $e) {
            $this->failLog($log, $e, $slug);
            throw $e;
        }
    }

    /* ===================================================================
     *  فعال/غیرفعال‌سازی
     * =================================================================== */
    public function toggleActivation(string $slug): InstalledModule
    {
        $installed = InstalledModule::where('slug', $slug)->firstOrFail();

        try {
            Artisan::call(
                $installed->is_active ? 'module:disable' : 'module:enable',
                ['module' => $installed->name]
            );
        } catch (Exception $e) {
            Log::warning('Module toggle failed via artisan', [
                'module' => $installed->name,
                'error' => $e->getMessage(),
            ]);
        }

        $installed->update(['is_active' => ! $installed->is_active]);
        $this->refreshCaches();

        return $installed->refresh();
    }

    /* ===================================================================
     *  دانلود ZIP
     * =================================================================== */
    private function downloadZip(string $token): string
    {
        $url = $this->api->getDownloadUrl($token);
        $disk = Storage::disk(config('packages.download.disk'));
        $relPath = config('packages.download.temp_path').'/'.Str::uuid().'.zip';

        $projectUrl = env('APP_URL');
        $response = Http::timeout(config('packages.download.timeout', 600))
            ->withToken(config('packages.api.token'))
            ->withHeaders([
                'X-Project-Key' => config('packages.api.project_key'),
                'X-Project-Url' => $projectUrl,
            ])->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('دانلود فایل پکیج ناموفق بود (کد: '.$response->status().')');
        }

        $disk->put($relPath, $response->body());

        return $disk->path($relPath);
    }

    /* ===================================================================
     *  تأیید SHA-256
     * =================================================================== */
    private function verifySignature(string $zipPath, string $expectedHash): void
    {
        $actual = hash_file('sha256', $zipPath);
        if (! hash_equals($expectedHash, $actual)) {
            throw new RuntimeException(
                'تأیید یکپارچگی فایل ناموفق بود! فایل احتمالاً دستکاری شده است.'
            );
        }
    }

    /* ===================================================================
     *  استخراج ZIP
     * =================================================================== */
    private function extractZip(string $zipPath, string $slug): string
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('افزونه ZipArchive روی سرور فعال نیست.');
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('باز کردن فایل ZIP ناموفق بود.');
        }

        $moduleName = null;
        $moduleJsonIndex = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (preg_match('#^([^/]+)/module\.json$#', $entry, $m)) {
                $moduleName = $m[1];
                $moduleJsonIndex = $i;
                break;
            }
        }

        if (! $moduleName) {
            $zip->close();
            throw new RuntimeException('فایل module.json در ZIP پیدا نشد. ساختار پکیج نامعتبر است.');
        }

        /* نام کانونی ماژول را از خود module.json می‌خوانیم.
         * پوشه داخل ZIP ممکن است هر حالت حروفی داشته باشد (مثلاً seoAudit)
         * اما namespace کلاس‌های PHP (Modules\SeoAudit\...) از فیلد name می‌آید؛
         * روی لینوکس فایل‌سیستم case-sensitive است و مغایرت نام پوشه با نام ماژول
         * یعنی autoload ناموفق → سرویس‌پروایدر/روت‌ها/منوی ماژول اصلاً لود نمی‌شوند. */
        $canonicalName = $moduleName;
        $meta = json_decode((string) $zip->getFromIndex($moduleJsonIndex), true);
        if (is_array($meta) && ! empty($meta['name']) && is_string($meta['name'])) {
            $canonicalName = trim($meta['name']);
        }

        $modulesPath = config('packages.modules.path');
        if (! File::exists($modulesPath)) {
            File::makeDirectory($modulesPath, 0755, true);
        }

        $targetPath = $modulesPath.'/'.$canonicalName;

        if (File::exists($targetPath)) {
            $backupPath = $modulesPath.'/.backups/'.$canonicalName.'_'.time();
            File::ensureDirectoryExists(dirname($backupPath), 0755);
            File::moveDirectory($targetPath, $backupPath);
        }

        // پوشه قدیمی با حالت حروفی متفاوت (نصب‌های قبلی) هم باید جایگزین شود
        if ($moduleName !== $canonicalName) {
            $legacyPath = $modulesPath.'/'.$moduleName;
            if (File::exists($legacyPath)) {
                $legacyBackup = $modulesPath.'/.backups/'.$moduleName.'_'.time();
                File::ensureDirectoryExists(dirname($legacyBackup), 0755);
                File::moveDirectory($legacyPath, $legacyBackup);
            }
        }

        // استخراج در پوشه موقت و انتقال با نام کانونی (نه نام پوشه ZIP)
        $tmpPath = $modulesPath.'/.tmp-extract-'.uniqid();
        File::ensureDirectoryExists($tmpPath, 0755);

        if (! $zip->extractTo($tmpPath)) {
            $zip->close();
            File::deleteDirectory($tmpPath);
            throw new RuntimeException('استخراج فایل ZIP ناموفق بود.');
        }
        $zip->close();

        if (! File::exists($tmpPath.'/'.$moduleName)) {
            File::deleteDirectory($tmpPath);
            throw new RuntimeException('پوشه ماژول داخل ZIP یافت نشد.');
        }

        File::moveDirectory($tmpPath.'/'.$moduleName, $targetPath, true);
        File::deleteDirectory($tmpPath);

        clearstatcache();

        return $canonicalName;
    }

    /* ===================================================================
     *  ثبت در دیتابیس
     * =================================================================== */
    private function registerInstall(
        string $slug,
        string $moduleName,
        string $licenseKey,
        array $verifyData
    ): InstalledModule {
        $integrityHash = md5(config('packages.api.token', '').config('packages.api.project_key', ''));

        return InstalledModule::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $moduleName,
                'version' => $verifyData['version'] ?? $this->readModuleVersion($moduleName),
                'license_key' => $licenseKey,
                'license_expires_at' => $verifyData['expires_at'] ?? null,
                'integrity_hash' => $integrityHash,
                'last_verified_at' => now(),
                'installed_at' => now(),
                'is_active' => false,
                'status' => InstalledModule::STATUS_UPDATING,
                'last_error' => null,
            ]
        );
    }

    /* ===================================================================
     *  ثبت Autoloader موقت
     * =================================================================== */
    /**
     * ثبت autoloader برای کلاس‌های ماژول جدید
     * (در runtime + بدون نیاز به composer dump-autoload)
     */
    private function registerModuleAutoloader(string $moduleName): void
    {
        $modulePath = config('packages.modules.path').'/'.$moduleName;
        $prefix = "Modules\\{$moduleName}\\";

        // جلوگیری از ثبت تکراری (در لاگ شما ۵ بار ثبت شده بود!)
        static $registered = [];
        if (isset($registered[$prefix])) {
            return;
        }
        $registered[$prefix] = true;

        spl_autoload_register(function ($class) use ($prefix, $modulePath) {
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $path = str_replace('\\', '/', $relative).'.php';

            // نسخه‌ای که دایرکتوری‌ها lcfirst شده‌اند:
            // Database\Seeders\Foo  →  database/seeders/Foo.php
            $parts = explode('/', $path);
            $classFile = array_pop($parts);
            $lowerPath = implode('/', array_map('lcfirst', $parts)).'/'.$classFile;

            $candidates = [
                $modulePath.'/app/'.$path,        // app/Console/Commands/Foo.php
                $modulePath.'/'.$path,            // Entities/Foo.php (بدون app)
                $modulePath.'/app/'.$lowerPath,
                $modulePath.'/'.$lowerPath,       // database/seeders/Foo.php  ← حل مشکل شما
            ];

            foreach ($candidates as $file) {
                if (is_file($file)) {
                    require_once $file;

                    return true;
                }
            }

            // Fallback نهایی: جستجوی «نام فایل = نام کلاس» در کل ماژول
            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($modulePath, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $f) {
                    if ($f->isFile() && $f->getFilename() === $classFile) {
                        require_once $f->getPathname();

                        return true;
                    }
                }
            } catch (\Throwable $e) {
                // نادیده بگیر
            }

            return false;
        }, true, true);

        Log::info("✅ Autoloader registered for module: {$moduleName}");
    }

    /* ===================================================================
     *  اجرای Composer Dump-Autoload (بدون exec)
     * =================================================================== */
    /* ===================================================================
 *  Composer Dump-Autoload - بدون exec
 *  فقط کش‌ها رو پاک می‌کنه و autoloader رو ثبت می‌کنه
 * =================================================================== */
    private function runComposerDumpAutoload(?string $moduleName = null): void
    {
        Log::info('🔄 Running cache cleanup (composer not available)');

        // ۱. ثبت autoloader ماژول (در runtime)
        if ($moduleName) {
            $this->registerModuleAutoloader($moduleName);
        }

        // ۲. حذف مستقیم فایل‌های کش (بدون نیاز به composer)
        $cacheFiles = [
            base_path('bootstrap/cache/modules.php'),
            base_path('bootstrap/cache/services.php'),
            base_path('bootstrap/cache/packages.php'),
            base_path('bootstrap/cache/routes-v7.php'),
            base_path('bootstrap/cache/routes.php'),
            base_path('bootstrap/cache/config.php'),
            base_path('bootstrap/cache/events.php'),
            base_path('bootstrap/cache/compiled.php'),
        ];

        foreach ($cacheFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
                Log::info('✅ '.basename($file).' removed');
            }
        }

        Log::info('✅ Cache cleanup completed');
    }

    /**
     * روش جایگزین برای composer dump-autoload
     */
    private function runComposerDumpAutoloadAlternative(): void
    {
        try {
            // استفاده از Artisan
            Artisan::call('optimize:clear');

            // اگر module:dump وجود دارد
            try {
                Artisan::call('module:dump');
                Log::info('✅ module:dump completed via Artisan');
            } catch (\Throwable $e) {
                // نادیده گرفتن
            }

            // حذف مستقیم فایل‌های کش
            $cacheFiles = [
                base_path('bootstrap/cache/modules.php'),
                base_path('bootstrap/cache/services.php'),
                base_path('bootstrap/cache/packages.php'),
                base_path('bootstrap/cache/autoload.php'),
            ];

            foreach ($cacheFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }

            Log::info('✅ Alternative dump-autoload completed');
        } catch (\Throwable $e) {
            Log::warning('⚠️ Alternative dump-autoload failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * پیدا کردن مسیر composer
     */
    private function findComposerPath(): ?string
    {
        $possiblePaths = [
            'composer',
            'composer.phar',
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            '/usr/bin/composer.phar',
            base_path('composer.phar'),
        ];

        foreach ($possiblePaths as $path) {
            try {
                $process = new Process([$path, '--version']);
                $process->run();
                if ($process->isSuccessful()) {
                    return $path;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /* ===================================================================
     *  اجرای Migrationها
     * =================================================================== */
    /* ===================================================================
 *  اجرای Migrationها
 * =================================================================== */
    private function runMigrations(string $moduleName): void
    {
        // ثبت autoloader (مهم برای پیدا کردن migrationها)
        $this->registerModuleAutoloader($moduleName);

        $modulePath = config('packages.modules.path').'/'.$moduleName;
        $migrationsPath = $this->findMigrationsPath($modulePath);

        if ($migrationsPath === null) {
            Log::warning('No migrations directory found', ['module' => $moduleName]);

            return;
        }

        $migrationFiles = glob($migrationsPath.'/*.php');
        sort($migrationFiles);

        Log::info('Found migration files', [
            'module' => $moduleName,
            'path' => $migrationsPath,
            'count' => count($migrationFiles),
        ]);

        if (empty($migrationFiles)) {
            return;
        }

        // اجرای مستقیم migrationها (بدون Artisan::call که در queue گیر می‌کنه)
        try {
            $this->runMigrationsIndividually($migrationFiles, $moduleName);
            Log::info('All migrations processed', ['module' => $moduleName]);
        } catch (Exception $e) {
            Log::error('Migration failed', ['module' => $moduleName, 'error' => $e->getMessage()]);
            // throw نکنیم - بذاریم نصب ادامه پیدا کنه
        }
    }

    /**
     * اجرای تک‌تک migrationها
     */
    private function runMigrationsIndividually(array $migrationFiles, string $moduleName): void
    {
        $this->registerModuleAutoloader($moduleName);

        $migrator = app('migrator');
        $repository = app('migration.repository');

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $ranMigrations = $repository->getRan();

        foreach ($migrationFiles as $file) {
            $name = basename($file);
            $migrationName = pathinfo($name, PATHINFO_FILENAME);

            if (in_array($migrationName, $ranMigrations)) {
                Log::info('Migration already ran, skipping', [
                    'module' => $moduleName,
                    'migration' => $migrationName,
                ]);

                continue;
            }

            Log::info('Running migration', [
                'module' => $moduleName,
                'migration' => $name,
            ]);

            try {
                $migrator->run($file);
                $repository->log($migrationName, 4);
            } catch (Exception $e) {
                if (str_contains($e->getMessage(), 'Base table or view already exists') ||
                    str_contains($e->getMessage(), 'already exists')) {

                    Log::warning('Migration skipped - table already exists', [
                        'module' => $moduleName,
                        'migration' => $name,
                    ]);

                    try {
                        $repository->log($migrationName, 4);
                    } catch (\Throwable $e2) {
                        // نادیده گرفتن
                    }

                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * رول‌بک migrationها
     */
    private function rollbackMigrations(string $moduleName): void
    {
        $modulePath = config('packages.modules.path').'/'.$moduleName;
        $migrationsPath = $this->findMigrationsPath($modulePath);

        if ($migrationsPath) {
            try {
                Artisan::call('migrate:reset', [
                    '--path' => $migrationsPath,
                    '--realpath' => true,
                    '--force' => true,
                ]);
                Log::info('Rollback completed', ['module' => $moduleName]);
            } catch (Exception $e) {
                Log::warning('Rollback failed', [
                    'module' => $moduleName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /* ===================================================================
     *  پیدا کردن مسیرها
     * =================================================================== */
    private function findMigrationsPath(string $modulePath): ?string
    {
        $patterns = [
            'database/migrations',
            'Database/Migrations',
            'database/Migrations',
            'Database/migrations',
        ];

        foreach ($patterns as $pattern) {
            $path = $modulePath.'/'.$pattern;
            if (is_dir($path)) {
                return $path;
            }
        }

        $databasePatterns = ['database', 'Database'];
        foreach ($databasePatterns as $dbPattern) {
            $dbPath = $modulePath.'/'.$dbPattern;
            if (is_dir($dbPath)) {
                $subdirs = scandir($dbPath);
                foreach ($subdirs as $subdir) {
                    if ($subdir === '.' || $subdir === '..') {
                        continue;
                    }
                    if (strtolower($subdir) === 'migrations') {
                        $migrationsPath = $dbPath.'/'.$subdir;
                        if (is_dir($migrationsPath)) {
                            return $migrationsPath;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function findSeedersPath(string $modulePath): ?string
    {
        $patterns = [
            'database/seeders',
            'Database/Seeders',
            'database/Seeders',
            'Database/seeders',
        ];

        foreach ($patterns as $pattern) {
            $path = $modulePath.'/'.$pattern;
            if (is_dir($path)) {
                return $path;
            }
        }

        $dbPatterns = ['database', 'Database'];
        foreach ($dbPatterns as $dbPattern) {
            $dbPath = $modulePath.'/'.$dbPattern;
            if (is_dir($dbPath)) {
                $subdirs = scandir($dbPath);
                foreach ($subdirs as $subdir) {
                    if ($subdir === '.' || $subdir === '..') {
                        continue;
                    }
                    if (strtolower($subdir) === 'seeders') {
                        $seedersPath = $dbPath.'/'.$subdir;
                        if (is_dir($seedersPath)) {
                            return $seedersPath;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function findAssetsPath(string $modulePath): ?string
    {
        $patterns = [
            'Resources/assets',
            'resources/assets',
            'Resources/Assets',
        ];

        foreach ($patterns as $pattern) {
            $path = $modulePath.'/'.$pattern;
            if (is_dir($path)) {
                return $path;
            }
        }

        $resourcePatterns = ['Resources', 'resources'];
        foreach ($resourcePatterns as $resPattern) {
            $resPath = $modulePath.'/'.$resPattern;
            if (is_dir($resPath)) {
                $subdirs = scandir($resPath);
                foreach ($subdirs as $subdir) {
                    if ($subdir === '.' || $subdir === '..') {
                        continue;
                    }
                    if (strtolower($subdir) === 'assets') {
                        $assetsPath = $resPath.'/'.$subdir;
                        if (is_dir($assetsPath)) {
                            return $assetsPath;
                        }
                    }
                }
            }
        }

        return null;
    }

    /* ===================================================================
     *  نصب پرمیژن‌ها
     * =================================================================== */
    private function installModulePermissions(string $moduleName): void
    {
        $commandClass = "Modules\\{$moduleName}\\Console\\Commands\\InstallPermissionsCommand";

        if (! class_exists($commandClass)) {
            return;
        }

        $this->step('install_permissions', 'نصب پرمیژن‌های ماژول');

        try {
            $commandInstance = app($commandClass);
            $commandInstance->setLaravel(app());

            $input = new ArrayInput([]);
            $output = new BufferedOutput;

            $commandInstance->run($input, $output);

            Log::info("Module permissions installed: {$moduleName}", [
                'output' => $output->fetch(),
            ]);
        } catch (Exception $e) {
            Log::warning('Permission install failed (continuing)', [
                'module' => $moduleName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function removeModulePermissions(string $moduleName): void
    {
        $commandClass = "Modules\\{$moduleName}\\Console\\Commands\\InstallPermissionsCommand";

        if (! class_exists($commandClass)) {
            return;
        }

        $this->step('remove_permissions', 'حذف پرمیژن‌های ماژول');

        try {
            $commandInstance = app($commandClass);
            $commandInstance->setLaravel(app());

            $input = new ArrayInput(['--remove' => true]);
            $output = new BufferedOutput;

            $commandInstance->run($input, $output);

            Log::info("Module permissions removed: {$moduleName}", [
                'output' => $output->fetch(),
            ]);
        } catch (Exception $e) {
            Log::warning('Permission removal failed (continuing)', [
                'module' => $moduleName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* ===================================================================
     *  انتشار Assetها
     * =================================================================== */
    private function publishModuleAssets(string $moduleName): void
    {
        $modulePath = config('packages.modules.path').'/'.$moduleName;
        $sourcePath = $this->findAssetsPath($modulePath);

        if (! $sourcePath) {
            return;
        }

        $this->step('publish_assets', 'انتشار فایل‌های استاتیک ماژول');

        $publicPath = public_path('modules/'.strtolower($moduleName));

        try {
            File::ensureDirectoryExists(dirname($publicPath), 0755, true);
            $this->copyDirectory($sourcePath, $publicPath);

            Log::info("Module assets published: {$moduleName}", [
                'source' => $sourcePath,
                'target' => $publicPath,
            ]);
        } catch (Exception $e) {
            Log::warning('Asset publish failed (continuing)', [
                'module' => $moduleName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function removePublishedAssets(string $moduleName): void
    {
        $publicPath = public_path('modules/'.strtolower($moduleName));

        if (File::exists($publicPath)) {
            try {
                File::deleteDirectory($publicPath);
                Log::info("Module assets removed: {$moduleName}");
            } catch (Exception $e) {
                Log::warning('Asset removal failed (continuing)', [
                    'module' => $moduleName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (! File::exists($destination)) {
            File::ensureDirectoryExists($destination, 0755, true);
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $target = $destination.'/'.$items->getSubPathName();
            if ($item->isDir()) {
                if (! File::exists($target)) {
                    File::ensureDirectoryExists($target, 0755, true);
                }
            } else {
                File::copy($item->getRealPath(), $target);
            }
        }
    }

    /* ===================================================================
     *  اجرای Seederها
     * =================================================================== */
    private function runModuleSeeders(string $moduleName): void
    {
        $this->registerModuleAutoloader($moduleName);

        $modulePath = config('packages.modules.path').'/'.$moduleName;
        $seedersPath = $this->findSeedersPath($modulePath);

        if ($seedersPath === null) {
            Log::info('No seeders directory found', ['module' => $moduleName]);

            return;
        }

        $this->step('run_seeders', 'اجرای seederهای ماژول');

        try {
            // ۱) require مستقیم همه فایل‌های seeder
            //    → مستقل از autoload؛ مشکل case-sensitivity کاملاً بی‌اثر می‌شود
            $this->requireAllPhpFiles($seedersPath);

            $mainSeederClass = "Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}DatabaseSeeder";

            // ۲) اگر هنوز لود نشده بود، کلاس را از خود فایل‌ها پیدا کن
            if (! class_exists($mainSeederClass)) {
                $mainSeederClass = $this->resolveSeederClass($moduleName, $seedersPath)
                    ?? $mainSeederClass;
            }

            if (! class_exists($mainSeederClass)) {
                Log::warning('Main seeder class not found', [
                    'module' => $moduleName,
                    'class' => $mainSeederClass,
                    'files' => scandir($seedersPath),
                ]);

                return;
            }

            $seeder = app($mainSeederClass);
            $seeder->setContainer(app());

            $output = new BufferedOutput;
            $command = new Command;
            $command->setLaravel(app());
            $command->setOutput(
                new OutputStyle(
                    new ArrayInput([]),
                    $output
                )
            );
            $seeder->setCommand($command);

            $seeder->__invoke();

            Log::info("Module seeders executed: {$moduleName}", [
                'output' => $output->fetch(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Seeder execution failed (continuing)', [
                'module' => $moduleName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function requireAllPhpFiles(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                try {
                    require_once $file->getPathname();
                } catch (\Throwable $e) {
                    Log::warning('Could not require seeder file', [
                        'file' => $file->getPathname(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function resolveSeederClass(string $moduleName, string $seedersPath): ?string
    {
        $target = strtolower($moduleName.'DatabaseSeeder.php');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($seedersPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $isExact = strtolower($file->getFilename()) === $target;
            $isAny = str_ends_with(strtolower($file->getFilename()), 'databaseseeder.php');

            if ($isExact || $isAny) {
                $content = file_get_contents($file->getPathname());
                $class = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                if (preg_match('/^\s*namespace\s+([^;]+);/m', $content, $m)) {
                    $class = trim($m[1]).'\\'.$class;
                }

                require_once $file->getPathname();

                if (class_exists($class)) {
                    return $class;
                }
                if ($isExact) {
                    break; // دقیقاً همین فایل بود ولی کلاس ندارد
                }
            }
        }

        return null;
    }

    /* ===================================================================
     *  رفرش کش‌ها (بدون exec)
     * =================================================================== */
    /* ===================================================================
 *  رفرش کش‌ها (بدون exec)
 * =================================================================== */
    private function refreshCaches(?string $moduleName = null): void
    {
        try {
            // ۱. ثبت autoloader موقت
            if ($moduleName) {
                $this->registerModuleAutoloader($moduleName);
            }

            // ۲. پاک کردن کش‌های لاراول
            $commands = ['config:clear', 'cache:clear', 'view:clear', 'route:clear', 'optimize:clear'];
            foreach ($commands as $command) {
                try {
                    Artisan::call($command);
                    Log::info("✅ {$command} executed");
                } catch (\Throwable $e) {
                    Log::warning("⚠️ {$command} failed", ['error' => $e->getMessage()]);
                }
            }

            // ۳. حذف مستقیم فایل‌های کش
            $cacheFiles = [
                base_path('bootstrap/cache/modules.php'),
                base_path('bootstrap/cache/services.php'),
                base_path('bootstrap/cache/packages.php'),
                base_path('bootstrap/cache/routes-v7.php'),
                base_path('bootstrap/cache/routes.php'),
                base_path('bootstrap/cache/config.php'),
                base_path('bootstrap/cache/events.php'),
                base_path('bootstrap/cache/compiled.php'),
            ];

            foreach ($cacheFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }

            Log::info('✅ All caches refreshed successfully');
        } catch (Exception $e) {
            Log::warning('Cache refresh partial failure', ['error' => $e->getMessage()]);
        }
    }

    /* ===================================================================
     *  Restart Queue Worker
     * =================================================================== */
    private function restartQueueWorker(): void
    {
        try {
            Artisan::call('queue:restart');
            Log::info('Queue restart signal sent');
        } catch (\Throwable $e) {
            Log::warning('Could not restart queue worker', [
                'error' => $e->getMessage(),
                'hint' => 'You may need to manually restart: php artisan queue:restart',
            ]);
        }
    }

    /* ===================================================================
     *  خواندن نسخه
     * =================================================================== */
    private function readModuleVersion(string $moduleName): ?string
    {
        $path = config('packages.modules.path').'/'.$moduleName.'/module.json';
        if (! File::exists($path)) {
            return null;
        }
        $data = json_decode(File::get($path), true);

        return $data['version'] ?? null;
    }

    /* ===================================================================
     *  پاکسازی فایل موقت
     * =================================================================== */
    private function cleanupTemp(string $zipPath): void
    {
        try {
            @unlink($zipPath);
        } catch (Exception $e) {
            // نادیده
        }
    }

    /* ===================================================================
     *  modules_statuses.json
     * =================================================================== */
    private function ensureModulesStatusesFile(): void
    {
        $statusesPath = base_path('modules_statuses.json');

        if (! file_exists($statusesPath)) {
            $installedModules = InstalledModule::where('status', 'installed')->get();

            $statuses = [];
            foreach ($installedModules as $module) {
                $moduleName = $module->name;
                if ($moduleName) {
                    $statuses[$moduleName] = (bool) ($module->is_active ?? true);
                }
            }

            if (! empty($this->currentModuleName) && ! isset($statuses[$this->currentModuleName])) {
                $statuses[$this->currentModuleName] = true;
            }

            ksort($statuses);

            file_put_contents(
                $statusesPath,
                json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            $this->step('create_statuses', 'ایجاد فایل وضعیت ماژول‌ها');

            return;
        }

        if (! empty($this->currentModuleName)) {
            $this->updateModuleStatusInFile($this->currentModuleName, true);
        }
    }

    private function updateModuleStatusInFile(string $moduleName, bool $status = true): void
    {
        $statusesPath = base_path('modules_statuses.json');

        if (! file_exists($statusesPath)) {
            $this->ensureModulesStatusesFile();

            return;
        }

        $content = file_get_contents($statusesPath);
        $statuses = json_decode($content, true) ?? [];

        $statuses[$moduleName] = $status;
        ksort($statuses);

        file_put_contents(
            $statusesPath,
            json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function rebuildModulesStatusesFile(): void
    {
        $statusesPath = base_path('modules_statuses.json');

        $installedModules = InstalledModule::all();

        $statuses = [];
        foreach ($installedModules as $module) {
            $moduleName = $module->name;
            if ($moduleName) {
                $statuses[$moduleName] = (bool) ($module->is_active ?? true);
            }
        }

        ksort($statuses);

        file_put_contents(
            $statusesPath,
            json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->step('rebuild_statuses', 'بازسازی فایل وضعیت ماژول‌ها');
    }

    /* ===================================================================
     *  لاگ‌گیری
     * =================================================================== */
    private function step(string $name, string $label): void
    {
        $this->steps[] = ['name' => $name, 'label' => $label, 'at' => now()->toDateTimeString()];
    }

    private function startLog(
        string $action,
        string $slug,
        ?int $adminId,
        ?string $fromVersion = null
    ): ModuleInstallLog {
        return ModuleInstallLog::create([
            'admin_id' => $adminId,
            'module_slug' => $slug,
            'module_name' => $slug,
            'action' => $action,
            'from_version' => $fromVersion,
            'status' => ModuleInstallLog::STATUS_RUNNING,
        ]);
    }

    private function finishLog(
        ModuleInstallLog $log,
        string $status,
        ?InstalledModule $installed = null,
        ?string $message = null
    ): void {
        $log->update([
            'status' => $status,
            'to_version' => $installed?->version,
            'message' => $message,
            'details' => ['steps' => $this->steps],
        ]);
    }

    private function failLog(ModuleInstallLog $log, Exception $e, string $slug): void
    {
        Log::error('Package install/update failed', [
            'slug' => $slug,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $log->update([
            'status' => ModuleInstallLog::STATUS_FAILED,
            'message' => $e->getMessage(),
            'details' => ['steps' => $this->steps, 'trace' => $e->getTraceAsString()],
        ]);

        $installed = InstalledModule::where('slug', $slug)->first();
        if ($installed) {
            $installed->markAsFailed($e->getMessage());
        }
    }

    private function licenseHasRemainingTime($expiresAt): bool
    {
        if (empty($expiresAt)) {
            return true; // لایسنس نامحدود → دارای اعتبار. (اگر می‌خواهید فقط لایسنسِ تاریخ‌دار آپدیت شود، false کنید)
        }
        try {
            return Carbon::parse($expiresAt)->isFuture();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /* ===================================================================
     *  نصب قالب‌ها (Templates) — فاز ۴۴، ادغام در همین سرویس در فاز ۴۵
     *
     *  جریان: دانلود ZIP (توکن یک‌بارمصرف یا download-url) → بررسی SHA-256 →
     *  اسکن امنیتی Zip-Slip → تشخیص نام قالب از ساختار ZIP → استخراج امن به
     *  themes/<Name> → ثبت در installed_themes → dump-autoload.
     *
     *  الگوی امنیتی عیناً از ThemeIndex::store (Zip-Slip) و الگوی دانلود/بکاپ
     *  عیناً از بخش پکیج‌های همین سرویس گرفته شده است.
     * =================================================================== */

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
    public function installTemplate(
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
            $zipPath = $this->downloadTemplateZip($slug, $downloadToken, $expectedHash);

            try {
                // ─── ۲) اسکن امنیتی + استخراج ───
                $themeDirName = $this->extractThemeZip($zipPath);
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

            $this->refreshThemeFramework($themeDirName);

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
    public function activateTheme(string $themeDirName): void
    {
        if (! is_safe_extension_name($themeDirName)) {
            throw new RuntimeException('نام قالب معتبر نیست.');
        }

        if (! is_dir($this->themesPath().'/'.$themeDirName)) {
            throw new RuntimeException('قالب پیدا نشد.');
        }

        // فاز ۶۸ — گارد ساختار قالب (ریشهٔ ۴۰۴ خام صفحه اصلی): کلاس provider باید با نام
        // پوشه هم‌خوانی داشته باشد؛ پوشهٔ دستیِ تغییرنام‌یافته با فضای‌نام قدیمی = تم بی‌روت.
        \App\Services\ThemeStructureGuard::assertActivatable($themeDirName, $this->themesPath());

        $targetPath = $this->themesPath()."/{$themeDirName}/src/resources/assets";
        $linkPath   = public_path("themes/{$themeDirName}");

        // فاز ۶۵ — زنجیرهٔ امن symlink→exec→کپی (File::link روی هاست‌های بدون symlink و exec کرش می‌کرد)
        if (is_dir($targetPath)) {
            \App\Services\ThemeAssetLinker::linkOrCopy($targetPath, $linkPath);
        }

        change_env('CURRENT_THEME', $themeDirName);
        Artisan::call('config:clear');
    }

    /** حذف قالب غیرفعال (قالب فعال اجازه حذف ندارد) */
    public function uninstallTheme(string $slug): bool
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

    /* ---------------- internals قالب‌ها ---------------- */

    /**
     * دانلود ZIP قالب به مسیر موقت + بررسی هش.
     *
     * @return string مسیر مطلق فایل zip موقت
     */
    private function downloadTemplateZip(string $slug, ?string $downloadToken, ?string $expectedHash): string
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
    private function extractThemeZip(string $zipPath): string
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
            $rootDir = $this->detectThemeRootDir($zip);

            if ($rootDir === null) {
                throw new RuntimeException('ساختار فایل قالب معتبر نیست (پوشه‌ی اصلی یافت نشد).');
            }

            if (! is_safe_extension_name($rootDir)) {
                throw new RuntimeException('نام قالب داخل zip معتبر نیست.');
            }

            // ۳) بکاپ نسخه‌ی قبلی (مثل بخش پکیج‌ها)
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
    private function detectThemeRootDir(ZipArchive $zip): ?string
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
    private function refreshThemeFramework(string $themeDirName): void
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

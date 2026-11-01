<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use ZipArchive;
use Exception;
use Throwable;

class ProcessUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60];
    public $maxExceptions = 2;
    public $timeout = 3600;

    protected $panelUrl;
    protected $updateCode;
    protected $currentVersion;


    public function __construct($panelUrl, $updateCode, $currentVersion)
    {
        $this->panelUrl = $panelUrl;
        $this->updateCode = $updateCode;
        $this->currentVersion = $currentVersion;
    }

    public function handle()
    {
        $zipPath = null;
        $extractPath = null;

        try {
            // مرحله 1: شروع
            Cache::put('update_step', 'شروع بروزرسانی', now()->addHours(2));
            Cache::put('update_progress', 0, now()->addHours(2));

            // 1. دریافت اطلاعات از پنل
            Cache::put('update_step', 'دریافت اطلاعات آپدیت', now()->addHours(2));
            Cache::put('update_progress', 5, now()->addHours(2));

            $response = Http::timeout(60)->get($this->panelUrl, [
                'token' => $this->updateCode,
                'version' => $this->currentVersion,
            ]);

            if (!$response->successful() || !$response->json('update_available')) {
                throw new Exception('نسخه جدیدی یافت نشد یا دسترسی غیرمجاز است.');
            }

            $data = $response->json();
            $downloadUrl = $data['download_url'];
            $newVersion = $data['version'];
            $checksum = $data['checksum'] ?? null;

            $zipPath = storage_path('app/temp/update-' . $newVersion . '.zip');
            $extractPath = storage_path('app/temp/extract-' . $newVersion);
            $backupPath = storage_path('app/backups/backup-' . $this->currentVersion . '-' . time());

            File::ensureDirectoryExists(dirname($zipPath));

            // 2. دانلود فایل با نمایش پیشرفت
            Cache::put('update_step', 'شروع دانلود فایل', now()->addHours(2));
            Cache::put('update_progress', 10, now()->addHours(2));

            $this->downloadFileWithProgress($downloadUrl, $zipPath);

            // 3. بررسی صحت فایل
            Cache::put('update_step', 'بررسی صحت فایل', now()->addHours(2));
            Cache::put('update_progress', 85, now()->addHours(2));

            if ($checksum && hash_file('sha256', $zipPath) !== $checksum) {
                throw new Exception('فایل دانلود شده معتبر نیست (checksum mismatch).');
            }

            // 4. استخراج
            Cache::put('update_step', 'در حال استخراج فایل...', now()->addHours(2));
            Cache::put('update_progress', 88, now()->addHours(2));

            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new Exception('خطا در باز کردن فایل ZIP.');
            }
            File::ensureDirectoryExists($extractPath);
            $zip->extractTo($extractPath);
            $zip->close();

            // 5. بکاپ گرفتن و نصب
            Cache::put('update_step', 'در حال نصب فایل‌ها...', now()->addHours(2));
            Cache::put('update_progress', 92, now()->addHours(2));

            File::ensureDirectoryExists($backupPath);
            $this->copyFiles($extractPath, base_path(), $backupPath);

            // 6. پاکسازی
            Cache::put('update_step', 'در حال پاکسازی فایل‌های موقت...', now()->addHours(2));
            Cache::put('update_progress', 96, now()->addHours(2));

            File::deleteDirectory($extractPath);
            File::delete($zipPath);

            // 7. ذخیره دائمی نسخه جدید
            Cache::put('update_step', 'ذخیره نسخه جدید', now()->addHours(2));
            Cache::put('update_progress', 97, now()->addHours(2));

            $this->setVersion($newVersion);

            // 8. پاک کردن OPcache
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            // 9. اجرای دستورات پس از آپدیت
            Cache::put('update_step', 'اجرای دستورات پس از بروزرسانی', now()->addHours(2));
            Cache::put('update_progress', 98, now()->addHours(2));

            /*
             * فاز ۱۴۰ — نردبان کاملِ پاک‌سازی کش پس از جایگذاری فایل‌ها.
             * قبلاً فقط cache/config/view پاک می‌شد؛ «route:cache» قدیمی از نسخهٔ
             * قبلی باقی می‌ماند و بعد از آپدیت، مسیرهای کدِ جدید با روت‌کشِ کدِ
             * قدیمی قاطی می‌شد (وضعیت ترکیبی = دقیقاً همان کلاس خطاهای
             * «Undefined variable» در ویوهای نیمه‌به‌روز پروداکشن).
             */
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // بازسازی کش کانفیگ فقط در production (هم‌قرارداد با saveGeneral فاز ۱۳۸)
            // تا سرعت production حفظ شود؛ شکستش هم هرگز آپدیت را قرمز نمی‌کند.
            if (app()->environment('production')) {
                try {
                    Artisan::call('config:cache');
                } catch (Throwable $e) {
                    report($e);
                }
            }

            // موفقیت - پاک کردن تمام فلگ‌ها
            Cache::put('update_status', 'success', now()->addHours(24));
            Cache::put('update_version', $newVersion, now()->addHours(24));
            Cache::put('update_progress', 100, now()->addHours(2));
            Cache::put('update_step', 'کامل شد ✅', now()->addHours(2));

            // پاک کردن تمام فلگ‌های وضعیت
            Cache::forget('update_processing');
            Cache::forget('update_queued');
            Cache::forget('update_job_dispatched');
            Cache::forget('update_job_id');

            \Log::info('Update completed successfully', ['version' => $newVersion]);

            // حذف Job از صف
            $this->delete();

        } catch (Exception $e) {
            // پاکسازی در صورت خطا
            if ($extractPath && File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            if ($zipPath && File::exists($zipPath)) {
                File::delete($zipPath);
            }

            // ذخیره خطا
            Cache::put('update_status', 'error', now()->addHours(24));
            Cache::put('update_error', $e->getMessage(), now()->addHours(24));
            Cache::put('update_error_details', $e->getTraceAsString(), now()->addHours(24));

            // پاک کردن تمام فلگ‌های وضعیت
            Cache::forget('update_processing');
            Cache::forget('update_queued');
            Cache::forget('update_job_dispatched');
            Cache::forget('update_job_id');

            \Log::error('Update failed', ['error' => $e->getMessage()]);

            // حذف Job از صف
            $this->delete();

            // جلوگیری از retry
            $this->fail($e);
        }
    }

    /**
     * دانلود فایل با نمایش پیشرفت
     */
    private function downloadFileWithProgress($url, $path)
    {
        $fp = fopen($path, 'wb');
        if (!$fp) {
            throw new Exception('خطا در ایجاد فایل موقت.');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) {
            if ($downloadSize > 0) {
                $percent = 10 + round(($downloaded / $downloadSize) * 75);
                $percent = min($percent, 84);

                Cache::put('update_progress', $percent, now()->addHours(2));

                $downloadedMB = round($downloaded / 1024 / 1024, 1);
                $totalMB = round($downloadSize / 1024 / 1024, 1);
                Cache::put('update_step', "در حال دانلود فایل ({$downloadedMB} MB از {$totalMB} MB)...", now()->addHours(2));
            }
        });

        $result = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($result === false) {
            throw new Exception('خطا در دانلود فایل: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('خطا در دانلود فایل: کد وضعیت ' . $httpCode);
        }

        if (!File::exists($path) || File::size($path) === 0) {
            throw new Exception('فایل دانلود شده خالی یا وجود ندارد.');
        }
    }

    private function copyFiles($source, $destination, $backupPath = null)
    {
        $excluded = [
            '.env',
            '.env.example',
            '.git',
            'storage',
            'bootstrap/cache',
            'vendor',
            'node_modules',
        ];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $totalFiles = iterator_count($files);
        $processedFiles = 0;

        foreach ($files as $file) {
            $relativePath = $files->getSubPathName();

            foreach ($excluded as $ex) {
                if ($relativePath === $ex || str_starts_with($relativePath, $ex . DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($file->isDir()) {
                File::ensureDirectoryExists($targetPath, 0755);
            } else {
                if ($backupPath && File::exists($targetPath)) {
                    $backupTarget = $backupPath . DIRECTORY_SEPARATOR . $relativePath;
                    File::ensureDirectoryExists(dirname($backupTarget), 0755);
                    File::copy($targetPath, $backupTarget);
                }

                File::ensureDirectoryExists(dirname($targetPath), 0755);
                File::copy($file->getPathname(), $targetPath);
            }

            $processedFiles++;
            if ($totalFiles > 0) {
                $installProgress = 92 + round(($processedFiles / $totalFiles) * 3);
                Cache::put('update_progress', min($installProgress, 95), now()->addHours(2));
            }
        }
    }

    private function setVersion($version)
    {
        File::put(
            storage_path('app/version.json'),
            json_encode(['version' => $version])
        );
    }
}

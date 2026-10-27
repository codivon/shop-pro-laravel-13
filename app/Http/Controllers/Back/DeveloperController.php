<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessUpdateJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DeveloperController extends Controller
{
    private $panelUrl;

    private $updateCode;

    public function __construct()
    {
        // این مقادیر را از تنظیمات یا .env بخوانید
        // فاز ۱۰۶ — سرور بروزرسانی به update.opashop.ir منتقل شد (قبلاً update.weblak.ir).
        $this->panelUrl = 'https://update.opashop.ir/api/v1/check-update';
        $this->updateCode = env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', config('self-update.updater_token'));
    }

    public function showSettings()
    {
        // Get last schedule run time
        $schedule_last_work = option('schedule_run');
        $schedule_run = false;
        $random_str = str_random(15);

        if ($schedule_last_work) {
            if (! is_object($schedule_last_work)) {
                $schedule_last_work = Carbon::createFromDate($schedule_last_work);
            }

            $diff = $schedule_last_work->diffInMinutes(now());
            $schedule_run = ($diff <= 2);
        }

        $shared_hosting_cron_enabled = option('shared_hosting_cron_enabled', 'off') === 'on';

        return view('back.developer.settings', compact(
            'schedule_run',
            'random_str',
            'shared_hosting_cron_enabled'
        ));
    }

    public function updateSettings(Request $request)
    {
        $scope = $request->input('settings_scope');

        if ($scope === 'shared_hosting') {
            // Process only shared hosting configurations
            $cronEnabled = $request->input('shared_hosting_cron_enabled') === 'on' ? 'on' : 'off';
            option_update('shared_hosting_cron_enabled', $cronEnabled);

            return response('success');
        }

        if ($scope === 'general') {
            // Update APP_DEBUG in env
            $appDebug = $request->input('app_debug_mode') === 'true' ? 'true' : 'false';
            change_env('APP_DEBUG', $appDebug);

            // Update enable_help_videos option
            $helpVideos = $request->input('enable_help_videos') === 'true' ? 'true' : 'false';
            option_update('enable_help_videos', $helpVideos);

            // Update updater token in env
            option_update('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', $request->input('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN'));
            change_env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', $request->input('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN'));

            // Update DEBUGBAR_ENABLED in env
            $debugbar = $request->input('debugbar_enabled') === 'true' ? 'true' : 'false';
            change_env('DEBUGBAR_ENABLED', $debugbar);

            // Update enable_old_updates option
            $oldUpdates = $request->input('enable_old_updates') === 'true' ? 'true' : 'false';
            option_update('enable_old_updates', $oldUpdates);
            Artisan::call('config:clear');

            return response('success');
        }

        return response('Invalid Settings Scope', 400);
    }

    public function downApplication(Request $request)
    {
        $request->validate([
            'secret' => 'required|string',
        ]);

        $down_options = $request->except(['secret']);

        foreach ($down_options as $option => $value) {
            option_update($option, $value);
        }

        Artisan::call("down --render='errors::503' --secret='$request->secret'");

        return response()->json(['secret' => $request->secret]);
    }

    public function upApplication()
    {
        Artisan::call('up');

        return response('success');
    }

    public function webpushNotification()
    {
        Artisan::call('webpush:vapid');

        return response('success');
    }

    public function showUpdater()
    {
        $token = config('self-update.updater_token');

        if (! $token) {
            toastr()->error('برای بروزرسانی نرم افزار لطفا شماره سفارش راست چین را وارد کنید.');

            return redirect()->route('admin.developer.settings');
        }

        $errors = null;

        // خواندن نسخه از فایل
        $currentVersion = $this->getVersion();
        $versionInstalled = $currentVersion;
        $description = null;
        // دریافت وضعیت آپدیت
        $isProcessing = Cache::get('update_processing', false);
        $updateStatus = Cache::get('update_status');
        $updateError = Cache::get('update_error');
        $newVersion = Cache::get('update_version');

        // درخواست به پنل برای چک کردن نسخه جدید
        $isNewVersionAvailable = false;
        $versionAvailable = $currentVersion;

        try {
            $response = Http::timeout(30)->get($this->panelUrl, [
                'token' => $this->updateCode,
                'version' => $currentVersion,
            ]);
            $data = $response->json();
            if ($response->successful()) {
                if ($data['update_available'] ?? false) {
                    $isNewVersionAvailable = true;
                    $description = $data['description'];
                    $versionAvailable = $data['version'];
                }
            }

            if (isset($data['error'])) {
                $errors = $data['error'];
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $translated = __('errors.'.$message);

            // اگر ترجمه پیدا نشد، خود پیام را نشان بده
            $errors = 'خطا رخ داده: '.(
                $translated != 'errors.'.$message ? $translated : $message
            );
        }

        return view('back.developer.updater', compact(
            'versionInstalled',
            'isNewVersionAvailable',
            'versionAvailable',
            'currentVersion',
            'isProcessing',
            'updateStatus',
            'updateError',
            'newVersion',
            'description',
            'errors'
        ));
    }

    public function updateApplication(Request $request)
    {
        // استفاده از Lock اتمیک — فقط یک درخواست می‌تواند وارد شود
        $lock = Cache::lock('update_in_progress', 1200); // 20 دقیقه

        if (! $lock->get()) {
            return response()->json([
                'status' => 'error',
                'message' => 'آپدیت دیگری در حال اجراست. لطفاً صبر کنید.',
            ], 429);
        }

        try {
            $currentVersion = $this->getVersion();

            // بررسی وضعیت موفقیت قبلی
            if (Cache::get('update_status') === 'success') {
                $lock->release();

                return response()->json([
                    'status' => 'error',
                    'message' => 'آپدیت قبلاً با موفقیت انجام شده است.',
                ], 400);
            }

            // دریافت اطلاعات از پنل
            $response = Http::timeout(30)->get($this->panelUrl, [
                'token' => $this->updateCode,
                'version' => $currentVersion,
            ]);

            if (! $response->successful() || ! $response->json('update_available')) {
                $lock->release();

                return response()->json([
                    'status' => 'error',
                    'message' => 'نسخه جدیدی یافت نشد یا دسترسی غیرمجاز است.',
                ], 403);
            }

            $data = $response->json();
            $newVersion = $data['version'];

            // تنظیم فلگ‌ها
            Cache::put('update_processing', true, now()->addHours(2));
            Cache::put('update_status', 'queued', now()->addHours(2));
            Cache::put('update_progress', 0, now()->addHours(2));
            Cache::put('update_step', 'در حال قرار گرفتن در صف...', now()->addHours(2));
            Cache::put('update_version', $newVersion, now()->addHours(2));
            Cache::forget('update_error');
            Cache::forget('update_error_details');

            // ارسال فقط یک Job
            $job = new ProcessUpdateJob($this->panelUrl, $this->updateCode, $currentVersion);
            dispatch($job);

            // آزاد کردن lock — Job داخل صف است و دیگر نیازی به lock نیست
            $lock->release();

            return response()->json([
                'status' => 'queued',
                'message' => 'درخواست بروزرسانی در صف قرار گرفت.',
                'version' => $newVersion,
            ]);

        } catch (Exception $e) {
            $lock->release();

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ارسال درخواست: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus()
    {
        $isProcessing = Cache::get('update_processing', false);
        $progress = Cache::get('update_progress', 0);
        $status = Cache::get('update_status');
        $error = Cache::get('update_error');
        $version = Cache::get('update_version');
        $step = Cache::get('update_step');
        $jobId = Cache::get('update_job_id');

        // وضعیت موفقیت
        if ($status === 'success') {
            return response()->json([
                'status' => 'success',
                'version' => $version,
                'message' => 'بروزرسانی با موفقیت انجام شد',
                'step' => 'کامل شد ✅',
                'progress' => 100,
            ]);
        }

        // وضعیت خطا
        if ($status === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $error ?? 'خطای ناشناخته',
                'details' => Cache::get('update_error_details'),
                'step' => 'خطا ❌',
                'progress' => $progress,
            ]);
        }

        // وضعیت در حال پردازش
        if ($isProcessing) {
            return response()->json([
                'status' => 'processing',
                'progress' => $progress,
                'message' => $step ?? 'در حال بروزرسانی...',
                'step' => $step ?? 'در حال بروزرسانی...',
                'job_id' => $jobId,
            ]);
        }

        // اگر Job در صف است ولی هنوز شروع نشده
        if (Cache::get('update_queued', false)) {
            return response()->json([
                'status' => 'waiting',
                'message' => 'در انتظار شروع بروزرسانی...',
                'step' => 'در انتظار شروع',
                'progress' => 0,
                'job_id' => $jobId,
            ]);
        }

        return response()->json([
            'status' => 'idle',
        ]);
    }

    public function updaterAfter()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            return response()->json([
                'status' => 'success',
                'message' => 'دستورات پس از بروزرسانی با موفقیت اجرا شدند.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در اجرای دستورات: '.$e->getMessage(),
            ], 500);
        }
    }
    /*    public function updaterAfter()
        {
            try {
                // کش‌ها را پاک کنید
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('view:clear');

                // اگر مایگریشن جدیدی دارید اجرا کنید
                // Artisan::call('migrate', ['--force' => true]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'دستورات پس از بروزرسانی با موفقیت اجرا شدند.'
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'خطا در اجرای دستورات: ' . $e->getMessage()
                ], 500);
            }
        }*/

    // متدهای کمکی
    private function getVersion()
    {
        $versionFile = storage_path('app/version.json');
        if (File::exists($versionFile)) {
            $content = json_decode(File::get($versionFile), true);

            return $content['version'] ?? config('app.version', '1.0.0');
        }

        return config('app.version', '1.0.0');
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

        foreach ($files as $file) {
            $relativePath = $files->getSubPathName();

            foreach ($excluded as $ex) {
                if ($relativePath === $ex || str_starts_with($relativePath, $ex.DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            $targetPath = $destination.DIRECTORY_SEPARATOR.$relativePath;

            if ($file->isDir()) {
                File::ensureDirectoryExists($targetPath, 0755);
            } else {
                if ($backupPath && File::exists($targetPath)) {
                    $backupTarget = $backupPath.DIRECTORY_SEPARATOR.$relativePath;
                    File::ensureDirectoryExists(dirname($backupTarget), 0755);
                    File::copy($targetPath, $backupTarget);
                }

                File::ensureDirectoryExists(dirname($targetPath), 0755);
                File::copy($file->getPathname(), $targetPath);
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

    public function checkUpdate()
    {
        $currentVersion = $this->getVersion();

        try {
            $response = Http::timeout(30)->get($this->panelUrl, [
                'token' => $this->updateCode,
                'version' => $currentVersion,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'update_available' => $data['update_available'] ?? false,
                    'version' => $data['version'] ?? null,
                    'changelog' => $data['changelog'] ?? null,
                ]);
            }
        } catch (Exception $e) {
            // خطا در ارتباط
        }

        return response()->json([
            'update_available' => false,
            'error' => 'خطا در ارتباط با سرور',
        ]);
    }

    public function resetUpdate()
    {
        Cache::forget('update_processing');
        Cache::forget('update_queued');
        Cache::forget('update_job_dispatched');
        Cache::forget('update_status');
        Cache::forget('update_progress');
        Cache::forget('update_step');
        Cache::forget('update_version');
        Cache::forget('update_error');
        Cache::forget('update_error_details');
        Cache::forget('update_job_id');

        return response()->json([
            'status' => 'success',
            'message' => 'وضعیت بروزرسانی با موفقیت ریست شد.',
        ]);
    }

    public function clearCache()
    {
        try {
            Artisan::call('optimize:clear');

            return response('success');
        } catch (Throwable $exception) {
            report($exception);

            return response('Cache Clear Failed', 500);
        }
    }
}

<?php

namespace App\Livewire\Admin\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تنظیمات توسعه‌دهنده — پورت ۱:۱ Back\DeveloperController
 * (showSettings/updateSettings[shared_hosting|general] / downApplication / upApplication /
 *  webpushNotification / clearCache / showUpdater / checkUpdate / updateApplication /
 *  updaterAfter / updateStatus / resetUpdate)
 *
 * گیت: قدیمی روت‌ها را با میدل‌ور CheckCreator می‌بست (فقط level=creator) و هیچ permission
 * «developer.*» در دیتابیس وجود ندارد → همین شرط creator اینجا اعمال شده است.
 *
 * انحراف مستندشده در clearCache: قدیمی optimize:clear را صدا می‌زد؛ طبق اسپک فاز ۷ فقط
 * cache:clear + config:clear + view:clear اجرا می‌شود (route:cache با Livewire ناسازگار است).
 */
#[Title('تنظیمات توسعه‌دهنده')]
class Developer extends Component
{
    /** آدرس پنل بروزرسانی (پورت constructor قدیمی) */
    protected string $panelUrl = 'https://update.weblak.ir/api/v1/check-update';

    /* ---------------- کرون جاب ---------------- */

    public bool $sharedHostingCronEnabled = false;

    /** زمان‌بند در ۲ دقیقه‌ی اخیر اجرا شده است؟ (پورت showSettings) */
    public bool $scheduleRun = false;

    public string $scheduleLastRun = '';

    /* ---------------- حالت بروزرسانی (down/up) ---------------- */

    public string $maintenanceSecret = '';

    public string $info_maintenance_mode_title = '';

    public string $info_maintenance_mode_description = '';

    /* ---------------- تنظیمات عمومی ---------------- */

    public string $updaterToken = '';

    public bool $appDebugMode = false;

    public bool $debugbarEnabled = false;

    public bool $enableOldUpdates = true;

    /** در قدیمی فیلدی در فرم نبود ولی هر بار «false» ذخیره می‌شد؛ اینجا مقدار فعلی حفظ می‌شود */
    public bool $enableHelpVideos = false;

    /* ---------------- بروزرسانی نرم‌افزار ---------------- */

    public string $versionInstalled = '1.0.0';

    public bool $isNewVersionAvailable = false;

    public string $versionAvailable = '';

    public ?string $updateDescription = null;

    public ?string $updateErrors = null;

    public bool $checking = false;

    public function mount(): void
    {
        $this->ensureCreator();

        // پورت showSettings
        $schedule_last_work = option('schedule_run');
        $this->scheduleLastRun = (string) ($schedule_last_work ?: '');
        if ($schedule_last_work) {
            if (! is_object($schedule_last_work)) {
                $schedule_last_work = \Carbon\Carbon::createFromDate($schedule_last_work);
            }

            $diff = $schedule_last_work->diffInMinutes(now());
            $this->scheduleRun = ($diff <= 2);
        }

        $this->sharedHostingCronEnabled = option('shared_hosting_cron_enabled', 'off') === 'on';

        // حالت بروزرسانی
        $this->maintenanceSecret = str_random(15);
        $this->info_maintenance_mode_title = (string) option('info_maintenance_mode_title', 'در حال بروزرسانی هستیم!');
        $this->info_maintenance_mode_description = (string) option('info_maintenance_mode_description', 'در حال بروزرسانی وبسایت هستیم لطفا دقایقی بعد مراجعه کنید.<br>از صبر و شکیبایی شما متشکریم.');

        // تنظیمات عمومی
        $this->updaterToken = (string) option('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN');
        $this->appDebugMode = config('app.debug') == true;
        $this->debugbarEnabled = config('debugbar.enabled') == true;
        $this->enableOldUpdates = option('enable_old_updates', 'true') == 'true';
        $this->enableHelpVideos = option('enable_help_videos', 'true') == 'true';

        // آپدیت‌کننده (پورت showUpdater — بدون اتصال شبکه در mount)
        $this->versionInstalled = $this->getVersion();
        $this->versionAvailable = $this->versionInstalled;
        $this->isNewVersionAvailable = false;
        $this->updateErrors = null;
    }

    /* ================= گیت ================= */

    private function ensureCreator(): void
    {
        $user = auth('adminPanel')->user();

        abort_unless($user && $user->level === 'creator', 403);
    }

    /* ================= ذخیره‌ها (پورت updateSettings) ================= */

    public function saveSharedHosting(): void
    {
        $this->ensureCreator();

        $cronEnabled = $this->sharedHostingCronEnabled ? 'on' : 'off';
        option_update('shared_hosting_cron_enabled', $cronEnabled);

        $this->dispatch('toast', message: 'تنظیمات صف (هاست اشتراکی) ذخیره شد.', type: 'success');
    }

    public function saveGeneral(): void
    {
        $this->ensureCreator();

        // APP_DEBUG در env
        $appDebug = $this->appDebugMode ? 'true' : 'false';
        change_env('APP_DEBUG', $appDebug);

        // enable_help_videos در option
        $helpVideos = $this->enableHelpVideos ? 'true' : 'false';
        option_update('enable_help_videos', $helpVideos);

        // توکن آپدیت‌کننده: option + env (پورت دقیق — هر دو)
        option_update('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', $this->updaterToken);
        change_env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', $this->updaterToken);

        // DEBUGBAR_ENABLED در env
        $debugbar = $this->debugbarEnabled ? 'true' : 'false';
        change_env('DEBUGBAR_ENABLED', $debugbar);

        // enable_old_updates در option
        $oldUpdates = $this->enableOldUpdates ? 'true' : 'false';
        option_update('enable_old_updates', $oldUpdates);

        Artisan::call('config:clear');

        $this->dispatch('toast', message: 'تنظیمات توسعه‌دهنده ذخیره شد.', type: 'success');
    }

    /* ================= حالت بروزرسانی (پورت downApplication/upApplication) ================= */

    public function downApplication(): void
    {
        $this->ensureCreator();

        $this->validate([
            'maintenanceSecret' => 'required|string',
        ], [
            'maintenanceSecret.required' => 'فیلد Secret الزامی است.',
        ], [
            'maintenanceSecret' => 'secret',
        ]);

        option_update('info_maintenance_mode_title', $this->info_maintenance_mode_title);
        option_update('info_maintenance_mode_description', $this->info_maintenance_mode_description);

        $secret = $this->maintenanceSecret;
        Artisan::call("down --render='errors::503' --secret='{$secret}'");

        $this->dispatch('toast', message: 'حالت بروزرسانی فعال شد. ورود از طریق آدرس مخفی: /'.$secret, type: 'warning');
    }

    public function upApplication(): void
    {
        $this->ensureCreator();

        Artisan::call('up');

        $this->dispatch('toast', message: 'حالت بروزرسانی غیرفعال شد.', type: 'success');
    }

    /* ================= وب‌پوش (پورت webpushNotification) ================= */

    public function webpushNotification(): void
    {
        $this->ensureCreator();

        Artisan::call('webpush:vapid');

        $this->dispatch('toast', message: 'کلیدهای وب پوش تنظیم شدند.', type: 'success');
    }

    /* ================= پاک‌کردن کش (پورت clearCache) ================= */

    public function clearCache(): void
    {
        $this->ensureCreator();

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            $this->dispatch('toast', message: 'کش برنامه با موفقیت پاک‌سازی شد.', type: 'success');
        } catch (\Throwable $exception) {
            report($exception);

            $this->dispatch('toast', message: 'پاک‌سازی کش ناموفق بود.', type: 'error');
        }
    }

    /* ================= آپدیت‌کننده (پورت updater) ================= */

    protected function updateCode(): ?string
    {
        return env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', config('self-update.updater_token'));
    }

    /** بررسی نسخه جدید (پورت checkUpdate + بخش showUpdater) */
    public function checkUpdate(): void
    {
        $this->ensureCreator();

        $this->checking = true;

        $currentVersion = $this->getVersion();

        try {
            $response = Http::timeout(30)->get($this->panelUrl, [
                'token' => $this->updateCode(),
                'version' => $currentVersion,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->isNewVersionAvailable = (bool) ($data['update_available'] ?? false);
                $this->versionAvailable = (string) ($data['version'] ?? $currentVersion);
                $this->updateDescription = $data['description'] ?? null;
                $this->updateErrors = isset($data['error']) ? (string) $data['error'] : null;
            } else {
                $this->updateErrors = 'خطا در ارتباط با سرور بروزرسانی (کد '.$response->status().')';
            }
        } catch (\Throwable $e) {
            // پورت رفتار قدیمی: خطای اتصال با ترجمه/پیام نمایش داده می‌شود
            $message = $e->getMessage();
            $translated = __('errors.'.$message);

            $this->updateErrors = 'خطا رخ داده: '.(
                $translated != 'errors.'.$message ? $translated : $message
            );
            $this->isNewVersionAvailable = false;
        } finally {
            $this->checking = false;
        }
    }

    /** شروع بروزرسانی (پورت updateApplication — قفل اتمیک + صف) */
    public function updateApplication(): void
    {
        $this->ensureCreator();

        $lock = Cache::lock('update_in_progress', 1200); // 20 دقیقه

        if (! $lock->get()) {
            $this->dispatch('toast', message: 'آپدیت دیگری در حال اجراست. لطفاً صبر کنید.', type: 'error');

            return;
        }

        try {
            $currentVersion = $this->getVersion();

            if (Cache::get('update_status') === 'success') {
                $this->dispatch('toast', message: 'آپدیت قبلاً با موفقیت انجام شده است.', type: 'error');

                return;
            }

            $response = Http::timeout(30)->get($this->panelUrl, [
                'token' => $this->updateCode(),
                'version' => $currentVersion,
            ]);

            if (! $response->successful() || ! $response->json('update_available')) {
                $this->dispatch('toast', message: 'نسخه جدیدی یافت نشد یا دسترسی غیرمجاز است.', type: 'error');

                return;
            }

            $newVersion = (string) $response->json('version');

            Cache::put('update_processing', true, now()->addHours(2));
            Cache::put('update_status', 'queued', now()->addHours(2));
            Cache::put('update_progress', 0, now()->addHours(2));
            Cache::put('update_step', 'در حال قرار گرفتن در صف...', now()->addHours(2));
            Cache::put('update_version', $newVersion, now()->addHours(2));
            Cache::forget('update_error');
            Cache::forget('update_error_details');

            dispatch(new \App\Jobs\ProcessUpdateJob($this->panelUrl, $this->updateCode(), $currentVersion));

            $lock->release();

            $this->dispatch('toast', message: 'درخواست بروزرسانی در صف قرار گرفت.', type: 'success');
        } catch (\Throwable $e) {
            $lock->release();
            $this->dispatch('toast', message: 'خطا در ارسال درخواست: '.$e->getMessage(), type: 'error');
        }
    }

    /** وضعیت لحظه‌ای بروزرسانی برای wire:poll (پورت updateStatus) */
    public function getUpdateStatusProperty(): array
    {
        $isProcessing = Cache::get('update_processing', false);
        $progress = Cache::get('update_progress', 0);
        $status = Cache::get('update_status');
        $error = Cache::get('update_error');
        $version = Cache::get('update_version');
        $step = Cache::get('update_step');
        $jobId = Cache::get('update_job_id');

        if ($status === 'success') {
            return [
                'status' => 'success',
                'version' => $version,
                'message' => 'بروزرسانی با موفقیت انجام شد',
                'step' => 'کامل شد ✅',
                'progress' => 100,
            ];
        }

        if ($status === 'error') {
            return [
                'status' => 'error',
                'message' => $error ?? 'خطای ناشناخته',
                'details' => Cache::get('update_error_details'),
                'step' => 'خطا ❌',
                'progress' => $progress,
            ];
        }

        if ($isProcessing) {
            return [
                'status' => 'processing',
                'progress' => $progress,
                'message' => $step ?? 'در حال بروزرسانی...',
                'step' => $step ?? 'در حال بروزرسانی...',
                'job_id' => $jobId,
            ];
        }

        if (Cache::get('update_queued', false)) {
            return [
                'status' => 'waiting',
                'message' => 'در انتظار شروع بروزرسانی...',
                'step' => 'در انتظار شروع',
                'progress' => 0,
                'job_id' => $jobId,
            ];
        }

        return ['status' => 'idle'];
    }

    /** دستورات پس از بروزرسانی (پورت updaterAfter) */
    public function updaterAfter(): void
    {
        $this->ensureCreator();

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            $this->dispatch('toast', message: 'دستورات پس از بروزرسانی با موفقیت اجرا شدند.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: 'خطا در اجرای دستورات: '.$e->getMessage(), type: 'error');
        }
    }

    /** ریست وضعیت بروزرسانی (پورت resetUpdate) */
    public function resetUpdate(): void
    {
        $this->ensureCreator();

        foreach ([
            'update_processing', 'update_queued', 'update_job_dispatched', 'update_status',
            'update_progress', 'update_step', 'update_version', 'update_error',
            'update_error_details', 'update_job_id',
        ] as $key) {
            Cache::forget($key);
        }

        $this->dispatch('toast', message: 'وضعیت بروزرسانی با موفقیت ریست شد.', type: 'success');
    }

    /* ================= متدهای کمکی (پورت) ================= */

    private function getVersion(): string
    {
        $versionFile = storage_path('app/version.json');
        if (File::exists($versionFile)) {
            $content = json_decode(File::get($versionFile), true);

            return $content['version'] ?? config('app.version', '1.0.0');
        }

        return config('app.version', '1.0.0');
    }

    /** آیا سایت در حالت بروزرسانی (down) است؟ */
    public function getMaintenanceIsDownProperty(): bool
    {
        return is_file(storage_path('framework/down'));
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.developer')
            ->layout('admin-panel::layout');
    }
}

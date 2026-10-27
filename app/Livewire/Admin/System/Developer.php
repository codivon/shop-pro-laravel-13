<?php

namespace App\Livewire\Admin\System;

use App\Jobs\ProcessUpdateJob;
use App\Models\BotTrap;
use App\Services\SecuritySettings;
use App\Support\MarketplaceSettings;
use App\Support\MultiVendorSettings;
use App\Support\PulseSettings;
use Carbon\Carbon;
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
    /** آدرس پنل بروزرسانی (پورت constructor قدیمی) — فاز ۱۰۶: منتقل‌شده به update.opashop.ir */
    protected string $panelUrl = 'https://update.opashop.ir/api/v1/check-update';

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

    /** فاز ۱۰۴ — داشبورد Pulse برای مدیران پنل (گیت viewPulse در AuthServiceProvider) */
    public bool $pulseAdminAccess = true;

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

    /* ---------------- امنیت (فاز ۴۴) ---------------- */

    public bool $securityCaptchaEnabled = true;

    public bool $securityCaptchaAdminLogin = true;

    public bool $securityCaptchaRegister = true;

    public bool $securityCaptchaCustomerLogin = false;

    public bool $securityCaptchaComments = false;

    public int $securityTimeTrapSeconds = 3;

    public bool $securityHoneypotEnabled = true;

    public bool $securityLockoutEnabled = true;

    public int $securityLockoutAdminMax = 5;

    public int $securityLockoutAdminMinutes = 15;

    public int $securityLockoutCustomerMax = 5;

    public int $securityLockoutCustomerMinutes = 1;

    public int $securityLockoutCommentsMax = 5;

    public int $securityLockoutCommentsMinutes = 10;

    public bool $securityHeadersEnabled = true;

    public bool $securityHstsEnabled = false;

    public bool $securityBotTrapEnabled = true;

    public bool $adminTwoFactorEnabled = false;

    public string $adminTwoFactorChannel = 'email';

    /* ---------------- بازارچه (فاز ۹۹) ---------------- */

    public bool $marketEnabled = true;

    public bool $marketAllowInstall = true;

    public bool $marketAllowUninstall = true;

    public bool $marketShowTemplates = true;

    public bool $marketAutoCheckUpdates = false;

    public int $marketItemsPerPage = MarketplaceSettings::DEFAULT_PER_PAGE;

    public string $marketNoticeText = '';

    /* ---------------- چندفروشندگی (فاز ۱۰۳) ---------------- */

    /** کلید اصلی — همان multi_vendor_system_status قدیمی (قرارداد 'true'/'false') */
    public bool $vendorEnabled = false;

    /** تسویهٔ خودکار فروشندگان پس از پرداخت سفارش */
    public bool $vendorAutoSettlement = true;

    /** اعتبار کمیسیون اختصاصی هر فروشنده */
    public bool $vendorSellerOverride = true;

    /** پرداخت دسته‌جمعی تسویه در صفحهٔ «درخواستی‌ها» */
    public bool $vendorBulkSettlement = true;

    /** کارت آمار تسویه در داشبورد فروشنده */
    public bool $vendorDashboardStats = true;

    /** صورت‌حساب تسویهٔ فروشنده (فاز ۱۰۶ — شفافیت مالی) */
    public bool $vendorSettlementStatement = true;

    /** گزارش فروش فروشنده (فاز ۱۰۶-۲ — درآمد زمانی/پرفروش‌ها/تبدیل) */
    public bool $vendorSellerAnalytics = true;

    /** سیگنال‌های تقاضا (فاز ۱۰۶-۳ — منتظر موجودی/کاهش قیمت) */
    public bool $vendorDemandSignals = true;

    /** دسترسی به نظرات محصولات (فاز ۱۰۶-۴ — امتیاز/توصیه/رضایت) */
    public bool $vendorReviewsAccess = true;

    /** مرکز موجودی (فاز ۱۰۶-۵ — ناموجود/کم‌موجود + حرکت انبار) */
    public bool $vendorInventoryCenter = true;

    /** خروجی اکسل محصولات فروشنده (فاز ۱۰۶-۶) */
    public bool $vendorProductsExport = true;

    /** لیدربورد مقایسهٔ فروشندگان (فاز ۱۰۶-۷ — ادمین) */
    public bool $vendorLeaderboard = true;

    /** کمیسیون پیش‌فرض سراسری ٪ — '' یعنی بدون پیش‌فرض (رفتار قبل: ۰) */
    public string $vendorDefaultCommission = '';

    /** پنل مدرن فروشندگان (فاز ۱۰۸ — لایوایر ۴ SPA مستقل از تم) */
    public bool $vendorModernPanel = true;

    /** حداقل مبلغ درخواست برداشت فروشنده (تومان) — ۰ یعنی بدون حد اضافه */
    public string $vendorMinWithdraw = '0';

    public function mount(): void
    {
        $this->ensureCreator();

        // پورت showSettings
        $schedule_last_work = option('schedule_run');
        $this->scheduleLastRun = (string) ($schedule_last_work ?: '');
        if ($schedule_last_work) {
            if (! is_object($schedule_last_work)) {
                $schedule_last_work = Carbon::createFromDate($schedule_last_work);
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

        // فاز ۱۰۴ — دسترسی مدیران به Pulse
        $this->pulseAdminAccess = PulseSettings::adminAccess();

        // آپدیت‌کننده (پورت showUpdater — بدون اتصال شبکه در mount)
        $this->versionInstalled = $this->getVersion();
        $this->versionAvailable = $this->versionInstalled;
        $this->isNewVersionAvailable = false;
        $this->updateErrors = null;

        // امنیت (فاز ۴۴)
        $this->securityCaptchaEnabled = SecuritySettings::captchaMasterEnabled();
        $this->securityCaptchaAdminLogin = SecuritySettings::captchaEnabled('admin_login');
        $this->securityCaptchaRegister = SecuritySettings::captchaEnabled('register');
        $this->securityCaptchaCustomerLogin = SecuritySettings::captchaEnabled('customer_login');
        $this->securityCaptchaComments = SecuritySettings::captchaEnabled('comments');
        $this->securityTimeTrapSeconds = SecuritySettings::timeTrapSeconds();
        $this->securityHoneypotEnabled = SecuritySettings::honeypotEnabled();
        $this->securityLockoutEnabled = SecuritySettings::lockoutEnabled();
        $this->securityLockoutAdminMax = SecuritySettings::lockoutMax('admin');
        $this->securityLockoutAdminMinutes = SecuritySettings::lockoutMinutes('admin');
        $this->securityLockoutCustomerMax = SecuritySettings::lockoutMax('customer');
        $this->securityLockoutCustomerMinutes = SecuritySettings::lockoutMinutes('customer');
        $this->securityLockoutCommentsMax = SecuritySettings::lockoutMax('comments');
        $this->securityLockoutCommentsMinutes = SecuritySettings::lockoutMinutes('comments');
        $this->securityHeadersEnabled = SecuritySettings::headersEnabled();
        $this->securityHstsEnabled = SecuritySettings::hstsEnabled();
        $this->securityBotTrapEnabled = SecuritySettings::botTrapEnabled();
        $this->adminTwoFactorEnabled = SecuritySettings::twoFactorEnabled();
        $this->adminTwoFactorChannel = SecuritySettings::twoFactorChannel();

        // بازارچه (فاز ۹۹)
        $this->marketEnabled = MarketplaceSettings::marketEnabled();
        $this->marketAllowInstall = MarketplaceSettings::allowInstall();
        $this->marketAllowUninstall = MarketplaceSettings::allowUninstall();
        $this->marketShowTemplates = MarketplaceSettings::showTemplates();
        $this->marketAutoCheckUpdates = MarketplaceSettings::autoCheckUpdates();
        $this->marketItemsPerPage = MarketplaceSettings::itemsPerPage();
        $this->marketNoticeText = MarketplaceSettings::noticeText();

        // چندفروشندگی (فاز ۱۰۳)
        $this->vendorEnabled = MultiVendorSettings::enabled();
        $this->vendorAutoSettlement = MultiVendorSettings::autoSettlement();
        $this->vendorSellerOverride = MultiVendorSettings::sellerOverrideEnabled();
        $this->vendorBulkSettlement = MultiVendorSettings::bulkSettlementEnabled();
        $this->vendorDashboardStats = MultiVendorSettings::dashboardStatsEnabled();
        $this->vendorSettlementStatement = MultiVendorSettings::settlementStatementEnabled();
        $this->vendorSellerAnalytics = MultiVendorSettings::analyticsEnabled();
        $this->vendorDemandSignals = MultiVendorSettings::demandSignalsEnabled();
        $this->vendorReviewsAccess = MultiVendorSettings::reviewsAccessEnabled();
        $this->vendorInventoryCenter = MultiVendorSettings::inventoryCenterEnabled();
        $this->vendorProductsExport = MultiVendorSettings::productsExportEnabled();
        $this->vendorLeaderboard = MultiVendorSettings::leaderboardEnabled();
        $this->vendorModernPanel = MultiVendorSettings::modernPanelEnabled();
        $this->vendorDefaultCommission = (string) (MultiVendorSettings::defaultCommission() ?? '');
        $this->vendorMinWithdraw = (string) MultiVendorSettings::minWithdrawAmount();
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

        // فاز ۱۰۴ — سوییچ دسترسی مدیران به داشبورد Pulse (/pulse)
        PulseSettings::setAdminAccess($this->pulseAdminAccess);

        Artisan::call('config:clear');

        $this->dispatch('toast', message: 'تنظیمات توسعه‌دهنده ذخیره شد.', type: 'success');
    }

    /* ================= امنیت (فاز ۴۴) ================= */

    public function saveSecurity(): void
    {
        $this->ensureCreator();

        $this->validate([
            'securityTimeTrapSeconds' => ['required', 'integer', 'between:0,300'],
            'securityLockoutAdminMax' => ['required', 'integer', 'between:1,100'],
            'securityLockoutAdminMinutes' => ['required', 'integer', 'between:1,1440'],
            'securityLockoutCustomerMax' => ['required', 'integer', 'between:1,100'],
            'securityLockoutCustomerMinutes' => ['required', 'integer', 'between:1,1440'],
            'securityLockoutCommentsMax' => ['required', 'integer', 'between:1,100'],
            'securityLockoutCommentsMinutes' => ['required', 'integer', 'between:1,1440'],
            'adminTwoFactorChannel' => ['required', 'in:email,sms,both'],
        ], [
            'securityTimeTrapSeconds.between' => 'تلهٔ زمان باید بین ۰ تا ۳۰۰ ثانیه باشد.',
            'securityLockoutAdminMax.between' => 'سقف تلاش مدیر باید بین ۱ تا ۱۰۰ باشد.',
            'securityLockoutAdminMinutes.between' => 'پنجرهٔ قفل مدیر باید بین ۱ تا ۱۴۴۰ دقیقه باشد.',
            'securityLockoutCustomerMax.between' => 'سقف تلاش مشتری باید بین ۱ تا ۱۰۰ باشد.',
            'securityLockoutCustomerMinutes.between' => 'پنجرهٔ قفل مشتری باید بین ۱ تا ۱۴۴۰ دقیقه باشد.',
            'securityLockoutCommentsMax.between' => 'سقف تلاش دیدگاه‌ها باید بین ۱ تا ۱۰۰ باشد.',
            'securityLockoutCommentsMinutes.between' => 'پنجرهٔ قفل دیدگاه‌ها باید بین ۱ تا ۱۴۴۰ دقیقه باشد.',
            'adminTwoFactorChannel.in' => 'کانال ارسال کد نامعتبر است.',
        ]);

        // کپچا
        option_update('security_captcha_enabled', $this->securityCaptchaEnabled ? 'on' : 'off');
        option_update('security_captcha_admin_login', $this->securityCaptchaAdminLogin ? 'on' : 'off');
        option_update('security_captcha_register', $this->securityCaptchaRegister ? 'on' : 'off');
        option_update('security_captcha_customer_login', $this->securityCaptchaCustomerLogin ? 'on' : 'off');
        option_update('security_captcha_comments', $this->securityCaptchaComments ? 'on' : 'off');
        option_update('security_time_trap_seconds', (string) $this->securityTimeTrapSeconds);

        // هانی‌پات
        option_update('security_honeypot_enabled', $this->securityHoneypotEnabled ? 'on' : 'off');

        // قفل تلاش‌ها
        option_update('security_lockout_enabled', $this->securityLockoutEnabled ? 'on' : 'off');
        option_update('security_lockout_admin_max', (string) $this->securityLockoutAdminMax);
        option_update('security_lockout_admin_minutes', (string) $this->securityLockoutAdminMinutes);
        option_update('security_lockout_customer_max', (string) $this->securityLockoutCustomerMax);
        option_update('security_lockout_customer_minutes', (string) $this->securityLockoutCustomerMinutes);
        option_update('security_lockout_comments_max', (string) $this->securityLockoutCommentsMax);
        option_update('security_lockout_comments_minutes', (string) $this->securityLockoutCommentsMinutes);

        // هدرهای امنیتی
        option_update('security_headers_enabled', $this->securityHeadersEnabled ? 'on' : 'off');
        option_update('security_hsts_enabled', $this->securityHstsEnabled ? 'on' : 'off');

        // تلهٔ ربات (فاز ۷۱) — کلید اصلی مسدودسازی آی‌پی
        option_update('security_bot_trap_enabled', $this->securityBotTrapEnabled ? 'on' : 'off');

        // ورود دومرحله‌ای مدیر
        option_update('admin_2fa_enabled', $this->adminTwoFactorEnabled ? 'on' : 'off');
        option_update('admin_2fa_channel', $this->adminTwoFactorChannel);

        $this->dispatch('toast', message: 'تنظیمات امنیتی ذخیره شد.', type: 'success');
    }

    /* ================= بازارچه (فاز ۹۹) ================= */

    public function saveMarketplace(): void
    {
        $this->ensureCreator();

        $this->validate([
            'marketItemsPerPage' => ['required', 'integer', 'between:1,'.MarketplaceSettings::MAX_PER_PAGE],
            'marketNoticeText' => ['nullable', 'string', 'max:500'],
        ], [
            'marketItemsPerPage.between' => 'تعداد آیتم هر صفحه باید بین ۱ تا '.MarketplaceSettings::MAX_PER_PAGE.' باشد.',
            'marketNoticeText.max' => 'متن بنر بازارچه حداکثر ۵۰۰ کاراکتر است.',
        ]);

        // کلید اصلی — گیت منو + صفحات packages/*
        option_update(MarketplaceSettings::KEY_ENABLED, $this->marketEnabled ? '1' : '0');
        option_update('packages_market_allow_install', $this->marketAllowInstall ? '1' : '0');
        option_update('packages_market_allow_uninstall', $this->marketAllowUninstall ? '1' : '0');
        option_update('packages_market_show_templates', $this->marketShowTemplates ? '1' : '0');
        option_update('packages_market_auto_check_updates', $this->marketAutoCheckUpdates ? '1' : '0');
        option_update('packages_market_items_per_page', (string) $this->marketItemsPerPage);
        option_update('packages_market_notice_text', trim((string) $this->marketNoticeText));

        $this->dispatch('toast', message: 'تنظیمات بازارچه ذخیره شد.', type: 'success');
    }

    /* ================= چندفروشندگی (فاز ۱۰۳) ================= */

    public function saveMultiVendor(): void
    {
        $this->ensureCreator();

        $this->validate([
            'vendorDefaultCommission' => ['nullable', 'integer', 'between:0,100'],
            'vendorMinWithdraw' => ['nullable', 'integer', 'between:0,50000000'],
        ], [
            'vendorDefaultCommission.integer' => 'کمیسیون پیش‌فرض باید عدد باشد.',
            'vendorDefaultCommission.between' => 'کمیسیون پیش‌فرض باید بین ۰ تا ۱۰۰ درصد باشد.',
            'vendorMinWithdraw.integer' => 'حداقل مبلغ برداشت باید عدد باشد.',
            'vendorMinWithdraw.between' => 'حداقل مبلغ برداشت باید بین ۰ تا ۵۰٬۰۰۰٬۰۰۰ تومان باشد.',
        ]);

        // کلید اصلی — همان کلید قدیمی فاز ۳۰ با قرارداد 'true'/'false'
        // (میدل‌ور Seller، سایدبار و Product.php با == 'true' می‌خوانند)
        MultiVendorSettings::setEnabled($this->vendorEnabled);

        option_update('multi_vendor_auto_settlement', $this->vendorAutoSettlement ? '1' : '0');
        option_update('multi_vendor_seller_commission_override', $this->vendorSellerOverride ? '1' : '0');
        option_update('multi_vendor_bulk_settlement', $this->vendorBulkSettlement ? '1' : '0');
        option_update('multi_vendor_dashboard_stats', $this->vendorDashboardStats ? '1' : '0');
        option_update('multi_vendor_settlement_statement', $this->vendorSettlementStatement ? '1' : '0');
        option_update('seller_analytics_enabled', $this->vendorSellerAnalytics ? '1' : '0');
        option_update('seller_demand_signals', $this->vendorDemandSignals ? '1' : '0');
        option_update('seller_reviews_access', $this->vendorReviewsAccess ? '1' : '0');
        option_update('seller_inventory_center', $this->vendorInventoryCenter ? '1' : '0');
        option_update('seller_products_export', $this->vendorProductsExport ? '1' : '0');
        option_update('multi_vendor_leaderboard', $this->vendorLeaderboard ? '1' : '0');
        option_update(MultiVendorSettings::KEY_MODERN_PANEL, $this->vendorModernPanel ? '1' : '0');
        option_update('multi_vendor_default_commission', trim((string) $this->vendorDefaultCommission) === '' ? '' : (string) max(0, min(100, (int) $this->vendorDefaultCommission)));
        option_update('multi_vendor_withdraw_min_amount', (string) max(0, (int) $this->vendorMinWithdraw));

        MultiVendorSettings::flushCache();

        $this->dispatch('toast', message: 'تنظیمات چندفروشندگی ذخیره شد.', type: 'success');
    }

    /* ================= تلهٔ ربات (فاز ۷۱) ================= */

    /**
     * لیست تله‌های فعال (۲۴ ساعت اخیر) برای نمایش در تنظیمات برنامه‌نویس.
     * حذف دستی رکورد از دیتابیس تا ۱۰ دقیقه اثر ندارد (کش)؛ رفع مسدودی از همین‌جا کش را هم پاک می‌کند.
     */
    public function getActiveTrapsProperty(): array
    {
        try {
            return BotTrap::query()
                ->where('last_hit_at', '>=', now()->subHours(BotTrap::BLOCK_TTL_HOURS))
                ->orderByDesc('last_hit_at')
                ->limit(50)
                ->get(['id', 'ip_address', 'trap_path', 'hit_count', 'last_hit_at'])
                ->toArray();
        } catch (\Throwable) {
            return []; // قبل از نصب یا هر خطای دیتابیس — صفحه نباید بشکند
        }
    }

    /** رفع مسدودی یک آی‌پی — حذف رکورد + پاک‌کردن کش (فوراً اثر می‌کند) */
    public function unblockIp(int $trapId): void
    {
        $this->ensureCreator();

        $trap = BotTrap::query()->find($trapId);

        if ($trap) {
            $ip = $trap->ip_address;
            $trap->delete();
            BotTrap::flushCache();

            $this->dispatch('toast', message: 'مسدودی آی‌پی '.$ip.' برداشته شد (کش هم پاک شد) — فوراً اثر می‌کند.', type: 'success');
        }
    }

    /** رفع مسدودی همهٔ آی‌پی‌ها — پاک‌سازی کامل تله‌ها + کش */
    public function unblockAllIps(): void
    {
        $this->ensureCreator();

        $count = BotTrap::query()->count();
        BotTrap::query()->delete();
        BotTrap::flushCache();

        $this->dispatch('toast', message: 'همهٔ مسدودی‌ها برداشته شد ('.$count.' رکورد) — کش هم پاک شد.', type: 'success');
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

            dispatch(new ProcessUpdateJob($this->panelUrl, $this->updateCode(), $currentVersion));

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

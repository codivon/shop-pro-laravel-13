<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تنظیمات دیگر — پورت ۱:۱ Back\SettingController::showOthers/updateOthers
 * + ویوی back/settings/others.blade.php
 *
 * - envهای نوشتنی: PUSHER_APP_ID/KEY/SECRET/CLUSTER + MAIL_HOST/PORT/USERNAME/PASSWORD/ENCRYPTION
 *   (+ BROADCAST_DRIVER خودکار بر اساس کامل بودن pusher — پورت دقیق)
 * - ⚠️ quirk پورت‌شده: فیلد name="transport" در ویوی قدیمی جزو env_options نیست →
 *   به‌صورت option با کلید transport ذخیره می‌شود (عیناً مثل قدیمی).
 * - فایل‌ها: factor_logo / watermarkImage از File Manager سایت انتخاب می‌شوند (مسیر رشته‌ای در option)
 * - انحراف کوچک: قدیمی در حلقه‌ی env هر کلید را بی‌قید change_env می‌کرد؛ اینجا هم همان است ولی
 *   فقط برای کلیدهای واقعاً موجود در فرم (MAIL_MAILER/MAIL_TRANSPORT در فرم قدیمی هم نبودند و
 *   $request->only آن‌ها را نمی‌آورد — رفتار یکسان ماند).
 */
#[Title('تنظیمات دیگر')]
class Others extends Component
{
    /* ---------------- فاکتور ---------------- */

    public string $factor_title = '';

    public string $factor_seller_name = '';

    public string $factor_national_code = '';

    public string $factor_registeration_id = '';

    public string $factor_economical_number = '';

    /** مقدار تازه انتخاب‌شده از File Manager (مسیر/URL رشته‌ای) — خالی یعنی «بدون تغییر» */
    public string $factor_logo = '';

    public string $current_factor_logo = '';

    /* ---------------- کاربران / معرفی ---------------- */

    public string $user_register_gift_credit = '0';

    // فاز ۳۰: بولین واقعی — سوییچ به‌جای select
    public bool $user_referrals_enable = false;

    public string $user_referrals_gift_type = 'discount_code';

    public string $user_referrals_gift_discount_type = 'percent';

    public string $owner_referrals_amount = '';

    public string $user_referrals_amount = '';

    public string $minimum_amount_gift = '0';

    public string $minimum_product_gift = '1';

    /* ---------------- تصاویر ---------------- */

    public string $optimizeImage = '10';

    public string $changePhotoFormat = 'webp';

    // فاز ۳۰: بولین واقعی — سوییچ به‌جای select
    public bool $watermarkStatus = false;

    /** مقدار تازه انتخاب‌شده از File Manager (مسیر/URL رشته‌ای) — خالی یعنی «بدون تغییر» */
    public string $watermarkImage = '';

    public string $current_watermarkImage = '';

    public string $watermarkImagePosition = 'bottom-right';

    /* ---------------- Pusher (env) ---------------- */

    public string $PUSHER_APP_ID = '';

    public string $PUSHER_APP_KEY = '';

    public string $PUSHER_APP_SECRET = '';

    public string $PUSHER_APP_CLUSTER = '';

    /* ---------------- Mail (env) ---------------- */

    public string $transport = '';

    public string $MAIL_HOST = '';

    public string $MAIL_PORT = '';

    public string $MAIL_USERNAME = '';

    public string $MAIL_PASSWORD = '';

    public string $MAIL_ENCRYPTION = '';

    /* ---------------- قابلیت‌های فروشگاه (فاز ۸۲ — از تنظیمات قالب به تنظیمات سایت منتقل شد) ---------------- */

    // نقشهٔ رنگ دکمهٔ هشدار قیمت (فاز ۸۳) — هم‌قرارداد با تم (hex جفت [روشن، تیره])
    public const PALETTE = [
        'red' => ['#ef394e', '#c8102e'],
        'orange' => ['#f97316', '#ea580c'],
        'green' => ['#10b981', '#059669'],
        'teal' => ['#06b6d4', '#0891b2'],
        'purple' => ['#8b5cf6', '#7c3aed'],
        'dark' => ['#334155', '#0f172a'],
    ];

    public const SIZES = ['sm', 'md', 'lg'];

    public const PLACEMENTS = ['below-cart', 'above-cart', 'under-price', 'float', 'custom-selector'];

    public const PLACEMENT_LABELS = [
        'below-cart' => 'زیر دکمهٔ خرید',
        'above-cart' => 'بالای دکمهٔ خرید',
        'under-price' => 'چسبیده به قیمت محصول',
        'float' => 'شناور در گوشهٔ صفحه',
        'custom-selector' => 'در تگ دلخواه (سلکتور)',
    ];

    // فاز ۸۴ — موقعیت‌های ۹گانهٔ دکمهٔ شناور (فیزیکی؛ در موبایل حاشیهٔ کوچک‌تر)
    public const FLOAT_POSITIONS = [
        'top-left', 'top-center', 'top-right',
        'middle-left', 'middle-center', 'middle-right',
        'bottom-left', 'bottom-center', 'bottom-right',
    ];

    public const FLOAT_POSITION_LABELS = [
        'top-left' => 'بالا-چپ',
        'top-center' => 'بالا-وسط',
        'top-right' => 'بالا-راست',
        'middle-left' => 'وسط-چپ',
        'middle-center' => 'وسط صفحه',
        'middle-right' => 'وسط-راست',
        'bottom-left' => 'پایین-چپ',
        'bottom-center' => 'پایین-وسط',
        'bottom-right' => 'پایین-راست',
    ];

    // سلکتورهای نمونهٔ پیشنهادی (چیپ‌های قابل‌کلیک در UI — مثل نمونهٔ CheckoutTurbo)
    public const SELECTOR_EXAMPLES = [
        '#buy-box' => 'باکس خرید دیجی‌کالا-مانند',
        '.product-seller-row' => 'ردیف فروشنده/قیمت',
        '.product-actions>.wrap' => 'اکشن‌های کارت محصول',
        '.product-carousel .owl-item' => 'اسلایدر محصولات',
    ];

    // ذخیره‌سازی «1»/«0» (هسته با option(..., '1') == '1' چک می‌کند)
    public bool $dt_price_alert_status = true;

    public string $dt_price_alert_button_text = '';

    public string $dt_price_alert_button_icon = '';

    // فاز ۸۳ — شخصی‌سازی کامل دکمه + مودال + کانال‌های اطلاع‌رسانی
    public string $dt_price_alert_button_color = 'red';

    public string $dt_price_alert_button_size = 'md';

    public string $dt_price_alert_button_radius = '10';

    public string $dt_price_alert_button_placement = 'below-cart';

    // فاز ۸۴ — موقعیت دکمهٔ شناور (۹گانه) + سلکتورهای «تگ دلخواه»
    public string $dt_price_alert_float_pos = 'bottom-right';

    /** @var array<int, string> سلکتورهای CSS (کلاس/آیدی) برای تزریق دکمه در فرانت */
    public array $dt_price_alert_selectors = [];

    /** اینپوتِ «افزودن سلکتور» (مقدار ردیف جدید قبل از تأیید) */
    public string $pa_selector_input = '';

    public string $dt_price_alert_modal_title = '';

    public string $dt_price_alert_modal_desc = '';

    public bool $dt_price_alert_channel_sms = true;

    public bool $dt_price_alert_channel_profile = true;

    public bool $dt_pre_order_status = true;

    public string $dt_pre_order_button_text = '';

    public string $dt_pre_order_badge_text = '';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('settings.others') ?: abort(403);

        // option ها — یک‌بار در mount خوانده می‌شوند
        // فاز ۳۰: سوییچ‌های بولین (مقادیر قدیمی true/false/1/0 هم پذیرفته می‌شود)
        // فاز ۱۰۸ — multi_vendor_system_status از این صفحه حذف شد (مالکیت: تب چندفروشندگی برنامه‌نویس)

        $this->factor_title = (string) (option('factor_title') ?: option('info_site_title'));
        $this->factor_seller_name = (string) option('factor_seller_name');
        $this->factor_national_code = (string) option('factor_national_code');
        $this->factor_registeration_id = (string) option('factor_registeration_id');
        $this->factor_economical_number = (string) option('factor_economical_number');
        $this->current_factor_logo = (string) option('factor_logo');

        $this->user_register_gift_credit = (string) (option('user_register_gift_credit', 0) ?: 0);
        $this->user_referrals_enable = filter_var((string) (option('user_referrals_enable') ?: 'false'), FILTER_VALIDATE_BOOLEAN);
        $this->user_referrals_gift_type = (string) (option('user_referrals_gift_type') ?: 'discount_code');
        $this->user_referrals_gift_discount_type = (string) (option('user_referrals_gift_discount_type') ?: 'percent');
        $this->owner_referrals_amount = (string) option('owner_referrals_amount');
        $this->user_referrals_amount = (string) option('user_referrals_amount');
        $this->minimum_amount_gift = (string) (option('minimum_amount_gift', 0) ?: 0);
        $this->minimum_product_gift = (string) (option('minimum_product_gift', 1) ?: 1);

        $this->optimizeImage = (string) (option('optimizeImage', '10') ?: '10');
        $this->changePhotoFormat = (string) (option('changePhotoFormat', 'webp') ?: 'webp');
        $this->watermarkStatus = filter_var((string) (option('watermarkStatus') ?: 'false'), FILTER_VALIDATE_BOOLEAN);
        $this->current_watermarkImage = (string) option('watermarkImage');
        $this->watermarkImagePosition = (string) (option('watermarkImagePosition') ?: 'bottom-right');

        // envها از config خوانده می‌شوند (پورت دقیق ویوی قدیمی)
        $this->PUSHER_APP_ID = (string) config('broadcasting.connections.pusher.app_id');
        $this->PUSHER_APP_KEY = (string) config('broadcasting.connections.pusher.key');
        $this->PUSHER_APP_SECRET = (string) config('broadcasting.connections.pusher.secret');
        $this->PUSHER_APP_CLUSTER = (string) config('broadcasting.connections.pusher.options.cluster');

        $this->transport = (string) config('mail.mailers.smtp.transport');
        $this->MAIL_HOST = (string) config('mail.mailers.smtp.host');
        $this->MAIL_PORT = (string) config('mail.mailers.smtp.port');
        $this->MAIL_USERNAME = (string) config('mail.mailers.smtp.username');
        $this->MAIL_PASSWORD = (string) config('mail.mailers.smtp.password');
        $this->MAIL_ENCRYPTION = (string) config('mail.mailers.smtp.encryption');

        // فاز ۸۲ — قابلیت‌های فروشگاه (پیش‌فرض‌ها دقیقاً مثل پیش‌فرض‌های مصرف‌کننده در هسته/تم)
        // «?:» تا اگر مقدار خالی ذخیره شده باشد، پیش‌فرض برگردد
        $this->dt_price_alert_status = option('dt_price_alert_status', '1') == '1';
        $this->dt_price_alert_button_text = (string) (option('dt_price_alert_button_text') ?: 'هشدار کاهش قیمت');
        $this->dt_price_alert_button_icon = (string) (option('dt_price_alert_button_icon') ?: 'mdi mdi-bell-ring-outline');

        // فاز ۸۳ — شخصی‌سازی دکمه/مودال/کانال‌ها (مقدار نامعتبر → پیش‌فرض)
        $this->dt_price_alert_button_color = in_array(($c = (string) option('dt_price_alert_button_color', 'red')), array_keys(self::PALETTE), true) ? $c : 'red';
        $this->dt_price_alert_button_size = in_array(($s = (string) option('dt_price_alert_button_size', 'md')), self::SIZES, true) ? $s : 'md';
        $this->dt_price_alert_button_radius = (string) min(30, max(0, (int) (option('dt_price_alert_button_radius', '10') ?: 10)));
        $this->dt_price_alert_button_placement = in_array(($p = (string) option('dt_price_alert_button_placement', 'below-cart')), self::PLACEMENTS, true) ? $p : 'below-cart';

        // فاز ۸۴ — موقعیت شناور + سلکتورهای تگ دلخواه
        $this->dt_price_alert_float_pos = in_array(($fp = (string) option('dt_price_alert_float_pos', 'bottom-right')), self::FLOAT_POSITIONS, true) ? $fp : 'bottom-right';
        $this->dt_price_alert_selectors = self::parseSelectors((string) option('dt_price_alert_selectors', ''));
        $this->pa_selector_input = '';

        $this->dt_price_alert_modal_title = (string) (option('dt_price_alert_modal_title') ?: '');
        $this->dt_price_alert_modal_desc = (string) (option('dt_price_alert_modal_desc') ?: '');

        $channels = collect(explode(',', (string) (option('dt_price_alert_channels', 'sms,profile') ?: 'sms,profile')))
            ->map(fn ($c) => trim(strtolower($c)))->values()->all();
        $this->dt_price_alert_channel_sms = in_array('sms', $channels, true);
        $this->dt_price_alert_channel_profile = in_array('profile', $channels, true);

        $this->dt_pre_order_status = option('dt_pre_order_status', '1') == '1';
        $this->dt_pre_order_button_text = (string) (option('dt_pre_order_button_text') ?: 'ثبت پیش‌فروش');
        $this->dt_pre_order_badge_text = (string) (option('dt_pre_order_badge_text') ?: 'پیش‌فروش');
    }

    /* ---------------- ذخیره (پورت updateOthers) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('settings.others') ?: abort(403);

        $env_options = [
            'PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER',
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
        ];

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // ========== 1. env ==========
        $envChanges = [];

        foreach ($env_options as $key) {
            $value = (string) $this->{$key};
            $oldValue = env($key);
            if ($oldValue != $value) {
                $envChanges[$key] = [
                    'old' => $oldValue ?: 'خالی',
                    'new' => $value ?: 'خالی',
                    'title' => $this->getOthersFieldTitle($key),
                ];
            }
            change_env($key, $value);
        }

        // بررسی BROADCAST_DRIVER (پورت دقیق)
        $oldBroadcastDriver = env('BROADCAST_DRIVER');
        if ($this->PUSHER_APP_ID && $this->PUSHER_APP_KEY && $this->PUSHER_APP_SECRET) {
            change_env('BROADCAST_DRIVER', 'pusher');
            if ($oldBroadcastDriver != 'pusher') {
                $envChanges['BROADCAST_DRIVER'] = [
                    'old' => $oldBroadcastDriver ?: 'خالی',
                    'new' => 'pusher',
                    'title' => 'درایور پخش',
                ];
            }
        } else {
            change_env('BROADCAST_DRIVER', 'log');
            if ($oldBroadcastDriver != 'log') {
                $envChanges['BROADCAST_DRIVER'] = [
                    'old' => $oldBroadcastDriver ?: 'خالی',
                    'new' => 'log',
                    'title' => 'درایور پخش',
                ];
            }
        }

        if (! empty($envChanges)) {
            $allChanges['env_settings'] = $envChanges;
        }

        // ========== 2. فایل‌ها (انتخاب از File Manager — مثل قدیمی مسیر رشته‌ای در option) ==========
        $file_inputs = [
            'factor_logo' => 'لوگو فاکتور',
            'watermarkImage' => 'تصویر واترمارک',
        ];

        $fileChanges = [];
        foreach ($file_inputs as $key => $title) {
            $newPath = trim((string) $this->{$key});

            if ($newPath !== '' && $newPath !== (string) option($key)) {
                $oldFile = option($key);

                option_update($key, $newPath);

                $this->{'current_'.$key} = $newPath;
                $this->{$key} = '';

                $fileChanges[$key] = [
                    'old' => $oldFile ?: 'بدون فایل',
                    'new' => $newPath,
                    'title' => $title,
                ];
            }
        }

        if (! empty($fileChanges)) {
            $allChanges['file_uploads'] = $fileChanges;
        }

        // ========== 3. options (پورت دقیق — شامل quirk کلید transport) ==========
        // فاز ۱۰۸ — سوییچ «سیستم چند فروشندگی» به تب «چندفروشندگی» صفحهٔ برنامه‌نویس منتقل شد
        // (فاز ۱۰۳) و از این صفحه حذف شد — اینجا دیگر مالکیت این کلید را ندارد.
        $optionKeys = [
            'factor_title', 'factor_seller_name', 'factor_national_code',
            'factor_registeration_id', 'factor_economical_number',
            'user_register_gift_credit', 'user_referrals_enable', 'user_referrals_gift_type',
            'user_referrals_gift_discount_type', 'owner_referrals_amount', 'user_referrals_amount',
            'minimum_amount_gift', 'minimum_product_gift',
            'optimizeImage', 'changePhotoFormat', 'watermarkStatus', 'watermarkImagePosition',
            'transport', // quirk قدیمی: این فیلد در env_options نبود → به‌صورت option ذخیره می‌شد
            'dt_price_alert_status', 'dt_price_alert_button_text', 'dt_price_alert_button_icon',
            'dt_price_alert_button_color', 'dt_price_alert_button_size', 'dt_price_alert_button_radius',
            'dt_price_alert_button_placement', 'dt_price_alert_modal_title', 'dt_price_alert_modal_desc',
            'dt_pre_order_status', 'dt_pre_order_button_text', 'dt_pre_order_badge_text',
        ];

        /*
         * فاز ۸۴ — «موقعیت شناور» و «سلکتورهای تگ دلخواه» جدا از حلقهٔ بالا ذخیره می‌شوند:
         * - float_pos با وایت‌لیست ۹موقعیته
         * - selectors به‌صورت JSON (هر سلکتور sanitize می‌شود — فقط کاراکترهای مجاز CSS)
         */

        // فاز ۳۰: این کلیدها سوییچ بولین دارند → مثل قدیمی "true"/"false" ذخیره شوند
        $boolOptionKeys = ['user_referrals_enable', 'watermarkStatus'];

        // فاز ۸۲ — قابلیت‌های فروشگاه: کلیدهای فعال/غیرفعال «1»/«0» ذخیره می‌شوند
        // (هسته ProcessPriceAlerts/PriceObserver/Product::isPreOrderable با == '1' چک می‌کنند)
        $oneZeroOptionKeys = ['dt_price_alert_status', 'dt_pre_order_status'];

        // فاز ۸۲ — قاعدهٔ «خالی بماند = پیش‌فرض»: هیچ متن/آیکون خالی ذخیره نمی‌شود
        $defaultOptionValues = [
            'dt_price_alert_button_text' => 'هشدار کاهش قیمت',
            'dt_price_alert_button_icon' => 'mdi mdi-bell-ring-outline',
            'dt_pre_order_button_text' => 'ثبت پیش‌فروش',
            'dt_pre_order_badge_text' => 'پیش‌فروش',
        ];

        $optionChanges = [];
        foreach ($optionKeys as $key) {
            // فاز ۳۰: سوییچ‌های بولین → "true"/"false" (فرمت داده مثل قدیمی)
            $value = in_array($key, $boolOptionKeys, true)
                ? ($this->{$key} ? 'true' : 'false')
                : (in_array($key, $oneZeroOptionKeys, true)
                    ? ($this->{$key} ? '1' : '0')
                    : (string) $this->{$key});

            // فاز ۸۲ — متن/آیکون خالی → پیش‌فرض
            if (isset($defaultOptionValues[$key]) && trim($value) === '') {
                $value = $defaultOptionValues[$key];
            }

            // فاز ۸۳/۸۴ — وایت‌لیست رنگ/اندازه/موقعیت + بازهٔ گردی گوشه‌ها
            $value = match ($key) {
                'dt_price_alert_button_color' => in_array($value, array_keys(self::PALETTE), true) ? $value : 'red',
                'dt_price_alert_button_size' => in_array($value, self::SIZES, true) ? $value : 'md',
                'dt_price_alert_button_placement' => in_array($value, self::PLACEMENTS, true) ? $value : 'below-cart',
                'dt_price_alert_button_radius' => (string) min(30, max(0, (int) $value)),
                default => $value,
            };

            $oldValue = option($key);

            if ($oldValue != $value) {
                $optionChanges[$key] = [
                    'old' => $oldValue ?: 'خالی',
                    'new' => $value ?: 'خالی',
                    'title' => $this->getOthersFieldTitle($key),
                ];
            }

            option_update($key, $value);
        }

        // فاز ۸۳ — کانال‌های اطلاع‌رسانی هشدار قیمت (dt_price_alert_channels = 'sms,profile'|'sms'|'profile')
        $channels = [];
        if ($this->dt_price_alert_channel_sms) {
            $channels[] = 'sms';
        }
        if ($this->dt_price_alert_channel_profile) {
            $channels[] = 'profile';
        }

        // هیچ کانالی انتخاب نشده → پیش‌فرض «هر دو» (قابلیت بدون کانال بی‌معناست)
        $channelsValue = $channels ? implode(',', $channels) : 'sms,profile';

        if (option('dt_price_alert_channels') != $channelsValue) {
            $optionChanges['dt_price_alert_channels'] = [
                'old' => $this->channelsLabel((string) option('dt_price_alert_channels')),
                'new' => $this->channelsLabel($channelsValue),
                'title' => $this->getOthersFieldTitle('dt_price_alert_channels'),
            ];
        }

        option_update('dt_price_alert_channels', $channelsValue);

        // فاز ۸۴ — موقعیت دکمهٔ شناور (۹گانه) + سلکتورهای تگ دلخواه (JSON)
        $floatPos = in_array($this->dt_price_alert_float_pos, self::FLOAT_POSITIONS, true) ? $this->dt_price_alert_float_pos : 'bottom-right';
        if (option('dt_price_alert_float_pos') != $floatPos) {
            $optionChanges['dt_price_alert_float_pos'] = [
                'old' => self::FLOAT_POSITION_LABELS[(string) option('dt_price_alert_float_pos', 'bottom-right')] ?? (string) option('dt_price_alert_float_pos', 'bottom-right'),
                'new' => self::FLOAT_POSITION_LABELS[$floatPos],
                'title' => $this->getOthersFieldTitle('dt_price_alert_float_pos'),
            ];
        }
        option_update('dt_price_alert_float_pos', $floatPos);

        $selectorsJson = json_encode(self::sanitizeSelectors($this->dt_price_alert_selectors), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ((string) option('dt_price_alert_selectors') != $selectorsJson) {
            $optionChanges['dt_price_alert_selectors'] = [
                'old' => (string) option('dt_price_alert_selectors') ?: 'خالی',
                'new' => $selectorsJson ?: 'خالی',
                'title' => $this->getOthersFieldTitle('dt_price_alert_selectors'),
            ];
        }
        option_update('dt_price_alert_selectors', $selectorsJson);

        if (! empty($optionChanges)) {
            $allChanges['option_settings'] = $optionChanges;
        }

        // ========== 4. لاگ نهایی (پورت دقیق) ==========
        if (! empty($allChanges)) {
            $logMessage = "مدیر {$adminName} تنظیمات عمومی سایت را به‌روزرسانی کرد: ";
            $changeGroups = [];

            if (isset($allChanges['env_settings']) && ! empty($allChanges['env_settings'])) {
                $envCount = count($allChanges['env_settings']);
                $changeGroups[] = "{$envCount} تنظیم محیطی";
            }

            if (isset($allChanges['file_uploads']) && ! empty($allChanges['file_uploads'])) {
                $fileCount = count($allChanges['file_uploads']);
                $changeGroups[] = "{$fileCount} فایل آپلودی";
            }

            if (isset($allChanges['option_settings']) && ! empty($allChanges['option_settings'])) {
                $optionCount = count($allChanges['option_settings']);
                $changeGroups[] = "{$optionCount} تنظیم دیگر";
            }

            $logMessage .= implode('، ', $changeGroups);

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات عمومی سایت را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => request()->ip(),
                ])
                ->log($logMessage);
        }

        // ری‌خوانی envها از config تازه (change_env کانفیگ را cache می‌کند)
        $this->mount();

        $this->dispatch('toast', message: 'تنظیمات عمومی سایت ذخیره شد.', type: 'success');
    }

    /** پورت دقیق getOthersFieldTitle قدیمی */
    private function getOthersFieldTitle($key)
    {
        $titles = [
            'PUSHER_APP_ID' => 'شناسه اپلیکیشن Pusher',
            'PUSHER_APP_KEY' => 'کلید اپلیکیشن Pusher',
            'PUSHER_APP_SECRET' => 'رمز اپلیکیشن Pusher',
            'PUSHER_APP_CLUSTER' => 'خوشه Pusher',
            'BROADCAST_DRIVER' => 'درایور پخش',
            'AI_TOKEN_KEY' => 'توکن هوش مصنوعی',
            'default_currency_id' => 'ارز پیش فرض',
            'default_rounding_amount' => 'مقدار گرد کردن',
            'default_rounding_type' => 'نحوه گرد کردن',
            'factor_logo' => 'لوگو فاکتور',
            'factor_title' => 'عنوان فاکتور',
            'factor_seller_name' => 'نام فروشنده در فاکتور',
            'factor_national_code' => 'شناسه ملی',
            'factor_registeration_id' => 'شناسه ثبت',
            'factor_economical_number' => 'شماره اقتصادی',
            'user_register_gift_credit' => 'اعتبار هدیه ثبت نام کاربر',
            'user_referrals_enable' => 'امکان معرفی افراد',
            'owner_referrals_amount' => 'تخفیف معرفی کننده',
            'user_referrals_amount' => 'تخفیف معرفی شونده',
            'optimizeImage' => 'درصد بهینه سازی تصاویر',
            'changePhotoFormat' => 'فرمت تبدیل تصاویر',
            'watermarkStatus' => 'وضعیت واترمارک',
            'watermarkImage' => 'تصویر واترمارک',
            'watermarkImagePosition' => 'موقعیت واترمارک',
            // فاز ۸۲ — قابلیت‌های فروشگاه
            'dt_price_alert_status' => 'قابلیت هشدار کاهش قیمت',
            'dt_price_alert_button_text' => 'متن دکمه هشدار کاهش قیمت',
            'dt_price_alert_button_icon' => 'آیکون دکمه هشدار کاهش قیمت',
            // فاز ۸۳ — شخصی‌سازی کامل
            'dt_price_alert_button_color' => 'رنگ دکمه هشدار کاهش قیمت',
            'dt_price_alert_button_size' => 'اندازه دکمه هشدار کاهش قیمت',
            'dt_price_alert_button_radius' => 'گردی گوشه‌های دکمه هشدار کاهش قیمت',
            'dt_price_alert_button_placement' => 'موقعیت دکمه هشدار کاهش قیمت',
            'dt_price_alert_float_pos' => 'موقعیت دکمه شناور هشدار کاهش قیمت',
            'dt_price_alert_selectors' => 'سلکتورهای تزریق دکمه هشدار کاهش قیمت',
            'dt_price_alert_modal_title' => 'عنوان مودال هشدار کاهش قیمت',
            'dt_price_alert_modal_desc' => 'توضیح مودال هشدار کاهش قیمت',
            'dt_price_alert_channels' => 'راه‌های اطلاع‌رسانی هشدار کاهش قیمت',
            'dt_pre_order_status' => 'قابلیت پیش‌فروش محصولات',
            'dt_pre_order_button_text' => 'متن دکمه پیش‌فروش',
            'dt_pre_order_badge_text' => 'متن نشان پیش‌فروش',
        ];

        return $titles[$key] ?? $key;
    }

    /** فاز ۸۳ — برچسب فارسی کانال‌ها برای لاگ فعالیت */
    private function channelsLabel(string $raw): string
    {
        $labels = [
            'sms' => 'پیامک',
            'profile' => 'اعلان پروفایل',
        ];

        $channels = collect(explode(',', $raw))->map(fn ($c) => trim($c))->filter()->map(fn ($c) => $labels[$c] ?? $c);

        return $channels->isNotEmpty() ? $channels->implode(' + ') : 'هر دو (پیامک + اعلان پروفایل)';
    }

    /* ================= فاز ۸۴ — سلکتورهای «تگ دلخواه» ================= */

    /**
     * حداکثر تعداد سلکتورها (ضد سوءاستفاده/شلوغی UI)
     */
    public const SELECTORS_MAX = 10;

    /**
     * پارس رشتهٔ ذخیره‌شدهٔ option (JSON آرایه یا رشتهٔ خط‌جداکنندهٔ قدیمی) → آرایهٔ تمیز
     */
    public static function parseSelectors(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $items = str_starts_with($raw, '[')
            ? (array) json_decode($raw, true)
            : (preg_split('/[\r\n,]+/', $raw) ?: []);

        return self::sanitizeSelectors(is_array($items) ? $items : []);
    }

    /**
     * پاک‌سازی سلکتورها: فقط کاراکترهای مجاز CSS (حروف/رقم/خط‌تیره/زیرخط/نقطه/هش/
     * براکت/کولن/اسپیس/Combine های > + ~) — حداکثر ۱۰ مورد، هر کدام تا ۱۲۰ کاراکتر، بدون تکرار
     */
    public static function sanitizeSelectors(array $items): array
    {
        $clean = [];

        foreach ($items as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $selector = trim((string) $item);

            // کاراکترهای خطرناک (برای جلوگیری از XSS در data-attribute/JS) حذف می‌شوند
            $selector = preg_replace('/[^A-Za-z0-9\-_\.\#\[\]\:"\' >+~*(),=\$\|]/', '', $selector) ?? '';
            $selector = trim(preg_replace('/\s+/', ' ', $selector) ?? '');

            if ($selector === '' || mb_strlen($selector) > 120) {
                continue;
            }

            $clean[$selector] = true;

            if (count($clean) >= self::SELECTORS_MAX) {
                break;
            }
        }

        return array_keys($clean);
    }

    /**
     * افزودن سلکتور از اینپوتِ «افزودن سلکتور» (فوراً در لیست + ذخیرهٔ option)
     */
    public function paAddSelector(): void
    {
        auth('adminPanel')->user()?->can('settings.others') ?: abort(403);

        $this->dt_price_alert_selectors = self::sanitizeSelectors(array_merge(
            $this->dt_price_alert_selectors,
            [$this->pa_selector_input]
        ));

        $this->pa_selector_input = '';

        // ذخیرهٔ فوری تا با رفرش صفحه از دست نرود (مثل کانال‌ها فقط با «ذخیره» نمی‌ماند)
        option_update('dt_price_alert_selectors', json_encode($this->dt_price_alert_selectors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->dispatch('toast', message: 'سلکتور اضافه شد (برای اعمال در فروشگاه، ذخیره کنید).', type: 'success');
    }

    /**
     * افزودن یکی از سلکتورهای نمونه (چیپ‌ها)
     */
    public function paAddExampleSelector(string $selector): void
    {
        auth('adminPanel')->user()?->can('settings.others') ?: abort(403);

        if (! array_key_exists($selector, self::SELECTOR_EXAMPLES)) {
            return;
        }

        $this->dt_price_alert_selectors = self::sanitizeSelectors(array_merge($this->dt_price_alert_selectors, [$selector]));
        option_update('dt_price_alert_selectors', json_encode($this->dt_price_alert_selectors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * حذف سلکتور با اندیس (دکمهٔ سطل زباله)
     */
    public function paRemoveSelector(int $index): void
    {
        auth('adminPanel')->user()?->can('settings.others') ?: abort(403);

        unset($this->dt_price_alert_selectors[$index]);
        $this->dt_price_alert_selectors = array_values($this->dt_price_alert_selectors);

        option_update('dt_price_alert_selectors', json_encode($this->dt_price_alert_selectors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.others')
            ->layout('admin-panel::layout');
    }
}

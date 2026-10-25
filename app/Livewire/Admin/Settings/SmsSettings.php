<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تنظیمات پیامک — پورت ۱:۱ Back\SettingController::showSms/updateSms
 * + ویوی back/settings/sms.blade.php و پارشیال‌های back/settings/partials/*-sms
 *
 * کلیدهای options دقیقاً همان قدیمی‌ها هستند:
 *  - اصلی: sms_panel_provider / admin_mobile_number
 *  - اطلاعات هر پنل: SMS_PANEL_USERNAME/PASSWORD/FROM ، KAVENEGAR_PANEL_APIKEY ،
 *    MELIPAYAMAK_PANEL_USERNAME/PASSWORD ، IDEHPARDAZAN_PANEL_APIKEY/SECRET_KEY ،
 *    FARAZSMS_PANEL_API_KEY/FROM
 *  - چک‌باکس‌ها: ۱۴ کلید قدیمی ($except) با مقدار on/off + happy_birthday_sms
 *  - پترن‌ها: {name}_pattern_code[_{provider}] دقیقاً مطابق پارشیال‌های قدیمی
 *
 * انحراف مستندشده: happy_birthday_sms در حلقه‌ی چک‌باکس‌های قدیمی نبود (وقتی تیک برمی‌داشت
 * هرگز خاموش نمی‌شد — باگ قدیمی)؛ اینجا مثل بقیه‌ی کلیدها on/off ذخیره می‌شود.
 */
#[Title('تنظیمات پیامک')]
class SmsSettings extends Component
{
    /** مقادیر متنی (ارائه‌دهنده، موبایل مدیر، اطلاعات پنل‌ها، پترن‌ها) */
    public array $sms = [];

    /** چک‌باکس‌های on/off */
    public array $toggles = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        foreach (array_keys($this->textKeys()) as $key) {
            $this->sms[$key] = (string) option($key, $key === 'sms_panel_provider' ? 'ippanel' : '');
        }

        foreach ($this->toggleKeys() as $key) {
            $this->toggles[$key] = option($key) == 'on';
        }
    }

    /** ارائه‌دهنده‌ها به ترتیب ویوی قدیمی */
    public static function providers(): array
    {
        return [
            'ippanel' => 'ippanel',
            'kavenegar' => 'کاوه نگار',
            'melipayamak' => 'ملی پیامک',
            'farazsms' => 'فراز اس ام اس',
            'idehpardazan' => 'ایده پردازان',
        ];
    }

    /** کلیدهای متنی که در main loop قدیمی ذخیره می‌شدند (credentials + پترن‌ها) */
    public function textKeys(): array
    {
        $keys = [
            'sms_panel_provider' => 'ippanel',
            'admin_mobile_number' => '',
        ];

        foreach (array_keys(self::providers()) as $provider) {
            foreach (self::credentials($provider) as $credential) {
                $keys[$credential['key']] = '';
            }
        }

        foreach (self::patternDefinitions() as $provider => $patterns) {
            foreach ($patterns as $key => $meta) {
                $keys[$key] = '';
            }
        }

        return $keys;
    }

    /** ۱۴ کلید چک‌باکس قدیمی ($except) + happy_birthday_sms */
    public function toggleKeys(): array
    {
        return [
            'sms_on_user_register', 'sms_on_seller_register', 'sms_to_verify_user',
            'forgot_password_link', 'login_with_code',
            'sms_on_order_paid', 'sms_on_order_cancelled',
            'seller_sms_on_order_paid', 'seller_sms_on_order_cancelled',
            'user_sms_on_order_paid', 'user_sms_on_order_cancelled',
            'wallet_increase_sms', 'wallet_decrease_sms', 'wallet_refund_sms',
            'happy_birthday_sms',
        ];
    }

    /** اطلاعات اتصال هر پنل (پورت پارشیال‌های قدیمی) */
    public static function credentials(string $provider): array
    {
        return match ($provider) {
            'ippanel' => [
                ['key' => 'SMS_PANEL_USERNAME', 'label' => 'نام کاربری'],
                ['key' => 'SMS_PANEL_PASSWORD', 'label' => 'رمز عبور'],
                ['key' => 'SMS_PANEL_FROM', 'label' => 'شماره ارسالی'],
            ],
            'kavenegar' => [
                ['key' => 'KAVENEGAR_PANEL_APIKEY', 'label' => 'کلید وب سرویس'],
            ],
            'melipayamak' => [
                ['key' => 'MELIPAYAMAK_PANEL_USERNAME', 'label' => 'نام کاربری'],
                ['key' => 'MELIPAYAMAK_PANEL_PASSWORD', 'label' => 'رمز عبور'],
            ],
            'idehpardazan' => [
                ['key' => 'IDEHPARDAZAN_PANEL_APIKEY', 'label' => 'کلید وب سرویس (Api key)'],
                ['key' => 'IDEHPARDAZAN_PANEL_SECRET_KEY', 'label' => 'کد امنیتی (Security code)'],
            ],
            'farazsms' => [
                ['key' => 'FARAZSMS_PANEL_API_KEY', 'label' => 'کلید API (Api-Key)'],
                ['key' => 'FARAZSMS_PANEL_FROM', 'label' => 'شماره خط ارسالی (line_number)'],
            ],
            default => [],
        };
    }

    /**
     * تعریف پترن‌های هر ارائه‌دهنده — پورت دقیق پارشیال‌های قدیمی
     * (نام کلید + برچسب + toggleClass + required + متن نمونه با {site})
     */
    public static function patternDefinitions(): array
    {
        return [
            'ippanel' => [
                'seller_register_pattern_code' => ['label' => 'کد پترن خوش آمدگویی فروشنده', 'toggle' => 'sms_on_seller_register', 'required' => true, 'sample' => "%fullname% فروشنده عزیز خوش آمدید.\r\n {site}"],
                'user_register_pattern_code' => ['label' => 'کد پترن خوش آمدگویی کاربر', 'toggle' => 'sms_on_user_register', 'required' => true, 'sample' => "%fullname% عزیز خوش آمدید.\r\n {site}"],
                'user_verify_pattern_code' => ['label' => 'کد پترن ارسال کد تایید', 'sample' => "کد تایید: %code% \r\n {site}"],
                'order_paid_pattern_code' => ['label' => 'کد پترن پرداخت سفارش (ادمین)', 'toggle' => 'sms_on_order_paid', 'required' => true, 'sample' => "سفارش جدید با شماره سفارش %order_id% ثبت و پرداخت شد.\r\n {site}"],
                'seller_order_paid_pattern_code' => ['label' => 'کد پترن پرداخت سفارش (فروشنده)', 'toggle' => 'seller_sms_on_order_paid', 'required' => true, 'sample' => "سفارش شما با شماره سفارش %order_id% با موفقیت ثبت شد.\r\n {site}"],
                'user_order_paid_pattern_code' => ['label' => 'کد پترن پرداخت سفارش (کاربر)', 'toggle' => 'user_sms_on_order_paid', 'required' => true, 'sample' => "سفارش شما با شماره سفارش %order_id% با موفقیت ثبت شد.\r\n {site}"],
                'order_cancelled_pattern_code' => ['label' => 'کد پترن لغو سفارش (ادمین)', 'toggle' => 'sms_on_order_cancelled', 'sample' => "سفارش شماره %order_id% لغو شد.\r\n {site}"],
                'seller_order_cancelled_pattern_code' => ['label' => 'کد پترن لغو سفارش (فروشنده)', 'toggle' => 'seller_sms_on_order_cancelled', 'sample' => "سفارش شماره %order_id% لغو شد. در صورت نیاز با پشتیبانی تماس بگیرید.\r\n {site}"],
                'user_order_cancelled_pattern_code' => ['label' => 'کد پترن لغو سفارش (کاربر)', 'toggle' => 'user_sms_on_order_cancelled', 'sample' => "سفارش شماره %order_id% به دلیل %reason% لغو شد. مبلغ %refund_amount% تومان به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_refund_pattern_code' => ['label' => 'کد پترن برگشت وجه به کیف پول', 'toggle' => 'wallet_refund_sms', 'sample' => "مبلغ %amount% تومان بابت لغو سفارش %order_id% به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_increase_pattern_code_ippanel' => ['label' => 'کد متن افزایش موجودی کیف پول', 'toggle' => 'wallet_increase_sms', 'required' => true, 'sample' => "مبلغ %amount% تومان به اعتبار کیف پول شما اضافه شد.\r\n {site}"],
                'wallet_decrease_pattern_code_ippanel' => ['label' => 'کد متن کاهش موجودی کیف پول', 'toggle' => 'wallet_decrease_sms', 'required' => true, 'sample' => "مبلغ %amount% تومان از اعتبار کیف پول شما کسر شد.\r\n {site}"],
                'happy_birthday_pattern_code_ippanel' => ['label' => 'کد پترن تبریک تولد', 'toggle' => 'happy_birthday_sms', 'required' => true, 'sample' => "%fullname% عزیز زندگی بسیار کوتاه است از هر لحظه آن لذت ببرید و با تکیه بر تجربه های سال های گذشته سال های آتی زندگی را به بهترین شکل ممکن بگذرانید تولدتان مبارک\r\n {site}"],
                'price_alert_pattern_code' => ['label' => 'کد پترن هشدار کاهش قیمت', 'sample' => "قیمت محصول %title% به %price% تومان کاهش یافت.\r\n {site}"],
                'cart_recovery_pattern_code' => ['label' => 'کد پترن یادآور سبد خرید رهاشده (پکیج مارکتینگ)', 'sample' => "%name% عزیز، سبد خرید شما (%items%) در انتظار شماست. مبلغ: %total% تومان.\r\n {site}"],
                'marketing_campaign_pattern_code' => ['label' => 'کد پترن کمپین پیامکی (پکیج مارکتینگ)', 'sample' => "%name% عزیز، %store% برای شما پیشنهاد ویژه دارد!\r\n {site}"],
            ],
            'kavenegar' => [
                'seller_register_pattern_code_kavenegar' => ['label' => 'الگوی خوش آمدگویی فروشنده', 'toggle' => 'sms_on_seller_register', 'required' => true, 'sample' => "فروشنده عزیز با عنوان فروشگاه %token2، خوش آمدید.\r\n {site}"],
                'user_register_pattern_code_kavenegar' => ['label' => 'الگوی خوش آمدگویی کاربر', 'toggle' => 'sms_on_user_register', 'required' => true, 'sample' => "%token2 عزیز خوش آمدید با شماره موبایل %token.\r\n {site}"],
                'user_verify_pattern_code_kavenegar' => ['label' => 'الگوی ارسال کد تایید', 'sample' => "کد تایید: %token \r\n {site}"],
                'order_paid_pattern_code_kavenegar' => ['label' => 'الگوی پرداخت سفارش', 'toggle' => 'sms_on_order_paid', 'required' => true, 'sample' => "سفارش جدید با شماره سفارش %token ثبت و پرداخت شد.\r\n {site}"],
                'seller_order_paid_pattern_code_kavenegar' => ['label' => 'الگوی پرداخت سفارش برای فروشنده', 'toggle' => 'seller_sms_on_order_paid', 'required' => true, 'sample' => "سفارش شما با شماره سفارش %token با موفقیت ثبت شد.\r\n {site}"],
                'user_order_paid_pattern_code_kavenegar' => ['label' => 'الگوی پرداخت سفارش برای کاربر', 'toggle' => 'user_sms_on_order_paid', 'required' => true, 'sample' => "سفارش شما با شماره سفارش %token با موفقیت ثبت شد.\r\n {site}"],
                'order_cancelled_pattern_code_kavenegar' => ['label' => 'الگوی لغو سفارش (ادمین)', 'toggle' => 'sms_on_order_cancelled', 'sample' => "سفارش شماره %token لغو شد.\r\n {site}"],
                'seller_order_cancelled_pattern_code_kavenegar' => ['label' => 'الگوی لغو سفارش (فروشنده)', 'toggle' => 'seller_sms_on_order_cancelled', 'sample' => "سفارش شماره %token لغو شد. در صورت نیاز با پشتیبانی تماس بگیرید.\r\n {site}"],
                'user_order_cancelled_pattern_code_kavenegar' => ['label' => 'الگوی لغو سفارش (کاربر)', 'toggle' => 'user_sms_on_order_cancelled', 'sample' => "سفارش شماره %token به دلیل %token2 لغو شد. مبلغ %token3 تومان به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_refund_pattern_code_kavenegar' => ['label' => 'الگوی برگشت وجه به کیف پول', 'toggle' => 'wallet_refund_sms', 'sample' => "مبلغ %token تومان بابت لغو سفارش %token2 به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_increase_pattern_code_kavenegar' => ['label' => 'کد متن افزایش موجودی کیف پول', 'toggle' => 'wallet_increase_sms', 'required' => true, 'sample' => "مبلغ %token تومان به اعتبار کیف پول شما اضافه شد.\r\n {site}"],
                'wallet_decrease_pattern_code_kavenegar' => ['label' => 'کد متن کاهش موجودی کیف پول', 'toggle' => 'wallet_decrease_sms', 'required' => true, 'sample' => "مبلغ %token تومان از اعتبار کیف پول شما کسر شد.\r\n {site}"],
                'happy_birthday_pattern_code_kavenegar' => ['label' => 'کد پترن تبریک تولد', 'toggle' => 'happy_birthday_sms', 'required' => true, 'sample' => "%token عزیز زندگی بسیار کوتاه است از هر لحظه آن لذت ببرید و با تکیه بر تجربه های سال های گذشته سال های آتی زندگی را به بهترین شکل ممکن بگذرانید تولدتان مبارک\r\n {site}"],
                'price_alert_pattern_code' => ['label' => 'الگوی هشدار کاهش قیمت', 'sample' => "قیمت محصول به %token2 تومان کاهش یافت.\r\n {site}"],
                'cart_recovery_pattern_code' => ['label' => 'الگوی یادآور سبد خرید رهاشده (پکیج مارکتینگ)', 'sample' => "%token عزیز، سبد خرید شما (%token2) در انتظار شماست. مبلغ: %token3 تومان.\r\n {site}"],
                'marketing_campaign_pattern_code' => ['label' => 'الگوی کمپین پیامکی (پکیج مارکتینگ)', 'sample' => "%token عزیز، %token2 برای شما پیشنهاد ویژه دارد!\r\n {site}"],
            ],
            'melipayamak' => [
                'seller_register_pattern_code_melipayamak' => ['label' => 'کد متن خوش آمدگویی فروشنده', 'toggle' => 'sms_on_seller_register', 'required' => true, 'sample' => "{0} فروشنده عزیز خوش آمدید. \r\n {site}"],
                'user_register_pattern_code_melipayamak' => ['label' => 'کد متن خوش آمدگویی کاربر', 'toggle' => 'sms_on_user_register', 'required' => true, 'sample' => "{0} عزیز خوش آمدید. \r\n {site}"],
                'user_verify_pattern_code_melipayamak' => ['label' => 'کد متن ارسال کد تایید', 'sample' => "کد تایید: {0} \r\n {site}"],
                'order_paid_pattern_code_melipayamak' => ['label' => 'کد متن پرداخت سفارش', 'toggle' => 'sms_on_order_paid', 'required' => true, 'sample' => "سفارش جدید با شماره سفارش {0} ثبت و پرداخت شد.\r\n {site}"],
                'seller_order_paid_pattern_code_melipayamak' => ['label' => 'کد متن پرداخت سفارش برای فروشنده', 'toggle' => 'seller_sms_on_order_paid', 'required' => true, 'sample' => "سفارش شما با شماره سفارش {0} با موفقیت ثبت شد.\r\n {site}"],
                'user_order_paid_pattern_code_melipayamak' => ['label' => 'کد متن پرداخت سفارش برای کاربر', 'toggle' => 'user_sms_on_order_paid', 'required' => true, 'sample' => "سفارش شما با شماره سفارش {0} با موفقیت ثبت شد.\r\n {site}"],
                'order_cancelled_pattern_code_melipayamak' => ['label' => 'کد متن لغو سفارش (ادمین)', 'toggle' => 'sms_on_order_cancelled', 'sample' => "سفارش شماره {0} لغو شد.\r\n {site}"],
                'seller_order_cancelled_pattern_code_melipayamak' => ['label' => 'کد متن لغو سفارش (فروشنده)', 'toggle' => 'seller_sms_on_order_cancelled', 'sample' => "سفارش شماره {0} لغو شد. در صورت نیاز با پشتیبانی تماس بگیرید.\r\n {site}"],
                'user_order_cancelled_pattern_code_melipayamak' => ['label' => 'کد متن لغو سفارش (کاربر)', 'toggle' => 'user_sms_on_order_cancelled', 'sample' => "سفارش شماره {0} به دلیل {1} لغو شد. مبلغ {2} تومان به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_refund_pattern_code_melipayamak' => ['label' => 'کد متن برگشت وجه به کیف پول', 'toggle' => 'wallet_refund_sms', 'sample' => "مبلغ {0} تومان بابت لغو سفارش {1} به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_increase_pattern_code_melipayamak' => ['label' => 'کد متن افزایش موجودی کیف پول', 'toggle' => 'wallet_increase_sms', 'required' => true, 'sample' => "مبلغ {0} تومان به اعتبار کیف پول شما اضافه شد.\r\n {site}"],
                'wallet_decrease_pattern_code_melipayamak' => ['label' => 'کد متن کاهش موجودی کیف پول', 'toggle' => 'wallet_decrease_sms', 'required' => true, 'sample' => "مبلغ {0} تومان از اعتبار کیف پول شما کسر شد.\r\n {site}"],
                'happy_birthday_pattern_code_melipayamak' => ['label' => 'کد پترن تبریک تولد', 'toggle' => 'happy_birthday_sms', 'required' => true, 'sample' => "{0} عزیز زندگی بسیار کوتاه است از هر لحظه آن لذت ببرید و با تکیه بر تجربه های سال های گذشته سال های آتی زندگی را به بهترین شکل ممکن بگذرانید تولدتان مبارک\r\n {site}"],
                'price_alert_pattern_code' => ['label' => 'کد متن هشدار کاهش قیمت', 'sample' => "قیمت محصول به {1} تومان کاهش یافت.\r\n {site}"],
                'cart_recovery_pattern_code' => ['label' => 'کد متن یادآور سبد خرید رهاشده (پکیج مارکتینگ)', 'sample' => "{0} عزیز، سبد خرید شما ({1}) در انتظار شماست. مبلغ: {2} تومان.\r\n {site}"],
                'marketing_campaign_pattern_code' => ['label' => 'کد متن کمپین پیامکی (پکیج مارکتینگ)', 'sample' => "{0} عزیز، {1} برای شما پیشنهاد ویژه دارد!\r\n {site}"],
            ],
            'idehpardazan' => [
                'user_register_pattern_code_idehpardazan' => ['label' => 'کد متن خوش آمدگویی', 'toggle' => 'sms_on_user_register', 'required' => true, 'sample' => "[fullname] عزیز خوش آمدید.\r\n {site}"],
                'user_verify_pattern_code_idehpardazan' => ['label' => 'کد متن ارسال کد تایید', 'sample' => "کد تایید: [code] \r\n {site}"],
                'order_paid_pattern_code_idehpardazan' => ['label' => 'کد متن پرداخت سفارش', 'toggle' => 'sms_on_order_paid', 'required' => true, 'sample' => "سفارش جدید با شماره سفارش [order_id] ثبت و پرداخت شد.\r\n {site}"],
                'seller_order_paid_pattern_code_idehpardazan' => ['label' => 'کد متن پرداخت سفارش برای فروشنده', 'toggle' => 'seller_sms_on_order_paid', 'required' => true, 'sample' => "سفارش شما با شماره سفارش [order_id] با موفقیت ثبت شد.\r\n {site}"],
                'user_order_paid_pattern_code_idehpardazan' => ['label' => 'کد متن پرداخت سفارش برای کاربر', 'toggle' => 'user_sms_on_order_paid', 'required' => true, 'sample' => "سفارش شما با شماره سفارش [order_id] با موفقیت ثبت شد.\r\n {site}"],
                'order_cancelled_pattern_code_idehpardazan' => ['label' => 'کد متن لغو سفارش (ادمین)', 'toggle' => 'sms_on_order_cancelled', 'sample' => "سفارش شماره [order_id] لغو شد.\r\n {site}"],
                'seller_order_cancelled_pattern_code_idehpardazan' => ['label' => 'کد متن لغو سفارش (فروشنده)', 'toggle' => 'seller_sms_on_order_cancelled', 'sample' => "سفارش شماره [order_id] لغو شد. در صورت نیاز با پشتیبانی تماس بگیرید.\r\n {site}"],
                'user_order_cancelled_pattern_code_idehpardazan' => ['label' => 'کد متن لغو سفارش (کاربر)', 'toggle' => 'user_sms_on_order_cancelled', 'sample' => "سفارش شماره [order_id] به دلیل [reason] لغو شد. مبلغ [refund_amount] تومان به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_refund_pattern_code_idehpardazan' => ['label' => 'کد متن برگشت وجه به کیف پول', 'toggle' => 'wallet_refund_sms', 'sample' => "مبلغ [amount] تومان بابت لغو سفارش [order_id] به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_increase_pattern_code_idehpardazan' => ['label' => 'کد متن افزایش موجودی کیف پول', 'toggle' => 'wallet_increase_sms', 'required' => true, 'sample' => "مبلغ [amount] تومان به اعتبار کیف پول شما اضافه شد.\r\n {site}"],
                'wallet_decrease_pattern_code_idehpardazan' => ['label' => 'کد متن کاهش موجودی کیف پول', 'toggle' => 'wallet_decrease_sms', 'required' => true, 'sample' => "مبلغ [amount] تومان از اعتبار کیف پول شما کسر شد.\r\n {site}"],
                'happy_birthday_pattern_code_idehpardazan' => ['label' => 'کد پترن تبریک تولد', 'toggle' => 'happy_birthday_sms', 'required' => true, 'sample' => "[fullname] عزیز زندگی بسیار کوتاه است از هر لحظه آن لذت ببرید و با تکیه بر تجربه های سال های گذشته سال های آتی زندگی را به بهترین شکل ممکن بگذرانید تولدتان مبارک\r\n {site}"],
                'price_alert_pattern_code' => ['label' => 'کد متن هشدار کاهش قیمت', 'sample' => "قیمت محصول به [price] تومان کاهش یافت.\r\n {site}"],
                'cart_recovery_pattern_code' => ['label' => 'کد متن یادآور سبد خرید رهاشده (پکیج مارکتینگ)', 'sample' => "[name] عزیز، سبد خرید شما ([items]) در انتظار شماست. مبلغ: [total] تومان.\r\n {site}"],
                'marketing_campaign_pattern_code' => ['label' => 'کد متن کمپین پیامکی (پکیج مارکتینگ)', 'sample' => "[name] عزیز، [store] برای شما پیشنهاد ویژه دارد!\r\n {site}"],
            ],
            'farazsms' => [
                'seller_register_pattern_code_farazsms' => ['label' => 'کد پترن خوش آمدگویی فروشنده', 'toggle' => 'sms_on_seller_register', 'sample' => "%fullname% فروشنده عزیز خوش آمدید.\r\n {site}"],
                'user_register_pattern_code_farazsms' => ['label' => 'کد پترن خوش آمدگویی کاربر', 'toggle' => 'sms_on_user_register', 'sample' => "%fullname% عزیز خوش آمدید.\r\n {site}"],
                'user_verify_pattern_code_farazsms' => ['label' => 'کد پترن ارسال کد تایید', 'sample' => "کد تایید: %code% \r\n {site}"],
                'order_paid_pattern_code_farazsms' => ['label' => 'کد پترن پرداخت سفارش (ادمین)', 'toggle' => 'sms_on_order_paid', 'sample' => "سفارش جدید با شماره سفارش %order_id% ثبت و پرداخت شد.\r\n {site}"],
                'seller_order_paid_pattern_code_farazsms' => ['label' => 'کد پترن پرداخت سفارش (فروشنده)', 'toggle' => 'seller_sms_on_order_paid', 'sample' => "سفارش شما با شماره سفارش %order_id% با موفقیت ثبت شد.\r\n {site}"],
                'user_order_paid_pattern_code_farazsms' => ['label' => 'کد پترن پرداخت سفارش (کاربر)', 'toggle' => 'user_sms_on_order_paid', 'sample' => "سفارش شما با شماره سفارش %order_id% با موفقیت ثبت شد.\r\n {site}"],
                'order_cancelled_pattern_code_farazsms' => ['label' => 'کد پترن لغو سفارش (ادمین)', 'toggle' => 'sms_on_order_cancelled', 'sample' => "سفارش شماره %order_id% لغو شد.\r\n {site}"],
                'seller_order_cancelled_pattern_code_farazsms' => ['label' => 'کد پترن لغو سفارش (فروشنده)', 'toggle' => 'seller_sms_on_order_cancelled', 'sample' => "سفارش شماره %order_id% لغو شد. در صورت نیاز با پشتیبانی تماس بگیرید.\r\n {site}"],
                'user_order_cancelled_pattern_code_farazsms' => ['label' => 'کد پترن لغو سفارش (کاربر)', 'toggle' => 'user_sms_on_order_cancelled', 'sample' => "سفارش شماره %order_id% به دلیل %reason% لغو شد. مبلغ %refund_amount% تومان به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_refund_pattern_code_farazsms' => ['label' => 'کد پترن برگشت وجه به کیف پول', 'toggle' => 'wallet_refund_sms', 'sample' => "مبلغ %amount% تومان بابت لغو سفارش %order_id% به کیف پول شما برگشت داده شد.\r\n {site}"],
                'wallet_increase_pattern_code_farazsms' => ['label' => 'کد پترن افزایش موجودی کیف پول', 'toggle' => 'wallet_increase_sms', 'sample' => "مبلغ %amount% تومان به اعتبار کیف پول شما اضافه شد.\r\n {site}"],
                'wallet_decrease_pattern_code_farazsms' => ['label' => 'کد پترن کاهش موجودی کیف پول', 'toggle' => 'wallet_decrease_sms', 'sample' => "مبلغ %amount% تومان از اعتبار کیف پول شما کسر شد.\r\n {site}"],
                'happy_birthday_pattern_code_farazsms' => ['label' => 'کد پترن تبریک تولد', 'toggle' => 'happy_birthday_sms', 'sample' => "%fullname% عزیز زندگی بسیار کوتاه است از هر لحظه آن لذت ببرید و با تکیه بر تجربه های سال های گذشته سال های آتی زندگی را به بهترین شکل ممکن بگذرانید تولدتان مبارک\r\n {site}"],
                'price_alert_pattern_code' => ['label' => 'کد پترن هشدار کاهش قیمت', 'sample' => "قیمت محصول به %price% تومان کاهش یافت.\r\n {site}"],
                'cart_recovery_pattern_code' => ['label' => 'کد پترن یادآور سبد خرید رهاشده (پکیج مارکتینگ)', 'sample' => "%name% عزیز، سبد خرید شما (%items%) در انتظار شماست. مبلغ: %total% تومان.\r\n {site}"],
                'marketing_campaign_pattern_code' => ['label' => 'کد پترن کمپین پیامکی (پکیج مارکتینگ)', 'sample' => "%name% عزیز، %store% برای شما پیشنهاد ویژه دارد!\r\n {site}"],
            ],
        ];
    }

    /** کارت‌های روشن/خاموش (پورت sms-toggle-card) */
    public function toggleCards(): array
    {
        return [
            'register' => [
                ['name' => 'sms_on_seller_register', 'title' => 'ارسال پیامک موقع ایجاد فروشنده', 'icon' => 'shopping-bag'],
                ['name' => 'sms_on_user_register', 'title' => 'ارسال پیامک موقع ایجاد کاربر', 'icon' => 'user'],
                ['name' => 'sms_to_verify_user', 'title' => 'تایید کاربر یا فروشنده با شماره همراه', 'icon' => 'shield'],
                ['name' => 'forgot_password_link', 'title' => 'بازیابی رمز عبور با کد تایید', 'icon' => 'key-round'],
                ['name' => 'login_with_code', 'title' => 'ورود با رمز یکبار مصرف', 'icon' => 'log-out'],
            ],
            'orders' => [
                ['name' => 'sms_on_order_paid', 'title' => 'پرداخت سفارش', 'desc' => 'اطلاع‌رسانی به مدیر', 'icon' => 'check-circle'],
                ['name' => 'sms_on_order_cancelled', 'title' => 'لغو سفارش', 'desc' => 'اطلاع‌رسانی به مدیر', 'icon' => 'circle-x'],
                ['name' => 'seller_sms_on_order_paid', 'title' => 'پرداخت سفارش', 'desc' => 'اطلاع‌رسانی به فروشنده', 'icon' => 'check-circle'],
                ['name' => 'seller_sms_on_order_cancelled', 'title' => 'لغو سفارش', 'desc' => 'اطلاع‌رسانی به فروشنده', 'icon' => 'circle-x'],
                ['name' => 'user_sms_on_order_paid', 'title' => 'پرداخت سفارش', 'desc' => 'اطلاع‌رسانی به کاربر', 'icon' => 'check-circle'],
                ['name' => 'user_sms_on_order_cancelled', 'title' => 'لغو سفارش', 'desc' => 'اطلاع‌رسانی به کاربر', 'icon' => 'circle-x'],
            ],
            'wallet' => [
                ['name' => 'wallet_increase_sms', 'title' => 'افزایش موجودی کیف پول', 'icon' => 'trending-up'],
                ['name' => 'wallet_decrease_sms', 'title' => 'کاهش موجودی کیف پول', 'icon' => 'trending-down'],
                ['name' => 'wallet_refund_sms', 'title' => 'برگشت وجه به کیف پول', 'icon' => 'rotate-ccw'],
            ],
            'birthday' => [
                ['name' => 'happy_birthday_sms', 'title' => 'ارسال پیامک تبریک تولد', 'desc' => 'کاملاً خودکار، روزانه توسط زمان‌بند سیستم بررسی می‌شود', 'icon' => 'gift'],
            ],
        ];
    }

    /** متادیتای یک پترن برای ویو */
    public function patternMeta(string $provider, string $key): ?array
    {
        return self::patternDefinitions()[$provider][$key] ?? null;
    }

    /** متن نمونه‌ی پترن با جایگزینی عنوان سایت */
    public function sample(string $sample): string
    {
        return str_replace('{site}', (string) option('info_site_title'), $sample);
    }

    /* ---------------- ذخیره (پورت updateSms) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        $except = $this->toggleKeys();

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // تنظیمات اصلی (sms_panel_provider, admin_mobile_number, اطلاعات پنل‌ها، پترن‌ها)
        foreach ($this->sms as $key => $value) {
            $value = (string) $value;
            $oldValue = option($key);
            if ($oldValue != $value) {
                $allChanges[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                    'title' => $this->getSmsFieldTitle($key),
                ];
            }
            option_update($key, $value);
        }

        // چک‌باکس‌ها — on/off (پورت دقیق)
        foreach ($except as $option) {
            $newValue = ($this->toggles[$option] ?? false) ? 'on' : 'off';
            $oldValue = option($option);

            if ($oldValue != $newValue) {
                $allChanges[$option] = [
                    'old' => $oldValue == 'on' ? 'فعال' : 'غیرفعال',
                    'new' => $newValue == 'on' ? 'فعال' : 'غیرفعال',
                    'title' => $this->getSmsFieldTitle($option),
                ];
            }

            option_update($option, $newValue);
        }

        // لاگ (پورت دقیق)
        if (! empty($allChanges)) {
            $changeTexts = [];
            foreach ($allChanges as $change) {
                $changeTexts[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
            }

            $logMessage = "مدیر {$adminName} تنظیمات پیامک را به‌روزرسانی کرد: ".implode('، ', $changeTexts);

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'update_sms_settings',
                    'changes' => $allChanges,
                    'ip' => request()->ip(),
                ])
                ->log($logMessage);
        }

        $this->dispatch('toast', message: 'تنظیمات پیامک ذخیره شد.', type: 'success');
    }

    /** پورت دقیق getSmsFieldTitle قدیمی */
    private function getSmsFieldTitle($key)
    {
        $titles = [
            'sms_panel_provider' => 'ارائه دهنده پنل پیامک',
            'admin_mobile_number' => 'شماره تلفن مدیر',
            'sms_on_user_register' => 'ارسال پیامک هنگام ثبت‌نام کاربر',
            'sms_on_seller_register' => 'ارسال پیامک هنگام ثبت‌نام فروشنده',
            'sms_to_verify_user' => 'تایید با شماره همراه',
            'forgot_password_link' => 'بازیابی رمز با کد تایید',
            'login_with_code' => 'ورود با رمز یکبار مصرف',
            'sms_on_order_paid' => 'ارسال پیامک به مدیر هنگام پرداخت سفارش',
            'sms_on_order_cancelled' => 'ارسال پیامک به مدیر هنگام لغو سفارش',
            'seller_sms_on_order_paid' => 'ارسال پیامک به فروشنده هنگام پرداخت سفارش',
            'seller_sms_on_order_cancelled' => 'ارسال پیامک به فروشنده هنگام لغو سفارش',
            'user_sms_on_order_paid' => 'ارسال پیامک به کاربر هنگام پرداخت سفارش',
            'user_sms_on_order_cancelled' => 'ارسال پیامک به کاربر هنگام لغو سفارش',
            'wallet_increase_sms' => 'ارسال پیامک افزایش موجودی کیف پول',
            'wallet_decrease_sms' => 'ارسال پیامک کاهش موجودی کیف پول',
            'wallet_refund_sms' => 'ارسال پیامک برگشت وجه به کیف پول',
            'ippanel_api_key' => 'API Key پنل ippanel',
            'ippanel_sender_number' => 'شماره فرستنده ippanel',
            'kavenegar_api_key' => 'API Key کاوه نگار',
            'kavenegar_sender_number' => 'شماره فرستنده کاوه نگار',
            'melipayamak_username' => 'نام کاربری ملی پیامک',
            'melipayamak_password' => 'رمز عبور ملی پیامک',
            'melipayamak_sender_number' => 'شماره فرستنده ملی پیامک',
            'idehpardazan_api_key' => 'API Key ایده پردازان',
            'idehpardazan_sender_number' => 'شماره فرستنده ایده پردازان',
            'farazsms_api_key' => 'API Key فراز اس ام اس',
            'farazsms_sender_number' => 'شماره فرستنده فراز اس ام اس',
        ];

        return $titles[$key] ?? $key;
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.sms')
            ->layout('admin-panel::layout');
    }
}

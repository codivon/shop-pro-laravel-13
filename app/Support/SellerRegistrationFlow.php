<?php

namespace App\Support;

use App\Models\Seller;
use App\Models\SellerRegistration;

/*
 * جریان ثبت‌نام مرحله‌ای فروشندگان (فاز ۱۱۰ — پنل مدرن)
 *
 * نگهداری و گیتِ پیش‌نویس: هر مرحله در جدول seller_registrations ذخیره
 * می‌شود (رفرش/قطع اینترنت ⇒ ادامه دقیقاً از همان مرحله)؛ با کوکی توکن‌دار
 * حتی با گم‌شدن سشن هم پیش‌نویس بازیابی می‌شود.
 *
 * قاعدهٔ گیت: رفتن به مرحلهٔ «جلوتر از پیشرفت فعلی» ممنوع (ریدایرکت به
 * مرحلهٔ فعلی)؛ بازگشت به مراحل قبل و ویرایش مجدد آزاد است.
 */
class SellerRegistrationFlow
{
    /** سشن شناسهٔ پیش‌نویس */
    public const SESSION_DRAFT = 'show-seller-registration-draft';

    /** سشن موبایل در انتظار تأیید (هم‌نام کلید قدیمی برای سازگاری لاگین) */
    public const SESSION_VERIFICATION = 'show-seller-verification-form';

    /** کوکی توکن بازیابی پیش‌نویس */
    public const COOKIE_TOKEN = 'sp_reg_token';

    /** فاصلهٔ ارسال مجدد کد (ثانیه) — پایدار با رفرش چون از updated_at دیتابیس محاسبه می‌شود */
    public const RESEND_SECONDS = 120;

    /** اعتبار کد تأیید (دقیقه) — هم‌قرارداد با جریان قدیمی */
    public const CODE_TTL_MINUTES = 15;

    /**
     * پیش‌نویس فعال کاربر: از سشن، وگرنه از کوکی توکن‌دار
     * (سشن دوباره مسلح می‌شود تا ادامه از همین صفحه امن باشد).
     */
    public static function currentDraft(): ?SellerRegistration
    {
        $draft = null;

        $id = (int) session(self::SESSION_DRAFT, 0);

        if ($id > 0) {
            $draft = SellerRegistration::find($id);
        }

        if (! $draft) {
            $token = (string) request()->cookie(self::COOKIE_TOKEN, '');

            if ($token !== '') {
                $draft = SellerRegistration::where('token', $token)->first();
            }
        }

        if ($draft) {
            session([self::SESSION_DRAFT => $draft->id]);
        }

        return $draft;
    }

    /** نام روت صفحهٔ هر مرحله */
    public static function stepRoute(SellerRegistration $draft): string
    {
        return match ($draft->step) {
            'password' => 'seller.registration_password',
            'type' => 'seller.registration_type',
            'identity' => 'seller.registration_identity',
            'business' => 'seller.registration_business_details',
            'documents' => 'seller.registration_documents',
            'checkout' => 'seller.registration_checkout',
            'complete' => 'seller.login',
            default => 'seller.registration_mobile', // otp
        };
    }

    /** URL ادامهٔ ثبت‌نام از وضعیت فعلی پیش‌نویس (اگر Seller ساخته شده، بر اساس status_register) */
    public static function resumeUrl(SellerRegistration $draft): string
    {
        if (in_array($draft->step, ['documents', 'checkout', 'complete'], true)) {
            $seller = Seller::where('mobile', $draft->mobile)->first();

            if ($seller) {
                if ($seller->status_register === 'complete') {
                    return route('seller.login');
                }

                return route($seller->status_register === 'documents'
                    ? 'seller.registration_checkout'
                    : 'seller.registration_documents');
            }
        }

        return route(self::stepRoute($draft));
    }

    /** پیش‌نویسِ باقی‌ماندهٔ ثبت‌نام (متعلق به این کوکی/سشن) — null یعنی جریان تازه */
    public static function activeDraft(): ?SellerRegistration
    {
        $draft = self::currentDraft();

        if ($draft && $draft->step !== 'complete') {
            return $draft;
        }

        return null;
    }
}

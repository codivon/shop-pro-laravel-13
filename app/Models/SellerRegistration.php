<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/*
 * فاز ۱۱۰ — پیش‌نویس ثبت‌نام مرحله‌ای فروشندگان (پنل مدرن)
 *
 * هر مرحله بلافاصله ذخیره می‌شود تا با رفرش/قطع اینترنت کاربر دقیقاً در
 * همان مرحله ادامه دهد؛ ترتیب مراحل با ستون step کنترل می‌شود (رفتن به
 * مرحلهٔ جلوتر از آدرس مرورگر ممکن نیست — گیت سروری در کنترلر).
 */
class SellerRegistration extends Model
{
    protected $guarded = ['id'];

    /** ترتیب مراحل — ایندکس پایین‌تر یعنی مرحلهٔ عقب‌تر */
    public const STEPS = [
        'otp' => 0, // در انتظار تأیید کد موبایل
        'type' => 1, // انتخاب وضعیت (حقیقی/حقوقی)
        'identity' => 2, // اطلاعات تکمیلی (کد ملی و…)
        'business' => 3, // اطلاعات کسب‌وکار و ورود
        'documents' => 4, // مدارک (پس از ساخت Seller)
        'checkout' => 5, // پایان و ورود به پنل
        'complete' => 6,
    ];

    /** انواع فروشنده */
    public const TYPE_REAL = 'real';

    public const TYPE_LEGAL = 'legal';

    /** این مرحله به مرحلهٔ داده‌شده رسیده (یا از آن جلوتر) است؟ */
    public function reached(string $step): bool
    {
        return (self::STEPS[$this->step] ?? 0) >= (self::STEPS[$step] ?? 99);
    }

    /** پیشروی ترتیبی به مرحلهٔ بعد */
    public function advanceTo(string $step): void
    {
        $this->update(['step' => $step]);
    }

    /** ساخت توکن یکتا برای بازیابی از کوکی */
    public static function newToken(): string
    {
        return Str::random(40);
    }
}

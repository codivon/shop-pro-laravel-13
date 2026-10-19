<?php

namespace App\Services;

use App\Models\BotTrap;
use Illuminate\Http\Request;

/*
 * تشخیص ربات روی فرم‌های حساس (ورود، ثبت‌نام، OTP)
 *
 * سه لایه:
 *  ۱) Honeypot: فیلد مخفی که کاربر واقعی آن را نمی‌بیند و پر نمی‌کند؛ ربات‌های خودکار همه فیلدها را پر می‌کنند.
 *  ۲) Time-trap: فرم‌های پرشده در کمتر از حداقل زمان انسانی = ربات.
 *  ۳) ثبت IP ربات‌ها در جدول bot_traps ⇒ مسدودسازی سراسری توسط میان‌افزار BlockBots.
 */
class BotDetector
{
    /** حداقل زمان طبیعی برای پرکردن فرم (ثانیه) */
    public const MIN_FORM_SECONDS = 3;

    /** آیا فرم توسط ربات پر شده است؟ */
    public function isBot(Request $request, ?string $renderedAt): bool
    {
        // ۱) تله‌ی فیلد مخفی — کاربر واقعی نمی‌تواند آن را پر کند (display:none)
        if ($request->filled('website_url') || $request->filled('confirm_email')) {
            return true;
        }

        // ۲) تله‌ی زمان — پرکردن فرم در کمتر از ۳ ثانیه غیرانسانی است
        if ($renderedAt && (int) $renderedAt > 0) {
            if (now()->getTimestamp() - (int) $renderedAt < self::MIN_FORM_SECONDS) {
                return true;
            }
        }

        return false;
    }

    /**
     * ثبت ربات در تله (مسدودسازی سراسری IP)
     * فقط در موارد قطعی (فیلد تله‌ی مخفی) استفاده می‌شود؛
     * time-trap ممکن است با اینترنت کندِ کاربر واقعی اتفاق بیفتد، پس فقط رد می‌کنیم نه مسدود.
     */
    public function trap(Request $request): void
    {
        BotTrap::catch(
            (string) $request->ip(),
            (string) $request->userAgent(),
            'form:'.$request->path()
        );
    }

    /** پیام خطای یکسان — بدون افشای مکانیزم تله */
    public static function botMessage(): string
    {
        return 'درخواست شما قابل پردازش نیست. لطفاً صفحه را تازه‌سازی کنید و دوباره تلاش کنید.';
    }
}

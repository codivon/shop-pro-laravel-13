<?php

namespace App\Services;

use App\Models\BotTrap;
use Illuminate\Http\Request;

/*
 * تشخیص ربات روی فرم‌های حساس (ورود، ثبت‌نام، OTP، دیدگاه‌ها)
 *
 * سه لایه:
 *  ۱) Honeypot: فیلد مخفی که کاربر واقعی آن را نمی‌بیند و پر نمی‌کند؛ ربات‌های خودکار همه فیلدها را پر می‌کنند.
 *  ۲) Time-trap: فرم‌های پرشده در کمتر از حداقل زمان انسانی = ربات.
 *  ۳) ثبت IP ربات‌ها در جدول bot_traps ⇒ مسدودسازی سراسری توسط میان‌افزار BlockBots.
 *
 * فاز ۴۴: هانی‌پات و تلهٔ زمان حالا از تنظیمات امنیتی (تب «امنیت» تنظیمات برنامه‌نویس)
 * کنترل می‌شوند — SecuritySettings::honeypotEnabled() و SecuritySettings::timeTrapSeconds().
 */
class BotDetector
{
    /** حداقل زمان طبیعی برای پرکردن فرم (ثانیه) — به‌عنوان پیش‌فرض تنظیمات استفاده می‌شود */
    public const MIN_FORM_SECONDS = 3;

    /** آیا فرم توسط ربات پر شده است؟ */
    public function isBot(Request $request, ?string $renderedAt): bool
    {
        // ۱) تله‌ی فیلد مخفی — کاربر واقعی نمی‌تواند آن را پر کند (display:none)
        //    در صورت خاموش بودن هانی‌پات از تنظیمات، این تله نادیده گرفته می‌شود
        if (SecuritySettings::honeypotEnabled()
            && ($request->filled('website_url') || $request->filled('confirm_email'))) {
            return true;
        }

        // ۲) تله‌ی زمان — پرکردن فرم در کمتر از حد مجاز غیرانسانی است (۰ = غیرفعال)
        $minSeconds = SecuritySettings::timeTrapSeconds();
        if ($minSeconds > 0 && $renderedAt && (int) $renderedAt > 0) {
            if (now()->getTimestamp() - (int) $renderedAt < $minSeconds) {
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

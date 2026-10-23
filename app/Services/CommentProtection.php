<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/*
 * حفاظت یکپارچهٔ فرم‌های عمومی (دیدگاه‌ها/بررسی‌ها) — فاز ۴۴
 *
 * همهٔ بررسی‌ها از تب «امنیت» تنظیمات برنامه‌نویس کنترل می‌شوند:
 *  - کپچا (نقطهٔ comments) × کلید اصلی کپچا
 *  - هانی‌پات (کلید اصلی) — پر شدن فیلدهای تله ⇒ مسدودسازی IP
 *  - تلهٔ زمان (حداقل ثانیهٔ پرکردن فرم) — ۰ = غیرفعال
 *  - قفل تلاش‌ها (نقطهٔ comments) — سقف ارسال در پنجرهٔ دقیقه‌ای
 */
class CommentProtection
{
    /**
     * اعمال کامل حفاظت روی یک ارسال عمومی؛ در صورت شکست ValidationException پرتاب می‌شود.
     *
     * @param  string  $throttleIdentity  شناسهٔ یکتا برای شمارش تلاش (مثلاً product:15)
     */
    public static function validateSubmission(Request $request, string $throttleIdentity): void
    {
        $detector = app(BotDetector::class);

        // ۱) هانی‌پات — تلهٔ قطعی ⇒ مسدودسازی IP
        if (SecuritySettings::honeypotEnabled()
            && ($request->filled('website_url') || $request->filled('confirm_email'))) {
            $detector->trap($request);

            throw ValidationException::withMessages([
                'body' => BotDetector::botMessage(),
            ]);
        }

        // ۲) تلهٔ زمان — فقط رد می‌شود (اینترنت کند کاربر واقعی نباید مسدود شود)
        if ($detector->isBot($request, $request->input('rendered_at'))) {
            throw ValidationException::withMessages([
                'body' => BotDetector::botMessage(),
            ]);
        }

        // ۳) کپچا
        if (SecuritySettings::captchaEnabled('comments')
            && ! app(CaptchaService::class)->verify($request->input('captcha'))) {
            app(CaptchaService::class)->flush();

            throw ValidationException::withMessages([
                'captcha' => 'کد امنیتی وارد شده صحیح نیست یا منقضی شده است.',
            ]);
        }

        // ۴) قفل تلاش‌ها — ضد اسپم پیاپی
        if (SecuritySettings::lockoutEnabled()) {
            $key = 'comments:'.sha1($throttleIdentity.'|'.$request->ip());
            $max = SecuritySettings::lockoutMax('comments');

            if (RateLimiter::tooManyAttempts($key, $max)) {
                $seconds = RateLimiter::availableIn($key);

                throw ValidationException::withMessages([
                    'body' => 'تعداد ارسال‌های شما بیش از حد مجاز است. لطفاً '.(int) ceil($seconds / 60).' دقیقه دیگر تلاش کنید.',
                ]);
            }

            RateLimiter::hit($key, SecuritySettings::lockoutWindowSeconds('comments'));
        }
    }

    /** بلوک‌های مخفی مشترک فرم‌ها (هانی‌پات + تلهٔ زمان) */
    public static function hiddenFields(): string
    {
        $timeTrap = '<input type="hidden" name="rendered_at" value="'.time().'">';

        if (! SecuritySettings::honeypotEnabled()) {
            return $timeTrap;
        }

        return $timeTrap
            .'<div style="position:absolute !important;height:0 !important;width:0 !important;margin:0 !important;padding:0 !important;overflow:hidden;opacity:0;" aria-hidden="true" tabindex="-1">'
            .'<label for="website_url">وب‌سایت شما</label>'
            .'<input id="website_url" type="text" name="website_url" tabindex="-1" autocomplete="off">'
            .'<label for="confirm_email">تأیید ایمیل</label>'
            .'<input id="confirm_email" type="text" name="confirm_email" tabindex="-1" autocomplete="off">'
            .'</div>';
    }
}

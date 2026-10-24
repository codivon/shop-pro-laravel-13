<?php

namespace App\Services;

use App\Providers\AppServiceProvider;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

/**
 * فاز ۲۵ — کارهای پس از ثبت‌نامِ اولین مدیر (مشترک بین پنل قدیمی و جدید)
 *
 * این منطق قبلاً داخل InstallController::configApplication بود؛ حالا یک‌جا
 * سرویس شده تا صفحه‌ی ثبت‌نامِ پنل جدید (Livewire) هم دقیقاً همان رفتار را داشته باشد:
 *
 *  ۱) ثبت آدرس اختصاصی پنل در .env (ADMIN_ROUTE_PREFIX)
 *  ۲) ثبت تم فعال (ThemeServiceProvider)
 *  ۳) shop:link — شورت‌کات (symlink) استایل و فایل‌های فرانت تم داخل public
 *  ۴) theme:config — مقداردهی اولیه تنظیمات تم
 *  ۵) سوییچ درایور سشن/صف به database
 *  ۶) نشان «نصب‌شده» (بستن همیشگی مسیرهای /install)
 *  ۷) اطلاع‌رسانی نصب به سرور مرکزی (اختیاری — خطا نادیده گرفته می‌شود)
 *  ۸) optimize (کش کانفیگ/مسیر/نما) — بعد از ارسال پاسخ
 *
 * نکته‌ی مهم فاز ۲۵: «config:cache» و «optimize» و پینگِ شبکه هرگز وسط
 * درخواستِ فعال اجرا نمی‌شوند. config:cache یک نمونه‌ی دوم Application را در
 * همان پردازش بوت می‌کند و همه‌ی Facadeها (session/auth/livewire-store/…)
 * را به اپِ جدید منتقل می‌کند؛ نتیجه‌اش این بود که افکتِ ریدایرکتِ بعد از
 * ثبت‌نام گم می‌شد و کاربر لاگین نمی‌شد. این کارهای سنگین با
 * register_shutdown_function به «بعد از ارسال پاسخ» منتقل شده‌اند.
 */
class SetupFinalizer
{
    /**
     * فاز ۲۶ — تأیید نهایی شورت‌کات استایل فرانت در public.
     *
     * برای هر لینک تعریف‌شده در config('front.links') و config('filesystems.links')
     * بررسی می‌شود که در public واقعاً موجود و سالم باشد؛ در غیر این صورت
     * پوشه‌ی والد ساخته و محتوا مستقیم کپی می‌شود. خطاها گزارش می‌شوند اما
     * هرگز زنجیره‌ی نصب را نمی‌شکنند.
     */
    protected function ensureThemeLinks(): void
    {
        $links = [];

        foreach ([(array) config('filesystems.links'), (array) config('front.links')] as $group) {
            foreach ($group as $link => $target) {
                $links[(string) $link] = (string) $target;
            }
        }

        foreach ($links as $link => $target) {
            try {
                // symlink سالم؟ کاری نکن
                if (is_link($link) && file_exists($link)) {
                    continue;
                }

                // symlink شکسته = جا باز شود
                if (is_link($link)) {
                    @unlink($link);
                }

                // پوشه‌ی واقعیِ کپی‌شده که محتوا دارد؟ کاری نکن
                if (is_dir($link)) {
                    $entries = @scandir($link);

                    if (is_array($entries) && count($entries) > 2) {
                        continue;
                    }
                }

                // والد + خود مسیر تضمین‌شده ساخته شوند (علت اصلی شکست بی‌صدای قبلی)
                if (!is_dir(dirname($link))) {
                    @mkdir(dirname($link), 0755, true);
                }

                if (!is_dir($link)) {
                    @mkdir($link, 0755, true);
                }

                if (is_dir($target)) {
                    \Illuminate\Support\Facades\File::copyDirectory($target, $link);
                    \Illuminate\Support\Facades\Log::info('SetupFinalizer: theme assets copied to public', ['link' => $link]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function finalize(string $adminPrefix): void
    {
        /*
         * زنجیره‌ی راه‌اندازی روی هاست‌های کم‌حافظه می‌تواند از حد حافظه/زمان
         * بگذرد؛ هر دو مقدار best-effort بالا برده می‌شوند (اگر هاست اجازه دهد).
         */
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        // ۱) آدرس اختصاصی پنل — بدون کش کانفیگ (درخواستِ فعال سالم بماند)
        change_env('ADMIN_ROUTE_PREFIX', $adminPrefix, false);

        // ۲) ثبت تم فعال
        try {
            AppServiceProvider::loadTheme();
        } catch (\Throwable $e) {
            report($e);
        }

        // ۳) شورت‌کات استایل فرانت در public (symlink تم + storage)
        try {
            Artisan::call('shop:link');
        } catch (\Throwable $e) {
            report($e);
        }

        /*
         * فاز ۲۶ — گارانتی شورت‌کات استایل فرانت در public:
         * روی برخی هاست‌های اشتراکی (symlink غیرفعال، proc_open بسته، نبود پوشه‌ی والد)
         * ساخت لینک شکست می‌خورد و کاربر فرانت را بدون استایل می‌دید. اینجا بعد از
         * shop:link «تأیید» می‌کنیم که همه‌ی لینک‌های تم واقعاً در public موجودند؛
         * اگر نبودند، محتوا مستقیم کپی می‌شود (همان کاری که پنل قدیمی با
         * configApplication() → shop:link می‌کرد، ولی با گارانتی).
         */
        $this->ensureThemeLinks();

        // ۴) پیکربندی اولیه تم
        try {
            Artisan::call('theme:config');
        } catch (\Throwable $e) {
            report($e);
        }

        // ۵) درایورهای دیتابیسی برای سشن و صف (بدون کش کانفیگ)
        change_env('SESSION_DRIVER', 'database', false);
        change_env('QUEUE_CONNECTION', 'database', false);

        option_update('app_debug_mode', 'on');

        // ۶) نشان نصب — مسیرهای /install و /update برای همیشه بسته می‌شوند
        try {
            touch(storage_path('installed'));
        } catch (\Throwable $e) {
            report($e);
        }

        /*
         * ۷ و ۸) کارهای سنگین/شبکه‌ای — بعد از ارسال پاسخ به مرورگر:
         *   - optimize (کش کانفیگ/مسیر/نما/رویدادها)
         *   - پینگ اطلاع‌رسانی نصب به سرور مرکزی
         * درخواستِ ثبت‌نام بلافاصله ریدایرکت می‌شود و کاربر سرعت را حس می‌کند.
         */
        try {
            register_shutdown_function(function () {
                try {
                    Artisan::call('optimize');
                } catch (\Throwable $e) {
                    // کش کردن کانفیگ حیاتی نیست — اپ بدون کش هم درست کار می‌کند
                    report($e);
                }

                try {
                    Http::withHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                    ])->timeout(10)->get(config('general.api_url') . '/shop/installed', [
                        'host'         => url('/') ?: config('app.url'),
                        'time'         => now(),
                        'script'       => 'shop',
                        'server_ip'    => request()->server('SERVER_ADDR'),
                        'installer_ip' => request()->ip(),
                    ]);
                } catch (Exception $e) {
                    // just continue
                }
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

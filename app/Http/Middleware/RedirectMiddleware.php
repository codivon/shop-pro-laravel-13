<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RedirectMiddleware
{
    /**
     * ★ فاز ۲۲ (بهینه‌سازی کوئری‌های کند):
     * این میدلور سراسری است و روی «هر درخواست» اجرا می‌شود؛
     *  - Schema::hasTable() یک کوئری سنگین روی information_schema اجرا می‌کند (روی هاست‌های اشتراکی ۲ تا ۴ ثانیه!)
     *  - کوئری ریدایرکت هم در هر درخواست اجرا می‌شد
     * هر دو اکنون کش می‌شوند (نتیجه وجود جدول برای همیشه + نقشه ریدایرکت‌ها ۱۰ دقیقه) و
     * با رویدادهای مدل Redirect باطل می‌شوند — صفر کوئری در مسیر پرتکرار.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // مسیر درخواست بدون اسلش‌های اول و آخر
        $currentUrl = trim($request->path(), '/');

        // ۱️⃣ اگر فایل .env حاضر نیست، ادامه می‌دهیم
        if (!file_exists(base_path('.env'))) {
            return $next($request);
        }

        // ۲️⃣ بررسی اتصال به دیتابیس (فقط وقتی هنوز اتصالی برقرار نشده)
        try {
            DB::connection()->getPdo();          // سعی در برقراری اتصال
        } catch (\Exception $e) {
            return $next($request);
        }

        // ۳️⃣ وجود جدول redirects — نتیجه کش می‌شود (چک information_schema در هر درخواست ممنوع)
        $hasTable = Cache::rememberForever('redirects.table_exists', function () {
            return Schema::hasTable('redirects');
        });

        if (!$hasTable) {
            // جدول هنوز ساخته نشده (مثلاً در حین نصب) → ادامه بدون ریدایرکت
            return $next($request);
        }

        // ۴️⃣ نقشه ریدایرکت‌ها (جدول معمولاً کوچک است) — کش ۱۰ دقیقه‌ای + باطل‌سازی با رویداد مدل
        try {
            $redirects = Cache::remember('redirects.map', now()->addMinutes(10), function () {
                return Redirect::query()->get(['from', 'to', 'type'])->keyBy('from');
            });
        } catch (\Exception $e) {
            return $next($request);
        }

        if ($redirects->has($currentUrl)) {
            $redirect = $redirects->get($currentUrl);

            return redirect($redirect->to, (int) $redirect->type ?: 301);
        }

        // ۵️⃣ در غیر این صورت به میدلورهای بعدی می‌رویم
        return $next($request);
    }
}

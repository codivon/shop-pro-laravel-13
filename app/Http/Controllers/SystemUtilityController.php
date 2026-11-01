<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * فاز ۱۳۸ — روت‌های ابزاری که قبلاً Closure بودند و «route:cache» را قفل می‌کردند
 *
 * artisan route:cache با روت‌های Closure سازگار نیست (خطای serialize می‌دهد)؛
 * همین ۳ روتِ کوچک مانع بود که پروداکشن هرگز نتواند کش مسیرها داشته باشد.
 * منطق هر سه عیناً از نسخهٔ قبلی پورت شده است — فقط کلاس‌دار شدند.
 */
class SystemUtilityController extends Controller
{
    /** manifest.json وب‌پوش — خروجی JSON ساده (قبلاً Closure در web.php) */
    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'gcm_sender_id' => config('webpush.gcm.sender_id'),
        ]);
    }

    /**
     * به‌روزرسانی خودکار csrf برای اسکریپت‌های داخلی — توکن به بیرون درز نمی‌کند
     * (قبلاً Closure در web.php؛ نام روت و throttle عیناً حفظ شده است).
     */
    public function refreshCsrf(): string
    {
        abort_unless(auth('web')->check() || auth('adminPanel')->check(), 403);

        return csrf_token();
    }

    /**
     * پاک‌سازی کش از پنل (قبلاً Closure در admin-panel.php) — فقط کاربر ۱.
     * نام روت np.cache-clear و ریدایرکت/توست عیناً حفظ شده است.
     */
    public function cacheClear()
    {
        if (auth('adminPanel')->id() !== 1) {
            abort(403);
        }

        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return redirect()->route('np.dashboard')->with('np-toast', [
            'type' => 'success',
            'message' => 'کش سیستم با موفقیت پاک شد.',
        ]);
    }
}

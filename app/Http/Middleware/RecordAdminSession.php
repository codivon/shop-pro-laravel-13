<?php

namespace App\Http\Middleware;

use App\Helpers\DeviceHelper;
use App\Models\AdminSession;
use App\Support\DeviceFingerprint;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RecordAdminSession
{
    /**
     * ★ فاز ۲۲ (بهینه‌سازی کوئری‌های کند):
     * این میدلور روی «هر درخواست ادمین + هر آپدیت Livewire» اجرا می‌شد و
     * updateOrCreate آن ۲ تا ۳ کوئری (select + update/insert) در هر درخواست اجرا می‌کرد.
     * اکنون: ثبت/بروزرسانی حداکثر هر ۶۰ ثانیه به‌ازای هر نشست انجام می‌شود
     * (دقت last_activity برای نمایش «نشست‌های فعال» کاملاً کافی است) و
     * در مسیر گرم به‌جای select+update فقط یک UPDATE اجرا می‌شود.
     */
    protected const THROTTLE_SECONDS = 60;

    public function handle(Request $request, Closure $next)
    {

        if (Auth::guard('adminPanel')->check() && ! $request->is('admin/login*') && ! $request->is('admin/logout')) {
            $lastSync = $request->session()->get('_admin_session_sync_at');

            if ($lastSync && (now()->getTimestamp() - (int) $lastSync) < self::THROTTLE_SECONDS) {
                return $next($request); // در پنجره ۶۰ ثانیه قبلی ثبت شده — صفر کوئری
            }

            $request->session()->put('_admin_session_sync_at', now()->getTimestamp());

            $sessionId = Session::getId();
            $deviceInfo = DeviceHelper::detect($request);
            $adminId = Auth::guard('adminPanel')->id();

            // تولید اثر انگشت یکتا بر اساس دستگاه + مرورگر + IP (مرکزی — فاز ۷۲)
            $deviceFingerprint = DeviceFingerprint::session($request);

            $payload = [
                'session_id' => $sessionId,
                'device_name' => $deviceInfo['device_name'],
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'platform' => $deviceInfo['platform'],
                'ip_address' => $deviceInfo['ip_address'],
                'user_agent' => $deviceInfo['user_agent'],
                'last_activity' => now(),
                'is_active' => true,
            ];

            // فاز ۷۲ — «سیستم اصلی»: اولین دستگاهی که برای یک مدیر نشست می‌سازد
            // (وقتی هیچ نشست فعال دیگری ندارد) نشست اصلی محسوب می‌شود و تا ۴۸ ساعت
            // از دستگاه دیگر (آیپی/مرورگر/سیستم متفاوت) قابل بن/خروج نیست.
            $isNewDevice = ! AdminSession::query()
                ->where('admin_id', $adminId)
                ->where('device_fingerprint', $deviceFingerprint)
                ->exists();

            // بروزرسانی یا ایجاد نشست — فاز ۳۴: «یک» دستور UPSERT اتمیک
            // قبلاً update-then-create بود؛ دو درخواستِ همزمان (مثلاً wire:poll و
            // کلیک کاربر، هر دو بعد از پایان پنجره‌ی ۶۰ ثانیه‌ای) هر دو SELECT/UPDATE
            // خالی می‌گرفتند و هر دو INSERT می‌زدند → دومی با خطای
            // 1062 Duplicate entry 'admin_device_unique' کل درخواست را ۵۰۰ می‌کرد.
            // INSERT ... ON DUPLICATE KEY UPDATE همیشه اتمیک است و ریس رخ نمی‌دهد.
            try {
                AdminSession::upsert(
                    [$payload + [
                        'admin_id' => $adminId,
                        'device_fingerprint' => $deviceFingerprint,
                    ]],
                    ['admin_id', 'device_fingerprint'], // کلید یکتا (admin_device_unique)
                    [ // ستون‌های قابل بروزرسانی در تکرار
                        'session_id',
                        'device_name',
                        'device_type',
                        'browser',
                        'platform',
                        'ip_address',
                        'user_agent',
                        'last_activity',
                        'is_active',
                        'updated_at',
                    ]
                );
            } catch (\Throwable $e) {
                // ردیابی نشست هیچ‌وقت نباید درخواست ادمین را بترکاند
                report($e);
            }

            // فاز ۷۲ — نگهداری وضعیت «سیستم اصلی»
            try {
                if ($isNewDevice) {
                    // دستگاه جدید — فقط اگر مدیر هیچ نشست فعال دیگری نداشت، این نشست «اصلی» است
                    $hasOtherActive = AdminSession::query()
                        ->where('admin_id', $adminId)
                        ->where('device_fingerprint', '!=', $deviceFingerprint)
                        ->where('is_active', true)
                        ->exists();

                    if (! $hasOtherActive) {
                        AdminSession::query()
                            ->where('admin_id', $adminId)
                            ->where('device_fingerprint', $deviceFingerprint)
                            ->update([
                                'is_primary' => true,
                                'primary_until' => now()->addHours(48),
                            ]);
                    }
                } else {
                    // دستگاه اصلی همچنان در حال استفاده است ⇒ تمدید پنجرهٔ ۴۸ ساعته
                    AdminSession::query()
                        ->where('admin_id', $adminId)
                        ->where('device_fingerprint', $deviceFingerprint)
                        ->where('is_primary', true)
                        ->update(['primary_until' => now()->addHours(48)]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }

}

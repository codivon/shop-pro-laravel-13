<?php

namespace App\Http\Middleware;

use App\Helpers\DeviceHelper;
use App\Models\AdminSession;
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

            // تولید اثر انگشت یکتا بر اساس دستگاه + مرورگر + IP
            $deviceFingerprint = $this->generateDeviceFingerprint($request);

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
        }

        return $next($request);
    }

    /**
     * تولید اثر انگشت یکتا بر اساس: مرورگر + سیستم عامل + نوع دستگاه + IP
     * ترکیب این عوامل باعث می‌شود:
     * - همان دستگاه با همان مرورگر و همان IP → بروزرسانی می‌شود (تکرار ثبت نمی‌شود)
     * - دستگاه متفاوت یا مرورگر متفاوت یا IP متفاوت → رکورد جدید ساخته می‌شود
     */
    private function generateDeviceFingerprint(Request $request)
    {
        $userAgent = $request->userAgent();

        $browser = $this->getBrowserName($userAgent);
        $platform = $this->getPlatformName($userAgent);
        $deviceType = $this->getDeviceType($userAgent);
        $ip = $request->ip();

        // ترکیب همه عوامل برای ایجاد اثر انگشت یکتا
        $fingerprint = md5($browser.'|'.$platform.'|'.$deviceType.'|'.$ip);

        return $fingerprint;
    }

    private function getBrowserName($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
            return 'Chrome';
        }
        if (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        }
        if (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            return 'Safari';
        }
        if (strpos($userAgent, 'Edg') !== false) {
            return 'Edge';
        }
        if (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
            return 'Opera';
        }

        return 'Unknown';
    }

    private function getPlatformName($userAgent)
    {
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        }
        if (strpos($userAgent, 'Mac') !== false) {
            return 'macOS';
        }
        if (strpos($userAgent, 'Linux') !== false && strpos($userAgent, 'Android') === false) {
            return 'Linux';
        }
        if (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        }
        if (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'iOS';
        }

        return 'Unknown';
    }

    private function getDeviceType($userAgent)
    {
        if (preg_match('/(mobile|android|iphone|ipod|blackberry|windows phone)/i', $userAgent)) {
            return 'mobile';
        }
        if (preg_match('/(ipad|tablet)/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }
}

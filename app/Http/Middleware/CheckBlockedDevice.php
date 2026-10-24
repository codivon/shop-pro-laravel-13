<?php
// app/Http/Middleware/CheckBlockedDevice.php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\AdminSession;
use App\Models\BlockedDevice;
use App\Support\DeviceFingerprint;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CheckBlockedDevice
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('adminPanel')->check()) {
            $admin = Auth::guard('adminPanel')->user();

            $blocked = self::activeBlockFor($admin, $request);

            if ($blocked) {
                $sessionId = Session::getId();

                // حذف نشست فعلی
                AdminSession::where('session_id', $sessionId)->delete();

                Auth::guard('adminPanel')->logout();
                Session::flush();

                // فاز ۷۲ — پیام مسدودی در «همان صفحهٔ ورود پنل جدید» نمایش داده می‌شود
                // (قبلاً به پنل قدیم هدایت می‌شد — اشتباه مسیر)
                return redirect()
                    ->route('np.login')
                    ->withErrors(['username' => self::blockMessage($blocked)]);
            }
        }

        return $next($request);
    }

    /**
     * یافتن بلاک فعال این مدیر برای درخواست جاری — فاز ۷۲
     *
     * یک نقطهٔ مشترک برای میدل‌ور و هر دو صفحهٔ ورود (پنل جدید/قدیمی):
     *  - نوع account (بن اکانت): بدون توجه به دستگاه/آیپی همیشه مسدود است
     *  - انواع دیگر: فقط اگر نشست/آیپی/دستگاه/مرورگر جاری با رکورد بلاک بخواند
     */
    public static function activeBlockFor(Admin $admin, Request $request): ?BlockedDevice
    {
        $sessionId = (string) Session::getId();
        $ipAddress = (string) $request->ip();
        $userAgent = (string) $request->userAgent();

        $deviceFingerprint = DeviceFingerprint::device($userAgent);
        $browserFingerprint = DeviceFingerprint::browser($userAgent);

        return BlockedDevice::query()
            ->where('admin_id', $admin->id)
            ->where(function ($q) use ($sessionId, $ipAddress, $deviceFingerprint, $browserFingerprint) {
                // بن اکانت — کل حساب قفل است
                $q->where('block_type', 'account')
                    // بلاک‌های دستگاهی — فقط وقتی با همین نشست/آیپی/دستگاه بخوانند
                    ->orWhere(function ($q2) use ($sessionId, $ipAddress, $deviceFingerprint, $browserFingerprint) {
                        $q2->where('block_type', '!=', 'account')
                            ->where(function ($q3) use ($sessionId, $ipAddress, $deviceFingerprint, $browserFingerprint) {
                                $q3->where('session_id', $sessionId)
                                    ->orWhere('ip_address', $ipAddress)
                                    ->orWhere('device_fingerprint', $deviceFingerprint)
                                    ->orWhere('browser_fingerprint', $browserFingerprint);
                            });
                    });
            })
            ->where(function ($q) {
                $q->where('is_permanent', true)
                    ->orWhere('blocked_until', '>', now());
            })
            ->orderByRaw("FIELD(block_type, 'account', 'all', 'ip', 'device', 'browser', 'session')")
            ->first();
    }

    /** پیام فارسی مسدودی — برای نمایش در صفحهٔ ورود */
    public static function blockMessage(BlockedDevice $blocked): string
    {
        if ($blocked->isAccountBan()) {
            $message = 'حساب کاربری شما توسط سازندهٔ سایت مسدود شده است. برای رفع مسدودی با مدیر سایت در تماس باشید.';
        } else {
            $blockTypeText = $blocked->block_type_text;
            $message = "دسترسی شما به دلیل «".($blocked->reason ?: 'بلاک شدن')."» مسدود شده است. (نوع بلاک: {$blockTypeText})";
        }

        if (! $blocked->is_permanent && $blocked->blocked_until) {
            $message .= ' تا تاریخ '.jdate($blocked->blocked_until)->format('d F Y H:i');
        }

        return $message;
    }
}

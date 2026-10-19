<?php
// app/Livewire/Admin/System/SessionIndex.php

namespace App\Livewire\Admin\System;

use App\Models\Admin;
use App\Models\AdminSession;
use App\Models\BlockedDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * نشست‌های فعال ادمین‌ها + دستگاه‌های بلاک‌شده — پورت ۱:۱ Back\AdminSessionController
 * (index / destroy / destroyAllAdminSessions / clearInactive / logoutOtherDevices /
 *  blockedList / blockSession / unblockDevice / clearAdminBlocks + fingerprint helpers)
 *
 * blockedList به‌صورت «تب دوم» در همان صفحه پورت شده است (تصمیم مستند در گزارش).
 * روت np.sessions.blocked = sessions/blocked می‌تواند همین کامپوننت را با defaults('tab','blocked') باز کند.
 */
#[Title('نشست‌های فعال')]
class SessionIndex extends Component
{
    use WithPagination;

    /* ---------------- تب فعال (sessions | blocked) ---------------- */

    public string $tab = 'sessions';

    /* ---------------- مودال بلاک دستگاه (پورت blockSession) ---------------- */

    public bool $showBlockModal = false;

    public ?int $blockingSession = null;

    public string $blockType = 'session';

    public string $blockDuration = 'permanent';

    public string $blockReason = '';

    /* ---------------- تاییدهای حذف ---------------- */

    /** خروج اجباری یک نشست (پورت destroy) */
    public ?int $confirmingDelete = null;

    /** حذف همه نشست‌های یک ادمین (پورت destroyAllAdminSessions) */
    public ?int $confirmingDestroyAllAdmin = null;

    /** رفع بلاک یک دستگاه (پورت unblockDevice) */
    public ?int $confirmingUnblock = null;

    /** پاک‌سازی همه بلاک‌های یک ادمین (پورت clearAdminBlocks) */
    public ?int $confirmingClearBlocks = null;

    public function mount(?string $tab = null): void
    {
        auth('adminPanel')->user()?->can('sessions.index') ?: abort(403);

        if ($tab === 'blocked') {
            auth('adminPanel')->user()?->can('sessions.blocked') ?: abort(403);
            $this->tab = 'blocked';
        }

        $this->currentSessionId = Session::getId();
        $this->currentAdminId = auth('adminPanel')->id();
    }

    public string $currentSessionId = '';

    public $currentAdminId = null;

    /* ---------------- لیست نشست‌ها (پورت index قدیمی) ---------------- */

    #[Computed]
    public function sessions()
    {
        $query = AdminSession::with('admin')
            ->where('is_active', true)
            ->orderBy('last_activity', 'desc');

        // ادمین غیر creator فقط نشست‌های خودش را می‌بیند (پورت دقیق index)
        if (! auth('adminPanel')->user()->isCreator()) {
            $query->where('admin_id', auth('adminPanel')->user()->id);
        }

        return $query->paginate(20);
    }

    /* ---------------- لیست بلاک‌ها (پورت blockedList قدیمی) ---------------- */

    #[Computed]
    public function blockedDevices()
    {
        return BlockedDevice::with('admin')
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'blockedPage');
    }

    /* ---------------- داده مودال‌های تایید (برای نمایش نام ادمین/دستگاه) ---------------- */

    #[Computed]
    public function deletingSession(): ?AdminSession
    {
        return $this->confirmingDelete ? AdminSession::with('admin')->find($this->confirmingDelete) : null;
    }

    #[Computed]
    public function destroyingAllAdmin(): ?Admin
    {
        return $this->confirmingDestroyAllAdmin ? Admin::find($this->confirmingDestroyAllAdmin) : null;
    }

    #[Computed]
    public function unblockingDevice(): ?BlockedDevice
    {
        return $this->confirmingUnblock ? BlockedDevice::with('admin')->find($this->confirmingUnblock) : null;
    }

    #[Computed]
    public function clearingBlocksAdmin(): ?Admin
    {
        return $this->confirmingClearBlocks ? Admin::find($this->confirmingClearBlocks) : null;
    }

    /* ---------------- حذف یک نشست (پورت destroy — gate sessions.exit) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('sessions.exit') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function destroySession(): void
    {
        auth('adminPanel')->user()?->can('sessions.exit') ?: abort(403);

        if (! $this->confirmingDelete) {
            return;
        }

        $session = AdminSession::find($this->confirmingDelete);

        if (! $session) {
            $this->confirmingDelete = null;

            return;
        }

        // جلوگیری از حذف نشست فعلی خود ادمین
        if ($session->session_id === Session::getId()) {
            $this->confirmingDelete = null;
            $this->dispatch('toast', message: 'نمی‌توانید نشست فعلی خود را حذف کنید', type: 'error');

            return;
        }

        $adminName = auth('adminPanel')->user()->full_name;
        $targetAdminName = $session->admin->full_name ?? 'نامشخص';
        $deviceInfo = $session->device_name ?? 'دستگاه نامشخص';
        $ipAddress = $session->ip_address ?? 'نامشخص';

        // حذف نشست از دیتابیس
        $session->delete();

        // حذف نشست از جدول sessions لاراول
        try {
            DB::table('sessions')->where('id', $session->session_id)->delete();
        } catch (\Exception $e) {
            // اگر از file driver استفاده می‌شود، نیازی به این کار نیست
        }

        // ثبت لاگ دستی
        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action' => 'logout_session',
                'target_admin' => $targetAdminName,
                'target_admin_id' => $session->admin_id,
                'device_info' => $deviceInfo,
                'ip_address' => $ipAddress,
                'session_id' => $session->session_id,
            ])
            ->log("مدیر {$adminName} نشست {$targetAdminName} را از دستگاه {$deviceInfo} خارج کرد");

        $this->confirmingDelete = null;
        unset($this->sessions);

        $this->dispatch('toast', message: 'نشست مورد نظر با موفقیت حذف شد', type: 'success');
    }

    /* ---------------- حذف تمام نشست‌های یک ادمین (پورت destroyAllAdminSessions) ---------------- */

    public function confirmDestroyAllAdmin(int $adminId): void
    {
        auth('adminPanel')->user()->isCreator() ?: abort(403);

        $this->confirmingDestroyAllAdmin = $adminId;
    }

    public function destroyAllAdminSessions(): void
    {
        auth('adminPanel')->user()->isCreator() ?: abort(403);

        if (! $this->confirmingDestroyAllAdmin) {
            return;
        }

        $adminId = $this->confirmingDestroyAllAdmin;

        $targetAdmin = Admin::find($adminId);
        $adminName = auth('adminPanel')->user()->full_name;
        $targetAdminName = $targetAdmin->full_name ?? 'نامشخص';

        $sessions = AdminSession::where('admin_id', $adminId)
            ->where('session_id', '!=', Session::getId())
            ->get();

        $deletedCount = 0;
        $devicesList = [];

        foreach ($sessions as $session) {
            $devicesList[] = $session->device_name ?? 'دستگاه نامشخص';
            $session->delete();
            try {
                DB::table('sessions')->where('id', $session->session_id)->delete();
            } catch (\Exception $e) {
            }
            $deletedCount++;
        }

        // ثبت لاگ دستی
        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action' => 'logout_all_sessions',
                'target_admin' => $targetAdminName,
                'target_admin_id' => $adminId,
                'devices_count' => $deletedCount,
                'devices_list' => $devicesList,
            ])
            ->log("مدیر {$adminName} تمام نشست‌های {$targetAdminName} ({$deletedCount} نشست) را خارج کرد");

        $this->confirmingDestroyAllAdmin = null;
        unset($this->sessions);

        $this->dispatch('toast', message: "{$deletedCount} نشست با موفقیت حذف شد", type: 'success');
    }

    /* ---------------- پاک‌سازی نشست‌های غیرفعال (پورت clearInactive) ---------------- */

    public function clearInactive(): void
    {
        auth('adminPanel')->user()->isCreator() ?: abort(403);

        $adminName = auth('adminPanel')->user()->full_name;
        $count = AdminSession::where('last_activity', '<', now()->subDays(30))->delete();

        // ثبت لاگ دستی
        if ($count > 0) {
            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'clear_inactive_sessions',
                    'deleted_count' => $count,
                    'days_threshold' => 30,
                ])
                ->log("مدیر {$adminName} تعداد {$count} نشست غیرفعال را حذف کرد");
        }

        unset($this->sessions);

        $this->dispatch('toast', message: "{$count} نشست غیرفعال حذف شد", type: 'success');
    }

    /* ---------------- خروج از سایر دستگاه‌های ادمین فعلی (پورت logoutOtherDevices) ---------------- */

    public function logoutOtherDevices(): void
    {
        auth('adminPanel')->user()->isCreator() ?: abort(403);

        $adminId = auth('adminPanel')->id();
        $adminName = auth('adminPanel')->user()->full_name;

        $sessions = AdminSession::where('admin_id', $adminId)
            ->where('session_id', '!=', Session::getId())
            ->get();

        $deletedCount = 0;

        foreach ($sessions as $session) {
            $session->delete();
            try {
                DB::table('sessions')->where('id', $session->session_id)->delete();
            } catch (\Exception $e) {
            }
            $deletedCount++;
        }

        // ثبت لاگ دستی
        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action' => 'logout_other_devices',
                'deleted_count' => $deletedCount,
            ])
            ->log("مدیر {$adminName} از {$deletedCount} دستگاه دیگر خود خارج شد");

        unset($this->sessions);

        $this->dispatch('toast', message: "{$deletedCount} دستگاه با موفقیت خارج شد", type: 'success');
    }

    /* ---------------- بلاک دستگاه (پورت blockSession) ---------------- */

    public function openBlock(int $id): void
    {
        auth('adminPanel')->user()?->can('sessions.blocked') ?: abort(403);

        $session = AdminSession::find($id);

        if (! $session) {
            $this->dispatch('toast', message: 'نشست یافت نشد.', type: 'error');

            return;
        }

        // جلوگیری از بلاک نشست فعلی خود کاربر
        if ($session->session_id === Session::getId()) {
            $this->dispatch('toast', message: 'نمی‌توانید نشست فعلی خود را بلاک کنید', type: 'error');

            return;
        }

        $this->blockingSession = $id;
        $this->blockType = 'session';
        $this->blockDuration = 'permanent';
        $this->blockReason = '';
        $this->showBlockModal = true;
    }

    public function blockSession(): void
    {
        auth('adminPanel')->user()?->can('sessions.blocked') ?: abort(403);

        $session = AdminSession::find($this->blockingSession);

        if (! $session) {
            $this->showBlockModal = false;
            $this->dispatch('toast', message: 'نشست یافت نشد.', type: 'error');

            return;
        }

        // جلوگیری از بلاک نشست فعلی خود کاربر
        if ($session->session_id === Session::getId()) {
            $this->showBlockModal = false;
            $this->dispatch('toast', message: 'نمی‌توانید نشست فعلی خود را بلاک کنید', type: 'error');

            return;
        }

        $this->validate([
            'blockType' => 'required|in:session,ip,device,browser,all',
            'blockDuration' => 'nullable|in:permanent,1day,1week,1month',
            'blockReason' => 'nullable|string|max:500',
        ], [], [
            'blockType' => 'نوع بلاک',
            'blockDuration' => 'مدت زمان بلاک',
            'blockReason' => 'دلیل بلاک',
        ]);

        $adminName = auth('adminPanel')->user()->full_name;
        $targetAdminName = $session->admin->full_name ?? 'نامشخص';
        $blockType = $this->blockType;
        $duration = $this->blockDuration ?: 'permanent';
        $reason = $this->blockReason ?: 'بدون دلیل';

        // محاسبه زمان انقضا
        $blockedUntil = null;
        $isPermanent = false;

        if ($duration === 'permanent') {
            $isPermanent = true;
            $durationText = 'دائمی';
        } elseif ($duration === '1day') {
            $blockedUntil = now()->addDay();
            $durationText = '1 روز';
        } elseif ($duration === '1week') {
            $blockedUntil = now()->addWeek();
            $durationText = '1 هفته';
        } elseif ($duration === '1month') {
            $blockedUntil = now()->addMonth();
            $durationText = '1 ماه';
        } else {
            $durationText = 'نامشخص';
        }

        // تولید اثر انگشت
        $deviceFingerprint = $this->generateDeviceFingerprint($session->user_agent);
        $browserFingerprint = $this->generateBrowserFingerprint($session->user_agent);

        $blockedItems = [];

        // بلاک بر اساس نوع انتخاب شده
        if (in_array($blockType, ['session', 'all'])) {
            BlockedDevice::create([
                'admin_id' => $session->admin_id,
                'session_id' => $session->session_id,
                'block_type' => 'session',
                'reason' => $reason,
                'blocked_until' => $blockedUntil,
                'is_permanent' => $isPermanent,
            ]);
            $blockedItems[] = 'نشست';
        }

        if (in_array($blockType, ['ip', 'all'])) {
            BlockedDevice::create([
                'admin_id' => $session->admin_id,
                'ip_address' => $session->ip_address,
                'block_type' => 'ip',
                'reason' => $reason,
                'blocked_until' => $blockedUntil,
                'is_permanent' => $isPermanent,
            ]);
            $blockedItems[] = 'آیپی';
        }

        if (in_array($blockType, ['device', 'all'])) {
            BlockedDevice::create([
                'admin_id' => $session->admin_id,
                'device_fingerprint' => $deviceFingerprint,
                'block_type' => 'device',
                'reason' => $reason,
                'blocked_until' => $blockedUntil,
                'is_permanent' => $isPermanent,
            ]);
            $blockedItems[] = 'دستگاه';
        }

        if (in_array($blockType, ['browser', 'all'])) {
            BlockedDevice::create([
                'admin_id' => $session->admin_id,
                'browser_fingerprint' => $browserFingerprint,
                'block_type' => 'browser',
                'reason' => $reason,
                'blocked_until' => $blockedUntil,
                'is_permanent' => $isPermanent,
            ]);
            $blockedItems[] = 'مرورگر';
        }

        // حذف نشست فعلی
        $session->delete();
        try {
            DB::table('sessions')->where('id', $session->session_id)->delete();
        } catch (\Exception $e) {
        }

        // ثبت لاگ دستی برای بلاک
        $blockedItemsText = implode('، ', $blockedItems);
        $deviceInfo = $session->device_name ?? 'دستگاه نامشخص';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action' => 'block_session',
                'target_admin' => $targetAdminName,
                'target_admin_id' => $session->admin_id,
                'block_type' => $blockType,
                'blocked_items' => $blockedItems,
                'duration' => $durationText,
                'reason' => $reason,
                'device_info' => $deviceInfo,
                'ip_address' => $session->ip_address,
                'session_id' => $session->session_id,
                'blocked_until' => $blockedUntil,
            ])
            ->log("مدیر {$adminName} دسترسی {$targetAdminName} را از نوع {$blockedItemsText} به مدت {$durationText} مسدود کرد");

        $this->showBlockModal = false;
        $this->blockingSession = null;
        unset($this->sessions, $this->blockedDevices);

        $this->dispatch('toast', message: 'بلاک با موفقیت انجام شد', type: 'success');
    }

    /* ---------------- رفع بلاک (پورت unblockDevice — gate sessions.blocked) ---------------- */

    public function confirmUnblock(int $id): void
    {
        auth('adminPanel')->user()?->can('sessions.blocked') ?: abort(403);

        $this->confirmingUnblock = $id;
    }

    public function unblockDevice(): void
    {
        auth('adminPanel')->user()?->can('sessions.blocked') ?: abort(403);

        if (! $this->confirmingUnblock) {
            return;
        }

        $blocked = BlockedDevice::find($this->confirmingUnblock);

        if (! $blocked) {
            $this->confirmingUnblock = null;

            return;
        }

        $adminName = auth('adminPanel')->user()->full_name;
        $blockType = $blocked->block_type;
        $targetAdminName = $blocked->admin->full_name ?? 'نامشخص';

        $blocked->delete();

        // ثبت لاگ دستی برای آنبلاک
        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action' => 'unblock_device',
                'target_admin' => $targetAdminName,
                'target_admin_id' => $blocked->admin_id,
                'block_type' => $blockType,
                'unblocked_by' => $adminName,
            ])
            ->log("مدیر {$adminName} مسدودیت نوع {$blockType} برای {$targetAdminName} را لغو کرد");

        $this->confirmingUnblock = null;
        unset($this->blockedDevices);

        $this->dispatch('toast', message: 'بلاک دستگاه با موفقیت حذف شد', type: 'success');
    }

    /* ---------------- پاک‌سازی بلاک‌های یک ادمین (پورت clearAdminBlocks) ---------------- */

    public function confirmClearBlocks(int $adminId): void
    {
        auth('adminPanel')->user()?->can('sessions.blocked') ?: abort(403);

        $this->confirmingClearBlocks = $adminId;
    }

    public function clearAdminBlocks(): void
    {
        auth('adminPanel')->user()?->can('sessions.blocked') ?: abort(403);

        if (! $this->confirmingClearBlocks) {
            return;
        }

        $adminId = $this->confirmingClearBlocks;

        $targetAdmin = Admin::find($adminId);
        $adminName = auth('adminPanel')->user()->full_name;
        $targetAdminName = $targetAdmin->full_name ?? 'نامشخص';

        $count = BlockedDevice::where('admin_id', $adminId)->delete();

        // ثبت لاگ دستی
        if ($count > 0) {
            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'clear_admin_blocks',
                    'target_admin' => $targetAdminName,
                    'target_admin_id' => $adminId,
                    'deleted_count' => $count,
                ])
                ->log("مدیر {$adminName} تمام {$count} مسدودیت {$targetAdminName} را پاک کرد");
        }

        $this->confirmingClearBlocks = null;
        unset($this->blockedDevices);

        $this->dispatch('toast', message: "{$count} بلاک با موفقیت حذف شد", type: 'success');
    }

    /* ------------------------------------------------------------------
     | تولید اثر انگشت (پورت generateDeviceFingerprint / generateBrowserFingerprint)
     ------------------------------------------------------------------ */

    private function generateDeviceFingerprint($userAgent): string
    {
        $browser = $this->getBrowserName($userAgent);
        $platform = $this->getPlatformName($userAgent);
        $deviceType = $this->getDeviceType($userAgent);

        return md5($browser.$platform.$deviceType);
    }

    private function generateBrowserFingerprint($userAgent): string
    {
        $browser = $this->getBrowserName($userAgent);
        $browserVersion = $this->getBrowserVersion($userAgent);

        return md5($browser.$browserVersion);
    }

    private function getBrowserVersion($userAgent): string
    {
        if (preg_match('/Chrome\/(\d+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Firefox\/(\d+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Edg\/(\d+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Safari\/(\d+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/OPR\/(\d+)/', $userAgent, $matches)) return $matches[1];

        return 'unknown';
    }

    private function getBrowserName($userAgent): string
    {
        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) return 'Safari';
        if (strpos($userAgent, 'Edg') !== false) return 'Edge';
        if (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) return 'Opera';

        return 'Unknown';
    }

    private function getPlatformName($userAgent): string
    {
        if (strpos($userAgent, 'Windows') !== false) return 'Windows';
        if (strpos($userAgent, 'Mac') !== false) return 'macOS';
        if (strpos($userAgent, 'Linux') !== false && strpos($userAgent, 'Android') === false) return 'Linux';
        if (strpos($userAgent, 'Android') !== false) return 'Android';
        if (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) return 'iOS';

        return 'Unknown';
    }

    private function getDeviceType($userAgent): string
    {
        if (preg_match('/(mobile|android|iphone|ipod|blackberry|windows phone)/i', $userAgent)) return 'mobile';
        if (preg_match('/(ipad|tablet)/i', $userAgent)) return 'tablet';

        return 'desktop';
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    /** آیکون دستگاه برای جدول (نگاشت فون‌آیکون قدیمی به آیکون‌های پنل) */
    public static function deviceIcon(string $deviceType): string
    {
        return match ($deviceType) {
            'mobile' => 'smartphone',
            'tablet' => 'tablet',
            'desktop' => 'monitor',
            default => 'help-circle',
        };
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.sessions')
            ->layout('admin-panel::layout');
    }
}

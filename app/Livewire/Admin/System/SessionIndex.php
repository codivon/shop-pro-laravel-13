<?php
// app/Livewire/Admin/System/SessionIndex.php

namespace App\Livewire\Admin\System;

use App\Models\Admin;
use App\Models\AdminSession;
use App\Models\BlockedDevice;
use App\Support\DeviceFingerprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
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

    /** فاز ۷۳ — مدت سفارشی بلاک (مقدار + واحد) */
    public string $blockCustomAmount = '';
    public string $blockCustomUnit = 'days';

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
        $query = BlockedDevice::with('admin')->orderBy('created_at', 'desc');

        // فاز ۷۲ — بن اکانت‌ها فقط برای سازنده قابل مشاهده‌اند
        if (! auth('adminPanel')->user()->isCreator()) {
            $query->where('block_type', '!=', 'account');
        }

        return $query->paginate(20, ['*'], 'blockedPage');
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

    /** فاز ۷۲ — نشست هدف مودال بلاک (برای نمایش اطلاعات ادمین/دستگاه در مودال جدید) */
    #[Computed]
    public function blockingSessionModel(): ?AdminSession
    {
        return $this->blockingSession ? AdminSession::with('admin')->find($this->blockingSession) : null;
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

        // فاز ۷۲ — نشست اصلی تا ۴۸ ساعت از دستگاه دیگر قابل خروج نیست
        if ($this->guardPrimaryProtection($session)) {
            $this->confirmingDelete = null;

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
        $skippedProtected = 0;

        foreach ($sessions as $session) {
            // فاز ۷۲ — نشست اصلی تا ۴۸ ساعت از دستگاه دیگر حذف نمی‌شود
            if ($this->isProtectedFromViewer($session)) {
                $skippedProtected++;

                continue;
            }

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

        $message = "{$deletedCount} نشست با موفقیت حذف شد";

        if ($skippedProtected > 0) {
            $message .= " — {$skippedProtected} نشست اصلی تا ۴۸ ساعت محافظت می‌شود";
        }

        $this->dispatch('toast', message: $message, type: 'success');
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
        $skippedProtected = 0;

        foreach ($sessions as $session) {
            // فاز ۷۲ — نشست اصلی تا ۴۸ ساعت از دستگاه دیگر حذف نمی‌شود
            if ($this->isProtectedFromViewer($session)) {
                $skippedProtected++;

                continue;
            }

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

        $message = "{$deletedCount} دستگاه با موفقیت خارج شد";

        if ($skippedProtected > 0) {
            $message .= " — {$skippedProtected} نشست اصلی تا ۴۸ ساعت محافظت می‌شود";
        }

        $this->dispatch('toast', message: $message, type: 'success');
    }

    /* ---------------- بلاک دستگاه + بن اکانت (پورت blockSession + فاز ۷۲) ---------------- */

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
        $this->blockCustomAmount = '';
        $this->blockCustomUnit = 'days';
        $this->blockReason = '';
        $this->showBlockModal = true;
    }

    public function blockSession(): void
    {
        auth('adminPanel')->user()?->can('sessions.blocked') ?: abort(403);

        $session = AdminSession::with('admin')->find($this->blockingSession);

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

        // فاز ۷۲ — نشست اصلی تا ۴۸ ساعت از دستگاه دیگر قابل بلاک نیست
        if ($this->guardPrimaryProtection($session)) {
            $this->showBlockModal = false;

            return;
        }

        $isAccountBan = $this->blockType === 'account';

        // فاز ۷۲ — بن اکانت فقط ابزار سازنده است
        if ($isAccountBan) {
            auth('adminPanel')->user()->isCreator() ?: abort(403);

            $targetAdmin = $session->admin;

            if (! $targetAdmin || $targetAdmin->level === 'creator') {
                $this->showBlockModal = false;
                $this->dispatch('toast', message: 'بن اکانت فقط برای مدیران (غیر سازنده) امکان‌پذیر است', type: 'error');

                return;
            }

            if ($targetAdmin->id === auth('adminPanel')->id()) {
                $this->showBlockModal = false;
                $this->dispatch('toast', message: 'نمی‌توانید حساب خودتان را مسدود کنید', type: 'error');

                return;
            }
        }

        $this->validate([
            'blockType' => 'required|in:session,ip,device,browser,all,account',
            // فاز ۷۳ — پیش‌فرض ۱۲ ساعت + مدت سفارشی اضافه شد
            'blockDuration' => 'nullable|in:permanent,12hours,1day,1week,1month,custom',
            'blockCustomAmount' => 'nullable|required_if:blockDuration,custom|integer|min:1|max:999',
            'blockCustomUnit' => 'nullable|required_if:blockDuration,custom|in:hours,days,weeks,months',
            'blockReason' => 'nullable|string|max:500',
        ], [], [
            'blockType' => 'نوع بلاک',
            'blockDuration' => 'مدت زمان بلاک',
            'blockCustomAmount' => 'مقدار مدت سفارشی',
            'blockCustomUnit' => 'واحد مدت سفارشی',
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
        } elseif ($duration === '12hours') {
            // فاز ۷۳ — پیش‌فرض جدید
            $blockedUntil = now()->addHours(12);
            $durationText = '12 ساعت';
        } elseif ($duration === '1day') {
            $blockedUntil = now()->addDay();
            $durationText = '1 روز';
        } elseif ($duration === '1week') {
            $blockedUntil = now()->addWeek();
            $durationText = '1 هفته';
        } elseif ($duration === '1month') {
            $blockedUntil = now()->addMonth();
            $durationText = '1 ماه';
        } elseif ($duration === 'custom') {
            // فاز ۷۳ — مدت سفارشی: مقدار + واحد (ساعت/روز/هفته/ماه)
            $amount = max(1, (int) $this->blockCustomAmount);
            $unit = $this->blockCustomUnit ?: 'days';

            $blockedUntil = match ($unit) {
                'hours'  => now()->addHours($amount),
                'days'   => now()->addDays($amount),
                'weeks'  => now()->addWeeks($amount),
                'months' => now()->addMonths($amount),
                default  => now()->addDays($amount),
            };

            $durationText = $amount.' '.match ($unit) {
                'hours'  => 'ساعت',
                'weeks'  => 'هفته',
                'months' => 'ماه',
                default  => 'روز',
            };
        } else {
            $durationText = 'نامشخص';
        }

        if ($isAccountBan) {
            // ─── بن اکانت: کل حساب قفل می‌شود و همهٔ نشست‌ها خروج می‌شوند ───
            BlockedDevice::create([
                'admin_id' => $session->admin_id,
                'block_type' => 'account',
                'reason' => $reason,
                'blocked_until' => $blockedUntil,
                'is_permanent' => $isPermanent,
            ]);

            $bannedSessions = AdminSession::where('admin_id', $session->admin_id)->get();

            foreach ($bannedSessions as $banned) {
                try {
                    DB::table('sessions')->where('id', $banned->session_id)->delete();
                } catch (\Exception $e) {
                }
                $banned->delete();
            }

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'ban_account',
                    'target_admin' => $targetAdminName,
                    'target_admin_id' => $session->admin_id,
                    'duration' => $durationText,
                    'reason' => $reason,
                    'terminated_sessions' => $bannedSessions->count(),
                ])
                ->log("سازنده {$adminName} حساب {$targetAdminName} را به مدت {$durationText} بن کرد (بن اکانت)");

            $this->showBlockModal = false;
            $this->blockingSession = null;
            unset($this->sessions, $this->blockedDevices);

            $this->dispatch('toast', message: 'حساب مدیر با موفقیت مسدود شد و از همهٔ دستگاه‌ها خارج گردید', type: 'success');

            return;
        }

        // تولید اثر انگشت (مرکزی — فاز ۷۲)
        $deviceFingerprint = DeviceFingerprint::device((string) $session->user_agent);
        $browserFingerprint = DeviceFingerprint::browser((string) $session->user_agent);

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

        // فاز ۷۲ — رفع «بن اکانت» فقط توسط سازنده
        if ($blocked->isAccountBan() && ! auth('adminPanel')->user()->isCreator()) {
            $this->confirmingUnblock = null;
            $this->dispatch('toast', message: 'رفع بن اکانت فقط توسط سازنده امکان‌پذیر است', type: 'error');

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

        $this->dispatch('toast', message: $blocked->isAccountBan() ? 'بن اکانت با موفقیت رفع شد' : 'بلاک دستگاه با موفقیت حذف شد', type: 'success');
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

        // فاز ۷۲ — پاک‌سازی بن اکانت فقط توسط سازنده
        if (! auth('adminPanel')->user()->isCreator()
            && BlockedDevice::where('admin_id', $adminId)->where('block_type', 'account')->exists()) {
            $this->confirmingClearBlocks = null;
            $this->dispatch('toast', message: 'حذف بن اکانت فقط توسط سازنده امکان‌پذیر است', type: 'error');

            return;
        }

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
     | فاز ۷۲ — محافظت ۴۸ ساعتهٔ «سیستم اصلی»
     |
     | اولین دستگاهی که برای مدیر نشست می‌سازد «سیستم اصلی» است؛ اگر کسی با
     | اطلاعات همان مدیر از دستگاه دیگر (آیپی/مرورگر/سیستم متفاوت) وارد شد،
     | تا ۴۸ ساعت نمی‌تواند نشست اصلی را بن/خروج کند. از همان دستگاه اصلی
     | همهٔ دکمه‌ها فعال می‌مانند. سازنده مستثناست و همه‌کار می‌کند (فاز ۷۴).
     ------------------------------------------------------------------ */

    /** اثر انگشت نشستِ بینندهٔ فعلی (از ردیف admin_sessions خودش، وگرنه محاسبهٔ زنده) */
    private function viewerSessionFingerprint(): string
    {
        $row = AdminSession::query()
            ->where('session_id', Session::getId())
            ->orderByDesc('last_activity')
            ->first();

        return $row?->device_fingerprint ?: DeviceFingerprint::session(Request::instance());
    }

    /** آیا این نشست برای بینندهٔ فعلی محافظت‌شده است؟ (نشست اصلی + پنجرهٔ فعال + دستگاه متفاوت)
     *
     * فاز ۷۴ — استثنای سازنده: سازنده از قید محافظت ۴۸ ساعته مستثناست و
     * می‌تواند از هر دستگاهی همهٔ نشست‌ها را خروج/بلاک کند (درخواست کاربر:
     * «این مورد برای creator مستثنی باشه، سازنده باید بتونه همه کار بکنه»).
     * محافظت برای بقیهٔ مدیرها به قوت خودش باقی است.
     */
    public function isProtectedFromViewer(AdminSession $session): bool
    {
        // سازنده بیننده است — هیچ نشستی از او پنهان/محافظت نمی‌شود
        if (auth('adminPanel')->user()?->isCreator()) {
            return false;
        }

        if (! $session->isPrimaryProtected()) {
            return false;
        }

        // نشست فعلی خود بیننده هرگز «محافظت‌شده از او» نیست
        if ($session->session_id === Session::getId()) {
            return false;
        }

        return $session->device_fingerprint !== $this->viewerSessionFingerprint();
    }

    /** گارد سرور-side — true یعنی عملیات رد شد و توست خطا نمایش داده شد */
    private function guardPrimaryProtection(AdminSession $session): bool
    {
        if ($this->isProtectedFromViewer($session)) {
            $this->dispatch('toast', message: 'این نشست، «سیستم اصلی» مدیر است و تا ۴۸ ساعت از دستگاه دیگر قابل خروج یا مسدودسازی نیست.', type: 'error');

            return true;
        }

        return false;
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

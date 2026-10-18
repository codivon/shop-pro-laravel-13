<?php

namespace App\Livewire\Admin\Users;

use App\Models\NotificationManage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('اعلان‌های کاربر')]
class UserNotifications extends Component
{
    use WithPagination;

    public User $user;

    public int $perPage = 20;

    /* ---------------- مودال ایجاد/ویرایش ---------------- */

    public bool $notifModal = false;

    public ?int $editingNotifId = null;

    public string $notifTitle = '';

    public string $notifPriority = 'low';

    public string $notifMessage = '';

    public bool $notifPopup = false;

    public function mount(User $user): void
    {
        auth('adminPanel')->user()?->can('users.view') ?: abort(403);

        $this->user = $user;
    }

    protected function notificationIds()
    {
        // پیوت کاربران — مثل قدیمی ستون user_id (نه seller_id)
        return DB::table('notification_manage_users')
            ->where('user_id', $this->user->id)
            ->pluck('notification_manage_id');
    }

    #[Computed]
    public function notifications()
    {
        return NotificationManage::query()
            ->where('private', 'user')
            ->whereIn('id', $this->notificationIds())
            ->latest('id')
            ->paginate($this->perPage);
    }

    /**
     * وضعیت خوانده‌شدن اعلان برای این کاربر (پیوت read/read_at).
     * باگ قدیمی: ->first()->read هنگام نبود ردیف پیوت خطای نال می‌داد — با value خنثی شد.
     */
    public function notifRead(NotificationManage $notification): bool
    {
        return (bool) DB::table('notification_manage_users')
            ->where('notification_manage_id', $notification->id)
            ->where('user_id', $this->user->id)
            ->value('read');
    }

    /* ---------------- ایجاد / ویرایش (حذف در قدیمی وجود نداشت) ---------------- */

    public function openNotifModal(): void
    {
        auth('adminPanel')->user()?->can('users.view') ?: abort(403);

        $this->reset(['editingNotifId', 'notifTitle', 'notifPriority', 'notifMessage', 'notifPopup']);
        $this->notifPriority = 'low';
        $this->notifModal = true;
    }

    public function editNotif(int $id): void
    {
        auth('adminPanel')->user()?->can('users.view') ?: abort(403);

        $notification = NotificationManage::where('id', $id)->where('private', 'user')->first();

        if (! $notification) {
            $this->dispatch('toast', message: 'اعلان یافت نشد.', type: 'error');

            return;
        }

        $this->editingNotifId = $notification->id;
        $this->notifTitle = (string) ($notification->title ?? '');
        $this->notifPriority = (string) ($notification->priority ?? 'low');
        $this->notifMessage = (string) ($notification->message ?? '');
        $this->notifPopup = (bool) $notification->popup;
        $this->notifModal = true;
    }

    public function storeNotif(): void
    {
        auth('adminPanel')->user()?->can('users.view') ?: abort(403);

        $this->validate([
            'notifMessage' => 'required',
            'notifPriority' => 'in:low,medium,high',
        ], ['notifMessage.required' => 'متن اعلان الزامی است.']);

        $notification = new NotificationManage();
        $notification->admin_id = auth('adminPanel')->id();
        $notification->title = $this->notifTitle;
        $notification->message = $this->notifMessage;
        $notification->private = 'user';
        $notification->priority = $this->notifPriority;
        $notification->popup = $this->notifPopup;
        $notification->save();

        // پیوت مثل قدیمی notification_store
        $notification->users()->attach($this->user->id);

        $this->notifModal = false;
        $this->dispatch('toast', message: 'اعلان با موفقیت ایجاد شد.', type: 'success');
    }

    public function updateNotif(): void
    {
        auth('adminPanel')->user()?->can('users.view') ?: abort(403);

        $this->validate([
            'notifMessage' => 'required',
            'notifPriority' => 'in:low,medium,high',
        ], ['notifMessage.required' => 'متن اعلان الزامی است.']);

        $notification = NotificationManage::findOrFail($this->editingNotifId);
        $notification->admin_id = auth('adminPanel')->id();
        $notification->title = $this->notifTitle;
        $notification->message = $this->notifMessage;
        $notification->private = 'user';
        $notification->priority = $this->notifPriority;
        $notification->popup = $this->notifPopup;
        $notification->save();

        $this->notifModal = false;
        $this->dispatch('toast', message: 'اعلان با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- ابزارها ---------------- */

    public static function jdate($date, $format = 'Y/m/d H:i'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.users.notifications')
            ->layout('admin-panel::layout');
    }
}

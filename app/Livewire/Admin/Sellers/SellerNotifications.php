<?php

namespace App\Livewire\Admin\Sellers;

use App\Models\NotificationManage;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('اعلان‌های فروشنده')]
class SellerNotifications extends Component
{
    use WithPagination;

    public Seller $seller;

    public int $perPage = 20;

    /* ---------------- مودال ایجاد/ویرایش ---------------- */

    public bool $notifModal = false;

    public ?int $editingNotifId = null;

    public string $notifTitle = '';

    public string $notifPriority = 'low';

    public string $notifMessage = '';

    public bool $notifPopup = false;

    public function mount(Seller $seller): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->seller = $seller;
    }

    protected function notificationIds()
    {
        return DB::table('notification_manage_users')
            ->where('seller_id', $this->seller->id)
            ->pluck('notification_manage_id');
    }

    #[Computed]
    public function notifications()
    {
        return NotificationManage::query()
            ->where('private', 'seller')
            ->whereIn('id', $this->notificationIds())
            ->latest('id')
            ->paginate($this->perPage);
    }

    /** وضعیت خوانده‌شدن اعلان برای این فروشنده (پیوت) */
    public function notifRead(NotificationManage $notification): bool
    {
        return (bool) DB::table('notification_manage_users')
            ->where('notification_manage_id', $notification->id)
            ->where('seller_id', $this->seller->id)
            ->value('read');
    }

    /* ---------------- ایجاد / ویرایش / حذف ---------------- */

    public function openNotifModal(): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->reset(['editingNotifId', 'notifTitle', 'notifPriority', 'notifMessage', 'notifPopup']);
        $this->notifPriority = 'low';
        $this->notifModal = true;
    }

    public function editNotif(int $id): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $notification = NotificationManage::where('id', $id)->where('private', 'seller')->first();

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
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->validate([
            'notifMessage' => 'required',
            'notifPriority' => 'in:low,medium,high',
        ], ['notifMessage.required' => 'متن اعلان الزامی است.']);

        $notification = new NotificationManage();
        $notification->admin_id = auth('adminPanel')->id();
        $notification->title = $this->notifTitle;
        $notification->message = $this->notifMessage;
        $notification->private = 'seller';
        $notification->priority = $this->notifPriority;
        $notification->popup = $this->notifPopup;
        $notification->save();

        $notification->sellers()->attach($this->seller->id);

        $this->notifModal = false;
        $this->dispatch('toast', message: 'اعلان با موفقیت ایجاد شد.', type: 'success');
    }

    public function updateNotif(): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->validate([
            'notifMessage' => 'required',
            'notifPriority' => 'in:low,medium,high',
        ], ['notifMessage.required' => 'متن اعلان الزامی است.']);

        $notification = NotificationManage::findOrFail($this->editingNotifId);
        $notification->admin_id = auth('adminPanel')->id();
        $notification->title = $this->notifTitle;
        $notification->message = $this->notifMessage;
        $notification->private = 'seller';
        $notification->priority = $this->notifPriority;
        $notification->popup = $this->notifPopup;
        $notification->save();

        $this->notifModal = false;
        $this->dispatch('toast', message: 'اعلان با موفقیت ویرایش شد.', type: 'success');
    }

    public function deleteNotif(int $id): void
    {
        auth('adminPanel')->user()?->can('notifications.delete') ?: abort(403);

        NotificationManage::where('id', $id)->where('private', 'seller')->delete();

        $this->dispatch('toast', message: 'اعلان با موفقیت حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.notifications')
            ->layout('admin-panel::layout');
    }
}

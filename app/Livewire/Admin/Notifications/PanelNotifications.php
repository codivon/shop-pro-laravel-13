<?php

namespace App\Livewire\Admin\Notifications;

use App\Support\AdminNotificationTypes;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * اعلان‌های پنل — پورت ۱:۱ MainController::notifications / markRead / markAllRead
 * رندر نوع‌ها عیناً با AdminNotificationTypes::meta + notification_link قدیمی
 */
#[Title('اعلان‌های سیستم')]
class PanelNotifications extends Component
{
    use WithPagination;

    #[Url(as: 'filter', except: 'all')]
    public string $filter = 'all';

    public int $perPage = 15;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('notifications.panel') ?: abort(403);

        // پورت: فیلتر نامعتبر → all
        if (!in_array($this->filter, ['all', 'unread', 'read'])) {
            $this->filter = 'all';
        }
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /* ---------------- لیست (پورت ۱:۱ notifications قدیمی) ---------------- */

    public function getNotificationsProperty()
    {
        $admin = auth('adminPanel')->user();

        $query = $admin->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        }
        if ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->latest()->paginate($this->perPage);
    }

    public function getStatsProperty(): array
    {
        $admin = auth('adminPanel')->user();

        return [
            'total'  => $admin->notifications()->count(),
            'unread' => $admin->unreadNotifications()->count(),
            'today'  => $admin->notifications()->whereDate('created_at', today())->count(),
        ];
    }

    /** متادیتای هر نوع اعلان + کنترل دسترسی (پورت دقیق بلید قدیمی) */
    public function meta($notification): array
    {
        $meta = AdminNotificationTypes::meta($notification->type);
        $meta['allowed'] = is_null($meta['can']) || Gate::allows($meta['can']);

        return $meta;
    }

    /* ---------------- خواندن تکی (پورت ۱:۱ markRead قدیمی) ---------------- */

    public function markRead(string $id): void
    {
        auth('adminPanel')->user()?->can('notifications.panel') ?: abort(403);

        $notification = auth('adminPanel')->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->dispatch('toast', message: 'اعلان خوانده‌شده علامت‌گذاری شد.', type: 'success');
    }

    /* ---------------- خواندن همه (پورت ۱:۱ markAllRead قدیمی) ---------------- */

    public function markAllRead(): void
    {
        auth('adminPanel')->user()?->can('notifications.panel') ?: abort(403);

        $admin = auth('adminPanel')->user();
        $count = $admin->unreadNotifications()->count();
        $admin->unreadNotifications->markAsRead();

        $this->dispatch('toast', message: "همه اعلان‌ها ({$count} مورد) خوانده‌شده علامت‌گذاری شدند.", type: 'success');
    }

    /* ---------------- ابزار ویو (پورت بلید قدیمی) ---------------- */

    public function dayLabel($notification, string $todayLabel, string $yesterdayLabel): string
    {
        $day = jdate($notification->created_at)->format('Y/m/d');

        return $day === $todayLabel ? 'امروز'
            : ($day === $yesterdayLabel ? 'دیروز' : $day);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.notifications.panel')
            ->layout('admin-panel::layout');
    }
}

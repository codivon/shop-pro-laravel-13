<?php

namespace App\Livewire\Admin\Notifications;

use App\Support\AdminNotificationTypes;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * فاز ۱۶ — زنگوله اعلان‌های هدر پنل جدید:
 * کلیک روی زنگوله دیگر به پنل قدیمی نمی‌رود؛ یک دراپ‌داون با آخرین اعلان‌ها
 * باز می‌شود + شمارنده اعلان‌های نخوانده روی آیکون (هر ۶۰ ثانیه به‌روز می‌شود).
 * کلیک روی هر اعلان: علامت‌گذاری به‌عنوان خوانده‌شده + ناوبری SPA به لینک پنل جدید.
 */
class HeaderNotifications extends Component
{
    public bool $open = false;

    /** شمارنده نخوانده‌ها (سرورمحور — با poll به‌روز می‌شود) */
    public int $unread = 0;

    public function mount(): void
    {
        $this->unread = auth('adminPanel')->user()?->unreadNotifications()->count() ?? 0;
    }

    /** آخرین اعلان‌ها برای دراپ‌داون */
    public function getLatestProperty()
    {
        $admin = auth('adminPanel')->user();

        return $admin ? $admin->notifications()->latest()->limit(6)->get() : collect();
    }

    /** متادیتای نمایشی هر اعلان — آیکون lucide پنل جدید + رنگ گرادیان + دسترسی + لینک */
    public function meta($notification): array
    {
        $meta = AdminNotificationTypes::meta($notification->type);
        $meta['allowed'] = is_null($meta['can']) || Gate::allows($meta['can']);
        $meta['link'] = $meta['allowed'] ? AdminNotificationTypes::npLink($notification) : null;
        $meta['unread'] = is_null($notification->read_at);

        // آیکون‌های «icon-*» مال پنل قدیمی‌اند؛ معادل lucide پنل جدید:
        $meta['lucide'] = match ($notification->type) {
            'OrderPaid'              => 'shopping-cart',
            'SellerRegistered'       => 'user-plus',
            'SellerEditProfile'      => 'user-check',
            'UserRegistered'         => 'user',
            'ContactCreated'         => 'mail',
            'TicketCreated'          => 'life-buoy',
            'CommentPostCreated'     => 'message-square',
            'CommentProductCreated'  => 'message-circle',
            'QuestionProductCreated' => 'circle-help',
            'UserRequestDeposit',
            'SellerRequestDeposit'   => 'credit-card',
            'SellerProductCreated',
            'SellerProductUpdate'    => 'package',
            default                  => 'bell',
        };

        return $meta;
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    /** علامت‌گذاری یک اعلان + ناوبری SPA (پورت markRead قدیمی) */
    public function markAndGo(string $id, string $url): void
    {
        $admin = auth('adminPanel')->user();

        if (! $admin) {
            return;
        }

        $notification = $admin->notifications()->find($id);

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $this->unread = $admin->unreadNotifications()->count();
        $this->open = false;

        $this->redirect($url, navigate: true);
    }

    /** علامت‌گذاری همه (پورت markAllRead قدیمی) */
    public function markAllRead(): void
    {
        $admin = auth('adminPanel')->user();

        if (! $admin) {
            return;
        }

        $count = $admin->unreadNotifications()->count();
        $admin->unreadNotifications->markAsRead();
        $this->unread = 0;

        $this->dispatch('toast', message: "همه اعلان‌ها ({$count} مورد) خوانده‌شده علامت‌گذاری شدند.", type: 'success');
    }

    /** به‌روزرسانی شمارنده با wire:poll */
    public function refreshCount(): void
    {
        $this->unread = auth('adminPanel')->user()?->unreadNotifications()->count() ?? 0;
    }

    /** برچسب زمان نسبی (امروز/دیروز/تاریخ) — الگوی همان helper جلالی پنل + ارقام فارسی */
    public static function timeLabel($notification): string
    {
        try {
            $createdAt = $notification->created_at;

            $label = $createdAt->isToday()
                ? 'امروز ساعت '.$createdAt->format('H:i')
                : ($createdAt->isYesterday()
                    ? 'دیروز ساعت '.$createdAt->format('H:i')
                    : jdate($createdAt)->format('Y/m/d'));

            return strtr($label, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
        } catch (\Throwable) {
            return '';
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.notifications.header');
    }
}

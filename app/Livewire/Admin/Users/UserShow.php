<?php

namespace App\Livewire\Admin\Users;

use App\Models\NotificationManage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('مشخصات کاربر')]
class UserShow extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        auth('adminPanel')->user()?->can('users.view') ?: abort(403);

        $this->user = $user;
    }

    /* ---------------- آمار (پورت کارت‌های show قدیمی) ---------------- */

    #[Computed]
    public function walletBalance(): int
    {
        return (int) $this->user->getWallet()->balance();
    }

    #[Computed]
    public function ordersCount(): int
    {
        return $this->user->orders()->count();
    }

    #[Computed]
    public function paidOrdersSum(): int
    {
        return (int) $this->user->orders()->paid()->sum('price');
    }

    #[Computed]
    public function commentsCount(): int
    {
        return $this->user->comments()->count();
    }

    #[Computed]
    public function cartProductsCount(): int
    {
        return $this->user->cart ? $this->user->cart->products()->count() : 0;
    }

    #[Computed]
    public function viewsToday(): int
    {
        return $this->user->views()->whereDate('created_at', now())->count();
    }

    #[Computed]
    public function viewsTotal(): int
    {
        return $this->user->views()->count();
    }

    /* ---------------- اعلان‌ها (پورت show قدیمی) ---------------- */

    protected function notificationIds(): array
    {
        return DB::table('notification_manage_users')
            ->where('user_id', $this->user->id)
            ->pluck('notification_manage_id')
            ->all();
    }

    #[Computed]
    public function notifications()
    {
        return NotificationManage::query()
            ->where('private', 'user')
            ->whereIn('id', $this->notificationIds())
            ->latest('id')
            ->get();
    }

    /**
     * وضعیت خوانده‌شدن اعلان برای این کاربر (پیوت).
     * باگ قدیمی: ->first()->read هنگام نبود ردیف پیوت خطا می‌داد — با value خنثی شد.
     */
    public function notifRead(NotificationManage $notification): bool
    {
        return (bool) DB::table('notification_manage_users')
            ->where('notification_manage_id', $notification->id)
            ->where('user_id', $this->user->id)
            ->value('read');
    }

    /* ---------------- حذف (پورت destroy) ---------------- */

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('users.delete') ?: abort(403);

        if ($this->user->id === auth('adminPanel')->id() || $this->user->level === 'creator') {
            $this->dispatch('toast', message: 'حذف این کاربر مجاز نیست.', type: 'error');

            return;
        }

        if ($this->user->image) {
            Storage::disk('public')->delete($this->user->image);
        }

        $this->user->delete();

        $this->dispatch('toast', message: 'کاربر با موفقیت حذف شد.', type: 'success');

        $this->redirect(route('np.users.index'), navigate: true);
    }

    /* ---------------- ابزارها ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.users.show')
            ->layout('admin-panel::layout');
    }
}

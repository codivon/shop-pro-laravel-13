<?php

namespace App\Livewire\Seller;

use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — اعلان‌های فروشنده (پورت NotificationSellerController@index).
 *
 * مبنای کوئری عیناً قدیم: seller()->notifications()->filter($request) —
 * scopeFilter روی Relation (priority / unread / sort) با Request ساختگی
 * اجرا می‌شود تا همان مسیر کد قدیمی (فیلتر روی pivot خوانده‌نشده و …) حفظ شود.
 *
 * علامت‌گذاری «خوانده شد»: فرم کلاسیک POST به seller.notifications.read
 * (پارامتر notification — کنترلر قدیمی JSON {status, count} می‌دهد و
 * درجا با fetch ردیف خوانده‌شده می‌شود، هم‌الگوی index.js قدیمی).
 */
#[Layout('sellers.layouts.panel')]
#[Title('پیام ها')]
class Notifications extends Component
{
    use WithPagination;

    /* ---------------- فیلترها — همان کلیدهای فرم قدیمی، در URL ---------------- */

    #[Url(as: 'priority', except: 'all')]
    public string $priority = 'all';

    #[Url(as: 'sort', except: 'latest')]
    public string $sort = 'latest';

    /** فقط خوانده‌نشده — چک‌باکس قدیمی با مقدار 'on' کار می‌کرد */
    #[Url(as: 'unread')]
    public bool $unread = false;

    public function updating(string $property): void
    {
        if (in_array($property, ['priority', 'sort', 'unread'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        // Request ساختگی — همان ورودی scopeFilter کنترلر قدیمی
        $request = Request::create('/', 'GET', [
            'priority' => $this->priority,
            'sort' => $this->sort,
            'unread' => $this->unread ? 'on' : null,
        ]);

        $notifications = seller()->notifications()->filter($request)->paginate(20);
        $notificationsCount = seller()->notifications()->filter($request)->count();

        return view('sellers.panel.notifications.index', [
            'notifications' => $notifications,
            'notificationsCount' => $notificationsCount,
        ]);
    }
}

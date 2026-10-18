<?php

namespace App\Livewire\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerInfo;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('داشبورد')]
class Dashboard extends Component
{
    public array $stats = [];

    public string $todayJalali = '';

    public array $recentOrders = [];

    public function mount()
    {
        $this->todayJalali = Jalalian::now()->format('l، j F Y');

        // آمار پایه با کش ۶۰ ثانیه — سبک و سریع
        $this->stats = Cache::remember('np-dashboard-stats', 60, function () {
            return [
                'users' => User::count(),
                'products' => Product::count(),
                'orders' => Order::count(),
                'sellers' => SellerInfo::count(),
                'ordersToday' => Order::whereDate('created_at', today())->count(),
                'usersToday' => User::whereDate('created_at', today())->count(),
                'paidOrders' => Order::where('status', 'paid')->count(),
                'admins' => Admin::count(),
            ];
        });

        $this->recentOrders = Order::query()
            ->latest('id')
            ->take(7)
            ->get(['id', 'user_id', 'name', 'mobile', 'status', 'price', 'created_at'])
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'status' => $o->status,
                'statusLabel' => $this->orderStatusLabel($o->status),
                'price' => $o->price,
                'date' => Jalalian::fromDateTime($o->created_at)->format('Y/m/d'),
            ])
            ->toArray();
    }

    protected function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'unpaid' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'canceled' => 'لغو شده',
            'pending_down_payment' => 'پیش‌پرداخت',
            'active' => 'فعال',
            default => $status,
        };
    }

    /* ---------- فرمترهای نمایش (اعداد فارسی) ---------- */

    public static function fa($value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return strtr((string) $value, array_combine(range(0, 9), $fa));
    }

    public static function number($value): string
    {
        return self::fa(number_format((float) $value));
    }

    public static function toman($value): string
    {
        return self::number($value) . ' تومان';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.dashboard')
            ->layout('admin-panel::layout');
    }
}

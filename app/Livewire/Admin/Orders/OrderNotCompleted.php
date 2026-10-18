<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Price;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Layout('admin-panel::layout')]
#[Title('محصولات منتظر ارسال')]
class OrderNotCompleted extends Component
{
    use WithPagination;

    public function mount(): void
    {
        if (! auth('adminPanel')->user()?->can('orders.index')) {
            abort(403);
        }
    }

    public function render()
    {
        $prices = Price::query()
            ->with(['product:id,title,image,type,slug'])
            ->whereHas('orderItems', function ($q) {
                $q->whereHas('order', fn ($q2) => $q2->notCompleted())
                    ->whereHas('product', fn ($q3) => $q3->physical());
            })
            ->paginate(20);

        return view('admin-panel::livewire.admin.orders.not-completed', [
            'prices' => $prices,
        ]);
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }
}

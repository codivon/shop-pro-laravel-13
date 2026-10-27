<?php

namespace App\Livewire\Seller\Orders;

use App\Models\Price;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — «محصولات منتظر ارسال» فروشنده (پورت OrderSellerController@notCompleted).
 *
 * همان کوئری قدیم: تنوع‌هایی از فروشندهٔ فعلی که سفارشِ notCompleted دارند
 * و محصول‌شان فیزیکی است — با تعداد در انتظار ارسال هر تنوع (pendingToSend).
 */
#[Layout('sellers.layouts.panel')]
#[Title('محصولات منتظر ارسال')]
class NotCompleted extends Component
{
    use WithPagination;

    public function render()
    {
        $prices = Price::whereHas('orderItems', function ($q) {
            $q->where('seller_id', sellerID())
                ->whereHas('order', fn ($q2) => $q2->notCompleted())
                ->whereHas('product', fn ($q3) => $q3->physical());
        })->paginate(20);

        return view('sellers.panel.orders.not-completed', [
            'prices' => $prices,
            'indexUrl' => route('seller.orders.index'),
        ]);
    }
}

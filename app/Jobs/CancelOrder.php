<?php

namespace App\Jobs;

use App\Events\ProductPricesChanged;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;
    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->order && $this->order->status == 'unpaid') {
            $this->order->update([
                'status' => 'canceled',
            ]);

            foreach ($this->order->items as $item) {
                $price = $item->get_price;

                // فاز ۷۸ — آیتم‌های پیش‌فروش هرگز رزرو/کسر موجودی نداشته‌اند؛
                // دست‌زدن به موجودی آن‌ها باعث بادکردن غیرواقعی انبار می‌شود
                if ($price && ! $item->product->pre_order) {
                    $price->unreserveStock(
                        $item->quantity,
                        $this->order->id,
                        $item->id,
                        "لغو خودکار سفارش - آزادسازی رزرو"
                    );

                    $price->update([
                        'stock' => $price->stock + $item->quantity
                    ]);

                    event(new ProductPricesChanged($price->product));
                }
            }

            foreach ($this->order->reservedOrders()->get() as $reserved_order) {
                $reserved_order->update([
                    'reserve' => true
                ]);
            }

            $this->order->reservedOrders()->detach();
        }
    }
}

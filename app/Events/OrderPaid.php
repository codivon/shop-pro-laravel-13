<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaid implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
        // امنیتی: کانال خصوصی — سفارش حاوی اطلاعات شخصی مشتری است
        return new PrivateChannel('orders');
    }

    /**
     * امنیتی: فقط فیلدهای غیرحساس پخش می‌شوند
     * (پیش‌تر کل مدل Order با نام/موبایل/آدرس مشتری پخش عمومی می‌شد)
     */
    public function broadcastWith(): array
    {
        return [
            'id'     => $this->order->id ?? null,
            'status' => $this->order->status ?? null,
            'price'  => $this->order->price ?? null,
        ];
    }
}

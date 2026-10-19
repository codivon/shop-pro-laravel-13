<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendMessage implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public function __construct(Message $message)
    {
        $this->message=$message;
    }

    public function broadcastOn()
    {
        // امنیتی: کانال خصوصی — محتوای پیام برای همه عمومی نبود
        return new PrivateChannel('send-message');
    }

    /** امنیتی: فقط فیلدهای غیرحساس پخش می‌شوند */
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id ?? null,
            'title'      => $this->message->title ?? null,
            'created_at' => optional($this->message->created_at)->toDateTimeString(),
        ];
    }
}

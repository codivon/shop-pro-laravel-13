<?php

namespace App\Events;

use App\Models\Message;
use App\Models\MessageUser;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendMessagePopUp implements  ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $messageUser;
    public function __construct(Message $message,MessageUser $messageUser)
    {
        $this->message=$message;
        $this->messageUser=$messageUser;
    }

    public function broadcastOn()
    {
        // امنیتی: کانال خصوصی به‌جای رشته‌ی عمومی — دسترسی کانال در routes/channels.php کنترل می‌شود
        return [new PrivateChannel('inbox-user.' . $this->messageUser->user_id)];
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

    public function broadcastAs()
    {
        return 'send-message-user';
    }
}

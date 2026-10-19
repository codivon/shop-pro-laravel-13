<?php

namespace App\Notifications\Sms;

use App\Channels\SmsChannel;
use App\Models\NewSeller;
use App\Models\Sms;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSellerCodeSent extends Notification
{
    use Queueable;

    protected $verify_code;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(NewSeller $newSeller)
    {

        $newSeller->update(['code_mobile_verification'    => random_int(10000, 99999)]);
        $this->verify_code = $newSeller->code_mobile_verification;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable)
    {
        return [
            'mobile'  => $notifiable->mobile,
            'data'    => [
                'code' => $this->verify_code
            ],
            'type'    => Sms::TYPES['VERIFY_CODE'],
            'seller_id' => null
        ];
    }
}

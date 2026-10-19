<?php

namespace App\Notifications\Sms;

use App\Channels\SmsChannel;
use App\Models\NewSeller;
use App\Models\OneTimeCode;
use App\Models\Sms;
use App\Models\User;
use App\Models\UserMobileVerify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserCodeSent extends Notification
{
    use Queueable;

    protected $verify_code;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(UserMobileVerify $user)
    {
        $user->update(['code'    => random_int(10000, 99999)]);
        $this->verify_code = $user->code;
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
            'user_id' => null
        ];
    }
}

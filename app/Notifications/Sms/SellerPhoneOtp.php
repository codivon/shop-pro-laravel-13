<?php

namespace App\Notifications\Sms;

use App\Channels\SmsChannel;
use App\Models\Sms;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/*
 * فاز ۱۱۰ — پیامک کد تأیید ۶ رقمی ثبت‌نام مرحله‌ای فروشندگان (پنل مدرن).
 * برخلاف NewSellerCodeSent (۵ رقمیِ جریان قدیمی)، کد بیرون ساخته و پاس
 * می‌شود تا کنترل تولید/زمان‌بندی (۲ دقیقه ارسال مجدد) با کنترلر جدید باشد.
 */
class SellerPhoneOtp extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(protected int $code) {}

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
            'mobile' => $notifiable->mobile,
            'data' => [
                'code' => $this->code,
            ],
            'type' => Sms::TYPES['VERIFY_CODE'],
            'seller_id' => null,
        ];
    }
}

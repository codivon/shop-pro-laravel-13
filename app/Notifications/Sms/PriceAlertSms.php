<?php

namespace App\Notifications\Sms;

use App\Channels\SmsChannel;
use App\Models\Product;
use App\Models\Sms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * فاز ۷۸ — پیامک هشدار کاهش قیمت.
 *
 * گیرنده می‌تواند PriceAlert باشد (کاربر یا مهمان)؛ موبایل از خود رکورد
 * هشدار خوانده می‌شود. الگوی پیامک: price_alert_pattern_code با دو متغیر
 * title و price (برای ملی‌پیامک به‌ترتیب ۰ و ۱).
 */
class PriceAlertSms extends Notification implements ShouldQueue
{
    use Queueable;

    public $product;
    public $price;

    public function __construct(Product $product, $price)
    {
        $this->product = $product;
        $this->price   = $price;
    }

    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable)
    {
        return [
            'mobile'  => $notifiable->mobile,
            'data'    => [
                'title' => $this->product->title,
                'price' => number_format((float) $this->price),
            ],
            'type'    => Sms::TYPES['PRICE_ALERT'],
            'user_id' => $notifiable->user_id,
        ];
    }
}

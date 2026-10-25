<?php

namespace App\Notifications\Product;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * فاز ۸۱ — اعلان فروشگاه (مدیران) بعد از پردازش هشدارهای کاهش قیمت یک محصول.
 *
 * ProcessPriceAlerts پس از پیامک‌دادن به مشتریانِ مشترک، مدیران را از طریق
 * کانال database (+ WebPush) باخبر می‌کند تا صاحب فروشگاه هم از افت قیمت
 * و تعداد مشتری‌های خبردارشده مطلع شود.
 */
class PriceAlertsSent extends Notification implements ShouldQueue
{
    use Queueable;

    protected $product;

    protected int $notifiedCount;

    public function __construct($product, int $notifiedCount)
    {
        $this->product = $product;
        $this->notifiedCount = $notifiedCount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', WebPushChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'message'    => 'کاهش قیمت «' . $this->product->title . '»: ' . $this->notifiedCount . ' مشتری مشترک هشدار کاهش قیمت باخبر شدند.',
            'product_id' => $this->product->id,
        ];
    }

    public function databaseType()
    {
        return 'PriceAlertsSent';
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('هشدار کاهش قیمت ارسال شد')
            ->icon('/notification-icon.png')
            ->body('کاهش قیمت «' . $this->product->title . '» به ' . $this->notifiedCount . ' مشتری اطلاع‌رسانی شد.')
            ->action('مشاهده محصول', url('/'));
    }
}

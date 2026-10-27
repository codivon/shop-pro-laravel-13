<?php

namespace App\Notifications\Seller;

use App\Models\Seller;
use App\Models\SellerInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SellerRequestDeposit extends Notification implements ShouldQueue
{
    use Queueable;

    protected $seller;

    protected $sellerInfo;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Seller $seller)
    {
        $this->seller = $seller;
        $this->sellerInfo = SellerInfo::where('seller_id', $seller->id)->first();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        // گارد null — فروشندهٔ بدون ردیف seller_info نباید اعلان را منفجر کند (فاز ۱۰۳)
        $businessName = $this->sellerInfo->business_name ?? '';

        return [
            'message' => 'فروشنده با عنوان فروشگاه '.$businessName.' درخواست واریز وجه کرده است.',
            'seller_id' => $this->seller->id,
        ];
    }

    public function databaseType()
    {
        return 'SellerRequestDeposit';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    protected $guarded = ['id'];

    const TYPES = [
        'SELLER_CREATED' => [
            'key'    => 'seller-created',
            'string' => 'خوش آمدگویی فروشنده',
            'method' => 'sellerCreated'
        ],
        'VERIFY_CODE' => [
            'key'    => 'verify-code',
            'string' => 'کد تایید',
            'method' => 'verifyCode'
        ],
        'USER_CREATED' => [
            'key'    => 'user-created',
            'string' => 'خوش آمدگویی کاربر',
            'method' => 'userCreated'
        ],
        'ORDER_PAID' => [
            'key'    => 'order-paid',
            'string' => 'اطلاع رسانی پرداخت سفارش به مدیر',
            'method' => 'orderPaid'
        ],
        'USER_ORDER_PAID' => [
            'key'    => 'user-order-paid',
            'string' => 'اطلاع رسانی پرداخت سفارش به کاربر',
            'method' => 'userOrderPaid'
        ],
        'SELLER_ORDER_PAID' => [
            'key'    => 'seller-order-paid',
            'string' => 'اطلاع رسانی پرداخت سفارش به فروشنده',
            'method' => 'sellerOrderPaid'
        ],
        'WALLET_AMOUNT_DECREASED' => [
            'key'    => 'wallet-amount-decreased',
            'string' => 'اطلاع رسانی کاهش موجودی کیف پول',
            'method' => 'walletAmountDecreased'
        ],
        'WALLET_AMOUNT_INCREASED' => [
            'key'    => 'wallet-amount-increased',  // اصلاح شد
            'string' => 'اطلاع رسانی افزایش موجودی کیف پول',
            'method' => 'walletAmountIncreased'
        ],


        'ORDER_CANCELLED' => [
            'key'    => 'order-cancelled',
            'string' => 'اطلاع رسانی لغو سفارش به مدیر',
            'method' => 'orderCancelled'
        ],
        'SELLER_ORDER_CANCELLED' => [
            'key'    => 'seller-order-cancelled',
            'string' => 'اطلاع رسانی لغو سفارش به فروشنده',
            'method' => 'sellerOrderCancelled'
        ],
        'USER_ORDER_CANCELLED' => [
            'key'    => 'user-order-cancelled',
            'string' => 'اطلاع رسانی لغو سفارش به کاربر',
            'method' => 'userOrderCancelled'
        ],
        'WALLET_REFUND' => [
            'key'    => 'wallet-refund',
            'string' => 'اطلاع رسانی برگشت وجه به کیف پول',
            'method' => 'walletRefund'
        ],
        'SEND_MESSAGE_USERS' => [
            'key'    => 'send-message-users',
            'string' => 'ارسال پبام به کاربران',
            'method' => 'sendMessageUsers'
        ],
        'PRICE_ALERT' => [
            'key'    => 'price-alert',
            'string' => 'هشدار کاهش قیمت محصول',
            'method' => 'priceAlert'
        ],
        // فاز ۸۰ — پکیج مارکتینگ و پیام‌رسانی (MarketingMessaging)
        'CART_RECOVERY' => [
            'key'    => 'cart-recovery',
            'string' => 'یادآور سبد خرید رهاشده',
            'method' => 'cartRecovery'
        ],
        'MARKETING_CAMPAIGN' => [
            'key'    => 'marketing-campaign',
            'string' => 'کمپین پیامکی مارکتینگ',
            'method' => 'marketingCampaign'
        ],
        // فاز ۹۱ — پکیج «لجستیک و باربری»: اطلاع‌رسانی وضعیت مرسوله به مشتری
        'LOGISTICS_SHIPMENT_SENT' => [
            'key'    => 'shipment-sent',
            'string' => 'اطلاع‌رسانی ارسال مرسوله (پکیج لجستیک)',
            'method' => 'shipmentSent'
        ],
        'LOGISTICS_SHIPMENT_DELIVERED' => [
            'key'    => 'shipment-delivered',
            'string' => 'اطلاع‌رسانی تحویل مرسوله (پکیج لجستیک)',
            'method' => 'shipmentDelivered'
        ],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function type()
    {
        foreach (self::TYPES as $type) {
            if ($this->type == $type['key']) {
                return $type['string'];
            }
        }
        return 'نامشخص';
    }

    // متد کمکی برای دریافت اطلاعات نوع
    public static function getTypeInfo($typeKey)
    {
        foreach (self::TYPES as $type) {
            if ($type['key'] == $typeKey) {
                return $type;
            }
        }
        return null;
    }

    // متد کمکی برای دریافت متد مربوط به نوع
    public static function getMethodByKey($typeKey)
    {
        $typeInfo = self::getTypeInfo($typeKey);
        return $typeInfo ? $typeInfo['method'] : null;
    }
}

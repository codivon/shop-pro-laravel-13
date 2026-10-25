<?php

namespace App\Services\Sms;

use App\Contracts\SmsContract;
use App\Contracts\SmsNotificationContract;
use Illuminate\Support\Facades\Config;
use Kavenegar;

class KavenegarSms extends SmsService implements SmsContract, SmsNotificationContract
{
    public function send()
    {
        $method = $this->method();
        $data   = $this->$method();

        $input_data   = $data['input_data'];
        $mobile       = $this->mobile();
        $template     = $data['template'];

        Config::set('kavenegar.apikey', option('KAVENEGAR_PANEL_APIKEY'));

        try {
            $token   = $input_data['token'] ?? null;
            $token2  = $input_data['token2'] ?? null;
            $token3  = $input_data['token3'] ?? null;
            $token10 = $input_data['token10'] ?? null;
            $token20 = $input_data['token20'] ?? null;

            $result = Kavenegar::VerifyLookup($mobile, $token, $token2, $token3, $template, $type = null, $token10, $token20);

            $response = json_encode($result);
        } catch (\Kavenegar\Exceptions\ApiException $e) {
            // در صورتی که خروجی وب سرویس 200 نباشد این خطا رخ می دهد
            $response = $e->errorMessage();
        } catch (\Kavenegar\Exceptions\HttpException $e) {
            // در زمانی که مشکلی در برقرای ارتباط با وب سرویس وجود داشته باشد این خطا رخ می دهد
            $response = $e->errorMessage();
        }

        return $response;
    }

    public function verifyCode()
    {
        return [
            'template'     => option('user_verify_pattern_code_kavenegar'),
            'input_data'   => [
                'token' => $this->data['code']
            ],
        ];
    }

    public function sellerCreated()
    {
        return [
            'template'     => option('seller_register_pattern_code_kavenegar'),
            'input_data'   => [
                'token20' => $this->data['fullname'],
                'token'   => $this->data['username'],
            ],
        ];
    }

    public function userCreated()
    {
        return [
            'template'     => option('user_register_pattern_code_kavenegar'),
            'input_data'   => [
                'token20' => $this->data['fullname'],
                'token'   => $this->data['username'],
            ],
        ];
    }

    public function orderPaid()
    {
        return [
            'template'     => option('order_paid_pattern_code_kavenegar'),
            'input_data'   => [
                'token' => $this->data['order_id']
            ],
        ];
    }

    public function sellerOrderPaid()
    {
        return [
            'template'     => option('seller_order_paid_pattern_code_kavenegar'),
            'input_data'   => [
                'token' => $this->data['order_id']
            ],
        ];
    }

    public function userOrderPaid()
    {
        return [
            'template'     => option('user_order_paid_pattern_code_kavenegar'),
            'input_data'   => [
                'token' => $this->data['order_id']
            ],
        ];
    }

    // ========== متدهای جدید برای لغو سفارش ==========

    /**
     * ارسال پیامک لغو سفارش به ادمین
     */
    public function orderCancelled()
    {
        return [
            'template'     => option('order_cancelled_pattern_code_kavenegar'),
            'input_data'   => [
                'token' => $this->data['order_id']
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به فروشنده
     */
    public function sellerOrderCancelled()
    {
        return [
            'template'     => option('seller_order_cancelled_pattern_code_kavenegar'),
            'input_data'   => [
                'token' => $this->data['order_id']
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به کاربر
     */
    public function userOrderCancelled()
    {
        $input_data = [
            'token' => $this->data['order_id']
        ];

        // اضافه کردن دلیل لغو در صورت وجود
        if (isset($this->data['reason']) && !empty($this->data['reason'])) {
            $input_data['token2'] = $this->data['reason'];
        }

        // اضافه کردن مبلغ برگشتی در صورت وجود
        if (isset($this->data['refund_amount']) && $this->data['refund_amount'] > 0) {
            $input_data['token3'] = (string) $this->data['refund_amount'];
        }

        return [
            'template'     => option('user_order_cancelled_pattern_code_kavenegar'),
            'input_data'   => $input_data,
        ];
    }

    /**
     * ارسال پیامک برگشت وجه به کیف پول
     */
    public function walletRefund()
    {
        $input_data = [
            'token' => $this->data['amount']
        ];

        // اضافه کردن شماره سفارش در صورت وجود
        if (isset($this->data['order_id']) && !empty($this->data['order_id'])) {
            $input_data['token2'] = $this->data['order_id'];
        }

        return [
            'template'     => option('wallet_refund_pattern_code_kavenegar'),
            'input_data'   => $input_data,
        ];
    }
    // ========== پایان متدهای جدید ==========

    public function walletAmountDecreased()
    {
        return [
            'template'     => option('wallet_decrease_pattern_code_kavenegar'),
            'input_data'   => [
                'token'    => $this->data['amount']
            ],
        ];
    }

    public function walletAmountIncreased()
    {
        return [
            'template'     => option('wallet_increase_pattern_code_kavenegar'),
            'input_data'   => [
                'token'    => $this->data['amount']
            ],
        ];
    }

    public function sendMessageUsers()
    {
        return [
            'template' => option('user_message_pattern_code'),
            'input_data'   =>  $this->data,
        ];
    }

    /** فاز ۷۸ — هشدار کاهش قیمت (الگو: price_alert_pattern_code با token عنوان و token2 قیمت) */
    public function priceAlert()
    {
        return [
            'template'     => option('price_alert_pattern_code'),
            'input_data'   => [
                'token'  => $this->data['title'],
                'token2' => $this->data['price'],
            ],
        ];
    }

    /** فاز ۸۰ — یادآور سبد خرید رهاشده (پکیج MarketingMessaging؛ الگو: cart_recovery_pattern_code با متغیرهای name، items و total) */
    public function cartRecovery()
    {
        return [
            'template'     => option('cart_recovery_pattern_code'),
            'input_data'   => [
                'token'  => $this->data['name'],
                'token2' => $this->data['items'],
                'token3' => $this->data['total'],
            ],
        ];
    }

    /** فاز ۸۰ — کمپین پیامکی مارکتینگ (پکیج MarketingMessaging؛ الگو: marketing_campaign_pattern_code با متغیرهای name و store) */
    public function marketingCampaign()
    {
        return [
            'template'     => option('marketing_campaign_pattern_code'),
            'input_data'   => [
                'token'  => $this->data['name'],
                'token2' => $this->data['store'],
            ],
        ];
    }
}

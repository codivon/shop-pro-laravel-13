<?php

namespace App\Services\Sms;

use App\Contracts\SmsContract;
use App\Contracts\SmsNotificationContract;
use Exception;
use Melipayamak\MelipayamakApi;

class MelipayamakSms extends SmsService implements SmsContract, SmsNotificationContract
{
    public function send()
    {
        $method = $this->method();
        $data   = $this->$method();

        $input_data   = $data['input_data'];
        $mobile       = $this->mobile();
        $bodyId       = $data['bodyId'];

        try {
            $username  = option('MELIPAYAMAK_PANEL_USERNAME');
            $password  = option('MELIPAYAMAK_PANEL_PASSWORD');
            $api       = new MelipayamakApi($username, $password);
            $sms       = $api->sms('soap');
            $to        = $mobile;
            $text      = implode(';', $input_data);
            $response  = $sms->sendByBaseNumber($text, $to, $bodyId);

            $message = json_encode($response);
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        return $message;
    }

    public function verifyCode()
    {
        return [
            'bodyId'       => option('user_verify_pattern_code_melipayamak'),
            'input_data'   => [
                '0' => $this->data['code']
            ],
        ];
    }

    public function sellerCreated()
    {
        return [
            'bodyId'       => option('seller_register_pattern_code_melipayamak'),
            'input_data'   => [
                '0'   => $this->data['fullname'],
                '1'   => $this->data['username'],
            ],
        ];
    }

    public function userCreated()
    {
        return [
            'bodyId'       => option('user_register_pattern_code_melipayamak'),
            'input_data'   => [
                '0'   => $this->data['fullname'],
                '1'   => $this->data['username'],
            ],
        ];
    }

    public function orderPaid()
    {
        return [
            'bodyId'       => option('order_paid_pattern_code_melipayamak'),
            'input_data'   => [
                '0' => $this->data['order_id']
            ],
        ];
    }

    public function sellerOrderPaid()
    {
        return [
            'bodyId'       => option('seller_order_paid_pattern_code_melipayamak'),
            'input_data'   => [
                '0' => $this->data['order_id']
            ],
        ];
    }

    public function userOrderPaid()
    {
        return [
            'bodyId'       => option('user_order_paid_pattern_code_melipayamak'),
            'input_data'   => [
                '0' => $this->data['order_id']
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
            'bodyId'       => option('order_cancelled_pattern_code_melipayamak'),
            'input_data'   => [
                '0' => $this->data['order_id']
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به فروشنده
     */
    public function sellerOrderCancelled()
    {
        return [
            'bodyId'       => option('seller_order_cancelled_pattern_code_melipayamak'),
            'input_data'   => [
                '0' => $this->data['order_id']
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به کاربر
     */
    public function userOrderCancelled()
    {
        $input_data = [
            '0' => $this->data['order_id']
        ];

        // اضافه کردن دلیل لغو در صورت وجود (index 1)
        if (isset($this->data['reason']) && !empty($this->data['reason'])) {
            $input_data['1'] = $this->data['reason'];
        }

        // اضافه کردن مبلغ برگشتی در صورت وجود (index 2)
        if (isset($this->data['refund_amount']) && $this->data['refund_amount'] > 0) {
            $input_data['2'] = (string) $this->data['refund_amount'];
        }

        return [
            'bodyId'       => option('user_order_cancelled_pattern_code_melipayamak'),
            'input_data'   => $input_data,
        ];
    }

    /**
     * ارسال پیامک برگشت وجه به کیف پول
     */
    public function walletRefund()
    {
        $input_data = [
            '0' => $this->data['amount']
        ];

        // اضافه کردن شماره سفارش در صورت وجود (index 1)
        if (isset($this->data['order_id']) && !empty($this->data['order_id'])) {
            $input_data['1'] = $this->data['order_id'];
        }

        return [
            'bodyId'       => option('wallet_refund_pattern_code_melipayamak'),
            'input_data'   => $input_data,
        ];
    }
    // ========== پایان متدهای جدید ==========

    public function walletAmountDecreased()
    {
        return [
            'bodyId'       => option('wallet_decrease_pattern_code_melipayamak'),
            'input_data'   => [
                '0' => $this->data['amount']
            ],
        ];
    }

    public function walletAmountIncreased()
    {
        return [
            'bodyId'       => option('wallet_increase_pattern_code_melipayamak'),
            'input_data'   => [
                '0' => $this->data['amount']
            ],
        ];
    }

    public function sendMessageUsers()
    {
        return [
            'bodyId' => option('user_message_pattern_code'),
            'input_data'   =>  $this->data,
        ];
    }

    /** فاز ۷۸ — هشدار کاهش قیمت (الگو: price_alert_pattern_code — ۰=عنوان، ۱=قیمت) */
    public function priceAlert()
    {
        return [
            'bodyId'       => option('price_alert_pattern_code'),
            'input_data'   => [
                '0' => $this->data['title'],
                '1' => $this->data['price'],
            ],
        ];
    }

    /** فاز ۸۰ — یادآور سبد خرید رهاشده (پکیج MarketingMessaging؛ الگو: cart_recovery_pattern_code با متغیرهای name، items و total) */
    public function cartRecovery()
    {
        return [
            'bodyId'       => option('cart_recovery_pattern_code'),
            'input_data'   => [
                '0' => $this->data['name'],
                '1' => $this->data['items'],
                '2' => $this->data['total'],
            ],
        ];
    }

    /** فاز ۸۰ — کمپین پیامکی مارکتینگ (پکیج MarketingMessaging؛ الگو: marketing_campaign_pattern_code با متغیرهای name و store) */
    public function marketingCampaign()
    {
        return [
            'bodyId'       => option('marketing_campaign_pattern_code'),
            'input_data'   => [
                '0' => $this->data['name'],
                '1' => $this->data['store'],
            ],
        ];
    }
}

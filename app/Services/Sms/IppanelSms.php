<?php

namespace App\Services\Sms;

use App\Contracts\SmsContract;
use App\Contracts\SmsNotificationContract;

class IppanelSms extends SmsService implements SmsContract, SmsNotificationContract {
    public function send()
    {
        $method = $this->method();
        $data   = $this->$method();

        $username      = option('SMS_PANEL_USERNAME');
        $password      = option('SMS_PANEL_PASSWORD');
        $from          = option('SMS_PANEL_FROM');
        $to            = array($this->mobile());
        $pattern_code  = $data['pattern_code'];
        $input_data    = $data['input_data'];

        $url      = "https://ippanel.com/patterns/pattern?username="
            . $username . "&password=" . urlencode($password)
            . "&from=$from&to=" . json_encode($to)
            . "&input_data=" . urlencode(json_encode($input_data))
            . "&pattern_code=$pattern_code";
        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_POSTFIELDS, $input_data);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handler);
        return $response;
    }

    public function verifyCode()
    {
        return [
            'pattern_code' => option('user_verify_pattern_code'),
            'input_data'   => [
                'code' => $this->data['code']
            ],
        ];
    }

    public function sellerCreated()
    {
        return [
            'pattern_code' => option('seller_register_pattern_code'),
            'input_data'   => [
                'fullname' => $this->data['fullname']
            ],
        ];
    }

    public function userCreated()
    {
        return [
            'pattern_code' => option('user_register_pattern_code'),
            'input_data'   => [
                'fullname' => $this->data['fullname']
            ],
        ];
    }

    public function orderPaid()
    {
        return [
            'pattern_code' => option('order_paid_pattern_code'),
            'input_data'   => [
                'order_id' => $this->data['order_id']
            ],
        ];
    }

    public function sellerOrderPaid()
    {
        return [
            'pattern_code' => option('seller_order_paid_pattern_code'),
            'input_data'   => [
                'order_id' => $this->data['order_id']
            ],
        ];
    }

    public function userOrderPaid()
    {
        return [
            'pattern_code' => option('user_order_paid_pattern_code'),
            'input_data'   => [
                'order_id' => $this->data['order_id']
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
            'pattern_code' => option('order_cancelled_pattern_code'),
            'input_data'   => [
                'order_id' => $this->data['order_id']
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به فروشنده
     */
    public function sellerOrderCancelled()
    {
        return [
            'pattern_code' => option('seller_order_cancelled_pattern_code'),
            'input_data'   => [
                'order_id' => $this->data['order_id']
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به کاربر
     */
    public function userOrderCancelled()
    {
        return [
            'pattern_code' => option('user_order_cancelled_pattern_code'),
            'input_data'   => [
                'order_id' => $this->data['order_id'],
                'reason' => $this->data['reason'] ?? '',
                'refund_amount' => $this->data['refund_amount'] ?? 0
            ],
        ];
    }

    /**
     * ارسال پیامک برگشت وجه به کیف پول
     */
    public function walletRefund()
    {
        return [
            'pattern_code' => option('wallet_refund_pattern_code'),
            'input_data'   => [
                'amount' => $this->data['amount'],
                'order_id' => $this->data['order_id'] ?? ''
            ],
        ];
    }
    // ========== پایان متدهای جدید ==========

    public function walletAmountDecreased()
    {
        return [
            'pattern_code' => option('wallet_decrease_pattern_code_ippanel'),
            'input_data'   => [
                'amount' => $this->data['amount']
            ],
        ];
    }

    public function walletAmountIncreased()
    {
        return [
            'pattern_code' => option('wallet_increase_pattern_code_ippanel'),
            'input_data'   => [
                'amount' => $this->data['amount']
            ],
        ];
    }

    public function sendMessageUsers()
    {
        return [
            'pattern_code' => option('user_message_pattern_code'),
            'input_data'   =>  $this->data,
        ];
    }

    /** فاز ۷۸ — هشدار کاهش قیمت (الگو: price_alert_pattern_code با متغیرهای title و price) */
    public function priceAlert()
    {
        return [
            'pattern_code' => option('price_alert_pattern_code'),
            'input_data'   => [
                'title' => $this->data['title'],
                'price' => $this->data['price'],
            ],
        ];
    }
}

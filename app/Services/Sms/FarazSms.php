<?php

namespace App\Services\Sms;

use App\Contracts\SmsContract;
use App\Contracts\SmsNotificationContract;
use Exception;
use GuzzleHttp\Client;

class FarazSms extends SmsService implements SmsContract, SmsNotificationContract
{
    public function send()
    {
        $method = $this->method();
        $data   = $this->$method();

        $pattern_code = $data['pattern_code'];
        $attributes   = $data['attributes'];
        $recipient    = $this->mobile();
        $line_number  = option('FARAZSMS_PANEL_FROM');
        $api_key      = option('FARAZSMS_PANEL_API_KEY');

        try {
            $client = new Client();
            $headers = [
                'Accept' => 'application/json',
                'Api-Key' => $api_key,
                'Content-Type' => 'application/json'
            ];

            $body = [
                'code' => $pattern_code,
                'attributes' => $attributes,
                'recipient' => $recipient,
                'line_number' => $line_number,
                'number_format' => 'english'
            ];

            // اضافه کردن زمان ارسال در صورت وجود
            if (isset($this->data['schedule']) && !empty($this->data['schedule'])) {
                $body['schedule'] = $this->data['schedule'];
            }

            $requestBody = json_encode($body);

            $request = new \GuzzleHttp\Psr7\Request(
                'POST',
                'https://api.iranpayamak.com/ws/v1/sms/pattern',
                $headers,
                $requestBody
            );

            $res = $client->sendAsync($request)->wait();
            $response = $res->getBody()->getContents();

        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return $response;
    }

    /**
     * ارسال کد تایید
     */
    public function verifyCode()
    {
        return [
            'pattern_code' => option('user_verify_pattern_code_farazsms'),
            'attributes'   => [
                'code' => $this->data['code']
            ],
        ];
    }

    /**
     * پیامک خوش آمدگویی فروشنده
     */
    public function sellerCreated()
    {
        return [
            'pattern_code' => option('seller_register_pattern_code_farazsms'),
            'attributes'   => [
                'fullname' => $this->data['fullname']
            ],
        ];
    }

    /**
     * پیامک خوش آمدگویی کاربر
     */
    public function userCreated()
    {
        return [
            'pattern_code' => option('user_register_pattern_code_farazsms'),
            'attributes'   => [
                'fullname' => $this->data['fullname']
            ],
        ];
    }

    /**
     * پیامک پرداخت سفارش به ادمین
     */
    public function orderPaid()
    {
        return [
            'pattern_code' => option('order_paid_pattern_code_farazsms'),
            'attributes'   => [
                'order_id' => $this->data['order_id']
            ],
        ];
    }

    /**
     * پیامک پرداخت سفارش به فروشنده
     */
    public function sellerOrderPaid()
    {
        return [
            'pattern_code' => option('seller_order_paid_pattern_code_farazsms'),
            'attributes'   => [
                'order_id' => $this->data['order_id']
            ],
        ];
    }

    /**
     * پیامک پرداخت سفارش به کاربر
     */
    public function userOrderPaid()
    {
        return [
            'pattern_code' => option('user_order_paid_pattern_code_farazsms'),
            'attributes'   => [
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
            'pattern_code' => option('order_cancelled_pattern_code_farazsms'),
            'attributes'   => [
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
            'pattern_code' => option('seller_order_cancelled_pattern_code_farazsms'),
            'attributes'   => [
                'order_id' => $this->data['order_id']
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به کاربر
     */
    public function userOrderCancelled()
    {
        $attributes = [
            'order_id' => $this->data['order_id'],
        ];

        // اضافه کردن دلیل لغو در صورت وجود
        if (isset($this->data['reason']) && !empty($this->data['reason'])) {
            $attributes['reason'] = $this->data['reason'];
        }

        // اضافه کردن مبلغ برگشتی در صورت وجود
        if (isset($this->data['refund_amount']) && $this->data['refund_amount'] > 0) {
            $attributes['refund_amount'] = (string) $this->data['refund_amount'];
        }

        return [
            'pattern_code' => option('user_order_cancelled_pattern_code_farazsms'),
            'attributes'   => $attributes,
        ];
    }

    /**
     * ارسال پیامک برگشت وجه به کیف پول
     */
    public function walletRefund()
    {
        $attributes = [
            'amount' => $this->data['amount']
        ];

        // اضافه کردن شماره سفارش در صورت وجود
        if (isset($this->data['order_id']) && !empty($this->data['order_id'])) {
            $attributes['order_id'] = $this->data['order_id'];
        }

        return [
            'pattern_code' => option('wallet_refund_pattern_code_farazsms'),
            'attributes'   => $attributes,
        ];
    }
    // ========== پایان متدهای جدید ==========

    /**
     * پیامک کاهش موجودی کیف پول
     */
    public function walletAmountDecreased()
    {
        return [
            'pattern_code' => option('wallet_decrease_pattern_code_farazsms'),
            'attributes'   => [
                'amount' => $this->data['amount']
            ],
        ];
    }

    /**
     * پیامک افزایش موجودی کیف پول
     */
    public function walletAmountIncreased()
    {
        return [
            'pattern_code' => option('wallet_increase_pattern_code_farazsms'),
            'attributes'   => [
                'amount' => $this->data['amount']
            ],
        ];
    }

    public function sendMessageUsers()
    {
        return [
            'pattern_code' => option('user_message_pattern_code'),
            'attributes'   =>  $this->data,
        ];
    }

    /** فاز ۷۸ — هشدار کاهش قیمت (الگو: price_alert_pattern_code با متغیرهای title و price) */
    public function priceAlert()
    {
        return [
            'pattern_code' => option('price_alert_pattern_code'),
            'attributes'   => [
                'title' => $this->data['title'],
                'price' => $this->data['price'],
            ],
        ];
    }
}

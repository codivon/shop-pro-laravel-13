<?php

namespace App\Services\Sms;

use App\Contracts\SmsContract;
use App\Contracts\SmsNotificationContract;

class IdehPardazanSms extends SmsService implements SmsContract, SmsNotificationContract
{
    public function send()
    {
        $method = $this->method();
        $data   = $this->$method();

        $body = [
            'mobile'     => $this->mobile,
            'UserApiKey' => option('IDEHPARDAZAN_PANEL_APIKEY'),
            'SecretKey'  => option('IDEHPARDAZAN_PANEL_SECRET_KEY')
        ];

        $body = array_merge($data, $body);
        $body = json_encode($body, true);

        $url     = "https://RestfulSms.com/api/UltraFastSend/direct";
        $headers = array(
            'Content-Type: application/json',
        );
        $handler = curl_init($url);

        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handler, CURLOPT_POSTFIELDS, $body);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handler);

        return $response;
    }

    public function verifyCode()
    {
        return [
            'TemplateId' => option('user_verify_pattern_code_idehpardazan'),
            'ParameterArray' => [
                [
                    "Parameter"      => "code",
                    "ParameterValue" => $this->data['code']
                ]
            ],
        ];
    }

    public function sellerCreated()
    {
        return [
            'pattern_code' => option('seller_register_pattern_code_idehpardazan'),
            'input_data'   => [
                'fullname' => $this->data['fullname']
            ],
        ];
    }

    public function userCreated()
    {
        return [
            'TemplateId' => option('user_register_pattern_code_idehpardazan'),
            'ParameterArray'   => [
                [
                    "Parameter"      => "fullname",
                    "ParameterValue" => $this->data['fullname']
                ]
            ],
        ];
    }

    public function orderPaid()
    {
        return [
            'TemplateId' => option('order_paid_pattern_code_idehpardazan'),
            'ParameterArray'   => [
                [
                    "Parameter"      => "order_id",
                    "ParameterValue" => $this->data['order_id']
                ]
            ],
        ];
    }

    public function sellerOrderPaid()
    {
        return [
            'bodyId'       => option('seller_order_paid_pattern_code_idehpardazan'),
            'input_data'   => [
                '0' => $this->data['order_id']
            ],
        ];
    }

    public function userOrderPaid()
    {
        return [
            'TemplateId' => option('user_order_paid_pattern_code_idehpardazan'),
            'ParameterArray'   => [
                [
                    "Parameter"      => "order_id",
                    "ParameterValue" => $this->data['order_id']
                ]
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
            'TemplateId' => option('order_cancelled_pattern_code_idehpardazan'),
            'ParameterArray' => [
                [
                    "Parameter"      => "order_id",
                    "ParameterValue" => $this->data['order_id']
                ]
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به فروشنده
     */
    public function sellerOrderCancelled()
    {
        return [
            'TemplateId' => option('seller_order_cancelled_pattern_code_idehpardazan'),
            'ParameterArray' => [
                [
                    "Parameter"      => "order_id",
                    "ParameterValue" => $this->data['order_id']
                ]
            ],
        ];
    }

    /**
     * ارسال پیامک لغو سفارش به کاربر
     */
    public function userOrderCancelled()
    {
        $parameters = [
            [
                "Parameter"      => "order_id",
                "ParameterValue" => $this->data['order_id']
            ]
        ];

        // اضافه کردن دلیل لغو در صورت وجود
        if (isset($this->data['reason']) && !empty($this->data['reason'])) {
            $parameters[] = [
                "Parameter"      => "reason",
                "ParameterValue" => $this->data['reason']
            ];
        }

        // اضافه کردن مبلغ برگشتی در صورت وجود
        if (isset($this->data['refund_amount']) && $this->data['refund_amount'] > 0) {
            $parameters[] = [
                "Parameter"      => "refund_amount",
                "ParameterValue" => (string) $this->data['refund_amount']
            ];
        }

        return [
            'TemplateId' => option('user_order_cancelled_pattern_code_idehpardazan'),
            'ParameterArray' => $parameters,
        ];
    }

    /**
     * ارسال پیامک برگشت وجه به کیف پول
     */
    public function walletRefund()
    {
        $parameters = [
            [
                "Parameter"      => "amount",
                "ParameterValue" => $this->data['amount']
            ]
        ];

        // اضافه کردن شماره سفارش در صورت وجود
        if (isset($this->data['order_id']) && !empty($this->data['order_id'])) {
            $parameters[] = [
                "Parameter"      => "order_id",
                "ParameterValue" => $this->data['order_id']
            ];
        }

        return [
            'TemplateId' => option('wallet_refund_pattern_code_idehpardazan'),
            'ParameterArray' => $parameters,
        ];
    }
    // ========== پایان متدهای جدید ==========

    public function walletAmountDecreased()
    {
        return [
            'TemplateId' => option('wallet_decrease_pattern_code_idehpardazan'),
            'ParameterArray'   => [
                [
                    "Parameter"      => "amount",
                    "ParameterValue" => $this->data['amount']
                ]
            ],
        ];
    }

    public function walletAmountIncreased()
    {
        return [
            'TemplateId' => option('wallet_increase_pattern_code_idehpardazan'),
            'ParameterArray'   => [
                [
                    "Parameter"      => "amount",
                    "ParameterValue" => $this->data['amount']
                ]
            ],
        ];
    }

    public function sendMessageUsers()
    {
        return [
            'TemplateId' => option('user_message_pattern_code'),
            'ParameterArray'   =>  $this->data,
        ];
    }

    /** فاز ۷۸ — هشدار کاهش قیمت (الگو: price_alert_pattern_code با پارامترهای title و price) */
    public function priceAlert()
    {
        return [
            'TemplateId'     => option('price_alert_pattern_code'),
            'ParameterArray' => [
                [
                    'Parameter'      => 'title',
                    'ParameterValue' => $this->data['title'],
                ],
                [
                    'Parameter'      => 'price',
                    'ParameterValue' => $this->data['price'],
                ],
            ],
        ];
    }
}

<?php

namespace App\Services\Checkout;

use Exception;

/**
 * خطای کنترل‌شدهٔ فلوی چک‌اوت — پیام فارسی برای کلاینت + کد وضعیت HTTP.
 * هرگز stack trace از این مسیر به بیرون نشت نمی‌کند.
 */
class CheckoutException extends Exception
{
    public function __construct(string $message, int $statusCode = 422)
    {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->getCode() ?: 422;
    }
}

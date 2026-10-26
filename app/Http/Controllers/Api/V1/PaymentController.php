<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * فاز ۹۵ — تأیید پرداخت درگاه بدون نشست.
 *
 * GET|POST /api/v1/payments/verify/{token}
 *  • token = توکن ذخیره‌شده در ردیف تراکنش (ساخته‌شده در POST checkout)
 *  • این روت عیناً callbackUrl درگاه است؛ پارامترهای کوئری/بادی درگاه باید
 *    دست‌نخورده به متد verify شِتابیت برسند.
 *  • بدون auth — مجاز در EnsureApiHeaderIsSet (درگاه‌ها هدر x-api-key نمی‌فرستند)
 *    و محدود نرخ ۲۰ در دقیقه.
 */
class PaymentController extends Controller
{
    public function __construct(protected CheckoutService $checkout) {}

    public function verify(Request $request, string $token): JsonResponse
    {
        try {
            $result = $this->checkout->verifyByToken($token);
        } catch (CheckoutException $e) {
            return $this->respondError($e->getMessage(), $e->statusCode());
        } catch (\Throwable $e) {
            report($e);

            return $this->respondError('خطا در تأیید پرداخت. لطفاً با پشتیبانی تماس بگیرید.', 500);
        }

        $order = $result['order'];

        return $this->apiResponse([
            'success' => true,
            'message' => 'پرداخت با موفقیت تأیید شد.',
            'result'  => [
                'order_id'     => $order->id,
                'status'       => $order->status,
                'price'        => $order->price,
                'already_paid' => (bool) ($result['already_paid'] ?? false),
            ],
        ]);
    }
}

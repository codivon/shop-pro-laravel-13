<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\StoreCheckoutRequest;
use App\Http\Resources\Api\V1\Checkout\CheckoutOrderResource;
use App\Http\Resources\Api\V1\Checkout\CheckoutResource;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * فاز ۹۵ — کنترلر تسویه‌حساب API v1.
 *
 *  GET  /api/v1/checkout  → خلاصهٔ چک‌اوت (گروه‌ها، روش‌های ارسال، تاریخ‌های
 *                           تحویل، آدرس‌ها، روش‌های پرداخت، جمع‌ها، تخفیف)
 *  POST /api/v1/checkout  → ثبت سفارش و حل پرداخت (کیف پول/درگاه شتابی/ماژولی)
 */
class CheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkout) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $summary = $this->checkout->summary(
                $request->user(),
                $request->filled('city_id') ? (int) $request->input('city_id') : null
            );
        } catch (CheckoutException $e) {
            return $this->respondError($e->getMessage(), $e->statusCode());
        } catch (\Throwable $e) {
            report($e);

            return $this->respondError('خطا در آماده‌سازی صفحهٔ تسویه‌حساب. لطفاً بعداً تلاش کنید.', 500);
        }

        return $this->respondWithResource(new CheckoutResource($summary));
    }

    public function store(StoreCheckoutRequest $request): JsonResponse
    {
        try {
            $result = $this->checkout->placeOrder(
                $request->user(),
                $request->validated(),
                $request
            );
        } catch (CheckoutException $e) {
            return $this->respondError($e->getMessage(), $e->statusCode());
        } catch (\Throwable $e) {
            report($e);

            return $this->respondError('خطا در ثبت سفارش. لطفاً بعداً تلاش کنید.', 500);
        }

        $message = ($result['paid'] ?? false)
            ? 'پرداخت با موفقیت انجام شد و سفارش شما ثبت شد.'
            : 'سفارش شما ثبت شد. برای تکمیل خرید به درگاه پرداخت بروید.';

        return $this->respondWithResource(
            new CheckoutOrderResource($result),
            $message,
            201
        );
    }
}

<?php

namespace App\Services\AdminPanel;

use App\Http\Controllers\Back\WalletController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Seller;
use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * منطق تغییر وضعیت/حذف سفارشات پنل جدید (Livewire)
 *
 * پورت کامل متدهای OrderController قدیمی — هیچ رفتاری تغییر نکرده است:
 * shipping_status / shippingsStatus / itemShippingStatus + refundOrderItems /
 * itemTrackingStatus / destroy / multipleDestroy
 */
class OrderPersistence
{
    /* ==================================================================
     |  حذف سفارش
     ================================================================== */

    public function destroy(Order $order): void
    {
        $order->items()->delete();
        $order->transactions()->delete();
        $order->delete();
    }

    public function multipleDestroy(array $ids): void
    {
        foreach (Order::whereIn('id', $ids)->get() as $order) {
            $this->destroy($order);
        }
    }

    /* ==================================================================
     |  وضعیت ارسال سطح سفارش
     ================================================================== */

    /**
     * تغییر وضعیت ارسال یک سفارش (+ رزروهای فرزند) با امکان بازگشت وجه به کیف پول
     */
    public function changeShippingStatus(Order $order, string $status, bool $backMoney = false): void
    {
        $adminName = $this->adminName();
        $oldStatus = $order->shipping_status;

        $order->update([
            'shipping_status' => $status,
        ]);

        $order->reservedOrders()->update([
            'shipping_status' => $status,
        ]);

        activity()
            ->performedOn($order)
            ->causedBy(Auth::guard('adminPanel')->user())
            ->event('updated')
            ->withProperties([
                'action' => 'update_shipping_status',
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'back_money' => $backMoney ? 'yes' : 'no',
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} وضعیت ارسال سفارش شماره {$order->id} را از {$oldStatus} به {$status} تغییر داد");

        if ($backMoney) {
            $user = User::find($order->user_id);
            $walletHistory = WalletHistory::where([
                'wallet_id' => $user->wallet->id,
                'order_id' => $order->id,
                'orderCanceled' => 1,
            ])->first();

            // برداشت سهم فروشندگان از کیف پولشان
            foreach ($order->items as $item) {
                if ($item->seller_id) {
                    $seller = Seller::find($item->seller_id);
                    $walletHistorySellerAmount = WalletHistory::where([
                        'wallet_id' => $seller->wallet->id,
                        'order_id' => $order->id,
                    ])->first();

                    if ($walletHistorySellerAmount) {
                        $walletHistorySeller = WalletHistory::where([
                            'wallet_id' => $seller->wallet->id,
                            'order_id' => $order->id,
                            'orderCanceled' => 1,
                        ])->first();

                        if (! $walletHistorySeller) {
                            $walletRequest = $this->walletRequest([
                                'backMoney' => 'on',
                                'amount' => $walletHistorySellerAmount->amount,
                                'type' => 'withdraw',
                                'order_id' => $order->id,
                                'description' => 'لغو سفارش با شماره: ' . $order->id,
                            ]);

                            (new WalletController)->store($seller->wallet, $walletRequest);
                            sleep(1);
                        }
                    }
                }
            }

            // واریز مبلغ سفارش به کیف پول کاربر
            if (! $walletHistory) {
                $walletRequest = $this->walletRequest([
                    'backMoney' => 'on',
                    'amount' => $order->price,
                    'type' => 'deposit',
                    'order_id' => $order->id,
                    'description' => 'لغو سفارش با شماره: ' . $order->id,
                ]);

                (new WalletController)->store($user->wallet, $walletRequest);
            }
        }
    }

    /**
     * تغییر وضعیت ارسال گروهی — پورت shippingsStatus
     */
    public function bulkShippingStatus(array $ids, string $status, bool $backMoney): void
    {
        $adminName = $this->adminName();
        $orders = Order::whereIn('id', $ids)->get();
        $oldStatuses = [];

        foreach ($orders as $order) {
            $oldStatuses[$order->id] = $order->shipping_status;

            if (! $order->isPaid()) {
                throw ValidationException::withMessages([
                    'id' => 'سفارش شماره ' . $order->id . ' پرداخت نشده است',
                ]);
            }

            if ($order->reserved()) {
                throw ValidationException::withMessages([
                    'id' => 'سفارش شماره ' . $order->id . ' رزرو شده است',
                ]);
            }

            if ($status == 'canceled' && $backMoney) {
                $user = User::find($order->user_id);
                $walletHistory = WalletHistory::where([
                    'wallet_id' => $user->wallet->id,
                    'order_id' => $order->id,
                    'orderCanceled' => 1,
                ])->first();

                if (! $walletHistory) {
                    $walletRequest = $this->walletRequest([
                        'amount' => $order->price,
                        'type' => 'deposit',
                        'order_id' => $order->id,
                        'description' => 'لغو سفارش با شماره: ' . $order->id,
                    ]);

                    (new WalletController)->store($user->wallet, $walletRequest);
                }
            }
        }

        foreach ($orders as $order) {
            $order->update([
                'shipping_status' => $status,
            ]);

            $order->reservedOrders()->update([
                'shipping_status' => $status,
            ]);
        }

        $orderIds = implode('، ', $ids);
        $backMoneyText = $backMoney ? 'بازگشت وجه انجام شد' : 'بدون بازگشت وجه';

        activity()
            ->causedBy(Auth::guard('adminPanel')->user())
            ->event('updated')
            ->withProperties([
                'action' => 'bulk_update_shipping_status',
                'order_ids' => $ids,
                'old_statuses' => $oldStatuses,
                'new_status' => $status,
                'back_money' => $backMoney ? 'yes' : 'no',
                'back_money_text' => $backMoneyText,
                'orders_count' => $orders->count(),
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} وضعیت ارسال سفارش‌های {$orderIds} را به صورت گروهی به {$status} تغییر داد ({$backMoneyText})");
    }

    /* ==================================================================
     |  وضعیت ارسال مرسوله (آیتم)
     ================================================================== */

    /**
     * تغییر وضعیت ارسال مرسوله — همه آیتم‌های همان فروشنده آپدیت می‌شوند (مطابق قدیم)
     */
    public function changeItemShippingStatus(OrderItem $orderItem, string $status, ?string $cancelReason = null, bool $refundAmount = false): void
    {
        $order = $orderItem->order()->first();
        $sellerId = $orderItem->seller_id;
        $adminName = $this->adminName();
        $oldStatus = $orderItem->shipping_status;

        // بررسی پرداخت شده بودن سفارش
        if (! $order->isPaid()) {
            throw ValidationException::withMessages([
                'message' => 'سفارش پرداخت نشده است',
            ]);
        }

        if ($orderItem->refunded) {
            throw ValidationException::withMessages([
                'message' => 'این آیتم سفارش قبلاً لغو شده و وجه آن برگشت داده شده است. امکان تغییر وضعیت وجود ندارد.',
            ]);
        }

        if ($status == 'delivered') {
            $order->items()->where('seller_id', $sellerId)->update([
                'delivery_date' => now(),
            ]);
        }

        // به‌روزرسانی وضعیت برای همه آیتم‌های همان فروشنده
        $order->items()->where('seller_id', $sellerId)->update([
            'shipping_status' => $status,
        ]);

        // ذخیره دلیل لغو
        if ($cancelReason) {
            $order->items()->where('seller_id', $sellerId)->update([
                'cancel_reason' => $cancelReason,
                'canceled_at' => now(),
            ]);
        }

        $logMessage = "مدیر {$adminName} وضعیت ارسال آیتم سفارش شماره {$order->id} (محصول: {$orderItem->product->title}) را از {$oldStatus} به {$status} تغییر داد";
        if ($cancelReason) {
            $logMessage .= " (دلیل لغو: {$cancelReason})";
        }

        activity()
            ->performedOn($order)
            ->causedBy(Auth::guard('adminPanel')->user())
            ->event('updated')
            ->withProperties([
                'action' => 'update_item_shipping_status',
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'product_title' => $orderItem->product->title ?? 'نامشخص',
                'seller_id' => $sellerId,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'cancel_reason' => $cancelReason,
                'refund_amount' => $refundAmount,
                'ip' => request()->ip(),
            ])
            ->log($logMessage);

        // در صورت لغو — بازگشت وجه (مطابق قدیم)
        if ($status == 'canceled') {
            $this->refundOrderItems($order, $orderItem, $sellerId);
        }
    }

    /**
     * ثبت کد رهگیری مرسوله — همه آیتم‌های همان فروشنده (مطابق قدیم)
     */
    public function updateItemTrackingCode(OrderItem $orderItem, string $trackingCode): void
    {
        $order = $orderItem->order()->first();
        $sellerId = $orderItem->seller_id;
        $adminName = $this->adminName();

        if (! $order->isPaid()) {
            throw ValidationException::withMessages([
                'message' => 'سفارش پرداخت نشده است',
            ]);
        }

        if ($orderItem->refunded) {
            throw ValidationException::withMessages([
                'message' => 'این آیتم سفارش قبلاً لغو شده و وجه آن برگشت داده شده است. امکان ثبت کد رهگیری وجود ندارد.',
            ]);
        }

        $oldTrackingCode = $orderItem->tracking_code;

        $order->items()->where('seller_id', $sellerId)->update([
            'tracking_code' => $trackingCode,
        ]);

        activity()
            ->performedOn($order)
            ->causedBy(Auth::guard('adminPanel')->user())
            ->event('updated')
            ->withProperties([
                'action' => 'set_item_tracking_code',
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'product_title' => $orderItem->product->title ?? 'نامشخص',
                'seller_id' => $sellerId,
                'old_tracking_code' => $oldTrackingCode,
                'new_tracking_code' => $trackingCode,
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} کد رهگیری آیتم سفارش شماره {$order->id} (محصول: {$orderItem->product->title}) را از «{$oldTrackingCode}» به «{$trackingCode}» تغییر داد");
    }

    /* ==================================================================
     |  بازگشت وجه (پورت کامل refundOrderItems)
     ================================================================== */

    protected function refundOrderItems(Order $order, OrderItem $orderItem, $sellerId): void
    {
        $orderItems = $order->items()->where('seller_id', $sellerId)->get();

        // مبلغ کل بازگشت: جمع قیمت نهایی آیتم‌ها + هزینه ارسال
        $totalAmount = $orderItems->sum('price') + $orderItems->first()?->shipping_cost;

        $commission = $orderItem->commission;
        $commissionPrice = $totalAmount * $commission / 100;
        $totalAmountSeller = $totalAmount - $commissionPrice;

        // ========== بررسی موجودی کیف پول فروشنده ==========
        if ($orderItem->seller_id) {
            $seller = $orderItem->seller;

            if (! $seller || ! $seller->wallet) {
                throw ValidationException::withMessages([
                    'message' => 'کیف پول فروشنده یافت نشد',
                ]);
            }

            if ($seller->getWallet()->balance() < $totalAmountSeller) {
                throw ValidationException::withMessages([
                    'message' => 'موجودی کیف پول فروشنده برای برگشت وجه کافی نیست. موجودی فعلی: ' . number_format($seller->getWallet()->balance()) . ' تومان',
                ]);
            }
        }

        // ========== 1. برداشت از کیف پول فروشنده ==========
        if ($orderItem->seller_id) {
            $seller = $orderItem->seller;

            $seller->wallet->decrement('balance', $totalAmountSeller);

            $this->createWalletHistory(
                $seller->wallet,
                'withdraw',
                $totalAmountSeller,
                $order->id,
                $orderItem->id,
                "برداشت بابت لغو سفارش شماره {$order->id} - فروشنده: {$seller->getWallet()->balance()}",
                'seller'
            );
        }

        // ========== 2. واریز به کیف پول کاربر (با محافظت از برگشت تکراری) ==========
        $user = $order->user;

        if (! $user || ! $user->wallet) {
            throw ValidationException::withMessages([
                'message' => 'کیف پول کاربر یافت نشد',
            ]);
        }

        $existingHistory = WalletHistory::where([
            'wallet_id' => $user->wallet->id,
            'order_id' => $order->id,
            'orderCanceled' => 1,
        ])->first();

        if (! $existingHistory) {
            $user->wallet->increment('balance', $totalAmount);

            $this->createWalletHistory(
                $user->wallet,
                'deposit',
                $totalAmount,
                $order->id,
                $orderItem->id,
                "بازگشت وجه سفارش لغو شده شماره {$order->id}",
                'user'
            );
        }

        // ========== 3. ثبت وضعیت refund ==========
        $order->items()->where('seller_id', $sellerId)->update([
            'refunded' => true,
            'refunded_at' => now(),
            'refunded_amount' => $totalAmount,
        ]);

        // ========== 4. بازگشت موجودی به انبار ==========
        $order->items()->where('seller_id', $sellerId)->get()->each(function ($item) {
            $item->get_price()->first()->increment('stock', $item->quantity);
        });
    }

    protected function createWalletHistory($wallet, $type, $amount, $orderId, $orderItemId, $description, $source = 'admin')
    {
        return $wallet->histories()->create([
            'type' => $type,
            'source' => $source,
            'status' => 'success',
            'amount' => $amount,
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'description' => $description,
            'orderCanceled' => 1,
        ]);
    }

    /**
     * ساخت Request موقت برای WalletController@store (مطابق پنل قدیمی)
     */
    protected function walletRequest(array $data)
    {
        $request = request();
        foreach ($data as $key => $value) {
            $request->merge([$key => $value]);
        }

        return $request;
    }

    protected function adminName(): string
    {
        $admin = Auth::guard('adminPanel')->user();

        return $admin?->full_name ?? $admin?->name ?? 'مدیر';
    }
}

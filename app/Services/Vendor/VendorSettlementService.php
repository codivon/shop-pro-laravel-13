<?php

namespace App\Services\Vendor;

use App\Events\WalletAmountIncreased;
use App\Models\Order;
use App\Models\Seller;
use App\Models\SellerDeposit;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Facades\DB;

/**
 * موتور تسویهٔ فروشندگان (فاز ۱۰۳).
 *
 * مسئولیت: پس از پرداختِ موفق یک سفارش، سهم هر فروشنده (پس از کسر
 * کمیسیون) به کیف پولش واریز و ردیف کمیسیونِ سایت در seller_deposits
 * ثبت شود — دقیقاً همان کاری که مسیر «پرداخت با کیف پول» از قبل در
 * Order::payUsingWallet() به‌صورت inline انجام می‌داد، ولی برای «همهٔ»
 * مسیرهای پرداخت و به‌صورت ایدمپوتنت.
 *
 * محاسبهٔ سهم: Order::priceSellerDepositWallet($sellerId) — همان منبع
 * واحدی که مسیر کیف پول استفاده می‌کند (کمیسیون مؤثر از طریق
 * MultiVendorSettings::resolveCommission داخل همان متد تزریق شده است).
 *
 * ایدمپوتنس: برای هر (order_id, seller_id) فقط یک ردیف seller_deposits.
 * چون مسیر کیف پول هم ردیف می‌سازد، سفارش دو-مسیره دوباره واریز نمی‌شود.
 * برای مسابقهٔ همزمان، داخل تراکنش ردیف‌های واریزِ همان سفارش
 * lockForUpdate و مجدداً چک می‌شوند.
 */
class VendorSettlementService
{
    /**
     * تسویهٔ سفارش برای همهٔ فروشندگانِ آیتم‌هایش.
     *
     * @return int تعداد فروشندگانی که در این فراخوانی تازه تسویه شدند
     */
    public function settleOrder(Order $order): int
    {
        $sellerIds = $order->items()
            ->whereNotNull('seller_id')
            ->distinct()
            ->pluck('seller_id');

        $settled = 0;

        foreach ($sellerIds as $sellerId) {
            if ($this->settleSeller($order, (int) $sellerId)) {
                $settled++;
            }
        }

        return $settled;
    }

    /**
     * تسویهٔ سهمِ یک فروشنده از یک سفارش (اگر قبلاً تسویه نشده باشد).
     *
     * @return bool آیا تازه واریز شد؟
     */
    public function settleSeller(Order $order, int $sellerId): bool
    {
        if (SellerDeposit::where('order_id', $order->id)->where('seller_id', $sellerId)->exists()) {
            return false;
        }

        $seller = Seller::find($sellerId);

        if (! $seller) {
            return false;
        }

        $settled = false;

        DB::transaction(function () use ($order, $seller, &$settled) {
            // قفل ردیف‌های واریزِ همین سفارش → دو پردازش همزمان دوباره‌کاری نمی‌کنند
            SellerDeposit::where('order_id', $order->id)->lockForUpdate()->get();

            if (SellerDeposit::where('order_id', $order->id)->where('seller_id', $seller->id)->exists()) {
                return; // مسیر کیف پول (یا اجرای همزمان) قبلاً تسویه کرده
            }

            $amount = $order->priceSellerDepositWallet($seller->id);
            $wallet = $seller->getWallet();

            // واریز سهم فروشنده — هم‌شکل inlineِ payUsingWallet
            $wallet->histories()->create([
                'type' => 'deposit',
                'amount' => $amount['priceForSeller'],
                'description' => 'تسویهٔ خودکار فروش سفارش #'.$order->id,
                'source' => 'seller',
                'status' => 'success',
                'order_id' => $order->id,
            ]);

            // ردیف کمیسیون سایت — مرجع ایدمپوتنس و گزارش‌های کمیسیون
            SellerDeposit::create([
                'seller_id' => $seller->id,
                'order_id' => $order->id,
                'amount' => $amount['priceForSite'],
                'category_id' => $amount['category_id'],
                'percent' => $amount['percent'],
                'description' => 'کمیسیون فروش سفارش #'.$order->id,
                'status' => 'success',
            ]);

            $wallet->refereshBalance();

            $settled = true;
        });

        // رویداد بعد از commit — خطای اطلاع‌رسانی نباید پول را rollback کند
        if ($settled) {
            event(new WalletAmountIncreased($seller->getWallet()));
        }

        return $settled;
    }

    /**
     * گیتِ کامل تسویهٔ خودکار — برای استفادهٔ کنترلرها/کامپوننت‌ها.
     */
    public static function autoSettlementActive(): bool
    {
        return MultiVendorSettings::enabled() && MultiVendorSettings::autoSettlement();
    }
}

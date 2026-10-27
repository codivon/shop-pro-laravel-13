<?php

namespace App\Listeners;

use App\Events\OrderPaid as EventsOrderPaid;
use App\Services\Vendor\VendorSettlementService;
use App\Support\MultiVendorSettings;

/**
 * فاز ۱۰۳ — تسویهٔ خودکار فروشندگان پس از پرداخت سفارش.
 *
 * ⭐ شکافی که این شنونده پر می‌کند: تا پیش از این فاز فقط سفارش‌هایی که با
 * «کیف پول خریدار» پرداخت می‌شدند سهم فروشنده را واریز می‌کردند
 * (Order::payUsingWallet — inline). سفارش‌هایی که با درگاه پرداخت می‌شدند
 * هرگز کیف پول فروشنده را شارژ نمی‌کردند و ردیف seller_deposits ثبت
 * نمی‌شد.
 *
 * هر دو مسیر پرداخت (وب: OrderController::orderPaid و API:
 * CheckoutService::finalizePaidOrder) رویداد OrderPaid را شلیک می‌کنند،
 * پس این شنونده هر دو را پوشش می‌دهد.
 *
 * ایدمپوتنت است: VendorSettlementService برای هر (سفارش، فروشنده) فقط
 * یک‌بار واریز می‌کند — پس سفارش کیف‌پولی (که inline واریز شده) دوباره‌کاری
 * نمی‌شود.
 *
 * گیت‌ها: کلید اصلی چندفروشندگی + کلید «تسویهٔ خودکار» (هر دو از تنظیمات).
 */
class SettleVendorsOnOrderPaid
{
    public function handle(EventsOrderPaid $event): void
    {
        if (! MultiVendorSettings::enabled()) {
            return;
        }

        if (! MultiVendorSettings::autoSettlement()) {
            return;
        }

        app(VendorSettlementService::class)->settleOrder($event->order);
    }
}

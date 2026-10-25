<?php

namespace App\Observers;

use App\Jobs\ProcessPriceAlerts;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Support\Carbon;

/**
 * همگام‌سازی ستون‌های دینرمال‌شدهٔ products (in_stock و min_price) با جدول prices.
 *
 * چرا؟ در مقیاس میلیون محصول، scopeAvailable / orderByStock / orderByPrice
 * نمی‌توانند در هر ریکوئست subquery بزنند — به‌جای آن این Observer هنگام
 * ذخیره/حذف هر قیمت، دو ستون محصول را در یک کوئری به‌روز می‌کند.
 *
 * نکته: آپدیت‌های مستقیم با Query Builder (بدون Eloquent Event) این Observer
 * را دور می‌زنند؛ برای اصلاح این موارد دستور artisan را ببینید:
 *   php artisan products:sync-denormalized
 */
class PriceObserver
{
    public function saved(Price $price): void
    {
        $this->sync($price);
        $this->dispatchPriceAlertsOnDrop($price);
    }

    /**
     * فاز ۷۸ — اگر قیمت مؤثر (تخفیف‌دار/معمولی) نسبت به مقدار پیش از ذخیره
     * افت کرده باشد، پردازش هشدارهای کاهش قیمتِ محصول به‌صورت صف رانده می‌شود.
     * تغییر موجودی/انتشار بدون افت قیمت، هشداری راه نمی‌اندازد.
     */
    protected function dispatchPriceAlertsOnDrop(Price $price): void
    {
        if (! $price->product_id || $price->wasRecentlyCreated) {
            return;
        }

        $old = $this->effectivePrice(
            $price->getRawOriginal('discount'),
            $price->getRawOriginal('discount_expire_at'),
            $price->getRawOriginal('discount_price'),
            $price->getRawOriginal('regular_price'),
            $price
        );

        $new = $this->effectivePrice(
            $price->discount,
            $price->discount_expire_at,
            $price->discount_price,
            $price->regular_price,
            $price
        );

        if ($old === null || $new === null || $new >= $old) {
            return;
        }

        ProcessPriceAlerts::dispatch((int) $price->product_id);
    }

    /** قیمت مؤثر یک ردیف قیمت با همان منطق hasDiscount/salePrice مدل Price */
    private function effectivePrice($discount, $discountExpireAt, $discountPrice, $regularPrice, Price $price): ?float
    {
        $hasDiscount = $discount && (is_null($discountExpireAt) || Carbon::parse($discountExpireAt)->gt(now()));

        $raw = $hasDiscount ? $discountPrice : $regularPrice;

        if ($raw === null) {
            return null;
        }

        return (float) $price->toRoundPrice($raw);
    }

    public function deleted(Price $price): void
    {
        $this->sync($price);
    }

    protected function sync(Price $price): void
    {
        if (! $price->product_id) {
            return;
        }

        Product::syncStockAndMinPrice((int) $price->product_id);
    }
}

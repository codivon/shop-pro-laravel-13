<?php

namespace App\Observers;

use App\Models\Price;
use App\Models\Product;

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

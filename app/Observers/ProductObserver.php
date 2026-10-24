<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * FIX کارایی بحرانی: قبلاً کانستراکتور Observer کش‌ها را در «هر ریکوئست»
 * پاک می‌کرد (Observerها هنگام ثبت در هر چرخهٔ برنامه instantiate می‌شوند) —
 * یعنی عملاً هیچ کشی روی مسیر فروشگاه زنده نمی‌ماند.
 * حالا ابطال کش فقط هنگام رویداد واقعی created/updated/deleted انجام می‌شود.
 */
class ProductObserver
{
    public function created(Product $product): void
    {
        $this->flush();
    }

    public function updated(Product $product): void
    {
        $this->flush();
    }

    public function deleted(Product $product): void
    {
        $this->flush();
    }

    public function restored(Product $product): void
    {
        $this->flush();
    }

    public function forceDeleted(Product $product): void
    {
        $this->flush();
    }

    protected function flush(): void
    {
        foreach (config('front.cache-forget.products', []) as $key) {
            Cache::forget($key);
        }

        foreach (Product::cacheKeys() as $key) {
            Cache::forget($key);
        }
    }
}

<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * FIX کارایی بحرانی: قبلاً کانستراکتور Observer در «هر ریکوئست» کش منوی
 * دسته‌بندی‌ها (front.productcats) را پاک می‌کرد و درخت دسته‌ها در هر بازدید
 * بازسازی می‌شد. حالا ابطال فقط هنگام رویداد واقعی انجام می‌شود.
 * همچنین «front.categories.version» افزایش می‌یابد تا کلیدهای کش‌های وابسته
 * (مثل شمارش محصولات دسته‌ها) بدون نیاز به تگ‌ینگ، خودبه‌خود باطل شوند.
 */
class CategoryObserver
{
    public function created(Category $category): void
    {
        $this->flush();
    }

    public function updated(Category $category): void
    {
        $this->flush();
    }

    public function deleted(Category $category): void
    {
        $this->flush();
    }

    protected function flush(): void
    {
        foreach (config('front.cache-forget.categories', []) as $key) {
            Cache::forget($key);
        }

        Cache::forget('category.children_counts');

        // نسخهٔ دسته‌ها برای ابطال خودکار کلیدهای کش ترکیبی (بدون Cache Tags)
        Cache::increment('front.categories.version');
    }
}

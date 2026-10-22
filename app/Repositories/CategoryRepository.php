<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;

trait CategoryRepository
{
    /**
     * شمارش فرزندان منتشرشدهٔ هر دسته با یک کوئری تجمیعی کش‌شده.
     * قبلاً برای هر دسته یک کوئری COUNT جداگانه اجرا می‌شد (N+1 در منو/مگامنو)
     * که روی صفحات پرترافیک ۴۰+ کوئری اضافه می‌کرد.
     * کش با تغییر دسته‌ها (CategoryObserver) باطل می‌شود.
     */
    public function getCategoriesCount()
    {
        $counts = Cache::rememberForever('category.children_counts', function () {
            return static::query()
                ->whereNotNull('category_id')
                ->where('published', true)
                ->selectRaw('category_id, COUNT(*) as total')
                ->groupBy('category_id')
                ->pluck('total', 'category_id')
                ->toArray();
        });

        return (int) ($counts[$this->id] ?? 0);
    }

    public function getCategories()
    {
        return $this->categories()->published()->get();
    }
}

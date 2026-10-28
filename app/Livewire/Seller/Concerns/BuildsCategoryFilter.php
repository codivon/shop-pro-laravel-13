<?php

namespace App\Livewire\Seller\Concerns;

use App\Models\Category;

/**
 * فاز ۱۱۹ — داده‌های «فیلتر مدرن دسته‌بندی» پنل فروشنده (مشترک میان
 * جستجو/درج محصول و مدیریت محصولات).
 *
 *  • $categoriesTree  — درخت رندرشونده (childrenTree روی هر گره)
 *  • $categoriesFlatJs — لیست تخت {id, title, path} برای جستجوی زنده
 *  • $categoryTitles  — نقشهٔ id → عنوانِ دسته‌های انتخاب‌شده (چیپ‌ها)
 *  • removeCategory() — حذف تکی چیپ
 *
 * پراپرتی لایووایر (category_id[]) و منطق فیلتر سرور دست‌نخورده مانده‌اند.
 */
trait BuildsCategoryFilter
{
    public function removeCategory(int $id): void
    {
        $this->category_id = array_values(
            array_filter($this->category_id, fn ($catId) => (int) $catId !== $id)
        );

        $this->resetPage();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>  $categories
     * @return array{tree: \Illuminate\Support\Collection, flatJs: array, titles: array}
     */
    protected function buildCategoryFilterData($categories): array
    {
        $byParent = $categories->groupBy(fn (Category $category) => $category->category_id);

        $buildTree = function (?int $parentId) use (&$buildTree, $byParent) {
            return ($byParent->get($parentId) ?? collect())
                ->map(function (Category $category) use ($buildTree) {
                    $category->childrenTree = $buildTree($category->id);

                    return $category;
                })
                ->values();
        };

        // مسیر کامل (پدرها + خودش) برای جستجوی تخت و چیپ‌ها
        $flatJs = $categories->map(function (Category $category) {
            $path = $category->parents()
                ->reverse()
                ->pluck('title')
                ->push($category->title)
                ->implode(' / ');

            return [
                'id' => (int) $category->id,
                'title' => $category->title,
                'path' => $path,
            ];
        })->values()->all();

        $titles = $categories
            ->whereIn('id', array_map('intval', $this->category_id))
            ->sortBy(fn (Category $category) => array_search($category->id, array_map('intval', $this->category_id)))
            ->pluck('title', 'id')
            ->all();

        return [
            'tree' => $buildTree(null),
            'flatJs' => $flatJs,
            'titles' => $titles,
        ];
    }
}

<?php

namespace App\Livewire\Seller\Wallet;

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — «کمیسیون و هزینه‌ها» فروشنده (پورت WalletSellerController@commission).
 *
 * جدول کمیسیون دسته‌بندی‌ها برای فروشنده — همان ساخت درخت دسته‌ها و
 * کمیسیونِ مؤثر با ارث‌بری (override دسته > والد > پیش‌فرض صفر).
 * متد کمکی commissionData() مشترک میان این صفحه و «نرخ کمیسیون» (CommissionGuide)
 * است — عیناً همان الگوی قدیمی که showCommissionRates() را به commission() وصل می‌کرد.
 */
#[Layout('sellers.layouts.panel')]
#[Title('کمیسیون و هزینه‌ها')]
class Commission extends Component
{
    /**
     * دادهٔ صفحهٔ کمیسیون — درخت دسته‌ها با کمیسیون مؤثر
     * (پورت ۱:۱ commission() + buildCategoryTree کنترلر قدیمی).
     */
    protected function commissionData(): array
    {
        $rootCategories = Category::whereNull('category_id')->where('type', 'productcat')->get();

        if ($rootCategories->isEmpty()) {
            $rootCategories = Category::all();
        }

        // استفاده از کالکشن به جای آرایه (هم‌الگوی قدیم)
        $categoriesWithCommission = collect();

        foreach ($rootCategories as $category) {
            $categoriesWithCommission->push($this->buildCategoryTree($category));
        }

        // جدول تخت‌شدهٔ درخت (DFS) — همان ترتیب رندر جزئی category-row قدیمی
        // (شماره‌گذاری «۱ / ۱.۱ / ۱.۲» + سطح تودرتو برای تورفتگی)
        $rows = [];
        $counter = 0;
        foreach ($categoriesWithCommission as $i => $tree) {
            $this->flattenTree($tree, 0, (string) ($i + 1), $rows, $counter);
        }

        // خلاصهٔ بالای جدول — عیناً همان محاسبات ویوی قدیمی (روی ریشه‌ها)
        $summary = [
            'max' => $categoriesWithCommission->max('commission') ?? 0,
            'avg' => round($categoriesWithCommission->avg('commission') ?? 0),
            'count' => $categoriesWithCommission->count(),
        ];

        return [
            'categoriesWithCommission' => $categoriesWithCommission,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * تبدیل بازگشتی درخت به ردیف‌های تخت با سطح و شمارهٔ سلسله‌مراتبی.
     */
    private function flattenTree(array $node, int $level, string $index, array &$rows, int &$counter): void
    {
        $counter++;
        $rows[] = [
            'id' => $node['id'],
            'name' => $node['name'],
            'level' => $level,
            'index' => $index,
            'commission' => $node['commission'],
            'commission_type' => $node['commission_type'],
            'has_children' => count($node['children']) > 0,
        ];

        foreach ($node['children'] as $childIndex => $child) {
            $this->flattenTree($child, $level + 1, $index.'.'.($childIndex + 1), $rows, $counter);
        }
    }

    public function render()
    {
        return view('sellers.panel.wallet.commission.index', $this->commissionData());
    }

    /**
     * ساخت گرهٔ درخت دسته‌ها با کمیسیون مؤثر (پورت buildCategoryTree).
     */
    private function buildCategoryTree($category)
    {
        // دریافت کمیسیون مؤثر با ارث‌بری
        $commissionInfo = $this->getEffectiveCommissionInfo($category);

        $result = [
            'id' => $category->id,
            'name' => $category->title,
            'commission' => $commissionInfo['commission'],
            'commission_type' => $commissionInfo['type'],
            'source_category' => $commissionInfo['source'] ?? null,
            'children' => [],
        ];

        // دریافت فرزندان (دسته‌بندی‌هایی که category_id آنها برابر id فعلی است)
        $children = Category::where('category_id', $category->id)->get();

        foreach ($children as $child) {
            $result['children'][] = $this->buildCategoryTree($child);
        }

        return $result;
    }

    /**
     * کمیسیون مؤثر با پیمایش به سمت والد (پورت getEffectiveCommissionInfo).
     */
    private function getEffectiveCommissionInfo($category)
    {
        $current = $category;
        $depth = 0;
        $sourceCategory = null;

        while ($current) {
            if (! is_null($current->commission)) {
                return [
                    'commission' => (int) $current->commission,
                    'type' => $depth == 0 ? 'explicit' : 'inherited',
                    'source' => $sourceCategory ?: $current->id,
                ];
            }

            // رفتن به والد (با استفاده از category_id)
            if (! is_null($current->category_id)) {
                $sourceCategory = $current->category_id;
                $current = Category::find($current->category_id);
            } else {
                $current = null;
            }
            $depth++;
        }

        return [
            'commission' => 0,
            'type' => 'default_zero',
            'source' => null,
        ];
    }
}

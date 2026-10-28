<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\Seller;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۱۹ — «موجودی و تنوع‌ها» پنل مدیریت (درخواست کاربر):
 * نمای کلی موجودی کل هر محصول + ریز تک‌تک تنوع‌ها با تفکیک
 * «فروش خودِ سایت» و «تنوع‌های فروشندگان» (چندفروشندگی).
 *
 * جای قرارگیری: گروه «عملیات محصولات → محصولات → موجودی و تنوع‌ها»
 * (np.products.stock) — چون مکمل «لیست محصولات» و «قیمت‌ها» است.
 * پرمیشن: products.index (همان دسترسی دیدن لیست محصولات — بدون پرمیشن جدید
 * تا نصب‌های موجود بدون اجرای مجدد سیدر کار کنند).
 */
#[Title('موجودی و تنوع‌ها')]
class StockIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'title', except: '')]
    public string $title = '';

    #[Url(as: 'category_id', except: [])]
    public array $categoryId = [];

    public int $perPage = 10;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['title', 'categoryId', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست محصولات با تنوع‌ها ---------------- */

    #[Computed]
    public function products()
    {
        $query = Product::query()->detectLang();

        if ($this->title !== '') {
            $query->where('title', 'like', '%'.$this->title.'%');
        }

        if ($this->categoryId) {
            $allcats = $this->categoryId;

            foreach ($this->categoryId as $category) {
                $category = Category::find($category);

                if ($category) {
                    $allcats = array_merge($category->allChildCategories(), $allcats);
                }
            }

            $query->whereIn('category_id', $allcats);
        }

        return $query->with([
            'category',
            'prices' => fn ($q) => $q->orderBy('id'),
            'prices.get_attributes.group',
            'prices.seller.seller_info',
            'prices.warehouse',
        ])
            ->latest()
            ->paginate($this->perPage);
    }

    /* ---------------- آمار کلی (روی همهٔ محصولات) ---------------- */

    #[Computed]
    public function stats(): array
    {
        return [
            // موجودی کل انبار روی همهٔ محصولات (هر دو فروش سایت و فروشندگان)
            'total_stock' => (int) Price::query()->sum('stock'),
            // تعداد کل تنوع‌ها
            'total_variants' => (int) Price::query()->count(),
            // تعداد تنوع‌های متعلق به فروشندگان (چندفروشندگی)
            'seller_variants' => (int) Price::query()->whereNotNull('seller_id')->count(),
            // تعداد محصول‌های دارای موجودی صفر
            'page_total_stock' => (int) $this->products->getCollection()->sum(
                fn ($product) => $product->prices->sum('stock')
            ),
        ];
    }

    #[Computed]
    public function categories()
    {
        return Category::query()->detectLang()
            ->where('type', 'productcat')
            ->orderBy('ordering')
            ->get();
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.products.stock')
            ->layout('admin-panel::layout');
    }
}

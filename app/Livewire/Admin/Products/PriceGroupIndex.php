<?php

namespace App\Livewire\Admin\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تغییر گروهی قیمت — پورت ۱:۱ ProductController@indexPricesGroup + updatePricesGroup.
 * سه هدف (محصول/دسته‌بندی/برند) + درصد یا مبلغ ثابت + گزینه تنوع فروشنده‌ها.
 */
#[Title('تغییر گروهی قیمت')]
class PriceGroupIndex extends Component
{
    /* ---------------- هدف تغییر ---------------- */

    /** product | category | brand (تب‌ها مثل قدیمی) */
    public string $type = 'product';

    /** increase | decrease */
    public string $typeChenge = '';

    public string $percent = '';

    public string $price = '';

    public bool $sellerPrice = false;

    /* ---------------- انتخاب محصولات (چیپ‌پیکر جای اتوکامپلیت قدیمی) ---------------- */

    public array $productsSelected = [];

    public string $productsSearch = '';

    public array $productsResults = [];

    /* ---------------- انتخاب دسته‌بندی‌ها / برندها ---------------- */

    public array $categories = [];

    public array $brands = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.pricesGroup') ?: abort(403);
    }

    /* ---------------- لیست‌های انتخاب (پورت indexPricesGroup قدیمی) ---------------- */

    #[Computed]
    public function categoryList()
    {
        return Category::query()->detectLang()
            ->where('type', 'productcat')
            ->orderBy('ordering')
            ->get();
    }

    #[Computed]
    public function brandList()
    {
        return Brand::query()->detectLang()
            ->orderBy('created_at')
            ->get();
    }

    /* ---------------- جستجوی محصولات (چیپ‌پیکر) ---------------- */

    public function updatedProductsSearch(): void
    {
        $this->productsResults = $this->searchProducts($this->productsSearch);
    }

    protected function searchProducts(string $search): array
    {
        $term = trim($search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        return Product::query()
            ->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('title_en', 'like', "%{$term}%");
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'title'])
            ->map(fn (Product $product) => [
                'id'    => (int) $product->id,
                'label' => (string) $product->title,
            ])
            ->values()
            ->all();
    }

    public function addProduct(int $productId): void
    {
        if (collect($this->productsSelected)->pluck('id')->contains((int) $productId)) {
            return;
        }

        if (! $product = Product::find($productId)) {
            return;
        }

        $this->productsSelected[] = ['id' => (int) $product->id, 'label' => (string) $product->title];
        $this->productsSearch = '';
        $this->productsResults = [];
    }

    public function removeProduct(int $productId): void
    {
        $this->productsSelected = array_values(
            array_filter($this->productsSelected, fn (array $chip) => (int) $chip['id'] !== $productId)
        );
    }

    /* ---------------- اعمال تغییر گروهی (پورت دقیق updatePricesGroup قدیمی) ---------------- */

    public function updatePricesGroup(): void
    {
        auth('adminPanel')->user()?->can('products.pricesGroup') ?: abort(403);

        $this->validate([
            'type'       => 'required|in:product,category,brand',
            'typeChenge' => 'required|in:increase,decrease',
            'percent'    => 'nullable|numeric|min:0|max:100|required_without_all:price',
            'price'      => 'nullable|numeric|required_without_all:percent',
        ]);

        // اعتبارسنجی بر اساس نوع هدف (پیام‌های فارسی عیناً از قدیمی)
        switch ($this->type) {
            case 'product':
                $this->validate(['productsSelected' => 'required|array'], ['productsSelected.required' => 'فیلد محصولات الزامی است']);
                break;
            case 'category':
                $this->validate(['categories' => 'required|array'], ['categories.required' => 'فیلد دسته بندی الزامی است']);
                break;
            case 'brand':
                $this->validate(['brands' => 'required|array'], ['brands.required' => 'فیلد برند الزامی است']);
                break;
        }

        // دریافت لیست محصولات مرتبط با دسته‌ها (پورت دقیق — بدون closure دور OR مثل قدیمی)
        if ($this->type === 'category') {
            $products = Product::query()
                ->whereIn('products.category_id', $this->categories)
                ->orWhereHas('categories', function ($q) {
                    $q->whereIn('category_product.category_id', $this->categories);
                })
                ->pluck('products.id');
        }

        // دریافت قیمت‌های مرتبط از جدول prices
        $query = Price::query();

        if ($this->type === 'product') {
            $query->whereIn('product_id', array_map('intval', array_column($this->productsSelected, 'id')));
        } elseif ($this->type === 'category') {
            $query->whereIn('product_id', $products);
        } elseif ($this->type === 'brand') {
            $query->whereHas('product', function ($q) {
                $q->whereIn('brand_id', array_map('intval', $this->brands));
            });
        }

        // حذف مواردی که seller_id دارند (مگر گزینه‌ی تنوع فروشنده‌ها فعال باشد)
        if (! $this->sellerPrice) {
            $query->whereNull('seller_id');
        }

        // دریافت قیمت‌های فیلتر شده
        $prices        = $query->get();
        $affectedCount = $prices->count();

        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // ذخیره اطلاعات تغییرات برای لاگ
        $oldPrices = [];
        $newPrices = [];
        $typeText  = '';
        $targetNames = [];

        // دریافت نام هدف‌ها برای لاگ (پورت دقیق)
        if ($this->type === 'product') {
            $typeText    = 'محصولات';
            $targetNames = Product::whereIn('id', array_map('intval', array_column($this->productsSelected, 'id')))->pluck('title')->toArray();
        } elseif ($this->type === 'category') {
            $typeText    = 'دسته‌بندی‌ها';
            $targetNames = Category::whereIn('id', array_map('intval', $this->categories))->pluck('title')->toArray();
        } elseif ($this->type === 'brand') {
            $typeText    = 'برندها';
            $targetNames = Brand::whereIn('id', array_map('intval', $this->brands))->pluck('name')->toArray();
        }

        // اعمال تغییر قیمت و ذخیره تغییرات (پورت دقیق)
        foreach ($prices as $price) {
            $oldPrice = $price->regular_price;

            if ($this->percent !== '' && $this->percent !== null) {
                $changeAmount = ($price->regular_price * $this->percent) / 100;
            } else {
                $changeAmount = $this->price;
            }

            if ($this->typeChenge === 'increase') {
                $newPrice = $price->regular_price + $changeAmount;
            } else {
                $newPrice = $price->regular_price - $changeAmount;
            }

            // اطمینان از عدم منفی شدن قیمت
            $newPrice = max(0, $newPrice);

            $productTitle  = $price->product->title ?? 'محصول نامشخص';
            $variationInfo = '';
            if ($price->attributes->count() > 0) {
                $variationInfo = ' (' . $price->attributes->pluck('name')->implode(' - ') . ')';
            }

            $key = $productTitle . $variationInfo;
            $oldPrices[$key] = number_format((float) $oldPrice) . ' تومان';
            $newPrices[$key] = number_format((float) $newPrice) . ' تومان';

            $price->regular_price = $newPrice;

            // اگر discount وجود دارد، discount_price را نیز محاسبه کن
            if (! is_null($price->discount) && $price->discount > 0) {
                $discountAmount        = ($price->regular_price * $price->discount) / 100;
                $price->discount_price = $price->regular_price - $discountAmount;
            } else {
                $price->discount_price = $price->regular_price;
            }

            $price->save();
        }

        // ساخت متن لاگ (پورت دقیق)
        $changeTypeText = $this->typeChenge === 'increase' ? 'افزایش' : 'کاهش';
        $amountText     = ($this->percent !== '' && $this->percent !== null)
            ? "{$this->percent} درصد"
            : number_format($this->price) . ' تومان';
        $targetsText = count($targetNames) > 3
            ? implode('، ', array_slice($targetNames, 0, 3)) . ' و ' . (count($targetNames) - 3) . ' مورد دیگر'
            : implode('، ', $targetNames);

        // ساختار استاندارد old و attributes برای properties — فقط نمونه‌ای (حداکثر 10)
        $oldData = [];
        $newData = [];

        if ($affectedCount > 0) {
            $sampleOld = array_slice($oldPrices, 0, 10);
            $sampleNew = array_slice($newPrices, 0, 10);

            foreach ($sampleOld as $key => $old) {
                $oldData[$key] = $old;
                $newData[$key] = $sampleNew[$key];
            }
        }

        $logMessage = "مدیر {$adminName} تغییر گروهی قیمت {$typeText} را انجام داد";
        $logMessage .= " ({$changeTypeText} به میزان {$amountText})";
        $logMessage .= " - تعداد آیتم‌های تحت تأثیر: {$affectedCount}";

        if (! empty($targetsText)) {
            $logMessage .= " - هدف‌ها: {$targetsText}";
        }

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('update_prices_group')
            ->withProperties([
                'action'       => 'update_prices_group',
                'type'         => $this->type,
                'typeChenge'   => $this->typeChenge,
                'change_type'  => $changeTypeText,
                'amount_type'  => ($this->percent !== '' && $this->percent !== null) ? 'percent' : 'fixed',
                'amount_value' => $this->percent !== '' && $this->percent !== null ? $this->percent : $this->price,
                'amount_text'  => $amountText,
                'affected_count' => $affectedCount,
                'targets'      => $targetNames,
                'seller_price' => $this->sellerPrice,
                'old'          => $oldData,
                'attributes'   => $newData,
                'ip'           => request()->ip(),
            ])
            ->log($logMessage);

        // پیام قدیمی: session()->put('toast-success', 'تغییر قیمت‌ها با موفقیت انجام شد.')
        $this->dispatch('toast', message: 'تغییر قیمت‌ها با موفقیت انجام شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.products.prices-group')
            ->layout('admin-panel::layout');
    }
}

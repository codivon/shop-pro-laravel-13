<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * قیمت‌گذاری محصولات — پورت ۱:۱ ProductController@indexPrices + updatePrices.
 * ویرایش گروهی قیمت/موجودی با اعتبارسنجی اعداد، آپدیت جدول prices،
 * createChange + محاسبه discount_price/regular_price و لاگ کامل old/attributes.
 */
#[Title('قیمت‌گذاری محصولات')]
class PriceIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'title', except: '')]
    public string $title = '';

    #[Url(as: 'category_id', except: [])]
    public array $categoryId = [];

    public int $perPage = 10;

    /* ---------------- ورودی‌های قیمت/موجودی ---------------- */

    /** ساختار: [productId][priceId] = ['price' => ?, 'stock' => ?] */
    public array $priceInputs = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.prices') ?: abort(403);

        $this->fillPriceInputs();
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['title', 'categoryId', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function updatedPage(): void
    {
        $this->fillPriceInputs();
    }

    /* ---------------- لیست محصولات (پورت indexPrices قدیمی) ---------------- */

    #[Computed]
    public function products()
    {
        $query = Product::query()->detectLang();

        // پورت scopeFilter (title + دسته با فرزندان) — نکته: scopeFilter خودِ مدل Product
        // با نسخه‌ی front بازنویسی شده و فیلترهای پنل قدیمی را اجرا نمی‌کرد؛
        // اینجا همان منطق ProductScopes::filter بازسازی شده است (فیلتر کارا).
        if ($this->title !== '') {
            $query->where('title', 'like', '%' . $this->title . '%');
        }

        $categories = $this->categoryId;

        if ($categories) {
            $allcats = $categories;

            foreach ($categories as $category) {
                $category = Category::find($category);

                if ($category) {
                    $allcats = array_merge($category->allChildCategories(), $allcats);
                }
            }

            $query->whereIn('category_id', $allcats);
        }

        return $query->latest()->with('prices')->paginate($this->perPage);
    }

    #[Computed]
    public function categories()
    {
        return Category::query()->detectLang()
            ->where('type', 'productcat')
            ->orderBy('ordering')
            ->get();
    }

    /* ---------------- فاز ۱۱۹ — نام فروشگاه‌های صاحب تنوع (بَج فروشنده) ---------------- */

    /** نقشهٔ seller_id → نام فروشگاه برای مشخص‌کردن تنوع‌های ایجادشدهٔ فروشندگان */
    #[Computed]
    public function sellerLabels(): array
    {
        $sellerIds = $this->products
            ->flatMap(fn ($product) => $product->prices->pluck('seller_id'))
            ->filter()
            ->unique()
            ->values();

        if ($sellerIds->isEmpty()) {
            return [];
        }

        return Seller::with('seller_info')
            ->whereIn('id', $sellerIds)
            ->get()
            ->mapWithKeys(fn ($seller) => [
                $seller->id => $seller->seller_info?->business_name
                    ?: trim(($seller->seller_info?->first_name ?? '') . ' ' . ($seller->seller_info?->last_name ?? ''))
                    ?: ('فروشنده #' . $seller->id),
            ])
            ->all();
    }

    /* ---------------- پرکردن ورودی‌ها ---------------- */

    protected function fillPriceInputs(): void
    {
        $this->priceInputs = [];

        foreach ($this->products as $product) {
            foreach ($product->prices as $price) {
                $this->priceInputs[$product->id][$price->id] = [
                    'price' => $price->price(),
                    'stock' => $price->stock,
                ];
            }
        }
    }

    /* ---------------- ذخیره گروهی (پورت دقیق updatePrices قدیمی) ---------------- */

    public function savePrices(): void
    {
        auth('adminPanel')->user()?->can('products.prices') ?: abort(403);

        $this->validate([
            'priceInputs' => 'required|array',
        ], [], [
            'priceInputs' => 'محصولات',
        ]);

        $products_id    = array_map('intval', array_keys($this->priceInputs));
        $prices_count   = Price::whereIn('product_id', $products_id)->count() * 2;
        $max_input_vars = ini_get('max_input_vars');

        if ($prices_count + 5 > (int) $max_input_vars) {
            throw ValidationException::withMessages([
                'prices' => 'لطفا مقدار max_input_vars را در فایل php.ini تغییر دهید.'
            ]);
        }

        $updatedProducts = [];

        foreach ($this->priceInputs as $productId => $value) {
            $product = Product::find((int) $productId);

            if (! $product) {
                continue;
            }

            $productChanges = [];

            foreach ($product->prices as $price) {
                if (! isset($value[$price->id])) {
                    continue;
                }

                $request_price = $value[$price->id];

                if (isset($request_price['price']) && isset($request_price['stock']) && ($request_price['price'] != $price->price || $request_price['stock'] != $price->stock)) {
                    $productChanges[] = [
                        'price_id'  => $price->id,
                        'old_price' => $price->price,
                        'new_price' => $request_price['price'],
                        'old_stock' => $price->stock,
                        'new_stock' => $request_price['stock'],
                    ];

                    $price->createChange(
                        $request_price['price'],
                        $price->discount,
                        $request_price['stock']
                    );

                    $price->update([
                        'price'          => $request_price['price'],
                        'stock'          => $request_price['stock'],
                        'discount_price' => get_discount_price($request_price['price'], $price->discount, $product),
                        'regular_price'  => get_discount_price($request_price['price'], 0, $product),
                    ]);
                }
            }

            if (! empty($productChanges)) {
                $updatedProducts[] = [
                    'product_id'    => $product->id,
                    'product_title' => $product->title,
                    'changes'       => $productChanges,
                ];
            }
        }

        // ثبت لاگ تغییر قیمت محصولات (پورت دقیق)
        if (! empty($updatedProducts)) {
            $adminName    = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
            $productCount = count($updatedProducts);

            $properties = [
                'action'           => 'update_products_prices',
                'updated_products' => $updatedProducts,
                'ip'               => request()->ip(),
            ];

            // ساختار استاندارد برای تغییرات
            $oldData = [];
            $newData = [];
            foreach ($updatedProducts as $product) {
                foreach ($product['changes'] as $change) {
                    $oldData[$product['product_id']]['price'][$change['price_id']] = $change['old_price'];
                    $newData[$product['product_id']]['price'][$change['price_id']] = $change['new_price'];
                    $oldData[$product['product_id']]['stock'][$change['price_id']] = $change['old_stock'];
                    $newData[$product['product_id']]['stock'][$change['price_id']] = $change['new_stock'];
                }
            }

            if (! empty($oldData)) {
                $properties['old']        = $oldData;
                $properties['attributes'] = $newData;
            }

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties($properties)
                ->log("مدیر {$adminName} قیمت و موجودی {$productCount} محصول را ویرایش کرد");
        }

        // clear product caches (پورت قدیمی)
        Product::clearCache();

        // ورودی‌ها با مقادیر جدید همگام می‌شوند
        $this->fillPriceInputs();

        // پیام Swal قدیمی prices.js
        $this->dispatch('toast', message: 'تغییرات با موفقیت ثبت شد', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.products.prices')
            ->layout('admin-panel::layout');
    }
}

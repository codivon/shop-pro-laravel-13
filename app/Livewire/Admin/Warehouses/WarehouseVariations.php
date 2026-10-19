<?php

namespace App\Livewire\Admin\Warehouses;

use App\Models\AttributeGroup;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

/**
 * تنوع‌های یک محصول در انبار — پورت ۱:۱ Back\WarehouseController
 * (productVariations + getVariationData + updateVariation + storeVariation + destroyVariation)
 *
 * همه تغییرات موجودی از StockMovementService قدیمی عبور می‌کنند (هیچ update مستقیم stock).
 * گیت ویرایش تنوع: getVariationData قدیمی «products.update» را چک می‌کرد — همان کلید روی
 * اکشن‌های تغییر تنوع هم اعمال شد (به‌صرفه‌سازی قرارداد «گیت در هر اکشن» — مستند در گزارش).
 */
#[Title('تنوع‌های محصول در انبار')]
class WarehouseVariations extends Component
{
    public $warehouse; // untyped — Livewire نمی‌تواند مدل تایپ‌شده را hydrate کند (الگوی همه کامپوننت‌های فازها)

    #[Url(as: 'product', except: '')]
    public $productId = null;

    /* ---------------- انتخابگر محصول ---------------- */

    #[Url(as: 'q', except: '')]
    public string $searchProduct = '';

    /* ---------------- مودال افزودن تنوع (پورت prices-template + storeVariation) ---------------- */

    public bool $creating = false;

    public $addWarehouse = null;
    public $addPrice = '';
    public $addDiscount = '';
    public $addDiscountExpireAt = '';
    public $addCartMax = null;
    public $addCartMin = null;
    public $addStock = '';
    public string $addPublished = '1';
    public $addSellerId = null;
    public array $addAttributes = [];

    /* ---------------- مودال ویرایش تنوع (پورت getVariationData + updateVariation) ---------------- */

    public ?int $editingPriceId = null;

    public $editWarehouse = null;
    public $editPrice = '';
    public $editDiscount = '';
    public $editDiscountExpireAt = '';
    public $editCartMax = null;
    public $editCartMin = null;
    public $editStock = '';
    public string $editPublished = '1';
    public $editSellerId = null;
    public array $editAttributes = [];

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(Warehouse $warehouse): void
    {
        auth('adminPanel')->user()?->can('warehouses.show') ?: abort(403);

        $this->warehouse = $warehouse;
        $this->addWarehouse = $warehouse->id;
        $this->editWarehouse = $warehouse->id;
    }

    public function updating(string $name, $value): void
    {
        if ($name === 'searchProduct') {
            $this->searchProduct = $value;
        }
    }

    #[Computed]
    public function product(): ?Product
    {
        if (! $this->productId) {
            return null;
        }

        return Product::where('id', $this->productId)->first();
    }

    /** لیست محصولات انبار برای انتخابگر (وقتی محصولی انتخاب نشده) */
    #[Computed]
    public function warehouseProducts()
    {
        return Product::whereHas('prices', fn ($q) => $q->where('warehouse_id', $this->warehouse->id))
            ->when($this->searchProduct !== '', fn ($q) => $q->where('title', 'like', '%'.$this->searchProduct.'%'))
            ->withCount(['prices as warehouse_prices_count' => fn ($q) => $q->where('warehouse_id', $this->warehouse->id)])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /* ================= پورت productVariations ================= */

    /** تنوع‌های انبار فعلی — مرتب بر اساس فروش (aggregate یک‌کوئری مثل بهینه‌سازی show قدیمی) */
    #[Computed]
    public function currentVariations()
    {
        $product = $this->product;
        if (! $product) {
            return collect();
        }

        $variations = $product->prices()
            ->where('warehouse_id', $this->warehouse->id)
            ->with('attributes')
            ->get();

        $soldCounts = OrderItem::whereIn('price_id', $variations->pluck('id'))
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->selectRaw('price_id, SUM(quantity) as total_sold')
            ->groupBy('price_id')
            ->pluck('total_sold', 'price_id');

        $variations->each(fn ($price) => $price->sold_count = $soldCounts[$price->id] ?? 0);

        return $variations->sortByDesc('sold_count')->values();
    }

    /** تنوع‌های همان فروشنده در سایر انبارهایش */
    #[Computed]
    public function otherSellerVariations()
    {
        $product = $this->product;
        if (! $product || ! $this->warehouse->seller_id) {
            return collect();
        }

        $result = collect();

        $otherWarehouses = Warehouse::where('seller_id', $this->warehouse->seller_id)
            ->where('id', '!=', $this->warehouse->id)
            ->get();

        foreach ($otherWarehouses as $wh) {
            $result = $result->merge($this->variationsFor($product, $wh, $wh->seller->business_name ?? null));
        }

        return $result;
    }

    /** تنوع‌های فروشگاه اصلی (انبارهای بدون فروشنده) */
    #[Computed]
    public function mainWarehouseVariations()
    {
        $product = $this->product;
        if (! $product) {
            return collect();
        }

        $result = collect();

        if ($this->warehouse->seller_id) {
            // شاخه فروشنده: تنوع‌های همه انبارهای بدون فروشنده
            $variations = $product->prices()
                ->whereDoesntHave('warehouse', function ($q) {
                    $q->whereNotNull('seller_id');
                })
                ->where('warehouse_id', '!=', $this->warehouse->id)
                ->with(['warehouse', 'attributes'])
                ->get()
                ->map(function ($price) {
                    $price->sold_count = $this->soldCount($price->id);
                    $price->warehouse_name = $price->warehouse->name ?? '—';
                    $price->warehouse_code = $price->warehouse->code ?? '';
                    $price->seller_name = 'فروشگاه اصلی';

                    return $price;
                });

            return $variations;
        }

        // شاخه اصلی: تنوع‌های هر انبار بدون فروشنده دیگر
        $otherMainWarehouses = Warehouse::whereNull('seller_id')
            ->where('id', '!=', $this->warehouse->id)
            ->get();

        foreach ($otherMainWarehouses as $wh) {
            $result = $result->merge($this->variationsFor($product, $wh, 'فروشگاه اصلی'));
        }

        return $result;
    }

    /** تنوع‌های انبار سایر فروشندگان */
    #[Computed]
    public function otherSellersVariations()
    {
        $product = $this->product;
        if (! $product || ! $this->warehouse->seller_id) {
            return collect();
        }

        return $product->prices()
            ->whereHas('warehouse', function ($q) {
                $q->whereNotNull('seller_id')
                    ->where('seller_id', '!=', $this->warehouse->seller_id);
            })
            ->where('warehouse_id', '!=', $this->warehouse->id)
            ->with(['warehouse.seller.seller_info', 'attributes'])
            ->get()
            ->map(function ($price) {
                $price->sold_count = $this->soldCount($price->id);
                $price->warehouse_name = $price->warehouse->name ?? '—';
                $price->warehouse_code = $price->warehouse->code ?? '';
                $price->seller_name = $this->sellerLabel($price->warehouse->seller) ?? 'فروشنده';

                return $price;
            });
    }

    protected function variationsFor(Product $product, Warehouse $wh, ?string $sellerLabel)
    {
        return $product->prices()
            ->where('warehouse_id', $wh->id)
            ->with('attributes')
            ->get()
            ->map(function ($price) use ($wh, $sellerLabel) {
                $price->sold_count = $this->soldCount($price->id);
                $price->warehouse_name = $wh->name;
                $price->warehouse_code = $wh->code;
                $price->seller_name = $sellerLabel ?? 'فروشگاه اصلی';

                return $price;
            });
    }

    protected function soldCount($priceId)
    {
        return OrderItem::where('price_id', $priceId)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->sum('quantity');
    }

    /** آمار — پورت دقیق productVariations قدیمی */
    #[Computed]
    public function stats(): array
    {
        $current = $this->currentVariations;
        $otherSeller = $this->otherSellerVariations;
        $main = $this->mainWarehouseVariations;
        $otherSellers = $this->otherSellersVariations;

        $totalSoldCurrent = $current->sum('sold_count');
        $bestSeller = $current->sortByDesc('sold_count')->first();

        return [
            'current_count' => $current->count(),
            'other_current_count' => $otherSeller->count(),
            'main_count' => $main->count(),
            'other_sellers_count' => $otherSellers->count(),
            'total_stock_current' => $current->sum('stock'),
            'total_stock_other_seller' => $otherSeller->sum('stock'),
            'total_stock_main' => $main->sum('stock'),
            'total_stock_other_sellers' => $otherSellers->sum('stock'),
            'total_sold_current' => $totalSoldCurrent,
            'best_seller_count' => $bestSeller ? $bestSeller->sold_count : 0,
            'best_seller_attributes' => $bestSeller ? $bestSeller->attributes->pluck('name')->implode(' - ') : 'ندارد',
            'avg_sold_per_variation' => $current->count() > 0 ? round($totalSoldCurrent / $current->count()) : 0,
        ];
    }

    /* ================= داده‌های فرم تنوع ================= */

    #[Computed]
    public function warehouses()
    {
        return Warehouse::active()->get();
    }

    #[Computed]
    public function attributeGroups()
    {
        return AttributeGroup::detectLang()->orderBy('ordering')->with('get_attributes')->get();
    }

    /** پورت کوئری getVariationData: فروشندگان تاییدشده — برچسب null-ایمن */
    #[Computed]
    public function sellers()
    {
        return Seller::with('seller_info')
            ->where(['status_register' => 'complete', 'status_documents' => 'Accept', 'status_work' => 'ACTIVE'])
            ->get()
            ->map(fn ($seller) => (object) [
                'id' => $seller->id,
                'label' => $seller->id.' - '.($this->sellerLabel($seller) ?? 'نامشخص'),
            ]);
    }

    /** برچسب null-ایمن فروشنده از sellers_info (الگوی فاز ۷ — اکسسور قدیمی روی ردیف بدون info کرش می‌کند) */
    protected function sellerLabel($seller): ?string
    {
        if (! $seller) {
            return null;
        }

        $info = $seller->seller_info;
        $name = $info->business_name ?? null;
        if (! $name) {
            $name = trim(($info->first_name ?? '').' '.($info->last_name ?? ''));
        }

        return $name !== '' ? $name : null;
    }

    public function isMultiVendor(): bool
    {
        return option('multi_vendor_system_status', 'false') == 'true';
    }

    /* ================= افزودن تنوع (پورت storeVariation) ================= */

    public function openCreate(): void
    {
        auth('adminPanel')->user()?->can('products.update') ?: abort(403);

        $this->reset([
            'addPrice', 'addDiscount', 'addDiscountExpireAt', 'addCartMax', 'addCartMin',
            'addStock', 'addSellerId', 'addAttributes',
        ]);
        $this->addWarehouse = $this->warehouse->id;
        $this->addPublished = '1';
        $this->creating = true;
    }

    public function storeVariation(): void
    {
        auth('adminPanel')->user()?->can('products.update') ?: abort(403);

        $product = $this->product;
        if (! $product) {
            return;
        }

        $this->validate([
            'addPrice' => 'required|numeric|min:0',
            'addStock' => 'required|integer|min:0',
            'addWarehouse' => 'required|exists:warehouses,id',
            'addDiscount' => 'nullable|integer|min:0|max:100',
            'addDiscountExpireAt' => 'nullable',
            'addCartMax' => 'nullable|integer|min:1',
            'addCartMin' => 'nullable|integer|min:1',
            'addPublished' => 'required|in:0,1',
            'addSellerId' => 'nullable|exists:sellers,id',
            'addAttributes' => 'nullable|array',
        ], [], [
            'addPrice' => 'قیمت',
            'addStock' => 'موجودی انبار',
            'addWarehouse' => 'انبار',
            'addDiscount' => 'تخفیف',
            'addDiscountExpireAt' => 'زمان انقضای تخفیف',
            'addCartMax' => 'بیشترین تعداد مجاز',
            'addCartMin' => 'کمترین تعداد مجاز',
            'addPublished' => 'وضعیت',
            'addSellerId' => 'فروشنده',
        ]);

        $adminName = auth('adminPanel')->user()->fullname ?? 'مدیر';
        $warehouseName = $this->warehouse->name ?? $this->warehouse->code ?? "#{$this->warehouse->id}";
        $productTitle = $product->title ?? $product->name ?? "#{$product->id}";

        try {
            $newPrice = DB::transaction(function () use ($product) {
                $attributes = $this->normalizeAttributes($this->addAttributes);

                $regularPrice = get_discount_price($this->addPrice, 0, $product);
                $discountPrice = get_discount_price($this->addPrice, $this->addDiscount ?? 0, $product);

                $discountExpireAt = $this->parseDiscountExpire($this->addDiscountExpireAt);

                $priceData = [
                    'product_id' => $product->id,
                    'warehouse_id' => $this->addWarehouse,
                    'price' => $this->addPrice,
                    'discount' => $this->addDiscount ?? 0,
                    'regular_price' => $regularPrice,
                    'discount_price' => $discountPrice,
                    'stock' => 0,
                    'cart_max' => $this->addCartMax,
                    'cart_min' => $this->addCartMin,
                    'published' => $this->addPublished,
                    'discount_expire_at' => $discountExpireAt,
                    'seller_id' => $this->addSellerId,
                ];

                $newPrice = Price::create($priceData);

                if (! empty($attributes)) {
                    foreach ($attributes as $attributeId) {
                        DB::table('attribute_price')->insert([
                            'attribute_id' => $attributeId,
                            'price_id' => $newPrice->id,
                            'seller_id' => $this->addSellerId,
                            'product_id' => $product->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($this->addStock > 0) {
                    app(StockMovementService::class)->inbound(
                        $newPrice,
                        (int) $this->addStock,
                        'ایجاد تنوع جدید - موجودی اولیه',
                        'variation_creation'
                    );
                }

                $newPrice->createChange($this->addPrice, $this->addDiscount ?? 0, $this->addStock);

                return $newPrice;
            });

            $newPrice->load('attributes');

            $selectedWarehouse = Warehouse::find($this->addWarehouse);
            $warehouseDisplay = $selectedWarehouse ? ($selectedWarehouse->name.' ('.$selectedWarehouse->code.')') : 'نامشخص';

            $sellerDisplay = 'مدیر سایت';
            if ($this->addSellerId) {
                $seller = Seller::with('seller_info')->find($this->addSellerId);
                if ($seller) {
                    $sellerDisplay = $this->sellerLabel($seller) ?? 'نامشخص';
                }
            }

            $discountExpireDisplay = 'ندارد';
            if ($this->addDiscountExpireAt) {
                try {
                    $discountExpireDisplay = Jalalian::fromFormat('Y-m-d H:i:s', $this->addDiscountExpireAt)->format('Y/m/d');
                } catch (\Exception $e) {
                }
            }

            $attributesNames = $newPrice->attributes->pluck('name')->implode('، ');
            $statusText = $this->addPublished ? 'فعال' : 'غیرفعال';

            $newData = [
                'قیمت' => number_format($this->addPrice).' تومان',
                'موجودی اولیه' => $this->addStock.' عدد',
                'وضعیت' => $statusText,
                'انبار' => $warehouseDisplay,
                'حداقل تعداد سفارش' => $this->addCartMin ?? '1',
                'حداکثر تعداد سفارش' => $this->addCartMax ?? 'نامحدود',
                'فروشنده' => $sellerDisplay,
            ];

            if ($this->addDiscount && $this->addDiscount > 0) {
                $newData['تخفیف'] = $this->addDiscount.'%';
                $newData['تاریخ انقضای تخفیف'] = $discountExpireDisplay;
            }

            if ($attributesNames) {
                $newData['ویژگی‌ها'] = $attributesNames;
            }

            activity()
                ->performedOn($newPrice)
                ->causedBy(auth('adminPanel')->user())
                ->event('created')
                ->withProperties([
                    'action' => 'ایجاد تنوع جدید',
                    'product_title' => $productTitle,
                    'product_id' => $product->id,
                    'warehouse_name' => $warehouseName,
                    'warehouse_id' => $this->warehouse->id,
                    'price_id' => $newPrice->id,
                    'attributes' => $newData,
                    'ip' => request()->ip(),
                ])
                ->log("مدیر {$adminName} تنوع جدیدی برای محصول «{$productTitle}» در انبار «{$warehouseName}» ایجاد کرد");

            $this->creating = false;
            $this->reset(['addPrice', 'addDiscount', 'addDiscountExpireAt', 'addCartMax', 'addCartMin', 'addStock', 'addSellerId', 'addAttributes']);

            $this->dispatch('toast', message: 'تنوع جدید با موفقیت اضافه شد.', type: 'success');
        } catch (\Exception $e) {
            \Log::error("Error creating variation for product {$product->id}: ".$e->getMessage());

            activity()
                ->performedOn($product)
                ->causedBy(auth('adminPanel')->user())
                ->event('created')
                ->withProperties([
                    'action' => 'create_variation_failed',
                    'product_title' => $productTitle,
                    'product_id' => $product->id,
                    'warehouse_name' => $warehouseName,
                    'error' => $e->getMessage(),
                    'ip' => request()->ip(),
                ])
                ->log("مدیر {$adminName} ایجاد تنوع جدید برای محصول «{$productTitle}» در انبار «{$warehouseName}» با خطا مواجه شد");

            $this->dispatch('toast', message: 'خطا در ایجاد تنوع: '.$e->getMessage(), type: 'error');
        }
    }

    /* ================= ویرایش تنوع (پورت getVariationData + updateVariation) ================= */

    public function editVariation(int $priceId): void
    {
        auth('adminPanel')->user()?->can('products.update') ?: abort(403);

        $price = Price::with('attributes')->findOrFail($priceId);

        $this->editingPriceId = $price->id;
        $this->editWarehouse = $price->warehouse_id;
        $this->editPrice = $price->price;
        $this->editDiscount = $price->discount;
        $this->editDiscountExpireAt = '';
        $this->editCartMax = $price->cart_max;
        $this->editCartMin = $price->cart_min;
        $this->editStock = $price->stock;
        $this->editPublished = $price->published ? '1' : '0';
        $this->editSellerId = $price->seller_id;
        $this->editAttributes = [];
    }

    /** پورت getVariationData — پر کردن ویرایشگر اتریبیوت‌ها (message دقیق قدیمی در toast) */
    public function loadVariationData(int $priceId): void
    {
        auth('adminPanel')->user()?->can('products.update') ?: abort(403);

        $this->editVariation($priceId);

        $price = Price::findOrFail($priceId);

        // prefill اتریبیوت‌ها بر اساس گروه‌ها (مثل select های prices-include قدیمی)
        $prefill = [];
        foreach ($this->attributeGroups as $group) {
            $selected = $price->get_attributes()->where('attribute_group_id', $group->id)->first();
            $prefill[$group->id] = $selected?->id;
        }
        $this->editAttributes = $prefill;

        // تاریخ انقضای تخفیف با فرمت ورودی پنل (Y-m-d H:i:s)
        if ($price->discount_expire_at) {
            $this->editDiscountExpireAt = Jalalian::fromDateTime($price->discount_expire_at)->format('Y-m-d H:i:s');
        }

        $this->dispatch('toast', message: 'تنوع با موفقیت بارگذاری شد.', type: 'success');
    }

    public function updateVariation(): void
    {
        auth('adminPanel')->user()?->can('products.update') ?: abort(403);

        $product = $this->product;
        $price = $this->editingPriceId ? Price::with('attributes')->find($this->editingPriceId) : null;

        if (! $product || ! $price) {
            return;
        }

        $warehouse = $this->warehouse;

        $this->validate([
            'editPrice' => 'required|numeric|min:0',
            'editStock' => 'required|integer|min:0',
            'editWarehouse' => 'required|exists:warehouses,id',
            'editDiscount' => 'nullable|integer|min:0|max:100',
            'editDiscountExpireAt' => 'nullable',
            'editCartMax' => 'nullable|integer|min:1',
            'editCartMin' => 'nullable|integer|min:1',
            'editPublished' => 'required|in:0,1',
            'editSellerId' => 'nullable|exists:sellers,id',
            'editAttributes' => 'nullable|array',
        ], [], [
            'editPrice' => 'قیمت',
            'editStock' => 'موجودی انبار',
            'editWarehouse' => 'انبار',
            'editDiscount' => 'تخفیف',
            'editDiscountExpireAt' => 'زمان انقضای تخفیف',
            'editCartMax' => 'بیشترین تعداد مجاز',
            'editCartMin' => 'کمترین تعداد مجاز',
            'editPublished' => 'وضعیت',
            'editSellerId' => 'فروشنده',
        ]);

        $adminName = auth('adminPanel')->user()->fullname ?? 'مدیر';
        $warehouseName = $warehouse->name ?? $warehouse->code ?? "#{$warehouse->id}";
        $productTitle = $product->title ?? $product->name ?? "#{$product->id}";

        // مقادیر قدیمی (پورت دقیق updateVariation)
        $oldAttributesNames = $price->attributes->pluck('name')->implode('، ');

        $oldSellerName = null;
        if ($price->seller_id) {
            $oldSeller = Seller::with('seller_info')->find($price->seller_id);
            $oldSellerName = $this->sellerLabel($oldSeller) ?? 'نامشخص';
        }

        $oldWarehouseName = null;
        if ($price->warehouse_id) {
            $oldWarehouse = Warehouse::find($price->warehouse_id);
            $oldWarehouseName = $oldWarehouse->name ?? $oldWarehouse->code ?? 'نامشخص';
        }

        $oldDiscountExpireAt = null;
        if ($price->discount_expire_at) {
            $oldDiscountExpireAt = Jalalian::fromDateTime($price->discount_expire_at)->format('Y/m/d');
        }

        $oldValues = [
            'price' => $price->price,
            'stock' => $price->stock,
            'discount' => $price->discount,
            'published' => $price->published ? 'فعال' : 'غیرفعال',
            'cart_max' => $price->cart_max,
            'cart_min' => $price->cart_min,
            'seller_id' => $oldSellerName,
            'warehouse_id' => $oldWarehouseName,
            'discount_expire_at' => $oldDiscountExpireAt,
            'attributes' => $oldAttributesNames ?: 'بدون ویژگی',
        ];

        try {
            DB::transaction(function () use ($price, $product) {
                $oldStock = $price->stock;
                $newStock = (int) $this->editStock;

                if ($oldStock != $newStock) {
                    app(StockMovementService::class)->adjustment(
                        $price,
                        $newStock,
                        "ویرایش مستقیم تنوع - تغییر موجودی از {$oldStock} به {$newStock}"
                    );
                }

                $regularPrice = get_discount_price($this->editPrice, 0, $product);
                $discountPrice = get_discount_price($this->editPrice, $this->editDiscount ?? 0, $product);

                $discountExpireAt = $this->parseDiscountExpire($this->editDiscountExpireAt);

                $price->update([
                    'price' => $this->editPrice,
                    'discount' => $this->editDiscount ?? 0,
                    'regular_price' => $regularPrice,
                    'discount_price' => $discountPrice,
                    'stock' => $newStock,
                    'cart_max' => $this->editCartMax,
                    'cart_min' => $this->editCartMin,
                    'published' => $this->editPublished ?? true,
                    'discount_expire_at' => $discountExpireAt,
                    'seller_id' => $this->editSellerId,
                    'warehouse_id' => $this->editWarehouse,
                ]);

                // پورت دقیق sync اتریبیوت‌ها (diff روی attribute_price)
                $attributes = $this->normalizeAttributes($this->editAttributes);

                if (! empty($attributes)) {
                    $currentAttributes = DB::table('attribute_price')
                        ->where('price_id', $price->id)
                        ->pluck('attribute_id')
                        ->toArray();

                    $attributesToAdd = array_diff($attributes, $currentAttributes);
                    $attributesToRemove = array_diff($currentAttributes, $attributes);

                    if (! empty($attributesToRemove)) {
                        DB::table('attribute_price')
                            ->where('price_id', $price->id)
                            ->whereIn('attribute_id', $attributesToRemove)
                            ->delete();
                    }

                    foreach ($attributesToAdd as $attributeId) {
                        DB::table('attribute_price')->insert([
                            'attribute_id' => $attributeId,
                            'price_id' => $price->id,
                            'seller_id' => $this->editSellerId,
                            'product_id' => $product->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $price->createChange($this->editPrice, $this->editDiscount ?? 0, $newStock);
            });

            $price->refresh();
            $price->load('attributes');

            $newAttributesNames = $price->attributes->pluck('name')->implode('، ');
            $newPublished = $this->editPublished ? 'فعال' : 'غیرفعال';

            $newSellerName = null;
            if ($this->editSellerId) {
                $newSeller = Seller::with('seller_info')->find($this->editSellerId);
                $newSellerName = $this->sellerLabel($newSeller) ?? 'نامشخص';
            }

            $newWarehouse = Warehouse::find($this->editWarehouse);
            $newWarehouseName = $newWarehouse->name ?? $newWarehouse->code ?? 'نامشخص';

            $newDiscountExpireAt = null;
            if ($this->editDiscountExpireAt) {
                try {
                    $newDiscountExpireAt = Jalalian::fromFormat('Y-m-d H:i:s', $this->editDiscountExpireAt)->format('Y/m/d');
                } catch (\Exception $e) {
                }
            }

            // ساخت تغییرات old/attributes (پورت دقیق)
            $oldData = [];
            $newData = [];

            if ($oldValues['price'] != $this->editPrice) {
                $oldData['قیمت'] = number_format($oldValues['price']).' تومان';
                $newData['قیمت'] = number_format($this->editPrice).' تومان';
            }
            if ($oldValues['stock'] != $this->editStock) {
                $oldData['موجودی'] = $oldValues['stock'];
                $newData['موجودی'] = $this->editStock;
            }
            if ($oldValues['discount'] != ($this->editDiscount ?? 0)) {
                $oldData['تخفیف'] = $oldValues['discount'].'%';
                $newData['تخفیف'] = ($this->editDiscount ?? 0).'%';
            }
            if ($oldValues['published'] != $newPublished) {
                $oldData['وضعیت'] = $oldValues['published'];
                $newData['وضعیت'] = $newPublished;
            }
            if ($oldValues['cart_max'] != $this->editCartMax) {
                $oldData['حداکثر تعداد سفارش'] = $oldValues['cart_max'] ?? 'نامحدود';
                $newData['حداکثر تعداد سفارش'] = $this->editCartMax ?? 'نامحدود';
            }
            if ($oldValues['cart_min'] != $this->editCartMin) {
                $oldData['حداقل تعداد سفارش'] = $oldValues['cart_min'] ?? '1';
                $newData['حداقل تعداد سفارش'] = $this->editCartMin ?? '1';
            }
            if ($oldValues['seller_id'] != $newSellerName) {
                $oldData['فروشنده'] = $oldValues['seller_id'] ?? 'مدیر سایت';
                $newData['فروشنده'] = $newSellerName ?? 'مدیر سایت';
            }
            if ($oldValues['warehouse_id'] != $newWarehouseName) {
                $oldData['انبار'] = $oldValues['warehouse_id'];
                $newData['انبار'] = $newWarehouseName;
            }
            if ($oldValues['discount_expire_at'] != $newDiscountExpireAt) {
                $oldData['تاریخ انقضای تخفیف'] = $oldValues['discount_expire_at'] ?? 'ندارد';
                $newData['تاریخ انقضای تخفیف'] = $newDiscountExpireAt ?? 'ندارد';
            }
            if ($oldValues['attributes'] != ($newAttributesNames ?: 'بدون ویژگی')) {
                $oldData['ویژگی‌ها'] = $oldValues['attributes'];
                $newData['ویژگی‌ها'] = $newAttributesNames ?: 'بدون ویژگی';
            }

            if (! empty($oldData)) {
                activity()
                    ->performedOn($price)
                    ->causedBy(auth('adminPanel')->user())
                    ->event('updated')
                    ->withProperties([
                        'action' => 'تنوع محصول را ویرایش کرد',
                        'product_title' => $productTitle,
                        'product_id' => $product->id,
                        'warehouse_name' => $warehouseName,
                        'warehouse_id' => $warehouse->id,
                        'price_id' => $price->id,
                        'old' => $oldData,
                        'attributes' => $newData,
                        'ip' => request()->ip(),
                    ])
                    ->log("مدیر {$adminName} تنوع محصول «{$productTitle}» را در انبار «{$warehouseName}» ویرایش کرد");
            }

            $this->editingPriceId = null;
            $this->reset(['editPrice', 'editDiscount', 'editDiscountExpireAt', 'editCartMax', 'editCartMin', 'editSellerId', 'editAttributes']);
            $this->editWarehouse = $this->warehouse->id;

            $this->dispatch('toast', message: 'تنوع با موفقیت ویرایش شد.', type: 'success');
        } catch (\Exception $e) {
            \Log::error("Error updating variation {$price->id}: ".$e->getMessage());

            activity()
                ->performedOn($price)
                ->causedBy(auth('adminPanel')->user())
                ->event('updated')
                ->withProperties([
                    'action' => 'update_variation_failed',
                    'product_title' => $productTitle,
                    'product_id' => $product->id,
                    'warehouse_name' => $warehouseName,
                    'price_id' => $price->id,
                    'error' => $e->getMessage(),
                    'ip' => request()->ip(),
                ])
                ->log("مدیر {$adminName} ویرایش تنوع محصول «{$productTitle}» در انبار «{$warehouseName}» با خطا مواجه شد");

            $this->dispatch('toast', message: 'خطا در ویرایش تنوع: '.$e->getMessage(), type: 'error');
        }
    }

    /* ================= حذف تنوع (پورت destroyVariation) ================= */

    public function confirmDeleteVariation(int $priceId): void
    {
        auth('adminPanel')->user()?->can('products.update') ?: abort(403);

        $this->confirmingDelete = $priceId;
    }

    public function destroyVariation(): void
    {
        auth('adminPanel')->user()?->can('products.update') ?: abort(403);

        $product = $this->product;
        $price = $this->confirmingDelete ? Price::with('attributes')->find($this->confirmingDelete) : null;

        if (! $product || ! $price) {
            $this->confirmingDelete = null;

            return;
        }

        $warehouse = $this->warehouse;

        $adminName = auth('adminPanel')->user()->fullname ?? 'مدیر';
        $warehouseName = $warehouse->name ?? $warehouse->code ?? "#{$warehouse->id}";
        $productTitle = $product->title ?? $product->name ?? "#{$product->id}";

        $variationPrice = $price->price;
        $variationStock = $price->stock;
        $attributesNames = $price->attributes->pluck('name')->implode('، ');

        try {
            DB::transaction(function () use ($price) {
                $hasActiveOrders = OrderItem::where('price_id', $price->id)
                    ->whereHas('order', fn ($q) => $q->whereIn('status', ['unpaid', 'pending', 'processing']))
                    ->exists();

                if ($hasActiveOrders) {
                    throw new \Exception('این تنوع دارای سفارش‌های فعال است و قابل حذف نمی‌باشد.');
                }

                if ($price->stock > 0) {
                    app(StockMovementService::class)->outbound(
                        $price,
                        $price->stock,
                        null,
                        null,
                        "حذف تنوع - خروج کل موجودی ({$price->stock} عدد) از انبار"
                    );
                }

                DB::table('attribute_price')->where('price_id', $price->id)->delete();
                DB::table('cart_product')->where('price_id', $price->id)->delete();
                $price->forceDelete();
            });

            // ساخت اطلاعات تنوع حذف‌شده (پورت دقیق)
            $oldData = [
                'قیمت' => number_format($variationPrice).' تومان',
                'موجودی' => $variationStock.' عدد',
            ];
            if ($attributesNames) {
                $oldData['ویژگی‌ها'] = $attributesNames;
            }

            activity()
                ->performedOn($product)
                ->causedBy(auth('adminPanel')->user())
                ->event('deleted')
                ->withProperties([
                    'action' => 'تنوع محصول را حذف کرد',
                    'product_title' => $productTitle,
                    'product_id' => $product->id,
                    'warehouse_name' => $warehouseName,
                    'warehouse_id' => $warehouse->id,
                    'price_id' => $price->id,
                    'old' => $oldData,
                    'ip' => request()->ip(),
                ])
                ->log("مدیر {$adminName} تنوع محصول «{$productTitle}» را در انبار «{$warehouseName}» حذف کرد");

            $this->confirmingDelete = null;

            $this->dispatch('toast', message: 'تنوع با موفقیت حذف شد.', type: 'success');
        } catch (\Exception $e) {
            \Log::error("Error deleting variation {$price->id}: ".$e->getMessage());

            activity()
                ->performedOn($product)
                ->causedBy(auth('adminPanel')->user())
                ->event('deleted')
                ->withProperties([
                    'action' => 'delete_variation_failed',
                    'product_title' => $productTitle,
                    'product_id' => $product->id,
                    'warehouse_name' => $warehouseName,
                    'price_id' => $price->id,
                    'error' => $e->getMessage(),
                    'ip' => request()->ip(),
                ])
                ->log("مدیر {$adminName} حذف تنوع محصول «{$productTitle}» در انبار «{$warehouseName}» با خطا مواجه شد");

            $this->confirmingDelete = null;

            $this->dispatch('toast', message: 'خطا در حذف تنوع: '.$e->getMessage(), type: 'error');
        }
    }

    /* ================= ابزار ================= */

    protected function normalizeAttributes($attributes): array
    {
        if (! is_array($attributes)) {
            return [];
        }

        // پورت دقیق فیلتر قدیمی: حذف null / رشته خالی / رشته 'null'
        return array_values(array_filter($attributes, function ($attr) {
            return ! is_null($attr) && $attr !== '' && $attr !== 'null';
        }));
    }

    protected function parseDiscountExpire($value)
    {
        if (! $value) {
            return null;
        }

        try {
            return Jalalian::fromFormat('Y-m-d H:i:s', $value)->toCarbon();
        } catch (\Exception $e) {
            return null;
        }
    }

    /** آمار پایین جدول — پورت calculateStats قدیمی */
    public function calculateStats($allPrices): array
    {
        return [
            'current_count' => $allPrices->count(),
            'total_stock_current' => $allPrices->sum('stock'),
            'total_sold' => $allPrices->sum('sold_count'),
            'total_reserved' => $allPrices->sum('reserved_stock'),
            'low_stock' => $allPrices->where('stock', '<=', 5)->where('stock', '>', 0)->count(),
            'out_of_stock' => $allPrices->where('stock', 0)->count(),
            'critical_stock' => $allPrices->where('stock', '<=', 2)->where('stock', '>', 0)->count(),
            'total_sold_current' => $allPrices->sum('sold_count'),
            'total_value' => $allPrices->sum(fn ($p) => $p->stock * ($p->cost_price ?? $p->price)),
        ];
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.warehouses.variations')
            ->layout('admin-panel::layout');
    }
}

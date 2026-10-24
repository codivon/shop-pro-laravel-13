<?php

namespace App\Services\AdminPanel;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Label;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductAbilityScore;
use App\Models\SellerVariant;
use App\Models\Specification;
use App\Models\SpecificationGroup;
use App\Services\StockMovementService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\Jalalian;

/**
 * منطق ذخیره‌سازی محصولات پنل مدیریت جدید (Livewire)
 *
 * پورت کامل متدهای خصوصی ProductController قدیمی — هیچ رفتاری تغییر نکرده است:
 * updateProductPrices / updateProductFiles / updateProductSpecifications /
 * updateProductBrand / updateProductImages / updateProductCategories /
 * updateProductLabels / updateProductSizes / updateProductAbilityScore / destroy
 *
 * ورودی‌ها آرایه‌های ساده‌اند (به‌جای Request) تا از Livewire قابل استفاده باشند.
 * فرمت آرایه‌ها دقیقاً مطابق فرمت فرم قدیمی است (prices[i][price] و ...).
 */
class ProductPersistence
{
    public function __construct(protected StockMovementService $stockService)
    {
    }

    /* ==================================================================
     |  تنوع‌ها / قیمت‌ها (محصولات فیزیکی)
     ================================================================== */

    public function updateProductPrices(Product $product, array $prices): void
    {
        if ($product->isDownload()) {
            return;
        }

        if (empty($prices) || ! is_array($prices)) {
            Log::warning('No prices data in request for product: ' . $product->id);

            return;
        }

        $pricesToKeep = [];
        $priceChangesLog = [];

        foreach ($prices as $index => $priceData) {

            // اعتبارسنجی
            if (! isset($priceData['price']) || ! isset($priceData['stock'])) {
                continue;
            }

            // پردازش تاریخ انقضا
            $discountExpireAt = null;
            if (! empty($priceData['discount_expire_at'])) {
                try {
                    $discountExpireAt = Jalalian::fromFormat('Y-m-d H:i:s', $priceData['discount_expire_at'])->toCarbon();
                } catch (\Exception $e) {
                    // خطای تاریخ را نادیده بگیر (مطابق پنل قدیمی)
                }
            }

            // پردازش ویژگی‌ها
            $attributes = $this->extractAttributes($priceData['attributes'] ?? []);
            $attributes = $this->validateAndFixDuplicateColors($attributes, $index);

            // اطلاعات پایه
            $sellerId = $priceData['seller_id'] ?? null;
            $warehouseId = $priceData['warehouse'] ?? null;
            $newStock = (int) $priceData['stock'];

            // جستجوی قیمت موجود
            $currentPriceId = $priceData['id'] ?? null;

            $existingPrice = $this->findExistingPrice(
                $product,
                $attributes,
                $sellerId,
                $warehouseId,
                $currentPriceId
            );

            // محاسبات قیمت (قیمت‌ها تومان صحیح؛ گرد کردن طبق تنظیمات محصول)
            $regularPrice = get_discount_price($priceData['price'], 0, $product);
            $discountPrice = get_discount_price($priceData['price'], $priceData['discount'] ?? 0, $product);

            $priceDataForSave = [
                'price' => $priceData['price'],
                'discount' => $priceData['discount'] ?? 0,
                'regular_price' => $regularPrice,
                'discount_price' => $discountPrice,
                'stock' => $newStock,
                'cart_max' => $priceData['cart_max'] ?? null,
                'cart_min' => $priceData['cart_min'] ?? null,
                'published' => isset($priceData['published']) ? (bool) $priceData['published'] : true,
                'discount_expire_at' => $discountExpireAt,
                'seller_id' => $sellerId,
                'warehouse_id' => $warehouseId,
            ];

            if ($existingPrice) {
                // ========== به‌روزرسانی تنوع موجود ==========

                $priceChange = [];
                if ($existingPrice->price != $priceData['price']) {
                    $priceChange['price'] = ['old' => $existingPrice->price, 'new' => $priceData['price']];
                }
                if ($existingPrice->stock != $newStock) {
                    $priceChange['stock'] = ['old' => $existingPrice->stock, 'new' => $newStock];
                }
                if (($existingPrice->discount ?? 0) != ($priceData['discount'] ?? 0)) {
                    $priceChange['discount'] = ['old' => $existingPrice->discount ?? 0, 'new' => $priceData['discount'] ?? 0];
                }

                if (! empty($priceChange)) {
                    $attributesNames = $this->attributesNames($attributes);
                    $priceChangesLog[] = [
                        'attributes' => $attributesNames ?: 'بدون ویژگی',
                        'changes' => $priceChange,
                    ];
                }

                $this->syncAttributesToPrice($existingPrice, $attributes, $sellerId, $product->id);

                $oldStock = $existingPrice->stock;
                $stockDifference = $newStock - $oldStock;

                // ثبت حرکت انبار برای تغییر موجودی
                if ($stockDifference != 0) {
                    $this->stockService->adjustment(
                        $existingPrice,
                        $newStock,
                        "ویرایش محصول - تغییر موجودی از {$oldStock} به {$newStock}"
                    );
                }

                // ثبت PriceChange در صورت تغییر قیمت/موجودی
                if ($existingPrice->price != $priceData['price'] || $existingPrice->stock != $newStock) {
                    $existingPrice->createChange(
                        $priceData['price'],
                        $priceData['discount'] ?? 0,
                        $newStock
                    );
                }

                $existingPrice->update($priceDataForSave);

                $pricesToKeep[] = $existingPrice->id;
            } else {
                // ========== ایجاد تنوع جدید ==========

                $attributesNames = $this->attributesNames($attributes);
                $priceChangesLog[] = [
                    'attributes' => $attributesNames ?: 'بدون ویژگی',
                    'changes' => [
                        'price' => ['old' => 'ندارد', 'new' => $priceData['price']],
                        'stock' => ['old' => 'ندارد', 'new' => $newStock],
                    ],
                ];

                $priceDataForSave['product_id'] = $product->id;
                $newPrice = Price::create($priceDataForSave);

                // ثبت حرکت انبار برای ایجاد تنوع جدید
                if ($newStock > 0) {
                    $newPrice->update(['stock' => 0]);
                    $this->stockService->inbound(
                        $newPrice,
                        $newStock,
                        'ایجاد تنوع جدید برای محصول - موجودی اولیه',
                        'product_variant_creation'
                    );
                }

                $newPrice->createChange(
                    $priceData['price'],
                    $priceData['discount'] ?? 0,
                    $newStock
                );

                $this->syncAttributesToPrice($newPrice, $attributes, $sellerId, $product->id);
                $pricesToKeep[] = $newPrice->id;
            }
        }

        // حذف قیمت‌های اضافی
        $this->deleteExtraPrices($product, $pricesToKeep);

        // به‌روزرسانی seller_variants
        $this->updateSellerVariants($product);

        // پاکسازی سبد خرید
        DB::table('cart_product')
            ->where('product_id', $product->id)
            ->whereNotNull('price_id')
            ->whereNotIn('price_id', $pricesToKeep)
            ->delete();
    }

    /**
     * استخراج ویژگی‌ها از فرمت‌های مختلف (آرایه‌ای یا کلیددار) + مرتب‌سازی
     */
    public function extractAttributes($attributesData): array
    {
        if (! is_array($attributesData)) {
            return [];
        }

        $isAssociative = array_keys($attributesData) !== range(0, count($attributesData) - 1);
        if ($isAssociative) {
            $attributes = array_values(array_filter($attributesData, fn ($value) => ! empty($value)));
        } else {
            $attributes = array_filter($attributesData, fn ($value) => ! empty($value));
        }

        sort($attributes);

        return $attributes;
    }

    /**
     * اعتبارسنجی و رفع رنگ تکراری (فقط اولین رنگ هر گروه رنگ نگه داشته می‌شود)
     */
    public function validateAndFixDuplicateColors(array $attributes, $index): array
    {
        $colorAttributeIds = [];

        foreach ($attributes as $attributeId) {
            $attribute = Attribute::find($attributeId);
            if ($attribute && $attribute->group && $attribute->group->type == 'color') {
                $colorAttributeIds[] = $attributeId;
            }
        }

        if (count($colorAttributeIds) > 1) {
            Log::error("Price {$index} has multiple colors: " . implode(', ', $colorAttributeIds));
            $firstColor = $colorAttributeIds[0];
            $attributes = array_values(array_diff($attributes, array_slice($colorAttributeIds, 1)));
            Log::info("Fixed: Kept only color {$firstColor}");
        }

        return $attributes;
    }

    /**
     * جستجوی قیمت موجود: اول با id، سپس با ترکیب (seller + warehouse + ویژگی‌های دقیق)
     */
    public function findExistingPrice($product, array $attributes, $sellerId, $warehouseId = null, $currentPriceId = null): ?Price
    {
        // اولویت اول: اگر currentPriceId دارد، همان را برگردان
        if ($currentPriceId && ! empty($currentPriceId)) {
            $price = Price::where('id', $currentPriceId)
                ->where('product_id', $product->id)
                ->first();

            if ($price) {
                return $price;
            }
        }

        // دوم: جستجو بر اساس ترکیب ویژگی‌ها
        $priceQuery = Price::where('product_id', $product->id);

        if ($warehouseId) {
            $priceQuery->where('warehouse_id', $warehouseId);
        }

        if ($sellerId) {
            $priceQuery->where('seller_id', $sellerId);
        } else {
            $priceQuery->whereNull('seller_id');
        }

        $existingPrices = $priceQuery->get();

        $sortedNewAttributes = collect($attributes)->sort()->values()->toArray();

        foreach ($existingPrices as $price) {
            $priceAttributes = DB::table('attribute_price')
                ->where('price_id', $price->id)
                ->pluck('attribute_id')
                ->sort()
                ->values()
                ->toArray();

            if ($priceAttributes == $sortedNewAttributes) {
                return $price;
            }
        }

        return null;
    }

    /**
     * همگام‌سازی ویژگی‌های قیمت (delete + insert)
     */
    public function syncAttributesToPrice($price, array $attributes, $sellerId, $productId): void
    {
        DB::table('attribute_price')
            ->where('price_id', $price->id)
            ->delete();

        foreach ($attributes as $attributeId) {
            DB::table('attribute_price')->insert([
                'attribute_id' => $attributeId,
                'price_id' => $price->id,
                'seller_id' => $sellerId,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * حذف قیمت‌های اضافی (forceDelete + خروج انبار + پاکسازی attribute_price)
     */
    public function deleteExtraPrices(Product $product, array $pricesToKeep): void
    {
        $deletedPrices = Price::where('product_id', $product->id)
            ->whereNotIn('id', $pricesToKeep)
            ->get();

        foreach ($deletedPrices as $deletedPrice) {
            if ($deletedPrice->stock > 0) {
                $this->stockService->outbound(
                    $deletedPrice,
                    $deletedPrice->stock,
                    null,
                    null,
                    "حذف تنوع - خروج کل موجودی ({$deletedPrice->stock} عدد) از انبار"
                );
            }

            DB::table('attribute_price')->where('price_id', $deletedPrice->id)->delete();
            $deletedPrice->forceDelete();
        }
    }

    /**
     * بازسازی جدول seller_variants برای همه فروشنده‌ها + گروه null (مدیر)
     */
    public function updateSellerVariants(Product $product): void
    {
        DB::table('seller_variants')->where('product_id', $product->id)->delete();

        $sellerIds = Price::where('product_id', $product->id)
            ->whereNotNull('seller_id')
            ->distinct()
            ->pluck('seller_id');

        foreach ($sellerIds as $sellerId) {
            $priceIds = Price::where('product_id', $product->id)
                ->where('seller_id', $sellerId)
                ->pluck('id')
                ->toArray();

            if (! empty($priceIds)) {
                DB::table('seller_variants')->insert([
                    'product_id' => $product->id,
                    'seller_id' => $sellerId,
                    'prices_id' => json_encode($priceIds),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $nullSellerPriceIds = Price::where('product_id', $product->id)
            ->whereNull('seller_id')
            ->pluck('id')
            ->toArray();

        if (! empty($nullSellerPriceIds)) {
            DB::table('seller_variants')->insert([
                'product_id' => $product->id,
                'seller_id' => null,
                'prices_id' => json_encode($nullSellerPriceIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function attributesNames(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $attrs = Attribute::whereIn('id', $attributes)->pluck('name')->toArray();

        return implode(' - ', $attrs);
    }

    /* ==================================================================
     |  فایل‌های دانلودی (محصولات دانلود)
     ================================================================== */

    public function updateProductFiles(Product $product, array $downloadFiles): void
    {
        if ($product->isPhysical()) {
            return;
        }

        $prices_id = [];
        $ordering = 1;

        foreach ($downloadFiles as $price) {

            $update_price = false;

            if (! empty($price['price_id'])) {
                $update_price = $product->prices()->withTrashed()->where('prices.id', $price['price_id'])->first();
            }

            if ($update_price) {

                $update_price->createChange(
                    $price['price'],
                    $price['discount']
                );

                $update_price->update([
                    'price' => $price['price'],
                    'discount' => $price['discount'],
                    'discount_price' => get_discount_price($price['price'], $price['discount'], $product),
                    'regular_price' => get_discount_price($price['price'], 0, $product),
                    'deleted_at' => null,
                    'ordering' => $ordering++,
                ]);

                $update_price->updateFile($price['title'], $price['file'] ?? null, $price['status']);

                $prices_id[] = $update_price->id;
            } else {
                $insert_price = $product->prices()->create([
                    'price' => $price['price'],
                    'discount' => $price['discount'],
                    'discount_price' => get_discount_price($price['price'], $price['discount'], $product),
                    'regular_price' => get_discount_price($price['price'], 0, $product),
                    'ordering' => $ordering++,
                ]);

                $insert_price->createFile($price['title'], $price['file'], $price['status']);

                $insert_price->createChange($price['price'], $price['discount']);

                $prices_id[] = $insert_price->id;
            }
        }

        $delete_prices = $product->prices()->whereNotIn('id', $prices_id)->get();

        foreach ($delete_prices as $delete_price) {
            $file = $delete_price->file;

            if ($file) {
                Storage::disk('downloads')->delete('product-files/' . $file->file);
                $file->delete();
            }

            $delete_price->delete();
        }
    }

    /* ==================================================================
     |  مشخصات فنی
     ================================================================== */

    public function updateProductSpecifications(Product $product, array $specificationGroup): void
    {
        $product->specifications()->detach();
        $group_ordering = 0;

        if (! empty($specificationGroup)) {
            foreach ($specificationGroup as $group) {
                if (! isset($group['specifications'])) {
                    continue;
                }

                $spec_group = SpecificationGroup::firstOrCreate([
                    'name' => $group['name'],
                ]);

                $specification_ordering = 0;

                foreach ($group['specifications'] as $specification) {
                    $spec = Specification::firstOrCreate([
                        'name' => $specification['name'],
                    ]);

                    $product->specifications()->attach([
                        $spec->id => [
                            'specification_group_id' => $spec_group->id,
                            'group_ordering' => $group_ordering,
                            'specification_ordering' => $specification_ordering++,
                            'value' => $specification['value'],
                            'special' => isset($specification['special']) ? true : false,
                        ],
                    ]);
                }

                $group_ordering++;
            }
        }
    }

    /* ==================================================================
     |  برند / دسته‌بندی‌ها / برچسب‌ها
     ================================================================== */

    public function updateProductBrand(Product $product, ?string $brandName): void
    {
        if ($brandName) {
            $brand = Brand::firstOrCreate(
                [
                    'name' => $brandName,
                    'lang' => app()->getLocale(),
                ],
                [
                    'slug' => $brandName,
                ]
            );

            $product->update([
                'brand_id' => $brand->id,
            ]);
        }
    }

    public function updateProductCategories(Product $product, array $categories): void
    {
        if ($categories) {
            $product->categories()->sync(array_merge($categories, [$product->category_id]));
        } else {
            $product->categories()->sync([$product->category_id]);
        }
    }

    public function updateProductLabels(Product $product, ?string $labels): void
    {
        $label_ids = [];

        if ($labels) {
            $labels = explode(',', $labels);

            foreach ($labels as $item) {
                $label = Label::firstOrCreate([
                    'title' => $item,
                    'lang' => app()->getLocale(),
                ]);

                $label_ids[] = $label->id;
            }
        }

        $product->labels()->sync($label_ids);
    }

    /* ==================================================================
     |  تصاویر (تصویر شاخص + گالری)
     ================================================================== */

    /**
     * @param  UploadedFile|null  $image  تصویر شاخص جدید (UploadedFile لایووایر یا عادی)
     * @param  array  $gallery  لیست مرتب: رشته (مسیر موجود) یا UploadedFile (تصویر جدید)
     */
    public function updateProductImages(Product $product, ?UploadedFile $image, array $gallery): void
    {
        // تصویر شاخص
        if ($image) {
            $optimizedImagePath = uploadOptimizedImage($image, 'products', $product->id);
            $product->update(['image' => $optimizedImagePath]);
        }

        // مدیریت حذف و اضافه کردن تصاویر گالری
        $product_images = $product->gallery()->pluck('image')->toArray();

        $images = [];

        foreach ($gallery as $item) {
            if ($item instanceof UploadedFile) {
                // تصویر جدید از لایووایر (فایل موقت) — آپلود و بهینه‌سازی
                $optimizedImagePath = uploadOptimizedImage($item, 'products');
                $images[] = $optimizedImagePath;
            } else {
                $path = (string) $item;
                // استخراج مسیر نسبی و نرمال‌سازی (مطابق پنل قدیمی)
                $parsedUrl = parse_url(trim($path));
                $path = $parsedUrl['path'] ?? $path;
                $path = ltrim($path, '/');
                if (! str_starts_with($path, 'uploads/')) {
                    $path = 'uploads/' . $path;
                }
                $images[] = $path;
            }
        }

        // حذف تصاویری که در لیست جدید وجود ندارند
        $deleted_images = array_diff($product_images, $images);

        foreach ($deleted_images as $del_img) {
            $del_imgModel = $product->gallery()->where('image', $del_img)->first();

            if ($del_imgModel) {
                if (Storage::disk('public')->exists($del_img)) {
                    Storage::disk('public')->delete($del_img);
                }
                $del_imgModel->delete();
            }
        }

        // مدیریت اضافه شدن و به‌روزرسانی ترتیب تصاویر
        $ordering = 1;
        foreach ($images as $imagePath) {
            $existing = $product->gallery()->where('image', $imagePath)->first();

            if ($existing) {
                $existing->update(['ordering' => $ordering++]);
            } else {
                $product->gallery()->create([
                    'image' => $imagePath,
                    'ordering' => $ordering++,
                ]);
            }
        }
    }

    /* ==================================================================
     |  سایز بندی / امتیاز ویژگی‌ها
     ================================================================== */

    public function updateProductSizes(Product $product, array $sizes): void
    {
        $product->sizes()->detach();

        if (empty($sizes)) {
            return;
        }

        $ordering = 1;
        $groupOrdering = 1;

        foreach ($sizes as $group => $groupSizes) {
            foreach ($groupSizes as $size_id => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $product->sizes()->attach([
                    $size_id => [
                        'group' => $groupOrdering,
                        'value' => $value,
                        'ordering' => $ordering++,
                    ],
                ]);
            }

            $groupOrdering++;
        }
    }

    public function updateProductAbilityScore(Product $product, array $abilityScores): void
    {
        $ordering = 0;

        if (! empty($abilityScores)) {
            ProductAbilityScore::where('product_id', $product->id)->delete();

            foreach ($abilityScores as $score) {
                if (empty($score['name'])) {
                    continue;
                }

                $Score = new ProductAbilityScore();
                $Score->product_id = $product->id;
                $Score->name = $score['name'];
                $Score->value = $score['value'];
                $Score->ordering = $ordering;
                $Score->save();
                $ordering++;
            }
        }
    }

    /* ==================================================================
     |  حذف محصول
     ================================================================== */

    public function destroy(Product $product): void
    {
        activity()
            ->performedOn($product)
            ->causedBy(auth('adminPanel')->user())
            ->event('deleted')
            ->withProperties([
                'action' => 'delete_product',
                'product_id' => $product->id,
                'product_title' => $product->title,
                'ip' => request()->ip(),
            ])
            ->log('حذف محصول «' . ($product->title ?? '#' . $product->id) . '»');

        $product->tags()->detach();
        $product->specifications()->detach();

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        foreach ($product->gallery as $image) {
            if (Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }

            $image->delete();
        }

        SellerVariant::where('product_id', $product->id)->delete();
        $this->stockService->deleteProductSafely($product);
        $product->delete();
    }

    /* ==================================================================
     |  تبدیل تاریخ‌های شمسی
     ================================================================== */

    /**
     * تبدیل رشته تاریخ شمسی «Y-m-d H:i:s» به Carbon (null در صورت خطا)
     */
    public static function jdate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Jalalian::fromFormat('Y-m-d H:i:s', $value)->toCarbon();
        } catch (\Throwable $e) {
            return null;
        }
    }
}

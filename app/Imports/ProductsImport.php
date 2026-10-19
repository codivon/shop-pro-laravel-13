<?php

namespace App\Imports;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Morilog\Jalali\Jalalian;

class ProductsImport extends Controller implements ToModel, WithStartRow, WithChunkReading,WithBatchInserts
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failCount = 0;
    protected $duplicates = [];
    protected $rowNumber = 1;
    protected $processedHashes = [];
    protected $updateDuplicate;
    public $request;
    protected $warehouse_id;
    protected StockMovementService $stockService;

    public function __construct(Request $request, StockMovementService $stockService)
    {
        $this->request = $request;
        $this->warehouse_id = $request->warehouse_id ?? null;
        $this->stockService = $stockService;
        $this->updateDuplicate = $request->has('update_duplicate') && $request->update_duplicate == 1;
    }

    public function model(array $file)
    {
        $this->rowNumber++;

        // رد کردن سطرهای کاملاً خالی
        if (empty(array_filter($file))) {
            return null;
        }

        // جلوگیری از پردازش تکراری
        $rowHash = md5(serialize($file));
        if (in_array($rowHash, $this->processedHashes)) {
            return null;
        }
        $this->processedHashes[] = $rowHash;

        $row = 0;
        $Alldata = [];
        $allArrays = [];
        $fileCount = count($file) - 1;
        $filtersCount = count($this->request->filters) - 1;

        if ($filtersCount > $fileCount) {
            return null;
        }

        session()->forget('ImportError');

        foreach ($this->request->filters as $key => $filter) {
            $Alldata[] = [$key => $file[$row]];
            $row++;
        }

        $count = count($Alldata) - 1;
        for ($i = 0; $i <= $count; $i++) {
            $allArrays[] = $Alldata[$i];
        }
        $combinedArray = call_user_func_array('array_merge', $allArrays);

        // اگر بعد از فیلتر کردن، هیچ داده‌ای باقی نمانده باشد
        if (empty(array_filter($combinedArray))) {
            return null;
        }

        // نادیده گرفتن سطرهای فاقد title
        if (empty($combinedArray['title'])) {
            return null;
        }

        // ساخت slug
        if (isset($combinedArray['slug']) && !empty($combinedArray['slug'])) {
            $slug = sluggable_helper_function($combinedArray['slug']);
        } elseif (isset($combinedArray['title']) && !empty($combinedArray['title'])) {
            $slug = sluggable_helper_function($combinedArray['title']);
        } else {
            return null;
        }

        // بررسی وجود محصول
        $product = Product::where('slug', $slug)->first();

        if ($product) {
            // محصول موجود است
            if ($this->updateDuplicate) {
                // بروزرسانی
                $updateResult = $this->updateExistingProduct($product, $combinedArray);
                if ($updateResult !== true) {
                    $this->failCount++;
                    $this->errors[] = [
                        'row' => $this->rowNumber,
                        'title' => $combinedArray['title'] ?? '',
                        'error' => $updateResult,
                        'data' => $combinedArray
                    ];
                    return null;
                }
                $this->successCount++;
                return null;
            } else {
                // تکراری
                $this->failCount++;
                $this->duplicates[] = [
                    'row' => $this->rowNumber,
                    'title' => $combinedArray['title'] ?? '',
                    'error' => 'محصول با این Slug قبلاً ثبت شده است.',
                    'data' => $combinedArray
                ];
                return null;
            }
        }

        // ایجاد محصول جدید
        $createResult = $this->createNewProduct($combinedArray);
        if ($createResult !== true) {
            $this->failCount++;
            $this->errors[] = [
                'row' => $this->rowNumber,
                'title' => $combinedArray['title'] ?? '',
                'error' => $createResult,
                'data' => $combinedArray
            ];
            return null;
        }

        $this->successCount++;
        return null;
    }

    private function createNewProduct($combinedArray)
    {
        try {
            $product = new Product();

            // فیلدهای مستقیم محصول
            $directFields = [
                'title', 'title_en', 'slug', 'weight', 'unit',
                'short_description', 'description', 'special', 'published',
                'meta_title', 'meta_description', 'publish_date'
            ];

            foreach ($directFields as $field) {
                if (isset($combinedArray[$field]) && !empty($combinedArray[$field])) {
                    if ($field == 'weight') {
                        $product->$field = (int)$combinedArray[$field];
                    } elseif ($field == 'special' || $field == 'published') {
                        $product->$field = (int)$combinedArray[$field];
                    } else {
                        $product->$field = $combinedArray[$field];
                    }
                }
            }

            // تنظیم more (برای فیلدهای اضافی)
            $moreFields = ['brand', 'price', 'stock', 'tags'];
            $moreValue = null;
            foreach ($moreFields as $field) {
                if (isset($combinedArray[$field]) && !empty($combinedArray[$field])) {
                    $moreValue = $combinedArray[$field];
                    break;
                }
            }
            if ($moreValue) {
                $product->more = $moreValue;
            }

            // تنظیم slug
            if (isset($product->title) && !empty($product->title)) {
                $product->slug = sluggable_helper_function($product->title);
            } elseif (isset($combinedArray['slug']) && !empty($combinedArray['slug'])) {
                $product->slug = sluggable_helper_function($combinedArray['slug']);
            } else {
                return 'Slug نامعتبر است.';
            }

            // تنظیم نوع محصول
            if (isset($combinedArray['type']) && !empty($combinedArray['type'])) {
                if ($combinedArray['type'] == "فیزیکی") {
                    $product->type = "physical";
                } elseif ($combinedArray['type'] == "دانلودی") {
                    $product->type = "download";
                }
            }

            // ذخیره محصول
            $product->save();

            // پردازش تصویر
            if (isset($combinedArray['image']) && !empty($combinedArray['image'])) {
                $this->handleProductImage($product, $combinedArray['image']);
            }

            // پردازش دسته‌بندی
            if (isset($combinedArray['category']) && !empty($combinedArray['category'])) {
                $this->handleProductCategory($product, $combinedArray['category']);
            }

            // پردازش قیمت
            if (isset($combinedArray['price']) && isset($combinedArray['stock'])) {
                $this->updateProductPrices($product, [
                    'prices' => [
                        'price' => $combinedArray['price'],
                        'stock' => $combinedArray['stock'],
                        'warehouse_id' => $this->warehouse_id,
                        'seller_id' => null,
                        'discount' => null,
                        'cart_max' => null,
                        'cart_min' => null,
                        'discount_expire_at' => null,
                        'published' => 1,
                        'attributes' => [null, null, null, null],
                    ]
                ]);
            }

            // پردازش برند
            if (isset($combinedArray['brand']) && !empty($combinedArray['brand'])) {
                $this->updateProductBrand($product, ['brand' => $combinedArray['brand']]);
            }

            // پردازش تگ‌ها
            if (isset($combinedArray['tags']) && !empty($combinedArray['tags'])) {
                $this->handleProductTags($product, $combinedArray['tags']);
            }

            return true;
        } catch (\Exception $e) {
            return 'خطا در ایجاد محصول: ' . $e->getMessage();
        }
    }

    private function updateExistingProduct($product, $combinedArray)
    {
        try {
            $updateData = [];

            // فیلدهای قابل به‌روزرسانی
            $updateFields = [
                'title', 'title_en', 'weight', 'unit',
                'short_description', 'description', 'special', 'published',
                'meta_title', 'meta_description', 'publish_date'
            ];

            foreach ($updateFields as $field) {
                if (isset($combinedArray[$field]) && !empty($combinedArray[$field])) {
                    if ($field == 'weight') {
                        $updateData[$field] = (int)$combinedArray[$field];
                    } elseif ($field == 'special' || $field == 'published') {
                        $updateData[$field] = (int)$combinedArray[$field];
                    } else {
                        $updateData[$field] = $combinedArray[$field];
                    }
                }
            }

            // به‌روزرسانی نوع محصول
            if (isset($combinedArray['type']) && !empty($combinedArray['type'])) {
                if ($combinedArray['type'] == "فیزیکی") {
                    $updateData['type'] = "physical";
                } elseif ($combinedArray['type'] == "دانلودی") {
                    $updateData['type'] = "download";
                }
            }

            if (!empty($updateData)) {
                $product->update($updateData);
            }

            // پردازش تصویر
            if (isset($combinedArray['image']) && !empty($combinedArray['image'])) {
                $this->handleProductImage($product, $combinedArray['image']);
            }

            // پردازش دسته‌بندی
            if (isset($combinedArray['category']) && !empty($combinedArray['category'])) {
                $this->handleProductCategory($product, $combinedArray['category']);
            }

            // پردازش قیمت
            if (isset($combinedArray['price']) && isset($combinedArray['stock'])) {
                $this->updateProductPrices($product, [
                    'prices' => [
                        'price' => $combinedArray['price'],
                        'stock' => $combinedArray['stock'],
                        'warehouse_id' => $this->warehouse_id,
                        'seller_id' => null,
                        'discount' => null,
                        'cart_max' => null,
                        'cart_min' => null,
                        'discount_expire_at' => null,
                        'published' => 1,
                        'attributes' => [null, null, null, null],
                    ]
                ]);
            }

            // پردازش برند
            if (isset($combinedArray['brand']) && !empty($combinedArray['brand'])) {
                $this->updateProductBrand($product, ['brand' => $combinedArray['brand']]);
            }

            // پردازش تگ‌ها
            if (isset($combinedArray['tags']) && !empty($combinedArray['tags'])) {
                $this->handleProductTags($product, $combinedArray['tags']);
            }

            return true;
        } catch (\Exception $e) {
            return 'خطا در به‌روزرسانی محصول: ' . $e->getMessage();
        }
    }

    private function handleProductImage($product, $imageUrl)
    {
        try {
            $path = public_path('/uploads/products');
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            if ($product->image && file_exists(public_path($product->image))) {
                @unlink(public_path($product->image));
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // امنیتی: گواهی TLS تأیید شود
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // امنیتی: تطابق هاست گواهی
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200 && $content !== false && !empty($content)) {
                $ext = pathinfo($imageUrl, PATHINFO_EXTENSION) ?: 'jpg';
                $name = 'img-' . time() . '-' . $product->id . '.' . $ext;
                $fullPath = $path . '/' . $name;

                if (file_put_contents($fullPath, $content) !== false) {
                    $product->image = '/uploads/products/' . $name;
                    $product->save();
                } else {
                    //\Log::warning('ذخیره فایل ناموفق: ' . $fullPath);
                }
            } else {
                //\Log::warning('دانلود تصویر ناموفق: ' . $imageUrl . ' (کد: ' . $httpCode . ')');
            }
        } catch (\Exception $e) {
           // \Log::error('خطا در ذخیره تصویر محصول: ' . $e->getMessage());
        }
    }

    private function handleProductCategory($product, $categoryName)
    {
        $categorySlug = sluggable_helper_function($categoryName);
        $category = Category::where('slug', $categorySlug)->first();

        if (!$category) {
            $category = Category::create([
                'title' => $categoryName,
                'slug' => $categorySlug,
                'lang' => 'fa',
            ]);
        }

        $product->update([
            'category_id' => $category->id
        ]);
        $product->categories()->syncWithoutDetaching([$category->id]);
    }

    private function handleProductTags($product, $tagsString)
    {
        $allTags = explode(',', $tagsString);
        foreach ($allTags as $tag) {
            $tag = trim($tag);
            if (empty($tag)) continue;

            $tag_slug = sluggable_helper_function($tag);
            $get_tag = Tag::where('slug', $tag_slug)->first();

            if (!$get_tag) {
                $new_tag = new Tag();
                $new_tag->name = $tag;
                $new_tag->slug = $tag_slug;
                $new_tag->lang = 'fa';
                $new_tag->save();
                $tag_id = $new_tag->id;
            } else {
                $tag_id = $get_tag->id;
            }

            $Taggable = DB::table('taggables')->where([
                'tag_id' => $tag_id,
                'taggable_id' => $product->id,
                'taggable_type' => 'App\Models\Product'
            ])->first();

            if (!$Taggable) {
                DB::table('taggables')->insert([
                    'tag_id' => $tag_id,
                    'taggable_id' => $product->id,
                    'taggable_type' => 'App\Models\Product'
                ]);
            }
        }
    }

    private function updateProductBrand($product, $request)
    {
        if (isset($request['brand']) && !empty($request['brand'])) {
            $brand = Brand::firstOrCreate(
                [
                    'name' => $request['brand'],
                    'lang' => app()->getLocale(),
                ],
                [
                    'slug' => sluggable_helper_function($request['brand']),
                ]
            );

            $product->update([
                'brand_id' => $brand->id
            ]);
        }
    }

    private function updateProductPrices($product, $request)
    {
        if ($product->isDownload()) {
            return;
        }

        $prices_id = [];

        foreach ($request as $price) {
            $time = null;
            if (isset($price['discount_expire']) && $price['discount_expire']) {
                $time = Carbon::instance(Jalalian::fromFormat('Y-m-d H:i:s', $price['discount_expire'])->toCarbon())->toDateTimeString() ?? null;
            }

            $attributes = array_filter($price['attributes'] ?? []);
            $seller_id = [$price["seller_id"]] ?? [];
            $update_price = false;

            if (count($product->prices()->withTrashed()->get())) {
                foreach ($product->prices()->withTrashed()->get() as $product_price) {
                    $product_price_attributes = $product_price->get_attributes()->get()->pluck('id')->toArray();
                    $product_price_attributes2 = [];

                    if ($product_price->seller_id) {
                        $product_price_attributes2 = $product_price->get_sellers()->get()->pluck('id')->toArray();
                    }

                    sort($product_price_attributes);
                    $product_price_attributes = array_merge($product_price_attributes, $product_price_attributes2);

                    if ($attributes == $product_price_attributes) {
                        $update_price = $product_price;
                        break 1;
                    }
                }
            }

            if ($price['attributes'][3]) {
                $Newattributes = array_slice($attributes, 0, count($attributes) - 1);
            } else {
                sort($attributes);
                $Newattributes = $attributes;
            }

            if ($update_price) {
                $update_price->createChange(
                    $price["price"],
                    $price["discount"]
                );

                $oldStock = $update_price->stock ?? 0;
                $newStock = $price["stock"] ?? 0;

                $update_price->update([
                    "seller_id" => $price["seller_id"],
                    "price" => $price["price"],
                    "discount" => $price["discount"],
                    "discount_price" => get_discount_price($price["price"], $price["discount"], $product),
                    "regular_price" => get_discount_price($price["price"], 0, $product),
                    "stock" => $price["stock"],
                    "cart_max" => $price["cart_max"],
                    "cart_min" => $price["cart_min"],
                    "discount_expire_at" => $price["discount_expire_at"] ? Jalalian::fromFormat('Y-m-d H:i:s', $price["discount_expire_at"])->toCarbon() : null,
                    "deleted_at" => null,
                    "published" => $price["published"],
                ]);

                $update_price->get_attributes()->sync($Newattributes);

                if ($price['attributes'][3]) {
                    foreach ($Newattributes as $attribute) {
                        $attribute_price = DB::table('attribute_price')
                            ->where(['attribute_id' => $attribute, 'price_id' => $update_price->id])
                            ->first();

                        if ($attribute_price) {
                            DB::table('attribute_price')
                                ->where(['attribute_id' => $attribute, 'price_id' => $update_price->id])
                                ->update([
                                    'seller_id' => $price['attributes'][3],
                                    'product_id' => $product->id,
                                    'warehouse_id' => $this->warehouse_id
                                ]);
                        }
                    }
                }

                if ($newStock != $oldStock) {
                    $this->registerStockMovement(
                        $update_price,
                        $newStock,
                        'adjustment',
                        "ویرایش موجودی - محصول: {$product->title} - از {$oldStock} به {$newStock}"
                    );
                }

                $prices_id[] = $update_price->id;
            } else {
                $insert_price = $product->prices()->create([
                    "seller_id" => $price["seller_id"],
                    "price" => $price["price"],
                    "discount" => $price["discount"],
                    "discount_price" => get_discount_price($price["price"], $price["discount"], $product),
                    "regular_price" => get_discount_price($price["price"], 0, $product),
                    "stock" => $price["stock"],
                    "cart_max" => $price["cart_max"],
                    "cart_min" => $price["cart_min"],
                    "discount_expire_at" => $price["discount_expire_at"] ? Jalalian::fromFormat('Y-m-d H:i:s', $price["discount_expire_at"])->toCarbon() : null,
                    "published" => $price["published"],
                    "warehouse_id" => $this->warehouse_id
                ]);

                foreach ($Newattributes as $attribute) {
                    $attribute_price = DB::table('attribute_price')
                        ->where(['attribute_id' => $attribute, 'price_id' => $insert_price->id])
                        ->first();

                    if (!$attribute_price) {
                        $insert_price->get_attributes()->attach([$attribute]);

                        if ($price['attributes'][3]) {
                            DB::table('attribute_price')
                                ->where(['attribute_id' => $attribute, 'price_id' => $insert_price->id])
                                ->update([
                                    'seller_id' => $price['attributes'][3],
                                    'product_id' => $product->id,
                                    'warehouse_id' => $this->warehouse_id
                                ]);
                        }
                    }
                }

                $insert_price->createChange($price["price"], $price["discount"]);
                $insert_price->createChange($price["price"], $price["discount"], $price["stock"]);

                if ($insert_price->stock > 0) {
                    $this->registerStockMovement(
                        $insert_price,
                        $insert_price->stock,
                        'inbound',
                        "ایجاد محصول جدید - موجودی اولیه: {$insert_price->stock}"
                    );
                }

                $prices_id[] = $insert_price->id;
            }
        }

        $get_prices = $product->prices()->whereNotIn('id', $prices_id)->get();
        foreach ($get_prices as $get_price) {
            DB::table('attribute_price')->where(['price_id' => $get_price->id])->delete();
        }
        $product->prices()->whereNotIn('id', $prices_id)->forceDelete();

        DB::table('cart_product')
            ->where('product_id', $product->id)
            ->whereNotNull('price_id')
            ->whereNotIn('price_id', $prices_id)
            ->delete();

        DB::table('seller_variants')->where(['product_id' => $product->id])->delete();
        $get_sellers_seller_ids = Price::whereIn('id', $prices_id)->get();
        $seller_id = [];

        foreach ($get_sellers_seller_ids as $get_sellers_seller_id) {
            $seller_id[] = $get_sellers_seller_id->seller_id;
        }

        $seller_ids = array_unique($seller_id);

        foreach ($seller_ids as $seller_id) {
            $get_sellers_ids = Price::where(['seller_id' => $seller_id, 'product_id' => $product->id])->get();
            $ids = [];

            for ($i = 0; $i <= count($get_sellers_ids) - 1; $i++) {
                $ids[] = $get_sellers_ids[$i]['id'];
            }

            if (count(DB::table('seller_variants')->where(['product_id' => $product->id, 'seller_id' => $seller_id])->get())) {
                DB::table('seller_variants')
                    ->where(['product_id' => $product->id, 'seller_id' => $seller_id])
                    ->update(['prices_id' => $ids]);
            } else {
                if ($seller_id) {
                    DB::insert('insert into seller_variants (`product_id`, `seller_id`) values (?, ?)', [$product->id, $seller_id]);
                    DB::table('seller_variants')
                        ->where(['product_id' => $product->id, 'seller_id' => $seller_id])
                        ->update(['prices_id' => $ids]);
                }
            }
        }
    }

    private function registerStockMovement($price, $newStock, $type = 'inbound', $additionalInfo = '')
    {
        try {
            if (!$price || !$this->stockService) {
                return;
            }

            switch ($type) {
                case 'inbound':
                    $this->stockService->inbound(
                        $price,
                        $newStock,
                        "واردات از اکسل - ایجاد محصول جدید - {$additionalInfo}",
                        'excel_import'
                    );
                    break;

                case 'adjustment':
                    $oldStock = $price->stock ?? 0;
                    $stockDifference = $newStock - $oldStock;

                    if ($stockDifference != 0) {
                        $this->stockService->adjustment(
                            $price,
                            $newStock,
                            "واردات از اکسل - تغییر موجودی از {$oldStock} به {$newStock} - {$additionalInfo}"
                        );
                    }
                    break;

                case 'outbound':
                    $this->stockService->outbound(
                        $price,
                        $newStock,
                        "واردات از اکسل - کاهش موجودی - {$additionalInfo}",
                        'excel_import'
                    );
                    break;

                case 'transfer':
                    $this->stockService->transfer(
                        $price,
                        $newStock,
                        "واردات از اکسل - انتقال موجودی - {$additionalInfo}",
                        'excel_import'
                    );
                    break;

                default:
                    $this->stockService->adjustment(
                        $price,
                        $newStock,
                        "واردات از اکسل - ثبت موجودی اولیه - {$additionalInfo}"
                    );
                    break;
            }
        } catch (\Exception $e) {
            /*\Log::error('خطا در ثبت حرکت انبار برای محصول: ' . $e->getMessage(), [
                'price_id' => $price->id ?? null,
                'product_id' => $price->product_id ?? null,
                'new_stock' => $newStock,
                'type' => $type
            ]);*/
        }
    }

    public function getReport()
    {
        $allFails = array_merge($this->errors, $this->duplicates);
        $uniqueErrors = [];
        $seen = [];
        foreach ($this->errors as $e) {
            $key = md5(serialize($e['data']));
            if (!in_array($key, $seen)) {
                $uniqueErrors[] = $e;
                $seen[] = $key;
            }
        }
        $uniqueDuplicates = [];
        $seenDup = [];
        foreach ($this->duplicates as $d) {
            $key = md5(serialize($d['data']));
            if (!in_array($key, $seenDup)) {
                $uniqueDuplicates[] = $d;
                $seenDup[] = $key;
            }
        }
        return [
            'success_count' => $this->successCount,
            'fail_count' => count($uniqueErrors) + count($uniqueDuplicates),
            'total_count' => $this->successCount + count($uniqueErrors) + count($uniqueDuplicates),
            'failed_rows' => array_merge($uniqueErrors, $uniqueDuplicates),
            'errors' => $uniqueErrors,
            'duplicates' => $uniqueDuplicates,
            'update_duplicate' => $this->updateDuplicate,
        ];
    }

    public function uniqueBy()
    {
        return 'slug';
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function startRow(): int
    {
        return 2;
    }
}

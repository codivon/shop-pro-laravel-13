<?php

namespace App\Http\Controllers\Back;

use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Brand;
use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Product\StoreProductRequest;
use App\Http\Requests\Back\Product\UpdateProductRequest;
use App\Http\Resources\Datatable\Product\ProductCollection;
use App\Models\Currency;
use App\Models\FieldValue;
use App\Models\Fild;
use App\Models\Label;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductAbilityScore;
use App\Models\Seller;
use App\Models\SellerVariant;
use App\Models\SizeType;
use App\Models\Specification;
use App\Models\SpecificationGroup;
use App\Models\SpecType;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Cviebrock\EloquentSluggable\Services\SlugService;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;
use PHPHtmlParser\Dom;

class ProductController extends Controller
{
    protected StockMovementService $stockService;


    public function __construct(StockMovementService $stockService)
    {
        $this->authorizeResource(Product::class, 'product');
        $this->stockService = $stockService;
    }

    public function index()
    {
        $statistics = [
            'total' => Product::count(),
            'active' => Product::where('published', true)->count(),
            'inactive' => Product::where('published', false)->count(),
        ];
        return view('back.products.index', compact('statistics'));
    }

    public function apiIndex(Request $request)
    {
        $this->authorize('products.index');

        $products = Product::detectLang()->datatableFilter($request);

        $products = datatable($request, $products);

        return new ProductCollection($products);
    }


    public function indexPrices(Request $request)
    {
        $this->authorize('products.prices');
        $products   = Product::detectLang()->filter($request)->customPaginate($request);
        return view('back.products.prices', compact('products'));
    }

    public function updatePrices(Request $request)
    {
        $this->authorize('products.prices');

        $request->validate([
            'products'        => 'required|array',
        ]);

        $products_id    = array_keys($request->products);
        $prices_count   = Price::whereIn('product_id', $products_id)->count() * 2;
        $max_input_vars = ini_get('max_input_vars');

        if ($prices_count + 5 > $max_input_vars) {
            throw ValidationException::withMessages([
                'prices' => 'لطفا مقدار max_input_vars را در فایل php.ini تغییر دهید.'
            ]);
        }

        $updatedProducts = [];

        foreach ($request->products as $key => $value) {
            $product = Product::find($key);

            if (!$product) {
                continue;
            }

            $productChanges = [];

            foreach ($product->prices as $price) {
                if (!isset($value['prices'][$price->id])) {
                    continue;
                }

                $request_price = $value['prices'][$price->id];

                if (isset($request_price['price']) && isset($request_price['stock']) && ($request_price['price'] != $price->price || $request_price['stock'] != $price->stock)) {

                    $productChanges[] = [
                        'price_id' => $price->id,
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

            if (!empty($productChanges)) {
                $updatedProducts[] = [
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'changes' => $productChanges
                ];
            }
        }

        // ثبت لاگ تغییر قیمت محصولات
        // در متد updatePrices، جایگزین قسمت ثبت لاگ

// ثبت لاگ تغییر قیمت محصولات
        if (!empty($updatedProducts)) {
            $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
            $productCount = count($updatedProducts);

            $properties = [
                'action' => 'update_products_prices',
                'updated_products' => $updatedProducts,
                'ip' => request()->ip()
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

            if (!empty($oldData)) {
                $properties['old'] = $oldData;
                $properties['attributes'] = $newData;
            }

            $logMessage = "مدیر {$adminName} قیمت و موجودی {$productCount} محصول را ویرایش کرد";

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties($properties)
                ->log($logMessage);
        }

        // clear product caches
        Product::clearCache();

        return response('success');
    }

    public function store(StoreProductRequest $request)
    {
        // اعتبار سنجی فیلد های اختصاصی
        $requiredFilds = Fild::where('belongs_to', 'products')->where('required', 1)->get();
        $validationRules = [];
        $messagesValidationRules = [];
        foreach ($requiredFilds as $requiredFild) {
            $validationRules["filds.$requiredFild->id"] = 'required';
            $messagesValidationRules["filds.$requiredFild->id.required"] = "فیلد {$requiredFild->title} اجباری است.";
        }
        $request->validate($validationRules, $messagesValidationRules);

        $product = Product::create([
            'title'              => $request->title,
            'title_en'           => $request->title_en,
            'category_id'        => $request->category_id,
            'spec_type_id'       => spec_type($request),
            'size_type_id'       => $request->size_type_id,
            'weight'             => $request->weight,
            'unit'               => $request->unit,
            'price_type'         => "multiple-price",
            'type'               => $request->type,
            'seller_id'          => $request->seller_id,
            'shipping_nature'    => $request->shipping_nature,
            'description'        => sanitize_rich_text($request->description),
            'short_description'  => sanitize_rich_text($request->short_description),
            'special'            => $request->special ? true : false,
            'slug'               => $request->slug ?: $request->title,
            'meta_title'         => $request->meta_title,
            'image_alt'          => $request->image_alt,
            'meta_description'   => $request->meta_description,
            'published'          => $request->published,
            'status'             => $request->status,
            'publish_date'       => $request->publish_date ? Jalalian::fromFormat('Y-m-d H:i:s', $request->publish_date)->toCarbon() : null,
            'special_end_date'   => $request->special_end_date ? Jalalian::fromFormat('Y-m-d H:i:s', $request->special_end_date)->toCarbon() : null,
            'currency_id'        => $request->currency_id,
            'rounding_amount'    => $request->rounding_amount,
            'rounding_type'      => $request->rounding_type,
            'lang'               => app()->getLocale(),
        ]);

        // update product brand
        $this->updateProductBrand($product, $request);

        // update product prices
        $this->updateProductPrices($product, $request);

        // update product files
        $this->updateProductFiles($product, $request);

        // update product specifications
        $this->updateProductSpecifications($product, $request);

        // update product images
        $this->updateProductImages($product, $request);
        // update product categories
        $this->updateProductCategories($product, $request);

        // update product labels
        $this->updateProductLabels($product, $request);

        // update product sizes
        $this->updateProductSizes($product, $request);

        // update product Ability Score
        $this->updateProductAbilityScore($product, $request);

        // store Filds
        if (isset($request->filds) and count($request->filds)) {
            saveFieldValues($request->filds, 'products', $product->id);
        }

        // ========== ثبت لاگ ایجاد محصول ==========
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
        $productTitle = $product->title ?? "#{$product->id}";

        // جمع‌آوری اطلاعات محصول برای لاگ
        $attributes = [
            'عنوان' => $product->title,
            'وضعیت انتشار' => $product->published ? 'فعال' : 'غیرفعال',
            'نوع محصول' => $product->type ?? 'فیزیکی',
            'دسته‌بندی' => $product->category->title ?? 'بدون دسته‌بندی',
        ];

        if ($product->brand_id) {
            $attributes['برند'] = $product->brand->name ?? '';
        }

        if ($product->price) {
            $attributes['قیمت'] = number_format($product->price) . ' تومان';
        }

        if ($product->seller_id) {
            $attributes['فروشنده'] = $product->seller->full_name ?? $product->seller->name ?? '';
        }

        $properties = [
            'action' => 'create_product',
            'product_id' => $product->id,
            'product_title' => $productTitle,
            'attributes' => $attributes,
            'ip' => request()->ip()
        ];

        activity()
            ->performedOn($product)
            ->causedBy(auth('adminPanel')->user())
            ->event('created')
            ->withProperties($properties)
            ->log("مدیر {$adminName} محصول جدید «{$productTitle}» را ایجاد کرد");

        session()->put('toast-success', 'محصول با موفقیت ایجاد شد.');
        return response("success");
    }
    public function create(Request $request)
    {
        $categories      = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        $specTypes       = SpecType::detectLang()->get();
        $sizetypes       = SizeType::detectLang()->get();
        $attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->get();
        $currencies      = Currency::latest()->get();
        $sellers         = Seller::where(['status_register' => 'complete', 'status_documents' => 'Accept', 'status_work' => 'ACTIVE'])->get();

        $copy_product = $request->product ? Product::where('slug', $request->product)->first() : null;

        $filds = Fild::where('belongs_to', 'products')->orderBy('created_at', 'desc')->get();

        $warehouses = Warehouse::active()->get();

        return view('back.products.create', compact(
            'categories',
            'specTypes',
            'sizetypes',
            'attributeGroups',
            'copy_product',
            'currencies',
            'sellers',
            'filds',
            'warehouses',
        ));
    }

    public function edit(Product $product)
    {
        $categories      = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        $specTypes       = SpecType::detectLang()->get();
        $sizetypes       = SizeType::detectLang()->get();
        $attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->get();
        $currencies      = Currency::latest()->get();
        $AbilityScores    = ProductAbilityScore::where('product_id', $product->id)->orderBy('ordering')->get();
        $sellers         = Seller::with('seller_info')->where(['status_register' => 'complete', 'status_documents' => 'Accept', 'status_work' => 'ACTIVE'])->get();
        $seller_info     = Seller::with('seller_info')->where(['id' => $product->seller_id, 'status_register' => 'complete', 'status_documents' => 'Accept', 'status_work' => 'ACTIVE'])->first();

        $filds = Fild::where('belongs_to', 'products')->orderBy('created_at', 'desc')->get();
        $fieldValues = FieldValue::where('related_id', $product->id)->get();

        $warehouses = Warehouse::active()->get();

        return view('back.products.edit', compact(
            'product',
            'categories',
            'specTypes',
            'sizetypes',
            'attributeGroups',
            'currencies',
            'AbilityScores',
            'sellers',
            'seller_info',
            'filds',
            'fieldValues',
            'warehouses',
        ));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        // اعتبار سنجی فیلد های اختصاصی
        $requiredFilds = Fild::where('belongs_to', 'products')->where('required', 1)->get();
        $validationRules = [];
        $messagesValidationRules = [];
        foreach ($requiredFilds as $requiredFild) {
            $validationRules["filds.$requiredFild->id"] = 'required';
            $messagesValidationRules["filds.$requiredFild->id.required"] = "فیلد {$requiredFild->title} اجباری است.";
        }
        $request->validate($validationRules, $messagesValidationRules);

        // ذخیره مقادیر قدیمی قبل از آپدیت (همه فیلدها)
        $oldValues = [
            'title' => $product->getOriginal('title'),
            'title_en' => $product->getOriginal('title_en'),
            'category_id' => $product->getOriginal('category_id'),
            'spec_type_id' => $product->getOriginal('spec_type_id'),
            'size_type_id' => $product->getOriginal('size_type_id'),
            'weight' => $product->getOriginal('weight'),
            'unit' => $product->getOriginal('unit'),
            'type' => $product->getOriginal('type'),
            'seller_id' => $product->getOriginal('seller_id'),
            'shipping_nature' => $product->getOriginal('shipping_nature'),
            'description' => $product->getOriginal('description'),
            'short_description' => $product->getOriginal('short_description'),
            'special' => $product->getOriginal('special') ? 'بله' : 'خیر',
            'slug' => $product->getOriginal('slug'),
            'meta_title' => $product->getOriginal('meta_title'),
            'image_alt' => $product->getOriginal('image_alt'),
            'meta_description' => $product->getOriginal('meta_description'),
            'published' => $product->getOriginal('published') ? 'فعال' : 'غیرفعال',
            'status' => $product->getOriginal('status'),
            'publish_date' => $product->getOriginal('publish_date') ? jdate($product->getOriginal('publish_date'))->format('Y/m/d H:i') : null,
            'special_end_date' => $product->getOriginal('special_end_date') ? jdate($product->getOriginal('special_end_date'))->format('Y/m/d H:i') : null,
            'currency_id' => $product->getOriginal('currency_id'),
            'rounding_amount' => $product->getOriginal('rounding_amount'),
            'rounding_type' => $product->getOriginal('rounding_type'),
            'brand_id' => $product->brand_id,
        ];

        $product->update([
            'title'              => $request->title,
            'title_en'           => $request->title_en,
            'category_id'        => $request->category_id,
            'spec_type_id'       => spec_type($request),
            'size_type_id'       => $request->size_type_id,
            'weight'             => $request->weight,
            'unit'               => $request->unit,
            'price_type'         => "multiple-price",
            'type'               => $request->type,
            'seller_id'          => $request->seller_id,
            'shipping_nature'    => $request->shipping_nature,
            'description'        => sanitize_rich_text($request->description),
            'short_description'  => sanitize_rich_text($request->short_description),
            'special'            => $request->special ? true : false,
            'slug'               => $request->slug ?: $request->title,
            'meta_title'         => $request->meta_title,
            'image_alt'          => $request->image_alt,
            'meta_description'   => $request->meta_description,
            'published'          => $request->published,
            'status'             => $request->status,
            'publish_date'       => $request->publish_date ? Jalalian::fromFormat('Y-m-d H:i:s', $request->publish_date)->toCarbon() : null,
            'special_end_date'   => $request->special_end_date ? Jalalian::fromFormat('Y-m-d H:i:s', $request->special_end_date)->toCarbon() : null,
            'currency_id'        => $request->currency_id,
            'rounding_amount'    => $request->rounding_amount,
            'rounding_type'      => $request->rounding_type,
        ]);

        // update product brand
        $this->updateProductBrand($product, $request);

        // ========== جمع‌آوری تغییرات قیمت‌ها (بدون ثبت لاگ جداگانه) ==========
        $priceChanges = $this->getProductPriceChanges($product, $request);

        // update product prices
        $this->updateProductPrices($product, $request);

        // update product files
        $this->updateProductFiles($product, $request);

        // update product specifications
        $this->updateProductSpecifications($product, $request);

        // update product images
        $this->updateProductImages($product, $request);

        // update product categories
        $this->updateProductCategories($product, $request);

        // update product labels
        $this->updateProductLabels($product, $request);

        // update product sizes
        $this->updateProductSizes($product, $request);

        // update product Ability Score
        $this->updateProductAbilityScore($product, $request);

        // store Filds
        if (isset($request->filds) and count($request->filds)) {
            saveFieldValues($request->filds, 'products', $product->id);
        }

        // ========== ثبت یک لاگ واحد با همه تغییرات ==========
        $newValues = [
            'title' => $product->title,
            'title_en' => $product->title_en,
            'category_id' => $product->category_id,
            'spec_type_id' => $product->spec_type_id,
            'size_type_id' => $product->size_type_id,
            'weight' => $product->weight,
            'unit' => $product->unit,
            'type' => $product->type,
            'seller_id' => $product->seller_id,
            'shipping_nature' => $product->shipping_nature,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'special' => $product->special ? 'بله' : 'خیر',
            'slug' => $product->slug,
            'meta_title' => $product->meta_title,
            'image_alt' => $product->image_alt,
            'meta_description' => $product->meta_description,
            'published' => $product->published ? 'فعال' : 'غیرفعال',
            'status' => $product->status,
            'publish_date' => $product->publish_date ? jdate($product->publish_date)->format('Y/m/d H:i') : null,
            'special_end_date' => $product->special_end_date ? jdate($product->special_end_date)->format('Y/m/d H:i') : null,
            'currency_id' => $product->currency_id,
            'rounding_amount' => $product->rounding_amount,
            'rounding_type' => $product->rounding_type,
            'brand_id' => $product->brand_id,
        ];

        // ساختار نهایی تغییرات
        $finalOld = [];
        $finalNew = [];

        // اضافه کردن تغییرات فیلدهای اصلی
        foreach ($oldValues as $key => $old) {
            if (isset($newValues[$key]) && $old != $newValues[$key]) {
                $fieldTitle = $this->getProductFieldTitle($key);
                $finalOld[$fieldTitle] = $old;
                $finalNew[$fieldTitle] = $newValues[$key];
            }
        }

        // اضافه کردن تغییرات قیمت‌ها
        if (!empty($priceChanges)) {
            foreach ($priceChanges as $change) {
                $key = $change['attributes'] . ' - ' . $change['field'];
                $finalOld[$key] = $change['old'];
                $finalNew[$key] = $change['new'];
            }
        }

        // ثبت لاگ واحد
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
        $productTitle = $product->title ?? "#{$product->id}";

        $properties = [
            'action' => 'را ویرایش کرد',
            'product_id' => $product->id,
            'product_title' => $productTitle,
            'ip' => request()->ip()
        ];

        if (!empty($finalOld)) {
            $properties['old'] = $finalOld;
            $properties['attributes'] = $finalNew;
        }

        $logMessage = "مدیر {$adminName}, محصول «{$productTitle}» را ویرایش کرد";

        activity()
            ->performedOn($product)
            ->causedBy(auth('adminPanel')->user())
            ->withProperties($properties)
            ->log($logMessage);

        session()->put('toast-success', 'محصول با موفقیت ویرایش شد.');
        return response("success");
    }

    public function image_store(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|image|max:10240',
        ]);

        $image = $request->file('file');

        $currentDate = Carbon::now()->toDateString();
        $imagename = 'img' . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

        $image->storeAs('tmp', $imagename);

        return response()->json(['imagename' => $imagename]);
    }

    public function image_delete(Request $request)
    {
        $filename = $request->get('filename');

        if (Storage::exists('tmp/' . $filename)) {
            Storage::delete('tmp/' . $filename);
        }

        return response('success');
    }

    public function destroy(Product $product)
    {
        // ثبت لاگ حذف محصول (قبل از حذف)
        $this->logProductActivity('delete_product', $product);

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

        return response('success');
    }

    public function multipleDestroy(Request $request)
    {
        $this->authorize('products.delete');

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:products,id',
        ]);

        foreach ($request->ids as $id) {
            $product = Product::find($id);
            $this->destroy($product);
        }

        return response('success');
    }

    public function generate_slug(Request $request)
    {
        $request->validate([
            'title' => 'required',
        ]);

        $slug = SlugService::createSlug(Product::class, 'slug', $request->title);

        return response()->json(['slug' => $slug]);
    }

    public function export(Request $request)
    {
        $this->authorize('products.export');

        $products = Product::detectLang()->datatableFilter($request)->get();
        $productsCount = $products->count();

        // ثبت لاگ خروجی گرفتن
        $dummyProduct = new Product(['id' => 0, 'title' => 'خروجی محصولات']);
        $extraData = [
            'export_type' => $request->export_type,
            'products_count' => $productsCount,
        ];
        $this->logProductActivity('export_products', $dummyProduct, null, null, $extraData);

        switch ($request->export_type) {
            case 'excel': {
                return $this->exportExcel($products, $request);
                break;
            }
            default: {
                return $this->exportPrint($products, $request);
            }
        }
    }

    //------------- Category methods

    public function categories()
    {
        $this->authorize('products.category');

        $categories = Category::detectLang()->where('type', 'productcat')->whereNull('category_id')
            ->with('childrenCategories')
            ->orderBy('ordering')
            ->get();

        return view('back.products.categories', compact('categories'));
    }

    private function updateProductPrices(Product $product, Request $request)
    {
        if ($product->isDownload()) {
            return;
        }

        if (!isset($request->prices) || !is_array($request->prices) || !count($request->prices)) {
            \Log::warning('No prices data in request for product: ' . $product->id);
            return;
        }

        $pricesToKeep = [];
        $priceChangesLog = [];

        foreach ($request->prices as $index => $priceData) {

            // اعتبارسنجی
            if (!isset($priceData['price']) || !isset($priceData['stock'])) {
                continue;
            }

            // پردازش تاریخ انقضا
            $discountExpireAt = null;
            if (isset($priceData['discount_expire_at']) && $priceData['discount_expire_at']) {
                try {
                    $discountExpireAt = Jalalian::fromFormat('Y-m-d H:i:s', $priceData['discount_expire_at'])->toCarbon();
                } catch (\Exception $e) {
                    // خطای تاریخ را نادیده بگیر
                }
            }

            // پردازش ویژگی‌ها
            $attributes = $this->extractAttributes($priceData['attributes'] ?? []);
            $attributes = $this->validateAndFixDuplicateColors($attributes, $index);

            // اطلاعات پایه
            $sellerId = $priceData['seller_id'] ?? null;
            $warehouseId = $priceData['warehouse'] ?? null;
            $newStock = $priceData['stock'] ?? 0;

            // جستجوی قیمت موجود (با در نظر گرفتن warehouse_id)
            $currentPriceId = $priceData['id'] ?? null;


            // جستجوی قیمت موجود (با ارسال currentPriceId)
            $existingPrice = $this->findExistingPrice(
                $product,
                $attributes,
                $sellerId,
                $priceData['warehouse'] ?? null,
                $currentPriceId
            );

            // محاسبات قیمت
            $regularPrice = get_discount_price($priceData['price'], 0, $product);
            $discountPrice = get_discount_price($priceData['price'], $priceData['discount'] ?? 0, $product);

            $priceDataForSave = [
                'price' => $priceData['price'],
                'discount' => $priceData['discount'] ?? 0,
                'regular_price' => $regularPrice,
                'discount_price' => $discountPrice,
                'stock' => $newStock,
                'cart_max' => $priceData['cart_max'],
                'cart_min' => $priceData['cart_min'],
                'published' => $priceData['published'] ?? true,
                'discount_expire_at' => $discountExpireAt,
                'seller_id' => $sellerId,
                'warehouse_id' => $warehouseId,
            ];


            if ($existingPrice) {
                // ========== به‌روزرسانی تنوع موجود ==========

                // ذخیره تغییرات
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

                if (!empty($priceChange)) {
                    $attributesNames = '';
                    if (!empty($attributes)) {
                        $attrs = Attribute::whereIn('id', $attributes)->pluck('name')->toArray();
                        $attributesNames = implode(' - ', $attrs);
                    }
                    $priceChangesLog[] = [
                        'attributes' => $attributesNames ?: 'بدون ویژگی',
                        'changes' => $priceChange
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

                // به‌روزرسانی قیمت موجود
                if ($existingPrice->price != $priceData['price'] ||
                    $existingPrice->stock != $newStock) {
                    $existingPrice->createChange(
                        $priceData['price'],
                        $priceData['discount'] ?? 0,
                        $newStock
                    );
                }

                $existingPrice->update($priceDataForSave);

                $pricesToKeep[] = $existingPrice->id;

                \Log::info("Updated existing price ID: {$existingPrice->id} for product {$product->id}");

            } else {
                // ========== ایجاد تنوع جدید ==========

                $attributesNames = '';
                if (!empty($attributes)) {
                    $attrs = Attribute::whereIn('id', $attributes)->pluck('name')->toArray();
                    $attributesNames = implode(' - ', $attrs);
                }
                $priceChangesLog[] = [
                    'attributes' => $attributesNames ?: 'بدون ویژگی',
                    'changes' => [
                        'price' => ['old' => 'ندارد', 'new' => $priceData['price']],
                        'stock' => ['old' => 'ندارد', 'new' => $newStock],
                    ]
                ];

                $priceDataForSave['product_id'] = $product->id;
                $newPrice = Price::create($priceDataForSave);

                // ثبت حرکت انبار برای ایجاد تنوع جدید
                if ($newStock > 0) {
                    $newPrice->update(['stock' => 0]);
                    $this->stockService->inbound(
                        $newPrice,
                        $newStock,
                        "ایجاد تنوع جدید برای محصول - موجودی اولیه",
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

        \Log::info("Product {$product->id} prices updated. Kept: " . count($pricesToKeep));
    }

    /**
     * استخراج ویژگی‌ها از فرمت‌های مختلف
     */
    private function extractAttributes($attributesData)
    {
        if (!is_array($attributesData)) {
            return [];
        }

        $isAssociative = array_keys($attributesData) !== range(0, count($attributesData) - 1);
        if ($isAssociative) {
            $attributes = array_values(array_filter($attributesData, function($value) {
                return !empty($value);
            }));
        } else {
            $attributes = array_filter($attributesData, function($value) {
                return !empty($value);
            });
        }

        sort($attributes);
        return $attributes;
    }

    /**
     * اعتبارسنجی و رفع رنگ تکراری
     */
    private function validateAndFixDuplicateColors($attributes, $index)
    {
        $colorAttributeIds = [];

        foreach ($attributes as $attributeId) {
            $attribute = Attribute::find($attributeId);
            if ($attribute && $attribute->group && $attribute->group->type == 'color') {
                $colorAttributeIds[] = $attributeId;
            }
        }

        if (count($colorAttributeIds) > 1) {
            \Log::error("Price {$index} has multiple colors: " . implode(', ', $colorAttributeIds));
            $firstColor = $colorAttributeIds[0];
            $attributes = array_values(array_diff($attributes, array_slice($colorAttributeIds, 1)));
            \Log::info("Fixed: Kept only color {$firstColor}");
        }

        return $attributes;
    }

    /**
     * جستجوی قیمت موجود
     */
    private function findExistingPrice($product, $attributes, $sellerId, $warehouseId = null, $currentPriceId = null)
    {
        // ========== اولویت اول: اگر currentPriceId دارد، همان را برگردان ==========
        if ($currentPriceId && !empty($currentPriceId)) {
            $price = Price::where('id', $currentPriceId)
                ->where('product_id', $product->id)
                ->first();

            if ($price) {
                \Log::info("Found price by currentPriceId: {$currentPriceId}", [
                    'warehouse_id' => $price->warehouse_id,
                    'seller_id' => $price->seller_id
                ]);
                return $price;
            }
        }

        // ========== دوم: جستجو بر اساس ترکیب ویژگی‌ها ==========
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

        // مرتب کردن ویژگی‌های جدید برای مقایسه
        $sortedNewAttributes = collect($attributes)->sort()->values()->toArray();

        foreach ($existingPrices as $price) {
            $priceAttributes = DB::table('attribute_price')
                ->where('price_id', $price->id)
                ->pluck('attribute_id')
                ->sort()
                ->values()
                ->toArray();

            if ($priceAttributes == $sortedNewAttributes) {
                \Log::info("Found price by attribute match: {$price->id}");
                return $price;
            }
        }

        \Log::info("No existing price found, will create new", [
            'attributes' => $sortedNewAttributes,
            'warehouse_id' => $warehouseId,
            'seller_id' => $sellerId
        ]);

        return null;
    }
    /**
     * همگام‌سازی ویژگی‌های قیمت
     */
    private function syncAttributesToPrice($price, $attributes, $sellerId, $productId)
    {
        // حذف ویژگی‌های قبلی
        DB::table('attribute_price')
            ->where('price_id', $price->id)
            ->delete();

        // اضافه کردن ویژگی‌های جدید
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

        \Log::info("Synced attributes for price ID: {$price->id}", [
            'new_attributes' => $attributes
        ]);
    }
    /**
     * حذف قیمت‌های اضافی
     */
    private function deleteExtraPrices($product, $pricesToKeep)
    {
        $deletedPrices = Price::where('product_id', $product->id)
            ->whereNotIn('id', $pricesToKeep)
            ->get();

        foreach ($deletedPrices as $deletedPrice) {
            // ========== ثبت حرکت انبار قبل از حذف ==========
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
     * به‌روزرسانی seller_variants برای همه فروشنده‌ها
     */
    private function updateSellerVariants(Product $product)
    {
        // حذف رکوردهای قدیمی
        DB::table('seller_variants')->where('product_id', $product->id)->delete();

        // فروشنده‌های دارای seller_id
        $sellerIds = Price::where('product_id', $product->id)
            ->whereNotNull('seller_id')
            ->distinct()
            ->pluck('seller_id');

        foreach ($sellerIds as $sellerId) {
            $priceIds = Price::where('product_id', $product->id)
                ->where('seller_id', $sellerId)
                ->pluck('id')
                ->toArray();

            if (!empty($priceIds)) {
                DB::table('seller_variants')->insert([
                    'product_id' => $product->id,
                    'seller_id' => $sellerId,
                    'prices_id' => json_encode($priceIds),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // فروشنده null (مدیر سایت)
        $nullSellerPriceIds = Price::where('product_id', $product->id)
            ->whereNull('seller_id')
            ->pluck('id')
            ->toArray();

        if (!empty($nullSellerPriceIds)) {
            DB::table('seller_variants')->insert([
                'product_id' => $product->id,
                'seller_id' => null,
                'prices_id' => json_encode($nullSellerPriceIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    private function updateProductFiles(Product $product, Request $request)
    {
        if ($product->isPhysical()) {
            return;
        }

        $prices_id = [];
        $ordering = 1;

        foreach ($request->download_files as $price) {

            $update_price = false;

            if (isset($price['price_id'])) {
                $update_price = $product->prices()->withTrashed()->where('prices.id', $price['price_id'])->first();
            }

            if ($update_price) {

                $update_price->createChange(
                    $price["price"],
                    $price["discount"]
                );

                $update_price->update([
                    "price"             => $price["price"],
                    "discount"          => $price["discount"],
                    "discount_price"    => get_discount_price($price["price"], $price["discount"], $product),
                    "regular_price"  => get_discount_price($price["price"], 0, $product),
                    "deleted_at"        => null,
                    "ordering"          => $ordering++
                ]);

                $update_price->updateFile($price['title'], $price['file'] ?? null, $price['status']);

                $prices_id[] = $update_price->id;
            } else {
                $insert_price = $product->prices()->create(
                    [
                        "price"           => $price["price"],
                        "discount"        => $price["discount"],
                        "discount_price"  => get_discount_price($price["price"], $price["discount"], $product),
                        "regular_price"  => get_discount_price($price["price"], $price["discount"], $product),
                        "ordering"        => $ordering++
                    ]
                );

                $insert_price->createFile($price['title'], $price['file'], $price['status']);

                $insert_price->createChange($price["price"], $price["discount"]);

                $insert_price->createChange(
                    $price["price"],
                    $price["discount"]
                );

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

    private function updateProductSpecifications(Product $product, Request $request)
    {
        $product->specifications()->detach();
        $group_ordering = 0;

        if ($request->specification_group) {
            foreach ($request->specification_group as $group) {

                if (!isset($group['specifications'])) {
                    continue;
                }

                $spec_group = SpecificationGroup::firstOrCreate([
                    'name' => $group['name'],
                ]);

                $specification_ordering = 0;

                foreach ($group['specifications'] as $specification) {
                    $spec = Specification::firstOrCreate([
                        'name' => $specification['name']
                    ]);

                    $product->specifications()->attach([
                        $spec->id => [
                            'specification_group_id' => $spec_group->id,
                            'group_ordering'         => $group_ordering,
                            'specification_ordering' => $specification_ordering++,
                            'value'                  => $specification['value'],
                            'special'                => isset($specification['special']) ? true : false
                        ]
                    ]);
                }

                $group_ordering++;
            }
        }
    }

    private function updateProductBrand(Product $product, Request $request)
    {
        if ($request->brand) {
            $brand = Brand::firstOrCreate(
                [
                    'name'    => $request->brand,
                    'lang'    => app()->getLocale(),
                ],
                [
                    'slug' => $request->brand,
                ]
            );

            $product->update([
                'brand_id' => $brand->id
            ]);
        }
    }

    private function updateProductImages(Product $product, Request $request)
    {
        if ($request->FromSite == "yes") {
            if (!Storage::exists('uploads/products')) {
                Storage::makeDirectory('uploads/products');
            }

            // اول گالری را پردازش کن
            if (!empty($request->image_fromSite)) {
                $ordering = 1;

                foreach ($request->image_fromSite as $image) {
                    $tempImagePath = public_path('uploads/tmp/' . $image);

                    if (file_exists($tempImagePath)) {
                        $uploadedFile = new \Illuminate\Http\UploadedFile(
                            $tempImagePath,
                            $image,
                            mime_content_type($tempImagePath),
                            null,
                            true
                        );

                        $optimizedImagePath = uploadOptimizedImage(
                            $uploadedFile,
                            'products',
                            $product->id,
                            [
                                'field' => 'image',
                                'size' => [800, 800],
                                'path' => "uploads/products/"
                            ]
                        );

                        $product->gallery()->create([
                            'image' => $optimizedImagePath,
                            'ordering' => $ordering
                        ]);

                        unlink($tempImagePath);
                        $ordering++;
                    }
                }
            }
// بعد تصویر اصلی را پردازش کن (اگر با گالری یکی نباشد)
            if ($request->base_image_fromSite) {
                $image = $request->base_image_fromSite;
                $tempImagePath = public_path('uploads/tmp/' . $image);

                // بررسی کن که آیا فایل هنوز وجود دارد (ممکن است در گالری حذف شده باشد)
                if (file_exists($tempImagePath)) {
                    $uploadedFile = new \Illuminate\Http\UploadedFile(
                        $tempImagePath,
                        $image,
                        mime_content_type($tempImagePath),
                        null,
                        true
                    );

                    $optimizedImagePath = uploadOptimizedImage(
                        $uploadedFile,
                        'products',
                        $product->id,
                        ['field' => 'image']
                    );

                    unlink($tempImagePath);
                    $product->update(['image' => $optimizedImagePath]);
                    $product->gallery()->create(['image' => $optimizedImagePath]);
                } else {
                    // اگر فایل وجود نداشت، شاید از قبل در گالری ذخیره شده
                    // می‌توانید از اولین تصویر گالری به عنوان تامنیل استفاده کنید
                    $firstGalleryImage = $product->gallery()->orderBy('ordering')->first();
                    if ($firstGalleryImage) {
                        $product->update(['image' => $firstGalleryImage->image]);
                    }
                }
            }

// پاک کردن پوشه tmp
            $tmpDir = public_path('uploads/tmp');
            if (is_dir($tmpDir) && count(glob($tmpDir . '/*')) === 0) {
                rmdir($tmpDir);
            }
        }
        else {
            if ($request->hasFile('image')) {
                $optimizedImagePath = uploadOptimizedImage($request->file('image'), 'products', $product->id);
                $product->update(['image' => $optimizedImagePath]);
            }

// مدیریت حذف و اضافه کردن تصاویر گالری
            $product_images = $product->gallery()->pluck('image')->toArray();

// دریافت images از request و استخراج فقط مسیر نسبی
            $imagesRaw = $request->images ?? '';
            $images = [];

            if (!empty($imagesRaw)) {
                $imageUrls = explode(',', $imagesRaw);
                foreach ($imageUrls as $url) {
                    // استخراج مسیر نسبی از URL کامل
                    $parsedUrl = parse_url(trim($url));
                    $path = $parsedUrl['path'] ?? $url;
                    $path = ltrim($path, '/');

                    // اگر مسیر با uploads/ شروع نشد، اضافه کن
                    if (!str_starts_with($path, 'uploads/')) {
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
            if (!empty($images)) {
                foreach ($images as $image) {
                    // بررسی آیا تصویر جدید است (در tmp پوشه وجود دارد)
                    // تصاویر جدید معمولاً فرمتی مثل img-2026-05-01-69f470790aaff.jpg دارند
                    $isNewImage = !str_contains($image, 'uploads/products/products-') &&
                        !$product->gallery()->where('image', $image)->exists();

                    // همچنین بررسی در پوشه tmp
                    $tmpPath = 'tmp/' . basename($image);
                    if (Storage::exists($tmpPath)) {
                        $isNewImage = true;

                        // دریافت محتوای فایل
                        $fileContent = Storage::get($tmpPath);

                        // ایجاد مسیر فایل موقت
                        $tempFilePath = storage_path('app/tmp_' . basename($image));
                        file_put_contents($tempFilePath, $fileContent);

                        // ایجاد یک شیء UploadedFile
                        $tempFile = new \Illuminate\Http\UploadedFile(
                            $tempFilePath,
                            basename($image),
                            mime_content_type($tempFilePath),
                            null,
                            true
                        );

                        // آپلود و بهینه‌سازی تصویر
                        $optimizedImagePath = uploadOptimizedImage($tempFile, 'products');

                        // حذف فایل موقت از سرور
                        unlink($tempFilePath);

                        // حذف فایل از tmp
                        Storage::delete($tmpPath);

                        // ذخیره تصویر در گالری
                        $product->gallery()->create([
                            'image' => $optimizedImagePath,
                            'ordering' => $ordering++,
                        ]);
                    }
                    // بررسی اگر تصویر قبلاً در گالری وجود دارد
                    elseif ($product->gallery()->where('image', $image)->exists()) {
                        // به‌روزرسانی ترتیب نمایش تصاویر موجود
                        $product->gallery()->where('image', $image)->update(['ordering' => $ordering++]);
                    }
                    // اگر تصویر موجود است ولی در tmp نیست و در گالری هم نیست (شاید تصویر اصلی محصول)
                    elseif (Storage::disk('public')->exists($image) && !$product->gallery()->where('image', $image)->exists()) {
                        $product->gallery()->create([
                            'image' => $image,
                            'ordering' => $ordering++,
                        ]);
                    }
                }
            }
        }
    }

    private function updateProductCategories(Product $product, Request $request)
    {
        if ($request->categories) {
            $product->categories()->sync(array_merge($request->categories, [$product->category_id]));
        } else {
            $product->categories()->sync([$product->category_id]);
        }
    }

    private function updateProductLabels(Product $product, Request $request)
    {
        $label_ids = [];

        if ($request->labels) {
            $labels = explode(',', $request->labels);

            foreach ($labels as $item) {
                $label = Label::firstOrCreate([
                    'title'    => $item,
                    'lang'     => app()->getLocale(),
                ]);

                $label_ids[] = $label->id;
            }
        }

        $product->labels()->sync($label_ids);
    }

    private function updateProductSizes(Product $product, Request $request)
    {
        $product->sizes()->detach();

        if (!$request->sizes) return;

        $ordering      = 1;
        $groupOrdering = 1;

        foreach ($request->sizes as $group => $sizes) {

            foreach ($sizes as $size_id => $value) {
                $product->sizes()->attach(
                    [
                        $size_id => [
                            'group'    => $groupOrdering,
                            'value'    => $value,
                            'ordering' => $ordering++
                        ]
                    ]
                );
            }

            $groupOrdering++;
        }
    }

    private function updateProductAbilityScore(Product $product, Request $request)
    {

        $ordering = 0;
        if ($request->AbilityProductScore) {
            if (ProductAbilityScore::where('product_id', $product->id)->exists()) {
                ProductAbilityScore::where('product_id', $product->id)->delete();
            }
            foreach ($request->AbilityProductScore as $specification) {

                $Score = new ProductAbilityScore();
                $Score->product_id = $product->id;
                $Score->name = $specification['name'];
                $Score->value = $specification['value'];
                $Score->ordering = $ordering;
                $Score->save();
                $ordering++;
            }
        }
    }

    private function exportExcel($products, Request $request)
    {
        return Excel::download(new ProductsExport($products, $request), 'products.xlsx');
    }

    private function exportPrint($products, Request $request)
    {
        //
    }

    public function getProductFromSite(Request $request)
    {

        $type = $request->type;

        switch ($type) {
            case "digikala":
                $request->validate([
                    'siteCode' => 'required|numeric',
                ]);

                $result = $this->digikala($request->siteCode, true);
                return response([$result]);

            case "torob":
                $request->validate([
                    'siteCode' => 'required',
                ]);

                $result = $this->getDataTorob($request->siteCode);
                return response([$result]);

            default:
                // اگر نوع نامعتبر بود، می‌توانید یک پاسخ خطا برگردانید
                return response(['error' => 'Invalid type'], 400);
        }
    }

    private function getDataTorob($siteCode)
    {

        $dom = new Dom;
        $dom = $dom->loadFromUrl($siteCode);
        $title = $dom->find('h1');
        if (count($title) >= 1) {
            $title = $title->text;
        } else {
            $title = '';
        }

        $abilities = [];
        $ability = $dom->find('.key_specs');

        if ($ability != '') {
            $ability = $ability->find('.key-specs-container');
            foreach ($ability as $item) {
                $ability1 = $item->find('.keys-values', 0);
                if ($ability1->find('span') != '') {
                    $ability1 = $ability1->find('span');
                }
                $ability2 = $item->find('.keys-values', 1);
                if ($ability2->find('span') != '') {
                    $ability2 = $ability2->find('span');
                }
                $texts = $ability1->text . ' : ' . $ability2->text;
                array_push($abilities, $texts);
            }
        }
        $categoriesTag = $dom->find('.breads', 0); // اولین عنصر با کلاس .breads
        if ($categoriesTag) {
            $categoriesItems = $categoriesTag->find('div.breadcrumb_breadItem__fWr4l'); // انتخاب مستقیم div با کلاس مشخص
            foreach ($categoriesItems as $item) {
                $cat[] = trim($item->plaintext) . PHP_EOL; // متن دسته‌بندی را چاپ کنید
            }
        }
        $categories = [];
        $category = '';
        $categoriesTag = $dom->find('.breads', 0); // اولین عنصر با کلاس .breads
        if ($categoriesTag) {
            $categoriesItems = $categoriesTag->find('[class^=breadcrumb_breadItem]'); // انتخابگر صحیح
            foreach ($categoriesItems as $item) {
                // امنیتی: خروجی اشکال‌زدایی حذف شد (dd) — dd(trim($item->plaintext) . PHP_EOL);
                echo trim($item->plaintext) . PHP_EOL; // متن دسته‌بندی را چاپ کنید
            }
            // امنیتی: خروجی اشکال‌زدایی حذف شد (dd) — dd('kk', $category);
            if ($category) {
                $category1 = Category::where('name', $category)->first();
                if ($category1) {
                    $category = $category1;
                } else {
                    $category = Category::create([
                        'name' => $category,
                        'nameSeo' => $category,
                        'keyword' => $category,
                    ]);
                }
                array_push($categories, $category);
            }
        }

        // امنیتی: خروجی اشکال‌زدایی حذف شد (dd) — dd($categories);
        $properties = [];
        $property = $dom->find('.sub-section');
        if ($property != '') {
            $property = $property->find('div');
            foreach ($property as $item) {
                $property1 = $item->find('.detail-title');
                $property2 = $item->find('.detail-value');
                if ($property1 != '' && $property2 != '') {
                    $p1 = [
                        'title' => $property1->text,
                        'body' => $property2->text,
                    ];
                    array_push($properties, $p1);
                }
            }
        }

        $price = 0;
        $count = 100;
        $priceElement = $dom->find('.cheapest-seller .buy_box_text', 1);
        if ($priceElement) {
            $price = $priceElement->text;
            $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishNumbers = range(0, 9);
            $price = str_replace($persianNumbers, $englishNumbers, $price);
            $price = str_replace(' تومان', '', $price);
            $price = str_replace('٫', '', $price);
            if ($price == 'ناموجود') {
                $count = 0;
            }
        }

        $images = [];
        $image = $dom->find('.gallery img');
        // امنیتی: خروجی اشکال‌زدایی حذف شد (dd) — dd($image);
        if ($image) {
            $image = $image->getAttribute('src');
            $image = str_replace('280x280', '560x560', $image);
            $year = Carbon::now()->year;
            $time = time();
            $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/image/' . $year . '/';
            $url = '/upload/image/' . $year . '/';
            $img = Image::make($image)->save('upload/image/' . $year . '/' . $time . '.' . 'jpg', 100, 'jpg');
            $sizefile = $img->filesize() / 1000;
            if ($sizefile > 1000) {
                $size = round($sizefile / 1000, 2) . 'mb';
            } else {
                $size = round($sizefile) . 'kb';
            }
            $image = Gallery::create([
                'name' => $time . '.' . 'jpg',
                'size' => $size,
                'type' => 'jpg',
                'user_id' => auth('adminPanel')->user()->id,
                'url' => $url . $time . '.' . 'jpg',
                'path' => $path . $time . '.' . 'jpg',
            ]);
            array_push($images, $image['url']);
        }

        return [$title, $price, $abilities, $properties, $categories, $images, $count];
    }

    public function digikala($siteCode, $autoCreateCategory = false)
    {
        try {
            $ch = curl_init('https://api.digikala.com/v2/product/'.$siteCode.'/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // امنیتی: گواهی TLS تأیید شود
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // امنیتی: تطابق هاست گواهی
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Accept-Charset: utf-8',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new \Exception('Curl error: ' . curl_error($ch));
            }

            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception("API returned HTTP code: " . $httpCode);
            }

            // حذف BOM و کاراکترهای مشکل‌دار
            if (substr($result, 0, 3) === "\xEF\xBB\xBF") {
                $result = substr($result, 3);
            }

            $result = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error: ' . json_last_error_msg());
            }

            $images = [];
            $colors = [];
            $specifications = [];
            $image = null;
            $review = [];
            $weight = null;

            // بررسی وجود key ها قبل از استفاده
            if (!isset($result['data']['product']['default_variant']['price']['selling_price'])) {
                throw new \Exception('Product price not found');
            }

            $defaultPrice = substr($result['data']['product']['default_variant']['price']['selling_price'], 0, -1);

            if(!is_dir('uploads/tmp')){
                mkdir("uploads/tmp", 0777, true);
            }

            // ==================== پردازش دسته بندی ====================
            $category = null;
            if ($autoCreateCategory && isset($result['data']['product']['category'])) {
                $categoryData = $result['data']['product']['category'];

                // جستجوی دسته بندی بر اساس کد یا نام
                $category = Category::Where('title', $categoryData['title_fa'])->first();

                // اگر دسته بندی وجود نداشت، ایجاد کن
                if (!$category) {
                    $category = Category::create([
                        'title' => $categoryData['title_fa'],
                        'slug' => sluggable_helper_function($categoryData['title_fa']),
                        'type' => 'productcat',
                        'lang' => 'fa',
                    ]);
                }
            }

            // ==================== پردازش تصویر اصلی ====================
            if (isset($result['data']['product']['images']['main']['webp_url'][0])){
                $imageUrlBase = $result['data']['product']['images']['main']['webp_url'][0];
                if (strpos($imageUrlBase, '?') !== false) {
                    $t = explode('?', $imageUrlBase);
                    $imageName = basename($t[0]);
                } else {
                    $imageName = basename($imageUrlBase);
                }
                $path = 'uploads/tmp/' . $imageName;

                if (!file_exists($path)) {
                    $this->resizeAndSaveSiteImage($imageUrlBase, $path, 600, 600);
                }
                $image = $imageName;
            }

            // ==================== پردازش لیست تصاویر ====================
            if (isset($result['data']['product']['images']['list']) && count($result['data']['product']['images']['list'])){
                $counter = 0;
                foreach($result['data']['product']['images']['list'] as $item){
                    if ($counter >= 11) break;

                    if (isset($item['url'][0])) {
                        $imageUrlBase = $item['url'][0];
                        if (strpos($imageUrlBase, '?') !== false) {
                            $t = explode('?', $imageUrlBase);
                            $imageName = basename($t[0]);
                        } else {
                            $imageName = basename($imageUrlBase);
                        }
                        $path = 'uploads/tmp/' . $imageName;

                        if (!file_exists($path)) {
                            $this->resizeAndSaveSiteImage($imageUrlBase, $path);
                            usleep(500000);
                        }

                        $Allimages = [
                            'imageName' => $imageName,
                            'path' => $path,
                        ];
                        array_push($images, $Allimages);
                        $counter++;
                    }
                }
            }

            // ==================== پردازش قیمت بر اساس رنگ ====================
            $variants = $result['data']['product']['variants'] ?? [];
            $colorPrices = [];

            // استخراج قیمت هر رنگ از واریانت‌ها
            foreach ($variants as $variant) {
                if (isset($variant['color']) && isset($variant['price']['selling_price'])) {
                    $colorId = $variant['color']['id'];
                    $price = substr($variant['price']['selling_price'], 0, -1);
                    // اگر چند واریانت برای یک رنگ وجود دارد، ارزان‌ترین را انتخاب کن
                    if (!isset($colorPrices[$colorId]) || $price < $colorPrices[$colorId]['price']) {
                        $colorPrices[$colorId] = [
                            'title' => $variant['color']['title'],
                            'hex_code' => $variant['color']['hex_code'],
                            'price' => $price,
                            'variant_id' => $variant['id']
                        ];
                    }
                }
            }

            // ==================== پردازش رنگ‌ها با قیمت اختصاصی ====================
            $attribute_group_id = AttributeGroup::where('type', 'color')->first();
            if (!$attribute_group_id) {
                $attribute_group_id = AttributeGroup::create([
                    'type' => 'color',
                    'name' => 'رنگ',

                ]);
                //throw new \Exception('Attribute group color not found');
            }
            $attribute_group_id = $attribute_group_id->id;

            $attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->get();
            $sellers = Seller::with('seller_info')
                ->where(['status_register'=>'complete', 'status_documents'=>'Accept', 'status_work'=>'ACTIVE'])
                ->get();

            $warehouses = Warehouse::active()->get();

            if (isset($result['data']['product']['colors']) && count($result['data']['product']['colors'])){
                $rowCount = 1;
                foreach ($result['data']['product']['colors'] as $color){
                    // قیمت اختصاصی این رنگ
                    $colorPrice = $defaultPrice;
                    if (isset($colorPrices[$color['id']])) {
                        $colorPrice = $colorPrices[$color['id']]['price'];
                    }

                    $attribute = Attribute::where(['attribute_group_id'=>$attribute_group_id, 'name'=>$color['title']])->first();
                    if (!$attribute){
                        $attribute = Attribute::create([
                            'attribute_group_id' => $attribute_group_id,
                            'name' => $color['title'],
                            'value' => $color['hex_code'] ?? null,
                        ]);
                    }

                    $html = view('back.products.partials.get-price-template')->with([
                        'attributeGroups' => $attributeGroups,
                        'attributeID' => $attribute->id,
                        'sellers' => $sellers,
                        'price' => $colorPrice, // استفاده از قیمت اختصاصی رنگ
                        'rowCount' => $rowCount,
                        'warehouses' => $warehouses,
                    ])->render();

                    $colors[] = response()->json([$html]);
                    $rowCount++;
                }
            } else {
                $html = view('back.products.partials.get-price-template')->with([
                    'attributeGroups' => $attributeGroups,
                    'attributeID' => null,
                    'sellers' => $sellers,
                    'price' => $defaultPrice,
                    'rowCount' => 1,
                    'warehouses' => $warehouses,
                ])->render();

                $colors[] = response()->json([$html]);
            }

            // ==================== پردازش وزن ====================
            if (isset($result['data']['product']['specifications'][0]['attributes'])) {
                $get_weights = collect($result['data']['product']['specifications'][0]['attributes'])
                    ->where('title', 'وزن')
                    ->first();

                if ($get_weights && isset($get_weights['values'][0])){
                    $weights = explode('گرم', $get_weights['values'][0]);
                    if (count($weights)){
                        $weights = $weights[0];
                        $weight = explode('کیلو', $weights);
                        if (count($weight) > 1){
                            $weight = trim($weight[0]) * 1000;
                        } else {
                            $weight = trim($weight[0]);
                        }
                    }
                }
            }

            // ==================== پردازش برند ====================
            $brand = null;
            if(isset($result['data']['product']['brand'])){
                $brand1 = Brand::where('name', $result['data']['product']['brand']['code'])->first();

                if($brand1){
                    $brand = $brand1;
                } else if (isset($result['data']['product']['brand']['logo']['url'][0])) {
                    $imageUrlBase = $result['data']['product']['brand']['logo']['url'][0];
                    if (strpos($imageUrlBase, '?') !== false) {
                        $t = explode('?', $imageUrlBase);
                        $imageName = basename($t[0]);
                    } else {
                        $imageName = basename($imageUrlBase);
                    }

                    if(!is_dir('uploads/brands')){
                        mkdir("uploads/brands", 0777, true);
                    }

                    $path = 'uploads/brands/' . $imageName;

                    if (file_exists($path)) {
                        unlink($path);
                    }

                    $this->resizeAndSaveSiteImage($imageUrlBase, $path, 500, 500);

                    $brand = Brand::create([
                        'name' => $result['data']['product']['brand']['code'],
                        'name_en' => $result['data']['product']['brand']['title_en'] ?? null,
                        'image' => $path,
                        'slug' => $result['data']['product']['brand']['code'],
                    ]);
                }
            }

            // ==================== پردازش نظرات ====================
            $review = [];
            if (isset($result['data']['product']['expert_reviews']['review_sections'])) {
                foreach ($result['data']['product']['expert_reviews']['review_sections'] as $reviews){
                    if (isset($reviews['sections'])) {
                        foreach ($reviews['sections'] as $sections){
                            if (isset($sections['template'])) {
                                if ($sections['template'] == "text" && isset($sections['text'])){
                                    $title = '<p style="font-weight: 700;font-size: 1.6rem;line-height: 2.15;color: #080a38;">' . ($reviews['title'] ?? '') . '</p>';
                                    $review_text = $title . $sections['text'];
                                    $review[] = $review_text;
                                }

                                if ($sections['template'] == "image" && isset($sections['image'])){
                                    $review_image = '<img style="width: 100%;" src="' . $sections['image'] . '">';
                                    $review[] = $review_image;
                                }
                            }
                        }
                    }
                }
            }

            // ==================== پردازش مشخصات ====================
            $specifications = [];
            if (isset($result['data']['product']['specifications']) && count($result['data']['product']['specifications'])){
                $rowCount = 1;
                foreach ($result['data']['product']['specifications'] as $specification){
                    $attributes_top = [];
                    if (isset($result['data']['product']['review']['attributes'])){
                        $attributes_top = $result['data']['product']['review']['attributes'];
                    }

                    $html = view('back.products.partials.get-attributes-template')->with([
                        'attributes' => $specification,
                        'rowCount' => $rowCount,
                        'attributes_top' => $attributes_top,
                        'title' => $result['data']['product']['category']['title_fa'] ?? '',
                    ])->render();

                    $specifications[] = response()->json([$html]);
                    $rowCount++;
                }
            }

            // ==================== پردازش سئو ====================
            $seoData = [];
            if (isset($result['data']['seo'])) {
                $seo = $result['data']['seo'];
                $seoData = [
                    'meta_title' => $seo['title'] ?? null,
                    'meta_description' => $seo['description'] ?? null,
                    'meta_keywords' => $result['data']['product']['category']['title_fa'] ?? null,
                    'og_title' => $seo['open_graph']['title'] ?? null,
                    'og_description' => $seo['open_graph']['description'] ?? null,
                    'og_image' => $seo['open_graph']['image'] ?? null,
                    'og_url' => $seo['open_graph']['url'] ?? null,
                    'twitter_title' => $seo['twitter_card']['title'] ?? null,
                    'twitter_description' => $seo['twitter_card']['description'] ?? null,
                    'twitter_image' => $seo['twitter_card']['image'] ?? null,
                    'canonical_url' => $seo['open_graph']['url'] ?? null,
                ];
            }

            $weightValue = 0;
            if (!empty($weight)) {
                // اگر آرایه است
                if (is_array($weight)) {
                    $weightValue = trim(round((float) reset($weight)));
                }
                // اگر رشته با کاما یا فاصله است
                elseif (is_string($weight) && (str_contains($weight, ',') || str_contains($weight, ' '))) {
                    $firstNumber = explode(',', $weight)[0];
                    $firstNumber = explode(' ', $firstNumber)[0];
                    $weightValue = trim(round((float) $firstNumber));
                }
                // عدد معمولی
                else {
                    $weightValue = trim(round((float) $weight));
                }
            }

            return [
                'product' => $result['data']['product'],
                'seo' => $seoData,
                'category' => $category,
                'color_prices' => $colorPrices, // لیست قیمت هر رنگ
                'colors' => $colors,
                'brands' => $brand,
                'price' => $defaultPrice,
                'images' => $images,
                'image' => $image,
                'weight' => $weightValue,
                'description' => $review,
                'specifications' => $specifications,
            ];

        } catch (\Exception $e) {
            Log::error('Digikala API Error: ' . $e->getMessage(), [
                'siteCode' => $siteCode,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'error' => $e->getMessage(),
                'product' => null,
                'seo' => [],
                'category' => null,
                'color_prices' => [],
                'colors' => [],
                'brands' => null,
                'price' => 0,
                'images' => [],
                'image' => null,
                'weight' => 0,
                'description' => [],
                'specifications' => [],
            ];
        }
    }

    protected static function requestTranslation($source, $target, $text)
    {
        $url = "https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";
        $fields = array(
            'sl' => urlencode($source),
            'tl' => urlencode($target),
            'q' => urlencode($text)
        );

        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }

        rtrim($fields_string, '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // امنیتی: گواهی TLS تأیید شود
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // امنیتی: تطابق هاست گواهی
        curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');

        $result = curl_exec($ch);

        curl_close($ch);
        return $result;
    }

    protected static function getSentencesFromJSON($json)
    {
        $sentencesArray = json_decode($json, true);
        $sentences = "";
        if (!empty($sentencesArray["sentences"])) {
            foreach ($sentencesArray["sentences"] as $s) {
                if (!empty($s["trans"])) {
                    $sentences .= $s["trans"];
                }
            }
        }
        return $sentences;
    }


    public function indexPricesGroup()
    {
        $this->authorize('products.pricesGroup');
        $categories      = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        $brands      = Brand::detectLang()->orderBy('created_at')->get();
        return view('back.products.prices-group', compact(
            'categories',
            'brands',
        ));
    }

    public function updatePricesGroup(Request $request)
    {
        $request->validate([
            'type' => 'required|in:product,category,brand',
            'typeChenge' => 'required|in:increase,decrease',
            'percent' => 'nullable|numeric|min:0|max:100|required_without_all:price',
            'price' => 'nullable|numeric|required_without_all:percent',
        ]);

        // اعتبارسنجی بر اساس نوع تغییر
        switch ($request->type) {
            case "product":
                $request->validate(['products' => 'required|array'], ['products.required' => 'فیلد محصولات الزامی است']);
                break;
            case "category":
                $request->validate(['categories' => 'required|array'], ['categories.required' => 'فیلد دسته بندی الزامی است']);
                break;
            case "brand":
                $request->validate(['brands' => 'required|array'], ['brands.required' => 'فیلد برند الزامی است']);
                break;
        }

        // دریافت لیست محصولات مرتبط با دسته‌ها
        if ($request->type === "category") {
            $products = \App\Models\Product::whereIn('products.category_id', $request->categories)
                ->orWhereHas('categories', function ($q) use ($request) {
                    $q->whereIn('category_product.category_id', $request->categories);
                })
                ->pluck('products.id');
        }

        // دریافت قیمت‌های مرتبط از جدول `prices`
        $query = Price::query();

        if ($request->type === "product") {
            $query->whereIn('product_id', $request->products);
        } elseif ($request->type === "category") {
            $query->whereIn('product_id', $products);
        } elseif ($request->type === "brand") {
            $query->whereHas('product', function ($q) use ($request) {
                $q->whereIn('brand_id', $request->brands);
            });
        }

        // حذف مواردی که `seller_id` دارند (مگر `sellerPrice` فعال باشد)
        if (!$request->has('sellerPrice') || !$request->sellerPrice) {
            $query->whereNull('seller_id');
        }

        // دریافت قیمت‌های فیلتر شده
        $prices = $query->get();
        $affectedCount = $prices->count();

        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // ذخیره اطلاعات تغییرات برای لاگ
        $oldPrices = [];
        $newPrices = [];
        $typeText = '';
        $targetNames = [];

        // دریافت نام هدف‌ها برای لاگ
        if ($request->type === "product") {
            $typeText = 'محصولات';
            $products_list = Product::whereIn('id', $request->products)->pluck('title')->toArray();
            $targetNames = $products_list;
        } elseif ($request->type === "category") {
            $typeText = 'دسته‌بندی‌ها';
            $categories_list = Category::whereIn('id', $request->categories)->pluck('title')->toArray();
            $targetNames = $categories_list;
        } elseif ($request->type === "brand") {
            $typeText = 'برندها';
            $brands_list = Brand::whereIn('id', $request->brands)->pluck('name')->toArray();
            $targetNames = $brands_list;
        }

        // اعمال تغییر قیمت و ذخیره تغییرات
        foreach ($prices as $price) {
            $oldPrice = $price->regular_price;

            if ($request->filled('percent')) {
                $changeAmount = ($price->regular_price * $request->percent) / 100;
            } else {
                $changeAmount = $request->price;
            }

            if ($request->typeChenge === "increase") {
                $newPrice = $price->regular_price + $changeAmount;
            } else {
                $newPrice = $price->regular_price - $changeAmount;
            }

            // اطمینان از عدم منفی شدن قیمت
            $newPrice = max(0, $newPrice);

            $productTitle = $price->product->title ?? 'محصول نامشخص';
            $variationInfo = '';
            if ($price->attributes->count() > 0) {
                $variationInfo = ' (' . $price->attributes->pluck('name')->implode(' - ') . ')';
            }

            $key = $productTitle . $variationInfo;
            $oldPrices[$key] = number_format($oldPrice) . ' تومان';
            $newPrices[$key] = number_format($newPrice) . ' تومان';

            $price->regular_price = $newPrice;

            // اگر `discount` وجود دارد، `discount_price` را نیز محاسبه کن
            if (!is_null($price->discount) && $price->discount > 0) {
                $discountAmount = ($price->regular_price * $price->discount) / 100;
                $price->discount_price = $price->regular_price - $discountAmount;
            } else {
                $price->discount_price = $price->regular_price;
            }

            $price->save();
        }

        // ساخت متن لاگ
        $changeTypeText = $request->typeChenge === "increase" ? 'افزایش' : 'کاهش';
        $amountText = $request->filled('percent') ? "{$request->percent} درصد" : number_format($request->price) . ' تومان';
        $targetsText = count($targetNames) > 3 ? implode('، ', array_slice($targetNames, 0, 3)) . ' و ' . (count($targetNames) - 3) . ' مورد دیگر' : implode('، ', $targetNames);

        // ساختار استاندارد old و attributes برای properties
        $oldData = [];
        $newData = [];

        if ($affectedCount > 0) {
            // فقط نمونه‌ای از تغییرات را ذخیره کن (حداکثر 10 مورد)
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
        if (!empty($targetsText)) {
            $logMessage .= " - هدف‌ها: {$targetsText}";
        }

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('update_prices_group')
            ->withProperties([
                'action' => 'update_prices_group',
                'type' => $request->type,
                'typeChenge' => $request->typeChenge,
                'change_type' => $changeTypeText,
                'amount_type' => $request->filled('percent') ? 'percent' : 'fixed',
                'amount_value' => $request->percent ?? $request->price,
                'amount_text' => $amountText,
                'affected_count' => $affectedCount,
                'targets' => $targetNames,
                'seller_price' => $request->has('sellerPrice') && $request->sellerPrice,
                'old' => $oldData,
                'attributes' => $newData,
                'ip' => request()->ip()
            ])
            ->log($logMessage);

        session()->put('toast-success', 'تغییر قیمت‌ها با موفقیت انجام شد.');
        return response("success");
    }

    private function resizeAndSaveSiteImage($imageUrl, $savePath, $width = null, $height = null)
    {
        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // امنیتی: گواهی TLS تأیید شود
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // امنیتی: تطابق هاست گواهی
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $imageContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $imageContent === false) {
            return false;
        }

        // اگر سایز مشخص نشده
        if ($width === null && $height === null) {
            return file_put_contents($savePath, $imageContent) !== false;
        }

        // ذخیره موقت و تغییر سایز
        $tempPath = $savePath . '.tmp';
        file_put_contents($tempPath, $imageContent);

        $imageInfo = getimagesize($tempPath);
        if ($imageInfo === false) {
            unlink($tempPath);
            return false;
        }

        $mime = $imageInfo['mime'];
        switch($mime) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($tempPath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($tempPath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($tempPath);
                break;
            default:
                unlink($tempPath);
                return false;
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        if ($width && $height) {
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
        } elseif ($width) {
            $newWidth = $width;
            $newHeight = round($originalHeight * ($width / $originalWidth));
        } elseif ($height) {
            $newHeight = $height;
            $newWidth = round($originalWidth * ($height / $originalHeight));
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime == 'image/png') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        switch($mime) {
            case 'image/jpeg':
                imagejpeg($resizedImage, $savePath, 90);
                break;
            case 'image/png':
                imagepng($resizedImage, $savePath, 9);
                break;
            case 'image/webp':
                imagewebp($resizedImage, $savePath, 90);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        unlink($tempPath);

        return true;
    }

    /**
     * ثبت لاگ برای عملیات روی محصولات
     */
    private function logProductActivity($action, $product, $oldValues = null, $newValues = null, $extraData = [])
    {
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
        $productTitle = $product->title ?? $product->name ?? "#{$product->id}";

        $properties = array_merge([
            'action' => $action,
            'product_id' => $product->id,
            'product_title' => $productTitle,
            'ip' => request()->ip()
        ], $extraData);

        // ساختار استاندارد attributes و old
        if ($oldValues && $newValues) {
            $changes = [];
            foreach ($oldValues as $key => $old) {
                if (isset($newValues[$key]) && $old != $newValues[$key]) {
                    $changes[$key] = [
                        'old' => $old,
                        'new' => $newValues[$key]
                    ];
                }
            }
            if (!empty($changes)) {
                $properties['old'] = [];
                $properties['attributes'] = [];
                foreach ($changes as $key => $change) {
                    $properties['old'][$key] = $change['old'];
                    $properties['attributes'][$key] = $change['new'];
                }
            }
        } elseif ($oldValues && !$newValues) {
            $properties['old'] = $oldValues;
        } elseif (!$oldValues && $newValues) {
            $properties['attributes'] = $newValues;
        }

        $logMessage = "مدیر {$adminName} ";

        switch ($action) {
            case 'create_product':
                $logMessage .= "محصول جدید «{$productTitle}» را ایجاد کرد";
                break;
            case 'update_product':
                $logMessage .= "محصول «{$productTitle}» را ویرایش کرد";
                break;
            case 'delete_product':
                $logMessage .= "محصول «{$productTitle}» را حذف کرد";
                break;
            case 'update_prices_group':
                $logMessage .= "تغییر گروهی قیمت محصولات را انجام داد";
                break;
            case 'export_products':
                $logMessage .= "خروجی محصولات را دریافت کرد";
                break;
            default:
                $logMessage .= $action;
        }

        activity()
            ->performedOn($product)
            ->causedBy(auth('adminPanel')->user())
            ->withProperties($properties)
            ->log($logMessage);
    }
    /**
     * دریافت عنوان فارسی فیلدهای محصول
     */

    /**
     * دریافت تغییرات قیمت‌ها بدون ثبت لاگ
     */
    private function getProductPriceChanges(Product $product, Request $request)
    {
        if ($product->isDownload()) {
            return [];
        }

        if (!isset($request->prices) || !is_array($request->prices) || !count($request->prices)) {
            return [];
        }

        $priceChanges = [];

        foreach ($request->prices as $index => $priceData) {
            if (!isset($priceData['price']) || !isset($priceData['stock'])) {
                continue;
            }

            $attributes = $this->extractAttributes($priceData['attributes'] ?? []);
            $attributes = $this->validateAndFixDuplicateColors($attributes, $index);

            $sellerId = $priceData['seller_id'] ?? null;
            $warehouseId = $priceData['warehouse'] ?? null;
            $newStock = $priceData['stock'] ?? 0;
            $currentPriceId = $priceData['id'] ?? null;

            $existingPrice = $this->findExistingPrice(
                $product,
                $attributes,
                $sellerId,
                $priceData['warehouse'] ?? null,
                $currentPriceId
            );

            $attributesNames = '';
            if (!empty($attributes)) {
                $attrs = Attribute::whereIn('id', $attributes)->pluck('name')->toArray();
                $attributesNames = implode(' - ', $attrs);
            }
            $attributesNames = $attributesNames ?: 'بدون ویژگی';

            if ($existingPrice) {
                if ($existingPrice->price != $priceData['price']) {
                    $priceChanges[] = [
                        'attributes' => $attributesNames,
                        'field' => 'قیمت',
                        'old' => number_format($existingPrice->price) . ' تومان',
                        'new' => number_format($priceData['price']) . ' تومان'
                    ];
                }
                if ($existingPrice->stock != $newStock) {
                    $priceChanges[] = [
                        'attributes' => $attributesNames,
                        'field' => 'موجودی',
                        'old' => $existingPrice->stock,
                        'new' => $newStock
                    ];
                }
                if (($existingPrice->discount ?? 0) != ($priceData['discount'] ?? 0)) {
                    $priceChanges[] = [
                        'attributes' => $attributesNames,
                        'field' => 'تخفیف',
                        'old' => ($existingPrice->discount ?? 0) . '%',
                        'new' => ($priceData['discount'] ?? 0) . '%'
                    ];
                }
            }
        }

        return $priceChanges;
    }

    private function getProductFieldTitle($key)
    {
        $titles = [
            'title' => 'عنوان محصول',
            'title_en' => 'عنوان انگلیسی',
            'category_id' => 'دسته‌بندی',
            'brand_id' => 'برند',
            'price' => 'قیمت',
            'stock' => 'موجودی',
            'discount' => 'تخفیف',
            'published' => 'وضعیت انتشار',
            'description' => 'توضیحات',
            'short_description' => 'توضیحات کوتاه',
            'special' => 'ویژه',
            'status' => 'وضعیت',
            'weight' => 'وزن',
            'unit' => 'واحد',
            'slug' => 'نامک',
            'meta_title' => 'عنوان سئو',
            'meta_description' => 'توضیحات سئو',
            'image' => 'تصویر اصلی',
            'seller_id' => 'فروشنده',
            'type' => 'نوع محصول',
            'shipping_nature' => 'ماهیت ارسال',
            'publish_date' => 'تاریخ انتشار',
            'special_end_date' => 'تاریخ پایان ویژه',
            'currency_id' => 'واحد پول',
            'rounding_amount' => 'مقدار گرد کردن',
            'rounding_type' => 'نوع گرد کردن',
        ];
        return $titles[$key] ?? str_replace('_', ' ', $key);
    }
}

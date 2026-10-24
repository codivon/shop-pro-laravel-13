<?php

namespace Themes\WeblakShop\src\Controllers\sellers;

use App\Events\SellerProductCreated as EventsSellerProductCreated;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\Price;
use App\Models\User;
use App\Notifications\Product\SellerProductCreated;
use App\Notifications\Product\SellerProductUpdate;
use App\Notifications\Sms\NewSellerCodeSent;
use Illuminate\Support\Facades\Notification;
use Themes\WeblakShop\src\Requests\seller_panel\products\StoreProductRequest;
use Themes\WeblakShop\src\Requests\seller_panel\products\UpdateProductRequest;
use App\Http\Resources\Datatable\Product\ProductCollection;
use App\Models\Address;
use App\Models\AttributeGroup;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Currency;
use App\Models\Favorite;
use App\Models\Label;
use App\Models\Product;
use App\Models\ProductAbilityScore;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerVariant;
use App\Models\SizeType;
use App\Models\Specification;
use App\Models\SpecificationGroup;
use App\Models\SpecType;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\Jalalian;


class ProductSellerController extends Controller
{
    public function index()
    {
        if (seller()->status_documents!="Accept"){
            session()->put('toast-warning', 'مدارک شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        if (seller()->status_work!="ACTIVE"){
            session()->put('toast-warning', 'اظلاعات شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        $categories      = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        return view('front::sellers.panel.products.index',compact('categories'));
    }

    public function apiIndex(Request $request)
    {
        //$this->authorize('products.index');

        if (seller()->status_documents!="Accept"){
            session()->put('toast-warning', 'مدارک شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        if (seller()->status_work!="ACTIVE"){
            session()->put('toast-warning', 'اظلاعات شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        $seller_product_id=SellerVariant::where('seller_id',seller_info()->seller_id)->get()->pluck('product_id')->toArray();
        $products = Product::with('category','lowestPrice','brand')->detectLang()->whereIN('id',$seller_product_id)->datatableFilter($request);
        $count=$products->count();
        $products = datatable($request, $products,$count);
        return new ProductCollection($products);
    }



    public function find()
    {
        if (seller()->status_documents!="Accept"){
            session()->put('toast-warning', 'مدارک شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        if (seller()->status_work!="ACTIVE"){
            session()->put('toast-warning', 'اظلاعات شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }

        $categories      = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        return view('front::sellers.panel.products.find',compact('categories'));
    }

    public function apiIndexFind(Request $request)
    {
        // فقط اگر کاربر ادمین است، authorize را اجرا کن
        if (auth()->guard('adminPanel')->check()) {
            $this->authorize('products.index');
        }

        // اگر فروشنده است، اجازه دسترسی کامل بده
        $products = Product::with('category','lowestPrice')
            ->published()
            ->detectLang()
            ->datatableFilter($request);

        $count = $products->count();
        $products = datatable($request, $products, $count);
        $pro = new ProductCollection($products);

        return $pro; // یا dd($pro->get());
    }
    public function create(Request $request)
    {
        if (seller()->status_documents!="Accept"){
            session()->put('toast-warning', 'مدارک شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        if (seller()->status_work!="ACTIVE"){
            session()->put('toast-warning', 'اظلاعات شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        $categories      = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        $specTypes       = SpecType::detectLang()->get();
        $sizetypes       = SizeType::detectLang()->get();
        $attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->get();
        $currencies      = Currency::latest()->get();
        $sellers         = Seller::where(['status_register'=>'complete','status_documents'=>'Accept','status_work'=>'ACTIVE'])->get();
        $brands          = Brand::all();
        $copy_product = $request->product ? Product::where('slug', $request->product)->first() : null;

        return view('front::sellers.panel.products.create', compact(
            'categories',
            'specTypes',
            'sizetypes',
            'attributeGroups',
            'copy_product',
            'currencies',
            'sellers',
            'brands',
        ));
    }


    public function store(StoreProductRequest $request)
    {
        if (seller()->status_documents!="Accept"){
            session()->put('toast-warning', 'مدارک شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        if (seller()->status_work!="ACTIVE"){
            session()->put('toast-warning', 'اظلاعات شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        $product = Product::create([
            'seller_id'          => seller()->id,
            'title'              => $request->title,
            'title_en'           => $request->title_en,
            'category_id'        => $request->category_id,
            'spec_type_id'       => spec_type($request),
            'size_type_id'       => $request->size_type_id,
            'weight'             => $request->weight,
            'unit'               => $request->unit,
            'price_type'         => "multiple-price",
            'type'               => $request->type,
            'shipping_nature'    => $request->shipping_nature,
            'description'        => sanitize_rich_text($request->description),
            'short_description'  => sanitize_rich_text($request->short_description),
            'special'            => $request->special ? true : false,
            'slug'               => $request->slug ?: $request->title,
            'meta_title'         => $request->meta_title,
            'image_alt'          => $request->image_alt,
            'meta_description'   => $request->meta_description,
            'published'          => 0,
            'publish_date'       => $request->publish_date ? Jalalian::fromFormat('Y-m-d H:i:s', $request->publish_date)->toCarbon() : null,
            'currency_id'        => $request->currency_id,
            'rounding_amount'    => $request->rounding_amount,
            'rounding_type'      => $request->rounding_type,
            'lang'               => app()->getLocale(),
            'status'             => "Waiting",
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
        $this->updateProductAbilityScore($product,$request);

        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new SellerProductCreated(seller(),$product));

        session()->put('toast-success','محصول با موفقیت ایجاد شد.');
        return response("success");
    }

    public function edit(Product $product)
    {
        if ($product->seller_id!=seller_info()->seller_id){
            abort(404);
        }
        $categories      = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        $specTypes       = SpecType::detectLang()->get();
        $sizetypes       = SizeType::detectLang()->get();
        $attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->get();
        $currencies      = Currency::latest()->get();
        $AbilityScores   = ProductAbilityScore::where('product_id',$product->id)->orderBy('ordering')->get();
        $sellers         = Seller::with('seller_info')->where(['status_register'=>'complete','status_documents'=>'Accept','status_work'=>'ACTIVE'])->get();
        $brands          = Brand::all();
        return view('front::sellers.panel.products.edit', compact(
            'product',
            'categories',
            'specTypes',
            'sizetypes',
            'attributeGroups',
            'currencies',
            'AbilityScores',
            'sellers',
            'brands',
        ));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        if ($product->seller_id!=seller_info()->seller_id){
            abort(404);
        }
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
            'shipping_nature'    => $request->shipping_nature,
            'description'        => sanitize_rich_text($request->description),
            'short_description'  => sanitize_rich_text($request->short_description),
            'special'            => $request->special ? true : false,
            'slug'               => $request->slug ?: $request->title,
            'meta_title'         => $request->meta_title,
            'image_alt'          => $request->image_alt,
            'meta_description'   => $request->meta_description,
            'published'          => 0,
            'publish_date'       => $request->publish_date ? Jalalian::fromFormat('Y-m-d H:i:s', $request->publish_date)->toCarbon() : null,
            'currency_id'        => $request->currency_id,
            'rounding_amount'    => $request->rounding_amount,
            'rounding_type'      => $request->rounding_type,
            'status'             => "Waiting",
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
        $this->updateProductAbilityScore($product,$request);

        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new SellerProductUpdate(seller(),$product));

        session()->put('toast-success','محصول با موفقیت ویرایش شد.');
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

    public function variant(Request $request)
    {
        if (seller()->status_documents!="Accept"){
            session()->put('toast-warning', 'مدارک شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        if (seller()->status_work!="ACTIVE"){
            session()->put('toast-warning', 'اظلاعات شما در حال برسی می باشد.');
            return redirect()->route('seller.dashboard');
        }
        $product=Product::find($request->product_id);
        if (!$product){
            abort(404);
        }
        $attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->get();
        return view('front::sellers.panel.products.variant',compact('product','attributeGroups'));
    }

    public function variant_store(Request $request, Product $product)
    {
        // اعتبارسنجی اولیه
        if (!isset($request->prices) || !is_array($request->prices) || !count($request->prices)) {
            session()->put('toast-warning', 'حداقل یک تنوع اضافه کنید.');
            return redirect()->back();
        }

        $currentSellerId = seller_info()->id;
        $currentSellerIdForAttr = seller_info()->seller_id;

        $pricesToKeep = [];
        $hasError = false;

        foreach ($request->prices as $index => $priceData) {
            // اعتبارسنجی فیلدهای اجباری
            if (empty($priceData['stock']) || empty($priceData['price'])) {
                session()->put('toast-error', 'قیمت و موجودی نمی توانند خالی باشند.');
                return redirect()->back();
            }

            // پردازش تاریخ انقضای تخفیف
            $time = null;
            if (isset($priceData['discount_expire']) && $priceData['discount_expire']) {
                try {
                    $time = Carbon::instance(
                        Jalalian::fromFormat('Y-m-d H:i:s', $priceData['discount_expire'])->toCarbon()
                    )->toDateTimeString();
                } catch (\Exception $e) {
                    \Log::error("Date parsing error: " . $e->getMessage());
                }
            }

            // پردازش ویژگی‌ها
            $attributes = array_filter($priceData['attributes'] ?? []);
            sort($attributes);

            // اعتبارسنجی: هر قیمت فقط یک رنگ می‌تواند داشته باشد
            $colorCount = 0;
            $colorNames = [];
            foreach ($attributes as $attributeId) {
                $attribute = Attribute::find($attributeId);
                if ($attribute && $attribute->group && $attribute->group->type == 'color') {
                    $colorCount++;
                    $colorNames[] = $attribute->name;
                }
            }

            if ($colorCount > 1) {
                session()->put('toast-error', 'هر قیمت نمی‌تواند بیش از یک رنگ داشته باشد. رنگ‌های انتخاب شده: ' . implode('، ', $colorNames));
                return redirect()->back();
            }

            // اضافه کردن seller_id به آرایه ویژگی‌ها برای یکتایی
            $attributesWithSeller = $attributes;
            if (count($attributesWithSeller)) {
                $attributesWithSeller[] = $currentSellerIdForAttr;
            }

            // جستجوی قیمت موجود
            $existingPrice = null;
            $sellerPrices = $product->prices()
                ->where('seller_id', $currentSellerId)
                ->withTrashed()
                ->get();

            foreach ($sellerPrices as $productPrice) {
                // گرفتن ویژگی‌های قیمت موجود (فقط برای این فروشنده)
                $productPriceAttrs = DB::table('attribute_price')
                    ->where('price_id', $productPrice->id)
                    ->where('seller_id', $currentSellerIdForAttr)
                    ->pluck('attribute_id')
                    ->toArray();

                sort($productPriceAttrs);

                // اضافه کردن seller_id برای مقایسه
                $productPriceAttrsWithSeller = $productPriceAttrs;
                if (count($productPriceAttrsWithSeller)) {
                    $productPriceAttrsWithSeller[] = $currentSellerIdForAttr;
                }

                if ($attributesWithSeller == $productPriceAttrsWithSeller) {
                    $existingPrice = $productPrice;
                    break;
                }
            }

            // ویژگی‌های نهایی (بدون seller_id)
            $finalAttributes = $attributes;

            if ($existingPrice) {
                // به‌روزرسانی قیمت موجود
                $existingPrice->createChange(
                    $priceData["price"],
                    $priceData["discount"] ?? 0,
                    $priceData["stock"]
                );

                $existingPrice->update([
                    "price"              => $priceData["price"],
                    "discount"           => $priceData["discount"] ?? 0,
                    "discount_price"     => get_discount_price($priceData["price"], $priceData["discount"] ?? 0, $product),
                    "regular_price"      => get_discount_price($priceData["price"], 0, $product),
                    "stock"              => $priceData["stock"],
                    "cart_max"           => $priceData["cart_max"] ?? 1,
                    "cart_min"           => $priceData["cart_min"] ?? 1,
                    "discount_expire_at" => $priceData["discount"] ? $time : null,
                    "deleted_at"         => null,
                    "published"          => $priceData["published"] ?? true,
                ]);

                // همگام‌سازی ویژگی‌ها (حذف قدیمی و اضافه جدید)
                DB::table('attribute_price')
                    ->where('price_id', $existingPrice->id)
                    ->where('seller_id', $currentSellerIdForAttr)
                    ->delete();

                foreach ($finalAttributes as $attributeId) {
                    DB::table('attribute_price')->insert([
                        'attribute_id' => $attributeId,
                        'price_id' => $existingPrice->id,
                        'seller_id' => $currentSellerIdForAttr,
                        'product_id' => $product->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $pricesToKeep[] = $existingPrice->id;

            } else {
                // ایجاد قیمت جدید
                $newPrice = $product->prices()->create([
                    "seller_id"           => $currentSellerId,
                    "price"               => $priceData["price"],
                    "discount"            => $priceData["discount"] ?? 0,
                    "discount_price"      => get_discount_price($priceData["price"], $priceData["discount"] ?? 0, $product),
                    "regular_price"       => get_discount_price($priceData["price"], 0, $product),
                    "stock"               => $priceData["stock"],
                    "cart_max"            => $priceData["cart_max"] ?? 1,
                    "cart_min"            => $priceData["cart_min"] ?? 1,
                    "discount_expire_at"  => $priceData["discount"] ? $time : null,
                    "published"           => $priceData["published"] ?? true,
                ]);

                // اتصال ویژگی‌ها
                foreach ($finalAttributes as $attributeId) {
                    DB::table('attribute_price')->insert([
                        'attribute_id' => $attributeId,
                        'price_id' => $newPrice->id,
                        'seller_id' => $currentSellerIdForAttr,
                        'product_id' => $product->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // ثبت تغییرات اولیه
                $newPrice->createChange($priceData["price"], $priceData["discount"] ?? 0);
                $newPrice->createChange($priceData["price"], $priceData["discount"] ?? 0, $priceData["stock"]);

                $pricesToKeep[] = $newPrice->id;
            }
        }

        // ========== مدیریت SellerVariant ==========
        $sellerVariant = SellerVariant::where([
            'product_id' => $product->id,
            'seller_id' => $currentSellerId
        ])->first();

        if (!$sellerVariant) {
            SellerVariant::create([
                'product_id' => $product->id,
                'seller_id'  => $currentSellerId,
                'prices_id'  => json_encode($pricesToKeep),
            ]);
            session()->put('toast-success', 'اکنون شما هم فروشنده این کالا هستید.');
        } else {
            $sellerVariant->update([
                'prices_id' => json_encode($pricesToKeep)
            ]);
            session()->put('toast-success', 'تنوع ها با موفقیت ویرایش شدند.');
        }

        // ========== حذف قیمت‌های اضافی این فروشنده ==========
        $extraPrices = $product->prices()
            ->where('seller_id', $currentSellerId)
            ->whereNotIn('id', $pricesToKeep)
            ->get();

        foreach ($extraPrices as $extraPrice) {
            // حذف ارتباطات ویژگی‌ها
            DB::table('attribute_price')
                ->where('price_id', $extraPrice->id)
                ->where('seller_id', $currentSellerIdForAttr)
                ->delete();

            // حذف قیمت
            $extraPrice->forceDelete();
        }

        // ========== پاکسازی سبد خرید ==========
        DB::table('cart_product')
            ->where('product_id', $product->id)
            ->whereNotNull('price_id')
            ->whereNotIn('price_id', $pricesToKeep)
            ->delete();

        // ========== به‌روزرسانی seller_variants برای سایر فروشندگان ==========
        $this->updateAllSellerVariants($product);

        return redirect()->route('seller.products.index');
    }

    /**
     * به‌روزرسانی seller_variants برای همه فروشندگان این محصول
     */
    private function updateAllSellerVariants(Product $product)
    {
        // گرفتن همه فروشنده‌های این محصول
        $sellerIds = Price::where('product_id', $product->id)
            ->whereNotNull('seller_id')
            ->distinct()
            ->pluck('seller_id');

        foreach ($sellerIds as $sellerId) {
            $priceIds = Price::where('product_id', $product->id)
                ->where('seller_id', $sellerId)
                ->pluck('id')
                ->toArray();

            SellerVariant::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'seller_id' => $sellerId
                ],
                [
                    'prices_id' => json_encode($priceIds)
                ]
            );
        }

        // فروشنده null (مدیر سایت)
        $nullSellerPriceIds = Price::where('product_id', $product->id)
            ->whereNull('seller_id')
            ->pluck('id')
            ->toArray();

        if (!empty($nullSellerPriceIds)) {
            SellerVariant::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'seller_id' => null
                ],
                [
                    'prices_id' => json_encode($nullSellerPriceIds)
                ]
            );
        }
    }

    private function updateProductPrices(Product $product, Request $request)
    {
        if ($product->isDownload()) {
            return;
        }

        $currentSellerId = seller_info()->id;
        $currentSellerIdForAttr = seller_info()->seller_id;

        $pricesToKeep = [];

        foreach ($request->prices as $index => $price) {
            // اعتبارسنجی اولیه
            if (empty($price['stock']) || empty($price['price'])) {
                session()->put('toast-error', 'قیمت و موجودی نمی توانند خالی باشند.');
                return redirect()->back();
            }

            // پردازش تاریخ انقضا
            $time = null;
            if (isset($price['discount_expire']) && $price['discount_expire']) {
                try {
                    $time = Carbon::instance(
                        Jalalian::fromFormat('Y-m-d H:i:s', $price['discount_expire'])->toCarbon()
                    )->toDateTimeString();
                } catch (\Exception $e) {
                    \Log::error("Date parsing error: " . $e->getMessage());
                }
            }

            // پردازش ویژگی‌ها
            $attributes = array_filter($price['attributes'] ?? []);
            sort($attributes);

            // اضافه کردن seller_id به انتهای آرایه ویژگی‌ها (برای یکتایی)
            $attributesWithSeller = $attributes;
            if (count($attributesWithSeller)) {
                $attributesWithSeller[] = $currentSellerIdForAttr;
            }

            // جستجوی قیمت موجود
            $existingPrice = $this->findExistingPrice($product, $attributesWithSeller, $currentSellerId);

            // ویژگی‌های نهایی (بدون seller_id)
            $finalAttributes = $attributes;

            if ($existingPrice) {
                // به‌روزرسانی قیمت موجود
                $this->updateExistingPrice($existingPrice, $price, $product, $time, $finalAttributes, $currentSellerIdForAttr);
                $pricesToKeep[] = $existingPrice->id;
            } else {
                // ایجاد قیمت جدید
                $newPrice = $this->createNewPrice($product, $price, $time, $finalAttributes, $currentSellerId, $currentSellerIdForAttr);
                $pricesToKeep[] = $newPrice->id;
            }
        }

        // حذف قیمت‌های اضافی این فروشنده
        $this->deleteExtraPrices($product, $pricesToKeep, $currentSellerId);

        // به‌روزرسانی سبد خرید
        $this->cleanupCart($product, $pricesToKeep);

        // به‌روزرسانی seller_variants
        $this->updateSellerVariants($product, $currentSellerId);

        session()->put('toast-success', 'قیمت‌ها با موفقیت به‌روزرسانی شد.');
        return true;
    }

    /**
     * پیدا کردن قیمت موجود
     */
    private function findExistingPrice($product, $attributesWithSeller, $currentSellerId)
    {
        $prices = $product->prices()
            ->where('seller_id', $currentSellerId)
            ->withTrashed()
            ->get();

        foreach ($prices as $price) {
            // گرفتن ویژگی‌های قیمت
            $priceAttributes = DB::table('attribute_price')
                ->where('price_id', $price->id)
                ->where('seller_id', $currentSellerId)
                ->pluck('attribute_id')
                ->toArray();

            sort($priceAttributes);

            // مقایسه با seller_id در انتهای آرایه
            $priceAttributesWithSeller = $priceAttributes;
            if (count($priceAttributesWithSeller)) {
                $priceAttributesWithSeller[] = $currentSellerId;
            }

            if ($attributesWithSeller == $priceAttributesWithSeller) {
                return $price;
            }
        }

        return null;
    }

    /**
     * به‌روزرسانی قیمت موجود
     */
    private function updateExistingPrice($price, $data, $product, $time, $finalAttributes, $sellerId)
    {
        // ثبت تغییرات
        $price->createChange(
            $data["price"],
            $data["discount"] ?? 0,
            $data["stock"]
        );

        // به‌روزرسانی فیلدها
        $price->update([
            "price"              => $data["price"],
            "discount"           => $data["discount"] ?? 0,
            "discount_price"     => get_discount_price($data["price"], $data["discount"] ?? 0, $product),
            "regular_price"      => get_discount_price($data["price"], 0, $product),
            "stock"              => $data["stock"],
            "cart_max"           => $data["cart_max"] ?? 1,
            "cart_min"           => $data["cart_min"] ?? 1,
            "discount_expire_at" => $data["discount"] ? $time : null,
            "deleted_at"         => null,
            "published"          => $data["published"] ?? true,
        ]);

        // همگام‌سازی ویژگی‌ها
        // ابتدا حذف ارتباطات قبلی این فروشنده
        DB::table('attribute_price')
            ->where('price_id', $price->id)
            ->where('seller_id', $sellerId)
            ->delete();

        // افزودن ارتباطات جدید
        foreach ($finalAttributes as $attributeId) {
            DB::table('attribute_price')->insert([
                'attribute_id' => $attributeId,
                'price_id' => $price->id,
                'seller_id' => $sellerId,
                'product_id' => $product->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \Log::info("Updated price ID: {$price->id} for seller: {$sellerId}");
    }

    /**
     * ایجاد قیمت جدید
     */
    private function createNewPrice($product, $data, $time, $finalAttributes, $currentSellerId, $sellerIdForAttr)
    {
        // ایجاد قیمت جدید
        $newPrice = $product->prices()->create([
            "seller_id"           => $currentSellerId,
            "price"               => $data["price"],
            "discount"            => $data["discount"] ?? 0,
            "discount_price"      => get_discount_price($data["price"], $data["discount"] ?? 0, $product),
            "regular_price"       => get_discount_price($data["price"], 0, $product),
            "stock"               => $data["stock"],
            "cart_max"            => $data["cart_max"] ?? 1,
            "cart_min"            => $data["cart_min"] ?? 1,
            "discount_expire_at"  => $data["discount"] ? $time : null,
            "published"           => $data["published"] ?? true,
        ]);

        // افزودن ویژگی‌ها
        foreach ($finalAttributes as $attributeId) {
            DB::table('attribute_price')->insert([
                'attribute_id' => $attributeId,
                'price_id' => $newPrice->id,
                'seller_id' => $sellerIdForAttr,
                'product_id' => $product->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ثبت تغییرات اولیه
        $newPrice->createChange($data["price"], $data["discount"] ?? 0);
        $newPrice->createChange($data["price"], $data["discount"] ?? 0, $data["stock"]);

        \Log::info("Created new price ID: {$newPrice->id} for seller: {$currentSellerId}");

        return $newPrice;
    }

    /**
     * حذف قیمت‌های اضافی این فروشنده
     */
    private function deleteExtraPrices($product, $pricesToKeep, $currentSellerId)
    {
        $extraPrices = $product->prices()
            ->where('seller_id', $currentSellerId)
            ->whereNotIn('id', $pricesToKeep)
            ->get();

        foreach ($extraPrices as $price) {
            // حذف ارتباطات ویژگی‌ها
            DB::table('attribute_price')
                ->where('price_id', $price->id)
                ->where('seller_id', $currentSellerId)
                ->delete();

            // حذف قیمت
            $price->forceDelete();

            \Log::info("Deleted extra price ID: {$price->id} for seller: {$currentSellerId}");
        }
    }

    /**
     * پاکسازی سبد خرید از قیمت‌های حذف شده
     */
    private function cleanupCart($product, $pricesToKeep)
    {
        DB::table('cart_product')
            ->where('product_id', $product->id)
            ->whereNotNull('price_id')
            ->whereNotIn('price_id', $pricesToKeep)
            ->delete();
    }

    /**
     * به‌روزرسانی جدول seller_variants
     */
    private function updateSellerVariants($product, $currentSellerId)
    {
        // گرفتن قیمت‌های این فروشنده
        $sellerPriceIds = Price::where('product_id', $product->id)
            ->where('seller_id', $currentSellerId)
            ->pluck('id')
            ->toArray();

        // به‌روزرسانی یا ایجاد seller_variant
        $sellerVariant = SellerVariant::where([
            'product_id' => $product->id,
            'seller_id' => $currentSellerId
        ])->first();

        if ($sellerVariant) {
            $sellerVariant->update([
                'prices_id' => json_encode($sellerPriceIds)
            ]);
        } else {
            SellerVariant::create([
                'product_id' => $product->id,
                'seller_id' => $currentSellerId,
                'prices_id' => json_encode($sellerPriceIds),
            ]);
        }

        \Log::info("Updated seller_variant for seller: {$currentSellerId}, prices: " . json_encode($sellerPriceIds));
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
                    "regular_price"     => get_discount_price($price["price"], 0, $product),
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
                        "regular_price"   => get_discount_price($price["price"], $price["discount"], $product),
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
        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $file = $request->image;
            $name = uniqid() . '_' . $product->id . '.' . $file->getClientOriginalExtension();
            $request->image->storeAs('products', $name);

            $product->image = '/uploads/products/' . $name;
            $product->save();
        }

        $product_images = $product->gallery()->pluck('image')->toArray();
        $images         = explode(',', $request->images);
        $deleted_images = array_diff($product_images, $images);

        foreach ($deleted_images as $del_img) {
            $del_img = $product->gallery()->where('image', $del_img)->first();

            if (!$del_img) {
                continue;
            }

            if (Storage::disk('public')->exists($del_img)) {
                Storage::disk('public')->delete($del_img);
            }

            $del_img->delete();
        }

        $ordering = 1;

        if ($request->images) {

            foreach ($images as $image) {

                if (Storage::exists('tmp/' . $image)) {

                    Storage::move('tmp/' . $image, 'products/' . $image);

                    $product->gallery()->create([
                        'image'    => '/uploads/products/' . $image,
                        'ordering' => $ordering++,
                    ]);
                } else {
                    $product->gallery()->where('image', $image)->update([
                        'ordering' => $ordering++,
                    ]);
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

    private function updateProductAbilityScore(Product $product,Request $request)
    {

        $ordering = 0;
        if ($request->AbilityProductScore) {
            if (ProductAbilityScore::where('product_id', $product->id)->exists()) {
                ProductAbilityScore::where('product_id', $product->id)->delete();
            }
            foreach ($request->AbilityProductScore as $specification) {

                $Score=new ProductAbilityScore();
                $Score->product_id= $product->id;
                $Score->name= $specification['name'];
                $Score->value= $specification['value'];
                $Score->ordering=$ordering;
                $Score->save();
                $ordering++;
            }
        }
    }
}

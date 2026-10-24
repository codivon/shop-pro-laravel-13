<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Datatable\Seller\SellerCollection;
use App\Models\Carrier;
use App\Models\Category;
use App\Models\City;
use App\Models\Filter;
use App\Models\Filterable;
use App\Models\Gateway;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Models\Tariff;
use App\Models\Widget;
use App\Services\HolidayService;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class  MainController extends Controller
{
    protected $holidayService;
    public function __construct(HolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }

    public function index()
    {
        $widgets = Widget::with('options')
            ->where('theme', current_theme_name())
            ->where('is_active', true)
            ->where('page', 'home')
            ->orderBy('ordering')
            ->get();

        return view('front::index', compact('widgets'));
    }

    public function checkout()
    {
        $cart = auth()->user()->cart;
        $gateways = Gateway::active()->orderBy('ordering')->get();

        if (!$cart || !$cart->products->count() || !check_cart_quantity()) {
            return redirect()->route('front.cart');
        }

        $addresses = auth()->user()->addresses()->orderBy('active', 'desc')->take(3)->get();
        $discount_status = check_cart_discount();
        $provinces = Province::active()->orderBy('ordering')->get();
        $wallet = auth()->user()->getWallet();

        $defaultAddress = auth()->user()->addresses()->where('active', 1)->first();
        $cityId = $defaultAddress ? $defaultAddress->city_id : null;
        $provinceId = $defaultAddress ? $defaultAddress->province_id : null;

        // ========== جدا کردن محصولات فیزیکی و دانلودی ==========
        $physicalProducts = $cart->products->filter(fn($p) => $p->type === 'physical');
        $downloadProducts = $cart->products->filter(fn($p) => $p->type === 'download');

        // ========== دسته‌بندی محصولات فیزیکی بر اساس seller_id ==========
        $sellerGroups = [];
        $sellerCounter = 1;

        foreach ($physicalProducts as $product) {
            $price = Price::with(['attributes', 'seller'])->find($product->pivot->price_id);
            if (!$price) continue;

            $sellerId = $price->seller_id;
            $groupId = $sellerId ? 'seller_' . $sellerId : 'store';

            $variants = [];
            foreach ($price->attributes as $attribute) {
                $variants[] = [
                    'type' => $attribute->group->type,
                    'name' => $attribute->name,
                    'value' => $attribute->value,
                    'color_code' => $attribute->type == 'color' ? $attribute->value : null
                ];
            }

            $productData = [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->image ? asset($product->image) : asset('empty.svg'),
                'quantity' => $product->pivot->quantity,
                'price' => $price->price,
                'final_price' => $price->discount_price,
                'discount' => $price->discount ?? null,
                'discount_expire_at' => $price->discount_expire_at,
                'weight' => ($product->weight ?? 0) * $product->pivot->quantity,
                'price_id' => $price->id,
                'variants' => $variants,
            ];

            if (!isset($sellerGroups[$groupId])) {
                if ($sellerId) {
                    $seller = Seller::find($sellerId);
                    $sellerInfo = $seller ? [
                        'id' => $seller->id,
                        'name' => $seller->seller_info->business_name ?? $seller->name,
                        'logo' => $seller->seller_info->logo ? asset($seller->seller_info->logo) : null,
                        'slug' => $seller->slug
                    ] : null;
                } else {
                    $sellerInfo = null;
                }

                $selectedCarrierId = null;
                $carrierKey = $groupId == 'store' ? 'carrier_id_store' : 'carrier_id_' . $groupId;
                if (isset($selectedCarriers[$carrierKey])) {
                    $selectedCarrierId = $selectedCarriers[$carrierKey];
                }

                $sellerGroups[$groupId] = [
                    'number' => $sellerCounter++,
                    'seller_id' => $sellerId,
                    'seller_info' => $sellerInfo,
                    'is_store' => is_null($sellerId),
                    'name' => $sellerInfo ? $sellerInfo['name'] : option('info_site_title'),
                    'logo' => $sellerInfo ? $sellerInfo['logo'] : option('info_icon', asset('favicon.ico')),
                    'products' => [],
                    'total_weight' => 0,
                    'total_price' => 0,
                    'carriers' => [],
                    'selected_carrier' => null,
                    'selected_carrier_id' => $selectedCarrierId,
                    'shipping_cost' => 0,
                    'delivery_info' => null
                ];
            }

            $sellerGroups[$groupId]['products'][] = $productData;
            $sellerGroups[$groupId]['total_weight'] += $productData['weight'];
            $sellerGroups[$groupId]['total_price'] += $productData['final_price'] * $productData['quantity'];
        }

        // ========== دریافت روش‌های ارسال فقط برای گروه‌های فیزیکی ==========
        foreach ($sellerGroups as $groupId => &$group) {
            $carriers = Carrier::active()
                ->when($group['is_store'], function($query) {
                    return $query->whereNull('seller_id');
                })
                ->when(!$group['is_store'], function($query) use ($group) {
                    return $query->where('seller_id', $group['seller_id']);
                })
                ->get();

            $availableCarriers = [];
            foreach ($carriers as $carrier) {
                $shippingCost = $this->calculateCarrierCost($carrier, $group['total_weight'], $cityId);
                $availableCarriers[] = [
                    'id' => $carrier->id,
                    'title' => $carrier->title,
                    'image' => $carrier->image ? asset($carrier->image) : null,
                    'description' => $carrier->description,
                    'shipping_cost' => $shippingCost,
                    'is_free' => $shippingCost == 0,
                    'delivery_type' => $carrier->delivery_time_type,
                    'delivery_text' => $carrier->delivery_time_type == 'default'
                        ? ($carrier->default_delivery_range ?? 'ارسال در 3 الی 6 روز کاری')
                        : null,
                    'delivery_dates' => $carrier->delivery_time_type == 'user_select'
                        ? $this->getDeliveryDates($carrier)
                        : null,
                    'carrige_forward' => $carrier->carrige_forward ?? false
                ];
            }

            $group['carriers'] = $availableCarriers;

            if (count($availableCarriers) > 0) {
                $group['selected_carrier'] = $availableCarriers[0];
                $group['shipping_cost'] = $availableCarriers[0]['shipping_cost'];
                $group['delivery_info'] = $availableCarriers[0]['delivery_text'];
            }
        }

        // ========== آماده‌سازی محصولات دانلودی برای نمایش ==========
        $downloadItems = [];
        foreach ($downloadProducts as $product) {
            $price = Price::with(['attributes'])->find($product->pivot->price_id);
            if (!$price) continue;

            $variants = [];
            foreach ($price->attributes as $attribute) {
                $variants[] = [
                    'type' => $attribute->group->type,
                    'name' => $attribute->name,
                    'value' => $attribute->value,
                    'color_code' => $attribute->type == 'color' ? $attribute->value : null
                ];
            }

            $downloadItems[] = [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->image ? asset($product->image) : asset('empty.svg'),
                'quantity' => $product->pivot->quantity,
                'price' => $price->price,
                'final_price' => $price->discount_price,
                'discount' => $price->discount ?? null,
                'variants' => $variants,
            ];
        }

        return view('front::carts.checkout', compact(
            'provinces',
            'discount_status',
            'gateways',
            'wallet',
            'cityId',
            'provinceId',
            'addresses',
            'sellerGroups',
            'downloadItems'
        ));
    }

    private function calculateCarrierCost($carrier, $weight, $cityId)
    {
        // ارسال رایگان برای وزن بالاتر
        if ($carrier->free_shipping_weight && $weight >= $carrier->free_shipping_weight) {
            return 0;
        }

        $destinationCity = City::find($cityId);
        if (!$destinationCity) {
            return false;
        }

        $destinationProvinceId = $destinationCity->province_id;
        $carrierProvinceId = $carrier->province_id; // استان فروشنده/انبار


        // بررسی داخل استان یا خارج استان (مقایسه استان فروشنده با استان مقصد)
        $isWithinProvince = ($destinationProvinceId == $carrierProvinceId);
        $type = $isWithinProvince ? 'within_province' : 'extra_province';

        $tariff = Tariff::where('carrier_id', $carrier->id)
            ->where('type', $type)
            ->where('max_weight', '>=', $weight)
            ->orderBy('max_weight', 'asc')
            ->first();

        if ($tariff) {
            return $tariff->shipping_cost;
        }

        if ($carrier->extra_cost) {
            return $weight * $carrier->extra_cost;
        }

        return false;
    }
    private function getDeliveryDates($carrier)
    {
        $rangeDays = (int) $carrier->user_select_ranges;
        if (!$rangeDays || $rangeDays <= 0) {
            $rangeDays = 7;
        }

        $startDate = now()->addDays($carrier->start_days_after_order);
        $dates = [];

        for ($i = 0; $i < $rangeDays; $i++) {
            $currentDate = clone $startDate;
            $currentDate->addDays($i);

            $jalali = \Morilog\Jalali\Jalalian::fromDateTime($currentDate);
            $jalaliDate = $jalali->format('Y/m/d');
            $isFriday = $currentDate->dayOfWeek == 5;
            $isHoliday = $this->isHoliday($currentDate);

            $isSelectable = true;
            if ($carrier->disable_fridays && $isFriday) $isSelectable = false;
            if ($carrier->disable_holidays && $isHoliday) $isSelectable = false;

            $dates[] = [
                'date' => $currentDate->format('Y-m-d'),
                'jalali' => $jalaliDate,
                'display' => $jalali->format('l j F Y'),
                'displayDate' => $jalali->format('j F Y'),
                'day_name' => $jalali->format('l'),
                'is_friday' => $isFriday,
                'is_holiday' => $isHoliday,
                'is_selectable' => $isSelectable
            ];
        }

        return $dates;
    }
    private function isHoliday($date)
    {
        $jalali = \Morilog\Jalali\Jalalian::fromDateTime($date);
        $jalaliDate = $jalali->format('Y/m/d');

        // استفاده از HolidayService
        return $this->holidayService->isHoliday($jalaliDate);
    }

    public function getPrices(Request $request)
    {
        $cart = auth()->user()->cart;

        if ($request->city_id) {
            $request->validate([
                'city_id' => 'required|exists:cities,id',
            ]);
        }
        $defaultAddress = auth()->user()->addresses()->where('active', 1)->first();
        $cityId = $request->city_id ?? ($defaultAddress ? $defaultAddress->city_id : null);

        // ========== دریافت روش‌های ارسال انتخابی ==========
        $selectedCarriers = $request->input('carriers', []);

        // ========== جدا کردن محصولات فیزیکی و دانلودی ==========
        $physicalProducts = $cart->products->filter(fn($p) => $p->type === 'physical');
        $downloadProducts = $cart->products->filter(fn($p) => $p->type === 'download');

        // ========== دسته‌بندی محصولات فیزیکی ==========
        $sellerGroups = [];
        $sellerCounter = 1;
        $totalShippingCost = 0;
        $subtotal = 0; // قیمت اصلی (بدون تخفیف)
        $sellerShippingCosts = [];

        foreach ($physicalProducts as $product) {
            $price = Price::with(['attributes', 'seller'])->find($product->pivot->price_id);
            if (!$price) continue;

            $sellerId = $price->seller_id;
            $groupId = $sellerId ? 'seller_' . $sellerId : 'store';

            // دریافت ویژگی‌ها
            $variants = [];
            foreach ($price->attributes as $attribute) {
                $variants[] = [
                    'type' => $attribute->group->type ?? null,
                    'name' => $attribute->name,
                    'value' => $attribute->value,
                    'color_code' => $attribute->type == 'color' ? $attribute->value : null
                ];
            }

            $finalPrice = $price->discount_price ?? $price->price;

            $productData = [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->image ? asset($product->image) : asset('empty.svg'),
                'quantity' => $product->pivot->quantity,
                'price' => $price->price, // قیمت اصلی
                'final_price' => $finalPrice, // قیمت نهایی با تخفیف (برای نمایش)
                'discount' => $price->discount ?? null,
                'weight' => ($product->weight ?? 0) * $product->pivot->quantity,
                'price_id' => $price->id,
                'variants' => $variants,
            ];

            // ایجاد یا به‌روزرسانی گروه فروشنده
            if (!isset($sellerGroups[$groupId])) {
                if ($sellerId) {
                    $seller = Seller::find($sellerId);
                    $sellerInfo = $seller ? [
                        'id' => $seller->id,
                        'name' => $seller->seller_info->business_name ?? $seller->name,
                        'logo' => $seller->seller_info->logo ? asset($seller->seller_info->logo) : null,
                        'slug' => $seller->slug
                    ] : null;
                } else {
                    $sellerInfo = null;
                }

                // روش ارسال انتخاب شده برای این گروه
                $selectedCarrierId = null;
                $carrierKey = $groupId == 'store' ? 'carrier_id_store' : 'carrier_id_seller_' . ($sellerId ?? '');

                if (isset($selectedCarriers[$carrierKey])) {
                    $selectedCarrierId = $selectedCarriers[$carrierKey];
                }

                $sellerGroups[$groupId] = [
                    'number' => $sellerCounter++,
                    'seller_id' => $sellerId,
                    'seller_info' => $sellerInfo,
                    'is_store' => is_null($sellerId),
                    'name' => $sellerInfo ? $sellerInfo['name'] : option('info_site_title'),
                    'logo' => $sellerInfo ? $sellerInfo['logo'] : option('info_icon', asset('favicon.ico')),
                    'products' => [],
                    'total_weight' => 0,
                    'total_price' => 0,
                    'carriers' => [],
                    'selected_carrier_id' => $selectedCarrierId,
                    'shipping_cost' => 0,
                    'delivery_info' => null
                ];
            }

            $sellerGroups[$groupId]['products'][] = $productData;
            $sellerGroups[$groupId]['total_weight'] += $productData['weight'];
            $sellerGroups[$groupId]['total_price'] += $finalPrice * $productData['quantity'];
            $subtotal += $price->price * $productData['quantity']; // قیمت اصلی
        }

        // ========== آماده‌سازی محصولات دانلودی برای نمایش ==========
        $downloadItems = [];
        foreach ($downloadProducts as $product) {
            $price = Price::with(['attributes'])->find($product->pivot->price_id);
            if (!$price) continue;

            $variants = [];
            foreach ($price->attributes as $attribute) {
                $variants[] = [
                    'type' => $attribute->group->type ?? null,
                    'name' => $attribute->name,
                    'value' => $attribute->value,
                    'color_code' => $attribute->type == 'color' ? $attribute->value : null
                ];
            }

            $finalPrice = $price->discount_price ?? $price->price;

            $downloadItems[] = [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->image ? asset($product->image) : asset('empty.svg'),
                'quantity' => $product->pivot->quantity,
                'price' => $price->price,
                'final_price' => $finalPrice,
                'discount' => $price->discount ?? null,
                'variants' => $variants,
            ];

            $subtotal += $price->price * $product->pivot->quantity; // قیمت اصلی
        }

        // ========== دریافت روش‌های ارسال و محاسبه هزینه برای هر گروه (فقط فیزیکی) ==========
        foreach ($sellerGroups as $groupId => &$group) {
            $carriers = Carrier::active()
                ->when($group['is_store'], function($query) {
                    return $query->whereNull('seller_id');
                })
                ->when(!$group['is_store'], function($query) use ($group) {
                    return $query->where('seller_id', $group['seller_id']);
                })
                ->get();

            $availableCarriers = [];
            $selectedCarrier = null;

            foreach ($carriers as $carrier) {
                $shippingCost = $this->calculateCarrierCost($carrier, $group['total_weight'], $cityId);

                $carrierData = [
                    'id' => $carrier->id,
                    'title' => $carrier->title,
                    'image' => $carrier->image ? asset($carrier->image) : null,
                    'description' => $carrier->description,
                    'shipping_cost' => $shippingCost !== false ? $shippingCost : 0,
                    'is_free' => ($shippingCost !== false && $shippingCost == 0),
                    'delivery_type' => $carrier->delivery_time_type,
                    'delivery_text' => $carrier->delivery_time_type == 'default'
                        ? ($carrier->default_delivery_range ?? 'ارسال در 3 الی 6 روز کاری')
                        : null,
                    'delivery_dates' => $carrier->delivery_time_type == 'user_select'
                        ? $this->getDeliveryDates($carrier)
                        : null,
                    'carrige_forward' => $carrier->carrige_forward ?? false
                ];

                if ($shippingCost !== false) {
                    $availableCarriers[] = $carrierData;

                    if ($group['selected_carrier_id'] == $carrier->id) {
                        $selectedCarrier = $carrierData;
                        $group['shipping_cost'] = $shippingCost;
                        $group['delivery_info'] = $carrierData['delivery_text'];
                        $totalShippingCost += $shippingCost;

                        $sellerName = $group['name'];
                        $sellerShippingCosts[$sellerName] = ($sellerShippingCosts[$sellerName] ?? 0) + $shippingCost;
                    }
                }
            }

            $group['carriers'] = $availableCarriers;

            if (!$selectedCarrier && count($availableCarriers) > 0) {
                $selectedCarrier = $availableCarriers[0];
                $group['selected_carrier_id'] = $selectedCarrier['id'];
                $group['shipping_cost'] = $selectedCarrier['shipping_cost'];
                $group['delivery_info'] = $selectedCarrier['delivery_text'];
                $totalShippingCost += $selectedCarrier['shipping_cost'];

                $sellerName = $group['name'];
                $sellerShippingCosts[$sellerName] = ($sellerShippingCosts[$sellerName] ?? 0) + $selectedCarrier['shipping_cost'];
            }

            $group['selected_carrier'] = $selectedCarrier;
        }

        // فاز D (۵۳): تخفیف با helper سانیار — با داده real (regular_price=NULL)
        // متد میزبان totalDiscount منفی می‌شد و finalPrice را دوبرابر می‌کرد
        $discount = saniar_cart_discount($cart);
        $finalPrice = $subtotal - $discount + $totalShippingCost;

        $AllDeliveryDates = [];
        foreach ($request->carriers as $key => $carrier_id) {
            $carrierDelivery = Carrier::find($carrier_id);
            if ($carrierDelivery && $carrierDelivery->delivery_time_type == "user_select") {
                $deliveryDates = $this->getDeliveryDates($carrierDelivery);
                $AllDeliveryDates[$key] = $deliveryDates;
            }
        }

        $deliveryDateForOne = [];
        if ($request->has('idGroupSelect') && $request->idGroupSelect != null) {
            $carrierG = 'carrier_id_' . $request->idGroupSelect;
            $carrier_id = $request->carriers[$carrierG] ?? null;
            if ($carrier_id) {
                $carrierDelivery = Carrier::find($carrier_id);
                $deliveryDates = [];
                if ($carrierDelivery && $carrierDelivery->delivery_time_type == "user_select") {
                    $deliveryDates = $this->getDeliveryDates($carrierDelivery);
                }
                $groupIdOne = $request->idGroupSelect;
                $deliveryDateForOne = [
                    'deliveryDateForOne' => $deliveryDates,
                    'groupId' => $groupIdOne,
                    'carrier_id' => $carrier_id,
                ];
            }
        }

        return [
            'checkout_sidebar' => view('front::carts.partials.checkout-sidebar', [
                'cart' => $cart,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'totalShippingCost' => $totalShippingCost,
                'sellerShippingCosts' => $sellerShippingCosts,
                'finalPrice' => $finalPrice,
                'downloadItems' => $downloadItems,
            ])->render(),

            'carriers_container' => view('front::carts.partials.carriers-container', [
                'cart' => $cart,
                'cityId' => $cityId,
                'sellerGroups' => $sellerGroups,
                'AllDeliveryDates' => $AllDeliveryDates,
                'request_carrier' => $request->carriers,
                'downloadItems' => $downloadItems,
            ])->render(),

            'deliveryDateForOne' => view('front::carts.partials.delivery-dates-for-one', [
                'deliveryDateForOne' => $deliveryDateForOne,
            ])->render(),
        ];
    }

    public function orderResultInfo(Order $order)
    {
        if ($order->user_id != auth()->user()->id) {
            abort(404);
        }
        if (session('transaction-error') or session('message')){
            session()->put('showOrderResultInfo','success');
        }

        if (!session('showOrderResultInfo')){
            return redirect()->route('front.orders.show', ['order' => $order]);
        }
        return view('front::carts.orderResultInfo',compact('order'));
    }

    public function showStore()
    {
        abort(404);
    }
    public function showSellerStore(Seller $seller,Request $request)
    {
        if(option('multi_vendor_system_status','false')=="false"){
            abort(404);
        }
        if (!$seller->checkIsFullActive()){
            abort(404);
        }

        $seller_info=SellerInfo::where('seller_id',$seller->id)->first();
        $products_id=[];
        $categoryId=[];
        $variants=DB::table('seller_variants')->where(['seller_id' =>$seller->id])->get();
        foreach ($variants as $variant){
            array_push($products_id,$variant->product_id);
        }

        $products  = Product::orderByStock()->whereIn('products.id', $products_id)->filter($request,$products_id)->paginate(20);
        $min_price = Price::where('stock', '>', 0)->whereIn('product_id', $products_id)->min('price');
        $max_price = Price::where('stock', '>', 0)->whereIn('product_id', $products_id)->max('price');

        $has_filter=Filter::where('is_seller', true)->first();;
        $filterable=[];
        if ($has_filter){
            $filterable=Filterable::where('filter_id',$has_filter->id)->orderBy('ordering')->get();
        }

        $category_products=DB::table('category_product')->whereIn('product_id',$products_id)->get();

        foreach ($category_products as $category_product){
            array_push($categoryId,$category_product->category_id);
        }

        $categorise=Category::whereIn('id',$categoryId)->get();
        return view('front::sellerStore',compact('seller','seller_info','products', 'min_price', 'max_price','has_filter','filterable','products_id','categorise'));
    }

}

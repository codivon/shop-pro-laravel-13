<?php

namespace App\Models;

use App\Events\WalletAmountDecreased;
use App\Events\WalletAmountIncreased;
use App\Notifications\Wallet\WalletAmountDecreasedSms;
use App\Support\MultiVendorSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\InstallmentPayment\Models\InstallmentPlan;
use Morilog\Jalali\Jalalian;

class Order extends Model
{
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function sellers()
    {
        return $this->belongsToMany(Seller::class, 'order_items');
    }

    public function province()
    {
        return $this->belongsTo(Province::class)->withTrashed();
    }

    public function city()
    {
        return $this->belongsTo(City::class)->withTrashed();
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function hasPhysicalItem()
    {
        return $this->products()->where('type', 'physical')->first() ? true : false;
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items');
    }

    public function isPaid()
    {
        return $this->status == 'paid';
    }

    public function getShipStatusAttribute()
    {
        return $this->shippingStatusText();
    }

    public function shippingStatusText()
    {
        if ($this->hasPhysicalItem()) {

            if ($this->status != 'paid') {
                return 'منتظر پرداخت';
            }

            if ($this->reserved()) {
                return 'رزرو شده';
            }

            $text = '';

            switch ($this->shipping_status) {
                case 'pending':

                    $text = 'در حال بررسی';
                    break;

                case 'waiting':

                    $text = 'منتظر ارسال';
                    break;

                case 'sent':

                    $text = 'ارسال شد';
                    break;

                case 'canceled':

                    $text = 'ارسال لغو شد';
                    break;

            }

            return $text;
        }

        return 'سفارش شما شامل محصول فیزیکی نمی باشد';
    }

    public function statusText()
    {
        switch ($this->status) {
            case 'paid':

                return 'پرداخت شده';

            case 'unpaid':

                return 'پرداخت نشده';

            case 'canceled':

                return 'لغو شده';

        }
    }

    public function scopeFilter($query, Request $request)
    {
        if ($fullname = $request->input('query.fullname')) {
            $query->whereHas('user', function ($q) use ($fullname) {
                $q->WhereRaw("concat(first_name, ' ', last_name) like '%{$fullname}%' ");
            });
        }

        if ($username = $request->input('query.username')) {
            $query->whereHas('user', function ($q) use ($username) {
                $q->where('username', 'like', "%$username%");
            });
        }

        if ($product_name = $request->input('query.product_name')) {
            $query->whereHas('products', function ($q) use ($product_name) {
                $q->where('products.title', 'like', "%$product_name%");
            });
        }

        if ($product_id = $request->input('query.product_id')) {
            $query->whereHas('products', function ($q) use ($product_id) {
                $q->where('products.id', 'like', "%$product_id%");
            });
        }
        if ($request->input('query.payment_type')) {
            $paymentType = $request->input('query.payment_type');

            if ($paymentType === 'installment') {
                $query->whereHas('installmentPlan', function ($q) {
                    $q->whereIn('status', ['pending_down_payment', 'active']);
                });
            } elseif ($paymentType === 'normal') {
                $query->whereDoesntHave('installmentPlan');
            }
        }

        $status = $request->input('query.status');

        if ($status && $status != 'all') {
            $query->where('status', $status);
        }

        $shipping_status = $request->input('query.shipping_status');

        if ($shipping_status && $shipping_status != 'all') {
            $query->whereHas('items', function ($q) use ($shipping_status) {
                $q->where('shipping_status', $shipping_status);

                $isSellerRoute = request()->routeIs('seller.*');
                // اگر کاربر فروشنده است، فقط آیتم‌های خودش را نشان بده
                if ($isSellerRoute) {
                    $q->where('seller_id', sellerID());
                }
            });
        }

        if ($id = $request->input('query.id')) {
            $query->where('id', $id);
        }

        if ($from_date = $request->input('query.from_date')) {
            $from_date = Jalalian::fromFormat('Y-m-d', $from_date)->toCarbon();

            $query->whereDate('created_at', '>=', $from_date);
        }

        if ($to_date = $request->input('query.to_date')) {
            $to_date = Jalalian::fromFormat('Y-m-d', $to_date)->toCarbon();

            $query->whereDate('created_at', '<=', $to_date);
        }

        if ($request->sort) {

            switch ($request->sort['field']) {
                case 'fullname':

                    $query->join('users', 'orders.user_id', '=', 'users.id')
                        ->orderBy('users.first_name', $request->sort['sort'])
                        ->orderBy('users.last_name', $request->sort['sort'])
                        ->select('orders.*');
                    break;

                case 'order_id':

                    $query->orderBy('id', $request->sort['sort']);
                    break;

                default:

                    if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $request->sort['field'])) {
                        $query->orderBy($request->sort['field'], $request->sort['sort']);
                    }

            }
        }

        return $query;
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class)->withTrashed();
    }

    public function gatewayRelation()
    {
        return $this->belongsTo(Gateway::class, 'gateway_id');
    }

    public function priceWithoutDiscount()
    {
        foreach ($this->items()->get() as $order_item) {
            $price[] = $order_item->real_price * $order_item->quantity;
        }
        $TotalPrice = array_sum($price);

        return $TotalPrice ?: 0;
    }

    public function priceSellerWithoutDiscount($seller_id = null)
    {
        if ($seller_id) {
            $sellerId = $seller_id;
        } else {
            $sellerId = sellerID();
        }
        foreach ($this->items()->get() as $order_item) {
            if ($order_item->seller_id == $sellerId) {
                $price[] = $order_item->real_price * $order_item->quantity;
            }
        }
        $TotalPrice = array_sum($price);

        return $TotalPrice ?: 0;
    }

    public function priceSeller($seller_id = null)
    {
        if ($seller_id) {
            $sellerId = $seller_id;
        } else {
            $sellerId = sellerID();
        }
        $price = [];
        foreach ($this->items()->get() as $order_item) {
            if ($order_item->seller_id == $sellerId) {
                $price[] = $order_item->real_price * $order_item->quantity;
            }
        }

        $TotalPrice = array_sum($price);
        $price = $TotalPrice + $this->shippingCostSeller($seller_id);

        return $price - $this->totalDiscountSeller($sellerId) ?: 0;
    }

    public function totalDiscountSeller($seller_id = null)
    {

        if ($seller_id) {
            $sellerId = $seller_id;
        } else {
            $sellerId = sellerID();
        }

        $real_price = [];
        $discount = [];
        $price = [];
        foreach ($this->items()->get() as $order_item) {

            if ($order_item->seller_id == $sellerId) {
                $price[] = $order_item->price;
                $real_price[] = $order_item->real_price;
                $discount[] = $order_item->real_price - $order_item->price;
            }
        }

        $TotalPrice = array_sum($real_price);
        $TotalDiscount = array_sum($price);

        $discountPrice = $TotalDiscount;
        if ($this->discount_price or $this->discount_percent) {
            if ($this->discount_price) {
                $discount_price = $this->discount_price / count($this->items()->get());
                $discount_price = $discount_price * count($real_price);
                $discountPrice = $TotalPrice - $discount_price;
            } elseif ($this->discount_percent) {
                $discount_percent = $this->discount_percent / count($this->items()->get());
                $discount_percent = $discount_percent * count($real_price);
                $discountPrice = intval($TotalPrice * ((100 - $discount_percent) / 100));
            }
            $sum = $TotalPrice - $TotalDiscount;

            $TotalPrice = $TotalPrice - $discountPrice + $sum;

            return $TotalPrice ?: 0;
        }

        $TotalPrice = array_sum($discount);

        return $TotalPrice ?: 0;
    }

    public function shippingCostSeller($seller_id = null)
    {
        if ($seller_id) {
            $sellerId = $seller_id;
        } else {
            $sellerId = sellerID();
        }

        foreach ($this->items()->get() as $order_item) {

            if ($order_item->seller_id != null and $order_item->seller_id == $sellerId) {
                return $order_item->shipping_cost ?: 0;
            }
        }

        return 0;
    }

    public function getAmountCommission()
    {
        $priceForSite = [];
        $commission = [];
        foreach ($this->items()->get() as $order_item) {
            $price = intval($this->price * ((100 - $order_item->commission) / 100));
            $priceForSite[] = $this->price - $price;
            $commission[] = $order_item->commission;
        }
        $priceForSite = array_sum($priceForSite);
        $commission = array_sum($commission);
        $result = [
            'priceForSite' => $priceForSite,
            'commission' => $commission,
        ];

        return $result;
    }

    public function getAmountSellerCommission($seller_id = null)
    {
        if ($seller_id) {
            $sellerId = $seller_id;
        } else {
            $sellerId = sellerID();
        }
        $priceForSite = [];
        $commission = [];
        foreach ($this->items()->get() as $order_item) {
            if ($order_item->seller_id == $sellerId) {
                $priceSeller = $this->priceSeller($sellerId);
                $price = intval($priceSeller * ((100 - $order_item->commission) / 100));
                $priceForSite[] = $priceSeller - $price;
                $commission[] = $order_item->commission;
            }
        }
        $priceForSite = array_sum($priceForSite);
        $commission = array_sum($commission);
        $result = [
            'priceForSite' => $priceForSite,
            'commission' => $commission,
        ];

        return $result;
    }

    public function priceSellerDepositWallet($seller_id = null)
    {
        if ($seller_id) {
            $sellerId = $seller_id;
        } else {
            $sellerId = sellerID();
        }

        $TotalCommission = [];
        $lastCategoryId = null;

        foreach ($this->items()->get() as $order_item) {
            if ($order_item->seller_id == $sellerId) {
                $product = $order_item->product()->first();
                $commissionInfo = $this->getCategoryCommissionWithParent($product->category_id);

                // فاز ۱۰۳ — کمیسیون مؤثر: override فروشنده > دسته‌بندی > پیش‌فرض سراسری
                // (وقتی MultiVendorSettings خاموش/بدون override/بدون پیش‌فرض باشد خروجی همان زنجیرهٔ دسته‌بندی قدیمی است)
                $commissionInfo = MultiVendorSettings::resolveCommission((int) $sellerId, $commissionInfo);

                $commission = $commissionInfo['commission'];
                $categoryId = $commissionInfo['category_id'];

                $order_item->commission = $commission;
                $order_item->save();

                $TotalCommission[] = $commission;
                $lastCategoryId = $categoryId;
            }
        }

        $TotalCommission = array_sum($TotalCommission);
        $priceSeller = $this->priceSeller($sellerId);
        $price = intval($priceSeller * ((100 - $TotalCommission) / 100));

        $totalPrice = [
            'priceForSeller' => $price,
            'priceForSite' => $priceSeller - $price,
            'category_id' => $lastCategoryId,
            'percent' => $TotalCommission,
        ];

        return $totalPrice;
    }

    private function getCategoryCommissionWithParent($categoryId)
    {
        if (is_null($categoryId)) {
            return [
                'commission' => 0,
                'category_id' => null,
            ];
        }

        $category = Category::find($categoryId);

        if (! $category) {
            return [
                'commission' => 0,
                'category_id' => null,
            ];
        }

        if (isset($category->commission)) {
            if (! is_null($category->commission)) {
                return [
                    'commission' => (int) $category->commission,
                    'category_id' => $category->id,
                ];
            }
        }
        if ($category->category_id) {
            return $this->getCategoryCommissionWithParent($category->category_id);
        }

        return [
            'commission' => 0,
            'category_id' => null,
        ];
    }

    public function totalDiscount()
    {
        return $this->discount_amount ?: 0;
    }

    public function scopeNotCompleted($query)
    {
        return $query->where('status', 'paid')->whereNotIn('shipping_status', ['sent', 'canceled']);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeNotPaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    public function scopeNotCanceled($query)
    {
        return $query->where('status', '!=', 'canceled');
    }

    public function hasPhysicalProduct()
    {
        foreach ($this->products as $product) {
            if ($product->isPhysical()) {
                return true;
            }
        }

        return false;
    }

    public function payUsingWallet()
    {
        $order = $this;
        $user = $order->user;
        $wallet = $user->getWallet();

        $sellersID = [];
        $orderId = [];
        $price = [];
        foreach ($order->items()->get() as $order_item) {
            if ($order_item->seller_id) {
                $sellersID[] = $order_item->seller_id;
                $orderId[] = $order_item->order_id;
                $price[] = $order_item->real_price * $order_item->quantity;
            }
        }
        $sellersID = array_unique($sellersID);

        if ($wallet->balance() >= $order->price) {
            DB::transaction(function () use ($wallet, $order, $sellersID) {
                $order->update([
                    'status' => 'paid',
                ]);

                $wallet->histories()->create([
                    'type' => 'withdraw',
                    'amount' => $order->price,
                    'description' => 'ثبت سفارش',
                    'source' => 'user',
                    'status' => 'success',
                    'order_id' => $order->id,
                ]);

                // add wallet for seller
                if (count($sellersID)) {
                    foreach (Seller::whereIn('id', $sellersID)->get() as $sellerItem) {
                        $sellerWallet = $sellerItem->getWallet();
                        $amount = $order->priceSellerDepositWallet($sellerItem->id);
                        $sellerWallet->histories()->create([
                            'type' => 'deposit',
                            'amount' => $amount['priceForSeller'],
                            'description' => 'ثبت سفارش',
                            'source' => 'seller',
                            'status' => 'success',
                            'order_id' => $order->id,
                        ]);
                        SellerDeposit::create([
                            'seller_id' => $sellerItem->id,
                            'order_id' => $order->id,
                            'amount' => $amount['priceForSite'],
                            'category_id' => $amount['category_id'],
                            'percent' => $amount['percent'],
                            'description' => 'ثبت سفارش',
                            'status' => 'success',
                        ]);
                        $sellerWallet->refereshBalance();
                        event(new WalletAmountIncreased($sellerWallet));
                    }
                }

                // Notification::send($wallet, new WalletAmountDecreasedSms($wallet,$order->price));
                $wallet->refereshBalance();
            });

            event(new WalletAmountDecreased($wallet));

            return true;
        }

        return false;
    }

    public function walletHistory()
    {
        return $this->hasOne(WalletHistory::class)->where('status', 'success');
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class)->withTrashed();
    }

    public static function cacheKeys()
    {
        return [
            'admin.orders_count',
            'admin.total_sell',
        ];
    }

    public function reserved()
    {
        return $this->reserve;
    }

    public function scopeReserved($query)
    {
        return $query->where('reserve', true);
    }

    public function reservedOrders()
    {
        return $this->belongsToMany(Order::class, 'reserved_orders', 'order_id', 'reserved_order_id');
    }

    public function mainOrder()
    {
        return $this->belongsTo(Order::class, 'main_order_id');
    }

    public function getGroupedItemsBySeller()
    {
        // گروه‌بندی آیتم‌ها بر اساس seller_id
        $grouped = $this->items->groupBy(function ($item) {
            return $item->seller_id ?? 'no_seller';
        });

        $result = [];

        foreach ($grouped as $sellerId => $items) {
            $firstItem = $items->first();

            // اطلاعات فروشنده
            if ($sellerId != 'no_seller' && $firstItem->seller) {
                $sellerInfo = $firstItem->seller->seller_info;
                $sellerName = $sellerInfo->business_name ?? $firstItem->seller->name ?? 'فروشنده';
            } else {
                $sellerName = 'فروشگاه اصلی';
                $sellerId = null;
            }

            $result[] = [
                'seller_id' => $sellerId,
                'seller_name' => $sellerName,
                'items' => $items,
                'total_price' => $items->sum(function ($item) {
                    return ($item->price - ($item->discount ?? 0)) * $item->quantity;
                }),
                'total_shipping' => $items->sum('shipping_cost'),
                'items_count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
            ];
        }

        return $result;
    }

    public function installmentPlan()
    {
        return $this->hasOne(InstallmentPlan::class);
    }
}

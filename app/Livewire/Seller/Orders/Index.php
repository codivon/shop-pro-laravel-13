<?php

namespace App\Livewire\Seller\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * فاز ۱۰۸ — «مدیریت سفارش‌های جاری» فروشنده (پورت OrderSellerController@index + apiIndex).
 *
 * پنل قدیمی جدول را با دیتاتیبل سمت‌سرور (seller.orders.apiIndex → OrderCollection) می‌ساخت؛
 * اینجا همان داده‌ها با جدول Livewire + WithPagination رندر می‌شود و فیلترها
 * (نام/نام‌کاربری/شماره سفارش/وضعیت/وضعیت ارسال/محصول/بازهٔ تاریخ) با همان کلیدهای
 * فرم فیلتر قدیمی (resources/views/sellers-old/panel/orders/partials/filters.blade.php)
 * در URL نگه داشته می‌شوند.
 *
 * مبنای کوئری (پورت apiIndex): سفارش‌هایی که آیتمِ seller_id = فروشندهٔ فعلی دارند.
 * دکمه‌های پرینت/اکسل لینک‌های کلاسیک به همان کنترلرهای قدیمی هستند
 * (seller.orders.export / seller.orders.printAll / seller.orders.printAllShippingForms…).
 */
#[Layout('sellers.layouts.panel')]
#[Title('سفارش‌ها')]
class Index extends Component
{
    use WithPagination;

    /* ---------------- فیلترها — همان کلیدهای فرم قدیمی، در URL ---------------- */

    #[Url(as: 'fullname', except: '')]
    public string $fullname = '';

    #[Url(as: 'username', except: '')]
    public string $username = '';

    #[Url(as: 'id', except: '')]
    public string $order_id = '';

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    #[Url(as: 'shipping_status', except: 'all')]
    public string $shipping_status = 'all';

    #[Url(as: 'product_name', except: '')]
    public string $product_name = '';

    #[Url(as: 'product_id', except: '')]
    public string $product_id = '';

    /** تاریخ شمسی Y-m-d — همان فرمت فیلتر قدیمی */
    #[Url(as: 'from_date', except: '')]
    public string $from_date = '';

    #[Url(as: 'to_date', except: '')]
    public string $to_date = '';

    /* ---------------- انتخاب ردیف‌ها برای پرینت گروهی (لینک کلاسیک GET) ---------------- */

    /** شناسه سفارش‌های انتخاب‌شده — به‌صورت ids[] به روت‌های پرینت قدیمی می‌رود */
    public array $selected = [];

    public function updating(string $property): void
    {
        // تغییر هر فیلتر، صفحه‌بندی را به ابتدا برمی‌گرداند و انتخاب‌ها را پاک می‌کند
        if (in_array($property, ['fullname', 'username', 'order_id', 'status', 'shipping_status', 'product_name', 'product_id', 'from_date', 'to_date'], true)) {
            $this->resetPage();
        }

        if ($property !== 'selected') {
            $this->selected = [];
        }
    }

    /** انتخاب/لغو انتخاب همهٔ ردیف‌های صفحهٔ فعلی */
    public function toggleAll(): void
    {
        $pageIds = $this->baseQuery()->forPage($this->getPage(), 20)->pluck('id')->all();

        if (count($this->selected) === count($pageIds) && count($pageIds) > 0) {
            $this->selected = [];
        } else {
            $this->selected = $pageIds;
        }
    }

    /**
     * کوئری پایه — پورت OrderSellerController@apiIndex:
     * سفارش‌هایی که حداقل یک آیتم با seller_id فروشندهٔ فعلی دارند
     * + فیلترهای همان فرم قدیمی (status/shipping_status/نام/محصول/بازهٔ تاریخ).
     */
    private function baseQuery()
    {
        $orderIds = OrderItem::where('seller_id', sellerID())->pluck('order_id')->unique()->values();

        $query = Order::whereIn('id', $orderIds)->latest();

        // پورت scopeFilter — ورودی‌های «query.» دیتاتیبل قدیم اینجا مستقیم از پراپرتی‌ها می‌آیند
        if (trim($this->fullname) !== '') {
            $fullname = $this->fullname;
            $query->whereHas('user', fn ($q) => $q->whereRaw("concat(first_name, ' ', last_name) like ?", ['%'.$fullname.'%']));
        }

        if (trim($this->username) !== '') {
            $username = $this->username;
            $query->whereHas('user', fn ($q) => $q->where('username', 'like', '%'.$username.'%'));
        }

        if (trim($this->product_name) !== '') {
            $product_name = $this->product_name;
            $query->whereHas('products', fn ($q) => $q->where('products.title', 'like', '%'.$product_name.'%'));
        }

        if (trim($this->product_id) !== '') {
            $product_id = $this->product_id;
            $query->whereHas('products', fn ($q) => $q->where('products.id', 'like', '%'.$product_id.'%'));
        }

        if ($this->status !== '' && $this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->shipping_status !== '' && $this->shipping_status !== 'all') {
            $shipping_status = $this->shipping_status;
            // همان رفتار قدیم: وضعیت ارسال روی آیتم‌های خودِ فروشنده بررسی می‌شود
            $query->whereHas('items', fn ($q) => $q->where('shipping_status', $shipping_status)->where('seller_id', sellerID()));
        }

        if (trim($this->order_id) !== '' && ctype_digit($this->order_id)) {
            $query->where('id', (int) $this->order_id);
        }

        // بازهٔ تاریخ شمسی — همان Jalalian::fromFormat('Y-m-d') قدیم با گارد فرمت نامعتبر
        if (trim($this->from_date) !== '') {
            try {
                $from = Jalalian::fromFormat('Y-m-d', $this->from_date)->toCarbon();
                $query->whereDate('created_at', '>=', $from);
            } catch (\Throwable) {
                // فرمت نامعتبر — فیلتر نادیده گرفته می‌شود (قدیم ۵۰۰ می‌داد؛ اینجا نرم)
            }
        }

        if (trim($this->to_date) !== '') {
            try {
                $to = Jalalian::fromFormat('Y-m-d', $this->to_date)->toCarbon()->endOfDay();
                $query->whereDate('created_at', '<=', $to);
            } catch (\Throwable) {
                // فرمت نامعتبر — فیلتر نادیده گرفته می‌شود
            }
        }

        return $query;
    }

    /**
     * قیمت کل سهم فروشنده از سفارش — پورت OrderCollection:
     * جمع (real_price × quantity) آیتم‌های فروشنده + هزینهٔ ارسال فروشنده − تخفیف فروشنده.
     */
    private function sellerPrice(Order $order): int
    {
        $price = 0;

        foreach ($order->items as $item) {
            if ((int) $item->seller_id === (int) sellerID()) {
                $price += (int) $item->real_price * (int) $item->quantity;
            }
        }

        $price += (int) $order->shippingCostSeller();
        $priceSeller = $price - (int) $order->totalDiscountSeller(sellerID());

        return $priceSeller ?: 0;
    }

    /** وضعیت ارسال سهم فروشنده در سفارش (اولین آیتمِ فروشنده) */
    private function sellerShippingStatus(Order $order): ?string
    {
        $item = $order->items->firstWhere('seller_id', (int) sellerID());

        return $item->shipping_status ?? null;
    }

    public function render()
    {
        $orders = $this->baseQuery()->with(['items', 'user:id,first_name,last_name,username'])->paginate(20);

        $rows = $orders->getCollection()->map(function (Order $order) {
            return (object) [
                'id' => $order->id,
                'name' => $order->name,
                'created_at' => jdate($order->created_at)->format('%d %B %Y | H:i'),
                'priceSeller' => $this->sellerPrice($order),
                'status' => $order->status,
                'shipping_status' => $this->sellerShippingStatus($order),
            ];
        });

        return view('sellers.panel.orders.index', [
            'orders' => $orders,
            'rows' => $rows,
            // لینک‌های کلاسیک به کنترلرهای قدیمی — پرینت گروهی با ids[] انتخاب‌شده‌ها
            'printAllUrl' => route('seller.orders.printAll'),
            'printAllShippingFormsUrl' => route('seller.orders.printAllShippingForms'),
            'printAllShippingFormsMinUrl' => route('seller.orders.printAllShippingFormsMin'),
            'exportUrl' => route('seller.orders.export'),
            'notCompletedUrl' => route('seller.orders.notCompleted'),
            'todayJalali' => Jalalian::fromCarbon(Carbon::today())->format('Y/m/d'),
        ]);
    }
}

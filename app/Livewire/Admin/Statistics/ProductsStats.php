<?php

namespace App\Livewire\Admin\Statistics;

use App\Livewire\Admin\Statistics\Concerns\InteractsWithStatistics;
use App\Models\OrderItem;
use App\Rules\CheckeJdate;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * آمار فروش هر محصول — پورت ۱:۱ StatisticsController@productTemplate.
 * (اجازه‌ی statistics.product مثل کنترلر قدیمی؛ در اسپک «statistics.orders.products»
 *  نوشته شده بود اما این نام در درخت دسترسی وجود ندارد — انحراف مستند.)
 */
#[Title('آمارگیری فروش هر محصول')]
class ProductsStats extends Component
{
    use WithPagination;
    use InteractsWithStatistics;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'product_name', except: '')]
    public string $product_name = '';

    #[Url(as: 'from_date', except: '')]
    public string $from_date = '';

    #[Url(as: 'to_date', except: '')]
    public string $to_date = '';

    /** newest | oldest | most_sold | least_sold */
    #[Url(as: 'ordering', except: 'newest')]
    public string $ordering = 'newest';

    public int $perPage = 50;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('statistics.product') ?: abort(403);

        // پورت لاگ مشاهده صفحه (StatisticsController@products)
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('view')
            ->withProperties([
                'action' => 'view_statistics_products',
                'ip'     => request()->ip(),
            ])
            ->log("مدیر {$adminName} صفحه آمار محصولات را مشاهده کرد");
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['ordering', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- اعمال فیلترها (پورت validate قدیمی) ---------------- */

    public function applyFilters(): void
    {
        auth('adminPanel')->user()?->can('statistics.product') ?: abort(403);

        $this->validate([
            'from_date' => ['nullable', new CheckeJdate()],
            'to_date'   => ['nullable', new CheckeJdate()],
            'ordering'  => ['nullable', 'in:newest,oldest,most_sold,least_sold'],
        ]);

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        auth('adminPanel')->user()?->can('statistics.product') ?: abort(403);

        $this->product_name = '';
        $this->from_date = '';
        $this->to_date = '';
        $this->ordering = 'newest';
        $this->resetPage();
    }

    /* ---------------- لیست (پورت دقیق کوئری productTemplate) ---------------- */

    #[Computed]
    public function products()
    {
        $from_date = $this->from_date !== '' ? Jalalian::fromFormat('Y-m-d', $this->from_date)->toCarbon() : null;
        $to_date   = $this->to_date !== '' ? Jalalian::fromFormat('Y-m-d', $this->to_date)->toCarbon() : null;

        // Subquery برای موجودی — جلوگیری از چند برابر شدن ردیف‌ها در JOIN
        $stockSubquery = DB::table('prices')
            ->selectRaw('product_id, SUM(stock) AS total_stock')
            ->groupBy('product_id');

        return OrderItem::selectRaw('
            products.id          AS product_id,
            products.slug        AS product_slug,
            products.title       AS product_title,
            products.image       AS product_image,
            SUM(order_items.quantity) AS total_orders,
            SUM(CASE WHEN orders.status = "paid"  THEN order_items.quantity ELSE 0 END) AS successful_orders,
            SUM(CASE WHEN orders.status != "paid" THEN order_items.quantity ELSE 0 END) AS failed_orders,
            SUM(CASE WHEN DATE(orders.created_at) = CURRENT_DATE THEN order_items.quantity ELSE 0 END) AS today_orders,
            SUM(order_items.quantity * order_items.price) AS total_order_amount,
            SUM(CASE WHEN orders.status = "paid" THEN order_items.quantity * order_items.price ELSE 0 END) AS total_profit,
            COALESCE(stock_data.total_stock, 0) AS available_stock
        ')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders',   'order_items.order_id',   '=', 'orders.id')
            // LEFT JOIN با subquery به جای JOIN مستقیم (مثل قدیمی)
            ->leftJoinSub($stockSubquery, 'stock_data', function ($join) {
                $join->on('stock_data.product_id', '=', 'products.id');
            })
            ->when($this->product_name, function ($query) {
                $decodedProductName = urldecode($this->product_name);
                $keywords = explode(' ', $decodedProductName);
                foreach ($keywords as $keyword) {
                    if (trim($keyword) !== '') {
                        $query->where('products.title', 'LIKE', "%{$keyword}%");
                    }
                }
            })
            ->when($from_date, function ($query, $from_date) {
                return $query->whereDate('orders.created_at', '>=', $from_date);
            })
            ->when($to_date, function ($query, $to_date) {
                return $query->whereDate('orders.created_at', '<=', $to_date);
            })
            ->groupBy('products.id', 'products.slug', 'products.title', 'products.image', 'stock_data.total_stock')
            ->when($this->ordering, function ($query) {
                switch ($this->ordering) {
                    case 'newest':
                        return $query->orderByRaw('MAX(orders.created_at) DESC');
                    case 'oldest':
                        return $query->orderByRaw('MAX(orders.created_at) ASC');
                    case 'most_sold':
                        return $query->orderBy('total_orders', 'desc');
                    case 'least_sold':
                        return $query->orderBy('total_orders', 'asc');
                }
            })
            ->paginate($this->perPage);
    }

    /* ---------------- لاگ فیلتر (پورت دقیق productTemplate) ---------------- */

    public function logFiltered(): void
    {
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('export')
            ->withProperties([
                'action'        => 'filter_product_statistics',
                'product_name'  => $this->product_name,
                'from_date'     => $this->from_date,
                'to_date'       => $this->to_date,
                'ordering'      => $this->ordering,
                'results_count' => $this->products->total(),
                'ip'            => request()->ip(),
            ])
            ->log("مدیر {$adminName} آمار فروش محصولات را با فیلترهای مشخص شده مشاهده کرد");
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.statistics.products')
            ->layout('admin-panel::layout');
    }
}

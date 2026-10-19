<?php

namespace App\Livewire\Admin;

use App\Models\Admin;
use App\Models\Comment;
use App\Models\Order;
use App\Models\Post;
use App\Models\Price;
use App\Models\Product;
use App\Models\Review;
use App\Models\SellerInfo;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('داشبورد')]
class Dashboard extends Component
{
    public array $stats = [];

    public string $todayJalali = '';

    public array $recentOrders = [];

    public array $salesChart = [];

    public array $statusChart = [];

    public array $topProducts = [];

    /* ---------- فاز ۹-ب: داشبورد غنی ---------- */

    /** درآمد امروز + مقایسه با دیروز */
    public array $revenueStats = [];

    /** سفارش‌های در انتظار / پرداخت‌شده آماده ارسال */
    public array $orderFlags = [];

    /** محصولات ناموجود / کم‌موجودی + لیست کم‌موجودی‌ها */
    public array $inventoryStats = [];

    /** دیدگاه‌های در انتظار، کل دیدگاه‌های محصول، امتیاز متوسط + لیست آخرین دیدگاه‌ها */
    public array $commentStats = [];

    /** تیکت‌های باز / کل + لیست آخرین تیکت‌ها */
    public array $ticketStats = [];

    /** مجموع موجودی کیف پول کاربران */
    public array $walletStats = [];

    /** فروشندگان (کل + در انتظار تایید مدارک) */
    public array $sellerStats = [];

    /** نمودار ثبت‌نام کاربران ۳۰ روز اخیر */
    public array $usersChart = [];

    /** جدیدترین کاربران */
    public array $newestUsers = [];

    /** آخرین تراکنش‌ها */
    public array $latestTransactions = [];

    public function mount()
    {
        $this->todayJalali = Jalalian::now()->format('l، j F Y');

        // آمار پایه با کش ۶۰ ثانیه — سبک و سریع
        $this->stats = Cache::remember('np-dashboard-stats', 60, function () {
            return [
                'users' => User::count(),
                'products' => Product::count(),
                'orders' => Order::count(),
                'sellers' => SellerInfo::count(),
                'ordersToday' => Order::whereDate('created_at', today())->count(),
                'usersToday' => User::whereDate('created_at', today())->count(),
                'paidOrders' => Order::where('status', 'paid')->count(),
                'admins' => Admin::count(),
            ];
        });

        $this->recentOrders = Cache::remember('np-dashboard-recent-orders', 60, function () {
            return Order::query()
                ->latest('id')
                ->take(7)
                ->get(['id', 'user_id', 'name', 'mobile', 'status', 'price', 'created_at'])
                ->map(fn (Order $o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'status' => $o->status,
                    'statusLabel' => $this->orderStatusLabel($o->status),
                    'price' => $o->price,
                    'date' => Jalalian::fromDateTime($o->created_at)->format('Y/m/d'),
                ])
                ->toArray();
        });

        // داده‌های نمودارها با کش ۵ دقیقه
        $this->salesChart = $this->buildSalesChart();
        $this->statusChart = $this->buildStatusChart();
        $this->topProducts = $this->buildTopProducts();

        // ---------- فاز ۹-ب: کارت‌ها، نمودار ثبت‌نام و لیست‌های جدید (همه با کش ۶۰ ثانیه) ----------

        $this->revenueStats = $this->buildRevenue();

        $this->orderFlags = Cache::remember('np-dashboard-order-flags', 60, function () {
            return [
                // «در انتظار» بر اساس statusهای واقعی مدل سفارش (unpaid / pending_down_payment)
                'pending' => Order::query()->whereIn('status', ['unpaid', 'pending_down_payment'])->count(),
                // پرداخت‌شده که هنوز ارسال نشده — پورت scopeNotCompleted مدل
                'unshipped' => Order::query()->where('status', 'paid')->whereNotIn('shipping_status', ['sent', 'canceled'])->count(),
            ];
        });

        $this->inventoryStats = $this->buildInventory();

        $this->commentStats = $this->buildComments();

        $this->ticketStats = $this->buildTickets();

        $this->walletStats = Cache::remember('np-dashboard-wallet', 60, function () {
            return [
                'balance' => (int) Wallet::query()->whereNotNull('user_id')->sum('balance'),
            ];
        });

        $this->sellerStats = Cache::remember('np-dashboard-sellers', 60, function () {
            return [
                'total' => SellerInfo::count(),
                // status_documents: Accept / Reject / Waiting (enum واقعی جدول sellers_info)
                'waiting' => SellerInfo::query()->where('status_documents', 'Waiting')->count(),
            ];
        });

        $this->usersChart = $this->buildUsersChart();

        $this->newestUsers = $this->buildNewestUsers();

        $this->latestTransactions = $this->buildLatestTransactions();
    }

    /**
     * چک دسترسی با گارد adminPanel — الگوی sidebar پنل
     * (Gate پیش‌فرض روی گارد web است و برای ادمین کار نمی‌کند)
     */
    public function can(string $permission): bool
    {
        return (bool) auth('adminPanel')->user()?->can($permission);
    }

    /**
     * نمودار فروش ۳۰ روز اخیر (تعداد سفارش + مبلغ پرداخت‌شده)
     */
    protected function buildSalesChart(): array
    {
        return Cache::remember('np-dashboard-sales-chart', 300, function () {
            $days = 30;

            $orders = Order::query()
                ->where('created_at', '>=', now()->subDays($days)->startOfDay())
                ->get(['id', 'price', 'status', 'created_at']);

            $labels = [];
            $counts = [];
            $revenues = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = today()->subDays($i);
                $labels[] = Jalalian::fromDateTime($date)->format('m/d');

                $dayOrders = $orders->filter(fn ($o) => $o->created_at->isSameDay($date));
                $counts[] = $dayOrders->count();
                $revenues[] = (int) $dayOrders->where('status', 'paid')->sum('price');
            }

            return [
                'labels' => $labels,
                'orders' => $counts,
                'revenues' => $revenues,
            ];
        });
    }

    /**
     * نمودار دایره‌ای وضعیت سفارشات
     */
    protected function buildStatusChart(): array
    {
        return Cache::remember('np-dashboard-status-chart', 300, function () {
            $rows = Order::query()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $labels = [];
            $values = [];

            foreach ($rows as $status => $count) {
                $labels[] = $this->orderStatusLabel($status);
                $values[] = (int) $count;
            }

            return [
                'labels' => $labels,
                'values' => $values,
            ];
        });
    }

    /**
     * پرفروش‌ترین محصولات (۳۰ روز اخیر)
     */
    protected function buildTopProducts(): array
    {
        return Cache::remember('np-dashboard-top-products', 300, function () {
            $rows = \App\Models\OrderItem::query()
                ->whereHas('order', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->selectRaw('product_id, sum(quantity) as sold')
                ->whereNotNull('product_id')
                ->with('product:id,title')
                ->groupBy('product_id')
                ->orderByDesc('sold')
                ->limit(5)
                ->get();

            return $rows->map(fn ($row) => [
                'title' => $row->product?->title ?? 'محصول حذف‌شده',
                'sold' => (int) $row->sold,
            ])->values()->toArray();
        });
    }

    /* ============================ فاز ۹-ب: سازنده‌های داده ============================ */

    /**
     * درآمد امروز (سفارش‌های paid) + مقایسه درصدی با دیروز
     */
    protected function buildRevenue(): array
    {
        return Cache::remember('np-dashboard-revenue', 60, function () {
            $today = (int) Order::query()->where('status', 'paid')->whereDate('created_at', today())->sum('price');
            $yesterday = (int) Order::query()->where('status', 'paid')->whereDate('created_at', today()->subDay())->sum('price');

            $direction = 'flat';
            $percent = null;

            if ($yesterday > 0) {
                $percent = (int) round((($today - $yesterday) / $yesterday) * 100);
                $direction = $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat');
            } elseif ($today > 0) {
                $direction = 'up';
            }

            return [
                'today' => $today,
                'yesterday' => $yesterday,
                'percent' => $percent,
                'direction' => $direction,
            ];
        });
    }

    /**
     * محصولات ناموجود / کم‌موجودی + لیست ۶ محصول کم‌موجودی (نام، کمترین موجودی تنوع، قیمت)
     * یک کوئری زیرکوئری‌دار برای لیست + دو aggregate — بدون N+1
     */
    protected function buildInventory(): array
    {
        return Cache::remember('np-dashboard-inventory', 60, function () {
            // کمترین موجودیِ تنوع‌های در-استوک هر محصول (subquery همبسته — جدول prices با SoftDeletes)
            $minStockSql = '(select min(p.stock) from prices p where p.product_id = products.id and p.stock > 0 and p.deleted_at is null)';

            $items = Product::query()
                ->select(['id', 'title'])
                ->selectRaw($minStockSql.' as min_stock')
                ->whereRaw($minStockSql.' is not null')
                ->whereRaw($minStockSql.' <= 5')
                ->orderByRaw('min_stock asc')
                ->limit(6)
                ->get();

            // کمترین قیمت فروش تنوع‌های در-استوک همین ۶ محصول — محاسبه با همان ریاضی اکسسور Price
            $minPrice = [];

            if ($items->isNotEmpty()) {
                Price::query()
                    ->whereIn('product_id', $items->pluck('id'))
                    ->where('stock', '>', 0)
                    ->get(['id', 'product_id', 'price', 'discount'])
                    ->each(function (Price $price) use (&$minPrice) {
                        $effective = (int) $price->discount_price; // اکسسور مدل: price - price*discount/100

                        if (! isset($minPrice[$price->product_id]) || $effective < $minPrice[$price->product_id]) {
                            $minPrice[$price->product_id] = $effective;
                        }
                    });
            }

            return [
                'outOfStock' => Product::query()->whereDoesntHave('prices', fn ($q) => $q->where('stock', '>', 0))->count(),
                'lowStock' => Product::query()->whereRaw($minStockSql.' is not null')->whereRaw($minStockSql.' <= 5')->count(),
                'items' => $items->map(fn (Product $product) => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'stock' => (int) $product->min_stock,
                    'price' => $minPrice[$product->id] ?? 0,
                ])->values()->toArray(),
            ];
        });
    }

    /**
     * دیدگاه‌ها: در انتظار تایید (محصول + مطلب)، کل دیدگاه‌های محصول، امتیاز متوسط + لیست آخرین دیدگاه‌ها
     */
    protected function buildComments(): array
    {
        return Cache::remember('np-dashboard-comments', 60, function () {
            $items = Comment::query()
                ->whereIn('commentable_type', [Product::class, Post::class])
                ->whereNull('comment_id')
                ->with([
                    'user:id,first_name,last_name,username',
                    'commentable:id,title',
                ])
                ->latest('id')
                ->take(6)
                ->get(['id', 'user_id', 'commentable_type', 'commentable_id', 'status', 'body', 'created_at']);

            return [
                // در انتظار تایید — همان semantics صفحه دیدگاه‌ها (فقط دیدگاه اصلی، بدون پاسخ‌ها)
                'pending' => Comment::query()
                    ->whereIn('commentable_type', [Product::class, Post::class])
                    ->whereNull('comment_id')
                    ->where('status', 'pending')
                    ->count(),
                'productTotal' => Comment::query()->where('commentable_type', Product::class)->count(),
                'ratingAvg' => (float) (Review::query()->where('status', 'accepted')->avg('rating') ?? 0),
                'ratingCount' => (int) Review::query()->where('status', 'accepted')->count(),
                'items' => $items->map(fn (Comment $comment) => [
                    'id' => $comment->id,
                    'author' => $comment->user ? ($comment->user->fullname ?: $comment->user->username) : 'مهمان',
                    'body' => \Illuminate\Support\Str::limit((string) $comment->body, 72, '…'),
                    'targetType' => $comment->commentable_type === Post::class ? 'مطلب' : 'محصول',
                    'targetTitle' => $comment->commentable?->title ?? 'حذف‌شده',
                    'status' => (string) $comment->status,
                    'statusLabel' => $this->commentStatusLabel((string) $comment->status),
                    'date' => Jalalian::fromDateTime($comment->created_at)->format('Y/m/d'),
                ])->values()->toArray(),
            ];
        });
    }

    /**
     * تیکت‌ها: باز (status != close — مدل Ticket بجای closed بولی از status enum استفاده می‌کند) + لیست آخرین تیکت‌ها
     */
    protected function buildTickets(): array
    {
        return Cache::remember('np-dashboard-tickets', 60, function () {
            $items = Ticket::query()
                ->with('user:id,first_name,last_name,username')
                ->latest('id')
                ->take(6)
                ->get(['id', 'subject', 'user_id', 'status', 'priority', 'created_at']);

            return [
                'open' => Ticket::query()->where('status', '!=', 'close')->count(),
                'total' => Ticket::count(),
                'items' => $items->map(fn (Ticket $ticket) => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'user' => $ticket->user ? ($ticket->user->fullname ?: $ticket->user->username) : '—',
                    'status' => $ticket->status,
                    'statusLabel' => $this->ticketStatusLabel($ticket->status),
                    'priority' => $ticket->priority,
                    'priorityLabel' => $this->ticketPriorityLabel($ticket->priority),
                    'date' => Jalalian::fromDateTime($ticket->created_at)->format('Y/m/d'),
                ])->values()->toArray(),
            ];
        });
    }

    /**
     * نمودار ثبت‌نام کاربران ۳۰ روز اخیر — aggregate گروه‌بندی روزانه (بدون بارگذاری ردیف‌ها)
     */
    protected function buildUsersChart(): array
    {
        return Cache::remember('np-dashboard-users-chart', 60, function () {
            $days = 30;

            $byDay = User::query()
                ->where('created_at', '>=', now()->subDays($days)->startOfDay())
                ->selectRaw('date(created_at) as day, count(*) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $labels = [];
            $counts = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = today()->subDays($i);
                $labels[] = Jalalian::fromDateTime($date)->format('m/d');
                $counts[] = (int) ($byDay[$date->format('Y-m-d')] ?? 0);
            }

            return [
                'labels' => $labels,
                'users' => $counts,
            ];
        });
    }

    /**
     * جدیدترین کاربران (آواتار/نام/موبایل/تاریخ ثبت‌نام)
     */
    protected function buildNewestUsers(): array
    {
        return Cache::remember('np-dashboard-newest-users', 60, function () {
            return User::query()
                ->latest('id')
                ->take(6)
                ->get(['id', 'first_name', 'last_name', 'username', 'mobile', 'image', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->fullname ?: $user->username,
                    'mobile' => $user->mobile,
                    'avatar' => $user->image ? asset($user->image) : null,
                    'date' => Jalalian::fromDateTime($user->created_at)->format('Y/m/d'),
                ])
                ->values()
                ->toArray();
        });
    }

    /**
     * آخرین تراکنش‌ها (کاربر، مبلغ، نوع/جهت، وضعیت) — morph با eager load کامل (حداکثر ۶ ردیف، بدون N+1)
     */
    protected function buildLatestTransactions(): array
    {
        return Cache::remember('np-dashboard-transactions', 60, function () {
            $rows = Transaction::query()
                ->with([
                    'user:id,first_name,last_name,username',
                    'transactionable',
                ])
                ->latest('id')
                ->take(6)
                ->get(['id', 'user_id', 'transactionable_type', 'transactionable_id', 'status', 'amount', 'created_at']);

            return $rows->map(function (Transaction $transaction) {
                // جهت فقط برای تراکنش‌های کیف پول (type: deposit/withdraw در WalletHistory)
                $direction = null;

                if ($transaction->transactionable_type === WalletHistory::class && $transaction->transactionable) {
                    $direction = $transaction->transactionable->type === 'deposit' ? 'واریز' : 'برداشت';
                }

                return [
                    'id' => $transaction->id,
                    'user' => $transaction->user ? ($transaction->user->fullname ?: $transaction->user->username) : 'حذف شده',
                    'amount' => (int) ($transaction->amount ?? 0),
                    'type' => $this->transactionTypeLabel((string) $transaction->transactionable_type),
                    'direction' => $direction,
                    'status' => (bool) $transaction->status,
                    'date' => Jalalian::fromDateTime($transaction->created_at)->format('Y/m/d'),
                ];
            })->values()->toArray();
        });
    }

    /* ============================ برچسب‌های فارسی ============================ */

    protected function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'unpaid' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'canceled' => 'لغو شده',
            'pending_down_payment' => 'پیش‌پرداخت',
            'active' => 'فعال',
            default => $status,
        };
    }

    /** وضعیت دیدگاه — پورت STATUS_TEXTS صفحه مدیریت دیدگاه‌ها */
    protected function commentStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'در انتظار',
            'accepted', 'approved' => 'تایید شده',
            'rejected' => 'رد شده',
            'unconfirmed' => 'تایید نشده',
            default => $status,
        };
    }

    /** وضعیت تیکت — پورت Ticket::statusText */
    protected function ticketStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'در انتظار پاسخ',
            'answered' => 'پاسخ داده شده',
            'open' => 'باز',
            'close' => 'بسته',
            default => (string) $status,
        };
    }

    /** اولویت تیکت — پورت Ticket::priorityText (تایپوی «hight» حفظ‌شده از enum دیتابیس) */
    protected function ticketPriorityLabel(?string $priority): string
    {
        return match ($priority) {
            'hight' => 'زیاد',
            'medium' => 'متوسط',
            default => 'کم',
        };
    }

    /** نوع تراکنش — پورت TransactionIndex::typeLabel */
    protected function transactionTypeLabel(string $morphType): string
    {
        return match ($morphType) {
            Order::class => 'خرید محصول',
            WalletHistory::class => 'کیف پول',
            default => 'تراکنش',
        };
    }

    /* ---------- فرمترهای نمایش (اعداد فارسی) ---------- */

    public static function fa($value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return strtr((string) $value, array_combine(range(0, 9), $fa));
    }

    public static function number($value): string
    {
        return self::fa(number_format((float) $value));
    }

    public static function toman($value): string
    {
        return self::number($value) . ' تومان';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.dashboard', [
            'salesOptions' => $this->salesChartOptions(),
            'statusOptions' => $this->statusChartOptions(),
            'topProductsOptions' => $this->topProductsChartOptions(),
            'usersOptions' => $this->usersChartOptions(),
        ])->layout('admin-panel::layout');
    }

    /**
     * آپشن‌های نمودار فروش (ECharts) — «fn:faNum» در سمت JS به فرمت‌کننده ارقام فارسی تبدیل می‌شود
     */
    protected function salesChartOptions(): array
    {
        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#10b981', '#0ea5e9'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'axis',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'legend' => ['show' => false],
            'grid' => ['top' => 20, 'right' => 20, 'bottom' => 30, 'left' => 16, 'containLabel' => true],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'data' => $this->salesChart['labels'] ?? [],
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'interval' => 4],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => 'تومان',
                    'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                    'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                    'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
                ],
                [
                    'type' => 'value',
                    'name' => 'سفارش',
                    'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                    'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNum'],
                    'splitLine' => ['show' => false],
                ],
            ],
            'series' => [
                [
                    'name' => 'فروش',
                    'type' => 'line',
                    'smooth' => true,
                    'symbol' => 'none',
                    'lineStyle' => ['width' => 2.5],
                    'areaStyle' => ['opacity' => 0.12],
                    'data' => $this->salesChart['revenues'] ?? [],
                    'yAxisIndex' => 0,
                ],
                [
                    'name' => 'سفارش',
                    'type' => 'line',
                    'smooth' => true,
                    'symbol' => 'none',
                    'lineStyle' => ['width' => 2, 'type' => 'dashed'],
                    'data' => $this->salesChart['orders'] ?? [],
                    'yAxisIndex' => 1,
                ],
            ],
        ];
    }

    protected function statusChartOptions(): array
    {
        $palette = ['#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#8b5cf6', '#64748b'];

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => $palette,
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'item',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'legend' => [
                'bottom' => 6,
                'textStyle' => ['color' => '#8b93a1', 'fontSize' => 11],
                'itemWidth' => 10,
                'itemHeight' => 10,
                'icon' => 'circle',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'radius' => ['52%', '78%'],
                    'center' => ['50%', '44%'],
                    'avoidLabelOverlap' => true,
                    'itemStyle' => ['borderRadius' => 8, 'borderWidth' => 2, 'borderColor' => 'transparent'],
                    'label' => ['show' => false],
                    'emphasis' => [
                        'scale' => true,
                        'itemStyle' => ['shadowBlur' => 12, 'shadowColor' => 'rgba(16,185,129,.35)'],
                    ],
                    'data' => collect($this->statusChart['labels'] ?? [])
                        ->map(fn ($label, $i) => [
                            'name' => $label,
                            'value' => $this->statusChart['values'][$i] ?? 0,
                        ])->values()->toArray(),
                ],
            ],
        ];
    }

    protected function topProductsChartOptions(): array
    {
        $rows = $this->topProducts;

        $labels = collect($rows)->map(fn ($r) => \Illuminate\Support\Str::limit($r['title'], 18, '…'))->values()->toArray();
        $values = collect($rows)->pluck('sold')->values()->toArray();

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#f59e0b'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'axis',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'grid' => ['top' => 10, 'right' => 24, 'bottom' => 10, 'left' => 8, 'containLabel' => true],
            'xAxis' => [
                'type' => 'value',
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNum'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'yAxis' => [
                'type' => 'category',
                'inverse' => true,
                'data' => $labels,
                'axisLine' => ['show' => false],
                'axisTick' => ['show' => false],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 11],
            ],
            'series' => [
                [
                    'type' => 'bar',
                    'barWidth' => 14,
                    'itemStyle' => ['borderRadius' => [0, 7, 7, 0]],
                    'label' => ['show' => true, 'position' => 'right', 'color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNum'],
                    'data' => $values,
                ],
            ],
        ];
    }

    /**
     * آپشن‌های نمودار ثبت‌نام کاربران (میله‌ای) — فاز ۹-ب، همان الگوی نمودارهای موجود
     */
    protected function usersChartOptions(): array
    {
        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#8b5cf6'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'axis',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'grid' => ['top' => 20, 'right' => 20, 'bottom' => 30, 'left' => 16, 'containLabel' => true],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => true,
                'data' => $this->usersChart['labels'] ?? [],
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'interval' => 4],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                'type' => 'value',
                'minInterval' => 1,
                'name' => 'کاربر',
                'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNum'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'series' => [
                [
                    'name' => 'ثبت‌نام',
                    'type' => 'bar',
                    'barWidth' => 12,
                    'itemStyle' => ['borderRadius' => [7, 7, 0, 0]],
                    'data' => $this->usersChart['users'] ?? [],
                ],
            ],
        ];
    }
}

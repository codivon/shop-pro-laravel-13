<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Seller;
use App\Models\Tag;
use App\Models\User;
use App\Models\Viewer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class MainController extends Controller
{
    public function index()
    {
        $users_count = Cache::rememberForever('admin.users_count', function () {
            return User::where('level', '!=', 'creator')->count();
        });

        $products_count = Cache::rememberForever('admin.products_count', function () {
            return Product::count();
        });

        $orders_count = Cache::rememberForever('admin.orders_count', function () {
            return Order::count();
        });

        $total_sell = Cache::rememberForever('admin.total_sell', function () {
            return Order::where('status', 'paid')->sum('price');
        });

        /*
         * فاز ۱۳۹ — بهینه‌سازی بارگذاری داشبورد پنل قدیمی (قاتل اصلی سرعت اولین صفحه):
         * ① قبلاً Order::orderby('id','desc')->get() و Review::filter()->get() «همهٔ»
         *    ردیف‌های سفارش/نظر را از دیتابیس می‌کشیدند و بعد در PHP با take(10)
         *    برش می‌خوردند — روی فروشگاهِ پرسفارش یعنی انتقال ده‌ها هزار ردیف در هر
         *    باز شدن داشبورد (سرور کم‌رم + swap = چندثانیه‌ای) → LIMIT سطح کوئری.
         * ② حلقه‌های foreach کاربران/فروشندگان فعال به‌ازای هر عضو یک کوئری Viewer
         *    جدا اجرا می‌کردند (۲۰+۲۰ کوئری در هر رندر) → یک کوئری groupBy_latest
         *    با unique روی کالکشن.
         * ③ رابطهٔ user/seller برای رندر ویو eager شد تا خود لیست هم N+1 نزند.
         */
        $orders = Order::latest('id')->take(10)->get();
        $reviews = Review::filter()->take(10)->get();
        $view_products = Product::published()->where('view', '!=', 0)->orderBy('view', 'desc')->take(20)->get();
        $sale_products = Product::published()->where('sell', '!=', 0)->orderBySale('desc')->take(20)->get();
        $questions = Comment::whereNull('comment_id')->latest()->where(['status' => 'accepted', 'commentable_type' => 'App\Models\Product'])->take(20)->get();

        $active_users = Viewer::whereNotNull('user_id')
            ->whereIn('user_id', User::query()->take(20)->pluck('id'))
            ->with('user')
            ->latest('id')
            ->get()
            ->unique('user_id')
            ->take(10)
            ->values();

        $active_sellers = Viewer::whereNotNull('seller_id')
            ->whereIn('seller_id', Seller::query()->take(20)->pluck('id'))
            ->with('seller')
            ->latest('id')
            ->get()
            ->unique('seller_id')
            ->take(10)
            ->values();

        return view('back.index', compact(
            'users_count',
            'products_count',
            'orders_count',
            'total_sell',
            'orders',
            'reviews',
            'view_products',
            'sale_products',
            'questions',
            'active_users',
            'active_sellers',
        ));
    }

    public function get_tags(Request $request)
    {
        $tags = Tag::detectLang()->where('name', 'like', '%' . $request->term . '%')
            ->latest()
            ->take(5)
            ->pluck('name')
            ->toArray();

        return response()->json($tags);
    }

    public function getLabels(Request $request)
    {
        $labels = Label::detectLang()->where('title', 'like', '%' . $request->term . '%')
            ->latest()
            ->take(5)
            ->pluck('title')
            ->toArray();

        return response()->json($labels);
    }

    public function login()
    {
        return view('back.auth.login');
    }

    public function notifications(Request $request)
    {
        $admin  = auth('adminPanel')->user();
        $filter = in_array($request->query('filter'), ['all', 'unread', 'read'])
            ? $request->query('filter') : 'all';

        $query = $admin->notifications();
        if ($filter === 'unread') $query->whereNull('read_at');
        if ($filter === 'read')   $query->whereNotNull('read_at');

        $notifications = $query->latest()->paginate(15)->appends(['filter' => $filter]);

        $stats = [
            'total'  => $admin->notifications()->count(),
            'unread' => $admin->unreadNotifications()->count(),
            'today'  => $admin->notifications()->whereDate('created_at', today())->count(),
        ];

        return view('back.notifications.panel', compact('notifications', 'filter', 'stats'));
    }

// ---------- خواندن تکی (AJAX) ----------
    public function markRead(string $id)
    {
        $notification = auth('adminPanel')->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'اعلان خوانده‌شده علامت‌گذاری شد.']);
    }

// ---------- خواندن همه (AJAX) ----------
    public function markAllRead()
    {
        $count = auth('adminPanel')->user()->unreadNotifications()->count();
        auth('adminPanel')->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => "همه اعلان‌ها ({$count} مورد) خوانده‌شده علامت‌گذاری شدند.",
            'count'   => $count,
        ]);
    }
    public function fileManager()
    {
        $this->authorize('file-manager');

        return view('back.file-manager');
    }

    public function fileManagerIframe()
    {
        $this->authorize('file-manager');

        return view('back.file-manager-iframe');
    }
    public function cache_clear()
    {
        session()->put('toast-success','کش با موفقیت پاک شد');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:cache');
        Artisan::call('view:clear');

        return redirect()->back();
    }
}

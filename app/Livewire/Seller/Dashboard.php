<?php

namespace App\Livewire\Seller;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerDeposit;
use App\Models\SellerVariant;
use App\Models\Viewer;
use App\Models\WalletHistory;
use App\Support\MultiVendorSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — داشبورد پنل فروشندگان مدرن (Livewire 4).
 *
 * پورت ۱:۱ DashboardSellerController@index — همان کوئری‌ها، همان گیت‌ها و
 * همان رشته‌های تست‌شده (فاز ۱۰۳): «نرخ کمیسیون اختصاصی شما: X٪»،
 * «کمیسیون طبق جدول دسته‌بندی‌ها»، «پیش‌فرض سایت X٪» و کلاس vset-card.
 * فقط ظاهر با توکن‌های ap-* بازطراحی شده است.
 */
#[Title('داشبورد')]
#[Layout('sellers.layouts.panel')]
class Dashboard extends Component
{
    public int $variantsCount = 0;

    public int $productsCount = 0;

    public int $noCompetitorCount = 0;

    // آمار بازدید فروشگاه
    public int $totalVisits = 0;

    public int $uniqueVisitors = 0;

    public int $todayVisits = 0;

    public float $changePercent = 0;

    public int $averageVisits = 0;

    public array $dailyVisits = [];

    /** آمار تسویه (فاز ۱۰۳) — null یعنی بلوک رندر نمی‌شود */
    public ?array $vendorStats = null;

    /** وضعیت‌های سه‌گانهٔ کارت‌های وضعیت (مدارک/قرارداد/پنل) */
    public array $status = [];

    /** ارزش سفارش‌های موفق — مبنای واحد: order_items.seller_id (فاز ۱۰۶ الف) */
    public int $paidOrdersValue = 0;

    public function mount(): void
    {
        // مثل پنل قدیمی: بدون چندفروشندگی این صفحه وجود ندارد (میدل‌ور Seller هم 404 می‌دهد)
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $sellerVariants = SellerVariant::where('seller_id', sellerID())->get();
        $sellerProductIds = SellerVariant::where('seller_id', sellerID())->get()->pluck('product_id')->toArray();

        $sellerCompetitor = SellerVariant::whereIn('product_id', $sellerProductIds)
            ->where('seller_id', '!=', sellerID())
            ->get()
            ->pluck('product_id');

        $this->noCompetitorCount = SellerVariant::where('seller_id', sellerID())
            ->whereNotIn('product_id', $sellerCompetitor)
            ->count();
        $this->variantsCount = $sellerVariants->count();
        // فاز ۱۲۱ — seller_info() برای فروشندهٔ تازه (بدون رکورد SellerInfo) null است؛
        // به‌جای کرش، مقادیر امن پیش‌فرض استفاده می‌شود.
        $this->productsCount = Product::where('seller_id', seller_info()?->seller_id ?? sellerID())->count();

        // ========== آمار بازدید فروشگاه ==========
        $sellerSlug = seller()->slug;
        $this->totalVisits = (int) Viewer::getSellerStoreVisitsCount($sellerSlug);
        $this->uniqueVisitors = (int) Viewer::getUniqueSellerStoreVisitors($sellerSlug);
        $this->todayVisits = (int) Viewer::getTodaySellerStoreVisits($sellerSlug);
        $this->changePercent = (float) Viewer::getChangePercent($sellerSlug);
        $this->averageVisits = (int) Viewer::getAverageDailyVisits($sellerSlug);
        $this->dailyVisits = collect(Viewer::getDailySellerStoreVisits($sellerSlug, 7))
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        // ========== وضعیت سه‌گانه ==========
        $documents = match (seller()->status_documents) {
            'Accept' => ['key' => 'success', 'label' => 'تایید شده'],
            'Reject' => ['key' => 'danger', 'label' => 'رد شده'],
            default => ['key' => 'warning', 'label' => 'در حال بررسی'],
        };
        $contract = (seller_info()?->econtract == '1')
            ? ['key' => 'success', 'label' => 'تایید شده']
            : ['key' => 'warning', 'label' => 'در انتظار تایید'];
        $work = match (seller()->status_work) {
            'ACTIVE' => ['key' => 'success', 'label' => 'فعال'],
            'EditProfile' => ['key' => 'warning', 'label' => 'در انتظار تایید (ویرایش اطلاعات)'],
            default => ['key' => 'danger', 'label' => 'توقف کاری'],
        };
        $this->status = ['documents' => $documents, 'contract' => $contract, 'work' => $work];

        // فاز ۱۰۶ الف — مبنای واحد آمار: order_items.seller_id
        $this->paidOrdersValue = (int) OrderItem::where('seller_id', sellerID())
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->selectRaw('COALESCE(SUM(price * quantity), 0) as total')
            ->value('total');

        // ========== آمار تسویه (فاز ۱۰۳ — گیت از تنظیمات چندفروشندگی) ==========
        if (MultiVendorSettings::enabled() && MultiVendorSettings::dashboardStatsEnabled()) {
            $sellerId = sellerID();
            $wallet = auth('seller')->user()->getWallet();

            $this->vendorStats = [
                'balance' => (int) $wallet->balance(),

                'total_sales' => (int) OrderItem::where('seller_id', $sellerId)
                    ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
                    ->selectRaw('COALESCE(SUM(price * quantity), 0) as total')
                    ->value('total'),

                'total_commission' => (int) SellerDeposit::where('seller_id', $sellerId)
                    ->where('status', 'success')
                    ->sum('amount'),

                'waiting_withdraw_count' => WalletHistory::whereHas('wallet', fn ($q) => $q->where('seller_id', $sellerId))
                    ->where(['withdraw' => 1, 'type' => 'withdraw'])
                    ->where('status_pay', 'waiting')
                    ->count(),

                'waiting_withdraw_amount' => (int) WalletHistory::whereHas('wallet', fn ($q) => $q->where('seller_id', $sellerId))
                    ->where(['withdraw' => 1, 'type' => 'withdraw'])
                    ->where('status_pay', 'waiting')
                    ->sum('amount'),

                'seller_override_commission' => MultiVendorSettings::sellerOverrideEnabled()
                    ? MultiVendorSettings::sellerOverride($sellerId)
                    : null,

                'default_commission' => MultiVendorSettings::defaultCommission(),
            ];
        }
    }

    public function render()
    {
        return view('sellers.panel.dashboard');
    }
}

<?php

namespace Themes\WeblakShop\src\Controllers\sellers;

use App\Events\OrderPaid;
use App\Events\WalletAmountDecreased;
use App\Events\WalletAmountIncreased;
use App\Exports\SellerSettlementsExport;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Gateway;
use App\Models\Seller;
use App\Models\SellerCommission;
use App\Models\SellerDeposit;
use App\Models\Transaction;
use App\Models\WalletHistory;
use App\Notifications\Seller\SellerRequestDeposit;
use App\Support\MultiVendorSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class WalletSellerController extends Controller
{
    public function index()
    {
        $seller = Seller::find(Auth::guard('seller')->id());
        $wallet = $seller->getWallet();
        $histories = $wallet->histories()->latest()->paginate(20);
        $gateways = Gateway::active()->get();

        return view('front::sellers.panel.wallet.index', compact('seller', 'wallet', 'gateways', 'histories'));
    }

    public function show(WalletHistory $wallet)
    {
        return view('front::sellers.panel.wallet.show')->with('history', $wallet);
    }

    public function store(Request $request)
    {
        $seller = Seller::find(Auth::guard('seller')->id());
        $gateways = Gateway::active()->pluck('key')->toArray();

        $request->validate([
            'amount' => 'required|numeric|max:500000000|min:10000',
            'gateway' => 'required|in:'.implode(',', $gateways),
        ]);

        $gateway = $request->gateway;
        $amount = intval($request->amount);
        $wallet = $seller->getWallet();

        $history = $wallet->histories()->create([
            'type' => 'deposit',
            'amount' => $amount,
            'description' => 'شارژ آنلاین کیف پول',
            'source' => 'seller',
            'status' => 'fail',
        ]);

        try {

            $gateway_configs = get_gateway_configs($gateway);

            return Payment::via($gateway)->config($gateway_configs)->callbackUrl(route('seller.wallet.verify', ['gateway' => $gateway]))->purchase(
                (new Invoice)->amount($amount),
                function ($driver, $transactionId) use ($history, $gateway, $amount, $seller) {
                    DB::table('transactions')->insert([
                        'status' => false,
                        'amount' => $amount,
                        'factorNumber' => $history->id,
                        'mobile' => $seller->mobile,
                        'message' => 'تراکنش ایجاد شد برای درگاه '.$gateway,
                        'transID' => $transactionId,
                        'token' => $transactionId,
                        'seller_id' => $seller->id,
                        'transactionable_type' => WalletHistory::class,
                        'transactionable_id' => $history->id,
                        'gateway_id' => Gateway::where('key', $gateway)->first()->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                    session()->put('transactionId', $transactionId);
                    session()->put('amount', $amount);
                }
            )->pay()->render();
        } catch (Exception $e) {
            return redirect()->route('seller.wallet', ['history' => $history])->with('transaction-error', $e->getMessage());
        }
    }

    public function verify($gateway)
    {
        $transactionId = session()->get('transactionId');
        $amount = session()->get('amount');

        $transaction = Transaction::where('status', false)->where('transID', $transactionId)->firstOrFail();

        $history = $transaction->transactionable;

        $gateway_configs = get_gateway_configs($gateway);

        try {
            $receipt = Payment::via($gateway)->config($gateway_configs);

            if ($amount) {
                $receipt = $receipt->amount(intval($amount));
            }

            $receipt = $receipt->transactionId($transactionId)->verify();

            DB::table('transactions')->where('transID', $transactionId)->update([
                'status' => 1,
                'traceNumber' => $receipt->getReferenceId(),
                'message' => $transaction->message.'<br>'.'پرداخت موفق با درگاه '.$gateway,
                'updated_at' => Carbon::now(),
            ]);

            $history->update([
                'status' => 'success',
            ]);

            event(new WalletAmountIncreased($history->wallet));

            $history->wallet->refereshBalance();

            if ($history->order) {
                $result = $history->order->payUsingWallet();

                if ($result) {
                    $history->order->update([
                        'status' => 'paid',
                    ]);

                    event(new OrderPaid($history->order));

                    return redirect()->route('front.orders.show', ['order' => $history->order])->with('message', 'ok');
                }
            }

            return redirect()->route('front.wallet', ['history' => $history])->with('message', 'ok');
        } catch (Exception $exception) {

            DB::table('transactions')->where('transID', $transactionId)->update([
                'message' => $transaction->message.'<br>'.$exception->getMessage(),
                'updated_at' => Carbon::now(),
            ]);

            if ($history->order) {
                return redirect()->route('front.orders.show', ['order' => $history->order])
                    ->with('transaction-error', $exception->getMessage())
                    ->with('order_id', $history->order->id);
            }

            return redirect()->route('seller.wallet.index', ['history' => $history])->with('transaction-error', $exception->getMessage());
        }
    }

    public function withdraw(Request $request)
    {
        $seller = Seller::find(Auth::guard('seller')->id());

        // فاز ۱۰۳ — حداقل مبلغ برداشت از تنظیمات چندفروشندگی (۰ = بدون حد اضافه؛ حداقل همیشگی فرم ۱۰٬۰۰۰ تومان)
        $minAmount = max(10000, MultiVendorSettings::minWithdrawAmount());

        $data = $request->validate([
            'amount' => 'required|numeric|max:50000000|min:'.$minAmount,
        ], [
            'amount.min' => MultiVendorSettings::minWithdrawAmount() > 10000
                ? 'حداقل مبلغ درخواست برداشت '.number_format($minAmount).' تومان است.'
                : 'حداقل مبلغ درخواست برداشت ۱۰٬۰۰۰ تومان است.',
        ]);

        $amount = intval($request->amount);
        if ($amount > $seller->getWallet()->balance()) {
            session()->put('toast-error', 'مبلغ وارد شده بیشتر از موجودی قابل برداشت می‌باشد.');

        } else {
            $wallet = $seller->getWallet();

            $data['source'] = 'seller';
            $data['status'] = 'success';
            $data['type'] = 'withdraw';
            $data['withdraw'] = true;
            $data['description'] = 'درخواست برداشت وجه توسط فروشنده به مبلغ : '.number_format($data['amount']).' تومان  ';
            DB::transaction(function () use ($wallet, $data) {
                $wallet->histories()->create($data);

                $wallet->update([
                    'balance' => $wallet->balance - $data['amount'],
                ]);
                event(new WalletAmountDecreased($wallet));

            });
            $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
            Notification::send($admins, new SellerRequestDeposit($seller));
            session()->put('toast-success', 'درخواست برداشت با موفقیت ارسال شد.');
        }

        return redirect()->back();
    }

    /**
     * فاز ۱۰۶-۱ — صورت‌حساب تسویهٔ فروشنده (شفافیت مالی).
     *
     * داده‌ها از فاز ۱۰۳ موجودند و فقط UI نداشتند:
     *  • seller_deposits: ردیف کمیسیونِ سایت به‌ازای هر سفارش (مبلغ/درصد/وضعیت)
     *  • WalletHistory (type=deposit, source=seller): سهم واریزشده به کیف پول
     * گیت قانون طلایی: سوییچ multi_vendor_settlement_statement در تب
     * «چندفروشندگی» تنظیمات برنامه‌نویس.
     */
    public function settlements()
    {
        if (! MultiVendorSettings::settlementStatementEnabled()) {
            session()->put('toast-error', 'صورت‌حساب تسویه از تنظیمات غیرفعال شده است.');

            return redirect()->route('seller.wallet.index');
        }

        $sellerId = (int) sellerID();

        $deposits = SellerDeposit::where('seller_id', $sellerId)
            ->with('order:id,price,status,created_at')
            ->latest()
            ->paginate(20);

        [$stats, $netByOrder] = $this->settlementStats($sellerId);

        return view('front::sellers.panel.wallet.settlements', compact('deposits', 'stats', 'netByOrder'));
    }

    /** خروجی اکسل صورت‌حساب — همان الگوی SellerOrdersExport */
    public function settlementsExport()
    {
        if (! MultiVendorSettings::settlementStatementEnabled()) {
            session()->put('toast-error', 'صورت‌حساب تسویه از تنظیمات غیرفعال شده است.');

            return redirect()->route('seller.wallet.index');
        }

        return Excel::download(new SellerSettlementsExport((int) sellerID()), 'settlements.xlsx');
    }

    /**
     * جمع‌های صورت‌حساب + نقشهٔ سهم واریزشده به‌ازای هر سفارش.
     *
     * @return array{0: array<string, int>, 1: Collection}
     */
    private function settlementStats(int $sellerId): array
    {
        $history = WalletHistory::whereHas('wallet', fn ($q) => $q->where('seller_id', $sellerId))
            ->where(['type' => 'deposit', 'source' => 'seller', 'status' => 'success'])
            ->get(['id', 'order_id', 'amount']);

        $stats = [
            // تعداد تسویه‌های انجام‌شده (هر سفارش حداکثر یک بار — ایدمپوتنس فاز ۱۰۳)
            'settled_orders' => $history->pluck('order_id')->filter()->unique()->count(),
            // جمع سهمِ واریزشده به کیف پول (تسویه‌شده)
            'total_net' => (int) $history->sum('amount'),
            // جمع کمیسیونِ کسرشدهٔ سایت
            'total_commission' => (int) SellerDeposit::where('seller_id', $sellerId)
                ->where('status', 'success')
                ->sum('amount'),
            // درخواست‌های برداشت در انتظار پرداخت
            'waiting_withdraw_amount' => (int) WalletHistory::whereHas('wallet', fn ($q) => $q->where('seller_id', $sellerId))
                ->where(['withdraw' => 1, 'type' => 'withdraw'])
                ->where('status_pay', 'waiting')
                ->sum('amount'),
        ];

        // سهم واریزشدهٔ هر سفارش — برای ستون «سهم شما» در جدول
        $netByOrder = $history->groupBy('order_id')->map->sum('amount');

        return [$stats, $netByOrder];
    }

    public function commission()
    {
        // $commissions=SellerCommission::paginate(30);

        $rootCategories = Category::whereNull('category_id')->where('type', 'productcat')->get();

        if ($rootCategories->isEmpty()) {
            $rootCategories = Category::all();
        }

        // استفاده از کالکشن به جای آرایه
        $categoriesWithCommission = collect(); // کالکشن خالی

        foreach ($rootCategories as $category) {
            $categoriesWithCommission->push($this->buildCategoryTree($category));
        }

        return view('front::sellers.panel.commission.index', compact('categoriesWithCommission'));
    }

    /**
     * فاز ۱۰۶ الف — روت seller.commission.guide از ابتدا به متدی اشاره می‌کرد که
     * هرگز نوشته نشده بود (500). «راهنمای کمیسیون» همان نرخ‌های مؤثرِ صفحهٔ کمیسیون
     * است (override فروشنده > جدول دسته‌بندی‌ها > پیش‌فرض سراسری)؛ پس همان ویو با
     * همان منطق سرو می‌شود (DRY — دو منبع حقیقت برای نرخ‌ها ساخته نمی‌شود).
     */
    public function showCommissionRates()
    {
        return $this->commission();
    }

    private function buildCategoryTree($category)
    {
        // دریافت کمیسیون مؤثر با ارث‌بری
        $commissionInfo = $this->getEffectiveCommissionInfo($category);

        $result = [
            'id' => $category->id,
            'name' => $category->title,
            'commission' => $commissionInfo['commission'],
            'commission_type' => $commissionInfo['type'],
            'source_category' => $commissionInfo['source'] ?? null,
            'children' => [],
        ];

        // دریافت فرزندان (دسته‌بندی‌هایی که category_id آنها برابر id فعلی است)
        $children = Category::where('category_id', $category->id)->get();

        foreach ($children as $child) {
            $result['children'][] = $this->buildCategoryTree($child);
        }

        return $result;
    }

    private function getEffectiveCommissionInfo($category)
    {
        $current = $category;
        $depth = 0;
        $sourceCategory = null;

        while ($current) {
            if (! is_null($current->commission)) {
                return [
                    'commission' => (int) $current->commission,
                    'type' => $depth == 0 ? 'explicit' : 'inherited',
                    'source' => $sourceCategory ?: $current->id,
                ];
            }

            // رفتن به والد (با استفاده از category_id)
            if (! is_null($current->category_id)) {
                $sourceCategory = $current->category_id;
                $current = Category::find($current->category_id);
            } else {
                $current = null;
            }
            $depth++;
        }

        return [
            'commission' => 0,
            'type' => 'default_zero',
            'source' => null,
        ];
    }
}

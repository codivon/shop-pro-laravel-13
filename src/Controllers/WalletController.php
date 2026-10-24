<?php

namespace Themes\SaniarTheme\src\Controllers;

use App\Events\OrderPaid;
use App\Events\WalletAmountDecreased;
use App\Events\WalletAmountIncreased;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Notifications\Seller\SellerRequestDeposit;
use App\Notifications\User\UserRequestDeposit;
use Illuminate\Http\Request;
use App\Models\WalletHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice;
use function GuzzleHttp\Promise\all;

class WalletController extends Controller
{
    public function index()
    {
        $user        = auth()->user();
        $wallet    = auth()->user()->getWallet();
        $histories = $wallet->histories()->latest()->paginate(20);
        $gateways = Gateway::active()->get();
        $active="wallet";
        return view('front::user.wallet.index', compact('user','wallet', 'gateways','histories','active'));
    }

    public function show(WalletHistory $wallet)
    {
        return view('front::user.wallet.show')->with('history', $wallet);
    }

    public function create()
    {
        $gateways = Gateway::active()->get();

        return view('front::user.wallet.create', compact('gateways'));
    }

    public function store(Request $request)
    {
        $gateways = Gateway::active()->pluck('key')->toArray();

        $request->validate([
            'amount'      => 'required|numeric|max:500000000|min:1000',
            'gateway'     => 'required|in:' . implode(',', $gateways),
        ]);

        $gateway = $request->gateway;
        $amount  = intval($request->amount);
        $wallet  = auth()->user()->getWallet();

        $history = $wallet->histories()->create([
            'type'        => 'deposit',
            'amount'      => $amount,
            'description' => 'شارژ آنلاین کیف پول',
            'source'      => 'user',
            'status'      => 'fail'
        ]);

        try {

            $gateway_configs = get_gateway_configs($gateway);

            return Payment::via($gateway)->config($gateway_configs)->callbackUrl(route('front.wallet.verify', ['gateway' => $gateway]))->purchase(
                (new Invoice)->amount($amount),
                function ($driver, $transactionId) use ($history, $gateway, $amount) {
                    DB::table('transactions')->insert([
                        'status'               => false,
                        'amount'               => $amount,
                        'factorNumber'         => $history->id,
                        'mobile'               => auth()->user()->username,
                        'message'              => 'تراکنش ایجاد شد برای درگاه ' . $gateway,
                        'transID'              => $transactionId,
                        'token'                => $transactionId,
                        'user_id'              => auth()->user()->id,
                        'transactionable_type' => WalletHistory::class,
                        'transactionable_id'   => $history->id,
                        'gateway_id'           => Gateway::where('key', $gateway)->first()->id,
                        "created_at"           => Carbon::now(),
                        "updated_at"           => Carbon::now(),
                    ]);

                    session()->put('transactionId', $transactionId);
                    session()->put('amount', $amount);
                }
            )->pay()->render();
        } catch (Exception $e) {
            return redirect()->route('front.wallet.index', ['history' => $history])->with('transaction-error', $e->getMessage());
        }
    }

    public function verify($gateway)
    {
        $transactionId = session()->get('transactionId');
        $amount        = session()->get('amount');

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
                'status'               => 1,
                'traceNumber'          => $receipt->getReferenceId(),
                'message'              => $transaction->message . '<br>' . 'پرداخت موفق با درگاه ' . $gateway,
                'updated_at'           => Carbon::now(),
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

            return redirect()->route('front.wallet.index', ['history' => $history])->with('message', 'ok');
        } catch (\Exception $exception) {

            DB::table('transactions')->where('transID', $transactionId)->update([
                'message'              => $transaction->message . '<br>' . $exception->getMessage(),
                "updated_at"           => Carbon::now(),
            ]);

            if ($history->order) {
                return redirect()->route('front.orders.show', ['order' => $history->order])
                    ->with('transaction-error', $exception->getMessage())
                    ->with('order_id', $history->order->id);
            }

            return redirect()->route('front.wallet.index', ['history' => $history])->with('transaction-error', $exception->getMessage());
        }
    }

    public function withdraw(Request $request)
    {
        $user        = auth()->user();

        $data = $request->validate([
            'amount'      => 'required|numeric|max:50000000|min:10000',
        ]);

        $amount  = intval($request->amount);
        if ($amount > $user->getWallet()->balance() ){
            session()->put('toast-error', 'مبلغ وارد شده بیشتر از موجودی قابل برداشت می‌باشد.');

        }else{
            $wallet  = auth()->user()->getWallet();

            $data['source'] = 'user';
            $data['status'] = 'success';
            $data['type'] = 'withdraw';
            $data['withdraw'] = true;
            $data['description'] = "درخواست برداشت وجه توسط کاربر به مبلغ : ".number_format($data['amount'])." تومان  ";
            DB::transaction(function () use ($wallet, $data) {
                $wallet->histories()->create($data);

                $wallet->update([
                  'balance' => $wallet->balance - $data['amount']
                ]);
                event(new WalletAmountDecreased($wallet));

            });
            $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
            Notification::send($admins, new UserRequestDeposit($user));

            session()->put('toast-success', 'درخواست برداشت با موفقیت ارسال شد.');
        }
        return redirect()->back();

    }
}

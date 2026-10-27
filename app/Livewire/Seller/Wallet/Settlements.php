<?php

namespace App\Livewire\Seller\Wallet;

use App\Models\SellerDeposit;
use App\Models\WalletHistory;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — صورت‌حساب تسویهٔ فروشنده (پورت WalletSellerController@settlements).
 *
 * شفافیت مالی: ردیف‌به‌ردیفِ کمیسیون سایت به‌ازای هر سفارش (seller_deposits فاز ۱۰۳)
 * + سهمِ واریزشده به کیف پول (WalletHistory type=deposit, source=seller).
 *
 * گیت قانون طلایی: سوییچ multi_vendor_settlement_statement — خاموش → redirect
 * به کیف پول با toast خطا (عیناً مسیر کنترلر قدیمی).
 */
#[Layout('sellers.layouts.panel')]
#[Title('صورت‌حساب تسویه')]
class Settlements extends Component
{
    use WithPagination;

    public function mount(): void
    {
        if (! MultiVendorSettings::settlementStatementEnabled()) {
            $this->dispatch('toast', message: 'صورت‌حساب تسویه از تنظیمات غیرفعال شده است.', type: 'error');
            $this->redirect(route('seller.wallet.index'), navigate: true);

            return;
        }
    }

    /**
     * جمع‌های صورت‌حساب + نقشهٔ سهم واریزشده به‌ازای هر سفارش
     * (پورت ۱:۱ settlementStats کنترلر قدیمی).
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

    public function render()
    {
        $sellerId = (int) sellerID();

        $deposits = SellerDeposit::where('seller_id', $sellerId)
            ->with('order:id,price,status,created_at')
            ->latest()
            ->paginate(20);

        [$stats, $netByOrder] = $this->settlementStats($sellerId);

        return view('sellers.panel.wallet.settlements', [
            'deposits' => $deposits,
            'stats' => $stats,
            'netByOrder' => $netByOrder,
            'exportUrl' => route('seller.wallet.settlements.export'),
        ]);
    }
}

<?php

namespace App\Livewire\Admin\Finance;

use App\Livewire\Admin\Finance\Concerns\InteractsWithWalletHistory;
use App\Models\WalletHistory;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('جزئیات تراکنش کیف پول')]
class WalletHistoryShow extends Component
{
    use InteractsWithWalletHistory;

    public WalletHistory $history;

    public function mount(WalletHistory $history): void
    {
        auth('adminPanel')->user()?->can('payments.wallet-histories.index') ?: abort(403);

        $this->history = $history->load(['wallet.user', 'wallet.seller', 'wallet.sellerInfo', 'transaction', 'order']);

        // مقدار اولیه سلکت وضعیت پرداخت + شماره پیگیری
        $this->statusPay = (string) $history->status_pay;
        $this->trackingId = (string) $history->trackingId;
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.finance.wallet-history-show')
            ->layout('admin-panel::layout');
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }
}

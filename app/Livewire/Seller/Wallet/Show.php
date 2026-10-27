<?php

namespace App\Livewire\Seller\Wallet;

use App\Models\Seller;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — جزئیات تراکنش کیف پول (پورت WalletSellerController@show).
 *
 * پارامتر {wallet} قدیمی در واقع WalletHistory است؛ همان امضا حفظ شد.
 * گارد مالکیت: تاریخچه باید متعلق به کیف پول خودِ فروشندهٔ فعلی باشد
 * (پنل قدیم این گارد را نداشت — طبق قرارداد فاز ۱۰۸ اضافه شد).
 */
#[Layout('sellers.layouts.panel')]
#[Title('جزئیات تراکنش')]
class Show extends Component
{
    public WalletHistory $history;

    public function mount(WalletHistory $wallet): void
    {
        // گارد مالکیت — تاریخچه‌ای که کیف پولش مال این فروشنده نیست ۴۰۴ است
        $seller = Seller::find(Auth::guard('seller')->id());
        $walletId = $seller->getWallet()->id;

        if ((int) $wallet->wallet_id !== (int) $walletId) {
            abort(404);
        }

        $this->history = $wallet;
    }

    public function render()
    {
        return view('sellers.panel.wallet.show', [
            'history' => $this->history,
            'indexUrl' => route('seller.wallet.index'),
        ]);
    }
}

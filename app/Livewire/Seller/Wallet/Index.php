<?php

namespace App\Livewire\Seller\Wallet;

use App\Models\Gateway;
use App\Models\Seller;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — کیف پول فروشنده (پورت WalletSellerController@index).
 *
 * کارت موجودی + تاریخچهٔ تراکنش‌ها ( paginate ۲۰ ) + دو فرم کلاسیک:
 *  • شارژ کیف پول → فرم کلاسیک POST به seller.wallet.store (فیلدهای amount / gateway)
 *    — کنترلر قدیمی به درگاه پرداخت می‌رود، پس این فرم واقعاً submit عادی است.
 *  • برداشت از کیف پول → فرم کلاسیک POST به seller.wallet.withdraw (فیلد amount)
 *    — کنترلر قدیمی با session toast + redirect back پاسخ می‌دهد.
 */
#[Layout('sellers.layouts.panel')]
#[Title('کیف پول')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $seller = Seller::find(Auth::guard('seller')->id());
        $wallet = $seller->getWallet();
        $histories = $wallet->histories()->latest()->paginate(20);
        $gateways = Gateway::active()->get();

        return view('sellers.panel.wallet.index', [
            'seller' => $seller,
            'wallet' => $wallet,
            'gateways' => $gateways,
            'histories' => $histories,
            'balance' => $wallet->balance(),
            'storeUrl' => route('seller.wallet.store'),
            'withdrawUrl' => route('seller.wallet.withdraw'),
            'settlementsUrl' => MultiVendorSettings::settlementStatementEnabled()
                ? route('seller.wallet.settlements')
                : null,
        ]);
    }
}

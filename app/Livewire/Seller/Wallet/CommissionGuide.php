<?php

namespace App\Livewire\Seller\Wallet;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * فاز ۱۰۸ — «نرخ کمیسیون» فروشنده (پورت WalletSellerController@showCommissionRates).
 *
 * همان صفحهٔ کمیسیون — در قدیم showCommissionRates() مستقیماً $this->commission()
 * را برمی‌گرداند (DRY — دو منبع حقیقت برای نرخ‌ها ساخته نمی‌شود)؛ اینجا هم با
 * وراثت از Commission همان داده‌ها با ویوی guide سرو می‌شود.
 */
#[Layout('sellers.layouts.panel')]
#[Title('نرخ کمیسیون')]
class CommissionGuide extends Commission
{
    public function render()
    {
        return view('sellers.panel.wallet.commission.guide', $this->commissionData());
    }
}

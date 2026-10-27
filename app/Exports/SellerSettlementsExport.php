<?php

namespace App\Exports;

use App\Models\SellerDeposit;
use App\Models\WalletHistory;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * فاز ۱۰۶-۱ — خروجی اکسل صورت‌حساب تسویهٔ فروشنده.
 *
 * الگوی SellerOrdersExport (FromView) — داده از دفتر فاز ۱۰۳:
 * seller_deposits (کمیسیون) + WalletHistory source=seller (سهم واریزشده).
 */
class SellerSettlementsExport implements FromView, WithTitle
{
    public function __construct(public int $sellerId) {}

    public function view(): View
    {
        $deposits = SellerDeposit::where('seller_id', $this->sellerId)
            ->with('order:id,price,status,created_at')
            ->latest()
            ->get();

        $netByOrder = WalletHistory::whereHas('wallet', fn ($q) => $q->where('seller_id', $this->sellerId))
            ->where(['type' => 'deposit', 'source' => 'seller', 'status' => 'success'])
            ->get(['order_id', 'amount'])
            ->groupBy('order_id')
            ->map->sum('amount');

        return view('back.exports.seller-settlements', [
            'deposits' => $deposits,
            'netByOrder' => $netByOrder,
        ]);
    }

    public function title(): string
    {
        return 'صورت‌حساب تسویه';
    }
}

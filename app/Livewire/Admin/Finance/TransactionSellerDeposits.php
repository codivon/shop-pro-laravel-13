<?php

namespace App\Livewire\Admin\Finance;

use App\Models\SellerDeposit;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست کمیسیون فروشندگان')]
class TransactionSellerDeposits extends Component
{
    use WithPagination;

    public int $perPage = 15;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('payments.transactions.seller_deposit') ?: abort(403);
    }

    /* ---------------- کوئری لیست (پورت seller_deposits قدیمی) ---------------- */

    #[Computed]
    public function deposits()
    {
        // قدیمی: SellerDeposit::latest()->paginate(20) — با eager fixing N+1
        return SellerDeposit::query()
            ->with(['seller.seller_info', 'category', 'order'])
            ->latest()
            ->paginate($this->perPage);
    }

    /* ---------------- آمار سربرگ ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        return [
            'total' => SellerDeposit::count(),
            'success' => SellerDeposit::where('status', 'success')->count(),
            'amount' => (int) SellerDeposit::where('status', 'success')->sum('amount'),
        ];
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.transactions.seller-deposits')
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

<?php

namespace App\Livewire\Admin\Finance;

use App\Models\Wallet;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('تاریخچه کیف پول')]
class WalletShow extends Component
{
    use WithPagination;

    public Wallet $wallet;

    public int $perPage = 20; // قدیمی: paginate(20)

    public function mount(Wallet $wallet): void
    {
        auth('adminPanel')->user()?->can('users.wallet') ?: abort(403);

        $this->wallet = $wallet;
    }

    /* ---------------- تاریخچه‌های کیف پول (پورت show قدیمی) ---------------- */

    #[Computed]
    public function histories()
    {
        return $this->wallet->histories()
            ->with(['transaction', 'order'])
            ->latest()
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.finance.wallet-show')
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

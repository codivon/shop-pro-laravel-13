<?php

namespace App\Livewire\Admin\Finance;

use App\Models\WalletHistory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('تاریخچه کیف پول')]
class WalletHistoryIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) — پورت WalletHistory::scopeFilter ---------------- */

    #[Url(as: 'status_pay', except: '')]
    public string $statusPay = '';

    #[Url(as: 'source', except: '')]
    public string $source = '';

    #[Url(as: 'ordering', except: 'latest')]
    public string $ordering = 'latest';

    public int $perPage = 15;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('payments.wallet-histories.index') ?: abort(403);
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['statusPay', 'source', 'ordering', 'perPage'])) {
            $this->resetPage();
        }
    }

    /** بازنشانی همه فیلترها */
    public function applyFilters(): void
    {
        $this->statusPay = '';
        $this->source = '';
        $this->ordering = 'latest';
        $this->resetPage();
    }

    /* ---------------- کوئری لیست (پورت WalletHistory::scopeFilter + قدیمی latest 20) ---------------- */

    protected function baseQuery()
    {
        $query = WalletHistory::query()
            ->with(['wallet.user', 'wallet.seller', 'wallet.sellerInfo', 'transaction']);

        // پورت: filter → status_pay
        if ($this->statusPay !== '') {
            $query->where('status_pay', $this->statusPay);
        }

        // پورت: filter → source
        if ($this->source !== '') {
            $query->where('source', $this->source);
        }

        // پورت: filter → ordering (oldest/latest)
        if ($this->ordering === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        return $query;
    }

    #[Computed]
    public function histories()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- آمار سربرگ ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        return [
            'total' => WalletHistory::count(),
            'deposit' => WalletHistory::where('type', 'deposit')->where('status', 'success')->count(),
            'withdraw' => WalletHistory::where('type', 'withdraw')->where('status', 'success')->count(),
        ];
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.finance.wallet-histories')
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

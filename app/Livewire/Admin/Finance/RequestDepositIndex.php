<?php

namespace App\Livewire\Admin\Finance;

use App\Livewire\Admin\Finance\Concerns\InteractsWithWalletHistory;
use App\Models\WalletHistory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('مدیریت درخواستی ها')]
class RequestDepositIndex extends Component
{
    use InteractsWithWalletHistory;
    use WithPagination;

    /* ---------------- فیلترها (State در URL) — پورت WalletHistory::scopeFilter ---------------- */

    #[Url(as: 'status_pay', except: '')]
    public string $statusPay = '';

    #[Url(as: 'source', except: '')]
    public string $source = '';

    #[Url(as: 'ordering', except: 'latest')]
    public string $ordering = 'latest';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- مودال تغییر وضعیت گروهی ---------------- */

    public bool $confirmingBulkStatus = false;

    public string $bulkStatus = '';

    /* ---------------- مودال خروجی اکسل (پورت request-deposit-export-modal قدیمی) ---------------- */

    public bool $exportModal = false;

    public string $exportSource = 'all';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('request_deposit') ?: abort(403);
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['statusPay', 'source', 'ordering', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    /** بازنشانی همه فیلترها */
    public function applyFilters(): void
    {
        $this->statusPay = '';
        $this->source = '';
        $this->ordering = 'latest';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    /* ---------------- کوئری لیست — پورت دقیق RequestDepositAdmin::index ---------------- */

    protected function baseQuery()
    {
        // قدیمی: WalletHistory::where(['withdraw'=>1,'type'=>'withdraw'])->filter($request)
        $query = WalletHistory::query()
            ->where(['withdraw' => 1, 'type' => 'withdraw'])
            ->with(['wallet.user', 'wallet.seller', 'wallet.sellerInfo']);

        // پورت scopeFilter: status_pay
        if ($this->statusPay !== '') {
            $query->where('status_pay', $this->statusPay);
        }

        // پورت scopeFilter: source
        if ($this->source !== '') {
            $query->where('source', $this->source);
        }

        // پورت scopeFilter: ordering (oldest/latest)
        if ($this->ordering === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        return $query;
    }

    #[Computed]
    public function items()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- آمار سربرگ (افزودنی — در قدیمی وجود نداشت) ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        $base = WalletHistory::query()->where(['withdraw' => 1, 'type' => 'withdraw']);

        return [
            'all' => (clone $base)->count(),
            'waiting' => (clone $base)->where('status_pay', 'waiting')->count(),
            'pay' => (clone $base)->where('status_pay', 'pay')->count(),
            'unpay' => (clone $base)->where('status_pay', 'unpay')->count(),
        ];
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->items->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- تغییر وضعیت گروهی (پورت multiple-status قدیمی با منطق unpay-refund) ---------------- */

    public function bulkStatusPay(): void
    {
        auth('adminPanel')->user()?->can('request_deposit') ?: abort(403);

        $this->validate([
            'bulkStatus' => 'required|in:waiting,pay,unpay,unpay-refund',
        ], [], ['bulkStatus' => 'وضعیت پرداخت']);

        // همان منطق status_pay قدیمی برای هر سطر (جبران unpay-refund + ذخیره)
        foreach (WalletHistory::whereIn('id', $this->selected)->get() as $history) {
            $this->applyStatusPay($history, $this->bulkStatus);
        }

        $this->confirmingBulkStatus = false;
        $this->bulkStatus = '';
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'وضعیت پرداخت درخواست‌های انتخاب‌شده بروزرسانی شد.', type: 'success');
    }

    /* ---------------- خروجی اکسل (پورت export قدیمی — open-url به روت قدیمی) ---------------- */

    public function exportRequestDeposit(): void
    {
        auth('adminPanel')->user()?->can('request_deposit') ?: abort(403);

        // قدیمی: RequestDepositExport — فقط source_type/export_type را می‌خواند
        $url = route('np.request-deposit.export')
            . '?source_type=' . urlencode($this->exportSource)
            . '&export_type=excel';

        $this->exportModal = false;

        $this->dispatch('open-url', url: $url);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.finance.request-deposit')
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

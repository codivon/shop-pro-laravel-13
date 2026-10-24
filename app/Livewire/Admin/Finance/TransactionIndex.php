<?php

namespace App\Livewire\Admin\Finance;

use App\Models\Transaction;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست تراکنش ها')]
class TransactionIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) — پورت Transaction::scopeFilter ---------------- */

    #[Url(as: 'fullname', except: '')]
    public string $fullname = '';

    #[Url(as: 'username', except: '')]
    public string $username = '';

    #[Url(as: 'transId', except: '')]
    public string $transId = '';

    /* دقت: نام ستون در جدول transactions واقعاً camelCase است: cardNumber */
    #[Url(as: 'cardNumber', except: '')]
    public string $cardNumber = '';

    /* آیدی تراکنش (بودن نام $id ریسک تداخل با Livewire دارد → transactionId) */
    #[Url(as: 'id', except: '')]
    public string $transactionId = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'order_id', except: '')]
    public string $orderId = '';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    /* ---------------- مودال مشاهده جزئیات ---------------- */

    public bool $showDetail = false;

    public ?int $viewingTransaction = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('payments.transactions.index') ?: abort(403);
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['fullname', 'username', 'transId', 'cardNumber', 'transactionId', 'status', 'orderId', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    /** بازنشانی همه فیلترها */
    public function applyFilters(): void
    {
        $this->fullname = '';
        $this->username = '';
        $this->transId = '';
        $this->cardNumber = '';
        $this->transactionId = '';
        $this->status = '';
        $this->orderId = '';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    /* ---------------- کوئری لیست (پورت دقیق Transaction::scopeFilter) ---------------- */

    protected function baseQuery()
    {
        $query = Transaction::query()->with(['user', 'transactionable']);

        // پورت: query.fullname → whereHas('user', WhereRaw concat(first_name,' ',last_name) like)
        if ($this->fullname !== '') {
            $fullname = $this->fullname;

            $query->whereHas('user', function ($q) use ($fullname) {
                $q->whereRaw("concat(first_name, ' ', last_name) like ?", ['%' . $fullname . '%']);
            });
        }

        // پورت: query.username → whereHas('user', username like)
        if ($this->username !== '') {
            $username = $this->username;

            $query->whereHas('user', function ($q) use ($username) {
                $q->where('username', 'like', '%' . $username . '%');
            });
        }

        // پورت: query.cardNumber → Where('cardNumber', like)  (ستون camelCase)
        if ($this->cardNumber !== '') {
            $query->where('cardNumber', 'like', '%' . $this->cardNumber . '%');
        }

        // پورت: query.transId → Where('transId', like)
        if ($this->transId !== '') {
            $query->where('transId', 'like', '%' . $this->transId . '%');
        }

        // پورت: query.id → Where('id', exact)
        if ($this->transactionId !== '' && ctype_digit($this->transactionId)) {
            $query->where('id', (int) $this->transactionId);
        }

        // پورت: query.status → فقط '0' یا '1'
        if (in_array($this->status, ['0', '1'], true)) {
            $query->where('status', $this->status);
        }

        // پورت: query.order_id → whereHasMorph روی Order
        if ($this->orderId !== '' && ctype_digit($this->orderId)) {
            $orderId = (int) $this->orderId;

            $query->whereHasMorph('transactionable', [\App\Models\Order::class], function ($q) use ($orderId) {
                $q->where('id', $orderId);
            });
        }

        // قدیمی: بدون sort → latest (datatable پیش‌فرض بر اساس جدیدترین)
        return $query->latest();
    }

    #[Computed]
    public function transactions()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- آمار سربرگ ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        return [
            'total' => Transaction::count(),
            'success' => Transaction::where('status', 1)->count(),
            'failed' => Transaction::where('status', 0)->count(),
        ];
    }

    /* ---------------- برچسب نوع تراکنش (پورت Transaction::type()) ---------------- */

    public static function typeLabel(Transaction $transaction): string
    {
        return match ($transaction->transactionable_type) {
            \App\Models\Order::class => 'خرید محصول',
            \App\Models\WalletHistory::class => 'شارژ کیف پول',
            default => '--',
        };
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->transactions->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public function openDetail(int $id): void
    {
        $this->viewingTransaction = $id;
        $this->showDetail = true;
    }

    #[Computed]
    public function detailTransaction(): ?Transaction
    {
        if (! $this->viewingTransaction) {
            return null;
        }

        return Transaction::query()
            ->with(['user', 'transactionable'])
            ->find($this->viewingTransaction);
    }

    /* ---------------- حذف ---------------- */

    public function confirmDelete(int $id): void
    {
        $this->authorizeTransaction('payments.transactions.delete');
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        $this->authorizeTransaction('payments.transactions.delete');

        $transaction = Transaction::find($this->confirmingDelete);

        if ($transaction) {
            $transaction->delete(); // پورت destroy قدیمی (حذف ساده)
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'تراکنش با موفقیت حذف شد.', type: 'success');
    }

    public function bulkDelete(): void
    {
        $this->authorizeTransaction('payments.transactions.delete');

        // پورت multipleDestroy: اعتبارسنجی ids + حلقه حذف تکی
        $this->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer|exists:transactions,id',
        ], [], ['selected' => 'تراکنش‌های انتخاب‌شده']);

        foreach (Transaction::whereIn('id', $this->selected)->get() as $transaction) {
            $transaction->delete();
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'تراکنش‌های انتخاب‌شده حذف شدند.', type: 'success');
    }

    protected function authorizeTransaction(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
    }

    /* ---------------- تاریخ جلالی ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.transactions.index')
            ->layout('admin-panel::layout');
    }
}

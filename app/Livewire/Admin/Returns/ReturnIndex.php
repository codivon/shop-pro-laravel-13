<?php

namespace App\Livewire\Admin\Returns;

use App\Models\ReturnRequest;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('مدیریت مرجوعی‌ها')]
class ReturnIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'payment_type', except: '')]
    public string $paymentType = '';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    public int $perPage = 15;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('returns.index') ?: abort(403);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['status', 'paymentType', 'search', 'perPage'])) {
            $this->resetPage();
        }
    }

    /** بازنشانی همه فیلترها */
    public function applyFilters(): void
    {
        $this->status = '';
        $this->paymentType = '';
        $this->search = '';
        $this->resetPage();
    }

    /* ---------------- آمار سربرگ (پورت آرایه $stats قدیمی) ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        return [
            'total'               => ReturnRequest::count(),
            'pending'             => ReturnRequest::where('status', 'pending')->count(),
            'approved'            => ReturnRequest::where('status', 'approved')->count(),
            'shipped_by_customer' => ReturnRequest::where('status', 'shipped_by_customer')->count(),
            'received'            => ReturnRequest::where('status', 'received')->count(),
            'reshipped'           => ReturnRequest::where('status', 'reshipped')->count(),
            'completed'           => ReturnRequest::where('status', 'completed')->count(),
            'rejected'            => ReturnRequest::where('status', 'rejected')->count(),
            'cash_count'          => ReturnRequest::where('payment_type', 'cash')->count(),
            'credit_count'        => ReturnRequest::where('payment_type', 'credit')->count(),
            'installment_count'   => ReturnRequest::where('payment_type', 'installment')->count(),
        ];
    }

    /* ---------------- کوئری لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function returns()
    {
        $query = ReturnRequest::query()
            ->with(['order', 'orderItem.product', 'user', 'reason']);

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->paymentType !== '') {
            $query->where('payment_type', $this->paymentType);
        }

        // جستجو: شماره سفارش دقیق «یا» نام/موبایل کاربر
        // ⚠ اصلاح باگ قدیمی: در legacy کوئری `$query->whereHas('order', ...)->orWhereHas('user', ...)`
        // بدون گروه‌بندی نوشته شده بود → فیلترهای status/payment_type وقتی search پر باشد
        // به‌کل نادیده گرفته می‌شدند (OR سراسری روی کل کوئری). اینجا داخل یک where(function) گروه شد.
        // همچنین where('id', $search) با ورودی غیرعددی نتیجه‌ای نمی‌دهد؛ تطبیق دقیق id
        // فقط برای ورودی عددی اعمال می‌شود (exact match مثل قدیمی).
        if ($this->search !== '') {
            $search = trim($this->search);

            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });

                if (ctype_digit($search)) {
                    $q->orWhereHas('order', fn ($o) => $o->where('id', (int) $search));
                }
            });
        }

        return $query->latest()->paginate($this->perPage);
    }

    /* ---------------- هلپرهای بج (تبدیل رنگ‌های inline قدیمی به کلاس Tailwind) ---------------- */

    /** کلاس بج وضعیت — رنگ‌های hex قدیمی به کلاس‌های خنثی Tailwind تبدیل شدند */
    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'pending'             => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400',
            'approved'            => 'bg-blue-50 text-blue-700 dark:bg-blue-500/12 dark:text-blue-400',
            'shipped_by_customer' => 'bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400',
            'received'            => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/12 dark:text-cyan-400',
            'reshipped'           => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/12 dark:text-indigo-400',
            'completed'           => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400',
            'rejected'            => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400',
            'cancelled'           => 'bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2',
            'failed'              => 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400',
            default               => 'bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2',
        };
    }

    /** آیکون وضعیت — نزدیک‌ترین آیکون موجود در icons-map (fa-* قدیمی وجود ندارد) */
    public static function statusIcon(string $status): string
    {
        return match ($status) {
            'pending'             => 'clock',
            'approved'            => 'check-circle',
            'shipped_by_customer' => 'truck',
            'received'            => 'box',
            'reshipped'           => 'truck',
            'completed'           => 'check-check',
            'rejected'            => 'circle-x',
            'cancelled'           => 'circle-alert',
            'failed'              => 'triangle-alert',
            default               => 'circle-dot',
        };
    }

    /** کلاس بج نوع پرداخت */
    public static function paymentBadgeClass(string $paymentType): string
    {
        return match ($paymentType) {
            'cash'        => 'bg-blue-50 text-blue-700 dark:bg-blue-500/12 dark:text-blue-400',
            'credit'      => 'bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400',
            'installment' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400',
            default       => 'bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2',
        };
    }

    /** آیکون نوع پرداخت */
    public static function paymentIcon(string $paymentType): string
    {
        return match ($paymentType) {
            'cash'        => 'banknote',
            'credit'      => 'credit-card',
            'installment' => 'hourglass',
            default       => 'circle-dot',
        };
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public static function itemTitle($return): string
    {
        return Str::limit($return->orderItem?->title ?? '—', 25);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.returns.index')
            ->layout('admin-panel::layout');
    }
}

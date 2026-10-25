<?php

namespace App\Livewire\Admin\PriceAlert;

use App\Models\PriceAlert;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * لیست هشدارهای کاهش قیمت — پورت ۱:۱ Back\PriceAlertController به Livewire
 * دسترسی: products.price-alert
 */
#[Title('هشدار کاهش قیمت')]
class PriceAlertIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /** فاز ۸۳ — فیلتر وضعیت: all | active | notified */
    public string $filter = 'all';

    /* ---------------- مودال جزئیات ---------------- */

    public bool $showDetail = false;

    public ?int $viewingAlert = null;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.price-alert') ?: abort(403);
    }

    /* ---------------- لیست ---------------- */

    #[Computed]
    public function priceAlerts()
    {
        return PriceAlert::query()
            ->with(['product', 'user'])
            ->when($this->filter === 'active', fn ($q) => $q->where('active', true))
            ->when($this->filter === 'notified', fn ($q) => $q->where('active', false))
            ->latest()
            ->paginate($this->perPage);
    }

    /** فاز ۸۳ — تغییر تب فیلتر + ریست صفحه‌بندی */
    public function setFilter(string $filter): void
    {
        auth('adminPanel')->user()?->can('products.price-alert') ?: abort(403);

        $this->filter = in_array($filter, ['all', 'active', 'notified'], true) ? $filter : 'all';
        $this->resetPage();
    }

    /** فاز ۸۳ — برچسب کانال‌های انتخابی مشتری */
    public function channelsLabel(PriceAlert $alert): array
    {
        $labels = [];

        if ($alert->notify_sms) {
            $labels[] = 'پیامک';
        }

        if ($alert->notify_profile) {
            $labels[] = 'اعلان پروفایل';
        }

        // رکوردهای فاز ۷۸ (قبل از ستون‌ها): هر دو ستون صفر = پیامک (رفتار قدیمی)
        if (! $alert->notify_sms && ! $alert->notify_profile) {
            $labels[] = 'پیامک';
        }

        return $labels;
    }

    #[Computed(persist: true, seconds: 60)]
    public function totalCount(): int
    {
        return PriceAlert::count();
    }

    /** فاز ۸۳ — شمارندهٔ تب‌های فیلتر */
    #[Computed]
    public function activeCount(): int
    {
        return PriceAlert::where('active', true)->count();
    }

    #[Computed]
    public function notifiedCount(): int
    {
        return PriceAlert::where('active', false)->count();
    }

    /* ---------------- مودال جزئیات ---------------- */

    public function openDetail(int $id): void
    {
        auth('adminPanel')->user()?->can('products.price-alert') ?: abort(403);

        if (! PriceAlert::find($id)) {
            $this->dispatch('toast', message: 'رکورد یافت نشد.', type: 'error');

            return;
        }

        $this->viewingAlert = $id;
        $this->showDetail = true;
    }

    #[Computed]
    public function detailAlert(): ?PriceAlert
    {
        if (! $this->viewingAlert) {
            return null;
        }

        return PriceAlert::query()->with('product')->find($this->viewingAlert);
    }

    /* ---------------- حذف ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('products.price-alert') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('products.price-alert') ?: abort(403);

        if ($alert = PriceAlert::find($this->confirmingDelete)) {
            $alert->delete();
        }

        $this->confirmingDelete = null;

        if ($this->viewingAlert !== null && ! PriceAlert::find($this->viewingAlert)) {
            $this->showDetail = false;
            $this->viewingAlert = null;
        }

        $this->dispatch('toast', message: 'هشدار قیمت با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    /** لینک فروشگاهی محصول */
    public function productFrontLink(PriceAlert $alert): ?string
    {
        if ($alert->product && Route::has('front.products.show')) {
            return route('front.products.show', ['product' => $alert->product]);
        }

        return null;
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.price-alert.price-alerts')
            ->layout('admin-panel::layout');
    }
}

<?php

namespace App\Livewire\Admin\StockNotify;

use App\Models\StockNotify;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * لیست اطلاع از موجودی — پورت ۱:۱ Back\StockNotifyController به Livewire
 * دسترسی: products.stock-notify (middleware can قدیمی)
 */
#[Title('اطلاع از موجودی')]
class StockNotifyIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public bool $showDetail = false;

    public ?int $viewingNotify = null;

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.stock-notify') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function stockNotifies()
    {
        return StockNotify::query()
            ->with('product')
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function totalCount(): int
    {
        return StockNotify::count();
    }

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public function openDetail(int $id): void
    {
        auth('adminPanel')->user()?->can('products.stock-notify') ?: abort(403);

        if (! StockNotify::find($id)) {
            $this->dispatch('toast', message: 'رکورد یافت نشد.', type: 'error');

            return;
        }

        $this->viewingNotify = $id;
        $this->showDetail = true;
    }

    #[Computed]
    public function detailNotify(): ?StockNotify
    {
        if (! $this->viewingNotify) {
            return null;
        }

        return StockNotify::query()->with('product')->find($this->viewingNotify);
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('products.stock-notify') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('products.stock-notify') ?: abort(403);

        if ($notify = StockNotify::find($this->confirmingDelete)) {
            $notify->delete();
        }

        $this->confirmingDelete = null;

        if ($this->viewingNotify !== null && ! StockNotify::find($this->viewingNotify)) {
            $this->showDetail = false;
            $this->viewingNotify = null;
        }

        $this->dispatch('toast', message: 'اطلاع از موجودی با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    /** لینک فروشگاهی محصول — پورت دقیق شرط Route::has('front.products.show') قدیمی */
    public function productFrontLink(StockNotify $notify): ?string
    {
        if ($notify->product && Route::has('front.products.show')) {
            return route('front.products.show', ['product' => $notify->product]);
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
        return view('admin-panel::livewire.admin.stock-notify.stock-notifies')
            ->layout('admin-panel::layout');
    }
}

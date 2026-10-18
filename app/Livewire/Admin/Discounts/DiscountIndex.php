<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست تخفیف‌ها')]
class DiscountIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 15;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    /* ---------------- گیت دسترسی ---------------- */

    public function mount(): void
    {
        $this->authorizeDiscount('discounts.index');
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    /** بازنشانی فیلترها */
    public function applyFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /* ---------------- آمار سربرگ ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        return [
            'total' => Discount::count(),
            'published' => Discount::where('published', true)->count(),
        ];
    }

    /* ---------------- کوئری لیست (پورت DiscountController@index) ---------------- */

    protected function baseQuery()
    {
        // مثل قدیمی: detectLang + latest؛ تعداد سفارش موفق با scopePaid روی Order
        $query = Discount::detectLang()
            ->withCount(['orders as paid_orders_count' => fn ($q) => $q->paid()]);

        if ($this->search !== '') {
            $term = str_replace(['%', '_'], ['\%', '\_'], $this->search);

            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        return $query->latest();
    }

    #[Computed]
    public function discounts()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- حذف ---------------- */

    public function confirmDelete(int $id): void
    {
        $this->authorizeDiscount('discounts.delete');
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        $this->authorizeDiscount('discounts.delete');

        if ($discount = Discount::find($this->confirmingDelete)) {
            $discount->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'تخفیف با موفقیت حذف شد.', type: 'success');
    }

    protected function authorizeDiscount(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
    }

    /* ---------------- نمایش ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.discounts.index')
            ->layout('admin-panel::layout');
    }
}

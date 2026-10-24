<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Filter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * لیست فیلترها — پورت FilterController@index/destroy
 * دسترسی: filters.index / filters.delete
 */
#[Title('فیلترها')]
class FilterIndex extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('filters.index') ?: abort(403);
    }

    /* ---------------- لیست ---------------- */

    #[Computed]
    public function filters()
    {
        // پورت: Filter::latest()->paginate(10) + شمارش موارد مرتبط
        return Filter::query()
            ->withCount('related')
            ->latest()
            ->paginate($this->perPage);
    }

    /* ---------------- حذف (پورت destroy) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('filters.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('filters.delete') ?: abort(403);

        if ($filter = Filter::find($this->confirmingDelete)) {
            $filter->related()->delete(); // پاک‌سازی مورف‌ها (در prod با cascade هم انجام می‌شد)
            $filter->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'فیلتر حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.filters')
            ->layout('admin-panel::layout');
    }
}

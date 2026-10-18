<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\SpecType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('انواع مشخصات')]
class SpecTypeIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلتر (State در URL) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 15;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.spectypes') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function spectypes()
    {
        return SpecType::query()
            ->detectLang()
            ->with('specificationGroups:id,name')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->latest()
            ->paginate($this->perPage);
    }

    public function applyFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /* ---------------- تعداد گروه مشخصات یکتا (مثل getData قدیمی) ---------------- */

    public function groupCount(SpecType $spectype): int
    {
        return $spectype->specificationGroups->unique('id')->count();
    }

    /* ---------------- حذف (پورت ۱:۱ destroy قدیمی: detach سپس delete) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('products.spectypes') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('products.spectypes') ?: abort(403);

        $spectype = SpecType::find($this->confirmingDelete);

        if ($spectype) {
            $spectype->specifications()->detach();
            $spectype->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'نوع مشخصات با موفقیت حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.spectypes')
            ->layout('admin-panel::layout');
    }
}

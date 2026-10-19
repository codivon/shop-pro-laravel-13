<?php

namespace App\Livewire\Admin\Geo;

use App\Models\Province;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('لیست استان‌ها')]
class ProvinceIndex extends Component
{
    use WithPagination;

    public int $perPage = 10;

    /* ---------------- فیلترها (پورت filters/scopeFilter قدیمی — State در URL) ---------------- */

    #[Url(as: 'name', except: '')]
    public string $filterName = '';

    #[Url(as: 'city_name', except: '')]
    public string $filterCityName = '';

    #[Url(as: 'is_active', except: '')]
    public string $filterIsActive = '';

    /* ---------------- مودال ایجاد (پورت create/store قدیمی) ---------------- */

    public bool $showCreateModal = false;

    public string $createName = '';

    public string $createLatitude = '';

    public string $createLongitude = '';

    public bool $createIsActive = true;

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingId = null;

    public string $editName = '';

    public string $editLatitude = '';

    public string $editLongitude = '';

    public string $editOrdering = '';

    public bool $editIsActive = true;

    /* ---------------- حذف تکی/گروهی ---------------- */

    public ?int $confirmingDelete = null;

    public ?string $confirmingBulkDelete = null;

    public array $selected = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['filterName', 'filterCityName', 'filterIsActive', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست (پورت apiIndex قدیمی + تعداد شهرها) ---------------- */

    #[Computed]
    public function provinces()
    {
        return Province::query()
            ->detectLang()
            ->withCount('cities')
            // پورت scopeFilter قدیمی (query.name / query.city_name / query.is_active)
            ->when($this->filterName !== '', fn ($q) => $q->where('name', 'like', '%' . $this->filterName . '%'))
            ->when($this->filterCityName !== '', fn ($q) => $q->whereHas('cities', fn ($c) => $c->where('name', 'like', '%' . $this->filterCityName . '%')))
            // «همه» (رشته خالی) هیچ شرطی اضافه نمی‌کند
            ->when($this->filterIsActive !== '', fn ($q) => $q->where('is_active', (bool) $this->filterIsActive))
            ->orderBy('ordering')
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    public function applyFilters(): void
    {
        $this->reset(['filterName', 'filterCityName', 'filterIsActive']);
        $this->resetPage();
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createProvince(): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.create') ?: abort(403);

        // رول‌های StoreProvinceRequest قدیمی
        $data = $this->validate([
            'createName'      => 'required|string',
            'createLatitude'  => 'nullable|string',
            'createLongitude' => 'nullable|string',
        ], [], [
            'createName'      => 'نام',
            'createLatitude'  => 'عرض جغرافیایی',
            'createLongitude' => 'طول جغرافیایی',
        ]);

        $data['name']      = $this->createName;
        $data['latitude']  = $this->createLatitude ?: null;
        $data['longitude'] = $this->createLongitude ?: null;
        // prepareForValidation قدیمی: is_active = has('is_active')
        $data['is_active'] = $this->createIsActive;
        $data['ordering']  = (int) Province::max('ordering') + 1;
        $data['lang']      = app()->getLocale();

        Province::create($data);

        $this->reset(['createName', 'createLatitude', 'createLongitude', 'showCreateModal']);

        $this->createIsActive = true;

        $this->dispatch('toast', message: 'استان با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ update قدیمی) ---------------- */

    public function editProvince(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.update') ?: abort(403);

        $province = Province::find($id);

        if (! $province) {
            return;
        }

        $this->editingId      = $province->id;
        $this->editName       = (string) $province->name;
        $this->editLatitude   = (string) $province->latitude;
        $this->editLongitude  = (string) $province->longitude;
        $this->editOrdering   = (string) $province->ordering;
        $this->editIsActive   = (bool) $province->is_active;
    }

    public function updateProvince(): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.update') ?: abort(403);

        // رول‌های UpdateProvinceRequest قدیمی (+ordering)
        $data = $this->validate([
            'editName'      => 'required|string',
            'editLatitude'  => 'nullable|string',
            'editLongitude' => 'nullable|string',
            'editOrdering'  => 'nullable',
        ], [], [
            'editName'      => 'نام',
            'editLatitude'  => 'عرض جغرافیایی',
            'editLongitude' => 'طول جغرافیایی',
            'editOrdering'  => 'ترتیب',
        ]);

        $province = $this->editingId ? Province::find($this->editingId) : null;

        if (! $province) {
            $this->reset(['editingId', 'editName', 'editLatitude', 'editLongitude', 'editOrdering']);

            return;
        }

        $province->update([
            'name'      => $this->editName,
            'latitude'  => $this->editLatitude ?: null,
            'longitude' => $this->editLongitude ?: null,
            'ordering'  => $this->editOrdering !== '' ? (int) $this->editOrdering : $province->ordering,
            // prepareForValidation قدیمی: is_active = has('is_active')
            'is_active' => $this->editIsActive,
        ]);

        $this->reset(['editingId', 'editName', 'editLatitude', 'editLongitude', 'editOrdering']);

        $this->dispatch('toast', message: 'استان با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- ترتیب (پورت sort قدیمی — جابه‌جایی همسایه‌ها و بازتخصیص ۱..n) ---------------- */

    public function moveUp(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.update') ?: abort(403);

        $this->move($id, -1);
    }

    public function moveDown(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.update') ?: abort(403);

        $this->move($id, 1);
    }

    protected function move(int $id, int $direction): void
    {
        $ids = Province::detectLang()
            ->orderBy('ordering')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        $index = $ids->search($id);

        if ($index === false || $index === null) {
            return;
        }

        $target = $index + $direction;

        if ($target < 0 || $target >= $ids->count()) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        foreach ($ids as $i => $provinceId) {
            Province::where('id', $provinceId)->update(['ordering' => $i + 1]);
        }

        $this->dispatch('toast', message: 'ترتیب استان‌ها به‌روزرسانی شد.', type: 'success');
    }

    /* ---------------- حذف تکی/گروهی (پورت destroy / multipleDestroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.delete') ?: abort(403);

        $province = Province::find($this->confirmingDelete);

        if ($province) {
            $province->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'استان با موفقیت حذف شد.', type: 'success');
    }

    public function confirmBulkDelete(): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.delete') ?: abort(403);

        if (count($this->selected)) {
            $this->confirmingBulkDelete = 'open';
        }
    }

    public function multipleDestroy(): void
    {
        auth('adminPanel')->user()?->can('carriers.provinces.delete') ?: abort(403);

        $this->validate([
            'selected'   => 'required|array',
            'selected.*' => 'required|exists:provinces,id',
        ], [], ['selected' => 'شناسه‌ها']);

        // پورت حلقه destroy($multiple=true) قدیمی
        foreach (Province::whereIn('id', $this->selected)->get() as $province) {
            $province->delete();
        }

        $this->selected = [];
        $this->confirmingBulkDelete = null;

        $this->dispatch('toast', message: 'استان‌های انتخاب شده با موفقیت حذف شدند', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public function toggleSelectAll(): void
    {
        $ids = $this->provinces->pluck('id')->map(fn ($id) => (string) $id)->all();

        if (count(array_diff($ids, $this->selected)) === 0 && count($this->selected) > 0) {
            $this->selected = array_values(array_diff($this->selected, $ids));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.geo.provinces')
            ->layout('admin-panel::layout');
    }
}

<?php

namespace App\Livewire\Admin\Geo;

use App\Models\City;
use App\Models\Province;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('شهرها')]
class CityIndex extends Component
{
    use WithPagination;

    public int $perPage = 10;

    /** استان انتخابی صفحه (پورت provinces.show/apiIndex/{province} قدیمی — بدون پارامتر روت، از URL) */
    #[Url(as: 'province', except: null)]
    public ?int $provinceId = null;

    /* ---------------- فیلترها (پورت filters/scopeFilter قدیمی) ---------------- */

    #[Url(as: 'name', except: '')]
    public string $filterName = '';

    #[Url(as: 'is_active', except: '')]
    public string $filterIsActive = '';

    /* ---------------- مودال ایجاد (پورت create/store قدیمی) ---------------- */

    public bool $showCreateModal = false;

    public string $createName = '';

    public string $createProvinceId = '';

    public string $createLatitude = '';

    public string $createLongitude = '';

    public bool $createIsActive = true;

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingId = null;

    public string $editName = '';

    public string $editProvinceId = '';

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
        // پورت authorize قدیمی CityController::apiIndex → carriers.provinces.show
        auth('adminPanel')->user()?->can('carriers.provinces.show') ?: abort(403);

        if (! $this->provinceId) {
            $this->provinceId = Province::detectLang()->orderBy('ordering')->orderBy('id')->value('id');
        }
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['filterName', 'filterIsActive', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function updatedProvinceId(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    /* ---------------- لیست (پورت apiIndex/{province} قدیمی) ---------------- */

    #[Computed]
    public function cities()
    {
        return City::query()
            ->detectLang()
            ->when($this->provinceId, fn ($q) => $q->where('province_id', $this->provinceId))
            ->with('province')
            // پورت scopeFilter قدیمی (query.name / query.is_active)
            ->when($this->filterName !== '', fn ($q) => $q->where('name', 'like', '%' . $this->filterName . '%'))
            ->when($this->filterIsActive !== '', fn ($q) => $q->where('is_active', (bool) $this->filterIsActive))
            ->orderBy('ordering')
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function provinces()
    {
        return Province::detectLang()->orderBy('ordering')->orderBy('id')->get(['id', 'name']);
    }

    #[Computed]
    public function currentProvince()
    {
        return $this->provinceId ? Province::find($this->provinceId) : null;
    }

    public function applyFilters(): void
    {
        $this->reset(['filterName', 'filterIsActive']);
        $this->resetPage();
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createCity(): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.create') ?: abort(403);

        // رول‌های StoreCityRequest قدیمی
        $this->validate([
            'createName'        => 'required|string',
            'createProvinceId'  => 'required|exists:provinces,id',
            'createLatitude'    => 'nullable|string',
            'createLongitude'   => 'nullable|string',
        ], [], [
            'createName'        => 'نام',
            'createProvinceId'  => 'استان',
            'createLatitude'    => 'عرض جغرافیایی',
            'createLongitude'   => 'طول جغرافیایی',
        ]);

        City::create([
            'name'        => $this->createName,
            'province_id' => (int) $this->createProvinceId,
            'latitude'    => $this->createLatitude ?: null,
            'longitude'   => $this->createLongitude ?: null,
            // prepareForValidation قدیمی: is_active = has('is_active')
            'is_active'   => $this->createIsActive,
            'ordering'    => (int) City::max('ordering') + 1,
            'lang'        => app()->getLocale(),
        ]);

        $this->reset(['createName', 'createLatitude', 'createLongitude', 'showCreateModal']);

        $this->createIsActive = true;
        $this->createProvinceId = (string) $this->provinceId;

        $this->dispatch('toast', message: 'شهر با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ update قدیمی) ---------------- */

    public function editCity(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.update') ?: abort(403);

        $city = City::find($id);

        if (! $city) {
            return;
        }

        $this->editingId     = $city->id;
        $this->editName      = (string) $city->name;
        $this->editProvinceId = (string) $city->province_id;
        $this->editLatitude  = (string) $city->latitude;
        $this->editLongitude = (string) $city->longitude;
        $this->editOrdering  = (string) $city->ordering;
        $this->editIsActive  = (bool) $city->is_active;
    }

    public function updateCity(): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.update') ?: abort(403);

        // رول‌های UpdateCityRequest قدیمی (+ordering)
        $this->validate([
            'editName'        => 'required|string',
            'editProvinceId'  => 'required|exists:provinces,id',
            'editLatitude'    => 'nullable|string',
            'editLongitude'   => 'nullable|string',
            'editOrdering'    => 'nullable',
        ], [], [
            'editName'        => 'نام',
            'editProvinceId'  => 'استان',
            'editLatitude'    => 'عرض جغرافیایی',
            'editLongitude'   => 'طول جغرافیایی',
            'editOrdering'    => 'ترتیب',
        ]);

        $city = $this->editingId ? City::find($this->editingId) : null;

        if (! $city) {
            $this->reset(['editingId', 'editName', 'editProvinceId', 'editLatitude', 'editLongitude', 'editOrdering']);

            return;
        }

        $city->update([
            'name'        => $this->editName,
            'province_id' => (int) $this->editProvinceId,
            'latitude'    => $this->editLatitude ?: null,
            'longitude'   => $this->editLongitude ?: null,
            'ordering'    => $this->editOrdering !== '' ? (int) $this->editOrdering : $city->ordering,
            // prepareForValidation قدیمی: is_active = has('is_active')
            'is_active'   => $this->editIsActive,
        ]);

        $this->reset(['editingId', 'editName', 'editProvinceId', 'editLatitude', 'editLongitude', 'editOrdering']);

        $this->dispatch('toast', message: 'شهر با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- ترتیب (پورت sort قدیمی — جابه‌جایی همسایه‌ها و بازتخصیص ۱..n) ---------------- */

    public function moveUp(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.update') ?: abort(403);

        $this->move($id, -1);
    }

    public function moveDown(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.update') ?: abort(403);

        $this->move($id, 1);
    }

    protected function move(int $id, int $direction): void
    {
        $ids = City::detectLang()
            ->when($this->provinceId, fn ($q) => $q->where('province_id', $this->provinceId))
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

        foreach ($ids as $i => $cityId) {
            City::where('id', $cityId)->update(['ordering' => $i + 1]);
        }

        $this->dispatch('toast', message: 'ترتیب شهرها به‌روزرسانی شد.', type: 'success');
    }

    /* ---------------- حذف تکی/گروهی (پورت destroy / multipleDestroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.delete') ?: abort(403);

        $city = City::find($this->confirmingDelete);

        if ($city) {
            $city->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'شهر با موفقیت حذف شد.', type: 'success');
    }

    public function confirmBulkDelete(): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.delete') ?: abort(403);

        if (count($this->selected)) {
            $this->confirmingBulkDelete = 'open';
        }
    }

    public function multipleDestroy(): void
    {
        auth('adminPanel')->user()?->can('carriers.cities.delete') ?: abort(403);

        $this->validate([
            'selected'   => 'required|array',
            'selected.*' => 'required|exists:cities,id',
        ], [], ['selected' => 'شناسه‌ها']);

        // پورت حلقه destroy($multiple=true) قدیمی
        foreach (City::whereIn('id', $this->selected)->get() as $city) {
            $city->delete();
        }

        $this->selected = [];
        $this->confirmingBulkDelete = null;

        $this->dispatch('toast', message: 'شهرهای انتخاب شده با موفقیت حذف شدند', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public function toggleSelectAll(): void
    {
        $ids = $this->cities->pluck('id')->map(fn ($id) => (string) $id)->all();

        if (count(array_diff($ids, $this->selected)) === 0 && count($this->selected) > 0) {
            $this->selected = array_values(array_diff($this->selected, $ids));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.geo.cities')
            ->layout('admin-panel::layout');
    }
}

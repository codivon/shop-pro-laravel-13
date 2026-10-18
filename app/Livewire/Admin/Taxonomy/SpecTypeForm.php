<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Specification;
use App\Models\SpecificationGroup;
use App\Models\SpecType;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('admin-panel::layout')]
#[Title('ویرایش نوع مشخصات')]
class SpecTypeForm extends Component
{
    public SpecType $spectype;

    public string $name = '';

    /**
     * ساختار تکرارشونده فرم:
     * [ ['name' => 'نام گروه', 'specifications' => [ ['name' => 'نام مشخصه'], ... ]], ... ]
     */
    public array $groups = [];

    public function mount(SpecType $spectype): void
    {
        auth('adminPanel')->user()?->can('products.spectypes') ?: abort(403);

        $this->spectype = $spectype;
        $this->name = (string) $spectype->name;

        $this->loadGroups();
    }

    /* ---------------- پیش‌پرکردن از پیوت موجود (مثل ویوی edit قدیمی) ---------------- */

    protected function loadGroups(): void
    {
        $seenGroupIds = [];
        $groups = [];

        // رابطه از قبل با group_ordering مرتب شده است
        foreach ($this->spectype->specificationGroups as $specGroup) {
            // مثل قدیمی unique() — گروه تکراری یک‌بار نمایش داده می‌شود
            if (in_array($specGroup->id, $seenGroupIds)) {
                continue;
            }

            $seenGroupIds[] = $specGroup->id;

            $specifications = $this->spectype->specifications()
                ->wherePivot('specification_group_id', $specGroup->id)
                ->orderByPivot('specification_ordering')
                ->get(['specifications.id', 'specifications.name']);

            $groups[] = [
                'name'          => (string) $specGroup->name,
                'specifications' => $specifications
                    ->map(fn (Specification $spec) => ['name' => (string) $spec->name])
                    ->values()
                    ->all(),
            ];
        }

        $this->groups = $groups;
    }

    /* ---------------- اکشن‌های ریپیتر ---------------- */

    public function addGroup(): void
    {
        $this->groups[] = ['name' => '', 'specifications' => [['name' => '']]];
    }

    public function removeGroup(int $index): void
    {
        unset($this->groups[$index]);
        $this->groups = array_values($this->groups);
    }

    public function addSpec(int $groupIndex): void
    {
        if (! isset($this->groups[$groupIndex])) {
            return;
        }

        $this->groups[$groupIndex]['specifications'][] = ['name' => ''];
    }

    public function removeSpec(int $groupIndex, int $specIndex): void
    {
        if (! isset($this->groups[$groupIndex]['specifications'][$specIndex])) {
            return;
        }

        unset($this->groups[$groupIndex]['specifications'][$specIndex]);
        $this->groups[$groupIndex]['specifications'] = array_values($this->groups[$groupIndex]['specifications']);
    }

    /* ---------------- ذخیره (پورت ۱:۱ update قدیمی) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('products.spectypes') ?: abort(403);

        $this->validate([
            'name' => ['required', Rule::unique('spec_types', 'name')->ignore($this->spectype->id)],
        ], [
            'name.required' => 'نام نوع مشخصات الزامی است.',
            'name.unique'   => 'این نام قبلاً ثبت شده است.',
        ], [
            'name' => 'نام',
        ]);

        $this->spectype->update([
            'name' => $this->name,
        ]);

        $this->spectype->specifications()->detach();
        $group_ordering = 0;

        foreach ($this->groups as $group) {
            // مثل قدیمی: گروه‌های بدون مشخصه نادیده گرفته می‌شوند
            $specNames = collect($group['specifications'] ?? [])
                ->pluck('name')
                ->map(fn ($specName) => trim((string) $specName))
                ->filter()
                ->unique()
                ->values();

            if ($specNames->isEmpty()) {
                continue;
            }

            // رفع باگ قدیمی: نام گروه خالی باعث firstOrCreate با name=null (خطای DB) می‌شد
            $groupName = trim((string) ($group['name'] ?? ''));

            if ($groupName === '') {
                continue;
            }

            $spec_group = SpecificationGroup::firstOrCreate([
                'name' => $groupName,
                'lang' => app()->getLocale(),
            ]);

            $specification_ordering = 0;

            foreach ($specNames as $specName) {
                $spec = Specification::firstOrCreate([
                    'name' => $specName,
                ]);

                $this->spectype->specifications()->attach([
                    $spec->id => [
                        'specification_group_id' => $spec_group->id,
                        'group_ordering'         => $group_ordering,
                        'specification_ordering' => $specification_ordering++,
                    ],
                ]);
            }

            $group_ordering++;
        }

        session()->flash('np-toast', ['type' => 'success', 'message' => 'نوع مشخصات با موفقیت ویرایش شد.']);

        $this->redirectIntended(route('np.spectypes.index', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.spectype-form');
    }
}

<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\AttributeGroup;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('گروه‌های ویژگی')]
class AttributeGroupIndex extends Component
{
    /* ---------------- کارت «گروه جدید» (پورت create/store قدیمی) ---------------- */

    public string $groupName = '';

    public string $groupType = 'checkbox';

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingGroupId = null;

    public string $editName = '';

    public string $editType = 'checkbox';

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی — بدون صفحه‌بندی، مرتب با ordering) ---------------- */

    #[Computed]
    public function groups()
    {
        return AttributeGroup::query()
            ->detectLang()
            ->withCount('get_attributes')
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createGroup(): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.create') ?: abort(403);

        $this->validate([
            'groupName' => 'required',
            'groupType' => 'required|in:color,checkbox',
        ], [], [
            'groupName' => 'نام',
            'groupType' => 'نوع گروه',
        ]);

        // مثل قدیمی: max سراسری + ۱
        $ordering = AttributeGroup::max('ordering') + 1;

        AttributeGroup::create([
            'name'     => $this->groupName,
            'lang'     => app()->getLocale(),
            'type'     => $this->groupType,
            'ordering' => $ordering,
        ]);

        $this->reset(['groupName', 'groupType']);
        $this->groupType = 'checkbox';

        $this->dispatch('toast', message: 'گروه ویژگی با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ update قدیمی) ---------------- */

    public function editGroup(int $id): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.update') ?: abort(403);

        $group = AttributeGroup::find($id);

        if (! $group) {
            return;
        }

        $this->editingGroupId = $group->id;
        $this->editName = (string) $group->name;
        $this->editType = (string) $group->type;
    }

    public function updateGroup(): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.update') ?: abort(403);

        $this->validate([
            'editName' => 'required',
            'editType' => 'required|in:color,checkbox',
        ], [], [
            'editName' => 'نام',
            'editType' => 'نوع گروه',
        ]);

        $group = $this->editingGroupId ? AttributeGroup::find($this->editingGroupId) : null;

        if ($group) {
            // مثل قدیمی ordering دست‌نخورده می‌ماند
            $group->update([
                'name' => $this->editName,
                'type' => $this->editType,
            ]);

            $this->dispatch('toast', message: 'گروه ویژگی با موفقیت ویرایش شد.', type: 'success');
        }

        $this->reset(['editingGroupId', 'editName', 'editType']);
    }

    /* ---------------- ترتیب (پورت sort قدیمی — بازتخصیص ordering از ۱ تا n) ---------------- */

    public function moveUp(int $id): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.update') ?: abort(403);

        $this->move($id, -1);
    }

    public function moveDown(int $id): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.update') ?: abort(403);

        $this->move($id, 1);
    }

    protected function move(int $id, int $direction): void
    {
        $ids = AttributeGroup::detectLang()
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

        // جابه‌جایی همسایه‌ها
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        // مثل قدیمی: بازتخصیص ordering به‌صورت ۱ تا n
        foreach ($ids as $i => $groupId) {
            AttributeGroup::where('id', $groupId)->update(['ordering' => $i + 1]);
        }

        $this->dispatch('toast', message: 'ترتیب گروه‌های ویژگی به‌روزرسانی شد.', type: 'success');
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.delete') ?: abort(403);

        $group = AttributeGroup::find($this->confirmingDelete);

        if ($group) {
            // برابری cascade قدیمی در MySQL (attributes.onDelete cascade → attribute_price هم cascade)
            // به‌صورت دستی اجرا می‌شود تا در SQLite (بدون FK) هم رکورد یتیم نماند
            foreach ($group->get_attributes()->get() as $attribute) {
                $attribute->prices()->detach();
                $attribute->delete();
            }

            $group->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'گروه ویژگی با موفقیت حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.attribute-groups')
            ->layout('admin-panel::layout');
    }
}

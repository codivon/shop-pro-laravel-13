<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('ویژگی‌های گروه')]
class AttributeGroupAttributes extends Component
{
    use WithPagination;

    public AttributeGroup $attributeGroup;

    /* ---------------- فیلتر (State در URL) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 15;

    /* ---------------- کارت «ویژگی جدید» (پورت create/store قدیمی) ---------------- */

    public string $attributeName = '';

    /** مقدار ویژگی (فقط برای گروه‌های رنگی) */
    public string $attributeValue = '#000000';

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(AttributeGroup $attributeGroup): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.index') ?: abort(403);

        $this->attributeGroup = $attributeGroup;
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست (پورت attributesIndex قدیمی — صفحه‌بندی ۱۵تایی) ---------------- */

    #[Computed]
    public function attributesList()
    {
        return $this->attributeGroup
            ->get_attributes()
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->paginate($this->perPage);
    }

    public function applyFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createAttribute(): void
    {
        auth('adminPanel')->user()?->can('attributes.create') ?: abort(403);

        $this->validate([
            'attributeName' => 'required',
        ], [], [
            'attributeName' => 'نام',
        ]);

        // مثل قدیمی: مقدار فقط برای گروه‌های رنگی ذخیره می‌شود، بقیه null
        $value = $this->attributeGroup->type === 'color' ? $this->attributeValue : null;

        Attribute::create([
            'name'               => $this->attributeName,
            'value'              => $value,
            'attribute_group_id' => $this->attributeGroup->id,
        ]);

        $this->reset(['attributeName', 'attributeValue']);
        $this->attributeValue = '#000000';

        $this->dispatch('toast', message: 'ویژگی با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('attributes.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('attributes.delete') ?: abort(403);

        $attribute = Attribute::find($this->confirmingDelete);

        if ($attribute) {
            // برابری cascade قدیمی MySQL روی پیوت attribute_price — قابل‌حمل بین SQLite/MySQL
            $attribute->prices()->detach();

            $attribute->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'ویژگی با موفقیت حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.attribute-group-attributes')
            ->layout('admin-panel::layout');
    }
}

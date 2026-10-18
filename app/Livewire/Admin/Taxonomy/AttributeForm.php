<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('admin-panel::layout')]
#[Title('ویژگی‌ها')]
class AttributeForm extends Component
{
    public ?Attribute $attribute = null;

    public bool $isEditing = false;

    /* ---------------- فیلدهای فرم ---------------- */

    public string $name = '';

    /** شناسه گروه ویژگی (legacy: attribute_group_id) */
    public $attributeGroupId = '';

    /** مقدار ویژگی — فقط برای گروه‌های رنگی */
    public string $value = '#000000';

    public function mount(?Attribute $attribute = null): void
    {
        $this->isEditing = (bool) $attribute;

        auth('adminPanel')->user()?->can($this->isEditing ? 'attributes.update' : 'attributes.create') ?: abort(403);

        $this->attribute = $attribute;

        if ($this->isEditing) {
            $this->name = (string) $attribute->name;
            $this->attributeGroupId = $attribute->attribute_group_id;

            if ($attribute->value) {
                $this->value = (string) $attribute->value;
            }
        }
    }

    /* ---------------- لیست گروه‌ها (legacy: AttributeGroup::all با مرتب‌سازی cosmetic) ---------------- */

    #[Computed]
    public function groups()
    {
        return AttributeGroup::query()
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /** نوع گروه انتخاب‌شده — نمایش فیلد رنگ مثل قدیمی (attributes/all.js) */
    public function selectedGroupType(): ?string
    {
        if (! $this->attributeGroupId) {
            return null;
        }

        return AttributeGroup::find($this->attributeGroupId)?->type;
    }

    /* ---------------- ذخیره (پورت update قدیمی + نرمال‌سازی value مثل store) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can($this->isEditing ? 'attributes.update' : 'attributes.create') ?: abort(403);

        $this->validate([
            'name'              => 'required',
            'attributeGroupId'  => 'required|exists:attribute_groups,id',
        ], [], [
            'name'             => 'نام',
            'attributeGroupId' => 'گروه ویژگی',
        ]);

        // قدیمی update() مقدار hex گروه‌های غیررنگی را دست‌نخورده نگه می‌داشت (ناسازگار با store)؛
        // مطابق store قدیمی: مقدار فقط برای گروه‌های رنگی ذخیره می‌شود
        $value = $this->selectedGroupType() === 'color' ? $this->value : null;

        if ($this->isEditing) {
            $this->attribute->update([
                'name'               => $this->name,
                'value'              => $value,
                'attribute_group_id' => $this->attributeGroupId,
            ]);

            session()->flash('np-toast', ['type' => 'success', 'message' => 'ویژگی با موفقیت ویرایش شد.']);
        } else {
            Attribute::create([
                'name'               => $this->name,
                'value'              => $value,
                'attribute_group_id' => $this->attributeGroupId,
            ]);

            session()->flash('np-toast', ['type' => 'success', 'message' => 'ویژگی با موفقیت ایجاد شد.']);
        }

        // مثل قدیمی که پس از ذخیره به لیست گروه‌ها برمی‌گشت
        $this->redirectIntended(route('np.attribute-groups.index', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.attribute-form');
    }
}

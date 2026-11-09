<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Support\Facades\DB;
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

    /* ---------------- انتخاب گروهی (فاز ۱۴۹) ---------------- */

    /** شناسه‌های انتخاب‌شده در صفحه فعلی */
    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    public function mount(AttributeGroup $attributeGroup): void
    {
        auth('adminPanel')->user()?->can('attributes.groups.index') ?: abort(403);

        $this->attributeGroup = $attributeGroup;
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    /** همه‌ی ویژگی‌های صفحه فعلی را انتخاب/لغو می‌کند (هم‌الگو با ProductIndex) */
    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->attributesList->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
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

    /* ---------------- حذف گروهی (فاز ۱۴۹) ---------------- */

    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('attributes.delete') ?: abort(403);

        $ids = array_values(array_unique(array_map('intval', $this->selected)));

        if ($ids === []) {
            $this->confirmingBulkDelete = false;
            return;
        }

        // نام ویژگی‌ها قبل از حذف برای گزارش کامل فعالیت (یک کوئری سبک)
        $names = DB::table('attributes')->whereIn('id', $ids)->pluck('name', 'id')->all();

        // پاک‌سازی پیوت attribute_price با یک کوئری — همان معنای detach() در حذف تکی
        DB::table('attribute_price')->whereIn('attribute_id', $ids)->delete();

        // حذف با کوئری‌بیلدر (بدون رویداد مدل) تا ناظر سراسری برای هر ردیف لاگ ننویسد — قرارداد فازهای ۱۴۵/۱۴۶:
        // هر عملیات گروهی فقط یک رکورد فعالیت ثبت می‌کند.
        $count = DB::table('attributes')->whereIn('id', $ids)->delete();

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('deleted')
            ->withProperties([
                'action' => 'bulk_delete_attribute',
                'attribute_group_id' => $this->attributeGroup->id,
                'attribute_ids' => $ids,
                'attribute_names' => $names,
                'count' => $count,
                'ip' => request()->ip(),
            ])
            ->log('حذف گروهی ' . $count . ' ویژگی از گروه «' . $this->attributeGroup->name . '»');

        $this->dispatch('toast', message: 'تعداد ' . $count . ' ویژگی با موفقیت حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.attribute-group-attributes')
            ->layout('admin-panel::layout');
    }
}

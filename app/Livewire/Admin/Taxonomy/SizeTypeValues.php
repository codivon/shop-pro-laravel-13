<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\SizeType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

/**
 * ویرایش مقادیر راهنمای سایز — پورت SizeTypeController@editValues/updateValues
 * ماتریس: ردیف = گروه (سایز X)، ستون = سایز (قد، دور سینه…)
 * لاگ فعالیت spatie عیناً پورت شده است.
 * {sizetype} با کلید id بایند می‌شود. دسترسی: products.sizetypes
 */
#[Title('مقادیر راهنمای سایز')]
class SizeTypeValues extends Component
{
    public SizeType $sizetype;

    /**
     * گروه‌های مقدار؛ هر گروه = آرایه [size_id => value]
     *
     * @var array<int, array<string, string>>
     */
    public array $valueGroups = [];

    public function mount(SizeType $sizetype): void
    {
        auth('adminPanel')->user()?->can('products.sizetypes') ?: abort(403);

        $this->sizetype = $sizetype;

        // پیش‌پرکردن ماتریس از پیوت (دسته‌بندی بر اساس گروه)
        $grouped = $sizetype->values()->get()->groupBy(fn ($row) => (int) $row->pivot->group);

        foreach ($grouped as $rows) {
            $bySize = $rows->keyBy(fn ($row) => (int) $row->pivot->size_id);

            $values = [];

            foreach ($this->sizes as $size) {
                $pivot = $bySize->get((int) $size->id);

                $values[$size->id] = $pivot ? (string) $pivot->pivot->value : '';
            }

            $this->valueGroups[] = $values;
        }

        if ($this->valueGroups === []) {
            $this->valueGroups[] = $this->emptyGroup();
        }
    }

    /* ---------------- مراجع ---------------- */

    #[Computed]
    public function sizes()
    {
        return $this->sizetype->sizes; // مرتب بر اساس ordering
    }

    protected function emptyGroup(): array
    {
        $values = [];

        foreach ($this->sizes as $size) {
            $values[$size->id] = '';
        }

        return $values;
    }

    /* ---------------- مدیریت گروه‌ها ---------------- */

    public function addGroup(): void
    {
        $this->valueGroups[] = $this->emptyGroup();
    }

    public function removeGroup(int $index): void
    {
        unset($this->valueGroups[$index]);
        $this->valueGroups = array_values($this->valueGroups);
    }

    /** جابه‌جایی ردیف گروه (جایگزین درگ‌اند‌دراپ قدیمی) */
    public function moveGroupUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->valueGroups[$index])) {
            return;
        }

        [$this->valueGroups[$index - 1], $this->valueGroups[$index]] = [$this->valueGroups[$index], $this->valueGroups[$index - 1]];
        $this->valueGroups = array_values($this->valueGroups);
    }

    public function moveGroupDown(int $index): void
    {
        if (! isset($this->valueGroups[$index], $this->valueGroups[$index + 1])) {
            return;
        }

        [$this->valueGroups[$index + 1], $this->valueGroups[$index]] = [$this->valueGroups[$index], $this->valueGroups[$index + 1]];
        $this->valueGroups = array_values($this->valueGroups);
    }

    /* ---------------- ذخیره (پورت ۱:۱ updateValues با لاگ فعالیت) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('products.sizetypes') ?: abort(403);

        $sizetype = $this->sizetype;
        $admin = auth('adminPanel')->user();
        $adminName = $admin?->full_name ?? $admin?->fullname ?? 'مدیر';
        $sizeTypeName = $sizetype->title ?? $sizetype->name ?? "#{$sizetype->id}";

        // ذخیره مقادیر قدیمی قبل از آپدیت (مثل قدیمی)
        $oldValues = [];

        foreach ($sizetype->values()->get() as $relation) {
            // رفع باگ قدیمی: به‌جای $relation->size_id (که روی مدل Size وجود ندارد)، از pivot خوانده می‌شود
            $sizeId = $relation->pivot->size_id;
            $key = "گروه {$relation->pivot->group} - سایز {$sizeId}";
            $oldValues[$key] = $relation->pivot->value;
        }

        $sizetype->values()->detach();
        $ordering = 1;
        $groupordering = 1;

        $newValues = [];

        foreach ($this->valueGroups as $groupValues) {
            foreach ($groupValues as $size_id => $value) {
                $size_id = (int) $size_id;

                if (! $size_id) {
                    continue;
                }

                $sizetype->values()->attach(
                    [
                        $size_id => [
                            'group'    => $groupordering,
                            'value'    => $value,
                            'ordering' => $ordering++,
                        ],
                    ]
                );

                // ذخیره مقادیر جدید برای لاگ
                $key = "گروه {$groupordering} - سایز {$size_id}";
                $newValues[$key] = $value;
            }
            $groupordering++;
        }

        // ساخت تغییرات با فرمت old و attributes (مثل قدیمی)
        $oldData = [];
        $newData = [];

        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $oldVal = $oldValues[$key] ?? 'ندارد';
            $newVal = $newValues[$key] ?? 'حذف شده';

            if ($oldVal != $newVal) {
                $oldData[$key] = $oldVal;
                $newData[$key] = $newVal;
            }
        }

        // ثبت لاگ فعالیت (پورت عینی)
        if (! empty($oldData)) {
            activity()
                ->performedOn($sizetype)
                ->causedBy($admin)
                ->event('updated')
                ->withProperties([
                    'action'         => 'update_size_type_values',
                    'size_type_name' => $sizeTypeName,
                    'size_type_id'   => $sizetype->id,
                    'old'            => $oldData,
                    'attributes'     => $newData,
                    'ip'             => request()->ip(),
                ])
                ->log("مدیر {$adminName} مقادیر راهنمای سایز «{$sizeTypeName}» را ویرایش کرد");
        } else {
            activity()
                ->performedOn($sizetype)
                ->causedBy($admin)
                ->event('updated')
                ->withProperties([
                    'action'         => 'update_size_type_values_no_change',
                    'size_type_name' => $sizeTypeName,
                    'size_type_id'   => $sizetype->id,
                    'ip'             => request()->ip(),
                ])
                ->log("مدیر {$adminName} مقادیر راهنمای سایز «{$sizeTypeName}» را ویرایش کرد اما تغییری اعمال نشد");
        }

        session()->flash('np-toast', ['type' => 'success', 'message' => 'مقادیر راهنمای سایز با موفقیت ویرایش شد.']);

        $this->redirect(route('np.sizetypes.index'), navigate: true);
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
        return view('admin-panel::livewire.admin.taxonomy.sizetype-values')
            ->layout('admin-panel::layout');
    }
}

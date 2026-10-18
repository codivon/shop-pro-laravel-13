<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\AttributeGroup;
use App\Models\Filter;
use App\Models\Filterable;
use App\Models\Specification;
use App\Models\StaticFilter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * ایجاد/ویرایش فیلتر — پورت FilterController@create/store/edit/update + add_filterable
 * {filter} اختیاری (ساخت) یا الزامی (ویرایش) با کلید id بایند می‌شود.
 * دسترسی: filters.create / filters.update
 */
#[Title('فرم فیلتر')]
class FilterForm extends Component
{
    public ?Filter $filter = null;

    public bool $isEditing = false;

    public ?int $filterId = null;

    public string $title = '';

    public bool $isSeller = false;

    /**
     * ردیف‌های فیلترپذیر؛ هر ردیف: {type, id, active, separator, label}
     *
     * @var array<int, array{type: string, id: ?int, active: bool, separator: string, label: string}>
     */
    public array $rows = [];

    public function mount(?Filter $filter = null): void
    {
        if ($filter && $filter->exists) {
            auth('adminPanel')->user()?->can('filters.update') ?: abort(403);

            $this->filter = $filter;
            $this->isEditing = true;
            $this->filterId = $filter->id;
            $this->title = (string) $filter->title;
            $this->isSeller = (bool) $filter->is_seller;

            $this->loadRows($filter);
        } else {
            auth('adminPanel')->user()?->can('filters.create') ?: abort(403);
        }
    }

    protected function authorizeAccess(): void
    {
        auth('adminPanel')->user()?->can($this->isEditing ? 'filters.update' : 'filters.create') ?: abort(403);
    }

    /* ---------------- مراجع (پورت create/edit) ---------------- */

    #[Computed]
    public function staticFilters()
    {
        return StaticFilter::orderBy('ordering')->get(['id', 'title']);
    }

    #[Computed]
    public function specifications()
    {
        return Specification::get(['id', 'name']);
    }

    #[Computed]
    public function attributeGroups()
    {
        return AttributeGroup::orderBy('ordering')->get(['id', 'name']);
    }

    /** آیا فیلتر فروشنده قبلاً تعریف شده؟ (در ایجاد، چک‌باکس مخفی می‌شود — مثل قدیمی) */
    #[Computed]
    public function sellerFilterExists(): bool
    {
        return Filter::isSeller()->exists();
    }

    public const ROW_TYPES = [
        'static_filter'  => 'فیلتر ثابت',
        'attributeGroup' => 'گروه ویژگی',
        'specification'  => 'مشخصات',
    ];

    /* ---------------- پیش‌پرکردن ردیف‌ها (ویرایش) ---------------- */

    protected function loadRows(Filter $filter): void
    {
        $this->rows = $filter->related()
            ->with('filterable')
            ->orderBy('ordering')
            ->get()
            ->map(function (Filterable $item) {
                $type = match ($item->filterable_type) {
                    StaticFilter::class    => 'static_filter',
                    AttributeGroup::class  => 'attributeGroup',
                    Specification::class   => 'specification',
                    default                => 'static_filter',
                };

                $related = $item->filterable;
                $label = $related
                    ? (string) ($related->title ?? $related->name ?? '')
                    : 'مورد حذف‌شده';

                return [
                    'type'      => $type,
                    'id'        => (int) $item->filterable_id,
                    'active'    => (bool) $item->active,
                    'separator' => (string) ($item->separator ?? ''),
                    'label'     => $label,
                ];
            })
            ->values()
            ->all();
    }

    /* ---------------- ریپیتر ---------------- */

    public function addRow(): void
    {
        $this->rows[] = ['type' => 'static_filter', 'id' => null, 'active' => true, 'separator' => '', 'label' => ''];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function moveRowUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->rows[$index])) {
            return;
        }

        [$this->rows[$index - 1], $this->rows[$index]] = [$this->rows[$index], $this->rows[$index - 1]];
        $this->rows = array_values($this->rows);
    }

    public function moveRowDown(int $index): void
    {
        if (! isset($this->rows[$index], $this->rows[$index + 1])) {
            return;
        }

        [$this->rows[$index + 1], $this->rows[$index]] = [$this->rows[$index], $this->rows[$index + 1]];
        $this->rows = array_values($this->rows);
    }

    /** با تغییر نوع هر ردیف، انتخاب قبلی پاک می‌شود */
    public function updatedRows($name, $value): void
    {
        if (preg_match('/^rows\.(\d+)\.type$/', (string) $name, $m)) {
            $i = (int) $m[1];

            if (isset($this->rows[$i])) {
                $this->rows[$i]['id'] = null;
                $this->rows[$i]['label'] = '';
            }
        }
    }

    /* ---------------- اعتبارسنجی ---------------- */

    protected function rules(): array
    {
        return [
            'title'            => ['required', 'unique:filters,title' . ($this->isEditing ? ',' . $this->filterId : '')],
            'rows'             => ['required', 'array', 'min:1'],
            'rows.*.type'      => ['required', 'in:static_filter,attributeGroup,specification'],
            'rows.*.id'        => ['required', 'integer'],
            'rows.*.active'    => ['boolean'],
            'rows.*.separator' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required'      => 'عنوان فیلتر الزامی است.',
            'title.unique'        => 'این عنوان قبلاً ثبت شده است.',
            'rows.required'       => 'حداقل یک مورد فیلترپذیر اضافه کنید.',
            'rows.min'            => 'حداقل یک مورد فیلترپذیر اضافه کنید.',
            'rows.*.type.required' => 'نوع هر ردیف الزامی است.',
            'rows.*.type.in'      => 'نوع انتخابی نامعتبر است.',
            'rows.*.id.required'  => 'مورد فیلترپذیر هر ردیف را انتخاب کنید.',
        ];
    }

    /* ---------------- ذخیره (پورت store/update) ---------------- */

    public function save(): void
    {
        $this->authorizeAccess();

        try {
            $data = $this->validate();
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        // قید تک‌فیلترِ فروشنده (پورت عینی store/update)
        if ($this->isEditing) {
            if (! $this->filter->is_seller && $this->isSeller && Filter::isSeller()->count() >= 1) {
                $this->dispatch('toast', message: 'بیش از یک فیلتر فروشنده تعریف شده است', type: 'error');

                throw ValidationException::withMessages([
                    'filter' => 'بیش از یک فیلتر فروشنده تعریف شده است',
                ]);
            }
        } elseif ($this->isSeller && Filter::isSeller()->count() >= 1) {
            $this->dispatch('toast', message: 'بیش از یک فیلتر فروشنده تعریف شده است', type: 'error');

            throw ValidationException::withMessages([
                'filter' => 'بیش از یک فیلتر فروشنده تعریف شده است',
            ]);
        }

        if ($this->isEditing) {
            $this->filter->update([
                'title'     => $data['title'],
                'is_seller' => $this->isSeller ? true : false,
            ]);
        } else {
            $this->filter = Filter::create([
                'title'     => $data['title'],
                'is_seller' => $this->isSeller ? true : false,
            ]);
        }

        // پورت حلقه‌ی store/update + حذف موارد حذف‌شده
        $ordering = 1;
        $filterables = [];

        foreach ($this->rows as $row) {
            $id = $this->addFilterable($this->filter, $row, $ordering++);

            if ($id) {
                $filterables[] = $id;
            }
        }

        $this->filter->related()->whereNotIn('id', $filterables)->delete();

        session()->flash('np-toast', ['type' => 'success', 'message' => $this->isEditing ? 'فیلتر با موفقیت ویرایش شد.' : 'فیلتر با موفقیت ایجاد شد.']);

        $this->redirect(route('np.filters.index'), navigate: true);
    }

    /**
     * پورت ۱:۱ add_filterable قدیمی — Filterable::updateOrCreate با کلید مورف
     * رفع باگ قدیمی: اگر مورد یافت نشود به‌جای خطای undefined مقدار null برمی‌گردد.
     */
    protected function addFilterable(Filter $filter, array $item, int $ordering): ?int
    {
        $filterableClass = match ($item['type']) {
            'static_filter'  => StaticFilter::class,
            'attributeGroup' => AttributeGroup::class,
            'specification'  => Specification::class,
            default          => null,
        };

        if (! $filterableClass || ! $filterable = $filterableClass::find($item['id'])) {
            return null;
        }

        $payload = [
            'ordering' => $ordering,
            'active'   => ! empty($item['active']),
        ];

        if ($item['type'] === 'specification') {
            $payload['separator'] = array_key_exists('separator', $item) ? $item['separator'] : null;
        }

        $inserted = Filterable::updateOrCreate(
            [
                'filter_id'       => $filter->id,
                'filterable_id'   => $filterable->id,
                'filterable_type' => $filterableClass,
            ],
            $payload
        );

        return $inserted->id;
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.filter-form')
            ->layout('admin-panel::layout');
    }
}

<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Fild;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * لیست فیلدهای اختصاصی — پورت FildController@index/create/store/multipleDestroy/destroy
 * دسترسی: filds / filds.create / filds.delete
 *
 * انحراف از قدیمی: اعتبارسنجی belongs_to بر اساس حقیقت دیتابیس (enum: users,products,blogs)
 * انجام شده است — قدیمی «posts» را قبول می‌کرد که در ستون enum وجود ندارد (باگ قدیمی).
 */
#[Title('فیلدهای اختصاصی')]
class FildIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- فرم ایجاد سریع ---------------- */

    public bool $showCreate = false;

    public string $title = '';

    public string $belongsTo = 'users';

    public string $type = 'input';

    public string $value = '';

    public string $selectOptions = '';

    public bool $published = true;

    public bool $required = false;

    public bool $userShow = false;

    /* ---------------- انتخاب گروهی و حذف ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    /** برچسب فارسی انواع فیلد (مثل قدیمی) */
    public const TYPES = [
        'input'       => '(input) ورودی کلمات',
        'textarea'    => '(textarea) ورودی متن',
        'number'      => '(number) ورودی عدد',
        'email'       => '(email) ورودی ایمیل',
        'colorPicker' => '(colorPicker) انتخاب رنگ',
        'checkbox'    => '(checkbox) چک باکس',
        'select'      => '(select) انتخابی',
    ];

    public const BELONGS = [
        'users'    => 'کاربران',
        'products' => 'محصولات',
        'blogs'    => 'مقالات',
    ];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('filds') ?: abort(403);
    }

    public function updatingShowCreate(): void
    {
        $this->resetPage();
    }

    /* ---------------- لیست ---------------- */

    #[Computed]
    public function items()
    {
        // پورت: Fild::orderBy('created_at', 'desc')->paginate(15)
        return Fild::query()->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    /* ---------------- ایجاد (پورت store) ---------------- */

    public function store(): void
    {
        auth('adminPanel')->user()?->can('filds.create') ?: abort(403);

        $this->validate([
            'title'      => ['required', 'max:255'],
            'belongsTo'  => ['required', 'in:users,products,blogs'],
            'type'       => ['required', 'in:input,textarea,number,email,colorPicker,checkbox,select'],
            'value'      => ['nullable', 'string'],
            'selectOptions' => ['nullable', 'string'],
        ], [
            'title.required'     => 'عنوان فیلد الزامی است.',
            'belongsTo.required' => 'انتخاب بخش متعلق الزامی است.',
            'type.required'      => 'انتخاب نوع فیلد الزامی است.',
        ]);

        Fild::create([
            'title'          => $this->title,
            'belongs_to'     => $this->belongsTo,
            'type'           => $this->type,
            'value'          => $this->value ?: null,
            'select_options' => $this->selectOptions ?: null,
            'published'      => $this->published,
            'required'       => $this->required,
            'user_show'      => $this->userShow,
        ]);

        $this->reset('title', 'value', 'selectOptions');
        $this->belongsTo = 'users';
        $this->type = 'input';
        $this->published = true;
        $this->required = false;
        $this->userShow = false;

        $this->dispatch('toast', message: 'فیلد با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->items->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف (پورت destroy/multipleDestroy) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('filds.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('filds.delete') ?: abort(403);

        if ($fild = Fild::find($this->confirmingDelete)) {
            $this->destroyFild($fild);
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'فیلد حذف شد.', type: 'success');
    }

    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('filds.delete') ?: abort(403);

        foreach (Fild::whereIn('id', $this->selected)->get() as $fild) {
            $this->destroyFild($fild);
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'فیلدهای انتخاب‌شده حذف شدند.', type: 'success');
    }

    /**
     * پورت destroy قدیمی + پاک‌سازی مقادیر فیلدها
     * (معادل cascade دیتابیس در prod؛ برای SQLite/بدون FK هم درست کار می‌کند)
     */
    protected function destroyFild(Fild $fild): void
    {
        $fild->fieldValues()->delete();
        $fild->delete();
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.filds')
            ->layout('admin-panel::layout');
    }
}

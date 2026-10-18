<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Fild;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * ویرایش فیلد اختصاصی — پورت FildController@edit/update
 * {fild} با کلید id بایند می‌شود. دسترسی: filds.update
 */
#[Title('ویرایش فیلد')]
class FildForm extends Component
{
    public Fild $fild;

    public string $title = '';

    public string $belongsTo = 'users';

    public string $type = 'input';

    public string $value = '';

    public string $selectOptions = '';

    public bool $published = false;

    public bool $required = false;

    public bool $userShow = false;

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

    public function mount(Fild $fild): void
    {
        auth('adminPanel')->user()?->can('filds.update') ?: abort(403);

        $this->fild = $fild;
        $this->title = (string) $fild->title;
        $this->belongsTo = (string) $fild->belongs_to;
        $this->type = (string) $fild->type;
        $this->value = (string) $fild->value;
        $this->selectOptions = (string) $fild->select_options;
        $this->published = (bool) $fild->published;
        $this->required = (bool) $fild->required;
        $this->userShow = (bool) $fild->user_show;
    }

    /* ---------------- ذخیره (پورت update) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('filds.update') ?: abort(403);

        $this->validate([
            'title'         => ['required', 'max:255'],
            'belongsTo'     => ['required', 'in:users,products,blogs'],
            'type'          => ['required', 'in:input,textarea,number,email,colorPicker,checkbox,select'],
            'value'         => ['nullable', 'string'],
            'selectOptions' => ['nullable', 'string'],
        ], [
            'title.required'     => 'عنوان فیلد الزامی است.',
            'belongsTo.required' => 'انتخاب بخش متعلق الزامی است.',
            'type.required'      => 'انتخاب نوع فیلد الزامی است.',
        ]);

        $this->fild->title = $this->title;
        $this->fild->belongs_to = $this->belongsTo;
        $this->fild->type = $this->type;
        $this->fild->value = $this->value ?: null;
        $this->fild->select_options = $this->selectOptions ?: null;
        $this->fild->published = $this->published;
        $this->fild->required = $this->required;
        $this->fild->user_show = $this->userShow;
        $this->fild->save();

        session()->flash('np-toast', ['type' => 'success', 'message' => 'فیلد با موفقیت ویرایش شد.']);

        $this->redirect(route('np.filds.index'), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.fild-form')
            ->layout('admin-panel::layout');
    }
}

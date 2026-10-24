<?php

namespace App\Livewire\Admin\Forms;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('فرم‌ساز')]
class FormBuilder extends Component
{
    /* ---------------- حالت فرم ---------------- */

    /** فرم در حال ویرایش (null = ایجاد فرم جدید) */
    public ?Form $form = null;

    public bool $isEditing = false;

    /* ---------------- متادیتای فرم (پورت store/update قدیمی) ---------------- */

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $meta_title = '';

    public string $meta_description = '';

    public string $success_message = '';

    public string $button_text = '';

    public string $email_notify = '';

    public bool $published = true;

    /**
     * پورت fields_data قدیمی — رشته‌ی JSON شامل «فیلدهای جدید»
     * که توسط Alpine (سازنده‌ی فیلد) پر می‌شود؛ ساختار هر آیتم:
     * {id, type, label, name, placeholder, required, help_text, default_value, class, validation, options[]}
     */
    public string $fieldsData = '[]';

    /** مودال حذف فیلد موجود (edit) */
    public ?int $confirmingDeleteField = null;

    /* ---------------- انواع فیلد (پورت renderFields + fb-type-cards قدیمی) ---------------- */

    public const TYPE_NAMES = [
        'text'      => 'متن ساده',
        'email'     => 'ایمیل',
        'tel'       => 'تلفن',
        'number'    => 'شماره',
        'textarea'  => 'متن چند خطی',
        'select'    => 'انتخابگر',
        'checkbox'  => 'چک‌باکس',
        'radio'     => 'رادیویی',
        'date'      => 'تاریخ',
        'file'      => 'فایل',
        'password'  => 'رمز عبور',
        'url'       => 'لینک',
    ];

    public const TYPE_ICONS = [
        'text'      => 'notebook-pen',
        'email'     => 'mail',
        'tel'       => 'smartphone',
        'number'    => 'hash',
        'textarea'  => 'menu',
        'select'    => 'list',
        'checkbox'  => 'check',
        'radio'     => 'circle-dot',
        'date'      => 'clock',
        'file'      => 'upload',
        'password'  => 'key-round',
        'url'       => 'link-2',
    ];

    public static function typeName(string $type): string
    {
        return self::TYPE_NAMES[$type] ?? $type;
    }

    public function mount(?Form $form = null): void
    {
        if ($form && $form->exists) {
            // پورت edit قدیمی — گیت authorizeResource → forms.update
            auth('adminPanel')->user()?->can('forms.update') ?: abort(403);

            $this->isEditing = true;
            $this->form = $form->load('fields');

            $this->title = (string) $form->title;
            // قدیمی مقدار input اسلاگ را با title پر می‌کرد (باگ)؛ اینجا اسلاگ واقعی پیش‌فرض می‌شود
            $this->slug = (string) $form->slug;
            $this->description = (string) $form->description;
            $this->meta_title = (string) $form->meta_title;
            $this->meta_description = (string) $form->meta_description;
            $this->success_message = (string) ($form->success_message ?: 'فرم با موفقیت ارسال شد');
            $this->button_text = (string) ($form->button_text ?: 'ارسال');
            $this->email_notify = (string) $form->email_notify;
            $this->published = (bool) $form->published;
        } else {
            // پورت create قدیمی — گیت authorizeResource → forms.create
            auth('adminPanel')->user()?->can('forms.create') ?: abort(403);

            $this->success_message = 'فرم با موفقیت ارسال شد';
            $this->button_text = 'ارسال';
            $this->published = true;
        }
    }

    /* ---------------- فیلدهای موجود (edit) ---------------- */

    #[Computed]
    public function existingFields()
    {
        return $this->form?->fields()->orderBy('order')->get() ?? collect();
    }

    /* ---------------- ذخیره (پورت ۱:۱ store/update قدیمی) ---------------- */

    public function save(): void
    {
        if ($this->isEditing) {
            auth('adminPanel')->user()?->can('forms.update') ?: abort(403);
        } else {
            auth('adminPanel')->user()?->can('forms.create') ?: abort(403);
        }

        // قدیمی: $request->offsetSet('slug', sluggable_helper_function($request->slug ?: $request->title))
        $this->slug = sluggable_helper_function($this->slug ?: $this->title);

        $rules = [
            'title'             => 'required|string|max:255',
            'slug'              => $this->isEditing
                ? 'required|string|unique:forms,slug,' . $this->form->id
                : 'nullable|string|unique:forms',
            'description'       => 'nullable|string',
            'meta_title'        => 'nullable|string',
            'meta_description'  => 'nullable|string',
            'success_message'   => 'nullable|string|max:500',
            'button_text'       => 'nullable|string|max:100',
            'email_notify'      => 'nullable|email',
            'published'         => 'boolean',
            'fieldsData'        => 'required|string|json',
        ];

        $validated = $this->validate($rules, [
            'title.required'      => 'عنوان فرم الزامی است',
            'slug.required'       => 'اسلاگ فرم الزامی است',
            'slug.unique'         => 'این اسلاگ قبلاً استفاده شده است',
            'fieldsData.required' => 'حداقل یک فیلد باید به فرم اضافه شود',
            'fieldsData.json'     => 'حداقل یک فیلد باید به فرم اضافه شود',
        ], [
            'title'       => 'عنوان فرم',
            'slug'        => 'اسلاگ فرم',
            'description' => 'توضیحات فرم',
            'meta_title'  => 'عنوان سئو',
        ]);

        // fieldsData فقط ورودی سازنده‌ی فیلد است — جزو ستون‌های فرم نیست
        // (قدیمی هم مثل حالت mass-assignment نادیده گرفته می‌شد؛ اینجا صریح حذف می‌شود)
        unset($validated['fieldsData']);

        $validated['published'] = $this->published;

        $fieldsData = json_decode($this->fieldsData, true) ?: [];

        if ($this->isEditing) {
            /* ================= پورت update قدیمی (رفتار دقیق) =================
             * فیلدهای موجود دست‌نخورده می‌مانند؛ فقط فیلدهای جدید به انتهای
             * ترتیب (maxOrder+index+1) اضافه می‌شوند و name تکراری → خطا.
             */
            $maxOrder = $this->form->fields()->max('order') ?? 0;

            foreach ($fieldsData as $index => $fieldData) {
                $existField = $this->form->fields()->where('name', $fieldData['name'])->first();

                if ($existField) {
                    // پیام عیناً مثل پاسخ 422 قدیمی
                    throw ValidationException::withMessages([
                        'fieldsData' => 'مقدار وارد شده با نام ' . $fieldData['name'] . ' از قبل موجود است',
                    ]);
                }

                $this->form->fields()->create([
                    'label'            => $fieldData['label'],
                    'name'             => $fieldData['name'],
                    'type'             => $fieldData['type'],
                    'required'         => $fieldData['required'] ?? false,
                    'placeholder'      => $fieldData['placeholder'] ?? null,
                    'help_text'        => $fieldData['help_text'] ?? null,
                    'default_value'    => $fieldData['default_value'] ?? null,
                    'class'            => $fieldData['class'] ?? null,
                    'rules_validation' => $fieldData['validation'] ?? null,
                    'options'          => $fieldData['options'] ?? null,
                    'order'            => $maxOrder + $index + 1,
                ]);
            }

            // قدیمی: اول فیلدها ساخته می‌شدند و بعد فرم آپدیت می‌شد
            $this->form->update($validated);
        } else {
            /* ================= پورت store قدیمی ================= */
            if (empty($fieldsData)) {
                throw ValidationException::withMessages([
                    'fieldsData' => 'حداقل یک فیلد باید به فرم اضافه شود',
                ]);
            }

            $form = Form::create($validated);

            foreach ($fieldsData as $index => $fieldData) {
                $form->fields()->create([
                    'label'            => $fieldData['label'],
                    'name'             => $fieldData['name'],
                    'type'             => $fieldData['type'],
                    'required'         => $fieldData['required'] ?? false,
                    'placeholder'      => $fieldData['placeholder'] ?? null,
                    'help_text'        => $fieldData['help_text'] ?? null,
                    'default_value'    => $fieldData['default_value'] ?? null,
                    'class'            => $fieldData['class'] ?? null,
                    'rules_validation' => $fieldData['validation'] ?? null,
                    'options'          => $fieldData['options'] ?? null,
                    'order'            => $index + 1,
                ]);
            }
        }

        session()->flash('np-toast', ['type' => 'success', 'message' => $this->isEditing ? 'فرم با موفقیت ویرایش شد.' : 'فرم با موفقیت ایجاد شد.']);

        $this->redirect(route('np.forms.index', absolute: false), navigate: true);
    }

    /* ---------------- حذف فیلد موجود (پورت deleteField قدیمی) ---------------- */

    public function confirmDeleteField(int $fieldId): void
    {
        auth('adminPanel')->user()?->can('forms.delete') ?: abort(403);

        $this->confirmingDeleteField = $fieldId;
    }

    public function deleteField(): void
    {
        auth('adminPanel')->user()?->can('forms.delete') ?: abort(403);

        if ($this->form && $this->confirmingDeleteField) {
            // پورت FormController::deleteField — حذف فیلد متعلق به همین فرم
            FormField::query()
                ->where('id', $this->confirmingDeleteField)
                ->where('form_id', $this->form->id)
                ->first()
                ?->delete();

            unset($this->existingFields);
        }

        $this->confirmingDeleteField = null;

        $this->dispatch('toast', message: 'فیلد با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ترتیب فیلدهای موجود (پورت reorderFields قدیمی) ---------------- */

    /**
     * پورت FormController::reorderFields — آرایه‌ی id فیلدها به ترتیب جدید،
     * order = index + 1 (فقط فیلدهای همین فرم).
     */
    public function reorderFields(array $order): void
    {
        auth('adminPanel')->user()?->can('forms.update') ?: abort(403);

        \Illuminate\Support\Facades\Validator::make(['order' => $order], [
            'order'   => 'required|array',
            'order.*' => 'required|integer|exists:form_fields,id',
        ], [], [
            'order' => 'ترتیب فیلدها',
        ])->validate();

        foreach ($order as $index => $fieldId) {
            FormField::query()
                ->where('id', $fieldId)
                ->where('form_id', $this->form->id)
                ->update(['order' => $index + 1]);
        }

        unset($this->existingFields);

        $this->dispatch('toast', message: 'ترتیب فیلدها با موفقیت ذخیره شد', type: 'success');
    }

    /**
     * جابجایی ↑↓ فیلد موجود (جایگزین drag&drop قدیمی مثل الگوی فاز ۵)
     * و فراخوانی همان پورت reorderFields.
     */
    public function moveExistingField(int $fieldId, string $direction): void
    {
        auth('adminPanel')->user()?->can('forms.update') ?: abort(403);

        $ordered = $this->form->fields()->orderBy('order')->pluck('id')->values();
        $current = $ordered->search($fieldId);

        if ($current === false) {
            return;
        }

        $target = $direction === 'up' ? $current - 1 : $current + 1;

        if ($target < 0 || $target >= $ordered->count()) {
            return;
        }

        [$ordered[$current], $ordered[$target]] = [$ordered[$target], $ordered[$current]];

        $this->reorderFields($ordered->all());
    }

    /* ---------------- ابزار ---------------- */

    public function render()
    {
        return view('admin-panel::livewire.admin.forms.form-builder')
            ->layout('admin-panel::layout');
    }
}

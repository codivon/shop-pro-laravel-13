<?php

namespace App\Livewire\Admin\Users;

use App\Models\FieldValue;
use App\Models\Fild;
use App\Models\Referral;
use App\Models\User;
use App\Rules\NotSpecialChar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

#[Title('فرم کاربر')]
class UserForm extends Component
{
    use WithFileUploads;

    public ?User $user = null;

    public bool $isEditing = false;

    /* ---------------- فیلدهای فرم (پورت create/edit قدیمی) ---------------- */

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    /** شماره همراه — در این سیستم نام کاربری همان موبایل است (legacy: mobile = username) */
    public string $username = '';

    public string $national_code = '';

    public string $card_number = '';

    /** تاریخ تولد — سه سلکت جدا مثل قدیمی (مقدار خالی = انتخاب نشده) */
    public string $birthDay = '';

    public string $birthMonth = '';

    public string $birthYear = '';

    public $image = null;

    public string $password = '';

    public string $password_confirmation = '';

    /** تایید شده بودن شماره همراه */
    public bool $verified = false;

    /** نقش‌ها — فرم قدیمی هیچ UIیی برای نقش‌ها نداشت (تایید شده)؛ مسیر attach/sync پورت شده است */
    public array $roles = [];

    /** فیلدهای اختصاصی — کلیدها شناسه‌ی جدول filds هستند */
    public array $filds = [];

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            auth('adminPanel')->user()?->can('users.update') ?: abort(403);

            $this->isEditing = true;
            $this->user = $user;

            $this->first_name = (string) ($user->first_name ?? '');
            $this->last_name = (string) ($user->last_name ?? '');
            $this->email = (string) ($user->email ?? '');
            $this->username = (string) ($user->username ?? '');
            $this->national_code = (string) ($user->national_code ?? '');
            $this->card_number = (string) ($user->card_number ?? '');
            $this->verified = (bool) $user->verified_at;

            // تاریخ تولد به صورت رشته‌ی جلالی Y/m/d ذخیره می‌شود
            $birth = $user->birth_date ? explode('/', $user->birth_date) : [];
            $this->birthYear = isset($birth[0]) ? (string) (int) $birth[0] : '';
            $this->birthMonth = isset($birth[1]) ? (string) (int) $birth[1] : '';
            $this->birthDay = isset($birth[2]) ? (string) (int) $birth[2] : '';

            // مقادیر ذخیره‌شده‌ی فیلدهای اختصاصی (پورت fieldValues قدیمی)
            foreach ($this->userFieldValues() as $fieldId => $value) {
                $this->filds[$fieldId] = (string) $value;
            }
        } else {
            auth('adminPanel')->user()?->can('users.create') ?: abort(403);
        }

        // مقدار پیش‌فرض فیلدهای اختصاصی از ستون value خود فیلد (مثل فرم create قدیمی)
        foreach ($this->fildsList as $fild) {
            if (! array_key_exists($fild->id, $this->filds)) {
                $this->filds[$fild->id] = $fild->value !== null ? (string) $fild->value : '';
            }

            // سلکت اجباری مثل HTML همیشه مقدار دارد (اولین گزینه) — پیش‌فرض‌گذاری می‌کنیم
            if ($fild->type === 'select' && $fild->required && ($this->filds[$fild->id] ?? '') === '') {
                $firstOption = trim((string) (explode(',', (string) $fild->select_options)[0] ?? ''));
                $this->filds[$fild->id] = $firstOption;
            }
        }
    }

    /* ---------------- فیلدهای اختصاصی ---------------- */

    #[Computed]
    public function fildsList()
    {
        // پورت: Fild::where('belongs_to', 'users')->orderBy('created_at', 'desc')->get()
        return Fild::where('belongs_to', 'users')->orderBy('created_at', 'desc')->get();
    }

    protected function userFieldValues(): array
    {
        if (! $this->isEditing) {
            return [];
        }

        return FieldValue::where('belongs_to', 'users')
            ->where('related_id', $this->user->id)
            ->pluck('value', 'field_id')
            ->toArray();
    }

    /* ---------------- گزینه‌های تاریخ تولد (پورت فرم قدیمی) ---------------- */

    #[Computed]
    public function monthOptions(): array
    {
        return [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
            5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
            9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
        ];
    }

    #[Computed]
    public function yearOptions(): array
    {
        // قدیمی تا ۱۴۰۰ هاردکد بود — به صورت پویا تا سال جلالی فعلی تولید می‌شود
        $currentJalaliYear = (int) Jalalian::now()->format('Y');

        return range(1360, max(1360, $currentJalaliYear));
    }

    /** رشته‌ی تاریخ تولد مثل قدیمی Y/m/d یا null اگر یکی از سلکت‌ها خالی باشد */
    protected function birthDateString(): ?string
    {
        if ($this->birthDay === '' || $this->birthMonth === '' || $this->birthYear === '') {
            return null;
        }

        return $this->birthYear . '/' . $this->birthMonth . '/' . $this->birthDay;
    }

    /* ---------------- قواعد اعتبارسنجی (پورت store/update) ---------------- */

    /** Rule یکتا با ignore شناسه در حالت ویرایش */
    protected function uniqueRule(string $column)
    {
        $rule = Rule::unique('users', $column);

        return $this->isEditing ? $rule->ignore($this->user->id) : $rule;
    }

    protected function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255', new NotSpecialChar()],
            'last_name' => ['required', 'string', 'max:255', new NotSpecialChar()],
            'username' => [
                'required', 'string',
                $this->uniqueRule('username'),
                // mobile همان username است؛ یکتایی موبایل را هم تضمین می‌کنیم (رفع خطای UNIQUE دیتابیس)
                $this->uniqueRule('mobile'),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255', $this->uniqueRule('email')],
            'national_code' => ['nullable', 'string', 'max:10', $this->uniqueRule('national_code')],
            'password' => $this->isEditing
                ? ['nullable', 'string', 'min:6', 'confirmed']
                : ['required', 'string', 'confirmed'],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'image' => 'nullable|image|max:2048',
            'filds' => 'nullable|array',
        ];

        // اعتبارسنجی فیلدهای اجباری اختصاصی — پیام مثل قدیمی: «فیلد X اجباری است.»
        foreach (Fild::where('belongs_to', 'users')->where('required', 1)->get(['id', 'title']) as $requiredFild) {
            $rules["filds.{$requiredFild->id}"] = 'required';
        }

        return $rules;
    }

    protected function messages(): array
    {
        $messages = [
            'first_name.required' => 'نام الزامی است.',
            'first_name.max' => 'نام نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'last_name.max' => 'نام خانوادگی نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'username.required' => 'شماره همراه (نام کاربری) الزامی است.',
            'username.unique' => 'این شماره همراه (نام کاربری) قبلاً ثبت شده است.',
            'email.email' => 'فرمت ایمیل معتبر نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'national_code.max' => 'کد ملی باید ۱۰ رقم باشد.',
            'national_code.unique' => 'این کد ملی قبلاً ثبت شده است.',
            'password.required' => 'گذرواژه الزامی است.',
            'password.min' => 'گذرواژه باید حداقل ۶ کاراکتر باشد.',
            'password.confirmed' => 'تکرار گذرواژه مطابقت ندارد.',
            'roles.*.exists' => 'نقش انتخاب شده معتبر نیست.',
            'image.image' => 'فایل آپلود شده باید تصویر باشد.',
            'image.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
        ];

        foreach (Fild::where('belongs_to', 'users')->where('required', 1)->get(['id', 'title']) as $requiredFild) {
            $messages["filds.{$requiredFild->id}.required"] = "فیلد {$requiredFild->title} اجباری است.";
        }

        return $messages;
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'email' => 'ایمیل',
            'username' => 'شماره همراه (نام کاربری)',
            'national_code' => 'کد ملی',
            'card_number' => 'شماره کارت',
            'password' => 'گذرواژه',
            'image' => 'تصویر',
        ];
    }

    /* ---------------- ذخیره (پورت store / update) ---------------- */

    public function save(): void
    {
        $this->isEditing
            ? (auth('adminPanel')->user()?->can('users.update') ?: abort(403))
            : (auth('adminPanel')->user()?->can('users.create') ?: abort(403));

        $this->validate();
        $birthDate = $this->birthDateString();

        // مقدارهای checkbox به رشته‌ی 0/1 نرمالایز می‌شوند تا در همه درایورهای دیتابیس ذخیره شود
        $filds = collect($this->filds)
            ->map(fn ($value) => $value === true ? '1' : ($value === false ? '0' : $value))
            ->all();

        if ($this->isEditing) {
            $user = $this->user;

            $user->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'username' => $this->username,
                'mobile' => $this->username, // legacy: username همان موبایل است
                'email' => $this->email ?: null,
                'national_code' => $this->national_code ?: null,
                'card_number' => $this->card_number ?: null,
                'level' => 'user', // مثل قدیمی سطح همیشه user می‌ماند
                'birth_date' => $birthDate,
                'verified_at' => $this->verified ? ($user->verified_at ?: now()) : null,
            ]);

            if ($this->password) {
                $user->update(['password' => Hash::make($this->password)]);

                // مثل قدیمی: با تغییر گذرواژه همه سشن‌های کاربر باطل می‌شود
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            if ($this->image) {
                $avatar = uploadOptimizedImage($this->image, 'users/avatars', $user->id);
                $user->update(['image' => $avatar]);
            }

            // قدیمی: roles()->sync($request->roles) — همیشه sync می‌شود
            $user->roles()->sync($this->roles ?: []);

            if (count($filds)) {
                saveFieldValues($filds, 'users', $user->id);
            }

            $this->dispatch('toast', message: 'کاربر با موفقیت ویرایش شد.', type: 'success');
        } else {
            $user = User::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'username' => $this->username,
                'mobile' => $this->username, // legacy: username همان موبایل است
                'email' => $this->email ?: null,
                'national_code' => $this->national_code ?: null,
                'card_number' => $this->card_number ?: null,
                'level' => 'user',
                'referral_code' => Referral::generateCode(),
                'birth_date' => $birthDate,
                'password' => Hash::make($this->password),
                'verified_at' => $this->verified ? now() : null,
            ]);

            if ($this->image) {
                $avatar = uploadOptimizedImage($this->image, 'users/avatars');
                $user->update(['image' => $avatar]);
            }

            if ($this->roles) {
                $user->roles()->attach($this->roles);
            }

            if (count($filds)) {
                saveFieldValues($filds, 'users', $user->id);
            }

            $this->dispatch('toast', message: 'کاربر با موفقیت ایجاد شد.', type: 'success');
        }

        $this->redirect(route('np.users.index'), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.users.form')
            ->layout('admin-panel::layout');
    }
}

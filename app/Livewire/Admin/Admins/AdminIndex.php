<?php

namespace App\Livewire\Admin\Admins;

use App\Models\Admin;
use App\Models\Role;
use App\Rules\NotSpecialChar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * مدیریت مدیران — پورت ۱:۱ Back\AdminController (index/apiIndex/create/edit/store/update/destroy/multipleDestroy)
 * به‌صورت لیست + مودال ایجاد/ویرایش + حذف تکی/گروهی + تغییر وضعیت.
 *
 * گاردها (پورت AdminPolicy + multipleDestroy):
 *  - update/delete فقط برای مدیرانی که level != creator است (سیاست قدیمی).
 *  - حذف تکی: خودِ مدیر جاری هم بلاک می‌شود (طبق اسپک فاز ۷؛ قدیمی فقط در حذف گروهی خودش را بلاک می‌کرد).
 *  - حذف گروهی: پورت دقیق قانون اعتبارسنجی ids.* (id != من && level != creator).
 *
 * تفاوت‌های مستندشده:
 *  - چک‌باکس «شماره تلفن تایید شده» (verified_at) در قدیمی هم no-op است (ستون verified_at در جدول admins وجود
 *    ندارد و در $fillable هم نیست) — برای پاریتی UI حفظ شد و مثل قدیمی هیچ چیزی ذخیره نمی‌کند.
 *  - به‌جای scopeFilter قدیمی که ورودی را مستقیم در WhereRaw می‌گذاشت (حفره تزریق)،
 *    از کوئری پارامتری با همان منطق concat(first_name,' ',last_name) استفاده شده است.
 *  - «آخرین ورود» از جدول admin_sessions (میدل‌ور RecordAdminSession) خوانده می‌شود.
 */
#[Title('مدیران')]
class AdminIndex extends Component
{
    use WithPagination;
    use WithFileUploads;

    public int $perPage = 15;

    /* ---------------- فیلترها (پورت scopeFilter قدیمی — State در URL) ---------------- */

    #[Url(as: 'fullname', except: '')]
    public string $fullname = '';

    #[Url(as: 'username', except: '')]
    public string $filterUsername = '';

    #[Url(as: 'email', except: '')]
    public string $filterEmail = '';

    /* ---------------- مودال ایجاد/ویرایش (پورت create/edit/store/update قدیمی) ---------------- */

    public bool $showFormModal = false;

    /** null = حالت ایجاد */
    public ?int $adminId = null;

    public string $firstName = '';

    public string $lastName = '';

    public ?string $email = null;

    public string $username = '';

    /** مثل فرم قدیمی: هنگام ایجاد اجباری، هنگام ویرایش اختیاری (min:8) */
    public string $password = '';

    public string $passwordConfirmation = '';

    /** چک‌باکس «شماره تلفن تایید شده» — در قدیمی هم ذخیره نمی‌شد (پاریتی) */
    public bool $verifiedAt = false;

    /** تصویر مدیر (Livewire TemporaryUploadedFile) */
    public $image = null;

    /** نقش‌های انتخاب‌شده */
    public array $roles = [];

    /* ---------------- انتخاب گروهی (پورت multipleDestroy) ---------------- */

    public bool $selectAll = false;

    public array $selected = [];

    /* ---------------- حذف تکی ---------------- */

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('admins.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['fullname', 'filterUsername', 'filterEmail', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست (پورت index/apiIndex قدیمی) ---------------- */

    #[Computed]
    public function admins()
    {
        // پورت scopeFilter قدیمی — با کوئری پارامتری (بجای WhereRaw خام ورودی کاربر)
        return Admin::query()
            ->when($this->fullname !== '', fn ($q) => $q->whereRaw(
                "concat(first_name, ' ', last_name) like ?",
                ['%' . $this->fullname . '%']
            ))
            ->when($this->filterUsername !== '', fn ($q) => $q->where('username', 'like', '%' . $this->filterUsername . '%'))
            ->when($this->filterEmail !== '', fn ($q) => $q->where('email', 'like', '%' . $this->filterEmail . '%'))
            ->with('roles')
            ->latest()
            ->paginate($this->perPage);
    }

    /** آخرین فعالیت هر ادمین از جدول admin_sessions (میدل‌ور RecordAdminSession) */
    #[Computed]
    public function lastLogins(): Collection
    {
        return DB::table('admin_sessions')
            ->selectRaw('admin_id, MAX(last_activity) as last_activity')
            ->groupBy('admin_id')
            ->pluck('last_activity', 'admin_id');
    }

    #[Computed]
    public function rolesList()
    {
        // پورت create/edit قدیمی: Role::latest()->get()
        return Role::query()->latest()->get();
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    /* ---------------- مودال ---------------- */

    public function openCreateModal(): void
    {
        auth('adminPanel')->user()?->can('admins.create') ?: abort(403);

        $this->resetForm();

        $this->showFormModal = true;
    }

    /** پورت edit قدیمی */
    public function openEditModal(int $id): void
    {
        // پورت AdminPolicy::update — ویرایش مدیر creator ممنوع
        auth('adminPanel')->user()?->can('admins.update') ?: abort(403);

        $admin = Admin::findOrFail($id);

        if ($admin->level === 'creator') {
            abort(403);
        }

        $this->adminId = $admin->id;
        $this->firstName = (string) $admin->first_name;
        $this->lastName = (string) $admin->last_name;
        $this->email = $admin->email;
        $this->username = (string) $admin->username;
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->verifiedAt = false;
        $this->image = null;
        $this->roles = $admin->roles()->pluck('roles.id')->map(fn ($id) => (string) $id)->all();
        $this->resetErrorBag();

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->adminId = null;
        $this->firstName = '';
        $this->lastName = '';
        $this->email = null;
        $this->username = '';
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->verifiedAt = false;
        $this->image = null;
        $this->roles = [];
        $this->resetErrorBag();
    }

    /* ---------------- ذخیره (پورت ۱:۱ store/update قدیمی) ---------------- */

    public function save(): void
    {
        $isEditing = $this->adminId !== null;

        auth('adminPanel')->user()?->can($isEditing ? 'admins.update' : 'admins.create') ?: abort(403);

        $admin = $isEditing ? Admin::findOrFail($this->adminId) : null;

        // پورت AdminPolicy::update — ویرایش مدیر creator ممنوع
        if ($admin && $admin->level === 'creator') {
            abort(403);
        }

        // مثل HTTP قدیمی (ConvertEmptyStringsToNull): رشته خالی → null
        $this->email = $this->email !== null && $this->email !== '' ? $this->email : null;

        $rules = [
            'firstName' => ['required', 'string', 'max:255', new NotSpecialChar()],
            'lastName'  => ['required', 'string', 'max:255', new NotSpecialChar()],
            'username'  => ['required', 'string', $admin
                ? Rule::unique('admins', 'username')->ignore($admin->id)
                : 'unique:admins,username'],
            'email'     => ['string', 'email', 'max:255', 'nullable', $admin
                ? Rule::unique('admins', 'email')->ignore($admin->id)
                : 'unique:admins,email'],
            'password'   => $admin
                ? ['nullable', 'string', 'min:8'] // تکرار با same:password چک می‌شود (معادل confirmed قدیمی)
                : ['required', 'string'],
            'roles'      => 'nullable|array',
            'roles.*'    => 'exists:roles,id',
            'image'      => 'nullable|image|max:2048',
        ];

        // قانون confirmed قدیمی روی password → معادل same برای passwordConfirmation
        $rules['passwordConfirmation'] = $admin
            ? ['nullable', 'string', 'same:password']
            : ['required', 'string', 'same:password'];

        $this->validate($rules, [], [
            'firstName'            => 'نام',
            'lastName'             => 'نام خانوادگی',
            'username'             => 'نام کاربری',
            'email'                => 'ایمیل',
            'password'             => 'گذرواژه',
            'passwordConfirmation' => 'تکرار گذرواژه',
            'roles'                => 'نقش‌ها',
            'image'                => 'تصویر',
        ]);

        $payload = [
            'first_name' => $this->firstName,
            'last_name'  => $this->lastName,
            'username'   => $this->username,
            'email'      => $this->email,
        ];

        if ($admin) {
            // پورت update قدیمی — level دست نمی‌خورد
            // پاریتی: verified_at مثل قدیمی ذخیره نمی‌شود (ستون/fillable ندارد)
            $admin->update($payload);

            if ($this->password) {
                $admin->update(['password' => Hash::make($this->password)]);

                DB::table('sessions')->where('user_id', $admin->id)->delete();
            }

            if ($this->image) {
                $admin->image = uploadOptimizedImage($this->image, 'admins', $admin->id);
                $admin->save();
            }

            $admin->roles()->sync($this->roles);

            $message = 'مدیر با موفقیت ویرایش شد.';
        } else {
            // پورت store قدیمی
            $newAdmin = Admin::create($payload + [
                'level'       => 'admin',
                'password'    => Hash::make($this->password),
                'verified_at' => $this->verifiedAt ? Carbon::now() : null,
            ]);

            if ($this->image) {
                $newAdmin->image = uploadOptimizedImage($this->image, 'admins');
                $newAdmin->save();
            }

            // قدیمی attach(null) را مستقیم به دیتابیس می‌داد؛ اینجا محافظت‌شده با همان معنا
            $newAdmin->roles()->attach($this->roles ?: []);

            $message = 'مدیر با موفقیت ایجاد شد.';
        }

        $this->showFormModal = false;
        $this->resetForm();

        unset($this->admins);

        $this->dispatch('toast', message: $message, type: 'success');
    }

    /* ---------------- تغییر وضعیت (افزودنی طبق اسپک فاز ۷) ---------------- */

    public function toggleStatus(int $id): void
    {
        auth('adminPanel')->user()?->can('admins.update') ?: abort(403);

        $admin = Admin::findOrFail($id);

        // پورت AdminPolicy::update
        if ($admin->level === 'creator') {
            abort(403);
        }

        $admin->status = $admin->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $admin->save();

        $this->dispatch('toast', message: 'وضعیت مدیر با موفقیت تغییر کرد.', type: 'success');
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->admins->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف (پورت destroy/multipleDestroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('admins.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('admins.delete') ?: abort(403);

        $admin = Admin::find($this->confirmingDelete);

        if ($admin) {
            // پورت AdminPolicy::delete + گارد خودِ مدیر (طبق اسپک فاز ۷)
            if ($admin->level === 'creator' || $admin->id === auth('adminPanel')->id()) {
                abort(403);
            }

            $this->destroyAdmin($admin);
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        unset($this->admins);

        // متن پیام عیناً از قدیمی («کاربر» — شگفتی قدیمی حفظ شد)
        $this->dispatch('toast', message: 'کاربر با موفقیت حذف شد.', type: 'success');
    }

    public function confirmBulkDelete(): void
    {
        auth('adminPanel')->user()?->can('admins.delete') ?: abort(403);

        if (count($this->selected) === 0) {
            $this->dispatch('toast', message: 'موردی انتخاب نشده است.', type: 'error');

            return;
        }

        $this->confirmingBulkDelete = true;
    }

    /** پورت ۱:۱ multipleDestroy قدیمی (قانون ids.* با گارد خودِ مدیر/creator) */
    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('admins.delete') ?: abort(403);

        $this->validate([
            'selected' => 'required|array',
        ], [], [
            'selected' => 'موارد انتخاب شده',
        ]);

        $me = auth('adminPanel')->user();

        $admins = Admin::whereIn('id', $this->selected)->get();

        // پورت قانون قدیمی: id != من && level != creator — موارد نامعتبر کل عملیات را رد می‌کنند
        $invalid = $admins->contains(fn (Admin $admin) => $admin->id === $me->id || $admin->level === 'creator');

        if ($invalid) {
            $this->addError('selected', 'حذف خودتان یا مدیر کل مجاز نیست.');

            return;
        }

        foreach ($admins as $admin) {
            $this->destroyAdmin($admin);
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        unset($this->admins);

        // متن پیام عیناً از JS قدیمی
        $this->dispatch('toast', message: 'کاربران انتخاب شده با موفقیت حذف شدند.', type: 'success');
    }

    /** پورت destroy قدیمی (حذف تصویر + حذف رکورد) */
    protected function destroyAdmin(Admin $admin): void
    {
        if ($admin->image) {
            Storage::disk('public')->delete($admin->image);
        }

        $admin->roles()->detach();
        $admin->delete();
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.admins.admins')
            ->layout('admin-panel::layout');
    }
}

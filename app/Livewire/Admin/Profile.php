<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * فاز ۲۱ — صفحه ویرایش پروفایل مدیر (پورت admin/profile پنل قدیمی)
 * لینک قدیمی منوی پروفایل به پنل قدیمی می‌رفت؛ حالا این صفحه در پنل جدید است.
 */
#[Layout('admin-panel::layout')]
#[Title('پروفایل من')]
class Profile extends Component
{
    use WithFileUploads;

    public string $first_name = '';
    public string $last_name = '';
    public string $username = '';
    public string $email = '';
    public string $bio = '';

    /** تصویر جدید پروفایل (TemporaryUploadedFile) */
    public $image = null;

    public string $password = '';
    public $password_confirmation = null;

    /** شبکه‌های اجتماعی */
    public string $instagram = '';
    public string $whatsapp = '';
    public string $eitaa = '';
    public string $telegram = '';
    public string $twitter = '';
    public string $facebook = '';
    public string $rubika = '';
    public string $bale = '';

    public function mount(): void
    {
        $admin = Auth::guard('adminPanel')->user();

        $this->first_name = (string) $admin->first_name;
        $this->last_name = (string) $admin->last_name;
        $this->username = (string) $admin->username;
        $this->email = (string) $admin->email;
        $this->bio = (string) $admin->bio;

        $this->instagram = (string) $admin->instagram;
        $this->whatsapp = (string) $admin->whatsapp;
        $this->eitaa = (string) $admin->eitaa;
        $this->telegram = (string) $admin->telegram;
        $this->twitter = (string) $admin->twitter;
        $this->facebook = (string) $admin->facebook;
        $this->rubika = (string) $admin->rubika;
        $this->bale = (string) $admin->bale;
    }

    protected function rules(): array
    {
        $admin = Auth::guard('adminPanel')->user();

        return [
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'username' => 'required|string|max:191|unique:admins,username,' . $admin->id,
            'email' => 'nullable|email|max:191|unique:admins,email,' . $admin->id,
            'bio' => 'nullable|string|max:2000',
            'image' => 'nullable|image|max:2048',
            'password' => 'nullable|string|min:8|same:password_confirmation',
            'password_confirmation' => 'nullable',

            'instagram' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/)?(www\.)?instagram\.com\/[A-Za-z0-9_.]+\/?$/'],
            'twitter' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/)?(www\.)?(twitter|x)\.com\/[A-Za-z0-9_]+\/?$/'],
            'facebook' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/)?(www\.)?facebook\.com\/[A-Za-z0-9.]+\/?$/'],
            'whatsapp' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/', 'max:20'],
            'telegram' => ['nullable', 'string', 'regex:/^[A-Za-z][A-Za-z0-9_]{4,31}$/', 'max:32'],
            'eitaa' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_]{3,30}$/', 'max:30'],
            'rubika' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_]{3,30}$/', 'max:30'],
            'bale' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_]{3,30}$/', 'max:30'],
        ];
    }

    protected function messages(): array
    {
        return [
            'first_name.required' => 'نام الزامی است',
            'last_name.required' => 'نام خانوادگی الزامی است',
            'username.required' => 'نام کاربری الزامی است',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است',
            'email.email' => 'ایمیل معتبر نیست',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است',
            'image.image' => 'فایل آپلود شده باید تصویر باشد',
            'image.max' => 'حجم تصویر حداکثر ۲ مگابایت است',
            'password.min' => 'رمز عبور حداقل ۸ کاراکتر است',
            'password.same' => 'تکرار رمز عبور مطابقت ندارد',
            'instagram.regex' => 'آدرس اینستاگرام معتبر نیست (مثال: instagram.com/username)',
            'twitter.regex' => 'آدرس توییتر/ایکس معتبر نیست',
            'facebook.regex' => 'آدرس فیسبوک معتبر نیست',
            'whatsapp.regex' => 'شماره واتساپ معتبر نیست (فرمت بین‌المللی)',
            'telegram.regex' => 'آیدی تلگرام معتبر نیست (بدون @، ۵ تا ۳۲ کاراکتر)',
            'eitaa.regex' => 'آیدی ایتا معتبر نیست',
            'rubika.regex' => 'آیدی روبیکا معتبر نیست',
            'bale.regex' => 'آیدی بله معتبر نیست',
        ];
    }

    public function save(): void
    {
        $admin = Auth::guard('adminPanel')->user();

        $this->validate();

        // تغییرات برای لاگ
        $changes = [];
        foreach (['first_name', 'last_name', 'username', 'email'] as $field) {
            if ($admin->{$field} != $this->{$field}) {
                $changes[$field] = [$admin->{$field}, $this->{$field}];
            }
        }

        $admin->fill([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email ?: null,
            'bio' => $this->bio ?: null,
            'instagram' => $this->instagram ?: null,
            'whatsapp' => $this->whatsapp ?: null,
            'eitaa' => $this->eitaa ?: null,
            'telegram' => $this->telegram ?: null,
            'twitter' => $this->twitter ?: null,
            'facebook' => $this->facebook ?: null,
            'rubika' => $this->rubika ?: null,
            'bale' => $this->bale ?: null,
        ]);

        // تصویر پروفایل
        if ($this->image) {
            if (! is_dir(public_path('uploads/admins'))) {
                mkdir(public_path('uploads/admins'), 0775, true);
            }

            $imageName = time() . '_' . $admin->id . '.' . $this->image->getClientOriginalExtension();
            $this->image->move(public_path('uploads/admins'), $imageName);

            if ($admin->image && file_exists(public_path($admin->image))) {
                @unlink(public_path($admin->image));
            }

            $admin->image = '/uploads/admins/' . $imageName;
            $this->reset('image');
        }

        if ($this->password) {
            $admin->password = Hash::make($this->password);
        }

        $admin->save();

        activity()
            ->performedOn($admin)
            ->causedBy($admin)
            ->withProperties(['action' => 'update_profile', 'ip' => request()->ip()])
            ->log('مدیر «' . $admin->fullname . '» پروفایل خود را ویرایش کرد');

        // پس از تغییر رمز، فیلدها پاک شوند
        $this->reset('password', 'password_confirmation');

        $this->dispatch('toast', message: 'پروفایل با موفقیت ذخیره شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.profile', [
            'admin' => Auth::guard('adminPanel')->user(),
        ]);
    }
}

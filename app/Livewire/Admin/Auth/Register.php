<?php

namespace App\Livewire\Admin\Auth;

use App\Models\Admin;
use App\Services\SetupFinalizer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۲۵ — صفحه‌ی ثبت‌نام اولین مدیر (طراحی جدید هم‌سبک با ورود پنل)
 *
 * بعد از اتمام نصب (جداول + سیدر)، مدیر به همین صفحه هدایت می‌شود:
 *   - حساب مدیر کل (سطح creator + نقش ۱) ساخته می‌شود
 *   - آدرس اختصاصی پنل در .env ثبت می‌شود
 *   - کارهای پس از نصب انجام می‌شود (شورت‌کات استایل فرانت در public،
 *     theme:config، درایور سشن/صف، optimize، نشان نصب و…)
 *   - مدیر مستقیم به پنل مدیریت وارد می‌شود
 */
#[Layout('admin-panel::layouts.login')]
#[Title('ثبت‌نام مدیر فروشگاه')]
class Register extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $username = '';
    public string $admin_route_prefix = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount()
    {
        // اگر مدیری وجود دارد، صفحه‌ی ثبت‌نام دیگر معنا ندارد
        if (Admin::count() > 0) {
            return $this->redirect(route('np.login', absolute: false), navigate: true);
        }

        if (Auth::guard('adminPanel')->check()) {
            return $this->redirect(route('np.dashboard', absolute: false), navigate: true);
        }

        // پیشوند پیشنهادی — یک‌بار تولید می‌شود تا ورودی و پیش‌نمایش یکسان بمانند
        $this->admin_route_prefix = 'admin' . random_int(11111, 99999);
    }

    protected function rules(): array
    {
        return [
            'first_name'         => 'required|string|max:191',
            'last_name'          => 'required|string|max:191',
            'username'           => 'required|string|max:191|unique:admins,username',
            'admin_route_prefix' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,100}$/'],
            'password'           => 'required|min:8|confirmed',
        ];
    }

    protected function messages(): array
    {
        return [
            'first_name.required'            => 'نام الزامی است.',
            'last_name.required'             => 'نام خانوادگی الزامی است.',
            'username.required'              => 'نام کاربری الزامی است.',
            'username.unique'                => 'این نام کاربری قبلاً ثبت شده است.',
            'admin_route_prefix.required'    => 'آدرس اختصاصی پنل الزامی است.',
            'admin_route_prefix.regex'       => 'آدرس پنل فقط می‌تواند شامل حروف انگلیسی، عدد و خط تیره باشد (حداکثر ۱۰۰ کاراکتر).',
            'password.required'              => 'گذرواژه الزامی است.',
            'password.min'                   => 'گذرواژه باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed'             => 'گذرواژه و تکرار آن یکسان نیستند.',
        ];
    }

    public function register(SetupFinalizer $finalizer): void
    {
        $this->validate();

        // امنیتی: قفل اتمی — جلوگیری از رقابت همزمان برای ساخت اولین مدیر
        try {
            $lock = cache()->lock('install:creator-lock', 30);

            if (! $lock->get()) {
                throw ValidationException::withMessages([
                    'username' => 'فرآیند نصب در حال انجام است؛ لطفاً چند لحظه بعد تلاش کنید.',
                ]);
            }

            try {
                $this->performRegistration($finalizer);
            } finally {
                optional($lock)->release();
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // اگر درایور کش از lock پشتیبانی نمی‌کند، بدون قفل ادامه بده
            report($e);

            $this->performRegistration($finalizer);
        }
    }

    protected function performRegistration(SetupFinalizer $finalizer): void
    {
        /*
         * فاز ۲۵ — ضد ارسال تکراری: اگر وسط راه‌اندازی (optimize و…) درخواست
         * دوباره برسد، ساخت مدیر دوم با خطای unique کرش می‌کرد. در این حالت
         * اولین مدیرِ ساخته‌شده را لاگین می‌کنیم و به پنل می‌رویم.
         */
        $existing = Admin::orderBy('id')->first();

        if ($existing) {
            Auth::guard('adminPanel')->login($existing);
            session()->regenerate();

            $prefix = admin_route_prefix() ?: 'admin';
            $this->redirect(url('/admin/' . $prefix));

            return;
        }

        try {
            $admin = Admin::create([
                'first_name'     => $this->first_name,
                'last_name'      => $this->last_name,
                'username'       => $this->username,
                'level'          => 'creator',
                'password'       => Hash::make($this->password),
                'remember_token' => Str::random(10),
                'verified_at'    => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // ارسال موازی/تکراری — مدیر اول را لاگین کن
            $admin = Admin::orderBy('id')->first();

            Auth::guard('adminPanel')->login($admin);
            session()->regenerate();

            $prefix = admin_route_prefix() ?: 'admin';
            $this->redirect(url('/admin/' . $prefix));

            return;
        }

        // نقش «مدیر کل» (اولین نقش/role_id=1) — مثل پنل قدیمی
        $roleId = DB::table('roles')->orderBy('id')->value('id') ?: 1;

        if (DB::getSchemaBuilder()->hasTable('admin_role')) {
            DB::table('admin_role')->insertOrIgnore([
                'role_id' => $roleId,
                'admin_id' => $admin->id,
            ]);
        }

        /*
         * فاز ۲۵ — مهم: لاگین «قبل» از finalize انجام می‌شود.
         *
         * finalize داخلش config:cache صدا می‌زند و getFreshConfiguration یک
         * نمونه‌ی دوم Application را در همین پردازش بوت می‌کند که همه‌ی Facadeها
         * (session/auth/…) را به آن اپ جدید منتقل می‌کند. اگر لاگین بعد از آن
         * انجام شود، داده‌ی احراز هویت در سشنِ بی‌ربطِ اپ دوم نوشته می‌شود و
         * هرگز ذخیره نمی‌شود (کاربر بعد از ثبت‌نام لاگ‌اوت می‌بود).
         */
        Auth::guard('adminPanel')->login($admin);
        session()->regenerate();

        // کارهای پس از نصب: prefix، شورت‌کات استایل فرانت در public،
        // theme:config، درایور سشن/صف، نشان نصب و اطلاع‌رسانی
        // (optimize و پینگِ شبکه بعد از ارسال پاسخ اجرا می‌شوند — SetupFinalizer)
        $finalizer->finalize($this->admin_route_prefix);

        // خروج کامل از صفحه (نه SPA) — چون .env و آدرس پنل تازه ثبت شده
        $this->redirect(url('/admin/' . $this->admin_route_prefix));
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.auth.register');
    }
}

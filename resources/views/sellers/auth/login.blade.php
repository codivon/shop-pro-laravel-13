{{-- ورود فروشندگان پنل مدرن (فاز ۱۰۸) — فرم با fetch به seller.login_check (JSON همان کنترلر قدیمی) --}}
{{-- فاز ۱۰۹ — هندلر چندخطی به متد منتقل شد (عبارت var-دار در evaluator جدید می‌شکند) --}}
<div class="space-y-5" x-data="{
        busy: false,
        async submit() {
            this.busy = true;
            let fd = new FormData();
            fd.append('username', this.$refs.username.value);
            fd.append('password', this.$refs.password.value);
            if (this.$refs.remember.checked) { fd.append('remember', 1); }
            try {
                let data = await spPost('{{ route('seller.login_check') }}', fd);
                if (data.status === 'success') { spToast(data.message || 'با موفقیت وارد شدید', 'success'); setTimeout(function () { window.location.href = data.redirect || '{{ route('seller.dashboard') }}'; }, 400); }
                else { spToast(data.message || 'ورود ناموفق بود', 'error'); this.busy = false; }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')

    <div class="ap-card p-6 sm:p-8">
        <div class="text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-brand-500/12 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin-panel::icon name="log-in" class="size-6" />
            </span>
            <h1 class="mt-3 text-lg font-extrabold text-ink dark:text-night-ink">ورود به پنل فروشندگان</h1>
            <p class="mt-1 text-xs font-semibold text-ink-3 dark:text-night-ink-2">با شمارهٔ موبایل یا ایمیل خود وارد شوید</p>
        </div>

        <form class="mt-6 space-y-4" x-on:submit.prevent="submit()">
            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-login-username">شماره موبایل یا ایمیل</label>
                <div class="relative">
                    <x-admin-panel::icon name="user" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-login-username" x-ref="username" type="text" required
                           class="ap-input pe-10! py-3!" placeholder="شماره موبایل یا ایمیل" autocomplete="username">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-login-password">رمز عبور</label>
                <div class="relative">
                    <x-admin-panel::icon name="lock" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-login-password" x-ref="password" type="password" required
                           class="ap-input pe-10! py-3!" placeholder="رمز عبور" autocomplete="current-password">
                </div>
            </div>

            <label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-ink-2 dark:text-night-ink-2">
                <input type="checkbox" x-ref="remember" class="size-4 rounded border-line accent-brand-600">
                مرا به خاطر بسپار
            </label>

            <button type="submit" class="ap-btn-primary w-full py-3!" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
                <span x-show="!busy" class="flex items-center justify-center gap-2">
                    <x-admin-panel::icon name="log-in" class="size-4.5" />
                    ورود به پنل
                </span>
                <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    در حال ورود…
                </span>
            </button>
        </form>

        <p class="mt-5 text-center text-xs font-bold text-ink-3 dark:text-night-ink-2">
            حساب کاربری ندارید؟
            <a href="{{ route('seller.registration') }}" wire:navigate class="text-brand-600 transition-colors hover:underline dark:text-brand-400">ثبت‌نام فروشنده</a>
        </p>
        <p class="mt-1.5 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
            <a href="{{ route('front.index') }}" class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">بازگشت به فروشگاه</a>
        </p>
    </div>
</div>

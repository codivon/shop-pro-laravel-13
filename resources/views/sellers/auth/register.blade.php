{{-- گام ۱ ثبت‌نام — اطلاعات پایه (فاز ۱۰۸). فیلدها عین قدیمی: email / password / mobile --}}
<!-- فاز ۱۰۹ — هندلرهای چندخطی به متد منتقل شد؛ عبارت‌های inline که با var شروع شوند
     در evaluator جدید Livewire/Alpine (‎__self.result = …‎) می‌شکنند -->
<div class="space-y-5" x-data="{
        busy: false,
        async submit() {
            this.busy = true;
            let fd = new FormData();
            fd.append('email', this.$refs.email.value);
            fd.append('password', this.$refs.password.value);
            fd.append('mobile', this.$refs.mobile.value);
            try {
                let data = await spPost('{{ route('seller.registration_new_seller') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; }
                else { spToast(data.message || 'ثبت‌نام ناموفق بود', 'error'); this.busy = false; }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 1])

    <div class="ap-card p-6 sm:p-8">
        <h1 class="text-lg font-extrabold text-ink dark:text-night-ink">ساخت حساب فروشنده</h1>
        <p class="mt-1 text-xs font-semibold text-ink-3 dark:text-night-ink-2">ایمیل، شمارهٔ موبایل و رمز عبور خود را وارد کنید</p>

        <form class="mt-6 space-y-4" x-on:submit.prevent="submit()">
            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-reg-email">ایمیل</label>
                <div class="relative">
                    <x-admin-panel::icon name="mail" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-reg-email" x-ref="email" type="email" dir="ltr" required
                           class="ap-input ps-10! py-3! text-start" placeholder="you@example.com" autocomplete="email">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-reg-password">رمز عبور</label>
                <div class="relative">
                    <x-admin-panel::icon name="lock" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-reg-password" x-ref="password" type="password" required
                           class="ap-input pe-10! py-3!" placeholder="رمز عبور" autocomplete="new-password">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-reg-mobile">شمارهٔ موبایل</label>
                <div class="relative">
                    <x-admin-panel::icon name="smartphone" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    {{-- فاز ۱۰۹ — پیش‌پرکردن موبایل از لندینگ (?mobile=) --}}
                    <input id="sp-reg-mobile" x-ref="mobile" type="tel" dir="ltr" inputmode="numeric" required
                           value="{{ request()->query('mobile') }}"
                           class="ap-input ps-10! py-3! text-start fa-digits" placeholder="09xxxxxxxxx" maxlength="11" autocomplete="tel">
                </div>
                <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کد تأیید به این شماره پیامک می‌شود</p>
            </div>

            <button type="submit" class="ap-btn-primary w-full py-3!" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
                <span x-show="!busy" class="flex items-center justify-center gap-2">
                    ادامه
                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                </span>
                <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    در حال ثبت…
                </span>
            </button>
        </form>

        <p class="mt-5 text-center text-xs font-bold text-ink-3 dark:text-night-ink-2">
            قبلاً ثبت‌نام کرده‌اید؟
            <a href="{{ route('seller.login') }}" wire:navigate class="text-brand-600 transition-colors hover:underline dark:text-brand-400">ورود به پنل</a>
        </p>
    </div>
</div>

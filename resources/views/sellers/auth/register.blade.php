{{-- گام ۱ ثبت‌نام — فاز ۱۱۰: فقط شمارهٔ موبایل (ایمیل/رمز در گام کسب‌وکار جمع می‌شود)
     ارسال با fetch به seller.registration_phone_send؛ کد ۶ رقمی پیامک می‌شود.
     فاز ۱۱۱: پیام‌های خطای سرور با spToast (میزبان در لایوت مهمان) نمایش داده می‌شود و
     پاسخ status=exists (فروشندهٔ فعالِ تکمیل‌شده) مودال دو‌دکمه‌ای ورود/تغییر شماره را باز می‌کند --}}
<div class="space-y-5" x-data="{
        busy: false,
        mobile: '{{ request()->query('mobile', '') }}',
        async submit() {
            this.busy = true;
            let fd = new FormData();
            fd.append('mobile', this.mobile);
            try {
                let data = await spPost('{{ route('seller.registration_phone_send') }}', fd);
                if (data.status === 'exists') {
                    document.dispatchEvent(new CustomEvent('sp-registration-exists', { detail: { login_url: data.login_url || '{{ route('seller.login') }}' } }));
                    this.busy = false;
                    return;
                }
                if (data.redirect) { window.location.href = data.redirect; }
                else { spToast(data.message || 'ارسال کد ناموفق بود', 'error'); this.busy = false; }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 1])

    <div class="ap-card p-6 sm:p-8">
        <div class="text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-brand-500/12 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin-panel::icon name="smartphone" class="size-6" />
            </span>
            <h1 class="mt-3 text-lg font-extrabold text-ink dark:text-night-ink">ساخت حساب فروشنده</h1>
            <p class="mt-1 text-xs font-semibold leading-6 text-ink-3 dark:text-night-ink-2">
                فقط شمارهٔ موبایل کافی است؛ کد تأیید برای شما پیامک می‌شود
            </p>
        </div>

        <form class="mt-6 space-y-4" x-on:submit.prevent="submit()">
            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-reg-mobile">شمارهٔ موبایل</label>
                <div class="relative">
                    <x-admin-panel::icon name="smartphone" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-reg-mobile" type="tel" dir="ltr" inputmode="numeric" required autofocus
                           x-model="mobile" maxlength="11"
                           class="ap-input py-3.5! ps-10! text-start fa-digits text-[15px]" placeholder="09xxxxxxxxx" autocomplete="tel">
                </div>
                <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">با وارد کردن شماره، کد تأیید ۶ رقمی برای شما پیامک می‌شود</p>
            </div>

            <button type="submit" class="ap-btn-primary w-full py-3.5!" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
                <span x-show="!busy" class="flex items-center justify-center gap-2">
                    ادامه
                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                </span>
                <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    در حال ارسال کد…
                </span>
            </button>
        </form>

        <p class="mt-5 text-center text-xs font-bold text-ink-3 dark:text-night-ink-2">
            قبلاً ثبت‌نام کرده‌اید؟
            <a href="{{ route('seller.login') }}" wire:navigate class="text-brand-600 transition-colors hover:underline dark:text-brand-400">ورود به پنل</a>
        </p>
    </div>
</div>

{{-- فاز ۱۱۱ — مودال «فروشنده با این شماره از قبل ثبت‌نام کرده است» (ورود / تغییر شماره) --}}
@include('sellers.auth.partials.exists-modal')

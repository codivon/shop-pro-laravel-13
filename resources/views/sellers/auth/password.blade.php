{{-- گام ۳ ثبت‌نام — فاز ۱۱۲: تنظیم رمز عبور (فقط دو فیلد رمز و تکرارش).
     ارسال به seller.registration_password_store؛ رمز هش‌شده در پیش‌نویس ذخیره
     می‌شود و بعد از آن، این گام دیگر به کاربر نمایش داده نمی‌شود --}}
<div class="space-y-5" x-data="{
        busy: false,
        errors: {},
        init() { window.spClearErrorOnInput(this.$refs.form, this); },
        async submit() {
            this.busy = true;
            this.errors = {};
            let fd = new FormData(this.$refs.form);
            // فاز ۱۱۳ — ولیدیشن کلاینتی فیلدهای الزامی (پیام فارسی، بدون حبابهٔ مرورگر)
            if (spRequire(this.$refs.form, this.errors)) {
                spToast('لطفاً فیلدهای الزامی را تکمیل کنید', 'error');
                this.busy = false;
                return;
            }
            try {
                let data = await spPost('{{ route('seller.registration_password_store') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; return; }
                this.errors = data.errors || {};
                spToast(data.message || 'ذخیرهٔ رمز عبور ناموفق بود', 'error');
                this.busy = false;
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        }
    }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 3, 'back' => route('seller.registration_mobile')])

    <div class="ap-card p-6 sm:p-8">
        <div class="text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-brand-500/12 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin-panel::icon name="lock-keyhole" class="size-6" />
            </span>
            <h1 class="mt-3 text-lg font-extrabold text-ink dark:text-night-ink">تنظیم رمز عبور</h1>
            <p class="mt-1 text-xs font-semibold leading-6 text-ink-3 dark:text-night-ink-2">
                یک رمز عبور برای ورود به پنل فروشندگان انتخاب کنید
            </p>
        </div>

        <form class="mt-6 space-y-4" x-on:submit.prevent="submit()" x-ref="form" novalidate>
            {{-- فاز ۱۱۲ — خطاهای ولیدیشن به‌صورت درون‌خطی زیر هر فیلد --}}
            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-pass">رمز عبور <span class="text-red-500">*</span></label>
                <div class="relative">
                    <x-admin-panel::icon name="lock" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-pass" type="password" name="password" required minlength="8"
                           x-on:input="delete errors.password"
                           class="ap-input py-3! pe-10!" placeholder="حداقل ۸ کاراکتر" autocomplete="new-password">
                </div>
                <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.password" x-text="(errors.password || [])[0]" x-cloak></p>
                <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2" x-show="! errors.password">با این رمز به پنل فروشندگان وارد می‌شوید</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-pass-confirm">تکرار رمز عبور <span class="text-red-500">*</span></label>
                <div class="relative">
                    <x-admin-panel::icon name="lock" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-pass-confirm" type="password" name="password_confirmation" required
                           x-on:input="delete errors.password"
                           class="ap-input py-3! pe-10!" placeholder="تکرار رمز عبور" autocomplete="new-password">
                </div>
                <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.password_confirmation" x-text="(errors.password_confirmation || [])[0]" x-cloak></p>
            </div>

            <button type="submit" class="ap-btn-primary w-full py-3.5!" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
                <span x-show="!busy" class="flex items-center justify-center gap-2">
                    ذخیره و ادامه
                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                </span>
                <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    در حال ذخیره…
                </span>
            </button>
        </form>

        <p class="mt-5 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
            رمز عبور را در جای امن نگه دارید؛ برای بازیابی آن با پشتیبانی تماس بگیرید.
        </p>
    </div>
</div>

{{-- ورود فروشندگان پنل مدرن — فاز ۱۱۲: دو روش ورود
     ① رمز عبور (موبایل/ایمیل + رمز → seller.login_check)
     ② کد تأیید (فقط موبایل → کد ۶ رقمی → seller.login_otp_verify)
     فاز ۱۱۳: روش اول ربات‌گیر (کد امنیتی تصویری CaptchaService) گرفت —
     سوییچ در تنظیمات برنامه‌نویس ← تب چندفروشندگی (seller_login_captcha) --}}
@php
    $loginCaptcha = \App\Support\MultiVendorSettings::loginCaptchaEnabled();
@endphp
<div class="space-y-5" x-data="{
        busy: false,
        errors: {},
        tab: 'password',
        otpStep: 'mobile',
        otpMobile: '',
        code: ['', '', '', '', '', ''],
        seconds: 0,
        tick() {
            if (this.seconds > 0) { this.seconds--; setTimeout(() => this.tick(), 1000); }
        },
        onInput(index, event) {
            let value = (event.target.value || '').replace(/[^0-9]/g, '');
            if (value.length > 1) {
                let rest = value.split('');
                for (let i = 0; i < rest.length && index + i < 6; i++) { this.code[index + i] = rest[i]; }
                let last = Math.min(index + rest.length, 5);
                this.$refs['box' + last]?.focus();
            } else {
                this.code[index] = value;
                if (value && index < 5) { this.$refs['box' + (index + 1)]?.focus(); }
            }
            if (this.code.join('').length === 6 && ! this.busy && this.tab === 'otp') { this.submitOtpVerify(); }
        },
        onKeydown(index, event) {
            if (event.key === 'Backspace' && ! this.code[index] && index > 0) { this.$refs['box' + (index - 1)]?.focus(); }
        },
        switchTab(t) {
            this.tab = t;
            this.errors = {};
            this.busy = false;
        },
        async submitPassword() {
            this.busy = true;
            this.errors = {};
            // فاز ۱۱۳ — ولیدیشن کلاینتی فیلدهای الزامی (پیام فارسی، بدون حبابهٔ مرورگر)
            if (! String(this.$refs.username.value || '').trim()) { this.errors.username = ['لطفا این قسمت را خالی نگذارید']; }
            if (! this.$refs.password.value) { this.errors.password = ['رمز عبور را وارد کنید']; }
            @if ($loginCaptcha)
            if (! String(this.$refs.captcha?.value || '').trim()) { this.errors.captcha = ['کد امنیتی را وارد کنید']; }
            @endif
            if (Object.keys(this.errors).length) { spToast('لطفاً فیلدهای الزامی را تکمیل کنید', 'error'); this.busy = false; return; }
            let fd = new FormData();
            fd.append('username', this.$refs.username.value);
            fd.append('password', this.$refs.password.value);
            if (this.$refs.remember.checked) { fd.append('remember', 1); }
            @if ($loginCaptcha)
            fd.append('captcha', this.$refs.captcha?.value || '');
            @endif
            try {
                let data = await spPost('{{ route('seller.login_check') }}', fd);
                if (data.status === 'success') { spToast(data.message || 'با موفقیت وارد شدید', 'success'); setTimeout(function () { window.location.href = data.redirect || '{{ route('seller.dashboard') }}'; }, 400); return; }
                this.errors = data.errors || {};
                spToast(data.message || 'ورود ناموفق بود', 'error');
                @if ($loginCaptcha)
                // کد امنیتی یک‌بارمصرف است — بعد از هر تلاش ناموفق تصویر تازه
                this.refreshCaptcha();
                @endif
                this.busy = false;
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        },
        @if ($loginCaptcha)
        refreshCaptcha() {
            if (this.$refs.captchaImg) { this.$refs.captchaImg.src = '{{ route('captcha.image') }}?_=' + Date.now(); }
            if (this.$refs.captcha) { this.$refs.captcha.value = ''; }
        },
        @endif
        async sendOtp() {
            this.busy = true;
            this.errors = {};
            let fd = new FormData();
            fd.append('mobile', this.$refs.otpMobile.value);
            try {
                let data = await spPost('{{ route('seller.login_otp_send') }}', fd);
                if (data.status === 'success') {
                    this.otpMobile = this.$refs.otpMobile.value;
                    this.otpStep = 'code';
                    this.code = ['', '', '', '', '', ''];
                    this.seconds = {{ \App\Support\SellerRegistrationFlow::RESEND_SECONDS }};
                    this.tick();
                    this.$nextTick(() => this.$refs['box0']?.focus());
                    spToast(data.message || 'کد تأیید پیامک شد', 'success');
                } else {
                    this.errors = data.errors || {};
                    spToast(data.message || 'ارسال کد ناموفق بود', 'error');
                }
                this.busy = false;
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        },
        async resendOtp() {
            let fd = new FormData();
            fd.append('mobile', this.otpMobile);
            try {
                let data = await spPost('{{ route('seller.login_otp_send') }}', fd);
                if (data.status === 'success') {
                    spToast('کد جدید پیامک شد', 'success');
                    this.code = ['', '', '', '', '', ''];
                    this.$refs['box0']?.focus();
                    this.seconds = {{ \App\Support\SellerRegistrationFlow::RESEND_SECONDS }};
                    this.tick();
                } else if (data.status === 'errorTime') { spToast('برای ارسال مجدد کمی صبر کنید', 'error'); }
                else { spToast(data.message || 'ارسال مجدد ناموفق بود', 'error'); }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); }
        },
        async submitOtpVerify() {
            this.busy = true;
            this.errors = {};
            let fd = new FormData();
            fd.append('mobile', this.otpMobile);
            fd.append('verify_code', this.code.join(''));
            if (this.$refs.otpRemember && this.$refs.otpRemember.checked) { fd.append('remember', 1); }
            try {
                let data = await spPost('{{ route('seller.login_otp_verify') }}', fd);
                if (data.status === 'success') { spToast(data.message || 'با موفقیت وارد شدید', 'success'); setTimeout(function () { window.location.href = data.redirect || '{{ route('seller.dashboard') }}'; }, 400); return; }
                this.errors = data.errors || {};
                spToast(data.message || 'ورود ناموفق بود', 'error');
                this.busy = false;
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
            <p class="mt-1 text-xs font-semibold text-ink-3 dark:text-night-ink-2">یکی از دو روش زیر برای ورود استفاده کنید</p>
        </div>

        {{-- سوییچ روش ورود --}}
        <div class="mt-5 grid grid-cols-2 gap-1 rounded-2xl bg-surface-2 p-1 dark:bg-night-3/70" role="tablist" aria-label="روش ورود">
            <button type="button" x-on:click="switchTab('password')" role="tab"
                    x-bind:aria-selected="String(tab === 'password')"
                    x-bind:class="tab === 'password' ? 'bg-surface shadow-sm text-brand-700 dark:bg-night-2 dark:text-brand-300' : 'text-ink-3 dark:text-night-ink-2'"
                    class="flex min-h-10 items-center justify-center gap-1.5 rounded-xl text-[13px] font-extrabold transition-all">
                <x-admin-panel::icon name="lock" class="size-4" />
                رمز عبور
            </button>
            <button type="button" x-on:click="switchTab('otp')" role="tab"
                    x-bind:aria-selected="String(tab === 'otp')"
                    x-bind:class="tab === 'otp' ? 'bg-surface shadow-sm text-brand-700 dark:bg-night-2 dark:text-brand-300' : 'text-ink-3 dark:text-night-ink-2'"
                    class="flex min-h-10 items-center justify-center gap-1.5 rounded-xl text-[13px] font-extrabold transition-all">
                <x-admin-panel::icon name="shield-check" class="size-4" />
                کد تأیید
            </button>
        </div>

        {{-- ========== روش ۱: رمز عبور (موبایل/ایمیل) ========== --}}
        <form class="mt-6 space-y-4" x-show="tab === 'password'" x-on:submit.prevent="submitPassword()" novalidate>
            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-login-username">شماره موبایل یا ایمیل</label>
                <div class="relative">
                    <x-admin-panel::icon name="user" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-login-username" x-ref="username" type="text" required x-on:input="delete errors.username"
                           class="ap-input pe-10! py-3!" placeholder="شماره موبایل یا ایمیل" autocomplete="username">
                </div>
                <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.username" x-text="(errors.username || [])[0]" x-cloak></p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="sp-login-password">رمز عبور</label>
                <div class="relative">
                    <x-admin-panel::icon name="lock" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input id="sp-login-password" x-ref="password" type="password" required x-on:input="delete errors.password"
                           class="ap-input pe-10! py-3!" placeholder="رمز عبور" autocomplete="current-password">
                </div>
                <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.password" x-text="(errors.password || [])[0]" x-cloak></p>
            </div>

            @if ($loginCaptcha)
                {{-- فاز ۱۱۳ — ربات‌گیر ورود با رمز عبور --}}
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="sp-login-captcha">کد امنیتی <span class="text-red-500">*</span></label>
                    <div class="flex items-stretch gap-2">
                        <div class="relative flex-1">
                            <x-admin-panel::icon name="shield" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                            <input id="sp-login-captcha" x-ref="captcha" type="text" dir="ltr" maxlength="5" autocomplete="off" autocapitalize="off" spellcheck="false"
                                   x-on:input="delete errors.captcha"
                                   class="ap-input pe-10! py-3! text-center font-mono tracking-widest" placeholder="ABCDX">
                        </div>
                        <button type="button" x-on:click="refreshCaptcha()" title="تولید کد جدید — با کلیک روی تصویر هم کد تازه می‌آید"
                                class="relative w-28 shrink-0 overflow-hidden rounded-xl border border-line bg-night-1 dark:border-night-line"
                                aria-label="تولید کد امنیتی جدید">
                            <img x-ref="captchaImg" src="{{ route('captcha.image') }}" alt="کد امنیتی" class="size-full object-cover">
                            <span class="absolute bottom-1 end-1 flex size-6 items-center justify-center rounded-lg bg-white/15 text-white backdrop-blur">
                                <x-admin-panel::icon name="refresh-cw" class="size-3.5" />
                            </span>
                        </button>
                    </div>
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.captcha" x-text="(errors.captcha || [])[0]" x-cloak></p>
                </div>
            @endif

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

        {{-- ========== روش ۲: موبایل + کد تأیید (فاز ۱۱۲) ========== --}}
        <div class="mt-6" x-show="tab === 'otp'" x-cloak>
            {{-- گام ۱: فقط شمارهٔ موبایل --}}
            <form class="space-y-4" x-show="otpStep === 'mobile'" x-on:submit.prevent="sendOtp()">
                <div>
                    <label class="mb-1.5 block text-xs font-bold" for="sp-otp-login-mobile">شمارهٔ موبایل</label>
                    <div class="relative">
                        <x-admin-panel::icon name="smartphone" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                        <input id="sp-otp-login-mobile" x-ref="otpMobile" type="tel" dir="ltr" inputmode="numeric" maxlength="11" required
                               x-on:input="delete errors.mobile"
                               class="ap-input pe-10! py-3! text-start fa-digits" placeholder="09xxxxxxxxx" autocomplete="tel">
                    </div>
                    <p class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.mobile" x-text="(errors.mobile || [])[0]" x-cloak></p>
                    <p class="mt-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2" x-show="! errors.mobile">کد تأیید ۶ رقمی به این شماره پیامک می‌شود</p>
                </div>

                <button type="submit" class="ap-btn-primary w-full py-3!" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
                    <span x-show="!busy" class="flex items-center justify-center gap-2">
                        <x-admin-panel::icon name="message-square" class="size-4.5" />
                        دریافت کد تأیید
                    </span>
                    <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                        در حال ارسال کد…
                    </span>
                </button>
            </form>

            {{-- گام ۲: کد ۶ رقمی --}}
            <form class="space-y-4" x-show="otpStep === 'code'" x-cloak x-on:submit.prevent="submitOtpVerify()">
                <div class="text-center">
                    <p class="text-xs font-semibold leading-6 text-ink-3 dark:text-night-ink-2">
                        کد تأیید ۶ رقمی به شمارهٔ
                        <span class="fa-digits font-extrabold text-ink dark:text-night-ink" x-text="otpMobile"></span>
                        پیامک شد
                    </p>
                    <button type="button" x-on:click="otpStep = 'mobile'; errors = {}"
                            class="mt-1 text-[11px] font-bold text-brand-600 transition-colors hover:underline dark:text-brand-400">
                        ویرایش شمارهٔ موبایل
                    </button>
                </div>

                <div>
                    <label class="mb-2 block text-center text-xs font-bold" for="sp-login-otp-0">کد تأیید ۶ رقمی</label>
                    <div class="flex justify-center gap-2" dir="ltr">
                        @foreach (range(0, 5) as $box)
                            <input id="sp-login-otp-{{ $box }}" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                                   x-model="code[{{ $box }}]" x-ref="box{{ $box }}"
                                   x-on:input="onInput({{ $box }}, $event)"
                                   x-on:keydown="onKeydown({{ $box }}, $event)"
                                   class="ap-input size-12! p-0! text-center font-mono fa-digits text-lg! sm:size-14!"
                                   aria-label="رقم {{ $box + 1 }} کد تأیید">
                        @endforeach
                    </div>
                    <p class="mt-2 text-center text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.verify_code" x-text="(errors.verify_code || [])[0]" x-cloak></p>
                </div>

                <label class="flex cursor-pointer items-center justify-center gap-2 text-xs font-bold text-ink-2 dark:text-night-ink-2">
                    <input type="checkbox" x-ref="otpRemember" class="size-4 rounded border-line accent-brand-600">
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

                <div class="text-center text-xs font-bold text-ink-3 dark:text-night-ink-2">
                    <span x-show="seconds > 0" class="fa-digits">ارسال مجدد کد تا <b x-text="String(Math.floor(seconds / 60)).padStart(1, '0') + ':' + String(seconds % 60).padStart(2, '0')"></b> دیگر</span>
                    <button type="button" x-show="seconds <= 0" x-cloak x-on:click="resendOtp()"
                            class="text-brand-600 transition-colors hover:underline dark:text-brand-400">
                        ارسال مجدد کد
                    </button>
                </div>
            </form>
        </div>

        <p class="mt-5 text-center text-xs font-bold text-ink-3 dark:text-night-ink-2">
            حساب کاربری ندارید؟
            <a href="{{ route('seller.registration') }}" wire:navigate class="text-brand-600 transition-colors hover:underline dark:text-brand-400">ثبت‌نام فروشنده</a>
        </p>
        <p class="mt-1.5 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
            <a href="{{ route('front.index') }}" class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">بازگشت به فروشگاه</a>
        </p>
    </div>
</div>

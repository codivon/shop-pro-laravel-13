{{-- گام ۲ ثبت‌نام — فاز ۱۱۰: کد تأیید ۶ رقمی.
     تایمر از سرور ($resendTime بر اساس updated_at دیتابیس) پر می‌شود؛ بنابراین
     با رفرش از دست نمی‌رود و ارسال مجدد هم با گیت ۲ دقیقه‌ای سمت سرور است --}}
<div class="space-y-5" x-data="{
        busy: false,
        errors: {},
        code: ['', '', '', '', '', ''],
        seconds: {{ $resendTime }},
        tick() {
            if (this.seconds > 0) { this.seconds--; setTimeout(() => this.tick(), 1000); }
        },
        onInput(index, event) {
            let value = (event.target.value || '').replace(/[^0-9]/g, '');
            if (value.length > 1) {
                // چسباندن/تایپ سریع — توزیع روی خانه‌ها
                let rest = value.split('');
                for (let i = 0; i < rest.length && index + i < 6; i++) {
                    this.code[index + i] = rest[i];
                }
                let last = Math.min(index + rest.length, 5);
                this.$refs['box' + last]?.focus();
            } else {
                this.code[index] = value;
                if (value && index < 5) { this.$refs['box' + (index + 1)]?.focus(); }
            }
            if (this.full() && ! this.busy) { this.submit(); }
        },
        onKeydown(index, event) {
            if (event.key === 'Backspace' && ! this.code[index] && index > 0) {
                this.$refs['box' + (index - 1)]?.focus();
            }
        },
        full() { return this.code.join('').length === 6; },
        async submit() {
            this.busy = true;
            this.errors = {};
            let fd = new FormData();
            fd.append('mobile', '{{ $seller['mobile'] }}');
            fd.append('verify_code', this.code.join(''));
            try {
                let data = await spPost('{{ route('seller.registration_phone_verify') }}', fd);
                if (data.status === 'exists') { window.location.href = data.login_url || '{{ route('seller.login') }}'; return; }
                if (data.redirect) { window.location.href = data.redirect; return; }
                this.errors = data.errors || {};
                spToast(data.message || 'کد وارد شده اشتباه است', 'error'); this.busy = false;
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
        },
        async resend() {
            let fd = new FormData();
            fd.append('mobile', '{{ $seller['mobile'] }}');
            try {
                let data = await spPost('{{ route('seller.registration_phone_resend') }}', fd);
                if (data.status === 'success') {
                    spToast('کد جدید پیامک شد', 'success');
                    this.code = ['', '', '', '', '', ''];
                    this.$refs['box0']?.focus();
                    this.seconds = {{ \App\Support\SellerRegistrationFlow::RESEND_SECONDS }};
                    this.tick();
                } else if (data.status === 'errorTime') { spToast('برای ارسال مجدد کمی صبر کنید', 'error'); }
                else if (data.redirect) { window.location.href = data.redirect; }
                else { spToast(data.message || 'ارسال مجدد ناموفق بود', 'error'); }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); }
        }
    }" x-init="tick(); $refs['box0']?.focus()">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 2])

    <div class="ap-card p-6 sm:p-8">
        <div class="text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-brand-500/12 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin-panel::icon name="shield-check" class="size-6" />
            </span>
            <h1 class="mt-3 text-lg font-extrabold text-ink dark:text-night-ink">تأیید شمارهٔ موبایل</h1>
            <p class="mt-1 text-xs font-semibold leading-6 text-ink-3 dark:text-night-ink-2">
                کد تأیید ۶ رقمی به شمارهٔ <span class="fa-digits font-extrabold text-ink dark:text-night-ink">{{ $seller['mobile'] }}</span> پیامک شد
            </p>
        </div>

        <form class="mt-6 space-y-4" x-on:submit.prevent="submit()">
            <input type="hidden" name="mobile" value="{{ $seller['mobile'] }}">

            <div>
                <label class="mb-2 block text-center text-xs font-bold" for="sp-otp-0">کد تأیید ۶ رقمی</label>
                <div class="flex justify-center gap-2" dir="ltr">
                    @foreach (range(0, 5) as $box)
                        <input id="sp-otp-{{ $box }}" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                               x-model="code[{{ $box }}]" x-ref="box{{ $box }}"
                               x-on:input="onInput({{ $box }}, $event)"
                               x-on:keydown="onKeydown({{ $box }}, $event)"
                               class="ap-input size-12! p-0! text-center font-mono fa-digits text-lg! sm:size-14!"
                               aria-label="رقم {{ $box + 1 }} کد تأیید">
                    @endforeach
                </div>
                <p class="mt-2 text-center text-[11px] font-bold text-red-600 dark:text-red-400" x-show="errors.verify_code" x-text="(errors.verify_code || [])[0]" x-cloak></p>
            </div>

            <button type="submit" class="ap-btn-primary w-full py-3.5!" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
                <span x-show="!busy" class="flex items-center justify-center gap-2">
                    تأیید و ادامه
                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                </span>
                <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    در حال بررسی…
                </span>
            </button>
        </form>

        {{-- ارسال مجدد — گیت ۲ دقیقه‌ای سروری، تایمر از دیتابیس (پایدار با رفرش) --}}
        <div class="mt-4 text-center text-xs font-bold text-ink-3 dark:text-night-ink-2">
            <span x-show="seconds > 0" class="fa-digits">ارسال مجدد کد تا <b x-text="String(Math.floor(seconds / 60)).padStart(1, '0') + ':' + String(seconds % 60).padStart(2, '0')"></b> دیگر</span>
            <button type="button" x-show="seconds <= 0" x-cloak
                    x-on:click="resend()"
                    class="text-brand-600 transition-colors hover:underline dark:text-brand-400">
                ارسال مجدد کد
            </button>
        </div>

        <p class="mt-5 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
            <a href="{{ route('seller.registration') }}" wire:navigate class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">ویرایش شمارهٔ موبایل</a>
        </p>
    </div>
</div>

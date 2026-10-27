{{-- گام ۲ ثبت‌نام — تأیید کد موبایل (فاز ۱۰۸). فیلدها عین قدیمی: mobile (hidden) + verify_code --}}
<div class="space-y-5" x-data="{
        busy: false,
        code: '',
        seconds: {{ $resendTime }},
        tick() { if (this.seconds > 0) { this.seconds--; setTimeout(() => this.tick(), 1000); } }
    }" x-init="tick()">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 2])

    <div class="ap-card p-6 sm:p-8">
        <div class="text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-brand-500/12 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin-panel::icon name="smartphone" class="size-6" />
            </span>
            <h1 class="mt-3 text-lg font-extrabold text-ink dark:text-night-ink">تأیید شمارهٔ موبایل</h1>
            <p class="mt-1 text-xs font-semibold leading-6 text-ink-3 dark:text-night-ink-2">
                کد تأیید به شمارهٔ <span class="fa-digits font-extrabold text-ink dark:text-night-ink">{{ $seller['mobile'] }}</span> پیامک شد
            </p>
        </div>

        <form class="mt-6 space-y-4" x-on:submit.prevent="
            busy = true;
            var fd = new FormData();
            fd.append('mobile', '{{ $seller['mobile'] }}');
            fd.append('verify_code', code);
            try {
                var data = await spPost('{{ route('seller.registration_mobile_check') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; }
                else { spToast(data.message || 'کد وارد شده اشتباه است', 'error'); busy = false; }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); busy = false; }
        ">
            <input type="hidden" name="mobile" value="{{ $seller['mobile'] }}">

            <div>
                <label class="mb-1.5 block text-center text-xs font-bold" for="sp-otp">کد تأیید ۵ رقمی</label>
                <input id="sp-otp" type="text" dir="ltr" inputmode="numeric" maxlength="5" required x-ref="otp"
                       x-model="code"
                       class="ap-input !py-3 text-center font-mono fa-digits tracking-[0.6em]"
                       placeholder="- - - - -" autocomplete="one-time-code">
            </div>

            <button type="submit" class="ap-btn-primary w-full !py-3" :disabled="busy || code.length < 5" x-bind:class="busy ? 'opacity-60' : ''">
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

        {{-- ارسال مجدد کد — همان اندپوینت قدیمی (get-new-code) --}}
        <div class="mt-4 text-center text-xs font-bold text-ink-3 dark:text-night-ink-2">
            @if ($resendTime > 0)
                <span x-show="seconds > 0" class="fa-digits">ارسال مجدد کد تا <b x-text="Math.ceil(seconds)"></b> ثانیه دیگر</span>
            @endif
            <button type="button" x-show="seconds <= 0" x-cloak
                    x-on:click="
                        var fd = new FormData();
                        fd.append('mobile', '{{ $seller['mobile'] }}');
                        var data = await spPost('{{ route('seller.get-new-code-for-seller') }}', fd);
                        if (data.status === 'success') { spToast('کد جدید پیامک شد', 'success'); seconds = 120; tick(); }
                        else if (data.status === 'errorTime') { spToast('کمی صبر کنید و دوباره تلاش کنید', 'error'); }
                        else if (data.redirect) { window.location.href = data.redirect; }
                        else { spToast(data.message || 'ارسال مجدد ناموفق بود', 'error'); }
                    "
                    class="text-brand-600 transition-colors hover:underline dark:text-brand-400">
                ارسال مجدد کد
            </button>
        </div>

        <p class="mt-5 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
            <a href="{{ route('seller.registration') }}" wire:navigate class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">ویرایش شمارهٔ موبایل</a>
        </p>
    </div>
</div>

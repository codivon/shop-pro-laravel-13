{{-- چالش ورود دومرحله‌ای مدیر (فاز ۴۴) — در هر دو پنل استفاده می‌شود --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- ستون فرم --}}
    <div class="relative flex items-center justify-center px-4 py-12 sm:px-8">
        {{-- دکمه تم --}}
        <button type="button" x-data x-on:click="ApTheme.toggle()"
                class="absolute end-5 top-5 rounded-xl border border-line bg-surface p-2.5 text-ink-2 transition-all hover:text-ink hover:shadow-card dark:border-night-line dark:bg-night-2 dark:text-night-ink-2 dark:hover:text-night-ink"
                aria-label="تغییر تم روشن/تاریک">
            <span class="hidden dark:block"><x-admin-panel::icon name="sun" class="size-5" /></span>
            <span class="dark:hidden"><x-admin-panel::icon name="moon" class="size-5" /></span>
        </button>

        <div class="w-full max-w-sm animate-fade-up"
             x-data="{
                expiresIn: {{ $challenge['expiresIn'] }},
                resendIn: {{ $challenge['resendIn'] }},
                lockedFor: {{ $challenge['lockedFor'] }},
                tick() {
                    if (this.expiresIn > 0) this.expiresIn--;
                    if (this.resendIn > 0) this.resendIn--;
                    if (this.lockedFor > 0) this.lockedFor--;
                },
                fmt(s) {
                    const m = Math.floor(s / 60), r = s % 60;
                    return (m > 0 ? m + ':' : '') + String(r).padStart(2, '0');
                }
             }"
             x-init="setInterval(() => tick(), 1000)">

            {{-- برند --}}
            <div class="mb-8 flex flex-col items-center text-center">
                <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-[0_10px_28px_-8px_rgb(5_150_105/.55)]">
                    <x-admin-panel::icon name="shield" class="size-7" />
                </span>
                <h1 class="text-xl font-extrabold">تأیید دومرحله‌ای</h1>
                <p class="mt-1.5 text-[13px] leading-6 text-ink-3 dark:text-night-ink-2">
                    هویت شما تأیید شد؛ کد ۶ رقمی یک‌بارمصرف برای شما ارسال شد.
                </p>
            </div>

            {{-- مقصد(های) کد --}}
            <div class="mb-5 space-y-2 rounded-xl border border-line bg-surface-2 p-3.5 text-[13px] dark:border-night-line dark:bg-night-3/60">
                @if (in_array($challenge['channel'], ['email', 'both']) && $challenge['email'])
                    <p class="flex items-center gap-2 text-ink-2 dark:text-night-ink-2">
                        <x-admin-panel::icon name="mail" class="size-4 shrink-0 text-brand-600 dark:text-brand-400" />
                        ارسال به ایمیل <span class="font-mono" dir="ltr">{{ $challenge['email'] }}</span>
                    </p>
                @endif
                @if (in_array($challenge['channel'], ['sms', 'both']) && $challenge['mobile'])
                    <p class="flex items-center gap-2 text-ink-2 dark:text-night-ink-2">
                        <x-admin-panel::icon name="smartphone" class="size-4 shrink-0 text-brand-600 dark:text-brand-400" />
                        ارسال به شماره <span class="font-mono" dir="ltr">{{ $challenge['mobile'] }}</span>
                    </p>
                @endif
                <p class="flex items-center gap-2 text-[12px] text-ink-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="clock" class="size-3.5 shrink-0" />
                    اعتبار کد: <span x-text="fmt(expiresIn)" class="font-mono">-</span>
                    · حداکثر ۵ تلاش در ۵ دقیقه
                </p>
            </div>

            <form wire:submit="verify" class="space-y-4">
                {{-- کد تأیید --}}
                <div>
                    <label for="code" class="mb-1.5 block text-[13px] font-semibold">کد تأیید ۶ رقمی</label>
                    <input id="code" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code"
                           autofocus maxlength="6" placeholder="------" dir="ltr"
                           class="ap-input h-12 text-center text-lg font-bold tracking-[0.5em] {{ $errors->has('code') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                    @error('code')
                        <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- دکمه تأیید --}}
                <button type="submit" class="ap-btn-primary w-full !py-3">
                    <span wire:loading.remove wire:target="verify">تأیید و ورود به پنل</span>
                    <span wire:loading wire:target="verify" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                        </svg>
                        در حال بررسی...
                    </span>
                </button>
            </form>

            {{-- ارسال مجدد + انصراف --}}
            <div class="mt-5 flex items-center justify-between gap-3">
                <button type="button" wire:click="resend" wire:loading.attr="disabled" wire:target="resend"
                        x-bind:disabled="resendIn > 0"
                        class="rounded-xl border border-line bg-surface px-4 py-2.5 text-[13px] font-bold text-ink-2 transition-colors hover:bg-surface-3 disabled:cursor-not-allowed disabled:opacity-50 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2 dark:hover:bg-night-3">
                    <x-admin-panel::icon name="send" class="me-1 inline size-4" />
                    <span x-show="resendIn > 0">ارسال مجدد تا <span x-text="fmt(resendIn)"></span> دیگر</span>
                    <span x-show="resendIn === 0" x-cloak>ارسال مجدد کد</span>
                </button>

                <button type="button" wire:click="cancel" wire:confirm="چالش لغو شود و به صفحه ورود برگردید؟"
                        class="text-[13px] font-medium text-ink-3 transition-colors hover:text-red-600 dark:text-night-ink-2 dark:hover:text-red-400">
                    انصراف و بازگشت
                </button>
            </div>
        </div>
    </div>

    {{-- ستون گرافیکی (دسکتاپ) --}}
    <div class="relative hidden overflow-hidden lg:block" aria-hidden="true">
        <div class="absolute inset-0 bg-linear-to-br from-brand-600 via-brand-700 to-[#0b3d2e]"></div>

        {{-- بافت هندسی --}}
        <svg class="absolute inset-0 size-full opacity-[.14]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="grid-2fa" width="42" height="42" patternUnits="userSpaceOnUse">
                    <path d="M42 0H0v42" fill="none" stroke="white" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid-2fa)"/>
        </svg>

        {{-- هاله‌های نور --}}
        <div class="absolute -start-24 top-1/4 size-80 rounded-full bg-brand-400/30 blur-3xl"></div>
        <div class="absolute -end-16 bottom-16 size-96 rounded-full bg-emerald-300/20 blur-3xl"></div>

        {{-- محتوا --}}
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div class="flex items-center gap-2.5">
                <span class="rounded-xl bg-white/12 px-3 py-1.5 text-xs font-bold backdrop-blur">لایهٔ امنیتی دوم</span>
            </div>

            <div class="max-w-md animate-fade-up">
                <h2 class="text-3xl font-extrabold leading-relaxed">
                    حساب شما،<br>
                    با یک قفل دیگر محفوظ است
                </h2>
                <p class="mt-4 text-[15px] leading-7 text-white/80">
                    ورود دومرحله‌ای حتی اگر رمز عبور افشا شود، جلوی دسترسی غیرمجاز به پنل مدیریت را می‌گیرد.
                </p>

                <div class="mt-8 grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <x-admin-panel::icon name="key-round" class="mb-2 size-5" />
                        <p class="text-[13px] font-bold">کد یک‌بارمصرف</p>
                        <p class="mt-0.5 text-[11px] text-white/70">۶ رقم — اعتبار ۵ دقیقه</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <x-admin-panel::icon name="lock" class="mb-2 size-5" />
                        <p class="text-[13px] font-bold">ضد حدس کد</p>
                        <p class="mt-0.5 text-[11px] text-white/70">۵ تلاش در ۵ دقیقه</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <x-admin-panel::icon name="refresh-cw" class="mb-2 size-5" />
                        <p class="text-[13px] font-bold">ارسال مجدد</p>
                        <p class="mt-0.5 text-[11px] text-white/70">هر ۱ دقیقه یک‌بار</p>
                    </div>
                </div>
            </div>

            <p class="text-xs text-white/60">© {{ date('Y') }} {{ option('info_site_title', config('app.name')) }}</p>
        </div>
    </div>
</div>

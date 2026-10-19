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

        <div class="w-full max-w-sm animate-fade-up">

            {{-- برند --}}
            <div class="mb-8 flex flex-col items-center text-center">
                <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-[0_10px_28px_-8px_rgb(5_150_105/.55)]">
                    <x-admin-panel::icon name="store" class="size-7" />
                </span>
                <h1 class="text-xl font-extrabold">ورود به پنل مدیریت</h1>
                <p class="mt-1.5 text-[13px] text-ink-3 dark:text-night-ink-2">
                    {{ option('info_site_title', config('app.name')) }} — برای ادامه وارد شوید
                </p>
            </div>

            <form wire:submit="authenticate" class="space-y-4">

                {{-- نام کاربری --}}
                <div>
                    <label for="username" class="mb-1.5 block text-[13px] font-semibold">نام کاربری یا ایمیل</label>
                    <div class="relative">
                        <x-admin-panel::icon name="user" class="pointer-events-none absolute inset-y-0 end-3.5 my-auto size-4.5 text-ink-3" />
                        <input id="username" type="text" wire:model="username" autofocus autocomplete="username"
                               placeholder="مثال: admin"
                               class="ap-input pe-10 {{ $errors->has('username') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                    </div>
                    @error('username')
                        <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- رمز عبور --}}
                <div>
                    <label for="password" class="mb-1.5 block text-[13px] font-semibold">رمز عبور</label>
                    <div class="relative">
                        <x-admin-panel::icon name="key-round" class="pointer-events-none absolute inset-y-0 end-3.5 my-auto size-4.5 text-ink-3" />
                        <input id="password" type="password" wire:model="password" autocomplete="current-password"
                               placeholder="••••••••"
                               class="ap-input pe-10 {{ $errors->has('password') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- مرا به خاطر بسپار --}}
                <label class="flex cursor-pointer items-center gap-2.5 select-none">
                    <input type="checkbox" wire:model="remember"
                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                    <span class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">مرا به خاطر بسپار</span>
                </label>

                {{-- ربات‌گیر: فیلد تله‌ی مخفی (برای انسان نامرئی و غیرقابل کلیک) --}}
                <div class="!h-0 !w-0 !m-0 !p-0 overflow-hidden opacity-0" aria-hidden="true" tabindex="-1">
                    <label for="website_url">وب‌سایت شما</label>
                    <input id="website_url" type="text" name="website_url" tabindex="-1" autocomplete="off"
                           wire:model="website_url">
                </div>

                {{-- کپچای امنیتی (ربات‌گیر) — در صورت فعال بودن --}}
                @if(config('security.login_captcha', true))
                <div>
                    <label for="captcha" class="mb-1.5 block text-[13px] font-semibold">کد امنیتی</label>
                    <div class="flex items-center gap-2.5">
                        <img src="{{ route('captcha.image') }}?v={{ $captchaVersion }}"
                             alt="کد امنیتی تصویری" width="120" height="44"
                             class="h-11 w-30 rounded-lg border border-line bg-surface object-cover select-none dark:border-night-line"
                             wire:loading.class="opacity-60">
                        <button type="button" wire:click="refreshCaptcha" wire:loading.attr="disabled"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-line bg-surface text-ink-2 transition-colors hover:text-brand-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2 dark:hover:text-brand-400"
                                title="کد جدید" aria-label="تازه‌سازی کد امنیتی">
                            <x-admin-panel::icon name="rotate-cw" class="size-4.5" />
                        </button>
                        <input id="captcha" type="text" wire:model="captcha" autocomplete="off"
                               placeholder="کد تصویر" maxlength="10"
                               class="ap-input h-11 flex-1 text-center tracking-[0.3em] {{ $errors->has('captcha') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                    </div>
                    @error('captcha')
                        <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                {{-- خطای اعتبارنامه --}}
                @if ($errors->has('username') && !str_contains($errors->first('username'), 'الزامی'))
                    <div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3 text-[13px] font-medium text-red-700 dark:border-red-500/25 dark:bg-red-950/40 dark:text-red-300">
                        <x-admin-panel::icon name="circle-alert" class="mt-0.5 size-4 shrink-0" />
                        <span>{{ $errors->first('username') }}</span>
                    </div>
                @endif

                {{-- دکمه ورود --}}
                <button type="submit" class="ap-btn-primary w-full !py-3">
                    <span wire:loading.remove wire:target="authenticate">ورود به پنل</span>
                    <span wire:loading wire:target="authenticate" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                        </svg>
                        در حال ورود...
                    </span>
                </button>
            </form>

            {{-- بازگشت به سایت --}}
            <div class="mt-8 text-center">
                <a href="{{ url('/') }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ink-3 transition-colors hover:text-brand-600 dark:text-night-ink-2 dark:hover:text-brand-400">
                    <x-admin-panel::icon name="arrow-left" class="size-4" />
                    بازگشت به فروشگاه
                </a>
            </div>
        </div>
    </div>

    {{-- ستون گرافیکی (دسکتاپ) --}}
    <div class="relative hidden overflow-hidden lg:block" aria-hidden="true">
        <div class="absolute inset-0 bg-linear-to-br from-brand-600 via-brand-700 to-[#0b3d2e]"></div>

        {{-- بافت هندسی --}}
        <svg class="absolute inset-0 size-full opacity-[.14]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="grid" width="42" height="42" patternUnits="userSpaceOnUse">
                    <path d="M42 0H0v42" fill="none" stroke="white" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)"/>
        </svg>

        {{-- هاله‌های نور --}}
        <div class="absolute -start-24 top-1/4 size-80 rounded-full bg-brand-400/30 blur-3xl"></div>
        <div class="absolute -end-16 bottom-16 size-96 rounded-full bg-emerald-300/20 blur-3xl"></div>

        {{-- محتوا --}}
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div class="flex items-center gap-2.5">
                <span class="rounded-xl bg-white/12 px-3 py-1.5 text-xs font-bold backdrop-blur">نسخه جدید</span>
                <span class="rounded-xl bg-white/12 px-3 py-1.5 text-xs font-bold backdrop-blur">Livewire 4</span>
                <span class="rounded-xl bg-white/12 px-3 py-1.5 text-xs font-bold backdrop-blur">SPA</span>
            </div>

            <div class="max-w-md animate-fade-up">
                <h2 class="text-3xl font-extrabold leading-relaxed">
                    مدیریت فروشگاه،<br>
                    سریع‌تر از همیشه
                </h2>
                <p class="mt-4 text-[15px] leading-7 text-white/80">
                    تجربه‌ای مدرن و برق‌آسا برای مدیریت محصولات، سفارشات، فروشندگان و مالی —
                    با طراحی جدید، تم روشن و تاریک و پیمایش فوری بین صفحات.
                </p>

                <div class="mt-8 grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <x-admin-panel::icon name="trending-up" class="mb-2 size-5" />
                        <p class="text-[13px] font-bold">سرعت بالا</p>
                        <p class="mt-0.5 text-[11px] text-white/70">پیمایش بدون رفرش</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <x-admin-panel::icon name="shield" class="mb-2 size-5" />
                        <p class="text-[13px] font-bold">دسترسی‌ها</p>
                        <p class="mt-0.5 text-[11px] text-white/70">سطح‌بندی کامل</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <x-admin-panel::icon name="sparkles" class="mb-2 size-5" />
                        <p class="text-[13px] font-bold">ظاهر مدرن</p>
                        <p class="mt-0.5 text-[11px] text-white/70">تم روشن و تاریک</p>
                    </div>
                </div>
            </div>

            <p class="text-xs text-white/60">© {{ date('Y') }} {{ option('info_site_title', config('app.name')) }}</p>
        </div>
    </div>
</div>

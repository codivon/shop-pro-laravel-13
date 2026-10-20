@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="grid min-h-screen lg:grid-cols-2" x-data="{
        prefix: {{ json_encode($admin_route_prefix) }},
        show1: false,
        show2: false,
        pw: '',
        pw2: '',
        get score() {
            let s = 0
            if (this.pw.length >= 8) s++
            if (this.pw.length >= 10 && /[A-Za-z]/.test(this.pw) && /\d/.test(this.pw)) s++
            if (this.pw.length >= 12 && /[^A-Za-z0-9]/.test(this.pw)) s++
            return s
        },
        get strengthTxt() {
            if (!this.pw) return ''
            return this.score <= 1 ? 'قدرت: ضعیف' : (this.score === 2 ? 'قدرت: متوسط' : 'قدرت: عالی')
        }
    }">

    {{-- ستون فرم --}}
    <div class="relative flex items-center justify-center px-4 py-12 sm:px-8">
        <div class="w-full max-w-md animate-fade-up">

            {{-- برند --}}
            <div class="mb-8 flex flex-col items-center text-center">
                <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-[0_10px_28px_-8px_rgb(5_150_105/.55)]">
                    <x-admin-panel::icon name="store" class="size-7" />
                </span>
                <h1 class="text-xl font-extrabold">ساخت حساب مدیر فروشگاه</h1>
                <p class="mt-1.5 text-[13px] text-ink-3 dark:text-night-ink-2">
                    {{ option('info_site_title', config('app.name')) }} — آخرین مرحله‌ی راه‌اندازی 🎉
                </p>
            </div>

            <form wire:submit="register" class="space-y-4" novalidate>

                {{-- نام و نام خانوادگی --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="first_name" class="mb-1.5 block text-[13px] font-semibold">نام</label>
                        <div class="relative">
                            <x-admin-panel::icon name="user" class="pointer-events-none absolute inset-y-0 end-3.5 my-auto size-4.5 text-ink-3" />
                            <input id="first_name" type="text" wire:model.blur="first_name" autocomplete="given-name"
                                   placeholder="مثلاً مهدی"
                                   class="ap-input pe-10 {{ $errors->has('first_name') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                        </div>
                        @error('first_name') <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="last_name" class="mb-1.5 block text-[13px] font-semibold">نام خانوادگی</label>
                        <div class="relative">
                            <x-admin-panel::icon name="user" class="pointer-events-none absolute inset-y-0 end-3.5 my-auto size-4.5 text-ink-3" />
                            <input id="last_name" type="text" wire:model.blur="last_name" autocomplete="family-name"
                                   placeholder="مثلاً محمدی"
                                   class="ap-input pe-10 {{ $errors->has('last_name') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                        </div>
                        @error('last_name') <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- نام کاربری --}}
                <div>
                    <label for="username" class="mb-1.5 block text-[13px] font-semibold">نام کاربری</label>
                    <div class="relative">
                        <x-admin-panel::icon name="users" class="pointer-events-none absolute inset-y-0 end-3.5 my-auto size-4.5 text-ink-3" />
                        <input id="username" type="text" wire:model.blur="username" dir="ltr" autocomplete="username"
                               placeholder="admin" class="ap-input pe-10 text-start {{ $errors->has('username') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                    </div>
                    @error('username') <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- آدرس اختصاصی پنل --}}
                <div>
                    <label for="admin_route_prefix" class="mb-1.5 block text-[13px] font-semibold">آدرس اختصاصی ورود به پنل</label>
                    <div class="rounded-xl border-[1.5px] border-dashed border-brand-500/60 bg-brand-50 p-3 dark:border-brand-500/40 dark:bg-brand-500/10">
                        <div class="flex items-center gap-2.5">
                            <x-admin-panel::icon name="lock" class="size-5 shrink-0 text-brand-600 dark:text-brand-400" />
                            <input id="admin_route_prefix" type="text" x-model="prefix" wire:model.blur="admin_route_prefix"
                                   dir="ltr" autocomplete="off" spellcheck="false"
                                   class="w-full border-0 bg-transparent p-0 font-mono text-[13.5px] font-bold text-brand-700 outline-none text-start dark:text-brand-300"
                                   style="direction:ltr">
                        </div>
                        <p class="mt-2 text-[11px] font-medium text-brand-700/80 dark:text-brand-300/80">
                            آدرس پنل شما: <strong dir="ltr" class="font-mono">{{ url('/') }}/<span x-text="prefix" dir="ltr"></span></strong>
                            — آن را جایی یادداشت کنید.
                        </p>
                    </div>
                    @error('admin_route_prefix') <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- گذرواژه‌ها --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="password" class="mb-1.5 block text-[13px] font-semibold">گذرواژه</label>
                        <div class="relative">
                            <x-admin-panel::icon name="key-round" class="pointer-events-none absolute inset-y-0 end-3.5 my-auto size-4.5 text-ink-3" />
                            <input id="password" x-model="pw" wire:model.blur="password"
                                   :type="show1 ? 'text' : 'password'" autocomplete="new-password"
                                   placeholder="حداقل ۸ کاراکتر"
                                   class="ap-input pe-16 {{ $errors->has('password') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                            <button type="button" x-on:click="show1 = !show1" aria-label="نمایش گذرواژه"
                                    class="absolute inset-y-0 start-2.5 my-auto flex size-8 items-center justify-center rounded-lg text-ink-3 transition-colors hover:text-brand-600 dark:hover:text-brand-400">
                                <span x-show="!show1"><x-admin-panel::icon name="eye" class="size-4" /></span>
                                <span x-show="show1" x-cloak><x-admin-panel::icon name="eye-off" class="size-4" /></span>
                            </button>
                        </div>
                        {{-- سنجش قدرت --}}
                        <div class="mt-1.5 flex gap-1" aria-hidden="true">
                            <i class="h-1 flex-1 rounded-full transition-colors duration-200"
                               :class="score >= 1 ? 'bg-red-400' : 'bg-line dark:bg-night-line'"></i>
                            <i class="h-1 flex-1 rounded-full transition-colors duration-200"
                               :class="score >= 2 ? 'bg-amber-400' : 'bg-line dark:bg-night-line'"></i>
                            <i class="h-1 flex-1 rounded-full transition-colors duration-200"
                               :class="score >= 3 ? 'bg-brand-500' : 'bg-line dark:bg-night-line'"></i>
                        </div>
                        <p class="mt-1 min-h-4 text-[11px] text-ink-3 dark:text-night-ink-2" x-text="strengthTxt"></p>
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-[13px] font-semibold">تکرار گذرواژه</label>
                        <div class="relative">
                            <x-admin-panel::icon name="key-round" class="pointer-events-none absolute inset-y-0 end-3.5 my-auto size-4.5 text-ink-3" />
                            <input id="password_confirmation" x-model="pw2" wire:model.blur="password_confirmation"
                                   :type="show2 ? 'text' : 'password'" autocomplete="new-password"
                                   placeholder="تکرار گذرواژه"
                                   class="ap-input pe-16 {{ $errors->has('password_confirmation') ? '!border-red-400 focus:!ring-red-400/15' : '' }}">
                            <button type="button" x-on:click="show2 = !show2" aria-label="نمایش گذرواژه"
                                    class="absolute inset-y-0 start-2.5 my-auto flex size-8 items-center justify-center rounded-lg text-ink-3 transition-colors hover:text-brand-600 dark:hover:text-brand-400">
                                <span x-show="!show2"><x-admin-panel::icon name="eye" class="size-4" /></span>
                                <span x-show="show2" x-cloak><x-admin-panel::icon name="eye-off" class="size-4" /></span>
                            </button>
                        </div>
                        <p class="mt-1 min-h-4 text-[11px] font-medium"
                           x-show="pw2" x-text="pw2 && pw === pw2 ? '✓ گذرواژه‌ها یکسان هستند' : '✗ گذرواژه‌ها یکسان نیستند'"
                           :class="pw2 && pw === pw2 ? 'text-brand-600 dark:text-brand-400' : 'text-red-600 dark:text-red-400'"></p>
                        @error('password_confirmation') <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- اطلاع یک‌بار بودن --}}
                <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[12.5px] font-medium text-amber-800 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-300">
                    <x-admin-panel::icon name="circle-alert" class="mt-0.5 size-4 shrink-0" />
                    <span>این فرم فقط یک‌بار و فقط برای ساخت اولین مدیر نمایش داده می‌شود. با همین اطلاعات از این پس وارد پنل می‌شوید؛ گذرواژه را جایی امن نگه دارید.</span>
                </div>

                {{-- دکمه ثبت‌نام --}}
                <button type="submit" class="ap-btn-primary w-full !py-3">
                    <span wire:loading.remove wire:target="register" class="flex items-center justify-center gap-2">
                        <x-admin-panel::icon name="check" class="size-4.5" />
                        ثبت‌نام و ورود به پنل مدیریت
                    </span>
                    <span wire:loading wire:target="register" class="flex items-center justify-center gap-2">
                        <x-admin-panel::icon name="loader-circle" class="size-4.5 animate-spin" />
                        در حال راه‌اندازی فروشگاه...
                    </span>
                </button>
            </form>

            {{-- بازگشت به سایت --}}
            <div class="mt-8 text-center">
                <a href="{{ url('/') }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ink-3 transition-colors hover:text-brand-600 dark:text-night-ink-2 dark:hover:text-brand-400">
                    <x-admin-panel::icon name="arrow-left" class="size-4" />
                    پس از ثبت‌نام، به فروشگاه هم می‌توانید سر بزنید
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
                <pattern id="grid-reg" width="42" height="42" patternUnits="userSpaceOnUse">
                    <path d="M42 0H0v42" fill="none" stroke="white" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid-reg)"/>
        </svg>

        {{-- هاله‌های نور --}}
        <div class="absolute -start-24 top-1/4 size-80 rounded-full bg-brand-400/30 blur-3xl"></div>
        <div class="absolute -end-16 bottom-16 size-96 rounded-full bg-emerald-300/20 blur-3xl"></div>

        {{-- محتوا --}}
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div class="flex items-center gap-2.5">
                <span class="rounded-xl bg-white/12 px-3 py-1.5 text-xs font-bold backdrop-blur">آخرین مرحله‌ی نصب</span>
                <span class="rounded-xl bg-white/12 px-3 py-1.5 text-xs font-bold backdrop-blur">Livewire 4</span>
                <span class="rounded-xl bg-white/12 px-3 py-1.5 text-xs font-bold backdrop-blur">SPA</span>
            </div>

            <div class="max-w-md animate-fade-up">
                <h2 class="text-3xl font-extrabold leading-relaxed">
                    فروشگاه شما<br>
                    آماده‌ی شروع است
                </h2>
                <p class="mt-4 text-[15px] leading-7 text-white/80">
                    با ساخت حساب مدیر کل، نصب رسمی تمام می‌شود؛ استایل فروشگاه در public ساخته می‌شود،
                    آدرس اختصاصی پنل ثبت می‌گردد و مستقیم به داشبورد وارد می‌شوید.
                </p>

                <div class="mt-8 flex flex-col gap-3.5">
                    <div class="flex items-center gap-3.5 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/15 text-sm font-extrabold">۱</span>
                        <div>
                            <p class="text-[13.5px] font-bold">ساخت حساب مدیر کل</p>
                            <p class="mt-0.5 text-[11.5px] text-white/70">نام کاربری و گذرواژه‌ی اختصاصی خودتان</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3.5 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/15 text-sm font-extrabold">۲</span>
                        <div>
                            <p class="text-[13.5px] font-bold">انتخاب آدرس اختصاصی پنل</p>
                            <p class="mt-0.5 text-[11.5px] text-white/70">مسیر ورود امن که فقط شما می‌دانید</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3.5 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/15 text-sm font-extrabold">۳</span>
                        <div>
                            <p class="text-[13.5px] font-bold">ورود مستقیم به پنل و شروع فروش</p>
                            <p class="mt-0.5 text-[11.5px] text-white/70">راه‌اندازی کامل استایل فرنت به‌صورت خودکار</p>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-white/60">© {{ date('Y') }} {{ option('info_site_title', config('app.name')) }}</p>
        </div>
    </div>
</div>

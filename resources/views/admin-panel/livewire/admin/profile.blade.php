{{-- پروفایل مدیر — فاز ۲۱ (پورت admin/profile پنل قدیمی) --}}
<div class="mx-auto max-w-5xl space-y-5">

    {{-- هدر --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="user" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">پروفایل من</h2>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">ویرایش اطلاعات حساب، شبکه‌های اجتماعی و رمز عبور</p>
            </div>
        </div>
        <a href="{{ route('np.dashboard') }}" wire:navigate class="ap-btn-ghost !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت
        </a>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- کارت تصویر و وضعیت --}}
        <div class="ap-card self-start p-5 text-center lg:sticky lg:top-24">
            <div class="relative mx-auto size-28">
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" alt="پیش‌نمایش تصویر جدید" class="size-28 rounded-2xl object-cover ring-2 ring-brand-500/30">
                @else
                    <img src="{{ $admin->imageUrl() }}" alt="تصویر پروفایل" class="size-28 rounded-2xl object-cover ring-2 ring-line">
                @endif
                <label class="absolute -bottom-2 -start-2 cursor-pointer rounded-xl border border-line bg-surface p-2 text-brand-600 shadow-card transition-colors hover:bg-brand-50 dark:border-night-line dark:bg-night-3 dark:text-brand-400 dark:hover:bg-brand-500/10"
                       title="تغییر تصویر" aria-label="انتخاب تصویر پروفایل">
                    <x-admin-panel::icon name="camera" class="size-4" />
                    <input type="file" wire:model="image" accept="image/*" class="hidden">
                </label>
            </div>
            <h3 class="mt-4 truncate text-sm font-extrabold">{{ $admin->fullname }}</h3>
            <p class="mt-0.5 truncate text-xs text-ink-3 dark:text-night-ink-2" dir="ltr">{{ '@' . ($admin->username ?? '') }}</p>

            <div class="mt-3 flex justify-center">
                <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                    سطح دسترسی: {{ $admin->level === 'creator' ? 'سازنده' : ($admin->level === 'admin' ? 'مدیر کل' : $admin->level) }}
                </span>
            </div>

            @error('image') <p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

            @if ($image)
                <button type="button" wire:click="$set('image', null)" class="mt-3 flex items-center gap-1 text-xs font-semibold text-red-500 hover:underline">
                    <x-admin-panel::icon name="x" class="size-3.5" />
                    حذف تصویر انتخابی
                </button>
            @endif

            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary mt-5 w-full !py-2.5">
                <x-admin-panel::icon name="save" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>

        {{-- ستون اصلی --}}
        <div class="space-y-5 lg:col-span-2">

            {{-- اطلاعات حساب --}}
            <div class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="id-card" class="size-4" />
                    </span>
                    اطلاعات حساب
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">نام <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="first_name" class="ap-input">
                        @error('first_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">نام خانوادگی <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="last_name" class="ap-input">
                        @error('last_name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">نام کاربری <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="username" dir="ltr" class="ap-input text-start">
                        @error('username') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">ایمیل</label>
                        <input type="email" wire:model="email" dir="ltr" class="ap-input text-start">
                        @error('email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">شماره همراه</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-ink-3"><x-admin-panel::icon name="smartphone" class="size-4" /></span>
                            <input type="tel" wire:model="mobile" dir="ltr" class="ap-input ps-10 text-start" placeholder="09123456789">
                        </div>
                        <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">مستقل از نام کاربری — با این شماره هم می‌توانید وارد شوید و کد ورود دومرحله‌ای به آن ارسال می‌شود</p>
                        @error('mobile') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-[13px] font-semibold">بیوگرافی</label>
                        <textarea wire:model="bio" rows="3" class="ap-input" placeholder="معرفی کوتاه…"></textarea>
                        @error('bio') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- فاز ۷۲ — ورود دومرحله‌ای برای خودِ مدیر --}}
            <div class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-500/12 dark:text-green-400">
                        <x-admin-panel::icon name="key-round" class="size-4" />
                    </span>
                    ورود دومرحله‌ای (2FA)
                </h3>

                @if (! \App\Services\SecuritySettings::twoFactorEnabled())
                    <div class="flex items-start gap-2.5 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                        <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0 text-ink-3" />
                        <p class="text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                            ورود دومرحله‌ای در تنظیمات برنامه‌نویس برای کل سایت خاموش است؛ این کلید سراسری را سازنده از بخش «تنظیمات برنامه‌نویس ← امنیت» می‌تواند فعال کند.
                        </p>
                    </div>
                @else
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3.5 transition-colors {{ $twoFactorDisabled ? 'border-line bg-surface-2 dark:border-night-line dark:bg-night-3' : 'border-green-200 bg-gradient-to-l from-green-50/90 via-green-50/40 to-transparent dark:border-green-500/20 dark:from-green-500/10 dark:via-green-500/5 dark:to-transparent' }}">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $twoFactorDisabled ? 'bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2' : 'bg-green-600/10 text-green-600 dark:bg-green-500/15 dark:text-green-400' }}">
                                <x-admin-panel::icon name="shield-check" class="size-4.5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[13px] font-bold">ورود دومرحله‌ای برای حساب من</span>
                                <span class="block text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                                    {{ $twoFactorDisabled ? 'برای حساب شما خاموش است — ورود فقط با رمز عبور انجام می‌شود.' : 'پس از ورود رمز، کد ۶ رقمی به ایمیل یا شماره همراه شما ارسال می‌شود.' }}
                                </span>
                            </span>
                        </span>
                        <button type="button"
                                wire:click="toggleTwoFactor({{ $twoFactorDisabled ? 'false' : 'true' }})"
                                wire:loading.attr="disabled" wire:target="toggleTwoFactor"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-300 {{ $twoFactorDisabled ? 'bg-line dark:bg-night-line' : 'bg-green-600' }}"
                                role="switch" aria-checked="{{ $twoFactorDisabled ? 'true' : 'false' }}"
                                aria-label="ورود دومرحله ای برای حساب من">
                            <span class="inline-block size-5 translate-x-0.5 rounded-full bg-white shadow transition-transform duration-300 rtl:translate-x-[-2px] {{ $twoFactorDisabled ? 'translate-x-0.5 rtl:-translate-x-0.5' : 'translate-x-[22px] rtl:-translate-x-[22px]' }}"></span>
                        </button>
                    </div>
                    <p class="mt-3 flex items-start gap-1.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="info" class="mt-0.5 size-3.5 shrink-0" />
                        <span>کلید فعال‌سازی سراسری در تنظیمات برنامه‌نویس است؛ با این کلید فقط برای حساب خودتان تصمیم می‌گیرید.</span>
                    </p>
                @endif
            </div>

            {{-- شبکه‌های اجتماعی --}}
            <div class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                        <x-admin-panel::icon name="share-2" class="size-4" />
                    </span>
                    شبکه‌های اجتماعی
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">اینستاگرام</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-ink-3"><x-admin-panel::icon name="instagram" class="size-4" /></span>
                            <input type="text" wire:model="instagram" dir="ltr" class="ap-input ps-10 text-start" placeholder="instagram.com/username">
                        </div>
                        @error('instagram') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">تلگرام</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-ink-3"><x-admin-panel::icon name="send" class="size-4" /></span>
                            <input type="text" wire:model="telegram" dir="ltr" class="ap-input ps-10 text-start" placeholder="username">
                        </div>
                        @error('telegram') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">واتساپ</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-ink-3"><x-admin-panel::icon name="message-circle" class="size-4" /></span>
                            <input type="text" wire:model="whatsapp" dir="ltr" class="ap-input ps-10 text-start" placeholder="+989123456789">
                        </div>
                        @error('whatsapp') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">توییتر / ایکس</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-ink-3"><x-admin-panel::icon name="twitter" class="size-4" /></span>
                            <input type="text" wire:model="twitter" dir="ltr" class="ap-input ps-10 text-start" placeholder="twitter.com/username">
                        </div>
                        @error('twitter') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">فیسبوک</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-ink-3"><x-admin-panel::icon name="facebook" class="size-4" /></span>
                            <input type="text" wire:model="facebook" dir="ltr" class="ap-input ps-10 text-start" placeholder="facebook.com/username">
                        </div>
                        @error('facebook') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">ایتا</label>
                        <input type="text" wire:model="eitaa" dir="ltr" class="ap-input text-start" placeholder="username">
                        @error('eitaa') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">روبیکا</label>
                        <input type="text" wire:model="rubika" dir="ltr" class="ap-input text-start" placeholder="username">
                        @error('rubika') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">بله</label>
                        <input type="text" wire:model="bale" dir="ltr" class="ap-input text-start" placeholder="username">
                        @error('bale') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- تغییر رمز عبور --}}
            <div class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                        <x-admin-panel::icon name="lock" class="size-4" />
                    </span>
                    تغییر رمز عبور
                    <span class="text-[11px] font-normal text-ink-3">(برای عدم تغییر خالی بگذارید)</span>
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">رمز عبور جدید</label>
                        <input type="password" wire:model="password" dir="ltr" class="ap-input text-start" autocomplete="new-password" placeholder="••••••••">
                        @error('password') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">تکرار رمز عبور جدید</label>
                        <input type="password" wire:model="password_confirmation" dir="ltr" class="ap-input text-start" autocomplete="new-password" placeholder="••••••••">
                        @error('password_confirmation') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ذخیره (موبایل) --}}
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="ap-btn-primary w-full !py-3 lg:hidden">
                <x-admin-panel::icon name="save" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>

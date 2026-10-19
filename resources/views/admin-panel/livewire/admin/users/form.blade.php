{{-- فرم ایجاد/ویرایش کاربر — پنل جدید (بازطراحی فاز ۱۲) — منطق و wire properties پورت ۱:۱ حفظ شده --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
    $heroName = $isEditing ? (trim((string) $user->first_name . ' ' . (string) $user->last_name) ?: $user->username) : null;
@endphp
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ صفحه --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white shadow-card">
                <x-admin-panel::icon name="{{ $isEditing ? 'user-cog' : 'users' }}" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-extrabold sm:text-lg">
                    {{ $isEditing ? 'ویرایش کاربر: ' . ($heroName ?: 'بدون نام') : 'کاربر جدید' }}
                </h1>
                @if ($isEditing)
                    <p class="mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                        شناسه: <span class="fa-digits font-semibold">{{ number_format($user->id) }}</span>
                        — نام کاربری: <span class="fa-digits font-semibold" dir="ltr">{{ $user->username }}</span>
                    </p>
                @else
                    <p class="mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">اطلاعات کاربر تازه را تکمیل و ذخیره کنید</p>
                @endif
            </div>
            {{-- بج وضعیت تایید شماره --}}
            @if ($isEditing && $user->verified_at)
                <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="check" class="size-3" />
                    شماره تایید شده
                </span>
            @else
                <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="clock" class="size-3" />
                    {{ $isEditing ? 'شماره تایید نشده' : 'در انتظار تایید' }}
                </span>
            @endif
        </div>
        <a href="{{ route('np.users.index') }}" wire:navigate class="ap-btn-ghost !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            لیست کاربران
        </a>
    </div>

    <form wire:submit.prevent="save" class="space-y-5">

        {{-- ================= کارت پروفایل (هیرو) ================= --}}
        <div class="ap-card overflow-hidden">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-4 border-b border-line bg-linear-to-l from-brand-50/80 via-surface to-surface p-4 dark:border-night-line dark:from-brand-500/10 dark:via-night-2 dark:to-night-2 sm:p-5">
                {{-- آواتار — انتخاب تصویر (label محصور؛ فقط کلیک روی همین ناحیه پنجره فایل را باز می‌کند) --}}
                <label class="group relative block size-24 shrink-0 cursor-pointer overflow-hidden rounded-full border-2 border-dashed border-line bg-surface transition-colors hover:border-brand-400 dark:border-night-line dark:bg-night-2"
                       title="انتخاب تصویر پروفایل">
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" alt="پیش‌نمایش تصویر انتخابی کاربر" class="absolute inset-0 size-full object-cover">
                    @elseif ($isEditing && $user->image)
                        <img src="{{ $user->imageUrl() }}" alt="تصویر فعلی کاربر" class="absolute inset-0 size-full object-cover">
                    @else
                        <span class="absolute inset-0 flex flex-col items-center justify-center gap-1 text-ink-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="image-plus" class="size-6 transition-transform group-hover:scale-110" />
                            <span class="text-[10px] font-semibold">انتخاب عکس</span>
                        </span>
                    @endif
                    <span class="absolute inset-x-0 bottom-0 hidden justify-center bg-slate-950/55 py-1 text-[10px] font-semibold text-white group-hover:flex" aria-hidden="true">
                        تغییر عکس
                    </span>
                    <span wire:loading wire:target="image" class="absolute inset-0 flex items-center justify-center bg-surface/70 backdrop-blur-sm dark:bg-night-2/70">
                        <svg class="size-5 animate-spin text-brand-600 dark:text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                        </svg>
                    </span>
                    <input type="file" wire:model="image" accept="image/*" class="absolute inset-0 size-full cursor-pointer opacity-0">
                </label>

                {{-- هویت کاربر --}}
                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-extrabold">
                        @if ($isEditing) {{ $heroName ?: 'بدون نام' }} @else تصویر و هویت کاربر @endif
                    </p>
                    <p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[12px] text-ink-2 dark:text-night-ink-2">
                        <span class="flex items-center gap-1.5">
                            <x-admin-panel::icon name="phone" class="size-3.5 text-ink-3 dark:text-night-ink-2" />
                            <span class="fa-digits" dir="ltr">{{ $isEditing ? $user->username : '—' }}</span>
                        </span>
                        @if ($isEditing && $user->email)
                            <span class="flex items-center gap-1.5">
                                <x-admin-panel::icon name="mail" class="size-3.5 text-ink-3 dark:text-night-ink-2" />
                                <span dir="ltr">{{ $user->email }}</span>
                            </span>
                        @endif
                    </p>
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">فرمت تصویر — حداکثر ۲ مگابایت</p>
                    @error('image') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    @if ($image)
                        <button type="button" wire:click="$set('image', null)" class="mt-1.5 flex items-center gap-1 text-[11px] font-semibold text-red-500 hover:underline">
                            <x-admin-panel::icon name="x" class="size-3" />
                            حذف فایل انتخاب‌شده
                        </button>
                    @endif
                </div>

                {{-- سوئیچ تایید شماره همراه --}}
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-surface p-3 transition-colors hover:border-brand-300 dark:border-night-line dark:bg-night-2 dark:hover:border-brand-500/50">
                    <input type="checkbox" wire:model="verified" class="peer sr-only">
                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-surface-3 transition-colors duration-200 after:absolute after:top-0.5 after:start-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-brand-600 peer-checked:after:translate-x-[-1.25rem] peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40 dark:bg-night-3 dark:after:bg-night-ink dark:peer-checked:bg-brand-500"
                          aria-hidden="true"></span>
                    <span class="min-w-0">
                        <span class="block text-[13px] font-bold">تایید شماره همراه</span>
                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">وضعیت تایید شماره کاربر</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- ================= اطلاعات حساب ================= --}}
        <div class="ap-card p-5 sm:p-6">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="user-cog" class="size-4" />
                </span>
                اطلاعات حساب
            </h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">شماره همراه (نام کاربری) <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="username" class="ap-input fa-digits" dir="ltr" autocomplete="off" inputmode="tel">
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">نام کاربری همان شماره همراه است؛ ورود کاربر با این شماره انجام می‌شود.</p>
                    @error('username') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">آدرس ایمیل</label>
                    <input type="email" wire:model="email" class="ap-input" dir="ltr" autocomplete="off">
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">اختیاری — برای بازیابی گذرواژه و اطلاع‌رسانی استفاده می‌شود.</p>
                    @error('email') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ================= اطلاعات شخصی ================= --}}
        <div class="ap-card p-5 sm:p-6">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="user" class="size-4" />
                </span>
                اطلاعات شخصی
            </h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">نام <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="first_name" class="ap-input" autocomplete="given-name">
                    @error('first_name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">نام خانوادگی <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="last_name" class="ap-input" autocomplete="family-name">
                    @error('last_name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">کد ملی</label>
                    <input type="text" wire:model="national_code" maxlength="10" class="ap-input fa-digits" dir="ltr" inputmode="numeric">
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">۱۰ رقم و بدون خط تیره</p>
                    @error('national_code') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">شماره کارت</label>
                    <input type="text" wire:model="card_number" class="ap-input fa-digits" dir="ltr" inputmode="numeric">
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">برای تسویه‌های فروشندگی و بازگشت وجه</p>
                    @error('card_number') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- تاریخ تولد — سه سلکت مثل فرم قدیمی --}}
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[13px] font-semibold">تاریخ تولد</label>
                    <div class="grid grid-cols-3 gap-3">
                        <select wire:model="birthDay" class="ap-input fa-digits" aria-label="روز تولد">
                            <option value="">روز</option>
                            @foreach (range(1, 31) as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                        <select wire:model="birthMonth" class="ap-input" aria-label="ماه تولد">
                            <option value="">ماه</option>
                            @foreach ($this->monthOptions as $monthValue => $monthName)
                                <option value="{{ $monthValue }}">{{ $monthName }}</option>
                            @endforeach
                        </select>
                        <select wire:model="birthYear" class="ap-input fa-digits" aria-label="سال تولد">
                            <option value="">سال</option>
                            @foreach ($this->yearOptions as $yearValue)
                                <option value="{{ $yearValue }}">{{ $yearValue }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">در صورت انتخاب هر سه گزینه تاریخ تولد ذخیره می‌شود.</p>
                </div>
            </div>
        </div>

        {{-- ================= امنیت و دسترسی ================= --}}
        <div class="ap-card p-5 sm:p-6">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="shield" class="size-4" />
                </span>
                امنیت و دسترسی
            </h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2" x-data="{ s: 0, pw: '', pwc: '' }">
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">
                        گذرواژه @if ($isEditing) <span class="text-[11px] font-medium text-ink-3">(برای تغییر ندادن، خالی بگذارید)</span> @else <span class="text-red-500">*</span> @endif
                    </label>
                    <input type="password" wire:model="password" class="ap-input" dir="ltr" autocomplete="new-password"
                           x-on:input="pw = $el.value; s = pw === '' ? 0 : Math.min(4, (pw.length >= 6 ? 1 : 0) + (/[A-Za-z]/.test(pw) ? 1 : 0) + (/[0-9]/.test(pw) ? 1 : 0) + (/[^A-Za-z0-9]/.test(pw) ? 1 : 0))">
                    @error('password') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

                    {{-- نشانگر قدرت گذرواژه (سبک و بدون اسکریپت سنگین) --}}
                    <div class="mt-2 flex items-center gap-2" x-show="s > 0" x-cloak>
                        <div class="flex flex-1 gap-1" aria-hidden="true">
                            <span class="h-1 flex-1 rounded-full transition-colors" :class="s >= 1 ? 'bg-red-400' : 'bg-surface-3 dark:bg-night-3'"></span>
                            <span class="h-1 flex-1 rounded-full transition-colors" :class="s >= 2 ? 'bg-amber-400' : 'bg-surface-3 dark:bg-night-3'"></span>
                            <span class="h-1 flex-1 rounded-full transition-colors" :class="s >= 3 ? 'bg-brand-400' : 'bg-surface-3 dark:bg-night-3'"></span>
                            <span class="h-1 flex-1 rounded-full transition-colors" :class="s >= 4 ? 'bg-brand-600' : 'bg-surface-3 dark:bg-night-3'"></span>
                        </div>
                        <span class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2" x-text="['', 'ضعیف', 'متوسط', 'خوب', 'قوی'][s]"></span>
                    </div>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">حداقل ۶ کاراکتر؛ ترکیب حرف، عدد و نماد گذرواژه را قوی‌تر می‌کند.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">تکرار گذرواژه</label>
                    <input type="password" wire:model="password_confirmation" class="ap-input" dir="ltr" autocomplete="new-password"
                           x-on:input="pwc = $el.value">
                    @error('password_confirmation') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-2 flex items-center gap-1 text-[11px] font-medium text-brand-600 dark:text-brand-400" x-show="pwc !== '' && pw !== '' && pw === pwc" x-cloak>
                        <x-admin-panel::icon name="check" class="size-3.5" />
                        گذرواژه‌ها یکسان‌اند
                    </p>
                    @if ($isEditing)
                        <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">با تغییر گذرواژه، همه نشست‌های فعال این کاربر باطل می‌شود.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ================= فیلدهای اختصاصی (پورت فیلدهای داینامیک قدیمی) ================= --}}
        @if (count($this->fildsList))
            <div class="ap-card p-5 sm:p-6">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="list" class="size-4" />
                    </span>
                    فیلدهای اختصاصی کاربر
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($this->fildsList as $fild)
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">
                                {{ $fild->title }} @if ($fild->required) <span class="text-red-500">*</span> @endif
                            </label>

                            @if ($fild->type == 'input')
                                <input type="text" wire:model="filds.{{ $fild->id }}" class="ap-input">
                            @elseif ($fild->type == 'textarea')
                                <textarea wire:model="filds.{{ $fild->id }}" rows="3" class="ap-input"></textarea>
                            @elseif ($fild->type == 'number')
                                <input type="number" wire:model="filds.{{ $fild->id }}" class="ap-input fa-digits" dir="ltr">
                            @elseif ($fild->type == 'email')
                                <input type="email" wire:model="filds.{{ $fild->id }}" class="ap-input" dir="ltr">
                            @elseif ($fild->type == 'colorPicker')
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model="filds.{{ $fild->id }}" class="size-9 shrink-0 cursor-pointer rounded-lg border border-line bg-transparent p-0.5 dark:border-night-line">
                                    <span class="fa-digits text-xs text-ink-3" dir="ltr">{{ $this->filds[$fild->id] ?? '' ?: '—' }}</span>
                                </div>
                            @elseif ($fild->type == 'checkbox')
                                <label class="flex cursor-pointer items-center gap-2.5 text-[13px]">
                                    <input type="checkbox" wire:model="filds.{{ $fild->id }}"
                                           class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                                    {{ $fild->title }}
                                </label>
                            @elseif ($fild->type == 'select')
                                @php $selectOptions = array_filter(array_map('trim', explode(',', (string) $fild->select_options))); @endphp
                                <select wire:model="filds.{{ $fild->id }}" class="ap-input">
                                    @if (! $fild->required)
                                        <option value="">—</option>
                                    @endif
                                    @foreach ($selectOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            @endif

                            @error('filds.' . $fild->id) <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- نوار ذخیره چسبان --}}
        <div class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-pop backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <p class="hidden items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2 sm:flex">
                <span class="text-red-500">*</span>
                فیلدهای ستاره‌دار الزامی هستند
            </p>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('np.users.index') }}" wire:navigate class="ap-btn-ghost">انصراف</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="ap-btn-primary min-w-36">
                    <span wire:loading.remove wire:target="save">{{ $isEditing ? 'ذخیره تغییرات' : 'ایجاد کاربر' }}</span>
                    <span wire:loading wire:target="save" class="flex items-center justify-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                        </svg>
                        در حال ذخیره…
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

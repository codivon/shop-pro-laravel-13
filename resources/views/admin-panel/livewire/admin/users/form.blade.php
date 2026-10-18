{{-- فرم ایجاد/ویرایش کاربر — پنل جدید (فاز ۴) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-black">{{ $isEditing ? 'ویرایش کاربر' : 'ایجاد کاربر جدید' }}</h1>
            @if ($isEditing)
                <p class="mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">
                    نام کاربری: <span class="fa-digits font-semibold">{{ $user->username }}</span>
                </p>
            @endif
        </div>
        <a href="{{ route('np.users.index') }}" wire:navigate class="ap-btn-outline !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به لیست
        </a>
    </div>

    <form wire:submit.prevent="save" class="space-y-5">

        {{-- اطلاعات پایه --}}
        <div class="ap-card p-5 sm:p-6">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="user" class="size-4.5 text-brand-600 dark:text-brand-400" />
                اطلاعات پایه
            </h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">نام <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="first_name" class="ap-input">
                    @error('first_name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">نام خانوادگی <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="last_name" class="ap-input">
                    @error('last_name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">شماره همراه (نام کاربری) <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="username" class="ap-input fa-digits" dir="ltr">
                    @error('username') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">آدرس ایمیل</label>
                    <input type="email" wire:model="email" class="ap-input" dir="ltr">
                    @error('email') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">کد ملی</label>
                    <input type="text" wire:model="national_code" maxlength="10" class="ap-input fa-digits" dir="ltr">
                    @error('national_code') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">شماره کارت</label>
                    <input type="text" wire:model="card_number" class="ap-input fa-digits" dir="ltr">
                    @error('card_number') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- تاریخ تولد — سه سلکت مثل فرم قدیمی --}}
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-[13px] font-semibold">تاریخ تولد</label>
                    <div class="grid grid-cols-3 gap-3">
                        <select wire:model="birthDay" class="ap-input">
                            <option value="">روز</option>
                            @foreach (range(1, 31) as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                        <select wire:model="birthMonth" class="ap-input">
                            <option value="">ماه</option>
                            @foreach ($this->monthOptions as $monthValue => $monthName)
                                <option value="{{ $monthValue }}">{{ $monthName }}</option>
                            @endforeach
                        </select>
                        <select wire:model="birthYear" class="ap-input fa-digits">
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

        {{-- تصویر و امنیت --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="ap-card p-5 sm:p-6">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="image" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    تصویر کاربر
                </h2>
                <div class="group relative mx-auto size-28 cursor-pointer overflow-hidden rounded-2xl border border-line bg-surface-2 dark:border-night-line dark:bg-night-3">
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" alt="پیش‌نمایش تصویر کاربر" class="absolute inset-0 size-full object-cover">
                    @elseif ($isEditing && $user->image)
                        <img src="{{ $user->imageUrl() }}" alt="تصویر فعلی کاربر" class="absolute inset-0 size-full object-cover">
                    @else
                        <span class="absolute inset-0 flex items-center justify-center text-ink-3">
                            <x-admin-panel::icon name="image-plus" class="size-8 transition-transform group-hover:scale-110" />
                        </span>
                    @endif
                    <input type="file" wire:model="image" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0">
                </div>
                @error('image') <p class="mt-2 text-center text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                @if ($image)
                    <button type="button" wire:click="$set('image', null)" class="mx-auto mt-2 flex items-center gap-1 text-xs font-semibold text-red-500 hover:underline">
                        <x-admin-panel::icon name="x" class="size-3.5" />
                        حذف فایل انتخاب‌شده
                    </button>
                @endif
                <p class="mt-2 text-center text-[11px] text-ink-3 dark:text-night-ink-2">فرمت تصویر — حداکثر ۲ مگابایت</p>
            </div>

            <div class="ap-card p-5 sm:p-6">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="key-round" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    گذرواژه و تایید شماره
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">
                            گذرواژه @if ($isEditing) <span class="text-[11px] font-medium text-ink-3">(برای تغییر ندادن، خالی بگذارید)</span> @else <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="password" wire:model="password" class="ap-input" dir="ltr" autocomplete="new-password">
                        @error('password') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">تکرار گذرواژه</label>
                        <input type="password" wire:model="password_confirmation" class="ap-input" dir="ltr" autocomplete="new-password">
                        @error('password_confirmation') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line p-3 text-[13px] dark:border-night-line">
                        <input type="checkbox" wire:model="verified" class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                        شماره تلفن تایید شده است
                    </label>
                </div>
            </div>
        </div>

        {{-- فیلدهای اختصاصی (پورت فیلدهای داینامیک قدیمی) --}}
        @if (count($this->fildsList))
            <div class="ap-card p-5 sm:p-6">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="list" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    فیلدهای اختصاصی کاربر
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

        {{-- دکمه‌ها --}}
        <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-end gap-2.5 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-card backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <a href="{{ route('np.users.index') }}" wire:navigate class="ap-btn-ghost">انصراف</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="ap-btn-primary min-w-36">
                <svg wire:loading wire:target="save" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                </svg>
                {{ $isEditing ? 'ذخیره تغییرات' : 'ایجاد کاربر' }}
            </button>
        </div>
    </form>
</div>

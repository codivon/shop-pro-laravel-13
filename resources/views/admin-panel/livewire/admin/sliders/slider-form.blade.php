{{-- فرم ایجاد/ویرایش اسلایدر — پنل جدید (فاز ۶) — پورت ۱:۱ back/sliders/create|edit + _image_uploader + _pages_section + _checkbox_grid --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $pageOptions  = $this->pageOptions;
    $groupOptions = $this->groupOptions;
@endphp

<div class="mx-auto max-w-7xl">

    <form wire:submit="save" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- سربرگ --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="images" class="size-5" />
                </span>
                <div>
                    <h1 class="text-base font-extrabold">{{ $isEditing ? 'ویرایش اسلایدر' : 'ایجاد اسلایدر جدید' }}</h1>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        @if ($isEditing)
                            شناسه: <span class="fa-digits">{{ number_format($slider->id) }}</span>
                            @if ($title) — {{ $title }} @endif
                        @else
                            اسلایدر در صفحات انتخابی سایت با گروه مشخص نمایش داده می‌شود.
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('np.sliders.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به اسلایدرها
            </a>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">

            {{-- ستون چک‌باکس‌ها (صفحات + گروه‌ها) — پورت ستون col-lg-7 قدیمی --}}
            <div class="space-y-5 lg:col-span-3">

                {{-- تصویر اسلایدر (پورت _image_uploader) --}}
                <div class="ap-card p-5">
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="image" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        تصویر اسلایدر
                        <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ضروری</span>
                    </h2>

                    <div class="group relative flex h-48 w-full cursor-pointer items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-line bg-surface-2 transition-colors hover:border-brand-400 dark:border-night-line dark:bg-night-3">
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" alt="پیش‌نمایش تصویر اسلایدر" class="absolute inset-0 size-full object-cover">
                        @elseif ($isEditing && $slider->image)
                            <img src="{{ asset($slider->image) }}" alt="تصویر فعلی اسلایدر" class="absolute inset-0 size-full object-cover">
                        @else
                            <span class="flex flex-col items-center gap-2 text-ink-3 dark:text-night-ink-2">
                                <x-admin-panel::icon name="image-plus" class="size-8 transition-transform group-hover:scale-110" />
                                <span class="text-xs font-semibold">تصویر اسلایدر را انتخاب کنید</span>
                                <span class="text-[11px]">فرمت تصویر — حداکثر ۲ مگابایت</span>
                            </span>
                        @endif
                        <input type="file" wire:model="image" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0" aria-label="انتخاب تصویر اسلایدر">
                    </div>

                    @if ($image || ($isEditing && $slider->image))
                        <p class="mt-2 text-[11px] text-ink-3 dark:text-night-ink-2">انتخاب فایل جدید، تصویر فعلی را جایگزین می‌کند.</p>
                    @endif
                    @error('image') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- نمایش در صفحه (پورت _pages_section — ۳ صفحه ثابت با لیبل خود مدل) --}}
                <div class="ap-card p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="file-text" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            نمایش در صفحه
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                <span class="fa-digits">{{ number_format(count($pages)) }}</span> مورد انتخاب شده
                            </span>
                            <button type="button" wire:click="selectAllPages" class="ap-btn-outline !px-2.5 !py-1.5 !text-[11px]">
                                <x-admin-panel::icon name="check-check" class="size-3.5" />
                                انتخاب همه
                            </button>
                            <button type="button" wire:click="clearPages" class="ap-btn-ghost !px-2.5 !py-1.5 !text-[11px]">
                                <x-admin-panel::icon name="x" class="size-3.5" />
                                پاک کردن
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                        @foreach ($pageOptions as $pageKey => $pageLabel)
                            <label class="flex cursor-pointer flex-col gap-2 rounded-xl border p-3 transition-colors has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/60 dark:border-night-line dark:bg-night-2 dark:has-[:checked]:bg-brand-500/10">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-2 text-[13px] font-bold">{{ $pageLabel }}</span>
                                    <input type="checkbox" wire:model="pages" value="{{ $pageKey }}"
                                           class="size-4 shrink-0 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                </span>
                                <code class="text-[10px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $pageKey }}</code>
                            </label>
                        @endforeach
                    </div>
                    @error('pages') <p class="mt-2.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- گروه‌های نمایش (پورت _checkbox_grid با ابعاد تصویر) --}}
                <div class="ap-card p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="layers" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            گروه‌های نمایش
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                <span class="fa-digits">{{ number_format(count($groups)) }}</span> مورد انتخاب شده
                            </span>
                            <button type="button" wire:click="selectAllGroups" class="ap-btn-outline !px-2.5 !py-1.5 !text-[11px]">
                                <x-admin-panel::icon name="check-check" class="size-3.5" />
                                انتخاب همه
                            </button>
                            <button type="button" wire:click="clearGroups" class="ap-btn-ghost !px-2.5 !py-1.5 !text-[11px]">
                                <x-admin-panel::icon name="x" class="size-3.5" />
                                پاک کردن
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        @foreach ($groupOptions as $groupKey => $group)
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-3 transition-colors has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/60 dark:border-night-line dark:bg-night-2 dark:has-[:checked]:bg-brand-500/10">
                                <span class="min-w-0">
                                    <span class="block truncate text-[13px] font-bold">{{ $group['label'] }}</span>
                                    <span class="fa-digits mt-0.5 block text-[11px] text-ink-3 dark:text-night-ink-2">ابعاد: {{ $group['size'] }}</span>
                                </span>
                                <input type="checkbox" wire:model="groups" value="{{ $groupKey }}"
                                       class="size-4 shrink-0 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            </label>
                        @endforeach
                    </div>
                    @error('groups') <p class="mt-2.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ستون اطلاعات — پورت sk-info-card قدیمی --}}
            <div class="space-y-5 lg:col-span-2">
                <div class="ap-card space-y-4 p-5">
                    <h2 class="flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="info" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        اطلاعات اسلایدر
                    </h2>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="slider-title">عنوان</label>
                        <input id="slider-title" type="text" wire:model="title" class="ap-input" placeholder="عنوان اسلایدر">
                        @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="slider-motion-title">عنوان متحرک</label>
                        <input id="slider-motion-title" type="text" wire:model="motionTitle" class="ap-input" placeholder="Motion Title">
                        @error('motionTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="slider-link">لینک (داخلی یا خارجی)</label>
                        <input id="slider-link" type="text" wire:model="link" dir="ltr" class="ap-input font-mono !text-xs" placeholder="products یا https://...">
                        @error('link') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="slider-description">توضیحات</label>
                        <textarea id="slider-description" wire:model="description" rows="4" class="ap-input" placeholder="توضیحات اسلایدر…"></textarea>
                        @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- نوار ذخیره (پورت sk-info-card-footer + publish-box) --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <label class="flex cursor-pointer items-center gap-2.5 text-[13px] font-semibold">
                <input type="checkbox" wire:model="published"
                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                <span>
                    انتشار اسلایدر
                    <span class="block text-[11px] font-normal text-ink-3 dark:text-night-ink-2">اگر فعال باشد، اسلایدر بلافاصله در سایت نمایش داده می‌شود.</span>
                </span>
            </label>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.sliders.index') }}" wire:navigate class="ap-btn-ghost !py-2.5">انصراف</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="check" class="size-4" />
                    {{ $isEditing ? 'به‌روزرسانی' : 'ذخیره اسلایدر' }}
                </button>
            </div>
        </div>
    </form>

    {{-- لودینگ آپلود تصویر --}}
    <div wire:loading wire:target="image"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری تصویر…</span>
    </div>
</div>

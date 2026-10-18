{{-- فرم ویرایش دسته‌بندی — پنل جدید (فاز ۵) — پورت ۱:۱ CategoryController@update --}}
@php
    $isProduct   = $type === 'productcat';
    $treeRoute   = $isProduct ? 'np.categories.product' : 'np.categories.post';
    $typeLabel   = $isProduct ? 'دسته‌بندی محصولات' : 'دسته‌بندی وبلاگ';
    $typeIcon    = $isProduct ? 'shopping-bag' : 'file-text';
@endphp

<div class="mx-auto max-w-7xl">

    <form wire:submit="save" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="folder-tree" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">ویرایش دسته‌بندی: {{ $title ?: '—' }}</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        شناسه: <span class="fa-digits">{{ number_format($category->id) }}</span>
                        — نوع:
                        <span class="inline-flex items-center gap-1 font-semibold text-ink-2 dark:text-night-ink-2">
                            <x-admin-panel::icon name="{{ $typeIcon }}" class="size-3.5" />
                            {{ $typeLabel }}
                        </span>
                        @if ($slug)
                            — نامک فعلی: <code class="text-[10px]">{{ $slug }}</code>
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route($treeRoute) }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به دسته‌ها
            </a>
        </div>

        {{-- فیلدها --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="cat-title">نام دسته بندی <span class="text-red-500">*</span></label>
                    <input id="cat-title" type="text" wire:model="title" class="ap-input" placeholder="عنوان دسته‌بندی…">
                    @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="cat-slug">url</label>
                    <div class="flex gap-2">
                        <input id="cat-slug" type="text" wire:model="slug" dir="ltr" class="ap-input font-mono !text-xs" placeholder="category-slug">
                        <button type="button" wire:click="generateSlug" wire:loading.attr="disabled" wire:target="generateSlug"
                                class="ap-btn-outline whitespace-nowrap !px-3 !text-xs">
                            ایجاد خودکار
                        </button>
                    </div>
                    @error('slug') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- تصویر اصلی --}}
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">تصویر</label>
                    <div class="group relative size-24 cursor-pointer overflow-hidden rounded-2xl border border-line bg-surface-2 dark:border-night-line dark:bg-night-3">
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" alt="پیش‌نمایش تصویر دسته‌بندی" class="absolute inset-0 size-full object-cover">
                        @elseif ($category->image)
                            <img src="{{ asset($category->image) }}" alt="تصویر فعلی دسته‌بندی" class="absolute inset-0 size-full object-cover">
                        @else
                            <span class="absolute inset-0 flex items-center justify-center text-ink-3">
                                <x-admin-panel::icon name="image-plus" class="size-7 transition-transform group-hover:scale-110" />
                            </span>
                        @endif
                        <input type="file" wire:model="image" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0" aria-label="انتخاب تصویر دسته‌بندی">
                    </div>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">انتخاب فایل جدید، تصویر فعلی را جایگزین می‌کند.</p>
                    @error('image') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- تصویر پس زمینه — ورودی مستقل (رفع باگ آپلود از input تصویر اصلی در قدیمی) --}}
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">تصویر پس زمینه</label>
                    <div class="group relative size-24 cursor-pointer overflow-hidden rounded-2xl border border-line bg-surface-2 dark:border-night-line dark:bg-night-3">
                        @if ($background_image)
                            <img src="{{ $background_image->temporaryUrl() }}" alt="پیش‌نمایش تصویر پس زمینه" class="absolute inset-0 size-full object-cover">
                        @elseif ($category->background_image)
                            <img src="{{ asset($category->background_image) }}" alt="تصویر پس زمینه فعلی دسته‌بندی" class="absolute inset-0 size-full object-cover">
                        @else
                            <span class="absolute inset-0 flex items-center justify-center text-ink-3">
                                <x-admin-panel::icon name="image" class="size-7 transition-transform group-hover:scale-110" />
                            </span>
                        @endif
                        <input type="file" wire:model="background_image" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0" aria-label="انتخاب تصویر پس زمینه">
                    </div>
                    @error('background_image') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- نوع فیلتر و انتخاب فیلتر — فقط دسته‌بندی محصولات (مثل ویو edit قدیمی) --}}
                @if ($isProduct)
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="cat-filter-type">نوع فیلتر</label>
                        <select id="cat-filter-type" wire:model.live="filter_type" class="ap-input">
                            <option value="inherit">ارث بری از دسته بالاتر</option>
                            <option value="none">بدون فیلتر</option>
                            <option value="filterId">انتخاب فیلتر</option>
                        </select>
                        @error('filter_type') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="cat-filter-id">انتخاب فیلتر</label>
                        <select id="cat-filter-id" wire:model="filter_id" class="ap-input" @disabled($this->filter_type !== 'filterId')>
                            <option value="">انتخاب فیلتر…</option>
                            @foreach ($this->filters as $filter)
                                <option value="{{ $filter->id }}">{{ $filter->title }}</option>
                            @endforeach
                        </select>
                        @error('filter_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="cat-commission">میزان کمیسیون (درصد)</label>
                        <input id="cat-commission" type="number" wire:model="commission" dir="ltr" min="0" max="100" class="ap-input" placeholder="مثال: 10">
                        <p class="mt-1.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                            <span class="font-semibold text-amber-600 dark:text-amber-400">۰</span> = کمیسیون قطعی صفر (به والد نگاه نمی‌کند) —
                            <span class="font-semibold">خالی</span> = ارث‌بری از اولین دسته والدِ دارای کمیسیون.
                        </p>
                        @error('commission') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="cat-meta-title">عنوان سئو</label>
                    <input id="cat-meta-title" type="text" wire:model="meta_title" class="ap-input" placeholder="عنوان سئو…">
                    @error('meta_title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="cat-meta-description">توضیحات سئو</label>
                    <textarea id="cat-meta-description" wire:model="meta_description" rows="3" class="ap-input" placeholder="توضیحات سئو…"></textarea>
                    @error('meta_description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- توضیحات (ویرایشگر) — فقط دسته‌بندی محصولات --}}
            @if ($isProduct)
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">توضیحات</label>
                    <div class="overflow-hidden rounded-xl border border-line dark:border-night-line" wire:ignore>
                        <div id="ap-ckeditor" data-editor-value="{{ $description }}"></div>
                    </div>
                    <input type="hidden" wire:model="description" id="ap-ckeditor-input">
                    @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        {{-- نوار ذخیره --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                <input type="checkbox" wire:model="published"
                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                انتشار دسته بندی؟
            </label>
            <div class="flex items-center gap-2">
                <a href="{{ route($treeRoute) }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ذخیره تغییرات
                </button>
            </div>
        </div>
    </form>
</div>

@if ($isProduct)
    @push('scripts')
    <script>
        // رندر مجدد راه‌اندازی CKEditor بعد از هر بار لود صفحه (wire:navigate سازگار)
        document.addEventListener('DOMContentLoaded', () => window.ApEditor?.init());
        document.addEventListener('livewire:navigated', () => window.ApEditor?.init());
        window.ApEditor?.init();
    </script>
    @endpush
@endif

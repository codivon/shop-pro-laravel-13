{{-- فرم ایجاد/ویرایش بنر — پنل جدید (فاز ۶) — پورت ۱:۱ back/banners/create|edit + _image_uploader + _checkbox_grid (صفحات/گروه‌ها/موقعیت‌ها) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $pageOptions  = $this->pageOptions;
    $groupOptions = $this->groupOptions;
    $placeOptions = $this->placeOptions;
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
                    <h1 class="text-base font-extrabold">{{ $isEditing ? 'ویرایش بنر' : 'ایجاد بنر جدید' }}</h1>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        @if ($isEditing)
                            شناسه: <span class="fa-digits">{{ number_format($banner->id) }}</span>
                            @if ($title) — {{ $title }} @endif
                        @else
                            بنر در صفحات انتخابی سایت با گروه و موقعیت مشخص نمایش داده می‌شود.
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('np.banners.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به بنرها
            </a>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">

            {{-- ستون چک‌باکس‌ها (صفحات + گروه‌ها + موقعیت‌ها) — پورت ستون col-lg-7 قدیمی --}}
            <div class="space-y-5 lg:col-span-3">

                {{-- تصویر بنر (پورت _image_uploader قدیمی — انتخاب از File Manager سایت) --}}
                <div class="ap-card p-5">
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="image" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        تصویر بنر
                    </h2>

                    <x-admin-panel::media-picker
                        wireModel="image"
                        :value="$image"
                        hint="تصویر را از رسانه‌های سایت انتخاب کنید — پنجره مدیریت فایل‌ها باز می‌شود و می‌توانید فایل جدید هم آپلود کنید"
                        required
                        height="h-44 sm:h-52"
                    />
                </div>

                {{-- نمایش در صفحه (پورت _pages_section بنر — ۲ صفحه ثابت با لیبل خود مدل) --}}
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

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        @foreach ($pageOptions as $pageKey => $pageLabel)
                            <label class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border p-3 transition-colors has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/60 dark:border-night-line dark:bg-night-2 dark:has-[:checked]:bg-brand-500/10">
                                <span class="min-w-0">
                                    <span class="block truncate text-[13px] font-bold">{{ $pageLabel }}</span>
                                    <code class="text-[10px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $pageKey }}</code>
                                </span>
                                <input type="checkbox" wire:model="pages" value="{{ $pageKey }}"
                                       class="size-4 shrink-0 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            </label>
                        @endforeach
                    </div>
                    @error('pages') <p class="mt-2.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- گروه‌های بنر (پورت _checkbox_grid با ابعاد تصویر) --}}
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

                {{-- موقعیت‌های بنر (پورت بخش places در _checkbox_grid بنر) --}}
                <div class="ap-card p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="map-pin" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            موقعیت‌های نمایش
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                <span class="fa-digits">{{ number_format(count($places)) }}</span> مورد انتخاب شده
                            </span>
                            <button type="button" wire:click="selectAllPlaces" class="ap-btn-outline !px-2.5 !py-1.5 !text-[11px]">
                                <x-admin-panel::icon name="check-check" class="size-3.5" />
                                انتخاب همه
                            </button>
                            <button type="button" wire:click="clearPlaces" class="ap-btn-ghost !px-2.5 !py-1.5 !text-[11px]">
                                <x-admin-panel::icon name="x" class="size-3.5" />
                                پاک کردن
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($placeOptions as $placeKey => $placeLabel)
                            <label class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border p-3 transition-colors has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50/60 dark:border-night-line dark:bg-night-2 dark:has-[:checked]:bg-violet-500/10">
                                <span class="min-w-0">
                                    <span class="block truncate text-[13px] font-bold">{{ $placeLabel }}</span>
                                    <code class="text-[10px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $placeKey }}</code>
                                </span>
                                <input type="checkbox" wire:model="places" value="{{ $placeKey }}"
                                       class="size-4 shrink-0 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            </label>
                        @endforeach
                    </div>
                    @error('places') <p class="mt-2.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ستون اطلاعات — پورت sk-info-card قدیمی --}}
            <div class="space-y-5 lg:col-span-2">
                <div class="ap-card space-y-4 p-5">
                    <h2 class="flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="info" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        اطلاعات بنر
                    </h2>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="banner-title">عنوان</label>
                        <input id="banner-title" type="text" wire:model="title" class="ap-input" placeholder="عنوان بنر">
                        @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="banner-link">لینک (داخلی یا خارجی)</label>
                        <input id="banner-link" type="text" wire:model="link" dir="ltr" class="ap-input font-mono !text-xs" placeholder="products یا https://...">
                        @error('link') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="banner-description">توضیحات</label>
                        <textarea id="banner-description" wire:model="description" rows="4" class="ap-input" placeholder="توضیحات بنر…"></textarea>
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
                    انتشار بنر
                    <span class="block text-[11px] font-normal text-ink-3 dark:text-night-ink-2">اگر فعال باشد، بنر بلافاصله در سایت نمایش داده می‌شود.</span>
                </span>
            </label>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.banners.index') }}" wire:navigate class="ap-btn-ghost !py-2.5">انصراف</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="check" class="size-4" />
                    {{ $isEditing ? 'به‌روزرسانی' : 'ذخیره بنر' }}
                </button>
            </div>
        </div>
    </form>
</div>

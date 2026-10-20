{{-- فرم ایجاد/ویرایش محصول — پنل جدید (فاز ۱) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl"
     x-data="apProductForm()"
     data-rounding-default="{{ option('default_rounding_amount', 'no') }}"
     data-rounding-type-default="{{ option('default_rounding_type', 'close') }}">

    <form wire:submit="save" class="space-y-6" x-on:keydown.enter.prevent>

        {{-- دریافت از دیجی‌کالا / ترب (فقط ایجاد) — فاز ۲۱: ترب فعال شد --}}
        @if (! $isEditing)
            <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
                <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
                    <span class="flex items-center gap-2 text-sm font-bold">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/12 dark:text-rose-400">
                            <x-admin-panel::icon name="download" class="size-4.5" />
                        </span>
                        دریافت محصول از دیجی‌کالا / ترب
                    </span>
                    <span class="ap-chevron" :class="open ? 'is-open' : ''">
                        <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
                    </span>
                </button>

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     class="pt-4">
                    {{-- انتخاب منبع --}}
                    <div class="mb-3 grid max-w-xs grid-cols-2 gap-2" role="radiogroup" aria-label="منبع دریافت محصول">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-2.5 text-[13px] font-semibold transition-all has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-700 dark:has-checked:bg-brand-500/12 dark:has-checked:text-brand-400 border-line dark:border-night-line">
                            <input type="radio" value="digikala" wire:model.live="scraperProvider" class="sr-only">
                            <x-admin-panel::icon name="shopping-bag" class="size-4" />
                            دیجی‌کالا
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-2.5 text-[13px] font-semibold transition-all has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-700 dark:has-checked:bg-brand-500/12 dark:has-checked:text-brand-400 border-line dark:border-night-line">
                            <input type="radio" value="torob" wire:model.live="scraperProvider" class="sr-only">
                            <x-admin-panel::icon name="search-check" class="size-4" />
                            ترب
                        </label>
                    </div>

                    <p class="mb-3 text-xs leading-5 text-ink-2 dark:text-night-ink-2" x-data>
                        <span x-show="$wire.scraperProvider === 'torob'">
                            آدرس کامل صفحه محصول در ترب را وارد کنید (مثال: <code dir="ltr">torob.com/p/xxxxxx/نام-محصول</code>).
                            عنوان، قیمت، تصاویر، دسته‌بندی و مشخصات به صورت خودکار پر می‌شود.
                        </span>
                        <span x-show="$wire.scraperProvider === 'digikala'" x-cloak>
                            کد محصول دیجی‌کالا را از انتهای آدرس صفحه محصول وارد کنید (مثال: dkp-12345678 → ۱۲۳۴۵۶۷۸).
                            عنوان، قیمت‌ها با رنگ‌ها، تصاویر، مشخصات فنی، برند و سئو به صورت خودکار پر می‌شود.
                        </span>
                    </p>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="min-w-48 flex-1">
                            <input type="text" wire:model="scraperSiteCode" :inputmode="$wire.scraperProvider === 'torob' ? 'url' : 'numeric'"
                                   :placeholder="$wire.scraperProvider === 'torob' ? 'https://torob.com/p/…' : 'کد محصول (dkp)'"
                                   class="ap-input fa-digits" aria-label="کد یا آدرس محصول">
                            @error('scraperSiteCode') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" wire:click="fetchFromSite" wire:loading.attr="disabled"
                                class="ap-btn-primary !py-2">
                            <span wire:loading.remove wire:target="fetchFromSite" class="flex items-center gap-2">
                                <x-admin-panel::icon name="cloud-download" class="size-4" />
                                دریافت اطلاعات
                            </span>
                            <span wire:loading wire:target="fetchFromSite" class="flex items-center gap-2">
                                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                                در حال دریافت…
                            </span>
                        </button>
                    </div>

                    @if (count($scraperImages) || $scraperBaseImage)
                        <p class="mt-3 flex items-center gap-2 text-xs font-semibold text-brand-700 dark:text-brand-400">
                            <x-admin-panel::icon name="check-circle" class="size-4" />
                            <span class="fa-digits">{{ count($scraperImages) + ($scraperBaseImage ? 1 : 0) }}</span>
                            تصویر دانلود شد — در تب «تصاویر» قابل مشاهده و حذف است.
                        </p>
                    @endif
                </div>
            </div>
        @endif

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="box" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">
                        {{ $isEditing ? 'ویرایش محصول: ' . $title : 'ایجاد محصول جدید' }}
                    </h2>
                    @if ($isEditing)
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                            شناسه: <span class="fa-digits">{{ number_format($product->id) }}</span>
                            — اسلاگ: <code class="text-[10px]">{{ $product->slug }}</code>
                        </p>
                    @endif
                </div>
            </div>
            <a href="{{ route('np.products.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        {{-- هشدارهای فروشنده (ویرایش) --}}
        @if ($isEditing && $product->seller_id)
            <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-[13px] text-amber-800 dark:border-amber-500/25 dark:bg-amber-950/40 dark:text-amber-300">
                <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4.5 shrink-0" />
                این محصول توسط فروشنده اضافه شده است.
            </div>
        @endif

        {{-- تب‌ها --}}
        <div class="ap-card overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                @foreach ([
                    'basics' => ['info', 'اطلاعات کلی'],
                    'seo' => ['search', 'سئو'],
                    'pricing' => ['coins', $type === 'download' ? 'فایل‌های دانلودی' : 'قیمت و تنوع'],
                    'images' => ['image', 'تصاویر'],
                    'specs' => ['list', 'مشخصات'],
                    'sizes' => ['ruler', 'سایز بندی'],
                    'scores' => ['star', 'امتیاز ویژگی‌ها'],
                    'fields' => ['forms', 'فیلدهای اختصاصی'],
                ] as $tab => [$icon, $label])
                    <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = '{{ $tab }}'"
                            x-bind:aria-selected="(tab === '{{ $tab }}').toString()" role="tab"
                            x-bind:class="tab === '{{ $tab }}' ? 'is-active' : ''">
                        <x-admin-panel::icon name="{{ $icon }}" class="size-4" />
                        {{ $label }}
                        @if ($tab === 'pricing')
                            @if ($type === 'download')
                                <span class="fa-digits ms-1 rounded-md bg-violet-100 px-1.5 text-[10px] font-bold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">{{ count($download_files) }}</span>
                            @else
                                <span class="fa-digits ms-1 rounded-md bg-brand-100 px-1.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ count($prices) }}</span>
                            @endif
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- ================= تب اطلاعات کلی ================= --}}
            <div x-show="tab === 'basics'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">عنوان محصول <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title" class="ap-input" placeholder="مثال: گوشی موبایل سامسونگ ...">
                        @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">عنوان انگلیسی</label>
                        <input type="text" wire:model="title_en" dir="ltr" class="ap-input text-start">
                        @error('title_en') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        {{-- فاز ۱۲: انتخابگر درختی دسته‌بندی --}}
                        <x-admin-panel::category-picker wireProp="category_id" :tree="$this->categoryTree()" :popular="$this->popularCategories()" label="دسته‌بندی اصلی" required placeholder="انتخاب دسته‌بندی اصلی…" />
                        @error('category_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">نوع محصول</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3 text-[13px] font-semibold transition-all has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-700 dark:has-checked:bg-brand-500/12 dark:has-checked:text-brand-400 border-line dark:border-night-line">
                                <input type="radio" value="physical" wire:model.live="type" class="sr-only">
                                <x-admin-panel::icon name="package" class="size-4.5" />
                                فیزیکی
                            </label>
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-3 text-[13px] font-semibold transition-all has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-700 dark:has-checked:bg-brand-500/12 dark:has-checked:text-brand-400 border-line dark:border-night-line">
                                <input type="radio" value="download" wire:model.live="type" class="sr-only">
                                <x-admin-panel::icon name="download" class="size-4.5" />
                                دانلودی
                            </label>
                        </div>
                        @error('type') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">برند</label>
                        <input type="text" wire:model="brand" list="brands-list" class="ap-input" placeholder="نام برند را تایپ یا انتخاب کنید">
                        <datalist id="brands-list">
                            @foreach (\App\Models\Brand::orderBy('name')->get(['name']) as $b)
                                <option value="{{ $b->name }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    @if ($type === 'physical')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">وزن (گرم) <span class="text-red-500">*</span></label>
                                <input type="number" min="0" wire:model="weight" class="ap-input fa-digits">
                                @error('weight') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">واحد <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="unit" class="ap-input">
                                @error('unit') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">ماهیت کالا</label>
                        <select wire:model="shipping_nature" class="ap-input">
                            <option value="">انتخاب کنید…</option>
                            <option value="small">کوچک</option>
                            <option value="medium">متوسط</option>
                            <option value="large">بزرگ</option>
                        </select>
                    </div>

                    @if ($multiVendor)
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">فروشنده</label>
                            <select wire:model="seller_id" class="ap-input">
                                <option value="">— (مدیر سایت)</option>
                                @foreach ($this->sellers as $seller)
                                    <option value="{{ $seller->id }}">
                                        {{ $seller->id }} - {{ $seller->seller_info?->business_name ?? $seller->seller_info?->fullname ?? $seller->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                {{-- دسته‌بندی‌های بیشتر (فاز ۱۲: انتخابگر درختی چندانتخابی) --}}
                <div>
                    <x-admin-panel::category-picker wireProp="extraCategories" :tree="$this->categoryTree()" :popular="$this->popularCategories()" :multiple="true" label="دسته‌بندی‌های بیشتر" placeholder="انتخاب دسته‌بندی‌های بیشتر…" />
                    @error('extraCategories.*') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- برچسب‌ها --}}
                <div x-data="apTags()" data-initial="{{ old('labels', $labels) }}" x-effect="$wire.labels = items.join(',')">
                    <label class="mb-1.5 block text-[13px] font-semibold">برچسب‌ها</label>
                    <div class="flex flex-wrap gap-1.5 rounded-xl border border-line bg-surface p-2.5 dark:border-night-line dark:bg-night-3">
                        <template x-for="(tag, idx) in items" :key="idx">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">
                                <span x-text="tag"></span>
                                <button type="button" x-on:click="remove(idx)" aria-label="حذف برچسب">
                                    <x-admin-panel::icon name="x" class="size-3.5" />
                                </button>
                            </span>
                        </template>
                        <input type="text" x-model="draft" x-on:keydown.enter.prevent="add()" list="labels-list"
                               placeholder="برچسب بنویسید و Enter بزنید…" class="min-w-40 flex-1 bg-transparent text-[13px] outline-none placeholder:text-ink-3">
                        <datalist id="labels-list">
                            @foreach ($this->labelSuggestions as $l)
                                <option value="{{ $l }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                {{-- توضیحات --}}
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">توضیحات کوتاه</label>
                    <textarea wire:model="short_description" rows="3" class="ap-input" placeholder="توضیح کوتاه برای نمایش در لیست محصولات…"></textarea>
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">توضیحات کامل</label>
                    <div class="overflow-hidden rounded-xl border border-line dark:border-night-line" wire:ignore>
                        <div id="ap-ckeditor" data-editor-value="{{ $description }}"></div>
                    </div>
                    <input type="hidden" wire:model="description" id="ap-ckeditor-input">
                </div>
            </div>

            {{-- ================= تب سئو ================= --}}
            <div x-show="tab === 'seo'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">عنوان سئو</label>
                        <input type="text" wire:model="meta_title" class="ap-input">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">آدرس (Slug)</label>
                        <div class="flex gap-2">
                            <input type="text" wire:model="slug" dir="ltr" class="ap-input text-start">
                            <button type="button" wire:click="generateSlug" class="ap-btn-outline shrink-0 !px-3">
                                <x-admin-panel::icon name="sparkles" class="size-4" />
                                ایجاد خودکار
                            </button>
                        </div>
                        @error('slug') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-[13px] font-semibold">توضیحات سئو</label>
                        <textarea wire:model="meta_description" rows="3" class="ap-input"></textarea>
                    </div>
                    <div class="md:col-span-2" x-data="apTags()" data-initial="{{ old('tags', $tags) }}" x-effect="$wire.tags = items.join(',')">
                        <label class="mb-1.5 block text-[13px] font-semibold">کلمات کلیدی</label>
                        <div class="flex flex-wrap gap-1.5 rounded-xl border border-line bg-surface p-2.5 dark:border-night-line dark:bg-night-3">
                            <template x-for="(tag, idx) in items" :key="idx">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-500/12 dark:text-sky-300">
                                    <span x-text="tag"></span>
                                    <button type="button" x-on:click="remove(idx)" aria-label="حذف کلمه">
                                        <x-admin-panel::icon name="x" class="size-3.5" />
                                    </button>
                                </span>
                            </template>
                            <input type="text" x-model="draft" x-on:keydown.enter.prevent="add()" list="tags-list"
                                   placeholder="کلمه کلیدی بنویسید و Enter بزنید…" class="min-w-40 flex-1 bg-transparent text-[13px] outline-none placeholder:text-ink-3">
                            <datalist id="tags-list">
                                @foreach ($this->tagSuggestions as $t)
                                    <option value="{{ $t }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= تب قیمت/فایل ================= --}}
            <div x-show="tab === 'pricing'" x-cloak class="animate-fade-in space-y-6 p-5 sm:p-6">

                @if ($type === 'download')
                    {{-- فایل‌های دانلودی --}}
                    <div class="flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="file-down" class="size-4.5 text-violet-600 dark:text-violet-400" />
                            فایل‌های دانلودی
                        </h3>
                        <button type="button" wire:click="addDownloadRow" class="ap-btn-outline !py-1.5 text-[13px]">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            فایل جدید
                        </button>
                    </div>

                    <div class="space-y-4">
                        @foreach ($download_files as $i => $row)
                            <div wire:key="dl-{{ $i }}" class="rounded-2xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3/60">
                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">عنوان فایل <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model="download_files.{{ $i }}.title" class="ap-input">
                                        @error("download_files.{$i}.title") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">
                                            فایل @if (empty($row['price_id'])) <span class="text-red-500">*</span> @endif
                                        </label>
                                        @if (! empty($row['file_name']))
                                            <div class="flex items-center gap-2.5 rounded-xl border border-line bg-surface p-2 dark:border-night-line">
                                                <x-admin-panel::icon name="file-archive" class="size-4.5 text-ink-3" />
                                                <span class="min-w-0 flex-1 truncate text-xs" dir="ltr">{{ $row['file_name'] }}</span>
                                                <label class="ap-btn-ghost cursor-pointer !px-2.5 !py-1.5 text-xs">
                                                    <x-admin-panel::icon name="rotate-cw" class="size-4" />
                                                    جایگزینی
                                                    <input type="file" wire:model="download_files.{{ $i }}.file" class="hidden">
                                                </label>
                                            </div>
                                        @else
                                            <input type="file" wire:model="download_files.{{ $i }}.file" class="ap-input !py-2 file:me-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                                        @endif
                                        @error("download_files.{$i}.file") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">قیمت (تومان) <span class="text-red-500">*</span></label>
                                        <input type="number" min="0" wire:model="download_files.{{ $i }}.price" class="ap-input fa-digits">
                                        @error("download_files.{$i}.price") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold">تخفیف (٪)</label>
                                            <input type="number" min="0" max="100" wire:model="download_files.{{ $i }}.discount" class="ap-input fa-digits">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                                            <select wire:model="download_files.{{ $i }}.status" class="ap-input">
                                                <option value="active">فعال</option>
                                                <option value="inactive">غیرفعال</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex justify-end">
                                    <button type="button" wire:click="removeDownloadRow({{ $i }})"
                                            class="rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10" title="حذف فایل">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- تنظیمات گرد کردن --}}
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">واحد پول</label>
                            <select wire:model="currency_id" x-on:change="recalcAll()" class="ap-input">
                                <option value="">تومان (پیش‌فرض)</option>
                                @foreach ($this->currencies as $cur)
                                    <option value="{{ $cur->id }}" data-amount="{{ $cur->amount }}" @if($currency_id == $cur->id) selected @endif>
                                        {{ $cur->title }} ({{ $cur->amount }})
                                    </option>
                                @endforeach
                            </select>
                            @error('currency_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">مقدار گرد کردن</label>
                            <select wire:model="rounding_amount" class="ap-input">
                                <option value="default">پیشفرض</option>
                                <option value="no">خیر</option>
                                <option value="100">۱۰۰</option>
                                <option value="1000">۱۰۰۰</option>
                                <option value="10000">۱۰۰۰۰</option>
                                <option value="100000">۱۰۰۰۰۰</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-semibold">نوع گرد کردن</label>
                            <select wire:model="rounding_type" class="ap-input">
                                <option value="default">پیشفرض</option>
                                <option value="close">نزدیک</option>
                                <option value="up">رو به بالا</option>
                                <option value="down">رو به پایین</option>
                            </select>
                        </div>
                    </div>

                    {{-- ردیف‌های تنوع --}}
                    <div class="flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="layers" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            قیمت‌ها و تنوع‌ها
                        </h3>
                        <button type="button" wire:click="addPriceRow" class="ap-btn-primary !py-1.5 text-[13px]">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            افزودن تنوع
                        </button>
                    </div>

                    <div class="space-y-4">
                        @foreach ($prices as $i => $row)
                            <div wire:key="price-{{ $i }}" class="rounded-2xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3/60"
                                 x-data="apFinalPrice()" x-init="init({{ $i }})" data-price-row data-idx="{{ $i }}">
                                <div class="mb-4 flex items-center justify-between">
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                        تنوع <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($i + 1) }}</span>
                                        @if (! empty($row['id']))
                                            <span class="opacity-60">#{{ $row['id'] }}</span>
                                        @endif
                                        @if (! empty($row['seller_id']))
                                            <span class="ms-1 rounded-md bg-sky-100 px-1.5 text-[10px] font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">فروشنده</span>
                                        @endif
                                    </span>
                                    <button type="button" wire:click="removePriceRow({{ $i }})"
                                            class="rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10" title="حذف تنوع">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">انبار <span class="text-red-500">*</span></label>
                                        <select wire:model="prices.{{ $i }}.warehouse" class="ap-input">
                                            <option value="">انتخاب انبار…</option>
                                            @foreach ($this->warehouses as $wh)
                                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                                            @endforeach
                                        </select>
                                        @error("prices.{$i}.warehouse") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">قیمت (تومان) <span class="text-red-500">*</span></label>
                                        <input type="number" min="0" wire:model="prices.{{ $i }}.price" data-price-input
                                               x-on:input="calc()" class="ap-input fa-digits">
                                        @error("prices.{$i}.price") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">موجودی انبار <span class="text-red-500">*</span></label>
                                        <input type="number" min="0" wire:model="prices.{{ $i }}.stock" class="ap-input fa-digits">
                                        @error("prices.{$i}.stock") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                {{-- ویژگی‌ها --}}
                                <div class="mt-4 space-y-3">
                                    <p class="text-xs font-bold text-ink-2 dark:text-night-ink-2">ویژگی‌های این تنوع:</p>
                                    @foreach ($this->attributeGroups as $group)
                                        @if ($group->get_attributes->count())
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="min-w-24 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">{{ $group->name }}:</span>
                                                @if ($group->type === 'color')
                                                    @foreach ($group->get_attributes as $attr)
                                                        <button type="button"
                                                                wire:click="pickColor({{ $i }}, {{ $group->id }}, {{ $attr->id }})"
                                                                class="group flex items-center gap-1.5 rounded-full border px-2 py-1 text-[11px] font-semibold transition-all
                                                                       {{ in_array((string) $attr->id, $row['attributes'] ?? []) || in_array($attr->id, $row['attributes'] ?? [])
                                                                            ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-300'
                                                                            : 'border-line bg-surface text-ink-2 hover:border-brand-300 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2' }}">
                                                            <span class="size-3.5 rounded-full border border-black/10" style="background: {{ $attr->value }}"></span>
                                                            {{ $attr->name }}
                                                        </button>
                                                    @endforeach
                                                @else
                                                    @foreach ($group->get_attributes as $attr)
                                                        <label class="cursor-pointer">
                                                            <input type="checkbox" value="{{ $attr->id }}" wire:model="prices.{{ $i }}.attributes" class="peer sr-only">
                                                            <span class="inline-block rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-all
                                                                         border-line bg-surface text-ink-2 hover:border-brand-300 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2
                                                                         peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700
                                                                         dark:peer-checked:bg-brand-500/12 dark:peer-checked:text-brand-300">
                                                                {{ $attr->name }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-5">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">تخفیف (٪)</label>
                                        <input type="number" min="0" max="100" wire:model="prices.{{ $i }}.discount" data-discount-input
                                               x-on:input="calc()" class="ap-input fa-digits">
                                        @error("prices.{$i}.discount") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">انقضای تخفیف</label>
                                        <input type="text" wire:model="prices.{{ $i }}.discount_expire_at" data-jdp data-jdp-only-date="false"
                                               placeholder="۱۴۰۴/۰۱/۰۱ ۱۲:۰۰" class="ap-input fa-digits text-center">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">حداکثر خرید</label>
                                        {{-- فاز ۲۱ — min=1 مقدار ۰ (نامحدود) را invalid می‌کرد و کل فرم غیرقابل ارسال می‌شد --}}
                                        <input type="number" min="0" wire:model="prices.{{ $i }}.cart_max" class="ap-input fa-digits">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">حداقل خرید</label>
                                        <input type="number" min="0" wire:model="prices.{{ $i }}.cart_min" class="ap-input fa-digits">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">وضعیت نمایش</label>
                                        <select wire:model="prices.{{ $i }}.published" class="ap-input">
                                            <option value="1">نمایش</option>
                                            <option value="0">عدم نمایش</option>
                                        </select>
                                    </div>
                                </div>

                                <p class="mt-3 rounded-xl bg-brand-50 px-3.5 py-2 text-xs font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                                    قیمت نهایی: <span class="fa-digits" data-final-price>—</span> تومان
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ================= تب تصاویر ================= --}}
            <div x-show="tab === 'images'" x-cloak class="animate-fade-in space-y-6 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {{-- تصویر شاخص --}}
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold">تصویر شاخص</label>
                        <label class="group relative flex aspect-square cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl border-2 border-dashed border-line bg-surface-2 transition-colors hover:border-brand-400 dark:border-night-line dark:bg-night-3/50">
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" alt="پیش‌نمایش تصویر شاخص" class="absolute inset-0 size-full object-cover">
                            @elseif ($scraperBaseImage && file_exists(public_path('uploads/tmp/' . $scraperBaseImage)))
                                {{-- فاز ۲۱ — تصویر شاخص دریافتی از دیجی‌کالا/ترب --}}
                                <img src="{{ asset('uploads/tmp/' . $scraperBaseImage) }}" alt="تصویر شاخص دریافتی" class="absolute inset-0 size-full object-cover">
                                <span class="absolute top-1.5 start-1.5 rounded-md bg-rose-600 px-1.5 py-0.5 text-[9px] font-bold text-white">دریافتی</span>
                                <div class="absolute inset-0 flex items-center justify-center bg-slate-950/55 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button type="button" wire:click="removeScraperBase"
                                            class="rounded-lg bg-red-500/80 p-1.5 text-white transition-colors hover:bg-red-500" title="حذف تصویر دریافتی" aria-label="حذف تصویر شاخص دریافتی">
                                        <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                    </button>
                                </div>
                            @elseif ($isEditing && $product->image)
                                <img src="{{ $product->imageUrl() }}" alt="تصویر فعلی محصول" class="absolute inset-0 size-full object-cover">
                            @else
                                <x-admin-panel::icon name="image-plus" class="size-10 text-ink-3 transition-transform group-hover:scale-110" />
                                <span class="text-xs font-semibold text-ink-3">انتخاب تصویر شاخص</span>
                                <span class="text-[10px] text-ink-3/70">600×600 پیشنهاد می‌شود</span>
                            @endif
                            <input type="file" wire:model="image" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0">
                        </label>
                        @error('image') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        @if ($image)
                            <button type="button" wire:click="$set('image', null)" class="mt-2 flex items-center gap-1 text-xs font-semibold text-red-500 hover:underline">
                                <x-admin-panel::icon name="x" class="size-3.5" />
                                حذف تصویر انتخابی
                            </button>
                        @endif
                    </div>

                    {{-- گالری --}}
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-[13px] font-semibold">گالری تصاویر <span class="text-[11px] font-normal text-ink-3">(ترتیب نمایش با فلش‌ها قابل تغییر است)</span></label>

                        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                            {{-- تصاویر موجود --}}
                            @foreach ($existingGallery as $gi => $img)
                                <div wire:key="eg-{{ $gi }}" class="group relative aspect-square overflow-hidden rounded-xl border border-line dark:border-night-line">
                                    <img src="{{ asset($img) }}" alt="تصویر گالری" class="size-full object-cover" loading="lazy">
                                    <div class="absolute inset-0 flex items-center justify-center gap-1 bg-slate-950/55 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button type="button" wire:click="moveExistingImage({{ $gi }}, 'up')"
                                                class="rounded-lg bg-white/20 p-1.5 text-white transition-colors hover:bg-white/35" title="انتقال به راست" aria-label="جابجایی">
                                            <x-admin-panel::icon name="chevron-right" class="size-3.5" />
                                        </button>
                                        <button type="button" wire:click="removeExistingImage({{ $gi }})"
                                                class="rounded-lg bg-red-500/80 p-1.5 text-white transition-colors hover:bg-red-500" title="حذف" aria-label="حذف تصویر">
                                            <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                        </button>
                                        <button type="button" wire:click="moveExistingImage({{ $gi }}, 'down')"
                                                class="rounded-lg bg-white/20 p-1.5 text-white transition-colors hover:bg-white/35" title="انتقال به چپ" aria-label="جابجایی">
                                            <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            {{-- تصاویر دریافتی از دیجی‌کالا/ترب (فاز ۲۱ — پیش‌نمایش + امکان حذف پیش از ذخیره) --}}
                            @foreach ($scraperImages as $si => $scraperImg)
                                <div wire:key="si-{{ $si }}" class="group relative aspect-square overflow-hidden rounded-xl border border-rose-300 dark:border-rose-500/40">
                                    <img src="{{ asset('uploads/tmp/' . $scraperImg) }}" alt="تصویر دریافتی" class="size-full object-cover" loading="lazy">
                                    <span class="absolute top-1.5 start-1.5 rounded-md bg-rose-600 px-1.5 py-0.5 text-[9px] font-bold text-white">دریافتی</span>
                                    <div class="absolute inset-0 flex items-center justify-center bg-slate-950/55 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button type="button" wire:click="removeScraperImage({{ $si }})"
                                                class="rounded-lg bg-red-500/80 p-1.5 text-white transition-colors hover:bg-red-500" title="حذف" aria-label="حذف تصویر دریافتی">
                                            <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            {{-- تصاویر جدید --}}
                            @foreach ($gallery as $ni => $newImg)
                                <div wire:key="ng-{{ $ni }}" class="group relative aspect-square overflow-hidden rounded-xl border border-brand-300 dark:border-brand-500/40">
                                    <img src="{{ $newImg->temporaryUrl() }}" alt="تصویر جدید" class="size-full object-cover">
                                    <span class="absolute top-1.5 start-1.5 rounded-md bg-brand-600 px-1.5 py-0.5 text-[9px] font-bold text-white">جدید</span>
                                    <div class="absolute inset-0 flex items-center justify-center bg-slate-950/55 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button type="button" wire:click="removeGalleryImage({{ $ni }})"
                                                class="rounded-lg bg-red-500/80 p-1.5 text-white transition-colors hover:bg-red-500" title="حذف" aria-label="حذف تصویر جدید">
                                            <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            {{-- افزودن --}}
                            {{-- فاز ۱۲: relative اجباری است — بدون آن input نامرئی file روی کل صفحه گسترده می‌یابد و هر کلیک، پنجره انتخاب فایل را باز می‌کرد --}}
                            <label class="group relative flex aspect-square cursor-pointer flex-col items-center justify-center gap-1.5 overflow-hidden rounded-xl border-2 border-dashed border-line bg-surface-2 transition-colors hover:border-brand-400 dark:border-night-line dark:bg-night-3/50">
                                @if (count($gallery) > 0 && ! $errors->has('gallery.*'))
                                    <x-admin-panel::icon name="plus" class="size-6 text-brand-600 dark:text-brand-400" />
                                @else
                                    <x-admin-panel::icon name="images" class="size-6 text-ink-3 transition-transform group-hover:scale-110" />
                                @endif
                                <span class="text-[10px] font-semibold text-ink-3">افزودن تصاویر</span>
                                <input type="file" wire:model="gallery" accept="image/*" multiple class="absolute inset-0 cursor-pointer opacity-0">
                            </label>
                        </div>
                        @error('gallery.*') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-4">
                            <label class="mb-1.5 block text-[13px] font-semibold">متن جایگزین تصویر</label>
                            <input type="text" wire:model="image_alt" class="ap-input" placeholder="توضیح تصویر برای موتورهای جستجو…">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= تب مشخصات ================= --}}
            <div x-show="tab === 'specs'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-56 flex-1">
                        <label class="mb-1.5 block text-[13px] font-semibold">نوع مشخصات</label>
                        <input type="text" wire:model="spec_type" list="spectypes-list" class="ap-input" placeholder="مثلا گوشی موبایل">
                        <datalist id="spectypes-list">
                            @foreach (\App\Models\SpecType::get(['name']) as $st)
                                <option value="{{ $st->name }}"></option>
                            @endforeach
                        </datalist>
                        @error('spec_type') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="button" wire:click="applySpecTemplate" class="ap-btn-outline">
                        <x-admin-panel::icon name="wand-2" class="size-4" />
                        اعمال قالب
                    </button>
                    <button type="button" wire:click="addSpecGroup" class="ap-btn-primary">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        گروه جدید
                    </button>
                </div>

                @foreach ($specificationGroup as $gi => $group)
                    <div wire:key="specg-{{ $gi }}" class="rounded-2xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3/60">
                        <div class="mb-3 flex items-center gap-2.5">
                            <input type="text" wire:model="specificationGroup.{{ $gi }}.name" class="ap-input max-w-xs" placeholder="نام گروه — مثال: مشخصات کلی">
                            <button type="button" wire:click="removeSpecGroup({{ $gi }})" class="ms-auto rounded-lg p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" title="حذف گروه">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            @foreach ($group['specifications'] as $si => $spec)
                                <div wire:key="spec-{{ $gi }}-{{ $si }}" class="grid grid-cols-12 items-center gap-2.5 rounded-xl bg-surface p-2.5 dark:bg-night-2">
                                    <div class="col-span-12 sm:col-span-4">
                                        <input type="text" wire:model="specificationGroup.{{ $gi }}.specifications.{{ $si }}.name" class="ap-input !py-2" placeholder="عنوان (مثال: اندازه صفحه)">
                                    </div>
                                    <div class="col-span-11 sm:col-span-7">
                                        <textarea rows="1" wire:model="specificationGroup.{{ $gi }}.specifications.{{ $si }}.value" class="ap-input !py-2" placeholder="مقدار (مثال: ۶.۷ اینچ)"></textarea>
                                    </div>
                                    <div class="col-span-1 flex items-center justify-center">
                                        <label title="نمایش ویژه" class="cursor-pointer">
                                            <input type="checkbox" wire:model="specificationGroup.{{ $gi }}.specifications.{{ $si }}.special" class="size-4 rounded-md border-line text-amber-500 focus:ring-amber-500/30 dark:border-night-line">
                                        </label>
                                    </div>
                                    <div class="col-span-12 flex justify-end">
                                        <button type="button" wire:click="removeSpecRow({{ $gi }}, {{ $si }})" class="rounded-lg p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10" title="حذف">
                                            <x-admin-panel::icon name="x" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addSpecRow({{ $gi }})" class="mt-3 flex items-center gap-1 text-xs font-bold text-brand-600 hover:underline dark:text-brand-400">
                            <x-admin-panel::icon name="plus" class="size-3.5" />
                            افزودن مشخصه
                        </button>
                    </div>
                @endforeach

                @if (! count($specificationGroup))
                    <p class="rounded-xl border border-dashed border-line p-6 text-center text-[13px] text-ink-3 dark:border-night-line">
                        گروهی اضافه نشده است. با دکمه «گروه جدید» مشخصات فنی را تعریف کنید.
                    </p>
                @endif
            </div>

            {{-- ================= تب سایز بندی ================= --}}
            <div x-show="tab === 'sizes'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                <div class="max-w-sm">
                    <label class="mb-1.5 block text-[13px] font-semibold">راهنمای سایز</label>
                    <select wire:model.live="size_type_id" class="ap-input">
                        <option value="">انتخاب راهنمای سایز…</option>
                        @foreach ($this->sizeTypes as $st)
                            <option value="{{ $st->id }}">{{ $st->title }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($size_type_id)
                    <div class="space-y-4">
                        @foreach ($sizes as $gi => $groupSizes)
                            <div wire:key="sizeg-{{ $gi }}" class="rounded-2xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3/60">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                        سایز گروه <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($gi + 1) }}</span>
                                    </span>
                                    <button type="button" wire:click="removeSizeGroup({{ $gi }})" class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                                    @foreach ($sizeTypeSizes as $size)
                                        <div>
                                            <label class="mb-1 block text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">{{ $size->title }}</label>
                                            <input type="text" wire:model="sizes.{{ $gi }}.{{ $size->id }}" class="ap-input !py-2 fa-digits">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" wire:click="addSizeGroup" class="ap-btn-outline">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن گروه سایز
                    </button>
                @endif
            </div>

            {{-- ================= تب امتیاز ویژگی‌ها ================= --}}
            <div x-show="tab === 'scores'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <p class="text-[13px] text-ink-2 dark:text-night-ink-2">امتیاز ۰ تا ۵ برای ویژگی‌های محصول (مثلا کیفیت ساخت، باتری…)</p>
                    <button type="button" wire:click="addAbilityScore" class="ap-btn-primary !py-1.5 text-[13px]">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن
                    </button>
                </div>

                @foreach ($abilityScores as $i => $score)
                    <div wire:key="score-{{ $i }}" class="grid grid-cols-12 items-center gap-3 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3/60">
                        <div class="col-span-12 sm:col-span-6">
                            <input type="text" wire:model="abilityScores.{{ $i }}.name" class="ap-input" placeholder="عنوان ویژگی (مثال: کیفیت ساخت)">
                        </div>
                        <div class="col-span-10 sm:col-span-4">
                            <input type="number" min="0" max="5" step="0.1" wire:model="abilityScores.{{ $i }}.value" class="ap-input fa-digits" placeholder="امتیاز ۰ تا ۵">
                        </div>
                        <div class="col-span-2 flex justify-end">
                            <button type="button" wire:click="removeAbilityScore({{ $i }})" class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ================= تب فیلدهای اختصاصی ================= --}}
            <div x-show="tab === 'fields'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                @php
                    $dynamicFilds = \App\Models\Fild::where('belongs_to', 'products')->orderBy('created_at', 'desc')->get();
                @endphp

                @if ($dynamicFilds->isEmpty())
                    <p class="rounded-xl border border-dashed border-line p-6 text-center text-[13px] text-ink-3 dark:border-night-line">
                        فیلد اختصاصی‌ای برای محصولات تعریف نشده است.
                    </p>
                @else
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        @foreach ($dynamicFilds as $fild)
                            <div>
                                <label class="mb-1.5 block text-[13px] font-semibold">
                                    {{ $fild->title }}
                                    @if ($fild->required) <span class="text-red-500">*</span> @endif
                                </label>

                                @if ($fild->type === 'textarea')
                                    <textarea wire:model="filds.{{ $fild->id }}" rows="3" class="ap-input"></textarea>
                                @elseif ($fild->type === 'select')
                                    <select wire:model="filds.{{ $fild->id }}" class="ap-input">
                                        <option value="">انتخاب کنید…</option>
                                        @foreach (explode(',', $fild->select_options) as $opt)
                                            <option value="{{ trim($opt) }}">{{ trim($opt) }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($fild->type === 'checkbox')
                                    <label class="flex cursor-pointer items-center gap-2.5 text-[13px]">
                                        <input type="checkbox" value="1" wire:model="filds.{{ $fild->id }}" class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                                        بله
                                    </label>
                                @elseif ($fild->type === 'number')
                                    <input type="number" wire:model="filds.{{ $fild->id }}" class="ap-input fa-digits">
                                @elseif ($fild->type === 'email')
                                    <input type="email" dir="ltr" wire:model="filds.{{ $fild->id }}" class="ap-input text-start">
                                @else
                                    <input type="text" wire:model="filds.{{ $fild->id }}" class="ap-input">
                                @endif

                                @error("filds.{$fild->id}") <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- کارت انتشار (چسبان) --}}
        <div class="ap-card sticky bottom-4 z-20 p-4 shadow-pop sm:p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت انتشار</label>
                    <select wire:model="published" class="ap-input !py-2">
                        <option value="1">انتشار</option>
                        <option value="0">پیش‌نویس</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت تایید</label>
                    <select wire:model="status" class="ap-input !py-2">
                        <option value="Accept">تایید شده</option>
                        <option value="Waiting">در انتظار تایید</option>
                        <option value="Reject">تایید نشده</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">تاریخ انتشار</label>
                    <input type="text" wire:model="publish_date" data-jdp data-jdp-only-date="false" class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۴/۰۱/۰۱">
                </div>
                <div>
                    <label class="mb-1.5 flex cursor-pointer items-center gap-2 text-xs font-semibold">
                        <input type="checkbox" wire:model.live="special" class="size-4 rounded-md border-line text-amber-500 dark:border-night-line">
                        محصول ویژه
                    </label>
                    <input type="text" wire:model="special_end_date" data-jdp data-jdp-only-date="false"
                           class="ap-input !py-2 fa-digits text-center {{ $special ? '' : 'opacity-40 pointer-events-none' }}"
                           placeholder="انقضای ویژه بودن">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ap-btn-primary w-full !py-2.5" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">ذخیره محصول</span>
                        <span wire:loading wire:target="save" class="flex items-center justify-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                            در حال ذخیره…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // رندر مجدد راه‌اندازی CKEditor بعد از هر بار لود صفحه (wire:navigate سازگار)
    document.addEventListener('DOMContentLoaded', () => window.ApEditor?.init());
    document.addEventListener('livewire:navigated', () => window.ApEditor?.init());
    window.ApEditor?.init();
</script>
@endpush

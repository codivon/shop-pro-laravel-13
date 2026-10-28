{{-- فاز ۱۱۹ — «موجودی و تنوع‌ها» پنل مدیریت
     نمای کلی: موجودی کل هر محصول + ریز تک‌تک تنوع‌ها با تفکیک
     فروشِ سایت (بدون فروشنده) و تنوع‌های فروشندگان (بَج فروشگاه) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- ================= سربرگ + آمار کلی ================= --}}
    <div class="ap-card p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="boxes" class="size-5" />
                </span>
                <div>
                    <h1 class="text-sm font-extrabold">موجودی و تنوع‌ها</h1>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        موجودی کل هر محصول به‌همراه ریز تک‌تک تنوع‌ها و تنوع‌های فروشندگان
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3/60">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">موجودی کل انبار</p>
                <p class="fa-digits mt-1 text-lg font-extrabold text-ink dark:text-night-ink">{{ number_format($this->stats['total_stock']) }}</p>
            </div>
            <div class="rounded-2xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3/60">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">تعداد کل تنوع‌ها</p>
                <p class="fa-digits mt-1 text-lg font-extrabold text-ink dark:text-night-ink">{{ number_format($this->stats['total_variants']) }}</p>
            </div>
            <div class="rounded-2xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3/60">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">تنوع‌های فروشندگان</p>
                <p class="fa-digits mt-1 text-lg font-extrabold text-sky-600 dark:text-sky-400">{{ number_format($this->stats['seller_variants']) }}</p>
            </div>
            <div class="rounded-2xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3/60">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">موجودی محصولات همین صفحه</p>
                <p class="fa-digits mt-1 text-lg font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($this->stats['page_total_stock']) }}</p>
            </div>
        </div>
    </div>

    {{-- ================= فیلترها ================= --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: {{ $title || $categoryId ? 'true' : 'false' }} }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر محصولات
                @if ($title || $categoryId)
                    <span class="size-2 rounded-full bg-brand-500"></span>
                @endif
            </span>
            <span class="ap-chevron" :class="open ? 'is-open' : ''">
                <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
            </span>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="pt-4">
            <form class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4" x-on:submit.prevent>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="stock-title">عنوان</label>
                    <input id="stock-title" type="text" wire:model.live.debounce.400ms="title" class="ap-input !py-2"
                           placeholder="عنوان محصول…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="stock-category">دسته‌بندی‌ها</label>
                    <select id="stock-category" wire:model="categoryId" multiple size="5" class="ap-input !py-2 h-auto">
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">
                                @for ($i = 0; $i < $category->parents()->count(); $i++)—@endfor
                                {{ $category->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="stock-per-page">تعداد در صفحه</label>
                    <select id="stock-per-page" wire:model.live="perPage" class="ap-input !py-2">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button" wire:click="$set('title', ''); $wire.set('categoryId', [])"
                            class="ap-btn-ghost !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= لیست محصولات ================= --}}
    @if ($this->products->count())
        <div class="space-y-4">
            @foreach ($this->products as $product)
                @php
                    $productStock = (int) $product->prices->sum('stock');
                    $sellerVariants = $product->prices->whereNotNull('seller_id');
                @endphp
                <div wire:key="stock-product-{{ $product->id }}" class="ap-card overflow-hidden"
                     x-data="{ open: false }">
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->title }}"
                             class="size-12 shrink-0 rounded-xl border border-line object-cover dark:border-night-line">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold">{{ $product->title }}</p>
                            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                                شناسه: {{ number_format($product->id) }}
                                @if ($product->category)
                                    — {{ $product->category->title }}
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="ap-badge {{ $productStock > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300' }}"
                                  title="موجودی کل محصول (جمع همهٔ تنوع‌ها)">
                                موجودی کل: <b class="fa-digits">{{ number_format($productStock) }}</b>
                            </span>
                            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2"
                                  title="تعداد کل تنوع‌های این محصول">
                                تنوع‌ها: <b class="fa-digits">{{ number_format($product->prices->count()) }}</b>
                            </span>
                            @if ($sellerVariants->count())
                                <span class="ap-badge bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"
                                      title="تعداد تنوع‌های متعلق به فروشندگان">
                                    فروشندگان: <b class="fa-digits">{{ number_format($sellerVariants->count()) }}</b>
                                </span>
                            @endif
                            <button type="button" x-on:click="open = !open"
                                    class="ap-btn-outline !py-1.5 text-[11px]">
                                <span x-text="open ? 'بستن تنوع‌ها' : 'نمایش تنوع‌ها'"></span>
                                <x-admin-panel::icon name="chevron-down" class="size-3.5 transition-transform"
                                                     x-bind:class="open ? 'rotate-180' : ''" />
                            </button>
                        </div>
                    </div>

                    {{-- ریز تنوع‌ها --}}
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         class="border-t border-line dark:border-night-line">
                        @if ($product->prices->count())
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-3xl text-start text-[13px]">
                                    <thead>
                                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                                            <th class="px-4 py-2.5 text-start font-bold">تنوع</th>
                                            <th class="w-44 px-4 py-2.5 text-start font-bold">فروشنده</th>
                                            <th class="w-40 px-4 py-2.5 text-start font-bold">قیمت نهایی</th>
                                            <th class="w-28 px-4 py-2.5 text-start font-bold">موجودی</th>
                                            <th class="w-36 px-4 py-2.5 text-start font-bold">انبار</th>
                                            <th class="w-24 px-4 py-2.5 text-start font-bold">وضعیت</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($product->prices as $price)
                                            <tr wire:key="stock-variant-{{ $price->id }}"
                                                class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                                                <td class="px-4 py-2.5">
                                                    <span class="fa-digits me-1.5 inline-flex size-5 items-center justify-center rounded-md bg-surface-2 text-[10px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-3">{{ \App\Livewire\Admin\Dashboard::fa($loop->iteration) }}</span>
                                                    {{ $price->getAttributesName() ?: 'عادی' }}
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    @if ($price->seller_id)
                                                        <span class="ap-badge bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"
                                                              title="این تنوع متعلق به فروشنده است">
                                                            <x-admin-panel::icon name="store" class="size-3" />
                                                            {{ $price->seller?->seller_info?->business_name
                                                                ?: trim(($price->seller?->seller_info?->first_name ?? '') . ' ' . ($price->seller?->seller_info?->last_name ?? ''))
                                                                ?: ('فروشنده #' . $price->seller_id) }}
                                                        </span>
                                                    @else
                                                        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                            فروش خود سایت
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="fa-digits px-4 py-2.5 font-bold">
                                                    @if ($price->discount_price && $price->hasDiscount())
                                                        <span class="text-red-500">{{ number_format($price->discount_price) }}</span>
                                                        <span class="text-[10px] text-ink-3 line-through">{{ number_format($price->regular_price) }}</span>
                                                    @else
                                                        {{ number_format($price->regular_price ?: $price->price) }}
                                                    @endif
                                                    <span class="text-[10px] font-normal text-ink-3">تومان</span>
                                                </td>
                                                <td class="fa-digits px-4 py-2.5">
                                                    <span class="inline-flex items-center gap-1 font-bold {{ $price->stock > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                                        @if ($price->stock > 0)
                                                            <x-admin-panel::icon name="check" class="size-3.5" />
                                                        @else
                                                            <x-admin-panel::icon name="x" class="size-3.5" />
                                                        @endif
                                                        {{ number_format($price->stock) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2.5 text-ink-2 dark:text-night-ink-2">
                                                    {{ $price->warehouse?->name ?: '—' }}
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    @if ($price->published)
                                                        <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">نمایش</span>
                                                    @else
                                                        <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">عدم نمایش</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="px-4 py-6 text-center text-xs text-ink-3 dark:text-night-ink-2">
                                این محصول تنوع (قیمت) ثبت‌شده ندارد.
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- صفحه‌بندی --}}
        <div class="ap-card px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->products->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->products->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->products->total()) }}</span> محصول
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->products->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->products->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
                        @elseif (! is_numeric(trim($link['label'])))
                            {{-- دکمه‌های قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                        @elseif ($link['active'])
                            <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                        @else
                            <button wire:click="gotoPage({{ trim($link['label']) }})"
                                    class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                {{ $link['label'] }}
                            </button>
                        @endif
                    @endforeach

                    @if ($this->products->hasMorePages())
                        <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @else
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @endif
                </nav>
            </div>
        </div>
    @else
        <div class="ap-card flex flex-col items-center gap-2 py-16 text-ink-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="package-search" class="size-10 opacity-40" />
            <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
        </div>
    @endif
</div>

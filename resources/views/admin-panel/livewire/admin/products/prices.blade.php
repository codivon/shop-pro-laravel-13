{{-- قیمت‌گذاری محصولات — پورت products/prices قدیمی (فاز ۷-ج۲) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- فیلترها --}}
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
                    <label class="mb-1.5 block text-xs font-semibold" for="prices-title">عنوان</label>
                    <input id="prices-title" type="text" wire:model.live.debounce.400ms="title" class="ap-input !py-2"
                           placeholder="عنوان محصول…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="prices-category">دسته‌بندی‌ها</label>
                    <select id="prices-category" wire:model="categoryId" multiple size="5" class="ap-input !py-2 h-auto">
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">
                                @for ($i = 0; $i < $category->parents()->count(); $i++)—@endfor
                                {{ $category->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="prices-per-page">تعداد در صفحه</label>
                    <select id="prices-per-page" wire:model.live="perPage" class="ap-input !py-2">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <a href="{{ Route::has('np.products.prices') ? route('np.products.prices') : '#' }}"
                       class="ap-btn-ghost !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if ($this->products->count())
        {{-- سربرگ ذخیره --}}
        <div class="sticky top-16 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 px-4 py-3 shadow-sm backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <div class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="coins" class="size-4.5 text-brand-600 dark:text-brand-400" />
                قیمت‌ها
            </div>
            <button type="button" wire:click="savePrices" wire:loading.attr="disabled" wire:target="savePrices"
                    class="ap-btn !py-2">
                <x-admin-panel::icon name="save" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>

        @error('prices')
            <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-[13px] font-semibold text-red-600 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
                {{ $message }}
            </div>
        @enderror

        {{-- لیست محصولات با ردیف‌های قیمت --}}
        <div class="space-y-4">
            @foreach ($this->products as $product)
                <div wire:key="prices-product-{{ $product->id }}" class="ap-card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-line px-4 py-3 dark:border-night-line">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->title }}"
                             class="size-12 shrink-0 rounded-xl border border-line object-cover dark:border-night-line">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold">{{ $product->title }}</p>
                            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                                شناسه: {{ number_format($product->id) }}
                            </p>
                        </div>
                        @if (Route::has('front.products.show') && $product->slug)
                            <a href="{{ route('front.products.show', ['product' => $product]) }}" target="_blank" rel="noopener"
                               class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                               title="مشاهده در فروشگاه">
                                <x-admin-panel::icon name="external-link" class="size-4" />
                            </a>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-2xl text-start text-[13px]">
                            <thead>
                                <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                                    <th class="px-4 py-2.5 text-start font-bold">تنوع</th>
                                    <th class="w-56 px-4 py-2.5 text-start font-bold">قیمت</th>
                                    <th class="w-32 px-4 py-2.5 text-start font-bold">موجودی</th>
                                    <th class="w-40 px-4 py-2.5 text-start font-bold">آخرین تغییر</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($product->prices as $price)
                                    <tr wire:key="prices-row-{{ $price->id }}"
                                        class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                                        <td class="px-4 py-2.5">
                                            {{ $price->getAttributesName() ?: 'عادی' }}
                                            {{-- فاز ۱۱۹ — مشخص‌کردن تنوع‌های ایجادشده توسط فروشندگان --}}
                                            @if ($price->seller_id)
                                                <span class="ap-badge ms-1.5 bg-sky-100 py-0.5 text-[10px] font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"
                                                      title="این تنوع توسط فروشنده ثبت شده است">
                                                    <x-admin-panel::icon name="store" class="size-3" />
                                                    فروشنده{{ isset($this->sellerLabels[$price->seller_id]) ? ': ' . $this->sellerLabels[$price->seller_id] : '' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="relative">
                                                <input type="number" step="any" min="0"
                                                       wire:model="priceInputs.{{ $product->id }}.{{ $price->id }}.price"
                                                       class="ap-input ltr !py-1.5 text-center font-mono"
                                                       aria-label="قیمت تنوع {{ $price->id }}">
                                                <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-[10px] text-ink-3">
                                                    {{ $product->currency ? $product->currency->title : 'تومان' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <input type="number" step="any" min="0"
                                                   wire:model="priceInputs.{{ $product->id }}.{{ $price->id }}.stock"
                                                   @if ($product->isDownload()) readonly @endif
                                                   class="ap-input ltr !py-1.5 text-center font-mono disabled:opacity-50"
                                                   aria-label="موجودی تنوع {{ $price->id }}">
                                        </td>
                                        <td class="fa-digits px-4 py-2.5 text-ink-3 dark:text-night-ink-2">
                                            {{ jdate($price->updated_at)->ago() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- دکمه ذخیره پایین --}}
        <div class="flex justify-center">
            <button type="button" wire:click="savePrices" wire:loading.attr="disabled" wire:target="savePrices"
                    class="ap-btn !py-2.5">
                <x-admin-panel::icon name="save" class="size-4" />
                ذخیره تغییرات
            </button>
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

    {{-- لودینگ --}}
    <div wire:loading wire:target="savePrices"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال ذخیره…</span>
    </div>
</div>

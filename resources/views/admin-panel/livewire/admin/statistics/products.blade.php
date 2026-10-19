{{-- آمارگیری فروش هر محصول — پورت orders/products + productItemTemplate قدیمی (فاز ۷-ج۲) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- فیلترها (پورت filters قدیمی) --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: {{ request()->query() ? 'true' : 'false' }} }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر آمار محصولات
                @if ($product_name || $from_date || $to_date)
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
            <form wire:submit="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="products-stats-name">نام محصول</label>
                    <input id="products-stats-name" type="text" wire:model="product_name" class="ap-input !py-2"
                           placeholder="عنوان محصول…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="products-stats-from">از تاریخ</label>
                    <input id="products-stats-from" type="text" wire:model="from_date" data-jdp data-jdp-only-date="true"
                           autocomplete="off" dir="ltr"
                           class="ap-input !py-2 !pe-9 font-mono" placeholder="1400-01-01">
                    @error('from_date') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="products-stats-to">تا تاریخ</label>
                    <input id="products-stats-to" type="text" wire:model="to_date" data-jdp data-jdp-only-date="true"
                           autocomplete="off" dir="ltr"
                           class="ap-input !py-2 !pe-9 font-mono" placeholder="1400-12-29">
                    @error('to_date') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="products-stats-ordering">مرتب‌سازی</label>
                    <select id="products-stats-ordering" wire:model.live="ordering" class="ap-input !py-2">
                        <option value="newest">جدیدترین سفارش</option>
                        <option value="oldest">قدیمی‌ترین سفارش</option>
                        <option value="most_sold">بیشترین فروش</option>
                        <option value="least_sold">کمترین فروش</option>
                    </select>
                </div>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                    <button type="submit" wire:loading.attr="disabled" wire:target="applyFilters"
                            class="ap-btn !py-2">
                        <x-admin-panel::icon name="filter" class="size-4" />
                        اعمال فیلتر
                    </button>
                    <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters"
                            class="ap-btn-ghost !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- کارت‌های محصول (پورت productItemTemplate قدیمی) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($this->products as $row)
            <div wire:key="product-stats-{{ $row->product_id }}" class="ap-card overflow-hidden transition-shadow hover:shadow-pop">
                <div class="relative h-36 bg-surface-2 dark:bg-night-3">
                    <img src="{{ $row->product_image ? asset($row->product_image) : asset('/empty.svg') }}"
                         alt="{{ $row->product_title }}" class="size-full object-contain p-3">
                    @if ($row->successful_orders > 0)
                        <span class="fa-digits absolute end-3 top-3 rounded-full bg-brand-600 px-2.5 py-1 text-[11px] font-bold text-white">
                            {{ $this->fa($row->successful_orders) }} فروش موفق
                        </span>
                    @endif
                </div>
                <div class="border-b border-line p-4 dark:border-night-line">
                    <p class="line-clamp-2 min-h-10 text-[13px] font-bold leading-5">{{ $row->product_title }}</p>
                </div>
                <div class="grid grid-cols-2 gap-2.5 p-4 text-[12px]">
                    <div class="rounded-lg bg-surface-2 p-2.5 dark:bg-night-3">
                        <p class="text-[10px] text-ink-3 dark:text-night-ink-2">کل فروش</p>
                        <p class="fa-digits mt-0.5 font-extrabold">{{ $this->fa($row->total_orders) }}</p>
                    </div>
                    <div class="rounded-lg bg-surface-2 p-2.5 dark:bg-night-3">
                        <p class="text-[10px] text-ink-3 dark:text-night-ink-2">فروش موفق</p>
                        <p class="fa-digits mt-0.5 font-extrabold text-brand-600 dark:text-brand-400">{{ $this->fa($row->successful_orders) }}</p>
                    </div>
                    <div class="rounded-lg bg-surface-2 p-2.5 dark:bg-night-3">
                        <p class="text-[10px] text-ink-3 dark:text-night-ink-2">ناموفق</p>
                        <p class="fa-digits mt-0.5 font-extrabold text-red-500">{{ $this->fa($row->failed_orders) }}</p>
                    </div>
                    <div class="rounded-lg bg-surface-2 p-2.5 dark:bg-night-3">
                        <p class="text-[10px] text-ink-3 dark:text-night-ink-2">امروز</p>
                        <p class="fa-digits mt-0.5 font-extrabold">{{ $this->fa($row->today_orders) }}</p>
                    </div>
                    <div class="col-span-2 rounded-lg bg-surface-2 p-2.5 dark:bg-night-3">
                        <p class="text-[10px] text-ink-3 dark:text-night-ink-2">مبلغ کل سفارش‌ها</p>
                        <p class="fa-digits mt-0.5 font-extrabold">{{ $this->fa(number_format((float) $row->total_order_amount)) }} <span class="text-[10px] font-normal">تومان</span></p>
                    </div>
                    <div class="col-span-2 rounded-lg border border-brand-200 bg-brand-50 p-2.5 dark:border-brand-500/20 dark:bg-brand-500/10">
                        <p class="text-[10px] text-brand-700 dark:text-brand-400">سود (سفارش موفق)</p>
                        <p class="fa-digits mt-0.5 font-extrabold text-brand-700 dark:text-brand-400">{{ $this->fa(number_format((float) $row->total_profit)) }} <span class="text-[10px] font-normal">تومان</span></p>
                    </div>
                    <div class="col-span-2 rounded-lg bg-surface-2 p-2.5 dark:bg-night-3">
                        <p class="text-[10px] text-ink-3 dark:text-night-ink-2">موجودی فعلی</p>
                        <p class="fa-digits mt-0.5 font-extrabold {{ $row->available_stock > 0 ? '' : 'text-red-500' }}">{{ $this->fa($row->available_stock) }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center gap-2 py-16 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="package-search" class="size-10 opacity-40" />
                <p class="text-sm font-medium">سفارشی برای این فیلترها یافت نشد</p>
            </div>
        @endforelse
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
</div>

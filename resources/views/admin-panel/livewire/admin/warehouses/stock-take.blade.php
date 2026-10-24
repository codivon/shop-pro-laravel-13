{{-- سرشماری انبار — پنل جدید (فاز ۷-ب۳) — پورت stockTakeData + stockTake (مودال قدیمی show → صفحه مستقل) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="clipboard-list" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-black">سرشماری انبار: {{ $warehouse->name }}</h1>
                <p class="fa-digits mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">کد: {{ $warehouse->code }}</p>
            </div>
        </div>
        <a href="{{ route('np.warehouses.show', ['warehouse' => $warehouse->id]) }}" wire:navigate class="ap-btn-ghost !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به انبار
        </a>
    </div>

    {{-- هشدار --}}
    <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-[13px] leading-6 text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
        <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4 shrink-0" />
        <span><strong>توجه:</strong> موجودی واقعی هر تنوع را وارد کنید. مغایرت‌ها در تاریخچه انبار ثبت می‌شوند.
            اگر هیچ عددی وارد نکنید، موجودی فعلی همه تنوع‌ها «تأیید» می‌شود.</span>
    </div>

    {{-- آمار (پورت stm-stat pills) --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card flex items-center justify-between p-4">
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">موجود</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->stats['ok']) }}</p>
            </div>
            <span class="size-2.5 rounded-full bg-emerald-500"></span>
        </div>
        <div class="ap-card flex items-center justify-between p-4">
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کم‌موجود</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->stats['low']) }}</p>
            </div>
            <span class="size-2.5 rounded-full bg-amber-500"></span>
        </div>
        <div class="ap-card flex items-center justify-between p-4">
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">اتمام موجودی</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->stats['empty']) }}</p>
            </div>
            <span class="size-2.5 rounded-full bg-red-500"></span>
        </div>
        <div class="ap-card flex items-center justify-between p-4">
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کل تنوع‌ها</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->stats['total']) }}</p>
            </div>
            <span class="size-2.5 rounded-full bg-sky-500"></span>
        </div>
    </div>

    {{-- نتیجه آخرین سرشماری --}}
    @if ($lastResult)
        <div class="ap-card p-4">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="circle-check" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                نتیجه سرشماری
                <span class="fa-digits text-xs font-semibold text-ink-3 dark:text-night-ink-2">
                    بروزرسانی: {{ number_format($lastResult['updated_count']) }} — تأیید: {{ number_format($lastResult['confirmed_count']) }} — یافت‌نشده: {{ number_format($lastResult['not_found_count']) }}
                </span>
            </h3>
            @if (count($lastResult['differences_list']))
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-start text-[13px]">
                        <thead>
                            <tr class="border-b border-line text-ink-2 dark:border-night-line dark:text-night-ink-2">
                                <th class="px-3 py-2 text-start font-bold">محصول</th>
                                <th class="px-3 py-2 text-start font-bold">ویژگی‌ها</th>
                                <th class="px-3 py-2 text-center font-bold">قبل</th>
                                <th class="px-3 py-2 text-center font-bold">واردشده</th>
                                <th class="px-3 py-2 text-center font-bold">مغایرت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lastResult['differences_list'] as $change)
                                <tr class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                                    <td class="px-3 py-2 font-semibold">{{ $change['product_title'] }}</td>
                                    <td class="px-3 py-2 text-ink-2 dark:text-night-ink-2">{{ $change['attributes'] }}</td>
                                    <td class="fa-digits px-3 py-2 text-center">{{ number_format($change['old_stock']) }}</td>
                                    <td class="fa-digits px-3 py-2 text-center">{{ number_format($change['new_stock']) }}</td>
                                    <td class="fa-digits px-3 py-2 text-center font-bold {{ $change['difference'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $change['difference'] > 0 ? '+' : '' }}{{ number_format($change['difference']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- فیلترها (پورت stm toolbar) --}}
    <div class="ap-card p-4">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="جستجوی محصول یا کد تنوع…">
            </div>
            <div class="flex items-end gap-2">
                <button type="button" wire:click="$set('stockFilter', '')" class="ap-btn-outline !py-2 flex-1 {{ $stockFilter === '' ? '!border-brand-500 !text-brand-600' : '' }}">
                    همه
                </button>
                <button type="button" wire:click="$set('stockFilter', 'empty')" class="ap-btn-outline !py-2 flex-1 {{ $stockFilter === 'empty' ? '!border-brand-500 !text-brand-600' : '' }}">
                    صفر
                </button>
                <button type="button" wire:click="$set('stockFilter', 'low')" class="ap-btn-outline !py-2 flex-1 {{ $stockFilter === 'low' ? '!border-brand-500 !text-brand-600' : '' }}">
                    کم‌موجود
                </button>
            </div>
        </div>
    </div>

    {{-- جدول سرشماری --}}
    <form wire:submit="submitStockTake">
        <div class="ap-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 dark:border-night-line">
                <h3 class="text-sm font-bold">موجودی واقعی هر تنوع را وارد کنید</h3>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="fillWithCurrent" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="wand-2" class="size-4" />
                        پرکردن با موجودی فعلی
                    </button>
                    <button type="button" wire:click="resetStocks" class="ap-btn-ghost !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        پاک‌کردن
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-4xl text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="px-3 py-3 text-start font-bold">محصول</th>
                            <th class="px-3 py-3 text-start font-bold">ویژگی‌ها</th>
                            <th class="px-3 py-3 text-center font-bold">موجودی سیستم</th>
                            <th class="px-3 py-3 text-center font-bold">موجودی واقعی</th>
                            <th class="px-3 py-3 text-center font-bold">مغایرت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->variations as $price)
                            @php
                                $entered = $stocks[$price->id] ?? null;
                                $difference = $entered !== null ? (int) $entered - (int) $price->stock : null;
                            @endphp
                            <tr wire:key="take-{{ $price->id }}"
                                class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="max-w-56 px-3 py-3">
                                    <p class="truncate font-semibold">{{ $price->product?->title ?? '—' }}</p>
                                    <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                                        کد تنوع: {{ number_format($price->id) }}
                                        @if ($price->product?->brand) — {{ $price->product->brand->name }} @endif
                                    </p>
                                </td>
                                <td class="max-w-40 px-3 py-3">
                                    @if ($price->attributes && $price->attributes->count())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($price->attributes as $attr)
                                                <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                    @if ($attr->group && $attr->group->type == 'color')
                                                        <span class="inline-block size-2.5 rounded-full" style="background-color: {{ $attr->value ?? '#6c757d' }};"></span>
                                                    @endif
                                                    {{ $attr->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-ink-3 dark:text-night-ink-2">بدون ویژگی</span>
                                    @endif
                                </td>
                                <td class="fa-digits px-3 py-3 text-center">
                                    <span class="ap-badge {{ $price->stock > 5 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($price->stock > 0 ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400') }}">
                                        {{ number_format($price->stock) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <input type="number" min="0" wire:model="stocks.{{ $price->id }}"
                                           class="ap-input !py-2 w-28 text-center" placeholder="{{ $price->stock }}">
                                    @error('stocks.'.$price->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($difference !== null)
                                        <span class="fa-digits ap-badge {{ $difference > 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($difference < 0 ? 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400' : 'bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2') }}">
                                            {{ $difference > 0 ? '+' : '' }}{{ number_format($difference) }}
                                        </span>
                                    @else
                                        <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="package-search" class="size-10 opacity-40" />
                                        <p class="text-sm font-medium">نتیجه‌ای یافت نشد</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- صفحه‌بندی --}}
            <div class="border-t border-line px-4 py-3 dark:border-night-line">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">
                        نمایش <span class="fa-digits">{{ number_format($this->variations->firstItem() ?? 0) }}</span> تا
                        <span class="fa-digits">{{ number_format($this->variations->lastItem() ?? 0) }}</span> از
                        <span class="fa-digits">{{ number_format($this->variations->total()) }}</span> تنوع — مغایرت کل همه صفحات بعد از ثبت محاسبه می‌شود
                    </p>
                    <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                        @if ($this->variations->onFirstPage())
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                        @else
                            <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                                <x-admin-panel::icon name="chevron-right" class="size-4" />
                            </button>
                        @endif

                        @foreach ($this->variations->onEachSide(1)->toArray()['links'] as $link)
                            @if ($link['label'] === '...')
                                <span class="px-2 text-xs text-ink-3">…</span>
                            @elseif (! is_numeric(trim($link['label'])))
                                {{-- قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                            @elseif ($link['active'])
                                <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                            @else
                                <button wire:click="gotoPage({{ trim($link['label']) }})"
                                        class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                    {{ $link['label'] }}
                                </button>
                            @endif
                        @endforeach

                        @if ($this->variations->hasMorePages())
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

        {{-- جمع مغایرت + توضیحات + ثبت (پورت stm summary bar) --}}
        <div class="ap-card mt-5 p-4">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">توضیحات (اختیاری)</label>
                    <textarea wire:model="description" rows="2" class="ap-input !py-2" placeholder="دلیل یا توضیحات سرشماری…"></textarea>
                    @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col justify-end gap-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="submitStockTake" class="ap-btn w-full !py-2.5">
                        <x-admin-panel::icon name="clipboard-list" class="size-4" />
                        ثبت سرشماری
                    </button>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        اگر هیچ عددی وارد نشود، موجودی فعلی همه تنوع‌های انبار تأیید می‌شود (سرشماری دوره‌ای).
                    </p>
                </div>
            </div>
        </div>
    </form>

    {{-- لودینگ --}}
    <div wire:loading wire:target="search, stockFilter, previousPage, nextPage, gotoPage, submitStockTake, fillWithCurrent"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>

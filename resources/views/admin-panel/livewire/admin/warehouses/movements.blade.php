{{-- تاریخچه حرکات انبار — پنل جدید (فاز ۷-ب۳) — پورت movements قدیمی --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                <x-admin-panel::icon name="history" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-black">تاریخچه حرکات انبار: {{ $warehouse->name }}</h1>
                <p class="fa-digits mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">کد: {{ $warehouse->code }}</p>
            </div>
        </div>
        <a href="{{ route('np.warehouses.show', ['warehouse' => $warehouse->id]) }}" wire:navigate class="ap-btn-ghost !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به انبار
        </a>
    </div>

    {{-- آمار (پورت info-boxهای قدیمی — روی کل رکوردها) --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="package" class="size-5" />
            </span>
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کل آیتم‌ها</p>
                <p class="fa-digits mt-0.5 text-xl font-black">{{ number_format($this->stats['total']) }}</p>
            </div>
        </div>
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="box" class="size-5" />
            </span>
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">ورود به انبار</p>
                <p class="fa-digits mt-0.5 text-xl font-black">{{ number_format($this->stats['in']) }}</p>
            </div>
        </div>
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                <x-admin-panel::icon name="upload" class="size-5" />
            </span>
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">خروج از انبار</p>
                <p class="fa-digits mt-0.5 text-xl font-black">{{ number_format($this->stats['out']) }}</p>
            </div>
        </div>
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="clock" class="size-5" />
            </span>
            <div>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">رزرو موقت</p>
                <p class="fa-digits mt-0.5 text-xl font-black">{{ number_format($this->stats['reserve']) }}</p>
            </div>
        </div>
    </div>

    {{-- فیلترها (پورت فرم فیلتر قدیمی) --}}
    <div class="ap-card p-4 sm:p-5">
        <form wire:submit="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="mb-1.5 block text-xs font-semibold">نوع حرکت</label>
                <select wire:model.live="type" class="ap-input !py-2">
                    <option value="all">همه</option>
                    <option value="in">ورود به انبار</option>
                    <option value="out">خروج از انبار</option>
                    <option value="reserve">رزرو موقت</option>
                    <option value="unreserve">لغو رزرو</option>
                    <option value="adjustment">تعدیل دستی</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">محصول</label>
                <select wire:model.live="productId" class="ap-input !py-2">
                    <option value="">همه محصولات</option>
                    @foreach ($this->products as $product)
                        <option value="{{ $product->id }}">{{ $product->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">از تاریخ</label>
                <input type="text" wire:model="fromDate" class="ap-input !py-2" placeholder="۱۴۰۴/۰۱/۰۱">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold">تا تاریخ</label>
                <input type="text" wire:model="toDate" class="ap-input !py-2" placeholder="۱۴۰۴/۱۲/۲۹">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="ap-btn !py-2 flex-1">
                    <x-admin-panel::icon name="filter" class="size-4" />
                    فیلتر
                </button>
                <button type="button" wire:click="resetFilters" class="ap-btn-outline !py-2" title="بازنشانی">
                    <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                </button>
            </div>
        </form>
    </div>

    {{-- جدول حرکات --}}
    <div class="ap-card overflow-hidden">
        <div class="border-b border-line px-4 py-4 dark:border-night-line">
            <h3 class="text-sm font-bold">لیست حرکات انبار</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-5xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="px-3 py-3 text-start font-bold">تاریخ و زمان</th>
                        <th class="px-3 py-3 text-start font-bold">نوع حرکت</th>
                        <th class="px-3 py-3 text-start font-bold">محصول</th>
                        <th class="px-3 py-3 text-start font-bold">تنوع</th>
                        <th class="px-3 py-3 text-center font-bold">تعداد</th>
                        <th class="px-3 py-3 text-center font-bold">موجودی قبل/بعد</th>
                        <th class="px-3 py-3 text-start font-bold">مرجع</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                        <th class="px-3 py-3 text-start font-bold">اپراتور</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->movements as $movement)
                        <tr wire:key="movement-{{ $movement->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="fa-digits whitespace-nowrap px-3 py-3">
                                {{ $this->jdate($movement->created_at, 'd F Y H:i:s') }}
                            </td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ $this->movementTypeBadgeClass($movement->type) }}">{{ $this->movementTypeLabel($movement->type) }}</span>
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                @if ($movement->price && $movement->price->product)
                                    <a href="{{ route('np.products.edit', ['product' => $movement->price->product]) }}" wire:navigate
                                       class="font-semibold text-brand-700 hover:underline dark:text-brand-400">
                                        {{ $movement->price->product->title }}
                                    </a>
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">محصول حذف شده</span>
                                @endif
                                @if ($movement->price)
                                    <small class="fa-digits block text-ink-3 dark:text-night-ink-2">کد قیمت: {{ number_format($movement->price_id) }}</small>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($movement->price)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($movement->price->attributes as $attr)
                                            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                @if ($attr->group && $attr->group->type == 'color')
                                                    <span class="inline-block size-2.5 rounded-full" style="background-color: {{ $attr->value ?? '#6c757d' }};"></span>
                                                @endif
                                                {{ $attr->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-red-500">تنوع حذف شده</span>
                                    @if ($movement->attributes)
                                        @php $deletedAttributes = json_decode($movement->attributes, true); @endphp
                                        @if (is_array($deletedAttributes) && count($deletedAttributes) > 0)
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @foreach ($deletedAttributes as $groupName => $attrs)
                                                    @foreach ($attrs as $attr)
                                                        <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                            @if (! empty($attr['value']))
                                                                <span class="inline-block size-2 rounded-full" style="background-color: {{ $attr['value'] }};"></span>
                                                            @endif
                                                            {{ $attr['name'] }}
                                                        </span>
                                                    @endforeach
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="fa-digits font-bold {{ $movement->type == 'in' ? 'text-emerald-600 dark:text-emerald-400' : ($movement->type == 'out' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400') }}">
                                    {{ $movement->type == 'in' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                                </span>
                            </td>
                            <td class="fa-digits px-3 py-3 text-center">{{ number_format($movement->before_stock) }} ← {{ number_format($movement->after_stock) }}</td>
                            <td class="px-3 py-3">
                                @if ($movement->order_id)
                                    <a href="{{ route('np.orders.show', ['order' => $movement->order_id]) }}" wire:navigate
                                       class="font-semibold text-brand-700 hover:underline dark:text-brand-400">
                                        سفارش #{{ number_format($movement->order_id) }}
                                    </a>
                                @elseif ($movement->reference)
                                    {{ $movement->reference }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="max-w-56 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ $movement->description ?? '-' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $movement->operator_name }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="clock" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هیچ حرکتی در این انبار ثبت نشده است</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->movements->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->movements->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->movements->total()) }}</span> حرکت
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->movements->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->movements->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->movements->hasMorePages())
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

    {{-- لودینگ --}}
    <div wire:loading wire:target="type, productId, fromDate, toDate, previousPage, nextPage, gotoPage, applyFilters, resetFilters"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>

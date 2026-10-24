{{-- بروزرسانی گروهی موجودی — پنل جدید (فاز ۷-ب۳) — پورت bulkStockData + bulkStockUpdate (مودال قدیمی show → صفحه مستقل) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                <x-admin-panel::icon name="sliders-horizontal" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-black">بروزرسانی گروهی موجودی: {{ $warehouse->name }}</h1>
                <p class="fa-digits mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">کد: {{ $warehouse->code }}</p>
            </div>
        </div>
        <a href="{{ route('np.warehouses.show', ['warehouse' => $warehouse->id]) }}" wire:navigate class="ap-btn-ghost !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به انبار
        </a>
    </div>

    {{-- جستجو + انتخاب همه (پورت bsm toolbar) --}}
    <div class="ap-card p-4">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="جستجوی محصول یا کد تنوع…">
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="toggleSelectAll" class="ap-btn-outline !py-2 w-full">
                    <x-admin-panel::icon name="check-check" class="size-4" />
                    انتخاب همه این صفحه
                </button>
            </div>
            <div class="flex items-end justify-end">
                <span class="fa-digits ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                    {{ number_format(count($selected)) }} تنوع انتخاب شده
                </span>
            </div>
        </div>
    </div>

    {{-- جدول تنوع‌ها --}}
    <form wire:submit="saveBulkUpdate">
        <div class="ap-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-4xl text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="w-10 px-3 py-3"></th>
                            <th class="px-3 py-3 text-start font-bold">محصول</th>
                            <th class="px-3 py-3 text-start font-bold">ویژگی‌ها</th>
                            <th class="px-3 py-3 text-center font-bold">موجودی فعلی</th>
                            <th class="px-3 py-3 text-center font-bold">موجودی جدید</th>
                            <th class="px-3 py-3 text-center font-bold">فروش</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->variations as $price)
                            @php
                                $newStock = $stocks[$price->id] ?? null;
                            @endphp
                            <tr wire:key="bulk-{{ $price->id }}"
                                class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="px-3 py-3">
                                    <input type="checkbox" wire:model="selected" value="{{ $price->id }}"
                                           class="size-4 rounded border-line text-brand-600 focus:ring-brand-500 dark:border-night-line">
                                </td>
                                <td class="max-w-56 px-3 py-3">
                                    <p class="truncate font-semibold">{{ $price->product?->title ?? '—' }}</p>
                                    <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                                        کد تنوع: {{ number_format($price->id) }}
                                        @if ($price->product?->category) — {{ $price->product->category->title }} @endif
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
                                <td class="px-3 py-3 text-center">
                                    <span class="fa-digits ap-badge {{ $price->stock > 5 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($price->stock > 0 ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400') }}">
                                        {{ number_format($price->stock) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <input type="number" min="0" wire:model="stocks.{{ $price->id }}"
                                           class="ap-input !py-2 w-28 text-center" placeholder="{{ $price->stock }}">
                                    @error('stocks.'.$price->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="fa-digits ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ number_format($price->sold_count ?? 0) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
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
                        <span class="fa-digits">{{ number_format($this->variations->total()) }}</span> تنوع — انتخاب‌ها بین صفحات حفظ می‌شوند
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

        {{-- پنل عملیات گروهی (پورت bsm-ops-panel) --}}
        <div class="ap-card mt-5 p-4">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="sliders-horizontal" class="size-4.5 text-brand-600 dark:text-brand-400" />
                عملیات گروهی
            </h3>
            <div class="mt-4 grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع عملیات</label>
                    <select wire:model="operationType" class="ap-input !py-2">
                        <option value="set">تنظیم مستقیم</option>
                        <option value="add">افزایش عدد</option>
                        <option value="subtract">کاهش عدد</option>
                        <option value="percentage_add">افزایش درصدی</option>
                        <option value="percentage_subtract">کاهش درصدی</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">مقدار</label>
                    <input type="number" wire:model="bulkValue" class="ap-input !py-2" placeholder="عدد…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">اعمال روی</label>
                    <select wire:model="applyScope" class="ap-input !py-2">
                        <option value="selected">تنوع‌های انتخاب شده</option>
                        <option value="product">محصولاتِ تنوع‌های انتخاب شده</option>
                        <option value="all">همه (این صفحه)</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button" wire:click="applyBulkOperation" class="ap-btn-outline !py-2 w-full">
                        <x-admin-panel::icon name="wand-2" class="size-4" />
                        اعمال روی ردیف‌ها
                    </button>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="mb-1.5 block text-xs font-semibold">توضیحات (اختیاری)</label>
                    <input type="text" wire:model="description" class="ap-input !py-2" placeholder="دلیل بروزرسانی موجودی…">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveBulkUpdate" class="ap-btn w-full !py-2.5">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ذخیره تغییرات
                    </button>
                    <button type="button" wire:click="resetForm" class="ap-btn-ghost !py-2.5" title="پاک‌کردن فرم">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                    </button>
                </div>
            </div>
            <p class="mt-3 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                تغییرات فقط بعد از «ذخیره تغییرات» ثبت می‌شوند؛ همه رکوردهای حرکت انبار (StockMovement) به‌صورت «تنظیم دستی» در تاریخچه ثبت می‌شوند.
            </p>
        </div>
    </form>

    {{-- لودینگ --}}
    <div wire:loading wire:target="search, previousPage, nextPage, gotoPage, saveBulkUpdate, applyBulkOperation, toggleSelectAll"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>

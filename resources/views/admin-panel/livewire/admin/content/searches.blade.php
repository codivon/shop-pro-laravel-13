{{-- جستجوهای فروشگاه — پورت back/searches (فاز ۷-ب۲) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ + فیلترها (پورت request(search_type/keyword) قدیمی) --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="search" class="size-3.5" />
                کلیدواژه‌های یکتا: <span class="fa-digits">{{ number_format($this->rows->total()) }}</span>
            </span>
        </div>
    </div>

    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر جستجوها
                @if ($filterType || $filterKeyword !== '')
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
                    <label class="mb-1.5 block text-xs font-semibold">کلمه جستجو</label>
                    <input type="text" wire:model.live.debounce.400ms="filterKeyword" class="ap-input !py-2" placeholder="جستجوی کلیدواژه…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع جستجو</label>
                    <select wire:model.live="filterType" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="products">محصولات</option>
                        <option value="posts">پست‌ها</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- نوار انتخاب گروهی --}}
    @if (count($selected))
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface p-3.5 dark:border-night-line dark:bg-night-2">
            <p class="text-[13px] font-semibold">
                <span class="fa-digits text-brand-600 dark:text-brand-400">{{ number_format(count($selected)) }}</span> مورد انتخاب شده:
            </p>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="confirmBulkDelete" class="ap-btn !py-1.5 bg-red-600 text-white hover:bg-red-700">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    حذف همه
                </button>
                <button type="button" wire:click="$set('selected', [])" class="ap-btn-ghost !py-1.5">لغو انتخاب‌ها</button>
            </div>
        </div>
    @endif

    {{-- جدول (پورت back/searches/index) --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-12 px-3 py-3">
                            <input type="checkbox" class="size-4 accent-brand-600" wire:click="toggleSelectAll" aria-label="انتخاب همه">
                        </th>
                        <th class="px-3 py-3 text-start font-bold">کلمه جستجو</th>
                        <th class="px-3 py-3 text-start font-bold">نوع جستجو</th>
                        <th class="px-3 py-3 text-center font-bold">تعداد جستجو</th>
                        <th class="px-3 py-3 text-center font-bold">میانگین نتایج</th>
                        <th class="px-3 py-3 text-center font-bold">آخرین جستجو</th>
                        <th class="px-3 py-3 text-center font-bold">اولین جستجو</th>
                        <th class="px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->pageItems as $index => $search)
                        <tr wire:key="search-{{ md5($search->keyword . '__' . $search->search_type) }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" class="size-4 accent-brand-600"
                                       value="{{ $search->keyword }}__{{ $search->search_type }}"
                                       wire:model="selected" aria-label="انتخاب {{ $search->keyword }}">
                            </td>
                            <td class="max-w-44 px-3 py-3">
                                <p class="truncate font-bold">{{ $search->keyword }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @if ($search->search_type === 'products')
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">محصولات</span>
                                @else
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">پست‌ها</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="fa-digits ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">{{ number_format($search->search_count) }} بار</span>
                            </td>
                            <td class="px-3 py-3 fa-digits text-center font-semibold">{{ number_format(round($search->avg_results)) }}</td>
                            <td class="px-3 py-3 fa-digits text-center text-ink-2 dark:text-night-ink-2">{{ self::jdate($search->last_searched, 'd F Y | H:i') }}</td>
                            <td class="px-3 py-3 fa-digits text-center text-ink-3 dark:text-night-ink-2">{{ self::jdate($search->first_searched) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    <button type="button" wire:click="openDetails({{ $index }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="جزئیات" aria-label="جزئیات">
                                        <x-admin-panel::icon name="users" class="size-4" />
                                    </button>
                                    @can('searches.delete')
                                        <button type="button" wire:click="confirmDelete({{ $index }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف کلیدواژه" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="search" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هیچ جستجویی یافت نشد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->rows->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->rows->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->rows->total()) }}</span> کلیدواژه
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->rows->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->rows->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->rows->hasMorePages())
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

    {{-- مودال جزئیات (پورت back/searches/details) --}}
    <div x-data="{ open: @entangle('showDetails').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:items-center">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-3xl rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold">جزئیات جستجو</h3>
                <button type="button" x-on:click="open=false" class="rounded-lg p-1.5 text-ink-3 hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <div class="mt-3 space-y-1.5 rounded-xl border border-line bg-surface-2 p-4 text-[13px] leading-6 dark:border-night-line dark:bg-night-3">
                <p><strong>کلمه جستجو: </strong> {{ $detailsKeyword }}</p>
                <p><strong>نوع جستجو: </strong>
                    @if ($detailsType === 'products')
                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">محصولات</span>
                    @else
                        <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">پست‌ها</span>
                    @endif
                </p>
                <p><strong>تعداد کل جستجوها: </strong> <span class="fa-digits">{{ number_format($totalSearches) }} بار</span></p>
            </div>

            <h4 class="mt-4 text-sm font-bold">لیست کاربران جستجو کننده:</h4>
            @if (count($detailsRows))
                <div class="mt-2 max-h-80 overflow-auto">
                    <table class="w-full text-start text-[12px]">
                        <thead>
                            <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                                <th class="w-10 px-2.5 py-2.5 text-start font-bold">ردیف</th>
                                <th class="px-2.5 py-2.5 text-start font-bold">نام کاربر</th>
                                <th class="px-2.5 py-2.5 text-start font-bold">ایمیل</th>
                                <th class="px-2.5 py-2.5 text-start font-bold">IP</th>
                                <th class="px-2.5 py-2.5 text-start font-bold">تاریخ جستجو</th>
                                <th class="px-2.5 py-2.5 text-start font-bold">تعداد نتایج</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detailsRows as $i => $row)
                                <tr class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                                    <td class="px-2.5 py-2.5 fa-digits text-ink-3 dark:text-night-ink-2">{{ $i + 1 }}</td>
                                    <td class="px-2.5 py-2.5 font-semibold">{{ $row['name'] }}</td>
                                    <td class="px-2.5 py-2.5" dir="ltr">{{ $row['email'] }}</td>
                                    <td class="px-2.5 py-2.5 font-mono" dir="ltr">{{ $row['ip'] }}</td>
                                    <td class="px-2.5 py-2.5 fa-digits">{{ $row['searched_at'] }}</td>
                                    <td class="px-2.5 py-2.5 fa-digits text-ink-2 dark:text-night-ink-2">{{ $row['results'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-center text-[13px] font-semibold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400">
                    هیچ کاربری این کلمه را جستجو نکرده است
                </div>
            @endif
        </div>
    </div>

    {{-- مودال تایید حذف یک کلیدواژه (پورت destroy) --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                همه رکوردهای این کلمه جستجو حذف می‌شوند.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="destroy" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف گروهی (پورت multipleDestroy) --}}
    <div x-data="{ open: @entangle('confirmingBulkDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                همه رکوردهای کلیدواژه‌های انتخابی حذف می‌شوند.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="multipleDestroy" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="openDetails, destroy, multipleDestroy"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>

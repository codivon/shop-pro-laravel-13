{{-- لیست صفحات + جستجو + حذف تکی/گروهی — پنل جدید (فاز ۶) — پورت back/pages/index --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
            کل صفحات: <span class="fa-digits">{{ number_format($this->totalCount) }}</span>
        </span>
        @can('pages.create')
            <a href="{{ route('np.pages.create') }}" wire:navigate class="ap-btn-primary !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                صفحه جدید
            </a>
        @endcan
    </div>

    {{-- کارت جستجو --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="search" class="size-4.5 text-brand-600 dark:text-brand-400" />
                جستجوی صفحات
                @if ($search)
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
            <form wire:submit="$refresh" class="flex flex-wrap items-end gap-3">
                <div class="min-w-56 flex-1">
                    <label class="mb-1.5 block text-xs font-semibold" for="pages-search">عنوان یا اسلاگ</label>
                    <input id="pages-search" type="text" wire:model.live.debounce.400ms="search"
                           class="ap-input !py-2" placeholder="جستجو در عنوان یا اسلاگ صفحات…">
                </div>
                <button type="submit" wire:click="$set('search', '')" class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                    بازنشانی فیلتر
                </button>
            </form>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-10 px-3 py-3">
                            <button type="button" wire:click="toggleSelectAll" aria-label="انتخاب همه"
                                    class="rounded-lg p-1 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                    title="انتخاب همه">
                                <x-admin-panel::icon name="check-check" class="size-4" />
                            </button>
                        </th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">لینک</th>
                        <th class="px-3 py-3 text-start font-bold">اسلاگ</th>
                        <th class="w-32 px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ ثبت</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->pages as $page)
                        <tr wire:key="page-{{ $page->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" value="{{ $page->id }}"
                                       wire:model.live="selected" aria-label="انتخاب صفحه {{ $page->title }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            </td>
                            <td class="max-w-52 px-3 py-3">
                                <p class="truncate font-semibold" title="{{ $page->title }}">{{ $page->title }}</p>
                            </td>
                            <td class="max-w-64 px-3 py-3">
                                @if (\Illuminate\Support\Facades\Route::has('front.pages.show'))
                                    <code class="block truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr"
                                          title="{{ $page->link() }}">{{ $page->link() }}</code>
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                @endif
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <code class="truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $page->slug ?? '—' }}</code>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($page->published)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400" title="منتشر شده">
                                        <x-admin-panel::icon name="eye" class="size-3" />
                                        منتشر شده
                                    </span>
                                @else
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400" title="پیش‌نویس">
                                        <x-admin-panel::icon name="eye-off" class="size-3" />
                                        پیش‌نویس
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($page->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    @can('pages.update')
                                        <a href="{{ route('np.pages.edit', ['page' => $page]) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                           title="ویرایش صفحه" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </a>
                                    @endcan
                                    @if (function_exists('module_is_active') && module_is_active('SeoAudit'))
                                        @can('pages.details')
                                            <a href="{{ route('admin.seo-audit.pages.details', ['page' => $page]) }}" target="_blank" rel="noopener"
                                               class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-500/10"
                                               title="جزئیات سئو" aria-label="جزئیات سئو صفحه">
                                                <x-admin-panel::icon name="search-check" class="size-4" />
                                            </a>
                                        @endcan
                                    @endif
                                    @can('pages.delete')
                                        <button type="button" wire:click="confirmDelete({{ $page->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف صفحه" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="file-text" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">صفحه‌ای برای نمایش وجود ندارد</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- حذف گروهی --}}
        @can('pages.delete')
            @if (count($selected) > 0)
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 dark:border-night-line">
                    <p class="text-xs font-semibold text-ink-2 dark:text-night-ink-2">
                        ردیف‌های انتخاب‌شده: <span class="fa-digits">{{ number_format(count($selected)) }}</span>
                    </p>
                    <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                            class="ap-btn !py-2 bg-red-600 text-white hover:bg-red-700">
                        <x-admin-panel::icon name="trash-2" class="size-4" />
                        حذف صفحات انتخاب‌شده
                    </button>
                </div>
            @endif
        @endcan

        {{-- صفحه‌بندی --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->pages->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->pages->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->pages->total()) }}</span> صفحه
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->pages->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->pages->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->pages->hasMorePages())
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

    {{-- مودال تایید حذف تکی --}}
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
            <h3 class="text-base font-bold">حذف صفحه</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف صفحه دیگر قادر به بازیابی آن نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف گروهی --}}
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
            <h3 class="text-base font-bold">حذف صفحات انتخاب‌شده</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="fa-digits">{{ number_format(count($selected)) }}</span> صفحه انتخاب شده است؛ با حذف، امکان بازیابی وجود نخواهد داشت.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="deleteSelected" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="search, previousPage, nextPage, gotoPage, delete, deleteSelected"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>

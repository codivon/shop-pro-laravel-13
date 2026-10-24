{{-- لیست فیلترها — فاز ۱۷: بازطراحی مدرن (کارت‌های شبکه‌ای) | پورت back/filters/index --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="relative overflow-hidden rounded-2xl border border-line bg-surface p-5 dark:border-night-line dark:bg-night-2 sm:p-6">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-l from-brand-500/10 via-transparent to-amber-500/10" aria-hidden="true"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-amber-500 text-white shadow-lg shadow-brand-500/25">
                    <x-admin-panel::icon name="filter" class="size-6" />
                </span>
                <div>
                    <h2 class="text-lg font-extrabold">فیلترها</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">مدیریت فیلترهای نمایش محصولات و فروشندگان</p>
                </div>
            </div>
            @can('filters.create')
                <a href="{{ route('np.filters.create') }}" wire:navigate class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    فیلتر جدید
                </a>
            @endcan
        </div>
    </div>

    {{-- شبکه کارت‌ها --}}
    @if ($this->filters->count())
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->filters as $filter)
                <div class="ap-card group flex items-center gap-3 p-4 transition-all hover:border-brand-200 hover:shadow-card dark:hover:border-brand-500/25"
                     wire:key="filter-{{ $filter->id }}">
                    {{-- آیکون --}}
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl transition-colors {{ $filter->is_seller
                        ? 'bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400'
                        : 'bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400' }}">
                        <x-admin-panel::icon name="{{ $filter->is_seller ? 'shopping-bag' : 'filter' }}" class="size-5" />
                    </span>

                    {{-- مشخصات --}}
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-bold">{{ $filter->title }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px]">
                            <span class="ap-badge {{ $filter->is_seller
                                ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400'
                                : 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400' }}">
                                {{ $filter->is_seller ? 'فیلتر فروشندگان' : 'فیلتر محصولات' }}
                            </span>
                            <span class="fa-digits ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                {{ number_format($filter->related_count) }} مورد
                            </span>
                        </div>
                    </div>

                    {{-- عملیات --}}
                    <div class="flex shrink-0 items-center gap-0.5">
                        @can('filters.update')
                            <a href="{{ route('np.filters.edit', ['filter' => $filter]) }}" wire:navigate
                               class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                               title="ویرایش" aria-label="ویرایش {{ $filter->title }}">
                                <x-admin-panel::icon name="pencil" class="size-4" />
                            </a>
                        @endcan
                        @can('filters.delete')
                            <button type="button" wire:click="confirmDelete({{ $filter->id }})"
                                    class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                    title="حذف" aria-label="حذف {{ $filter->title }}">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>

        {{-- صفحه‌بندی --}}
        @if ($this->filters->hasPages())
            <div class="ap-card flex flex-wrap items-center justify-between gap-3 p-4">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->filters->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->filters->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->filters->total()) }}</span> فیلتر
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->filters->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->filters->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
                        @elseif (! is_numeric(trim($link['label'])))
                        @elseif ($link['active'])
                            <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                        @else
                            <button wire:click="gotoPage({{ trim($link['label']) }})"
                                    class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                {{ $link['label'] }}
                            </button>
                        @endif
                    @endforeach

                    @if ($this->filters->hasMorePages())
                        <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @else
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @endif
                </nav>
            </div>
        @endif
    @else
        <div class="ap-card flex flex-col items-center gap-3 p-12 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                <x-admin-panel::icon name="filter" class="size-7 opacity-50" />
            </span>
            <p class="text-sm font-bold">فیلتری ثبت نشده است</p>
            <p class="text-xs text-ink-3 dark:text-night-ink-2">اولین فیلتر را برای مدیریت نمایش محصولات بسازید.</p>
            @can('filters.create')
                <a href="{{ route('np.filters.create') }}" wire:navigate class="ap-btn-primary mt-1 !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    ایجاد اولین فیلتر
                </a>
            @endcan
        </div>
    @endif

    {{-- مودال تایید حذف --}}
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
            <h3 class="text-base font-bold">حذف فیلتر</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف فیلتر، دسته‌بندی‌های مرتبط امکان فیلتر را از دست می‌دهند!
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

    {{-- لودینگ حذف --}}
    <div wire:loading wire:target="delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال حذف…</span>
    </div>
</div>

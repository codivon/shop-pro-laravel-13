{{-- لیست اسلایدرها — پنل جدید (فاز ۶) — پورت ۱:۱ back/sliders/index.blade.php + _slider_card --}}
@php
    $sliders    = $this->sliders;
    $stats      = $this->stats;
    $groupCatalog = $this->groupCatalog;

    // پورت pageSections ویوی قدیمی (۳ صفحه ثابت با لیبل فارسی خودِ مدل)
    $pageSections = \App\Models\Slider::availablePages();

    $pageIcons = [
        'home'    => 'layout-dashboard',
        'posts'   => 'file-text',
        'sellers' => 'store',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-base font-extrabold">مدیریت اسلایدرها</h1>
            <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">اسلایدرها بر اساس صفحه‌ی نمایش، به‌صورت کارت گروه‌بندی شده‌اند.</p>
        </div>
        @can('sliders.create')
            <a href="{{ route('np.sliders.create') }}" wire:navigate class="ap-btn !py-2.5">
                <x-admin-panel::icon name="plus" class="size-4" />
                اسلایدر جدید
            </a>
        @endcan
    </div>

    {{-- آمار (پورت sk-stats قدیمی) --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="images" class="size-5" />
            </span>
            <div>
                <p class="fa-digits text-lg font-extrabold">{{ number_format($stats['total']) }}</p>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">کل اسلایدرها</p>
            </div>
        </div>
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="circle-check" class="size-5" />
            </span>
            <div>
                <p class="fa-digits text-lg font-extrabold">{{ number_format($stats['published']) }}</p>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">منتشر شده</p>
            </div>
        </div>
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="pencil" class="size-5" />
            </span>
            <div>
                <p class="fa-digits text-lg font-extrabold">{{ number_format($stats['drafts']) }}</p>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">پیش‌نویس</p>
            </div>
        </div>
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                <x-admin-panel::icon name="layers" class="size-5" />
            </span>
            <div>
                <p class="fa-digits text-lg font-extrabold">{{ number_format($stats['pagesCovered']) }}</p>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">صفحات پوشش‌داده‌شده</p>
            </div>
        </div>
    </div>

    @if ($stats['total'] > 0)
        {{-- بخش‌های گروه‌بندی‌شده بر اساس صفحه (پورت sk-page-section) --}}
        @foreach ($pageSections as $pageKey => $pageLabel)
            @php
                $pageSliders = $sliders->filter(fn ($slider) => in_array($pageKey, $slider->pages ?: []));
            @endphp
            <section class="ap-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3.5 dark:border-night-line">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="{{ $pageIcons[$pageKey] ?? 'file-text' }}" class="size-4.5" />
                        </span>
                        <div>
                            <h2 class="text-sm font-bold">{{ $pageLabel }}</h2>
                            <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                                اسلایدرهای نمایش‌داده‌شده در {{ $pageLabel }}
                            </p>
                        </div>
                    </div>
                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                        <span class="fa-digits">{{ number_format($pageSliders->count()) }}</span> اسلایدر
                    </span>
                </div>

                <div class="p-4">
                    @if ($pageSliders->count() > 0)
                        <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($pageSliders as $slider)
                                {{-- کارت اسلایدر (پورت _slider_card) --}}
                                <div wire:key="slider-{{ $pageKey }}-{{ $slider->id }}"
                                     class="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition-shadow hover:shadow-pop dark:border-night-line dark:bg-night-2 {{ $slider->published ? '' : 'opacity-90' }}">
                                    {{-- تصویر + نشانگرها --}}
                                    <div class="relative h-36 overflow-hidden bg-surface-2 dark:bg-night-3">
                                        @if ($slider->image)
                                            <img src="{{ asset($slider->image) }}" alt="{{ $slider->title ?: 'اسلایدر' }}"
                                                 class="absolute inset-0 size-full object-cover">
                                        @else
                                            <span class="absolute inset-0 flex items-center justify-center text-ink-3">
                                                <x-admin-panel::icon name="image" class="size-9 opacity-40" />
                                            </span>
                                        @endif

                                        @if ($slider->ordering)
                                            <span class="fa-digits absolute top-2 start-2 flex size-7 items-center justify-center rounded-lg bg-slate-950/65 text-[12px] font-bold text-white backdrop-blur">
                                                {{ number_format($slider->ordering) }}
                                            </span>
                                        @endif

                                        <span class="absolute top-2 end-2 inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-bold backdrop-blur
                                            {{ $slider->published
                                                ? 'bg-emerald-500/90 text-white'
                                                : 'bg-slate-700/90 text-slate-200' }}">
                                            <span class="size-1.5 rounded-full bg-current"></span>
                                            {{ $slider->published ? 'منتشر' : 'پیش‌نویس' }}
                                        </span>
                                    </div>

                                    {{-- بدنه کارت --}}
                                    <div class="flex flex-1 flex-col gap-2.5 p-4">
                                        <h3 class="truncate text-sm font-bold">{{ $slider->title ?: 'بدون عنوان' }}</h3>

                                        @if ($slider->motionTitle)
                                            <p class="flex items-center gap-1.5 text-xs text-ink-2 dark:text-night-ink-2">
                                                <x-admin-panel::icon name="sparkles" class="size-3.5 shrink-0 text-amber-500" />
                                                <span class="truncate">{{ $slider->motionTitle }}</span>
                                            </p>
                                        @endif

                                        @if ($slider->link)
                                            <a href="{{ $slider->link }}" target="_blank" rel="noopener"
                                               class="flex items-center gap-1.5 truncate text-xs text-brand-600 hover:underline dark:text-brand-400" dir="ltr">
                                                <x-admin-panel::icon name="link-2" class="size-3.5 shrink-0" />
                                                <span class="truncate">{{ $slider->link }}</span>
                                            </a>
                                        @endif

                                        {{-- گروه‌ها (پورت sk-group-chip) --}}
                                        @if (count($slider->groups ?: []))
                                            <div class="mt-auto space-y-1.5">
                                                <p class="flex items-center gap-1 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">
                                                    <x-admin-panel::icon name="layers" class="size-3.5" />
                                                    گروه‌ها
                                                </p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach ($slider->groups as $groupKey)
                                                        @php
                                                            $group  = $groupCatalog[$groupKey] ?? null;
                                                            $gLabel = $group['label'] ?? $groupKey;
                                                            $gSize  = $group['size'] ?? '';
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-surface-2 px-2 py-1 text-[11px] font-semibold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                            {{ $gLabel }}
                                                            @if ($gSize)
                                                                <span class="fa-digits text-ink-3 dark:text-night-ink-2">— {{ $gSize }}</span>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- اکشن‌ها (ویرایش/حذف/ترتیب) --}}
                                    <div class="flex items-center justify-between gap-2 border-t border-line px-3 py-2.5 dark:border-night-line">
                                        <div class="flex items-center gap-0.5">
                                            @can('sliders.update')
                                                <button type="button" wire:click="moveUp({{ $slider->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink-2 dark:hover:bg-night-3"
                                                        title="انتقال به بالا" aria-label="انتقال به بالا">
                                                    <x-admin-panel::icon name="chevron-up" class="size-4" />
                                                </button>
                                                <button type="button" wire:click="moveDown({{ $slider->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink-2 dark:hover:bg-night-3"
                                                        title="انتقال به پایین" aria-label="انتقال به پایین">
                                                    <x-admin-panel::icon name="chevron-down" class="size-4" />
                                                </button>
                                            @endcan
                                        </div>
                                        <div class="flex items-center gap-0.5">
                                            @can('sliders.update')
                                                <a href="{{ route('np.sliders.edit', ['slider' => $slider]) }}" wire:navigate
                                                   class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-brand-600 transition-colors hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10"
                                                   title="ویرایش اسلایدر">
                                                    <x-admin-panel::icon name="pencil" class="size-3.5" />
                                                    ویرایش
                                                </a>
                                            @endcan
                                            @can('sliders.delete')
                                                <button type="button" wire:click="confirmDelete({{ $slider->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                        title="حذف اسلایدر" aria-label="حذف">
                                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="{{ $pageIcons[$pageKey] ?? 'file-text' }}" class="size-9 opacity-40" />
                            <p class="text-sm font-medium">هیچ اسلایدری برای این صفحه ثبت نشده است.</p>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    @else
        {{-- حالت خالی کلی (پورت sk-empty) --}}
        <div class="ap-card flex flex-col items-center gap-3 py-16 text-ink-3 dark:text-night-ink-2">
            <span class="flex size-16 items-center justify-center rounded-2xl bg-surface-2 dark:bg-night-3">
                <x-admin-panel::icon name="image" class="size-8 opacity-50" />
            </span>
            <h2 class="text-sm font-bold text-ink-2 dark:text-night-ink-2">هنوز اسلایدری ثبت نشده است</h2>
            <p class="text-xs">اولین اسلایدر خود را ایجاد کنید تا در صفحات سایت نمایش داده شود.</p>
            @can('sliders.create')
                <a href="{{ route('np.sliders.create') }}" wire:navigate class="ap-btn mt-1 !py-2.5">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    ایجاد اسلایدر
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
            <h3 class="text-base font-bold">حذف اسلایدر</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                این اسلایدر حذف شود؟ با حذف اسلایدر دیگر قادر به بازیابی آن نخواهید بود.
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

    {{-- لودینگ --}}
    <div wire:loading wire:target="moveUp, moveDown, delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>

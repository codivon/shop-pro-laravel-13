{{-- ابزارک‌های صفحه اصلی — پنل جدید (بازطراحی مدرن فاز ۹-ه) — کامپوننت WidgetIndex دست‌نخورده --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- ===== سربرگ گرادیانی برند ===== --}}
    <div class="relative overflow-hidden rounded-3xl bg-linear-to-bl from-brand-700 via-brand-600 to-brand-500 p-5 text-white shadow-lg shadow-brand-600/20 sm:p-6">
        <span class="pointer-events-none absolute -top-10 -left-10 size-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-14 left-1/3 size-52 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>

        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <span class="flex size-13 items-center justify-center rounded-2xl bg-white/15 shadow-inner backdrop-blur">
                    <x-admin-panel::icon name="layout-template" class="size-7" />
                </span>
                <div>
                    <h1 class="text-lg font-bold sm:text-xl">{{ $this->pageTitle() }}</h1>
                    <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-white/85">
                        <span>بلوک‌های صفحه اصلی را بچین، فعال/غیرفعال کن و سفارشی کن.</span>
                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-bold">
                            قالب: {{ get_current_theme()['name'] ?? '—' }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- دعوت بزرگ به ویجت‌ساز تعاملی (روت توسط coordinator ثبت می‌شود: np.widgets.builder) --}}
            <a href="{{ route('np.widgets.builder') }}" wire:navigate
               class="group flex items-center gap-2.5 rounded-2xl bg-white px-4 py-3 text-[13px] font-bold text-brand-700 shadow-lg shadow-brand-900/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl active:translate-y-0">
                <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition-transform duration-200 group-hover:rotate-12">
                    <x-admin-panel::icon name="sparkles" class="size-4.5" />
                </span>
                <span>
                    ویجت‌ساز تعاملی
                    <span class="mt-0.5 block text-[10px] font-semibold text-brand-600/80">چیدمان زنده با پیش‌نمایش سایت</span>
                </span>
                <x-admin-panel::icon name="chevron-left" class="size-4 transition-transform duration-200 group-hover:-translate-x-0.5" />
            </a>
        </div>
    </div>

    {{-- هشدار خالی بودن registry (هیچ نوع ابزارکی ثبت نشده) --}}
    @if ($this->isRegistryEmpty())
        <div class="ap-card flex flex-col items-start gap-3 border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-500/20 dark:bg-amber-500/8 sm:flex-row sm:items-center">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                <x-admin-panel::icon name="layout-template" class="size-5" />
            </span>
            <div>
                <p class="text-sm font-bold text-amber-800 dark:text-amber-300">هیچ نوع ابزارکی ثبت نشده است</p>
                <p class="mt-1 text-xs leading-6 text-amber-700/90 dark:text-amber-400/80">
                    انواع ابزارک‌ها از ماژول‌های نصب‌شده (یا config قالب) خوانده می‌شوند؛ وقتی ماژولی ابزارکی ثبت کند،
                    همین صفحه به‌صورت کامل فعال می‌شود. ابزارک‌های قبلاً ذخیره‌شده در پایین لیست می‌مانند.
                </p>
            </div>
        </div>
    @endif

    {{-- ===== گرید کارت‌های ابزارک ===== --}}
    @if (count($this->widgets))
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->widgets as $widget)
                @php
                    $widgetTemplate = \App\Services\WidgetTemplateRegistry::findConfigForPage($this->page, (string) $widget->key);
                    $widgetImg = $widgetTemplate['image_url'] ?? (isset($widgetTemplate['image']) ? theme_asset($widgetTemplate['image']) : null);
                @endphp
                <div wire:key="widget-{{ $widget->id }}"
                     class="ap-card group overflow-hidden p-0 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-950/8 dark:hover:shadow-black/30">
                    {{-- تصویر template --}}
                    <div class="relative aspect-video w-full overflow-hidden bg-surface-2 dark:bg-night-3">
                        @if ($widgetImg)
                            <img src="{{ $widgetImg }}" alt="{{ $this->typeTitle($widget->key) }}"
                                 class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 onerror="this.classList.add('hidden'); this.nextElementSibling?.classList.remove('hidden');">
                            <span class="absolute inset-0 hidden place-items-center text-ink-3 dark:text-night-ink-2">
                                <x-admin-panel::icon name="layout-template" class="size-10" />
                            </span>
                        @else
                            <span class="absolute inset-0 grid place-items-center bg-linear-to-br from-surface-2 to-surface-3 text-ink-3 dark:from-night-3 dark:to-night-2 dark:text-night-ink-2">
                                <x-admin-panel::icon name="layout-template" class="size-10" />
                            </span>
                        @endif

                        {{-- بج ترتیب + وضعیت --}}
                        <span class="absolute top-2.5 start-2.5 flex size-7 items-center justify-center rounded-xl bg-slate-950/55 text-xs font-bold text-white backdrop-blur">
                            <span class="fa-digits">{{ number_format($widget->ordering ?? 0) }}</span>
                        </span>
                        <span class="absolute top-2.5 end-2.5 rounded-full px-2 py-1 text-[10px] font-bold shadow-sm {{ $widget->is_active
                            ? 'bg-emerald-500/90 text-white' : 'bg-slate-950/55 text-white/90 backdrop-blur' }}">
                            {{ $widget->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </div>

                    {{-- بدنه کارت --}}
                    <div class="p-4">
                        <h3 class="truncate text-sm font-bold" title="{{ $widget->title }}">{{ $widget->title }}</h3>
                        <p class="mt-1 flex items-center gap-1.5">
                            <span class="ap-badge bg-surface-3 text-[10px] dark:bg-night-3">{{ $this->typeTitle($widget->key) }}</span>
                            <code class="ltr truncate text-[10px] text-ink-3 dark:text-night-ink-2">{{ $widget->key }}</code>
                        </p>

                        <div class="mt-3.5 flex items-center justify-between gap-2 border-t border-line pt-3.5 dark:border-night-line">
                            {{-- تغییر وضعیت --}}
                            <label class="inline-flex cursor-pointer items-center gap-2" title="{{ $widget->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}">
                                <span class="sr-only">تغییر وضعیت {{ $widget->title }}</span>
                                <input type="checkbox" wire:click="toggleActive({{ $widget->id }})"
                                       {{ $widget->is_active ? 'checked' : '' }}
                                       class="peer sr-only">
                                <span class="relative h-5 w-9 rounded-full bg-surface-3 transition-colors after:absolute after:top-0.5 after:start-0.5 after:size-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-brand-600 peer-checked:after:translate-x-[-1.25rem] dark:bg-night-3 dark:after:bg-night-ink dark:peer-checked:bg-brand-500"></span>
                                <span class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">نمایش در سایت</span>
                            </label>

                            {{-- اکشن‌ها --}}
                            <div class="flex items-center gap-0.5">
                                <button type="button" wire:click="moveUp({{ $widget->id }})"
                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-brand-500/10"
                                        @if ($loop->first) disabled @endif
                                        title="انتقال به بالا" aria-label="انتقال {{ $widget->title }} به بالا">
                                    <x-admin-panel::icon name="chevron-up" class="size-4" />
                                </button>
                                <button type="button" wire:click="moveDown({{ $widget->id }})"
                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-brand-500/10"
                                        @if ($loop->last) disabled @endif
                                        title="انتقال به پایین" aria-label="انتقال {{ $widget->title }} به پایین">
                                    <x-admin-panel::icon name="chevron-down" class="size-4" />
                                </button>
                                <a href="{{ $this->editRoute($widget) }}" wire:navigate
                                   class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                   title="ویرایش ابزارک" aria-label="ویرایش {{ $widget->title }}">
                                    <x-admin-panel::icon name="pencil" class="size-4" />
                                </a>
                                <button type="button" wire:click="confirmDelete({{ $widget->id }})"
                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                        title="حذف ابزارک" aria-label="حذف {{ $widget->title }}">
                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- حالت خالی --}}
        <div class="ap-card flex flex-col items-center gap-3 p-10 text-center">
            <span class="flex size-14 items-center justify-center rounded-3xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400">
                <x-admin-panel::icon name="layout-template" class="size-7" />
            </span>
            <p class="text-sm font-bold">هنوز ابزارکی برای این صفحه ساخته نشده است</p>
            <p class="max-w-sm text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                @if ($this->isRegistryEmpty())
                    با نصب ماژول‌ها یا ثبت ابزارک‌ها در قالب، این صفحه فعال می‌شود.
                @else
                    با ویجت‌ساز تعاملی، اولین بلوک صفحه اصلی را با پیش‌نمایش زنده بسازید.
                @endif
            </p>
            <a href="{{ route('np.widgets.builder') }}" wire:navigate class="ap-btn mt-1 !py-2">
                <x-admin-panel::icon name="sparkles" class="size-4" />
                رفتن به ویجت‌ساز
            </a>
        </div>
    @endif

    {{-- مودال تایید حذف (API کامپوننت دست‌نخورده) --}}
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
            <h3 class="text-base font-bold">حذف ابزارک</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف ابزارک، تنظیمات و گزینه‌های آن هم حذف می‌شوند و قابل بازیابی نیستند.
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
</div>

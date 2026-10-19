{{-- ابزارک‌ها — پنل جدید (فاز ۱۱ بازطراحی: لیست + درگ‌انددراپ + مودال ایجاد/ویرایش) --}}
{{-- کامپوننت WidgetIndex — PostWidgetIndex با همین مارک‌آپ (route ها/متن‌ها از کامپوننت می‌آیند) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-6xl space-y-5"
     data-ap-dnd-tree data-dnd-flat data-dnd-group="widgets" data-dnd-save="reorderWidgets"
     x-init="$el.__apDndWire = $wire; window.ApDnd && window.ApDnd.init($el)">

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
                        <span>بلوک‌های این صفحه را بچین، فعال/غیرفعال کن و سفارشی کن.</span>
                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-bold">
                            قالب: {{ get_current_theme()['name'] ?? '—' }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- ویجت‌ساز تعاملی (در صورت وجود روت) --}}
                @if ($this->builderRoute())
                    <a href="{{ $this->builderRoute() }}" wire:navigate
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
                @endif

                {{-- افزودن ابزارک (مودال دو مرحله‌ای) --}}
                <button type="button" wire:click="openAddFlow"
                        class="flex items-center gap-2 rounded-2xl bg-slate-950/25 px-4 py-3 text-[13px] font-bold text-white ring-1 ring-white/30 backdrop-blur transition-all duration-200 hover:bg-slate-950/35">
                    <x-admin-panel::icon name="plus" class="size-4.5" />
                    افزودن ابزارک
                </button>
            </div>
        </div>
    </div>

    {{-- هشدار خالی بودن registry --}}
    @if ($this->isRegistryEmpty())
        <div class="ap-card flex flex-col items-start gap-3 border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-500/20 dark:bg-amber-500/8 sm:flex-row sm:items-center">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                <x-admin-panel::icon name="layout-template" class="size-5" />
            </span>
            <div>
                <p class="text-sm font-bold text-amber-800 dark:text-amber-300">هیچ نوع ابزارکی ثبت نشده است</p>
                <p class="mt-1 text-xs leading-6 text-amber-700/90 dark:text-amber-400/80">
                    انواع ابزارک‌ها از ماژول‌های نصب‌شده (یا config قالب) خوانده می‌شوند؛ وقتی ماژولی ابزارکی ثبت کند،
                    همین صفحه به‌صورت کامل فعال می‌شود. ابزارک‌های قبلاً ذخیره‌شده در لیست پایین می‌مانند.
                </p>
            </div>
        </div>
    @endif

    {{-- ===== لیست ابزارک‌ها (درگ‌انددراپ) ===== --}}
    <div class="ap-card overflow-hidden p-0">
        @if (count($this->widgets))
            <div class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-[13px] font-bold">
                    <x-admin-panel::icon name="layers" class="size-4 text-brand-600 dark:text-brand-400" />
                    ابزارک‌ها
                    <span class="ap-badge bg-surface-3 text-[10px] dark:bg-night-3">
                        <span class="fa-digits">{{ number_format(count($this->widgets)) }}</span>
                    </span>
                </h2>
                <p class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="grip-vertical" class="size-3.5" />
                    برای تغییر ترتیب، هندل سمت راست را بکشید
                </p>
            </div>

            <ul id="ap-widget-rows" data-dnd-list class="divide-y divide-line/70 dark:divide-night-line/70">
                @foreach ($this->widgets as $widget)
                    <li wire:key="widget-row-{{ $widget->id }}" data-widget-id="{{ $widget->id }}"
                        data-dnd-id="{{ $widget->id }}" class="relative transition-colors duration-200">

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 transition-colors hover:bg-surface-2/60 dark:hover:bg-night-3/40">
                            {{-- هندل درگ (SortableJS — فاز ۱۳) — فقط از همین هندل --}}
                            <span data-dnd-handle
                                  class="touch-none select-none rounded-lg p-1 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-brand-500/10"
                                  title="کشیدن برای تغییر ترتیب" aria-label="کشیدن {{ $widget->title }} برای تغییر ترتیب">
                                <x-admin-panel::icon name="grip-vertical" class="size-4.5" />
                            </span>

                            {{-- شماره ترتیب --}}
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-surface-2 text-xs font-extrabold text-ink-2 dark:bg-night-3 dark:text-night-ink-2" title="ترتیب نمایش">
                                <span class="fa-digits">{{ number_format($widget->ordering ?? 0) }}</span>
                            </span>

                            {{-- عنوان + نوع --}}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13.5px] font-bold" title="{{ $widget->title }}">{{ $widget->title }}</p>
                                <p class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                    <span class="ap-badge bg-brand-50 text-[10px] text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                        {{ $this->typeTitle($widget->key) }}
                                    </span>
                                    <code class="ltr truncate text-[10px] text-ink-3 dark:text-night-ink-2">{{ $widget->key }}</code>
                                </p>
                            </div>

                            {{-- سوییچ وضعیت --}}
                            <label class="inline-flex shrink-0 cursor-pointer items-center gap-2" title="{{ $widget->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}">
                                <span class="sr-only">تغییر وضعیت {{ $widget->title }}</span>
                                <input type="checkbox" wire:click="toggleActive({{ $widget->id }})"
                                       {{ $widget->is_active ? 'checked' : '' }}
                                       class="peer sr-only">
                                <span class="relative h-5 w-9 rounded-full bg-surface-3 transition-colors after:absolute after:top-0.5 after:start-0.5 after:size-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-brand-600 peer-checked:after:translate-x-[-1.25rem] dark:bg-night-3 dark:after:bg-night-ink dark:peer-checked:bg-brand-500"></span>
                                <span class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">{{ $widget->is_active ? 'فعال' : 'غیرفعال' }}</span>
                            </label>

                            {{-- اکشن‌ها --}}
                            <div class="flex shrink-0 items-center gap-0.5">
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
                                <button type="button" wire:click="openEditModal({{ $widget->id }})"
                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                        title="ویرایش ابزارک (مودال)" aria-label="ویرایش {{ $widget->title }}">
                                    <x-admin-panel::icon name="pencil" class="size-4" />
                                </button>
                                <button type="button" wire:click="confirmDelete({{ $widget->id }})"
                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                        title="حذف ابزارک" aria-label="حذف {{ $widget->title }}">
                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <p class="border-t border-line px-4 py-2.5 text-[10px] leading-5 text-ink-3 dark:border-night-line dark:text-night-ink-2">
                ابزارک‌های غیرفعال در سایت نمایش داده نمی‌شوند؛ ترتیب نمایش همین‌جا یا در ویجت‌ساز تعاملی قابل تغییر است.
            </p>
        @else
            {{-- حالت خالی --}}
            <div class="flex flex-col items-center gap-3 p-10 text-center">
                <span class="flex size-14 items-center justify-center rounded-3xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400">
                    <x-admin-panel::icon name="layout-template" class="size-7" />
                </span>
                <p class="text-sm font-bold">هنوز ابزارکی برای این صفحه ساخته نشده است</p>
                <p class="max-w-sm text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                    @if ($this->isRegistryEmpty())
                        با نصب ماژول‌ها یا ثبت ابزارک‌ها در قالب، این صفحه فعال می‌شود.
                    @else
                        با دکمه «افزودن ابزارک» اولین بلوک را بسازید یا از ویجت‌ساز تعاملی استفاده کنید.
                    @endif
                </p>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    @unless ($this->isRegistryEmpty())
                        <button type="button" wire:click="openAddFlow" class="ap-btn mt-1 !py-2">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            افزودن ابزارک
                        </button>
                    @endunless
                    @if ($this->builderRoute())
                        <a href="{{ $this->builderRoute() }}" wire:navigate class="ap-btn-outline mt-1 !py-2">
                            <x-admin-panel::icon name="sparkles" class="size-4" />
                            رفتن به ویجت‌ساز
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ================= مودال ۱: انتخاب نوع ابزارک ================= --}}
    {{-- فاز ۱۶: z-[75] بالاتر از مودال مشخصات (z-[65]) — چون از داخل مودال ویرایش هم باز می‌شود --}}
    <div x-data="{ open: @entangle('showTypeModal') }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[75] flex items-end justify-center p-0 sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open = false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             class="relative flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-3xl border border-line bg-surface shadow-pop sm:rounded-3xl dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true" aria-label="انتخاب نوع ابزارک">
            <div class="flex items-center justify-between border-b border-line px-5 py-3.5 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="layout-template" class="size-4" />
                    </span>
                    <span>
                        انتخاب نوع ابزارک
                        <span class="mt-0.5 block text-[10px] font-medium text-ink-3 dark:text-night-ink-2">{{ $this->isModalEditing ? 'در حال تغییر نوع ابزارک «' . $this->modalTitle . '»' : 'نوع بلوک موردنظر را انتخاب کنید' }}</span>
                    </span>
                </h3>
                <button type="button" x-on:click="open = false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <div class="grid flex-1 grid-cols-2 gap-3 overflow-y-auto p-4 sm:grid-cols-3 md:grid-cols-4">
                @forelse (\App\Services\WidgetTemplateRegistry::mergedForPage($this->page) as $templateKey => $templateConfig)
                    @php
                        $cardImg = $templateConfig['image_url'] ?? (isset($templateConfig['image']) ? theme_asset($templateConfig['image']) : null);
                    @endphp
                    <button type="button"
                            wire:click="pickType({{ \Illuminate\Support\Js::from($templateKey)->toHtml() }})"
                            wire:loading.attr="disabled" wire:target="pickType"
                            class="group flex flex-col gap-2 rounded-2xl border border-line bg-surface p-2.5 text-start transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-500/60 hover:shadow-lg hover:shadow-brand-500/10 dark:border-night-line dark:bg-night-3/60"
                            title="{{ $templateConfig['title'] ?? $templateKey }}">
                        <span class="relative block aspect-video w-full overflow-hidden rounded-xl border border-line bg-surface-2 dark:border-night-line dark:bg-night-3">
                            @if ($cardImg)
                                <img src="{{ $cardImg }}" alt="{{ $templateConfig['title'] ?? $templateKey }}"
                                     class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                     onerror="this.classList.add('hidden'); this.nextElementSibling?.classList.remove('hidden');">
                            @endif
                            <span class="absolute inset-0 hidden place-items-center text-ink-3 dark:text-night-ink-2">
                                <x-admin-panel::icon name="layout-template" class="size-7" />
                            </span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-bold">{{ $templateConfig['title'] ?? $templateKey }}</span>
                            <span class="mt-1 flex items-center gap-1">
                                <code class="ltr truncate text-[9px] text-ink-3 dark:text-night-ink-2">{{ $templateKey }}</code>
                                @if (isset($templateConfig['module']))
                                    <span class="shrink-0 rounded-full bg-brand-50 px-1.5 text-[9px] font-bold text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $templateConfig['module'] }}</span>
                                @endif
                            </span>
                        </span>
                    </button>
                @empty
                    <p class="col-span-full py-8 text-center text-xs text-ink-3 dark:text-night-ink-2">
                        هیچ نوع ابزارکی ثبت نشده است؛ انواع ابزارک‌ها از config قالب یا ماژول‌های نصب‌شده خوانده می‌شوند.
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ================= مودال ۲: مشخصات ابزارک (ایجاد/ویرایش) ================= --}}
    <div x-data="{ open: @entangle('showFormModal') }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[65] flex items-end justify-center p-0 sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" x-show="open" x-on:click="open = false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl border border-line bg-surface shadow-pop sm:rounded-3xl dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true" aria-label="مشخصات ابزارک">

            {{-- سربرگ --}}
            <div class="flex items-center justify-between border-b border-line px-5 py-3.5 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="sliders-horizontal" class="size-4" />
                    </span>
                    <span>
                        {{ $this->isModalEditing ? 'ویرایش ابزارک' : 'مشخصات ابزارک جدید' }}
                        <span class="mt-0.5 block text-[10px] font-medium text-ink-3 dark:text-night-ink-2">
                            {{ $this->isModalEditing ? 'مشخصات را ویرایش و ذخیره کنید' : 'تنظیمات را تکمیل و دکمه «ایجاد» را بزنید' }}
                        </span>
                    </span>
                </h3>
                <button type="button" x-on:click="open = false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            {{-- بدنه --}}
            <div class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-5">

                {{-- نوع ابزارک — باکس کلیک‌شدنی که مودال انتخاب نوع را باز می‌کند (به‌جای select — فاز ۱۱) --}}
                @php
                    $typeTemplate = $this->modalKey !== '' ? \App\Services\WidgetTemplateRegistry::findConfigForPage($this->page, $this->modalKey) : null;
                    $typeImg = $typeTemplate['image_url'] ?? (isset($typeTemplate['image']) ? theme_asset($typeTemplate['image']) : null);
                @endphp
                <button type="button"
                        wire:click="$set('showTypeModal', true)"
                        class="flex w-full items-center gap-3 rounded-2xl border border-brand-200 bg-brand-50/50 p-3 text-start transition-colors hover:border-brand-400 dark:border-brand-500/25 dark:bg-brand-500/8 dark:hover:border-brand-500/50"
                        title="انتخاب/تغییر نوع ابزارک">
                    @if ($typeImg)
                        <img src="{{ $typeImg }}" alt="{{ $typeTemplate['title'] ?? $this->modalKey }}"
                             class="size-12 shrink-0 rounded-xl border border-line object-cover dark:border-night-line"
                             onerror="this.classList.add('hidden');">
                    @else
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl border border-line bg-surface text-ink-3 dark:border-night-line dark:bg-night-3">
                            <x-admin-panel::icon name="layout-template" class="size-6" />
                        </span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block text-[10.5px] font-semibold text-ink-3 dark:text-night-ink-2">نوع ابزارک — برای تغییر کلیک کنید</span>
                        <span class="block truncate text-[13px] font-bold text-brand-800 dark:text-brand-300">
                            @if ($this->modalKey !== '')
                                {{ $typeTemplate['title'] ?? $this->modalKey }}
                                @if (isset($typeTemplate['module']))
                                    <span class="ms-1 rounded-full bg-brand-100 px-1.5 py-0.5 text-[9px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">{{ $typeTemplate['module'] }}</span>
                                @endif
                            @else
                                انتخاب کنید…
                            @endif
                        </span>
                        @if ($this->modalKey !== '')
                            <code class="ltr mt-0.5 block text-[9.5px] text-ink-3 dark:text-night-ink-2">{{ $this->modalKey }}</code>
                        @endif
                    </span>
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-brand-100/70 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">
                        <x-admin-panel::icon name="repeat" class="size-4" />
                    </span>
                </button>
                @error('modalKey') <p class="-mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

                {{-- عنوان + وضعیت + ترتیب --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold" for="ap-idx-title">عنوان ابزارک <span class="text-red-500">*</span></label>
                        <input id="ap-idx-title" type="text" wire:model="modalTitle" class="ap-input" placeholder="مثال: اسلایدر اصلی">
                        @error('modalTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                        <div class="inline-flex w-full rounded-xl border border-line bg-surface-2 p-1 dark:border-night-line dark:bg-night-3" role="group" aria-label="وضعیت ابزارک">
                            <button type="button" wire:click="$set('modalActive', true)"
                                    class="flex-1 rounded-lg px-3 py-1.5 text-xs font-bold transition-all duration-200 {{ $modalActive
                                        ? 'bg-surface text-brand-600 shadow-sm dark:bg-night-2 dark:text-brand-400'
                                        : 'text-ink-3 hover:text-ink-2 dark:text-night-ink-2' }}"
                                    :aria-pressed="{{ $modalActive ? 'true' : 'false' }}">
                                فعال
                            </button>
                            <button type="button" wire:click="$set('modalActive', false)"
                                    class="flex-1 rounded-lg px-3 py-1.5 text-xs font-bold transition-all duration-200 {{ ! $modalActive
                                        ? 'bg-surface text-brand-600 shadow-sm dark:bg-night-2 dark:text-brand-400'
                                        : 'text-ink-3 hover:text-ink-2 dark:text-night-ink-2' }}"
                                    :aria-pressed="{{ $modalActive ? 'false' : 'true' }}">
                                غیرفعال
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="ap-idx-ordering">ترتیب نمایش</label>
                        <input id="ap-idx-ordering" type="number" min="1" wire:model="modalOrdering" class="ap-input fa-digits" placeholder="خودکار — آخر لیست">
                        @error('modalOrdering') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- options داینامیک نوع انتخاب‌شده --}}
                @if ($typeTemplate && ! empty($typeTemplate['options']))
                    <div class="border-t border-line pt-4 dark:border-night-line">
                        <p class="mb-3 flex items-center gap-1.5 text-xs font-bold text-ink-2 dark:text-night-ink">
                            <x-admin-panel::icon name="settings-2" class="size-3.5 text-brand-600 dark:text-brand-400" />
                            تنظیمات تخصصی این ابزارک
                        </p>
                        {{-- فاز ۱۶: تک‌ستونه — هر فیلد یک ردیف کامل و منظم --}}
                        <div class="grid grid-cols-1 gap-4">
                            @include('admin-panel::livewire.admin.widgets.partials.template-options', [
                                'options' => $typeTemplate['options'],
                                'widget'  => $this->modalWidget,
                            ])
                        </div>
                    </div>
                @elseif ($typeTemplate)
                    <p class="rounded-xl border border-line bg-surface-2 p-3 text-xs text-ink-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                        این نوع ابزارک تنظیمات اضافه‌ای ندارد.
                    </p>
                @endif
            </div>

            {{-- فوتر --}}
            <div class="flex items-center justify-between gap-3 border-t border-line bg-surface-2/60 px-5 py-3.5 dark:border-night-line dark:bg-night-3/40">
                <button type="button" x-on:click="open = false" class="ap-btn-ghost !py-2">
                    انصراف
                </button>
                <button type="button" wire:click="saveModal" wire:loading.attr="disabled" wire:target="saveModal"
                        class="ap-btn-primary !py-2.5" @if ($this->modalKey === '') disabled @endif>
                    <span wire:loading.remove wire:target="saveModal" class="inline-flex items-center gap-2">
                        <x-admin-panel::icon name="{{ $this->isModalEditing ? 'check' : 'plus' }}" class="size-4" />
                        {{ $this->isModalEditing ? 'ذخیره تغییرات' : 'ایجاد' }}
                    </span>
                    <span wire:loading wire:target="saveModal" class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                        </svg>
                        در حال ذخیره…
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4">
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

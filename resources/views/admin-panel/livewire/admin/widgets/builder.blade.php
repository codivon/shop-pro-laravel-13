{{-- ویجت‌ساز تمام‌صفحه (فاز ۹-ه + بازطراحی فاز ۱۱) — پنل ابزارک‌ها + درگ‌انددراپ + پیش‌نمایش زنده iframe --}}
{{-- فاز ۱۱: افزودن دو مرحله‌ای (انتخاب نوع → مشخصات → ایجاد) + ویرایش با مودال --}}
<div x-data="{
        device: 'desktop',
        guides: false,
        panelOpen: false,
        previewLoading: true,
        draggingId: null,
        overId: null,
        overAfter: false,
        init() {
            const frame = this.$refs.previewFrame;
            frame.addEventListener('load', () => {
                this.previewLoading = false;
                // پس از هر load دوباره برای ابزارک انتخابی هایلایت بفرست
                const selected = Number(document.getElementById('ap-builder-selected')?.textContent || 0);
                if (selected) { setTimeout(() => this.postToPreview({ apWidgetId: selected, apOutline: true }), 350); }
                if (this.guides) { setTimeout(() => this.postToPreview({ apShowGuides: true }), 400); }
            });
            this.reloadPreview();

            window.addEventListener('ap-refresh-preview', () => this.reloadPreview());
            window.addEventListener('ap-highlight-widget', (e) => {
                const id = Number(e.detail?.id || 0);
                if (! id) { return; }
                if (! this.previewLoading) { this.postToPreview({ apWidgetId: id, apOutline: true }); }
            });
        },
        previewUrl() {
            const base = this.$refs.previewFrame.dataset.baseUrl;
            return base + (base.indexOf('?') > -1 ? '&' : '?') + 'ap-preview=1&ts=' + Date.now();
        },
        reloadPreview() {
            this.previewLoading = true;
            this.$refs.previewFrame.src = this.previewUrl();
        },
        postToPreview(payload) {
            try { this.$refs.previewFrame.contentWindow?.postMessage(payload, '*'); } catch (e) {}
        },
        toggleGuides() {
            this.guides = ! this.guides;
            this.postToPreview({ apShowGuides: this.guides });
        },
        handleDrop(targetId) {
            if (! this.draggingId || Number(this.draggingId) === Number(targetId)) { return; }
            const items = Array.from(document.querySelectorAll('#ap-widget-list [data-widget-id]'));
            const order = items.map((el) => Number(el.dataset.widgetId));
            const from = order.indexOf(Number(this.draggingId));
            if (from === -1) { return; }
            order.splice(from, 1);
            let idx = order.indexOf(Number(targetId));
            if (idx === -1) { return; }
            idx = this.overAfter ? idx + 1 : idx;
            order.splice(idx, 0, Number(this.draggingId));
            this.$wire.reorder(order);
            this.draggingId = null; this.overId = null; this.overAfter = false;
        }
     }"
     class="flex h-screen flex-col overflow-hidden bg-surface-2 text-ink dark:bg-night-1 dark:text-night-ink">

    {{-- وضعیت انتخاب فعلی برای JS (خارج از wire:ignore آپدیت می‌شود) --}}
    <span id="ap-builder-selected" class="hidden" aria-hidden="true">{{ $this->modalWidgetId ?? 0 }}</span>

    {{-- ================= تولبار بالایی (sticky) ================= --}}
    <header class="z-30 flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-line bg-surface px-3 py-2.5 shadow-sm sm:px-5 dark:border-night-line dark:bg-night-2">
        <div class="flex items-center gap-2.5">
            <span class="flex size-9 items-center justify-center rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25">
                <x-admin-panel::icon name="sparkles" class="size-4.5" />
            </span>
            <div class="leading-tight">
                <h1 class="flex items-center gap-2 text-sm font-bold sm:text-base">
                    {{ $this->pageTitle() }}
                </h1>
                <p class="mt-0.5 flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                    <span class="ap-badge bg-surface-3 px-1.5 py-0 text-[10px] dark:bg-night-3">
                        <x-admin-panel::icon name="layout-template" class="size-3" />
                        قالب: {{ get_current_theme()['name'] ?? '—' }}
                    </span>
                    <span class="ap-badge bg-surface-3 px-1.5 py-0 text-[10px] dark:bg-night-3">
                        <x-admin-panel::icon name="hash" class="size-3" />
                        صفحه: {{ $this->pageLabel() }}
                    </span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- سوییچ دستگاه پیش‌نمایش --}}
            <div class="flex items-center rounded-xl border border-line bg-surface-2 p-1 dark:border-night-line dark:bg-night-3" role="group" aria-label="اندازه پیش‌نمایش">
                <button type="button" x-on:click="device = 'desktop'"
                        :class="device === 'desktop' ? 'bg-surface text-brand-600 shadow-sm dark:bg-night-2 dark:text-brand-400' : 'text-ink-3 hover:text-ink-2 dark:text-night-ink-2'"
                        :aria-pressed="device === 'desktop' ? 'true' : 'false'"
                        class="rounded-lg p-1.5 transition-colors" title="دسکتاپ" aria-label="نمایش دسکتاپ">
                    <x-admin-panel::icon name="monitor" class="size-4" />
                </button>
                <button type="button" x-on:click="device = 'tablet'"
                        :class="device === 'tablet' ? 'bg-surface text-brand-600 shadow-sm dark:bg-night-2 dark:text-brand-400' : 'text-ink-3 hover:text-ink-2 dark:text-night-ink-2'"
                        :aria-pressed="device === 'tablet' ? 'true' : 'false'"
                        class="rounded-lg p-1.5 transition-colors" title="تبلت" aria-label="نمایش تبلت">
                    <x-admin-panel::icon name="tablet" class="size-4" />
                </button>
                <button type="button" x-on:click="device = 'mobile'"
                        :class="device === 'mobile' ? 'bg-surface text-brand-600 shadow-sm dark:bg-night-2 dark:text-brand-400' : 'text-ink-3 hover:text-ink-2 dark:text-night-ink-2'"
                        :aria-pressed="device === 'mobile' ? 'true' : 'false'"
                        class="rounded-lg p-1.5 transition-colors" title="موبایل" aria-label="نمایش موبایل">
                    <x-admin-panel::icon name="smartphone" class="size-4" />
                </button>
            </div>

            {{-- سوییچ خطوط راهنما --}}
            <button type="button" x-on:click="toggleGuides()"
                    :class="guides ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400' : 'text-ink-3 hover:text-ink-2 dark:text-night-ink-2 dark:hover:text-night-ink'"
                    :aria-pressed="guides ? 'true' : 'false'"
                    class="flex items-center gap-1.5 rounded-xl border border-line bg-surface-2 px-2.5 py-2 text-xs font-semibold transition-colors dark:border-night-line dark:bg-night-3"
                    title="نمایش خطوط راهنمای ابزارک‌ها در پیش‌نمایش">
                <x-admin-panel::icon name="ruler" class="size-4" />
                <span class="hidden sm:inline">خطوط راهنما</span>
            </button>

            {{-- بازخوانی پیش‌نمایش --}}
            <button type="button" x-on:click="reloadPreview()"
                    class="flex items-center gap-1.5 rounded-xl border border-line bg-surface-2 px-2.5 py-2 text-xs font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2"
                    title="بازخوانی پیش‌نمایش">
                <x-admin-panel::icon name="refresh-cw" class="size-4" />
                <span class="hidden sm:inline">بازخوانی پیش‌نمایش</span>
            </button>

            {{-- بازگشت به پنل / خروج از حالت builder --}}
            <a href="{{ $this->indexRoute() }}" wire:navigate
               class="ap-btn-outline !px-3 !py-2 !text-xs">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به پنل
            </a>
        </div>
    </header>

    {{-- ================= بدنه: پنل ابزارک‌ها + پیش‌نمایش ================= --}}
    <div class="flex min-h-0 flex-1">

        {{-- ----- پنل ابزارک‌ها (راست در RTL؛ در موبایل drawer پایین) ----- --}}
        <aside class="ap-builder-panel relative z-20 flex h-full w-full shrink-0 flex-col border-e border-line bg-surface dark:border-night-line dark:bg-night-2 md:w-[380px] max-md:fixed max-md:inset-x-0 max-md:bottom-0 max-md:z-40 max-md:h-[68vh] max-md:rounded-t-3xl max-md:border-e-0 max-md:border-t max-md:shadow-2xl max-md:transition-transform max-md:duration-300"
               :class="panelOpen ? 'max-md:translate-y-0' : 'max-md:translate-y-full'"
               aria-label="پنل ابزارک‌ها">

            {{-- دستگیره‌ی موبایل --}}
            <div class="flex items-center justify-between border-b border-line px-4 pt-2.5 pb-2 dark:border-night-line md:hidden">
                <span class="mx-auto h-1.5 w-12 rounded-full bg-surface-3 dark:bg-night-3" aria-hidden="true"></span>
            </div>

            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-[13px] font-bold">
                    <x-admin-panel::icon name="layers" class="size-4 text-brand-600 dark:text-brand-400" />
                    ابزارک‌های {{ $this->pageLabel() }}
                    <span class="ap-badge bg-surface-3 text-[10px] dark:bg-night-3">
                        <span class="fa-digits">{{ number_format(count($this->widgets)) }}</span>
                    </span>
                </h2>
                <div class="flex items-center gap-1.5">
                    <button type="button" x-on:click="panelOpen = false"
                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 md:hidden dark:hover:bg-night-3"
                            aria-label="بستن پنل">
                        <x-admin-panel::icon name="x" class="size-4" />
                    </button>
                    <button type="button" wire:click="$set('showAddModal', true)"
                            class="ap-btn !px-3 !py-1.5 !text-xs">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن ابزارک
                    </button>
                </div>
            </div>

            {{-- لیست ابزارک‌ها (درگ‌انددراپ) --}}
            <div id="ap-widget-list" class="ap-builder-scroll flex-1 space-y-2.5 overflow-y-auto p-3">

                @forelse ($this->widgets as $widget)
                    @php
                        $widgetTemplate = $this->templates[$widget->key] ?? null;
                        $isSelected = $this->modalWidgetId === $widget->id;
                    @endphp
                    <div wire:key="builder-widget-{{ $widget->id }}"
                         data-widget-id="{{ $widget->id }}"
                         class="relative rounded-2xl border bg-surface p-2.5 transition-all duration-200 dark:bg-night-2"
                         :class="{
                            'opacity-45': draggingId === {{ $widget->id }},
                            'border-brand-500/60 ring-2 ring-brand-500/30': {{ $isSelected ? 'true' : 'false' }} || overId === {{ $widget->id }},
                            'border-line dark:border-night-line': ! ({{ $isSelected ? 'true' : 'false' }} || overId === {{ $widget->id }}),
                         }"
                         x-on:dragover.prevent="overId = {{ $widget->id }}; overAfter = draggingId !== null && draggingId !== {{ $widget->id }} && (($event.clientY - $el.getBoundingClientRect().top) > $el.offsetHeight / 2)"
                         x-on:dragleave="if (overId === {{ $widget->id }}) { overId = null; overAfter = false; }"
                         x-on:drop.prevent="handleDrop({{ $widget->id }})">

                        {{-- نشانگر جای‌گذاری درگ --}}
                        <span x-show="overId === {{ $widget->id }} && ! overAfter" x-cloak
                              class="absolute inset-x-2 -top-1 z-10 h-1 rounded-full bg-brand-500 shadow-md shadow-brand-500/40" aria-hidden="true"></span>
                        <span x-show="overId === {{ $widget->id }} && overAfter" x-cloak
                              class="absolute inset-x-2 -bottom-1 z-10 h-1 rounded-full bg-brand-500 shadow-md shadow-brand-500/40" aria-hidden="true"></span>

                        <div class="flex items-center gap-2">
                            {{-- هندل درگ --}}
                            <button type="button"
                                    draggable="true"
                                    x-on:dragstart="draggingId = {{ $widget->id }}; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.setData('text/plain', String({{ $widget->id }}))"
                                    x-on:dragend="draggingId = null; overId = null; overAfter = false"
                                    class="cursor-grab touch-none rounded-lg p-1 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink-2 active:cursor-grabbing dark:text-night-ink-2 dark:hover:bg-night-3"
                                    title="جابجایی با درگ" aria-label="جابجایی {{ $widget->title }}">
                                <x-admin-panel::icon name="menu" class="size-4 rotate-90" />
                            </button>

                            {{-- بج ترتیب --}}
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-surface-2 text-xs font-extrabold text-ink-2 dark:bg-night-3 dark:text-night-ink-2" title="ترتیب نمایش">
                                <span class="fa-digits">{{ number_format($widget->ordering ?? 0) }}</span>
                            </span>

                            {{-- عنوان + بج نوع --}}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-bold" title="{{ $widget->title }}">{{ $widget->title }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5">
                                    <span class="truncate text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">{{ $widgetTemplate['title'] ?? $widget->key }}</span>
                                    <span class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $widget->is_active ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-surface-3 text-ink-3 dark:bg-night-3 dark:text-night-ink-2' }}">
                                        {{ $widget->is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </p>
                            </div>

                            {{-- سوییچ فعال/غیرفعال --}}
                            <label class="inline-flex shrink-0 cursor-pointer items-center" title="{{ $widget->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}">
                                <span class="sr-only">تغییر وضعیت {{ $widget->title }}</span>
                                <input type="checkbox" wire:click="toggleActive({{ $widget->id }})"
                                       {{ $widget->is_active ? 'checked' : '' }}
                                       wire:loading.attr="disabled" wire:target="toggleActive({{ $widget->id }})"
                                       class="peer sr-only">
                                <span class="relative h-5 w-9 shrink-0 rounded-full bg-surface-3 transition-colors after:absolute after:top-0.5 after:start-0.5 after:size-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-brand-600 peer-checked:after:translate-x-[-1.25rem] dark:bg-night-3 dark:after:bg-night-ink dark:peer-checked:bg-brand-500"></span>
                            </label>

                            {{-- ویرایش (مودال مشخصات — فاز ۱۱) --}}
                            <button type="button" wire:click="openEditor({{ $widget->id }})"
                                    class="shrink-0 rounded-lg p-1.5 transition-colors {{ $isSelected ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' : 'text-ink-3 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400' }}"
                                    title="ویرایش {{ $widget->title }}" aria-label="ویرایش {{ $widget->title }}">
                                <x-admin-panel::icon name="pencil" class="size-4" />
                            </button>

                            {{-- حذف --}}
                            <button type="button" wire:click="confirmDelete({{ $widget->id }})"
                                    class="shrink-0 rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                    title="حذف {{ $widget->title }}" aria-label="حذف {{ $widget->title }}">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2.5 rounded-2xl border border-dashed border-line p-8 text-center dark:border-night-line">
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                            <x-admin-panel::icon name="layout-template" class="size-6" />
                        </span>
                        <p class="text-[13px] font-bold">هنوز ابزارکی ساخته نشده است</p>
                        <p class="text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">با دکمه‌ی «افزودن ابزارک» اولین بلوک {{ $this->pageLabel() }} را اضافه کنید.</p>
                        <button type="button" wire:click="$set('showAddModal', true)" class="ap-btn mt-1 !py-2 !text-xs">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            افزودن ابزارک
                        </button>
                    </div>
                @endforelse
            </div>

            <p class="border-t border-line px-4 py-2.5 text-[10px] leading-5 text-ink-3 dark:border-night-line dark:text-night-ink-2">
                ترتیب نمایش را با کشیدن دستگیره تغییر دهید؛ ابزارک‌های غیرفعال در سایت نمایش داده نمی‌شوند.
            </p>
        </aside>

        {{-- ----- پیش‌نمایش زنده (iframe سایت فرانت) ----- --}}
        <main class="relative flex min-w-0 flex-1 items-start justify-center overflow-hidden bg-surface-3 p-0 sm:p-4 dark:bg-night-1" aria-label="پیش‌نمایش زنده سایت">
            <div class="ap-builder-frame relative h-full overflow-hidden bg-white shadow-xl shadow-slate-950/10 transition-ax-width] duration-300 ease-out sm:rounded-2xl sm:border sm:border-line dark:sm:border-night-line"
                 :class="{ 'w-full max-w-none': device === 'desktop', 'w-full max-w-[768px]': device === 'tablet', 'w-full max-w-[390px]': device === 'mobile' }">
                <div wire:ignore class="relative size-full">
                    <iframe id="ap-builder-preview"
                            x-ref="previewFrame"
                            data-base-url="{{ $this->previewBaseUrl() }}"
                            class="size-full border-0 bg-white"
                            title="پیش‌نمایش سایت"></iframe>

                    {{-- اسپینر تا load --}}
                    <div x-show="previewLoading" x-cloak
                         class="absolute inset-0 z-10 grid place-items-center bg-white/75 backdrop-blur-sm dark:bg-night-1/75">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="size-9 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                            </svg>
                            <p class="text-xs font-semibold text-ink-2 dark:text-night-ink-2">در حال بارگذاری پیش‌نمایش…</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    {{-- دکمه شناور موبایل (باز کردن drawer پنل) --}}
    <button type="button" x-on:click="panelOpen = true" x-show="! panelOpen" x-cloak
            class="fixed bottom-4 start-4 z-30 flex items-center gap-2 rounded-2xl bg-brand-600 px-4 py-3 text-xs font-bold text-white shadow-lg shadow-brand-600/30 transition-transform active:scale-95 md:hidden"
            aria-label="باز کردن پنل ابزارک‌ها">
        <x-admin-panel::icon name="layers" class="size-4" />
        ابزارک‌ها
        <span class="fa-digits rounded-full bg-white/20 px-1.5 text-[10px]">{{ number_format(count($this->widgets)) }}</span>
    </button>

    {{-- ================= مودال مرحله ۱: انتخاب نوع ابزارک (گرید کارت‌ها) ================= --}}
    <div x-data="{ open: @entangle('showAddModal') }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-end justify-center p-0 sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open = false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             class="relative flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-3xl border border-line bg-surface shadow-pop sm:rounded-3xl dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true" aria-label="انتخاب نوع ابزارک">
            <div class="flex items-center justify-between border-b border-line px-5 py-3.5 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="plus" class="size-4" />
                    </span>
                    <span>
                        افزودن ابزارک به {{ $this->pageLabel() }}
                        <span class="mt-0.5 block text-[10px] font-medium text-ink-3 dark:text-night-ink-2">مرحله ۱ از ۲ — نوع ابزارک را انتخاب کنید</span>
                    </span>
                </h3>
                <button type="button" x-on:click="open = false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <div class="ap-builder-scroll grid flex-1 grid-cols-2 gap-3 overflow-y-auto p-4 sm:grid-cols-3 md:grid-cols-4">
                @forelse ($this->templates as $templateKey => $templateConfig)
                    @php
                        $cardImg = $templateConfig['image_url'] ?? (isset($templateConfig['image']) ? theme_asset($templateConfig['image']) : null);
                    @endphp
                    <button type="button"
                            wire:click="createFromTemplate({{ \Illuminate\Support\Js::from($templateKey)->toHtml() }})"
                            wire:loading.attr="disabled" wire:target="createFromTemplate"
                            class="group flex flex-col gap-2 rounded-2xl border border-line bg-surface p-2.5 text-start transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-500/60 hover:shadow-lg hover:shadow-brand-500/10 dark:border-night-line dark:bg-night-3/60"
                            title="افزودن {{ $templateConfig['title'] ?? $templateKey }}">
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

    {{-- ================= مودال مرحله ۲: مشخصات ابزارک (ایجاد/ویرایش — فاز ۱۱) ================= --}}
    <div x-data="{ open: @entangle('showFormModal') }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-end justify-center p-0 sm:items-center sm:p-4">
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
                        {{ $this->isModalEditing ? 'ویرایش ابزارک' : 'مشخصات ابزارک' }}
                        <span class="mt-0.5 block text-[10px] font-medium text-ink-3 dark:text-night-ink-2">
                            {{ $this->isModalEditing ? 'تنظیمات را ویرایش و ذخیره کنید' : 'مرحله ۲ از ۲ — تنظیمات را تکمیل و دکمه «ایجاد» را بزنید' }}
                        </span>
                    </span>
                </h3>
                <button type="button" x-on:click="open = false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            {{-- بدنه --}}
            <div class="ap-builder-scroll flex-1 space-y-4 overflow-y-auto p-4 sm:p-5">

                {{-- نوع ابزارک (باکس غیرقابل ویرایش — مطابق اسکرین‌شات) --}}
                @php
                    $modalTemplate = $this->isModalEditing
                        ? ($this->modalWidget ? \App\Services\WidgetTemplateRegistry::findConfigForPage($this->page, (string) $this->modalWidget->key) : null)
                        : ($this->draftKey !== '' ? \App\Services\WidgetTemplateRegistry::findConfigForPage($this->page, $this->draftKey) : null);
                    $modalTypeKey = $this->isModalEditing ? (string) ($this->modalWidget?->key ?? '') : $this->draftKey;
                    $modalTypeImg = $modalTemplate['image_url'] ?? (isset($modalTemplate['image']) ? theme_asset($modalTemplate['image']) : null);
                @endphp
                <div class="flex items-center gap-3 rounded-2xl border border-brand-200 bg-brand-50/50 p-3 dark:border-brand-500/25 dark:bg-brand-500/8">
                    @if ($modalTypeImg)
                        <img src="{{ $modalTypeImg }}" alt="{{ $modalTemplate['title'] ?? $modalTypeKey }}"
                             class="size-12 shrink-0 rounded-xl border border-line object-cover dark:border-night-line"
                             onerror="this.classList.add('hidden');">
                    @else
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl border border-line bg-surface text-ink-3 dark:border-night-line dark:bg-night-3">
                            <x-admin-panel::icon name="layout-template" class="size-6" />
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-[10.5px] font-semibold text-ink-3 dark:text-night-ink-2">نوع ابزارک</p>
                        <p class="truncate text-[13px] font-bold text-brand-800 dark:text-brand-300">
                            {{ $modalTemplate['title'] ?? $modalTypeKey }}
                            @if (isset($modalTemplate['module']))
                                <span class="ms-1 rounded-full bg-brand-100 px-1.5 py-0.5 text-[9px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">{{ $modalTemplate['module'] }}</span>
                            @endif
                        </p>
                    </div>
                    @if ($this->isModalEditing)
                        <button type="button" wire:click="openTypePickerForEdit"
                                class="shrink-0 rounded-lg border border-line bg-surface px-2.5 py-1.5 text-[10px] font-bold text-ink-3 transition-colors hover:text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2"
                                title="تغییر نوع">
                            تغییر نوع
                        </button>
                    @else
                        <button type="button" x-on:click="open = false; $wire.$set('showAddModal', true)"
                                class="shrink-0 rounded-lg border border-brand-300 bg-surface px-2.5 py-1.5 text-[10px] font-bold text-brand-700 transition-colors hover:bg-brand-50 dark:border-brand-500/40 dark:bg-night-3 dark:text-brand-400"
                                title="بازگشت به انتخاب نوع">
                            تغییر نوع
                        </button>
                    @endif
                </div>

                {{-- عنوان + وضعیت + ترتیب (سه‌گانه اسکرین‌شات) --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold" for="ap-modal-title">عنوان ابزارک <span class="text-red-500">*</span></label>
                        <input id="ap-modal-title" type="text" wire:model="modalTitle" class="ap-input" placeholder="مثال: اسلایدر اصلی">
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
                        <label class="mb-1.5 block text-xs font-semibold" for="ap-modal-ordering">ترتیب نمایش</label>
                        <input id="ap-modal-ordering" type="number" min="1" wire:model="modalOrdering" class="ap-input fa-digits" placeholder="خودکار — آخر لیست">
                        @error('modalOrdering') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- options داینامیک نوع انتخاب‌شده --}}
                @if ($modalTemplate && ! empty($modalTemplate['options']))
                    <div class="border-t border-line pt-4 dark:border-night-line">
                        <p class="mb-3 flex items-center gap-1.5 text-xs font-bold text-ink-2 dark:text-night-ink">
                            <x-admin-panel::icon name="settings-2" class="size-3.5 text-brand-600 dark:text-brand-400" />
                            تنظیمات تخصصی این ابزارک
                        </p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @include('admin-panel::livewire.admin.widgets.partials.template-options', [
                                'options' => $modalTemplate['options'],
                                'widget'  => $this->modalWidget,
                            ])
                        </div>
                    </div>
                @elseif ($modalTemplate)
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
                @if ($this->isModalEditing)
                    <button type="button" wire:click="saveOptions" wire:loading.attr="disabled" wire:target="saveOptions"
                            class="ap-btn-primary !py-2.5">
                        <span wire:loading.remove wire:target="saveOptions" class="inline-flex items-center gap-2">
                            <x-admin-panel::icon name="check" class="size-4" />
                            ذخیره تغییرات
                        </span>
                        <span wire:loading wire:target="saveOptions" class="inline-flex items-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                            </svg>
                            در حال ذخیره…
                        </span>
                    </button>
                @else
                    <button type="button" wire:click="createWidget" wire:loading.attr="disabled" wire:target="createWidget"
                            class="ap-btn-primary !py-2.5">
                        <span wire:loading.remove wire:target="createWidget" class="inline-flex items-center gap-2">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            ایجاد
                        </span>
                        <span wire:loading wire:target="createWidget" class="inline-flex items-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                            </svg>
                            در حال ایجاد…
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ================= مودال تایید حذف (فارسی) ================= --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[80] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open = false" x-transition.opacity aria-hidden="true"></div>
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
                با حذف ابزارک، تنظیمات و گزینه‌های آن هم حذف می‌شوند و قابل بازیابی نیستند. آیا مطمئن هستید؟
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open = false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>
</div>

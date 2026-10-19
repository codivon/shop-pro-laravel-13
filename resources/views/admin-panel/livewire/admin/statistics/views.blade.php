{{-- آمار بازدیدها — پورت views/index قدیمی (فاز ۷-ج۲) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- کارت‌های آمار (کل/میانگین) --}}
    <section class="stagger grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="eye" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کل بازدیدها</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($viewsMeta['total'] ?? '۰') }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="chart-line" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">میانگین بازدیدها</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($viewsMeta['avg'] ?? '۰') }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-500/12 dark:text-indigo-400">
                    <x-admin-panel::icon name="users" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کل بازدیدکنندگان</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($viewersMeta['total'] ?? '۰') }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="chart-pie" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">میانگین بازدیدکنندگان</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($viewersMeta['avg'] ?? '۰') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- فیلتر بازه (پورت filter-tabs قدیمی) --}}
    <div class="ap-card p-4 sm:p-5">
        <form wire:submit="applyFilters" class="flex flex-wrap items-end gap-4">
            <div class="flex flex-wrap gap-1.5">
                @foreach (['daily' => 'روزانه', 'weekly' => 'هفتگی', 'monthly' => 'ماهانه', 'yearly' => 'سالانه'] as $key => $label)
                    <button type="button" wire:click="applyPeriod('{{ $key }}')"
                            class="rounded-xl px-3.5 py-2 text-[13px] font-bold transition-colors
                                {{ $period === $key
                                    ? 'bg-brand-600 text-white shadow-sm'
                                    : 'bg-surface-2 text-ink-2 hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="ms-auto flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="views-from-date">از تاریخ</label>
                    <input id="views-from-date" type="text" wire:model="from_date" data-jdp data-jdp-only-date="true"
                           autocomplete="off" dir="ltr"
                           class="ap-input !py-2 !pe-9 font-mono" placeholder="1400-01-01">
                    @error('from_date') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="views-to-date">تا تاریخ</label>
                    <input id="views-to-date" type="text" wire:model="to_date" data-jdp data-jdp-only-date="true"
                           autocomplete="off" dir="ltr"
                           class="ap-input !py-2 !pe-9 font-mono" placeholder="1400-12-29">
                    @error('to_date') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="applyFilters"
                        class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="filter" class="size-4" />
                    اعمال بازه
                </button>
            </div>
        </form>
    </div>

    {{-- نمودار بازدیدها --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <div class="flex items-center gap-2.5">
                <x-admin-panel::icon name="chart-line" class="size-4.5 text-brand-600 dark:text-brand-400" />
                <h3 class="text-sm font-bold">تعداد بازدیدها</h3>
            </div>
            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">
                بازه: <span class="fa-digits font-semibold">{{ ['daily' => 'روزانه', 'weekly' => 'هفتگی', 'monthly' => 'ماهانه', 'yearly' => 'سالانه'][$period] ?? 'روزانه' }}</span>
            </span>
        </header>
        <div data-chart="stats-views" data-options="{{ json_encode($this->viewsChartOptions()) }}" class="h-96 w-full"></div>
    </section>

    {{-- نمودار بازدیدکنندگان --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <div class="flex items-center gap-2.5">
                <x-admin-panel::icon name="users" class="size-4.5 text-indigo-600 dark:text-indigo-400" />
                <h3 class="text-sm font-bold">تعداد بازدیدکنندگان</h3>
            </div>
        </header>
        <div data-chart="stats-viewers" data-options="{{ json_encode($this->viewersChartOptions()) }}" class="h-96 w-full"></div>
    </section>
</div>

@script
<script>
    /* به‌روزرسانی نمودارها پس از تغییر فیلترها — داده از کامپوننت dispatch می‌شود */
    $wire.on('chart-update', (payload) => {
        const charts = (payload && payload.charts) ? payload.charts : {};

        Object.keys(charts).forEach((key) => {
            const el = document.querySelector('[data-chart="' + key + '"]');
            if (!el || !window.ApChart || !window.ApECharts) return;

            const options = window.ApChart.resolveFormatters(charts[key]);
            let chart = window.ApChart.instances[key];

            if (!chart || chart.getDom() !== el) {
                chart = window.ApChart.instances[key] = window.ApECharts.init(el);
            }

            chart.setOption(options, true);
            chart.resize();
        });
    });
</script>
@endscript

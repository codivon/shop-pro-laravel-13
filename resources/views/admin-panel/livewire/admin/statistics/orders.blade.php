{{-- آمار سفارش‌ها — پورت orders/index قدیمی با ۴ تب (فاز ۷-ج۲) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5"
     x-data="{ tab: '{{ $tab }}', resize(key) { $nextTick(() => window.ApChart?.instances[key]?.resize()) } }">

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
                    <label class="mb-1.5 block text-xs font-semibold" for="orders-from-date">از تاریخ</label>
                    <input id="orders-from-date" type="text" wire:model="from_date" data-jdp data-jdp-only-date="true"
                           autocomplete="off" dir="ltr"
                           class="ap-input !py-2 !pe-9 font-mono" placeholder="1400-01-01">
                    @error('from_date') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="orders-to-date">تا تاریخ</label>
                    <input id="orders-to-date" type="text" wire:model="to_date" data-jdp data-jdp-only-date="true"
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

    {{-- تب‌ها (پورت orderstab قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <header class="flex flex-wrap items-center gap-1.5 border-b border-line px-4 py-3 dark:border-night-line">
            @foreach (['values' => 'ارزش سفارشات', 'counts' => 'تعداد سفارشات', 'users' => 'تعداد کاربران سفارش دهنده', 'products' => 'تعداد محصولات سفارشات'] as $key => $label)
                <button type="button"
                        x-on:click="tab = '{{ $key }}'; $wire.loadTab('{{ $key }}'); resize('stats-orders-{{ $key }}')"
                        class="rounded-xl px-3.5 py-2 text-[13px] font-bold transition-colors"
                        :class="tab === '{{ $key }}'
                            ? 'bg-brand-600 text-white shadow-sm'
                            : 'bg-surface-2 text-ink-2 hover:bg-surface-3 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-night-2'">
                    {{ $label }}
                </button>
            @endforeach
        </header>

        @foreach (['values' => 'value', 'counts' => 'count', 'users' => 'user', 'products' => 'product'] as $key => $pane)
            <div x-show="tab === '{{ $key }}'" x-cloak>
                {{-- متریک‌ها (پورت ردیف‌های کل/میانگین/موفق/ناموفق قدیمی) --}}
                <div class="grid grid-cols-2 gap-3 px-5 pt-5 lg:grid-cols-4">
                    <div class="rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                        <p class="text-[11px] font-medium text-ink-3 dark:text-night-ink-2">کل</p>
                        <p class="fa-digits mt-1 text-lg font-extrabold">{{ $this->fa($metas[$key]['total'] ?? '۰') }}</p>
                    </div>
                    <div class="rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                        <p class="text-[11px] font-medium text-ink-3 dark:text-night-ink-2">میانگین</p>
                        <p class="fa-digits mt-1 text-lg font-extrabold">{{ $this->fa($metas[$key]['avg'] ?? '۰') }}</p>
                    </div>
                    <div class="rounded-xl border border-brand-200 bg-brand-50 p-3.5 dark:border-brand-500/20 dark:bg-brand-500/10">
                        <p class="text-[11px] font-medium text-brand-700 dark:text-brand-400">موفق</p>
                        <p class="fa-digits mt-1 text-lg font-extrabold text-brand-700 dark:text-brand-400">{{ $this->fa($metas[$key]['total_success'] ?? '۰') }}</p>
                    </div>
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 dark:border-red-500/20 dark:bg-red-500/10">
                        <p class="text-[11px] font-medium text-red-600 dark:text-red-400">ناموفق</p>
                        <p class="fa-digits mt-1 text-lg font-extrabold text-red-600 dark:text-red-400">{{ $this->fa($metas[$key]['total_fail'] ?? '۰') }}</p>
                    </div>
                </div>

                <div data-chart="stats-orders-{{ $key }}" data-options="{{ json_encode($this->chartOptions($key)) }}" class="h-96 w-full p-2"></div>
            </div>
        @endforeach
    </div>
</div>

@script
<script>
    /* به‌روزرسانی نمودارها پس از تغییر فیلتر/تب — داده از کامپوننت dispatch می‌شود */
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

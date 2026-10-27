{{-- صفحهٔ مشترک «بازدید/بازدیدکنندهٔ یکتا» (فاز ۱۰۸) — $typeLabel از کامپوننت --}}
<div class="mx-auto max-w-7xl space-y-5" x-data>

    <div class="ap-card p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                    <x-admin-panel::icon name="{{ $typeLabel === 'بازدید' ? 'eye' : 'users' }}" class="size-5" />
                </span>
                <div>
                    <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">{{ $typeLabel }} فروشگاه</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">بر اساس بازهٔ انتخابی</p>
                </div>
            </div>

            {{-- انتخاب دوره (State در URL — مثل فیلترهای پنل) --}}
            <nav class="inline-flex rounded-xl border border-line bg-surface-2 p-1 dark:border-night-line dark:bg-night-3" aria-label="بازهٔ زمانی">
                @foreach (['weekly' => '۷ روز', 'monthly' => '۱۲ ماه', 'yearly' => '۵ سال'] as $key => $label)
                    <a href="?period={{ $key }}" wire:navigate
                       class="rounded-lg px-3 py-1.5 text-[12px] font-bold transition-all {{ $period === $key ? 'bg-brand-600 text-white' : 'text-ink-2 hover:text-ink dark:text-night-ink-2 dark:hover:text-night-ink' }}"
                       role="tab" aria-selected="{{ $period === $key ? 'true' : 'false' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">کل در بازه</p>
                <p class="mt-1 text-lg font-extrabold text-ink dark:text-night-ink">{{ $total }}</p>
            </div>
            <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">میانگین هر بازه</p>
                <p class="mt-1 text-lg font-extrabold text-ink dark:text-night-ink">{{ $avg }}</p>
            </div>
        </div>

        <div x-data x-init="if (window.ApECharts) { var c = ApECharts.init($refs.chart); c.setOption({
            grid: { top: 12, right: 12, bottom: 26, left: 48 },
            xAxis: { type: 'category', data: @js($chartLabels), axisLabel: { fontSize: 10, rotate: 30 } },
            yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
            tooltip: { trigger: 'axis' },
            series: [{ type: 'line', smooth: true, data: @js($chartData), areaStyle: { opacity: 0.12 }, lineStyle: { width: 3 }, itemStyle: { color: '#8b5cf6' } }],
        }); }" class="mt-4">
            <div x-ref="chart" class="h-64 w-full" dir="ltr" aria-hidden="true"></div>
        </div>
    </div>

    <a href="{{ route('seller.statistics.index') }}" wire:navigate class="ap-btn-outline !py-2 !text-xs">
        <x-admin-panel::icon name="arrow-right" class="size-4" />
        بازگشت به آمار کامل
    </a>
</div>

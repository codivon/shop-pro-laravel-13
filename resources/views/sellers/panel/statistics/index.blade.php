{{-- آمار و گزارش فروشگاه (فاز ۱۰۸) — پورت sellers-old/panel/statistics/index.blade.php
     رشته‌ها/IDهای تست‌شدهٔ فاز ۱۰۶ عیناً حفظ شده‌اند: «آمار بازدید فروشگاه»،
     «بازدیدکنندگان یکتا»، stx-daily-chart، «گزارش فروش (۳۰ روز اخیر)»،
     «روند درآمد ۳۰ روز اخیر»، stx-revenue-chart، «پرفروش‌ترین محصولات»، «بازدید → سفارش» --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="ap-card p-5 sm:p-6">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                <x-admin-panel::icon name="chart-spline" class="size-5.5" />
            </span>
            <div class="min-w-0">
                <h1 class="text-base font-extrabold text-ink dark:text-night-ink">آمار و گزارش فروشگاه</h1>
                <p class="text-[11px] font-semibold leading-5 text-ink-3 dark:text-night-ink-2">
                    گزارش فروش و آمار بازدید فروشگاه شما — منبع فروش: آیتم‌های سفارش‌های پرداخت‌شده؛ منبع بازدید: رهگیری صفحات فروشگاه.
                </p>
            </div>
        </div>
    </div>

    {{-- کارت‌های آمار بازدید --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['label' => 'بازدید امروز', 'value' => number_format($todayVisits), 'icon' => 'calendar-days', 'accent' => 'bg-brand-500/12 text-brand-600 dark:text-brand-400'],
            ['label' => 'بازدید دیروز', 'value' => number_format($yesterdayVisits), 'icon' => 'calendar-minus', 'accent' => 'bg-sky-500/12 text-sky-600 dark:text-sky-400'],
            ['label' => 'کل بازدیدها', 'value' => number_format($totalVisits), 'icon' => 'eye', 'accent' => 'bg-violet-500/12 text-violet-600 dark:text-violet-400'],
            ['label' => 'بازدیدکنندگان یکتا', 'value' => number_format($uniqueVisitors), 'icon' => 'users', 'accent' => 'bg-amber-500/12 text-amber-600 dark:text-amber-400'],
        ] as $card)
            <div class="ap-card p-4">
                <span class="flex size-9 items-center justify-center rounded-xl {{ $card['accent'] }}">
                    <x-admin-panel::icon name="{{ $card['icon'] }}" class="size-4.5" />
                </span>
                <p class="fa-digits mt-3 text-xl font-extrabold text-ink dark:text-night-ink">{{ $card['value'] }}</p>
                <p class="mt-0.5 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- روند و تفکیک --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="ap-card p-5 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">روند بازدید ۷ روز اخیر</h2>
                <span class="rounded-xl px-2.5 py-1 text-[11px] font-bold
                            {{ $changePercent >= 0 ? 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/12 text-red-600 dark:text-red-400' }}">
                    {{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent, 1) }}٪ نسبت به دیروز
                </span>
            </div>
            <div x-data x-init="if (window.ApECharts) { var c = ApECharts.init($refs.chart); c.setOption({
                grid: { top: 12, right: 12, bottom: 26, left: 48 },
                xAxis: { type: 'category', data: @json(collect($dailyVisits)->pluck('date')), axisLabel: { fontSize: 10 } },
                yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
                tooltip: { trigger: 'axis' },
                series: [{ type: 'bar', barMaxWidth: 26, data: @json(collect($dailyVisits)->pluck('count')), itemStyle: { color: '#8b5cf6', borderRadius: [6, 6, 0, 0] } }],
            }); }" class="mt-4">
                <div id="stx-daily-chart" x-ref="chart" class="h-64 w-full" dir="ltr" aria-hidden="true"></div>
            </div>
        </div>

        <div class="ap-card p-5">
            <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">تفکیک بازدید امروز</h2>
            <div class="mt-4 space-y-3">
                @foreach ([
                    ['label' => 'صفحات فروشگاه', 'value' => $todayPathVisits, 'accent' => 'bg-brand-500/12 text-brand-600 dark:text-brand-400', 'icon' => 'store'],
                    ['label' => 'صفحات محصول', 'value' => $todayProductVisits, 'accent' => 'bg-violet-500/12 text-violet-600 dark:text-violet-400', 'icon' => 'package'],
                    ['label' => 'صفحات عمومی', 'value' => $todayPageVisits, 'accent' => 'bg-amber-500/12 text-amber-600 dark:text-amber-400', 'icon' => 'layout-grid'],
                ] as $row)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-line p-3 dark:border-night-line">
                        <span class="flex items-center gap-2 text-[12px] font-bold text-ink-2 dark:text-night-ink-2">
                            <span class="flex size-8 items-center justify-center rounded-lg {{ $row['accent'] }}">
                                <x-admin-panel::icon name="{{ $row['icon'] }}" class="size-4" />
                            </span>
                            {{ $row['label'] }}
                        </span>
                        <span class="fa-digits text-sm font-extrabold text-ink dark:text-night-ink">{{ number_format($row['value']) }}</span>
                    </div>
                @endforeach
                <div class="rounded-xl bg-surface-2 p-3 text-center dark:bg-night-3">
                    <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">میانگین روزانه (۳۰ روز اخیر)</p>
                    <p class="fa-digits mt-0.5 text-lg font-extrabold text-ink dark:text-night-ink">{{ number_format($averageVisits) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= گزارش فروش (فاز ۱۰۶-۲ — گیت seller_analytics_enabled) ================= --}}
    @if (! is_null($sales))
        <div class="ap-card p-5 sm:p-6">
            <h2 class="flex items-center gap-2 text-sm font-extrabold text-ink dark:text-night-ink">
                <x-admin-panel::icon name="shopping-bag" class="size-4.5 text-brand-600 dark:text-brand-400" />
                گزارش فروش (۳۰ روز اخیر)
            </h2>

            {{-- کارت‌های خلاصهٔ فروش --}}
            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'درآمد سفارش‌های موفق', 'value' => number_format($sales['revenue']).' تومان'],
                    ['label' => 'سفارش‌های پرداخت‌شده', 'value' => number_format($sales['orders'])],
                    ['label' => 'اقلام فروخته‌شده', 'value' => number_format($sales['items'])],
                    ['label' => 'میانگین هر سفارش', 'value' => number_format($sales['avgOrder']).' تومان'],
                ] as $card)
                    <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                        <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">{{ $card['label'] }}</p>
                        <p class="fa-digits mt-1 text-[15px] font-extrabold text-ink dark:text-night-ink">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-5">
                {{-- نمودار درآمد --}}
                <div class="ap-card p-4 lg:col-span-3">
                    <h3 class="text-[13px] font-extrabold text-ink dark:text-night-ink">روند درآمد ۳۰ روز اخیر</h3>
                    <div x-data x-init="if (window.ApECharts) { var c = ApECharts.init($refs.chart); c.setOption({
                        grid: { top: 12, right: 12, bottom: 26, left: 60 },
                        xAxis: { type: 'category', data: @js($sales['chartLabels']), axisLabel: { fontSize: 10 } },
                        yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
                        tooltip: { trigger: 'axis' },
                        series: [{ type: 'line', smooth: true, data: @js($sales['chartData']), areaStyle: { opacity: 0.12 }, lineStyle: { width: 3 }, itemStyle: { color: '#10b981' } }],
                    }); }" class="mt-3">
                        <div id="stx-revenue-chart" x-ref="chart" class="h-64 w-full" dir="ltr" aria-hidden="true"></div>
                    </div>
                </div>

                {{-- پرفروش‌ترین‌ها + بازدید→سفارش --}}
                <div class="ap-card p-4 lg:col-span-2">
                    <h3 class="text-[13px] font-extrabold text-ink dark:text-night-ink">پرفروش‌ترین محصولات</h3>
                    @if ($sales['bestsellers']->isEmpty())
                        <p class="mt-3 rounded-xl bg-surface-2 p-4 text-center text-[12px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                            هنوز فروشی در ۳۰ روز اخیر ثبت نشده است.
                        </p>
                    @else
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-[12px]">
                                <thead class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                    <tr>
                                        <th class="rounded-s-xl p-2.5 text-start font-bold">محصول</th>
                                        <th class="p-2.5 text-start font-bold">تعداد</th>
                                        <th class="rounded-e-xl p-2.5 text-start font-bold">درآمد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sales['bestsellers'] as $row)
                                        <tr class="border-t border-line dark:border-night-line">
                                            <td class="max-w-44 p-2.5 font-bold text-ink dark:text-night-ink">
                                                @if ($row->product && $row->product->slug)
                                                    {{-- نام روت با پیشوند گروه بیرونی front. — درس فاز ۹۰/۱۰۶ الف --}}
                                                    <a href="{{ route('front.products.show', ['product' => $row->product->slug]) }}" target="_blank" rel="noopener" title="{{ $row->product->title }}" class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">
                                                        {{ \Illuminate\Support\Str::limit($row->product->title, 34) }}
                                                    </a>
                                                @else
                                                    {{ \Illuminate\Support\Str::limit($row->product->title ?? 'محصول', 34) }}
                                                @endif
                                            </td>
                                            <td class="fa-digits p-2.5 font-extrabold text-ink dark:text-night-ink">{{ number_format($row->qty) }}</td>
                                            <td class="fa-digits p-2.5 font-extrabold text-ink dark:text-night-ink">{{ number_format($row->revenue) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- بازدید → سفارش (فاز ۱۰۶-۲) — دامنهٔ محصولات خودِ فروشنده --}}
                    <div class="mt-4 rounded-xl border border-line p-3.5 dark:border-night-line">
                        <div class="flex items-center justify-between gap-2">
                            <p class="flex items-center gap-1.5 text-[12px] font-bold text-ink-2 dark:text-night-ink-2">
                                <x-admin-panel::icon name="repeat" class="size-4 text-brand-600 dark:text-brand-400" />
                                بازدید → سفارش
                            </p>
                            <p class="fa-digits text-[13px] font-extrabold text-ink dark:text-night-ink">
                                {{ is_null($sales['conversionRate']) ? '—' : number_format($sales['conversionRate'], 1).'٪' }}
                            </p>
                        </div>
                        <p class="mt-1 text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">
                            <span class="fa-digits">{{ number_format($sales['views']) }}</span> بازدید محصول / <span class="fa-digits">{{ number_format($sales['orders']) }}</span> سفارش موفق
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('seller.statistics.viewCounts') }}" wire:navigate class="ap-btn-outline !py-2 !text-xs">آمار بازدیدها</a>
        <a href="{{ route('seller.statistics.viewerCounts') }}" wire:navigate class="ap-btn-outline !py-2 !text-xs">بازدیدکنندگان یکتا</a>
    </div>
</div>

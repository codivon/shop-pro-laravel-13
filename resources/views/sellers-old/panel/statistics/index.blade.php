@extends('sellers-old.panel.layouts.master')

@section('content')
    @php
        // فاز ۱۰۶ الف — برچسب‌های نمودار به تقویم جلالی (یک‌بار در سرور، نه در JS)
        $chartLabels = $dailyVisits->map(fn ($row) => jdate(\Carbon\Carbon::parse($row->date))->format('m/d'))->values()->all();
        $chartData = $dailyVisits->map(fn ($row) => (int) $row->count)->values()->all();

        $trendLabel = null;
        $trendClass = 'muted';
        if ($changePercent > 0) {
            $trendLabel = '▲ '.number_format($changePercent, 1).'٪ نسبت به دیروز';
            $trendClass = 'up';
        } elseif ($changePercent < 0) {
            $trendLabel = '▼ '.number_format(abs($changePercent), 1).'٪ نسبت به دیروز';
            $trendClass = 'down';
        } else {
            $trendLabel = 'بدون تغییر نسبت به دیروز';
        }

        // فاز ۱۰۶-۲ — برچسب‌های نمودار درآمد (جلالی، در سرور)
        $salesChartLabels = is_array($sales) ? ($sales['chartLabels'] ?? []) : [];
        $salesChartData = is_array($sales) ? ($sales['chartData'] ?? []) : [];
    @endphp

    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">آمار و گزارش فروشگاه</span>
                <span class="c-content-page__header-desc">گزارش فروش و آمار بازدید فروشگاه شما — منبع فروش: آیتم‌های سفارش‌های پرداخت‌شده؛ منبع بازدید: رهگیری صفحات فروشگاه.</span>
            </div>
        </div>
    </div>

    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">

        <style>
            /* اسکوپ‌شده — بدون تداخل با بقیهٔ پنل */
            .stx-card { background: #fff; border: 1px solid #eef0f4; border-radius: 12px; padding: 16px 18px; height: 100%; }
            .stx-card__label { font-size: 13px; color: #6b7280; margin-bottom: 6px; }
            .stx-card__value { font-size: 24px; font-weight: 700; color: #1f2937; line-height: 1.3; }
            .stx-card__trend { font-size: 12px; margin-top: 6px; display: inline-block; }
            .stx-card__trend.up { color: #28a745; }
            .stx-card__trend.down { color: #dc3545; }
            .stx-card__trend.muted { color: #9ca3af; }
            .stx-chart { background: #fff; border: 1px solid #eef0f4; border-radius: 12px; padding: 16px; }
            #stx-daily-chart { min-height: 260px; }
            .stx-subtitle { font-size: 15px; font-weight: 700; color: #1f2937; margin: 4px 0 12px; display: flex; align-items: center; gap: 8px; }
            .stx-subtitle::before { content: ''; width: 4px; height: 18px; border-radius: 2px; background: #7367f0; display: inline-block; }
            .stx-table { width: 100%; font-size: 13px; color: #1f2937; }
            .stx-table thead th { background: #f9fafb; color: #6b7280; font-weight: 600; padding: 10px 12px; text-align: right; white-space: nowrap; }
            .stx-table tbody td { padding: 10px 12px; border-top: 1px solid #eef0f4; }
            .stx-table tbody tr:first-child td { border-top: 0; }
            .stx-empty { color: #9ca3af; font-size: 13px; padding: 14px 4px; }
        </style>

        {{-- ================= گزارش فروش (فاز ۱۰۶-۲ — گیت seller_analytics_enabled) =================
             استایل نمودار درآمد هم فقط وقتی بخش فروش سرو می‌شود چاپ می‌شود (عدم نشت ID در حالت خاموش) --}}
        @if(!is_null($sales))
            <style>#stx-revenue-chart { min-height: 260px; }</style>
            <div class="stx-subtitle"><i class="feather icon-shopping-bag"></i> گزارش فروش (۳۰ روز اخیر)</div>

            {{-- کارت‌های خلاصهٔ فروش --}}
            <div class="row">
                <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                    <div class="stx-card">
                        <div class="stx-card__label">درآمد سفارش‌های موفق</div>
                        <div class="stx-card__value">{{ number_format($sales['revenue']) }} <small style="font-size:12px;color:#6b7280">تومان</small></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                    <div class="stx-card">
                        <div class="stx-card__label">سفارش‌های پرداخت‌شده</div>
                        <div class="stx-card__value">{{ number_format($sales['orders']) }}</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                    <div class="stx-card">
                        <div class="stx-card__label">اقلام فروخته‌شده</div>
                        <div class="stx-card__value">{{ number_format($sales['items']) }}</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                    <div class="stx-card">
                        <div class="stx-card__label">میانگین هر سفارش</div>
                        <div class="stx-card__value">{{ number_format($sales['avgOrder']) }} <small style="font-size:12px;color:#6b7280">تومان</small></div>
                    </div>
                </div>
            </div>

            {{-- نمودار درآمد + پرفروش‌ترین‌ها --}}
            <div class="row mt-2">
                <div class="col-lg-7 col-md-12 col-xs-12 mb-2">
                    <div class="c-card h-100">
                        <div class="c-card__header">
                            <h2 class="c-card__title">روند درآمد ۳۰ روز اخیر</h2>
                        </div>
                        <div class="card-body">
                            <div class="stx-chart">
                                <div id="stx-revenue-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 col-md-12 col-xs-12 mb-2">
                    <div class="c-card h-100">
                        <div class="c-card__header">
                            <h2 class="c-card__title">پرفروش‌ترین محصولات</h2>
                        </div>
                        <div class="card-body">
                            @if($sales['bestsellers']->isEmpty())
                                <div class="stx-empty">هنوز فروشی در ۳۰ روز اخیر ثبت نشده است.</div>
                            @else
                                <table class="stx-table">
                                    <thead>
                                        <tr>
                                            <th>محصول</th>
                                            <th>تعداد</th>
                                            <th>درآمد</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sales['bestsellers'] as $row)
                                            <tr>
                                                <td class="text-truncate" style="max-width:180px">
                                                    @if($row->product && $row->product->slug)
                                                        {{-- نام روت با پیشوند گروه بیرونی front. — درس فاز ۹۰/۱۰۶ الف (دو-پیشوندی نشدن نام‌ها) --}}
                                                        <a href="{{ route('front.products.show', ['product' => $row->product->slug]) }}" target="_blank" title="{{ $row->product->title }}">{{ \Illuminate\Support\Str::limit($row->product->title, 34) }}</a>
                                                    @else
                                                        {{ \Illuminate\Support\Str::limit($row->product->title ?? 'محصول', 34) }}
                                                    @endif
                                                </td>
                                                <td>{{ number_format($row->qty) }}</td>
                                                <td>{{ number_format($row->revenue) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- نرخ تبدیل بازدید→سفارش --}}
            <div class="row mt-2">
                <div class="col-12">
                    <div class="c-card">
                        <div class="c-card__header">
                            <h2 class="c-card__title">بازدید → سفارش (۳۰ روز اخیر)</h2>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 col-xs-12 mb-2">
                                    <div class="stx-card">
                                        <div class="stx-card__label"><i class="feather icon-eye"></i> بازدید صفحات محصول</div>
                                        <div class="stx-card__value">{{ number_format($sales['views']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xs-12 mb-2">
                                    <div class="stx-card">
                                        <div class="stx-card__label"><i class="feather icon-shopping-cart"></i> سفارش‌های موفق</div>
                                        <div class="stx-card__value">{{ number_format($sales['orders']) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xs-12 mb-2">
                                    <div class="stx-card">
                                        <div class="stx-card__label"><i class="feather icon-percent"></i> نرخ تبدیل</div>
                                        <div class="stx-card__value">{{ is_null($sales['conversionRate']) ? '—' : number_format($sales['conversionRate'], 1).'٪' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ================= آمار بازدید (فاز ۱۰۶ الف) ================= --}}
        <div class="stx-subtitle mt-2"><i class="feather icon-bar-chart-2"></i> آمار بازدید فروشگاه</div>

        {{-- کارت‌های خلاصه --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="stx-card">
                    <div class="stx-card__label">کل بازدیدها</div>
                    <div class="stx-card__value">{{ number_format($totalVisits) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="stx-card">
                    <div class="stx-card__label">بازدیدکنندگان یکتا</div>
                    <div class="stx-card__value">{{ number_format($uniqueVisitors) }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="stx-card">
                    <div class="stx-card__label">بازدید امروز</div>
                    <div class="stx-card__value">{{ number_format($todayVisits) }}</div>
                    <span class="stx-card__trend {{ $trendClass }}">{{ $trendLabel }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-6 mb-2">
                <div class="stx-card">
                    <div class="stx-card__label">میانگین روزانه (۳۰ روز)</div>
                    <div class="stx-card__value">{{ number_format($averageVisits) }}</div>
                </div>
            </div>
        </div>

        {{-- نمودار ۷ روز اخیر --}}
        <div class="row mt-2">
            <div class="col-12">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">روند بازدید ۷ روز اخیر</h2>
                    </div>
                    <div class="card-body">
                        <div class="stx-chart">
                            <div id="stx-daily-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- تفکیک امروز بر اساس نوع صفحه --}}
        <div class="row mt-2 mb-2">
            <div class="col-12">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">بازدید امروز بر اساس نوع صفحه</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 col-xs-12 mb-2">
                                <div class="stx-card">
                                    <div class="stx-card__label"><i class="feather icon-home"></i> صفحهٔ فروشگاه</div>
                                    <div class="stx-card__value">{{ number_format($todayPathVisits) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-xs-12 mb-2">
                                <div class="stx-card">
                                    <div class="stx-card__label"><i class="feather icon-package"></i> صفحات محصولات</div>
                                    <div class="stx-card__value">{{ number_format($todayProductVisits) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-xs-12 mb-2">
                                <div class="stx-card">
                                    <div class="stx-card__label"><i class="feather icon-file-text"></i> سایر صفحات</div>
                                    <div class="stx-card__value">{{ number_format($todayPageVisits) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('back/app-assets/vendors/css/charts/apexcharts.css') }}">
@endpush

@push('plugin-scripts')
    <script src="{{ asset('back/app-assets/vendors/js/charts/apexcharts.min.js') }}"></script>
@endpush

@push('scripts')
    <script>
        (function () {
            if (typeof ApexCharts === 'undefined') {
                return;
            }

            var hasApex = function (id) { return document.getElementById(id) !== null; };

            // نمودار بازدید ۷ روزه (فاز ۱۰۶ الف)
            if (hasApex('stx-daily-chart')) {
                var visitLabels = @json($chartLabels);
                var visitData = @json($chartData);

                new ApexCharts(document.querySelector('#stx-daily-chart'), {
                    chart: { type: 'bar', height: 260, fontFamily: 'inherit', toolbar: { show: false }, animations: { enabled: true } },
                    series: [{ name: 'بازدید', data: visitData }],
                    colors: ['#7367f0'],
                    plotOptions: { bar: { columnWidth: '45%', borderRadius: 6 } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: visitLabels, labels: { show: visitLabels.length > 0 }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { formatter: function (val) { return Math.round(val); } } },
                    grid: { borderColor: '#eef0f4', padding: { left: 8, right: 8 } },
                    tooltip: { y: { formatter: function (val) { return val + ' بازدید'; } } }
                }).render();
            }

            // نمودار درآمد ۳۰ روزه (فاز ۱۰۶-۲ — گیت seller_analytics_enabled)
            @if(!is_null($sales))
            if (hasApex('stx-revenue-chart')) {
                var revLabels = @json($salesChartLabels);
                var revData = @json($salesChartData);

                new ApexCharts(document.querySelector('#stx-revenue-chart'), {
                    chart: { type: 'area', height: 260, fontFamily: 'inherit', toolbar: { show: false }, animations: { enabled: true } },
                    series: [{ name: 'درآمد', data: revData }],
                    colors: ['#2d9b6e'],
                    stroke: { curve: 'smooth', width: 3 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 0.6, opacityFrom: 0.35, opacityTo: 0.05 } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: revLabels, labels: { show: revLabels.length > 0 }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { formatter: function (val) { return Math.round(val).toLocaleString('fa-IR'); } } },
                    grid: { borderColor: '#eef0f4', padding: { left: 8, right: 8 } },
                    tooltip: { y: { formatter: function (val) { return val.toLocaleString('fa-IR') + ' تومان'; } } }
                }).render();
            }
            @endif
        })();
    </script>
@endpush

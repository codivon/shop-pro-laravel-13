@extends('front::sellers.panel.layouts.master')

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
    @endphp

    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">آمار بازدید فروشگاه</span>
                <span class="c-content-page__header-desc">بازدیدها، بازدیدکنندگان یکتا و روند ۷ روز اخیر فروشگاه شما — منبع: رهگیری بازدید صفحات فروشگاه.</span>
            </div>
        </div>
    </div>

    @include('front::sellers.panel.partials.sidebar')

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
        </style>

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
            var labels = @json($chartLabels);
            var data = @json($chartData);

            if (typeof ApexCharts === 'undefined' || !document.getElementById('stx-daily-chart')) {
                return;
            }

            var options = {
                chart: { type: 'bar', height: 260, fontFamily: 'inherit', toolbar: { show: false }, animations: { enabled: true } },
                series: [{ name: 'بازدید', data: data }],
                colors: ['#7367f0'],
                plotOptions: { bar: { columnWidth: '45%', borderRadius: 6 } },
                dataLabels: { enabled: false },
                xaxis: { categories: labels, labels: { show: labels.length > 0 }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: function (val) { return Math.round(val); } } },
                grid: { borderColor: '#eef0f4', padding: { left: 8, right: 8 } },
                tooltip: { y: { formatter: function (val) { return val + ' بازدید'; } } }
            };

            new ApexCharts(document.querySelector('#stx-daily-chart'), options).render();
        })();
    </script>
@endpush

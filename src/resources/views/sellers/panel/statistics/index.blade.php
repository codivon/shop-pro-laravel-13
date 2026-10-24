{{-- آمار و بازدید فروشنده — سانیار فاز J
    قرارداد SellerStatisticsController@index :
    totalVisits, uniqueVisitors, todayVisits, yesterdayVisits, changePercent,
    averageVisits, dailyVisits, todayPathVisits, todayProductVisits, todayPageVisits
    AJAX: seller.statistics.viewCounts / viewerCounts — JSON {status, data:{n:{chart_category,total}}, meta:{total,avg}} --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'آمار و بازدید', 'active' => 'statistics'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow main-color-one-color ms-1"></i>آمار و بازدید</h5>
                    <p class="font-13 text-muted mb-0">روند بازدید فروشگاه و محصولات شما را دنبال کنید.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- یادداشت یک‌باره: هنوز هیچ بازدیدی ثبت نشده --}}
    @if(!count($dailyVisits))
        <div class="alert alert-secondary font-13" role="alert">
            <i class="bi bi-info-circle ms-1"></i>هنوز بازدیدی ثبت نشده است.
        </div>
    @endif

    {{-- کارت‌های آمار --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-eye"></i></span>
                </div>
                <span class="stat-value d-block">{{ number_format($totalVisits) }}</span>
                <span class="stat-label d-block">کل بازدیدها</span>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-person"></i></span>
                </div>
                <span class="stat-value d-block">{{ number_format($uniqueVisitors) }}</span>
                <span class="stat-label d-block">بازدیدکننده یکتا</span>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-calendar-day"></i></span>
                    @if($changePercent > 0)
                        <span class="stat-trend up font-13 fw-bold"><i class="bi bi-arrow-up-short"></i>{{ abs($changePercent) }}٪ افزایش</span>
                    @elseif($changePercent < 0)
                        <span class="stat-trend down font-13 fw-bold"><i class="bi bi-arrow-down-short"></i>{{ abs($changePercent) }}٪ کاهش</span>
                    @endif
                </div>
                <span class="stat-value d-block">{{ number_format($todayVisits) }}</span>
                <span class="stat-label d-block">بازدید امروز</span>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-graph-up"></i></span>
                </div>
                <span class="stat-value d-block">{{ number_format($averageVisits) }}</span>
                <span class="stat-label d-block">میانگین روزانه (۳۰ روز)</span>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-clock-history"></i></span>
                </div>
                <span class="stat-value d-block">{{ number_format($yesterdayVisits) }}</span>
                <span class="stat-label d-block">بازدید دیروز</span>
            </div>
        </div>
    </div>

    {{-- کارت نمودار + تب بازدیدها/بازدیدکنندگان یکتا --}}
    <div class="seller-chart-card mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line main-color-one-color ms-1"></i>روند بازدید فروشگاه</h6>
            <ul class="nav nav-pills gap-1" role="tablist" aria-label="نوع آمار">
                <li class="nav-item" role="presentation">
                    <button type="button" id="sfs-views-tab-btn" class="btn btn-sm rounded-3 font-12 main-color-one-bg text-white active"
                            data-bs-toggle="pill" data-bs-target="#sfs-tab-views" role="tab" aria-selected="true">
                        <i class="bi bi-eye ms-1"></i>تعداد بازدیدها
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" id="sfs-viewers-tab-btn" class="btn btn-sm rounded-3 font-12 btn-outline-secondary"
                            data-bs-toggle="pill" data-bs-target="#sfs-tab-viewers" role="tab" aria-selected="false">
                        <i class="bi bi-person ms-1"></i>بازدیدکنندگان یکتا
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            {{-- ===== تب تعداد بازدیدها ===== --}}
            <div class="tab-pane fade show active" id="sfs-tab-views" role="tabpanel" aria-labelledby="sfs-views-tab-btn">
                <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                    <div class="btn-group" role="group" aria-label="بازه زمانی آمار بازدیدها">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 rounded-end font-12 sfs-period-pill" data-period="daily">روزانه</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 font-12 sfs-period-pill" data-period="weekly">هفتگی</button>
                        <button type="button" class="btn btn-sm main-color-one-bg text-white font-12 sfs-period-pill active" data-period="monthly">ماهانه</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 rounded-start font-12 sfs-period-pill" data-period="yearly">سالانه</button>
                    </div>
                    <div>
                        <label class="form-label font-12 fw-bold mb-1" for="sfs-views-from">از تاریخ</label>
                        <input type="text" class="form-control form-control-sm font-13 ltr-direction" dir="ltr" inputmode="numeric"
                               id="sfs-views-from" placeholder="۱۴۰۴-۰۱-۰۱" aria-describedby="sfs-views-from-hint">
                        <div class="font-12 text-muted mt-1" id="sfs-views-from-hint">تاریخ شمسی اختیاری</div>
                    </div>
                    <div>
                        <label class="form-label font-12 fw-bold mb-1" for="sfs-views-to">تا تاریخ</label>
                        <input type="text" class="form-control form-control-sm font-13 ltr-direction" dir="ltr" inputmode="numeric"
                               id="sfs-views-to" placeholder="۱۴۰۴-۱۲-۲۹" aria-describedby="sfs-views-to-hint">
                        <div class="font-12 text-muted mt-1" id="sfs-views-to-hint">تاریخ شمسی اختیاری</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-12 sfs-apply">
                        <i class="bi bi-arrow-repeat ms-1"></i>به‌روزرسانی
                    </button>
                </div>

                <div id="sfs-views-chart" style="position: relative; height: 360px;">
                    <canvas id="sfs-views-canvas" role="img" aria-label="نمودار تعداد بازدیدهای فروشگاه"></canvas>
                </div>

                <div class="font-13 text-muted mt-2">
                    کل: <span id="sfs-views-total" class="fw-bold text-dark">—</span>
                    <span class="mx-1">|</span>
                    میانگین: <span id="sfs-views-avg" class="fw-bold text-dark">—</span>
                </div>
            </div>

            {{-- ===== تب بازدیدکنندگان یکتا ===== --}}
            <div class="tab-pane fade" id="sfs-tab-viewers" role="tabpanel" aria-labelledby="sfs-viewers-tab-btn">
                <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                    <div class="btn-group" role="group" aria-label="بازه زمانی آمار بازدیدکنندگان">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 rounded-end font-12 sfs-period-pill" data-period="daily">روزانه</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 font-12 sfs-period-pill" data-period="weekly">هفتگی</button>
                        <button type="button" class="btn btn-sm main-color-one-bg text-white font-12 sfs-period-pill active" data-period="monthly">ماهانه</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 rounded-start font-12 sfs-period-pill" data-period="yearly">سالانه</button>
                    </div>
                    <div>
                        <label class="form-label font-12 fw-bold mb-1" for="sfs-viewers-from">از تاریخ</label>
                        <input type="text" class="form-control form-control-sm font-13 ltr-direction" dir="ltr" inputmode="numeric"
                               id="sfs-viewers-from" placeholder="۱۴۰۴-۰۱-۰۱" aria-describedby="sfs-viewers-from-hint">
                        <div class="font-12 text-muted mt-1" id="sfs-viewers-from-hint">تاریخ شمسی اختیاری</div>
                    </div>
                    <div>
                        <label class="form-label font-12 fw-bold mb-1" for="sfs-viewers-to">تا تاریخ</label>
                        <input type="text" class="form-control form-control-sm font-13 ltr-direction" dir="ltr" inputmode="numeric"
                               id="sfs-viewers-to" placeholder="۱۴۰۴-۱۲-۲۹" aria-describedby="sfs-viewers-to-hint">
                        <div class="font-12 text-muted mt-1" id="sfs-viewers-to-hint">تاریخ شمسی اختیاری</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-12 sfs-apply">
                        <i class="bi bi-arrow-repeat ms-1"></i>به‌روزرسانی
                    </button>
                </div>

                <div id="sfs-viewers-chart" style="position: relative; height: 360px;">
                    <canvas id="sfs-viewers-canvas" role="img" aria-label="نمودار بازدیدکنندگان یکتای فروشگاه"></canvas>
                </div>

                <div class="font-13 text-muted mt-2">
                    کل: <span id="sfs-viewers-total" class="fw-bold text-dark">—</span>
                    <span class="mx-1">|</span>
                    میانگین: <span id="sfs-viewers-avg" class="fw-bold text-dark">—</span>
                </div>
            </div>
        </div>
    </div>

    {{-- تفکیک بازدید امروز بر اساس نوع صفحه (همان قرارداد compact کنترلر) --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-layout-three-columns main-color-one-color ms-1"></i>بازدید امروز به تفکیک</h6>
            </div>
            <div class="row g-0 text-center py-3">
                <div class="col-4">
                    <span class="font-13 text-muted d-block mb-1"><i class="bi bi-shop me-1"></i>صفحه فروشگاه</span>
                    <span class="fw-bold font-16">{{ number_format($todayPathVisits) }}</span>
                </div>
                <div class="col-4 border-start">
                    <span class="font-13 text-muted d-block mb-1"><i class="bi bi-box-seam me-1"></i>صفحات محصول</span>
                    <span class="fw-bold font-16">{{ number_format($todayProductVisits) }}</span>
                </div>
                <div class="col-4 border-start">
                    <span class="font-13 text-muted d-block mb-1"><i class="bi bi-file-earmark-text me-1"></i>سایر صفحات</span>
                    <span class="fw-bold font-16">{{ number_format($todayPageVisits) }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('plugin/chartjs/chart.js') }}"></script>
    <script src="{{ theme_asset('js/pages/sellers/seller-statistics.js') }}?v=1"
            data-views-action="{{ route('seller.statistics.viewCounts') }}"
            data-viewers-action="{{ route('seller.statistics.viewerCounts') }}"></script>
@endpush

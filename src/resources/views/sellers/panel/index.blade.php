{{-- داشبورد پنل فروشنده (فاز G)
    قرارداد DashboardSellerController@index :
    $seller_variants, $products, $seller_competitor, $seller_no_competitor,
    $totalVisits, $uniqueVisitors, $todayVisits, $changePercent, $averageVisits, $dailyVisits --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'داشبورد فروشنده', 'active' => 'dashboard'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=1">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- خوش‌آمد --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-1">
                <div>
                    <h5 class="fw-bold mb-1">سلام {{ seller_info()->first_name ?? 'فروشنده' }} عزیز 👋</h5>
                    <p class="font-13 text-muted mb-0">به مرکز فروشندگان {{ option('info_site_title', config('front.demo.name')) }} خوش آمدید.</p>
                </div>
                @if(seller_info())
                    <a href="{{ route('front.showSellerStore', ['seller' => seller()]) }}" target="_blank"
                       class="btn btn-outline-primary rounded-3 font-14">
                        <i class="bi bi-shop me-1"></i>مشاهده فروشگاه من
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- کارت‌های آمار بازدید فروشگاه (داینامیک از Viewer) --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-eye"></i></span>
                </div>
                <span class="stat-value d-block">{{ number_format($totalVisits) }}</span>
                <span class="stat-label d-block">کل بازدیدهای فروشگاه</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-calendar-day"></i></span>
                    @if($changePercent > 0)
                        <span class="stat-trend up font-13 fw-bold"><i class="bi bi-arrow-up-short"></i>{{ $changePercent }}%</span>
                    @elseif($changePercent < 0)
                        <span class="stat-trend down font-13 fw-bold"><i class="bi bi-arrow-down-short"></i>{{ abs($changePercent) }}%</span>
                    @endif
                </div>
                <span class="stat-value d-block">{{ number_format($todayVisits) }}</span>
                <span class="stat-label d-block">بازدید امروز</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-people"></i></span>
                </div>
                <span class="stat-value d-block">{{ number_format($uniqueVisitors) }}</span>
                <span class="stat-label d-block">بازدیدکننده یکتا</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-graph-up"></i></span>
                </div>
                <span class="stat-value d-block">{{ number_format($averageVisits) }}</span>
                <span class="stat-label d-block">میانگین بازدید روزانه (۳۰ روز)</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- نمودار بازدید ۷ روز اخیر (chartjs) --}}
        <div class="col-lg-8">
            <div class="seller-chart-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line main-color-one-color ms-1"></i>بازدید فروشگاه در ۷ روز اخیر</h6>
                </div>
                @php
                    $chart_labels = collect($dailyVisits)->map(fn($v) => \Morilog\Jalali\Jalalian::fromDateTime($v->date . ' 12:00:00')->format('m/d'))->values();
                    $chart_values = collect($dailyVisits)->pluck('count')->values();
                @endphp
                <div class="seller-chart-wrap" style="position: relative; height: 260px;">
                    <canvas id="seller-visits-chart" role="img"
                            aria-label="نمودار بازدید هفت روز اخیر فروشگاه"
                            data-labels="{{ json_encode($chart_labels) }}"
                            data-values="{{ json_encode($chart_values) }}"></canvas>
                </div>
            </div>
        </div>

        {{-- وضعیت کالاها (داینامیک از SellerVariant/Product) --}}
        <div class="col-lg-4">
            <div class="seller-store-link-card">
                <h6 class="fw-bold mb-3"><i class="bi bi-box-seam main-color-one-color ms-1"></i>وضعیت کالاها</h6>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="font-14 text-muted"><i class="bi bi-tag me-1"></i>تنوع‌های فروش من</span>
                        <span class="fw-bold">{{ number_format($seller_variants->count()) }}</span>
                    </li>
                    <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="font-14 text-muted"><i class="bi bi-bag me-1"></i>کالاهای ثبت‌شده من</span>
                        <span class="fw-bold">{{ number_format($products->count()) }}</span>
                    </li>
                    <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="font-14 text-muted"><i class="bi bi-shield-check me-1"></i>تنوع‌های بدون رقیب</span>
                        <span class="fw-bold main-color-three">{{ number_format($seller_no_competitor->count()) }}</span>
                    </li>
                    <li class="d-flex align-items-center justify-content-between py-2">
                        <span class="font-14 text-muted"><i class="bi bi-people me-1"></i>کالاهای دارای رقیب</span>
                        <span class="fw-bold">{{ number_format($seller_competitor->count()) }}</span>
                    </li>
                </ul>
                <hr>
                <div class="d-grid gap-2">
                    <a href="{{ route('seller.products.find') }}" class="btn main-color-one-bg text-white rounded-3 font-14">
                        <i class="bi bi-plus-lg ms-1"></i>جستجو یا درج محصول
                    </a>
                    <a href="{{ route('seller.orders.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                        <i class="bi bi-cart-check ms-1"></i>مشاهده سفارش‌ها
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- آخرین تنوع‌های من (داینامیک) --}}
    @if(count($seller_variants))
        <div class="content-box">
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history main-color-one-color ms-1"></i>تنوع‌های اخیر من</h6>
                    <a href="{{ route('seller.products.index') }}" class="font-13 main-color-one-color">همه محصولات <i class="bi bi-arrow-left"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle site-tabel mb-3">
                        <thead>
                        <tr>
                            <th scope="col">کالا</th>
                            <th scope="col" class="text-center">قیمت</th>
                            <th scope="col" class="text-center">موجودی</th>
                            <th scope="col" class="text-center">فروش</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($seller_variants->sortByDesc('updated_at')->take(5) as $variant)
                            @php
                                $v_product = \App\Models\Product::find($variant->product_id);
                                $v_price = \App\Models\Price::find($variant->prices_id);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($v_product && $v_product->image)
                                            <img src="{{ asset($v_product->image) }}" alt="{{ $v_product->title }}"
                                                 style="width:44px;height:44px;object-fit:cover;" class="rounded-3">
                                        @endif
                                        <span class="font-13">{{ $v_product->title ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="text-center font-13">{{ $v_price ? number_format($v_price->discount_price) : '—' }}</td>
                                <td class="text-center font-13">{{ $v_price ? number_format($v_price->stock) : '—' }}</td>
                                <td class="text-center font-13">{{ $v_price ? number_format($v_price->sold_count) : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ theme_asset('plugin/chartjs/chart.js') }}"></script>
    <script src="{{ theme_asset('js/pages/sellers/seller-panel.js') }}?v=1"></script>
@endpush

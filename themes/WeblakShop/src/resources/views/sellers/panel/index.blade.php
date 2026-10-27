@extends('front::sellers.panel.layouts.master')

@push('styles')
    <style>
        @media(max-width: 767px) {
            .sidebar-d-show-mobile{
                display: block!important;
            }
        }

        /* استایل‌های جدید برای آمار بازدید */
        .visit-stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
        }

        .visit-stats-number {
            font-size: 32px;
            font-weight: bold;
        }

        .visit-stats-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .trend-up {
            color: #4cd964;
        }

        .trend-down {
            color: #ff3b30;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .visitors-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .visitor-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .visitor-item:hover {
            background-color: #f8f9fa;
        }

        .visitor-ip {
            font-weight: 600;
            color: #333;
        }

        .visitor-time {
            font-size: 12px;
            color: #999;
        }

        .visitor-page {
            font-size: 13px;
            color: #666;
        }
        .stat-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-card-body {
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .stat-card-icon {
            flex: 0 0 60px;
            text-align: center;
        }

        .stat-card-info {
            flex: 1;
            text-align: left;
        }

        .stat-card-value {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }

        .stat-card-label {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
    </style>
@endpush

@section('content')

    @include('front::sellers.panel.partials.sidebar')

    @php
        if(seller()->status_documents=="Waiting"){
            $status_documents="warning";
        }elseif(seller()->status_documents=="Accept"){
            $status_documents="success";
        }elseif(seller()->status_documents=="Reject"){
            $status_documents="danger";
        }

        if(seller()->status_work=="ACTIVE"){
            $status_work="success";
        }elseif(seller()->status_work=="Stop"){
            $status_work="danger";
        }elseif(seller()->status_work=="EditProfile"){
            $status_work="warning";
        }

        $orderItem_ids=\App\Models\OrderItem::where('seller_id',sellerID())->get();
        $order_ids=[];
            foreach ($orderItem_ids as $orderItem_id){
                $order_ids[]=$orderItem_id->order_id;
            }
            $order_ids=array_unique($order_ids);
    @endphp

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right">

        {{-- وضعیت کارت‌ها (همان کد قبلی) --}}
        <div class="row interactive-status-card ml-0">
            <div class="col-4 interactive-status-item {{$status_documents}}">
                <div class="interactive-status__title">وضعیت مدارک</div>
                <div class="interactive-status__description">
                    @if(seller()->status_documents=="Waiting")
                        در حال بررسی
                    @elseif(seller()->status_documents=="Accept")
                        تایید شده
                    @elseif(seller()->status_documents=="Reject")
                        رد شده
                    @endif
                </div>
            </div>

            <div class="col-4 interactive-status-item {{seller_info()->econtract=="1" ? 'success' : 'warning'}}">
                <div class="interactive-status__title">وضعیت قرارداد</div>
                <div class="interactive-status__description">
                    @if(seller_info()->econtract=="1")
                        تایید شده
                    @else
                        در انتظار تایید
                    @endif
                </div>
            </div>

            <div class="col-4 interactive-status-item  {{$status_work}}">
                <div class="interactive-status__title">وضعیت پنل</div>
                <div class="interactive-status__description">
                    @if(seller()->status_work=="ACTIVE")
                        فعال
                    @elseif(seller()->status_work=="EditProfile")
                        در انتظار تایید (ویرایش اطلاعات)
                    @else
                        توقف کاری
                    @endif
                </div>
            </div>
        </div>

        {{-- آمار تسویه (فاز ۱۰۳ — گیت از تنظیمات چندفروشندگی؛ بدون فرانت‌سپند — سبک inline اسکوپ‌شده) --}}
        @if(!is_null($vendorStats))
            @php
                $vsItems = [
                    [
                        'title' => 'موجودی قابل برداشت',
                        'value' => number_format($vendorStats['balance']).' تومان',
                        'color' => '#2d9b6e',
                        'icon'  => 'wallet',
                        'link'  => route('seller.dashboard'),
                    ],
                    [
                        'title' => 'فروش موفق',
                        'value' => number_format($vendorStats['total_sales']).' تومان',
                        'color' => '#6b5ce7',
                        'icon'  => 'shopping-bag',
                        'link'  => null,
                    ],
                    [
                        'title' => 'کمیسیون پرداخت‌شده',
                        'value' => number_format($vendorStats['total_commission']).' تومان',
                        'color' => '#d97706',
                        'icon'  => 'percent',
                        'link'  => null,
                    ],
                    [
                        'title' => 'درخواست در انتظار',
                        'value' => $vendorStats['waiting_withdraw_count'] > 0
                            ? number_format($vendorStats['waiting_withdraw_count']).' درخواست / '.number_format($vendorStats['waiting_withdraw_amount']).' تومان'
                            : 'درخواستی در انتظار نیست',
                        'color' => '#0ea5e9',
                        'icon'  => 'hourglass',
                        'link'  => route('seller.dashboard'),
                    ],
                ];
            @endphp
            <div class="row ml-0 mt-4 vset-cards">
                @foreach($vsItems as $vsItem)
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="vset-card" @if($vsItem['link']) onclick="window.location='{{ $vsItem['link'] }}'" style="cursor:pointer" @endif>
                            <div class="vset-card__head">
                                <span class="vset-card__icon" style="background: {{ $vsItem['color'] }}1a; color: {{ $vsItem['color'] }};">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle></svg>
                                </span>
                                <span class="vset-card__title">{{ $vsItem['title'] }}</span>
                            </div>
                            <div class="vset-card__value">{{ $vsItem['value'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <style>
                /* اسکوپ اختصاصی کارت‌های تسویه — بدون وابستگی به استایل سراسری تم */
                .vset-cards { display:flex; flex-wrap:wrap; margin-right:-8px; margin-left:-8px; }
                .vset-cards > [class*="col-"] { padding-right:8px; padding-left:8px; margin-bottom:16px; }
                .vset-card { background:#fff; border:1px solid #ececf1; border-radius:14px; padding:16px; height:100%; box-shadow:0 2px 10px rgba(0,0,0,.04); }
                .vset-card__head { display:flex; align-items:center; gap:8px; }
                .vset-card__icon { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:10px; flex-shrink:0; }
                .vset-card__title { font-size:13px; font-weight:600; color:#555; }
                .vset-card__value { margin-top:10px; font-size:14px; font-weight:700; color:#1f2430; line-height:1.8; }
                @media (max-width:767px){ .vset-card__value { font-size:13px; } }
            </style>
        @endif



        {{-- کارت‌های سریع (همان کد قبلی) --}}
        <div class="row dashboard-steps-1 mt-4">
            <div class="col-lg-4 col-md-12 col-xs-12 dashboard-steps-1-item mb-2">
                <a href="{{route('seller.products.create')}}" class="c-card c-card--is-link c-card--has-btn c-dashboard-status__jc-c" id="dashboard-step-6">
                    <div class="c-card__header c-card__header--no-border">
                        <h2 class="c-card__title c-card__title--dark">افزودن محصول جدید</h2>
                        <div class="c-card__header-btn c-card__header-btn--add">+</div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-12 col-xs-12 dashboard-steps-1-item mb-2">
                <a href="{{ route('seller.products.index') }}" class="c-card c-card--is-link c-dashboard-status__jc-c" id="dashboard - step - 7">
                    <div class="c-card__header c-card__header--no-border">
                        <h2 class="c-card__title c-card__title--dark">تنوع های فعال در پروموشن‌ها
                            <span class="c-card__title-side c-card__title-side--arrow"> {{count($seller_variants)}}
                        </span>
                        </h2>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-12 col-xs-12 dashboard-steps-1-item mb-2">
                <a class="c-card c-card--is-link c-card--has-btn c-card--has-success c-dashboard-status__jc-c" id="dashboard - step - 8">
                    <div class="c-card__header c-card__header--no-border">
                        <h2 class="c-card__title c-card__title--dark">وضعیت پرداخت<small>فعال</small></h2>
                        <div class="c-card__header-btn c-card__header-btn--success"><i class="fa-solid fa-check"></i></div>
                    </div>
                </a>
            </div>
        </div>

        {{-- ادامه کدهای قبلی --}}
        <div class="row dashboard-steps-2">
            <div class="col-lg-4 col-md-12 col-xs-12 dashboard-steps-2-item mb-2">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">مدیریت موجودی انبار</h2>
                    </div>
                    <div class="c-card__body">
                        <div class="c-rating-chart c-rating-chart--condensed">
                            <a class="c-rating-chart__details-bar">
                                <div class="c-rating-chart__description c-rating-chart__description--bar c-card__stat-description uk-inline">
                                    تعداد کل تنوع‌ها
                                    <div class="c-rating-chart__description-dropdown js-dropdown-desc"></div>

                                </div>
                                <div class="c-rating-chart__details-value c-rating-chart__details-value--large">
                                    {{count($seller_variants)}}
                                </div>
                            </a>

                        </div>


                        <div class="c-rating-chart c-rating-chart--condensed">
                            <a class="c-rating-chart__details-bar">
                                <div class="c-rating-chart__description c-rating-chart__description--bar c-card__stat-description uk-inline">
                                    تنوع‌های فعال بدون موجودی
                                    <div class="c-rating-chart__description-dropdown js-dropdown-desc"></div>
                                </div>
                                @php
                                    $prise_ids=[];
                                    $ids=[];
                                    foreach ($seller_variants as $seller_variant){

                                        $res = str_replace( array( '/\s+/','[', ']', '' ), ' ', $seller_variant->prices_id);
                                        $res=trim($res);
                                        $prise_ids[]=explode(',',$res);
                                    }
                                    foreach ($prise_ids as $prise_id){
                                        foreach ($prise_id as $id){
                                            $ids[]=$id;
                                        }
                                      }
                                    $prices_count=\App\Models\Price::whereIn('id',$ids)->get();
                                    $price_no_count=0;
                                    $price_no_count_soon=0;
                                    foreach ($prices_count as $price_count){
                                        if ($price_count->stock<1){
                                            $price_no_count++;
                                        }
                                         if ($price_count->stock<3){
                                            $price_no_count_soon++;
                                        }
                                    }
                                @endphp
                                <div class="c-rating-chart__details-value c-rating-chart__details-value--large">
                                    {{$price_no_count}}
                                </div>
                            </a>

                        </div>

                        <div class="c-rating-chart c-rating-chart--condensed">
                            <a class="c-rating-chart__details-bar">
                                <div class="c-rating-chart__description c-rating-chart__description--bar c-card__stat-description uk-inline">
                                    تنوع‌های در حال اتمام موجودی
                                </div>
                                <div class="c-rating-chart__details-value c-rating-chart__details-value--large">
                                    {{$price_no_count_soon}}
                                </div>
                            </a>
                        </div>


                    </div>
                </div>

            </div>

            <div class="col-lg-4 col-md-12 col-xs-12 dashboard-steps-2-item mb-2 ">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">مدیریت سفارشات</h2>
                    </div>
                    <div class="c-card__body">
                        <a class="c-rating-chart c-rating-chart--condensed">
                            <div class="c-rating-chart__details-bar">
                                <div class="c-rating-chart__description c-rating-chart__description--bar c-card__stat-description uk-inline">
                                    تعداد سفارشات
                                </div>
                                <div class="c-rating-chart__details-value c-rating-chart__details-value--large">
                                    {{count($order_ids)}}
                                </div>
                            </div>
                        </a>

                        <a class="c-rating-chart c-rating-chart--condensed">
                            <div class="c-rating-chart__details-bar">
                                <div class="c-rating-chart__description c-rating-chart__description--bar c-card__stat-description uk-inline">
                                    ارزش سفارشات موفق
                                </div>
                                <div class="c-rating-chart__details-value ">
                                    {{number_format(seller()->orders()->paid()->sum('price'))}} تومان
                                </div>
                            </div>
                        </a>



                    </div>
                </div>

            </div>

            <div class="col-lg-4 col-md-12 col-xs-12 dashboard-steps-2-item mb-2 ">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">مدیریت قیمت‌گذاری</h2>
                    </div>
                    <div class="c-card__body c-card__body--justify">

                        <a class="c-rating-chart c-rating-chart--condensed">
                            <div class="c-rating-chart__details-bar">
                                <div class="c-rating-chart__description c-rating-chart__description--bar c-card__stat-description uk-inline">
                                    تنوع‌های دارای رقیب

                                </div>
                                <div class="c-rating-chart__details-value c-rating-chart__details-value--large">
                                    {{count($seller_competitor)}}
                                </div>

                            </div>
                        </a>

                        <a class="c-rating-chart c-rating-chart--condensed">
                            <div class="c-rating-chart__details-bar">
                                <div class="c-rating-chart__description c-rating-chart__description--bar c-card__stat-description uk-inline">
                                    تنوع های بدون رقیب
                                </div>
                                <div class="c-rating-chart__details-value c-rating-chart__details-value--large">
                                    {{count($seller_no_competitor)}}
                                </div>

                            </div>
                        </a>


                    </div>
                </div>

            </div>
        </div>

        <div class="row dashboard-steps-3">
            <div class="col-12 dashboard-steps-3-item ">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">مدیریت محصولات</h2>
                    </div>
                    <div class="c-card__body uk-height-1-1 uk-flex-middle">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="{{route('seller.products.index')}}" class="c-card__stat">
                                    <div class="c-card__stat-value">{{$products->count()}}</div>
                                    <p class="c-card__stat-description">کالاهای درج شده </p>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{route('seller.products.index')}}" class="c-card__stat">
                                    <div class="c-card__stat-value">{{count(collect($products)->where('published','1')->where('status','Accept'))}}</div>
                                    <div class="c-card__stat-description">
                                        کالاهای تأیید شده
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3 ">
                                <a href="{{route('seller.products.index')}}" class="c-card__stat">
                                    <div class="c-card__stat-value">{{count(collect($products)->where('status','Waiting'))}}</div>
                                    <p class="c-card__stat-description">کالاهای در انتظار تأیید</p>
                                </a>
                            </div>

                            <div class="col-md-3">
                                <a href="{{route('seller.products.index')}}" class="c-card__stat">
                                    <div class="c-card__stat-value">{{count(collect($products)->where('status','Reject'))}}</div>
                                    <p class="c-card__stat-description">تایید نشده</p>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>



        {{-- ========== بخش جدید: آمار بازدید فروشگاه ========== --}}
        <div class="row mt-3">
            <div class="col-12">
                <div class="visit-stats-card">
                    <div class="row align-items-center">
                        <div class="col-md-3 col-6 mb-3 mb-md-0">
                            <div class="text-center">
                                <div class="visit-stats-number">{{ number_format($totalVisits ?? 0) }}</div>
                                <div class="visit-stats-label">کل بازدیدها</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3 mb-md-0">
                            <div class="text-center">
                                <div class="visit-stats-number">{{ number_format($uniqueVisitors ?? 0) }}</div>
                                <div class="visit-stats-label">بازدیدکننده یکتا</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3 mb-md-0">
                            <div class="text-center">
                                <div class="visit-stats-number">{{ number_format($todayVisits ?? 0) }}</div>
                                <div class="visit-stats-label">بازدید امروز</div>
                                @if(($changePercent ?? 0) != 0)
                                    <small class="{{ ($changePercent ?? 0) > 0 ? 'trend-up' : 'trend-down' }}">
                                        <i class="fa-solid fa-arrow-{{ ($changePercent ?? 0) > 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($changePercent ?? 0) }}%
                                    </small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="visit-stats-number">{{ number_format($averageVisits ?? 0) }}</div>
                                <div class="visit-stats-label">میانگین روزانه</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- نمودار بازدیدهای 7 روز اخیر --}}
        @if(isset($dailyVisits) && $dailyVisits->count() > 0)
            <div class="row">
                <div class="col-12">
                    <section class="card" id="statistics-card">
                        <div class="card-content">
                            <div class="card-body">
                                <ul class="nav nav-tabs mb-2" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-toggle="tab" href="#view-counts" role="tab" aria-controls="view-counts" aria-selected="true">
                                            تعداد بازدیدها
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-toggle="tab" href="#viewer-counts" role="tab" aria-controls="viewer-counts" aria-selected="true">
                                            تعداد بازدیدگنندگان
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="view-counts" role="tabpanel" aria-labelledby="value">
                                        @include('back.statistics.views.filter-tabs')

                                        <div id="view-counts-chart" class="chart-area" style="min-height: 445px;" data-min-height="445px" data-action="{{ route('seller.statistics.viewCounts') }}"></div>

                                        <div class="col-12 mt-2">
                                            <div class="row">
                                                <div class="col-md-3 mb-2">
                                                    <span class="border-bottom">کل بازدیدها : <span class="views-total"></span></span>
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <span class="border-bottom">میانگین : <span class="views-avg"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="viewer-counts" role="tabpanel" aria-labelledby="value">
                                        @include('back.statistics.views.filter-tabs')

                                        <div id="viewer-counts-chart" class="chart-area" style="min-height: 445px;" data-min-height="445px" data-action="{{ route('seller.statistics.viewerCounts') }}"></div>

                                        <div class="col-12 mt-2">
                                            <div class="row">
                                                <div class="col-md-3 mb-2">
                                                    <span class="border-bottom">کل بازدیدها : <span class="viewers-total"></span></span>
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <span class="border-bottom">میانگین : <span class="viewers-avg"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>
            </div>
        @endif

    </div>

@endsection
@include('back.partials.plugins', ['plugins' => ['apexcharts', 'persian-datepicker']])

@push('scripts')
    <script src="{{ asset('back/assets/js/pages/statistics/views.js') }}?v=1"></script>

    <script src="{{ asset('back/app-assets/vendors/js/charts/apexcharts.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // نمودار بازدیدهای 7 روز اخیر با ApexCharts
            @if(isset($dailyVisits) && $dailyVisits->count() > 0)
            const dailyVisits = @json($dailyVisits);

            // داده‌ها را به ترتیب صعودی مرتب کن
            const sortedData = [...dailyVisits].reverse();

            const labels = sortedData.map(item => item.date);
            const data = sortedData.map(item => item.count);

            // گزینه‌های نمودار
            const options = {
                chart: {
                    type: 'area',
                    height: 350,
                    fontFamily: 'IRANYekanX, tahoma',
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                series: [{
                    name: 'تعداد بازدید',
                    data: data,
                    color: '#4e73df'
                }],
                xaxis: {
                    categories: labels,
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '12px',
                            fontFamily: 'IRANYekanX, tahoma'
                        }
                    },
                    title: {
                        text: 'تاریخ',
                        style: {
                            fontSize: '13px',
                            fontFamily: 'IRANYekanX, tahoma'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'تعداد بازدید',
                        style: {
                            fontSize: '13px',
                            fontFamily: 'IRANYekanX, tahoma'
                        }
                    },
                    min: 0,
                    labels: {
                        formatter: function(value) {
                            return Math.floor(value);
                        }
                    }
                },
                title: {
                    text: 'آمار بازدیدهای 7 روز اخیر',
                    align: 'center',
                    style: {
                        fontSize: '16px',
                        fontFamily: 'IRANYekanX, tahoma',
                        fontWeight: 'bold'
                    }
                },
                subtitle: {
                    text: 'بازدید از فروشگاه شما',
                    align: 'center',
                    style: {
                        fontSize: '12px',
                        fontFamily: 'IRANYekanX, tahoma'
                    }
                },
                tooltip: {
                    theme: 'dark',
                    x: {
                        format: 'yyyy/MM/dd'
                    },
                    y: {
                        formatter: function(value) {
                            return value + ' بازدید';
                        }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                        stops: [0, 90, 100]
                    }
                },
                markers: {
                    size: 4,
                    colors: ['#4e73df'],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: {
                        size: 6
                    }
                },
                grid: {
                    borderColor: '#e7e7e7',
                    row: {
                        colors: ['#f3f3f3', 'transparent'],
                        opacity: 0.5
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center',
                    fontSize: '13px',
                    fontFamily: 'IRANYekanX, tahoma'
                }
            };

            // رسم نمودار
            const chart = new ApexCharts(document.querySelector("#visitsChart"), options);
            chart.render();
            @endif
        });
    </script>
@endpush

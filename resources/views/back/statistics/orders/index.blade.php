@extends('back.layouts.master')

@push('styles')
    @if(function_exists('module_is_active') && module_is_active('CreditPay'))
        <link rel="stylesheet" href="{{ module_asset('CreditPay', 'css/installment.css') }}">
    @endif
@endpush

@section('content')

    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb no-border">
                                    <li class="breadcrumb-item">مدیریت
                                    </li>
                                    <li class="breadcrumb-item">گزارشات
                                    </li>
                                    <li class="breadcrumb-item active">سفارشات
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">

                <section class="card" id="statistics-card">
                    <div class="card-content">
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-2" id="orderstab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-toggle="tab" href="#order-values" role="tab" aria-controls="order-values" aria-selected="true">
                                        ارزش سفارشات
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-toggle="tab" href="#order-counts" role="tab" aria-controls="order-counts" aria-selected="false">
                                        تعداد سفارشات
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-toggle="tab" href="#order-users" role="tab" aria-controls="order-users" aria-selected="false">
                                        تعداد کاربران سفارش دهنده
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-toggle="tab" href="#order-products" role="tab" aria-controls="order-products" aria-selected="false">
                                        تعداد محصولات سفارشات
                                    </a>
                                </li>
                                @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-toggle="tab" href="#order-installments" role="tab" aria-controls="order-installments" aria-selected="false">
                                            <i class="fas fa-money-check-alt"></i> خریدهای قسطی
                                        </a>
                                    </li>
                                @endif
                            </ul>

                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="order-values" role="tabpanel" aria-labelledby="value">
                                    @include('back.statistics.orders.filter-tabs')

                                    <div id="order-values-chart" class="chart-area" style="min-height: 445px;" data-min-height="445px" data-action="{{ route('admin.statistics.orderValues') }}"></div>

                                    <div class="col-12 mt-2">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom">کل سفارشات : <span class="orders-total"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom">میانگین سفارشات : <span class="orders-avg"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom"> سفارشات موفق: <span class="orders-success"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom"> سفارشات ناموفق: <span class="orders-fail"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="order-counts" role="tabpanel" aria-labelledby="count">
                                    @include('back.statistics.orders.filter-tabs')

                                    <div id="order-counts-chart" class="chart-area" style="min-height: 445px;" data-min-height="445px" data-action="{{ route('admin.statistics.orderCounts') }}"></div>

                                    <div class="col-12 mt-2">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom">کل سفارشات : <span class="orders-total"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom">میانگین سفارشات : <span class="orders-avg"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom"> سفارشات موفق: <span class="orders-success"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom"> سفارشات ناموفق: <span class="orders-fail"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="order-users" role="tabpanel" aria-labelledby="user">
                                    @include('back.statistics.orders.filter-tabs')

                                    <div id="order-users-chart" class="chart-area" style="min-height: 445px;" data-min-height="445px" data-action="{{ route('admin.statistics.orderUsers') }}"></div>

                                    <div class="col-12 mt-2">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom">کل سفارشات : <span class="orders-total"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom">میانگین سفارشات : <span class="orders-avg"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom"> سفارشات موفق: <span class="orders-success"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom"> سفارشات ناموفق: <span class="orders-fail"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="order-products" role="tabpanel" aria-labelledby="product">
                                    @include('back.statistics.orders.filter-tabs')

                                    <div id="order-products-chart" class="chart-area" style="min-height: 445px;" data-min-height="445px" data-action="{{ route('admin.statistics.orderProducts') }}"></div>

                                    <div class="col-12 mt-2">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom">کل سفارشات : <span class="orders-total"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom">میانگین سفارشات : <span class="orders-avg"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom"> سفارشات موفق: <span class="orders-success"></span></span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <span class="border-bottom"> سفارشات ناموفق: <span class="orders-fail"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ======== تب خریدهای قسطی ======== --}}
                                @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                                    <div class="tab-pane fade" id="order-installments" role="tabpanel" aria-labelledby="installment">
                                        @include('credit-pay::back.statistics.filter-tabs')

                                        {{-- نمودار ارزش طرح‌های اقساطی --}}
                                        <h6 class="mt-3 mb-2"><i class="fas fa-chart-line text-primary"></i> ارزش طرح‌های اقساطی</h6>
                                        <div id="installment-plan-values-chart" class="chart-area" style="min-height: 350px;" data-action="{{ route('admin.installment.statistics.planValues') }}"></div>
                                        <div class="col-12 mt-2 mb-3">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">کل: <span class="installments-total"></span></span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">موفق: <span class="installments-success"></span></span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">معوق: <span class="installments-fail"></span></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- نمودار تعداد طرح‌های اقساطی --}}
                                        <h6 class="mt-4 mb-2"><i class="fas fa-chart-bar text-info"></i> تعداد طرح‌های اقساطی</h6>
                                        <div id="installment-plan-counts-chart" class="chart-area" style="min-height: 350px;" data-action="{{ route('admin.installment.statistics.planCounts') }}"></div>
                                        <div class="col-12 mt-2 mb-3">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">کل: <span class="installments-total"></span></span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">موفق: <span class="installments-success"></span></span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">معوق: <span class="installments-fail"></span></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- نمودار مبلغ اقساط دریافتی --}}
                                        <h6 class="mt-4 mb-2"><i class="fas fa-money-bill-wave text-success"></i> مبلغ اقساط دریافتی</h6>
                                        <div id="installment-payment-values-chart" class="chart-area" style="min-height: 350px;" data-action="{{ route('admin.installment.statistics.paymentValues') }}"></div>
                                        <div class="col-12 mt-2 mb-3">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">کل: <span class="installments-total"></span></span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">دریافتی: <span class="installments-success"></span></span>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <span class="border-bottom">معوق: <span class="installments-fail"></span></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- دکمه مشاهده آمار کامل --}}
                                        <div class="text-center mt-4">
                                            <a href="{{ route('admin.installment.statistics.index') }}" class="btn btn-primary">
                                                <i class="fas fa-chart-pie"></i> مشاهده آمار کامل اقساطی
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>

@endsection

@include('back.partials.plugins', ['plugins' => ['apexcharts', 'persian-datepicker']])

@if(function_exists('module_is_active') && module_is_active('CreditPay'))
    @push('scripts')
        <script src="{{ module_asset('CreditPay', 'js/installment_statistics.js') }}?v=1"></script>
    @endpush
@endif

@push('scripts')
    <script src="{{ asset('back/assets/js/pages/statistics/orders.js') }}?v=3"></script>
@endpush

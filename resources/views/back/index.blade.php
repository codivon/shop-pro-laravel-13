@extends('back.layouts.master')
@push('styles')
    <link rel="stylesheet" type="text/css" href="{{asset('back/assets/css/pages/dashboard.css')}}">
@endpush

@section('content')

    <div class="app-content content d-wrap">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">

            {{-- Breadcrumb --}}
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb no-border">
                                    <li class="breadcrumb-item"><i class="feather icon-home"></i> مدیریت</li>
                                    <li class="breadcrumb-item active">داشبورد</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">

                {{-- ═══ Banner ═══ --}}
                <div class="d-banner mb-2">
                    <div>
                        <h2 class="d-banner-title">
                            <i class="feather icon-grid"></i> داشبورد مدیریت
                        </h2>
                        <p class="d-banner-sub">خوش آمدید — آمار و اطلاعات کسب‌وکار خود را دنبال کنید.</p>
                    </div>
                    <div class="d-date-chip">
                        <i class="feather icon-calendar"></i>
                        <div>
                            <span class="d-date-chip-label">تاریخ امروز</span>
                            <span class="d-date-chip-value">{{ jdate()->format('d F Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="">

                    {{-- ═══ Stat Cards ═══ --}}
                    <div class="d-stats-row">
                        @can('users.index')
                            <div class="d-stat d-stat--blue">
                                <div class="d-stat-icon d-stat-icon--blue">
                                    <i class="feather icon-users"></i>
                                </div>
                                <div>
                                    <div class="d-stat-value">{{ number_format($users_count) }}</div>
                                    <div class="d-stat-label">کل کاربران</div>
                                </div>
                            </div>
                        @endcan

                        @can('products.index')
                            <div class="d-stat d-stat--amber">
                                <div class="d-stat-icon d-stat-icon--amber">
                                    <i class="feather icon-shopping-cart"></i>
                                </div>
                                <div>
                                    <div class="d-stat-value">{{ number_format($products_count) }}</div>
                                    <div class="d-stat-label">محصولات فعال</div>
                                </div>
                            </div>
                        @endcan

                        @can('orders.index')
                            <div class="d-stat d-stat--green">
                                <div class="d-stat-icon d-stat-icon--green">
                                    <i class="feather icon-briefcase"></i>
                                </div>
                                <div>
                                    <div class="d-stat-value">{{ number_format($orders_count) }}</div>
                                    <div class="d-stat-label">سفارشات</div>
                                </div>
                            </div>
                        @endcan
                    </div>

                    {{-- ═══ Main Two-Column Grid ═══ --}}
                    <div class="row">

                        {{-- ── Left/Main column ── --}}
                        <div class="col-md-8">

                            @can('orders.index')
                                {{-- Statistics Tabs Card --}}
                                <div class="d-card" id="statistics-card">

                                    {{-- Tab Nav --}}
                                    <div class="d-tabs" id="orderstab" role="tablist">
                                        <button class="d-tab active" data-toggle="tab" data-target="#order-values" role="tab" type="button">
                                            <i class="feather icon-dollar-sign"></i> ارزش
                                        </button>
                                        <button class="d-tab" data-toggle="tab" data-target="#order-counts" role="tab" type="button">
                                            <i class="feather icon-shopping-bag"></i> تعداد
                                        </button>
                                        <button class="d-tab" data-toggle="tab" data-target="#order-users" role="tab" type="button">
                                            <i class="feather icon-users"></i> کاربران
                                        </button>
                                        <button class="d-tab" data-toggle="tab" data-target="#order-products" role="tab" type="button">
                                            <i class="feather icon-package"></i> محصولات
                                        </button>
                                        @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                                            <button class="d-tab" data-toggle="tab" data-target="#order-installments" role="tab" type="button">
                                                <i class="fas fa-money-check-alt"></i> اقساطی
                                            </button>
                                        @endif
                                    </div>

                                    <div class="tab-content">
                                        {{-- Value Tab --}}
                                        <div class="tab-pane fade show active" id="order-values" role="tabpanel">
                                            @include('back.statistics.orders.filter-tabs')
                                            <div id="order-values-chart" class="chart-area" style="min-height:445px;" data-min-height="445px" data-action="{{ route('admin.statistics.orderValues') }}"></div>
                                            <div class="d-chart-stats">
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--blue"><span class="orders-total"></span></div>
                                                    <div class="d-chart-stat-lbl">کل سفارشات</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--amber"><span class="orders-avg"></span></div>
                                                    <div class="d-chart-stat-lbl">میانگین</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--green"><span class="orders-success"></span></div>
                                                    <div class="d-chart-stat-lbl">موفق</div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Count Tab --}}
                                        <div class="tab-pane fade" id="order-counts" role="tabpanel">
                                            @include('back.statistics.orders.filter-tabs')
                                            <div id="order-counts-chart" class="chart-area" style="min-height:445px;" data-min-height="445px" data-action="{{ route('admin.statistics.orderCounts') }}"></div>
                                            <div class="d-chart-stats d-chart-stats--4">
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--blue"><span class="orders-total"></span></div>
                                                    <div class="d-chart-stat-lbl">کل</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--amber"><span class="orders-avg"></span></div>
                                                    <div class="d-chart-stat-lbl">میانگین</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--green"><span class="orders-success"></span></div>
                                                    <div class="d-chart-stat-lbl">موفق</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--red"><span class="orders-fail"></span></div>
                                                    <div class="d-chart-stat-lbl">ناموفق</div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Users Tab --}}
                                        <div class="tab-pane fade" id="order-users" role="tabpanel">
                                            @include('back.statistics.orders.filter-tabs')
                                            <div id="order-users-chart" class="chart-area" style="min-height:445px;" data-min-height="445px" data-action="{{ route('admin.statistics.orderUsers') }}"></div>
                                            <div class="d-chart-stats d-chart-stats--4">
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--blue"><span class="orders-total"></span></div>
                                                    <div class="d-chart-stat-lbl">کل</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--amber"><span class="orders-avg"></span></div>
                                                    <div class="d-chart-stat-lbl">میانگین</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--green"><span class="orders-success"></span></div>
                                                    <div class="d-chart-stat-lbl">موفق</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--red"><span class="orders-fail"></span></div>
                                                    <div class="d-chart-stat-lbl">ناموفق</div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Products Tab --}}
                                        <div class="tab-pane fade" id="order-products" role="tabpanel">
                                            @include('back.statistics.orders.filter-tabs')
                                            <div id="order-products-chart" class="chart-area" style="min-height:445px;" data-min-height="445px" data-action="{{ route('admin.statistics.orderProducts') }}"></div>
                                            <div class="d-chart-stats d-chart-stats--4">
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--blue"><span class="orders-total"></span></div>
                                                    <div class="d-chart-stat-lbl">کل</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--amber"><span class="orders-avg"></span></div>
                                                    <div class="d-chart-stat-lbl">میانگین</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--green"><span class="orders-success"></span></div>
                                                    <div class="d-chart-stat-lbl">موفق</div>
                                                </div>
                                                <div class="d-chart-stat">
                                                    <div class="d-chart-stat-val d-chart-stat-val--red"><span class="orders-fail"></span></div>
                                                    <div class="d-chart-stat-lbl">ناموفق</div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Installments Tab --}}
                                        @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                                            <div class="tab-pane fade" id="order-installments" role="tabpanel">
                                                @include('credit-pay::back.statistics.filter-tabs')
                                                <div id="installment-plan-values-chart" class="chart-area" style="min-height:445px;" data-min-height="445px" data-action="{{ route('admin.installment.statistics.planValues') }}"></div>
                                                <div class="d-chart-stats d-chart-stats--4">
                                                    <div class="d-chart-stat">
                                                        <div class="d-chart-stat-val d-chart-stat-val--blue"><span class="installments-total"></span></div>
                                                        <div class="d-chart-stat-lbl">کل</div>
                                                    </div>
                                                    <div class="d-chart-stat">
                                                        <div class="d-chart-stat-val d-chart-stat-val--green"><span class="installments-success"></span></div>
                                                        <div class="d-chart-stat-lbl">موفق</div>
                                                    </div>
                                                    <div class="d-chart-stat">
                                                        <div class="d-chart-stat-val d-chart-stat-val--red"><span class="installments-fail"></span></div>
                                                        <div class="d-chart-stat-lbl">معوق</div>
                                                    </div>
                                                    <div class="d-chart-stat">
                                                        <a href="{{ route('admin.installment.statistics.index') }}" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-chart-bar"></i> آمار کامل
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Latest Orders --}}
                                <div class="d-card">
                                    <div class="d-card-head">
                                        <h4 class="d-card-head-title">
                                            <i class="feather icon-shopping-bag"></i> آخرین سفارشات
                                        </h4>
                                        <div class="heading-elements">
                                            <ul class="list-inline mb-0">
                                                <li><a data-action="collapse"><i class="feather icon-chevron-down" style="color:var(--d-muted)"></i></a></li>
                                                <li><a data-action="expand"><i class="feather icon-maximize" style="color:var(--d-muted)"></i></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="card-content">
                                        @if(count($orders))
                                            <div class="table-responsive">
                                                <table class="d-table">
                                                    <thead>
                                                    <tr>
                                                        <th>ردیف</th>
                                                        <th>شماره سفارش</th>
                                                        <th>تاریخ ثبت</th>
                                                        <th>قیمت کل</th>
                                                        <th>وضعیت</th>
                                                        <th>عملیات</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($orders as $order)
                                                        @php
                                                            $sc = match($order->status) {
                                                                'paid'     => 'success',
                                                                'unpaid'   => 'danger',
                                                                'canceled' => 'warning',
                                                                default    => 'info',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td style="color:var(--d-muted)">{{ $loop->iteration }}</td>
                                                            <td><span class="d-order-id color-white">#{{ $order->id }}</span></td>
                                                            <td style="color:var(--d-muted)">
                                                                {{ jdate($order->created_at)->format('%d %B %Y') }}
                                                            </td>
                                                            <td style="font-weight:600;">
                                                                <span class="d-badge d-badge--info"> {{ trans('messages.currency.prefix') . number_format($order->price) . trans('messages.currency.suffix') }}</span>

                                                            </td>
                                                            <td><span class="d-badge d-badge--{{ $sc }}">{{ $order->statusText() }}</span></td>
                                                            <td class="orders-badge">
                                                                <span class="d-block" style="overflow: visible; position: relative; width: 249px;">
                                                                     <ul class="mb-0">
                                                                    @foreach($order->items as $order_item)
                                                                             @php
                                                                                 if ($order_item->seller_id && $order_item->seller) {
                        // با فروشنده
                        $seller = $order_item->seller->seller_info;
                        $itemsArray = [
                            'id' => $order_item->id,
                            'status' => $order_item->shipping_status,
                            'sellerName' => $seller->business_name ?? 'فروشنده',
                            'sellerId' => $order_item->seller_id,
                            'link' => route('admin.orders.show-item', $order_item->id),
                        ];
                    } else {
                        // بدون فروشنده (فروشگاه اصلی)
                        $itemsArray = [
                            'id' => $order_item->id,
                            'status' => $order_item->shipping_status,
                            'sellerName' => 'فروشگاه اصلی',
                            'sellerId' => null,
                            'link' => route('admin.orders.show-item', $order_item->id),
                        ];
                    }
                                                                             @endphp
                                                                             <li class="d-inline-flex align-items-center justify-content-between w-100 border-bottom">
                                                                            <div class="d-flex flex-column justify-content-center">
                                                                                <div class="lts-05">
                                                                                    <span class="fs-9 text-gray">شناسه مرسوله: </span>
                                                                                    <span class="fw-bold text-dark fs-8">{{$itemsArray['id']}}</span>
                                                                                </div>
                                                                                <div class="lts-05">
                                                                                    <span class="fs-9 text-gray">ارسال توسط: </span>
                                                                                    <span class="text-dark fw-bold fs-8">{{$itemsArray['sellerName']}}</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="d-flex flex-column justify-content-center ms-4">
                                                                                <div class="status lts-05">
                                                                                    <span class="badge badge-secondary">{{$itemsArray['status']}}</span>
                                                                                </div>
                                                                                <a href="{{ $itemsArray['link'] }}" class="btn btn-outline-light pt-1 pb-1 lts-05" type="button" onclick="viewShipment(22)">
                                                                                    <span>بررسی</span>
                                                                                </a>
                                                                            </div>
                                                                        </li>

                                                                         @endforeach
                                                                </ul>
                                                                </span>



                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <a href="{{ route('admin.orders.index') }}" class="d-card-footer">
                                                <i class="feather icon-list" style="font-size:14px"></i> مشاهده همه سفارشات
                                            </a>
                                        @else
                                            <div class="d-empty">
                                                <i class="feather icon-inbox"></i>
                                                <p>چیزی برای نمایش وجود ندارد!</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endcan

                            @can('products.index')
                                {{-- Best Selling Products --}}
                                <div class="d-card">
                                    <div class="d-card-head">
                                        <h4 class="d-card-head-title">
                                            <i class="feather icon-trending-up icon--amber"></i> پرفروش‌ترین محصولات
                                        </h4>
                                        <div class="heading-elements">
                                            <ul class="list-inline mb-0">
                                                <li><a data-action="collapse"><i class="feather icon-chevron-down" style="color:var(--d-muted)"></i></a></li>
                                                <li><a data-action="expand"><i class="feather icon-maximize" style="color:var(--d-muted)"></i></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="card-content">
                                        @if(count($sale_products))
                                            <div class="table-responsive">
                                                <table class="d-table">
                                                    <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>تصویر</th>
                                                        <th>عنوان محصول</th>
                                                        <th>تاریخ ایجاد</th>
                                                        <th>موجودی</th>
                                                        <th>فروش</th>
                                                        <th>وضعیت</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($sale_products as $product)
                                                        <tr>
                                                            <td style="color:var(--d-muted);font-size:12px">#{{ $product->id }}</td>
                                                            <td>
                                                                <a target="_blank" href="{{ route('front.products.show', ['product' => $product]) }}">
                                                                    <img class="d-product-thumb" src="{{ $product->image ? asset($product->image) : asset('/empty.svg') }}" alt="{{ $product->title }}">
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <a target="_blank" href="{{ route('front.products.show', ['product' => $product]) }}" style="text-decoration:none;font-weight:500;">
                                                                    {{ $product->title }}
                                                                </a>
                                                            </td>
                                                            <td style="color:var(--d-muted)">{{ jdate($product->created_at)->format('%d %B %Y') }}</td>
                                                            <td><span class="d-badge d-badge--info">{{ $product->prices()->sum('stock') }}</span></td>
                                                            <td><span class="d-badge d-badge--purple">{{ $product->sell }}</span></td>
                                                            <td>
                                                                <div style="display:flex;flex-direction:column;gap:4px;">
                                                                    @if($product->isPublished())
                                                                        <span class="d-status d-status--success">منتشر شده</span>
                                                                    @else
                                                                        <span class="d-status d-status--danger">پیش‌نویس</span>
                                                                    @endif
                                                                    @if($product->status === 'Accept')
                                                                        <span class="d-status d-status--success">تایید شده</span>
                                                                    @elseif($product->status === 'Waiting')
                                                                        <span class="d-status d-status--warning">در انتظار</span>
                                                                    @elseif($product->status === 'Reject')
                                                                        <span class="d-status d-status--danger">رد شده</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="d-empty">
                                                <i class="feather icon-package"></i>
                                                <p>چیزی برای نمایش وجود ندارد!</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Most Viewed Products --}}
                                <div class="d-card">
                                    <div class="d-card-head">
                                        <h4 class="d-card-head-title">
                                            <i class="feather icon-eye icon--purple"></i> پربازدیدترین محصولات
                                        </h4>
                                        <div class="heading-elements">
                                            <ul class="list-inline mb-0">
                                                <li><a data-action="collapse"><i class="feather icon-chevron-down" style="color:var(--d-muted)"></i></a></li>
                                                <li><a data-action="expand"><i class="feather icon-maximize" style="color:var(--d-muted)"></i></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="card-content">
                                        @if(count($view_products))
                                            <div class="table-responsive">
                                                <table class="d-table">
                                                    <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>تصویر</th>
                                                        <th>عنوان محصول</th>
                                                        <th>تاریخ ایجاد</th>
                                                        <th>موجودی</th>
                                                        <th>بازدید</th>
                                                        <th>وضعیت</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($view_products as $product)
                                                        <tr>
                                                            <td style="color:var(--d-muted);font-size:12px">#{{ $product->id }}</td>
                                                            <td>
                                                                <a target="_blank" href="{{ route('front.products.show', ['product' => $product]) }}">
                                                                    <img class="d-product-thumb" src="{{ $product->image ? asset($product->image) : asset('/empty.svg') }}" alt="{{ $product->title }}">
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <a target="_blank" href="{{ route('front.products.show', ['product' => $product]) }}" style="text-decoration:none;font-weight:500;">
                                                                    {{ $product->title }}
                                                                </a>
                                                            </td>
                                                            <td style="color:var(--d-muted)">{{ jdate($product->created_at)->format('%d %B %Y') }}</td>
                                                            <td><span class="d-badge d-badge--info">{{ $product->prices()->sum('stock') }}</span></td>
                                                            <td><span class="d-badge d-badge--purple">{{ $product->view }}</span></td>
                                                            <td>
                                                                <div style="display:flex;flex-direction:column;gap:4px;">
                                                                    @if($product->isPublished())
                                                                        <span class="d-status d-status--success">منتشر شده</span>
                                                                    @else
                                                                        <span class="d-status d-status--danger">پیش‌نویس</span>
                                                                    @endif
                                                                    @if($product->status === 'Accept')
                                                                        <span class="d-status d-status--success">تایید شده</span>
                                                                    @elseif($product->status === 'Waiting')
                                                                        <span class="d-status d-status--warning">در انتظار</span>
                                                                    @elseif($product->status === 'Reject')
                                                                        <span class="d-status d-status--danger">رد شده</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="d-empty">
                                                <i class="feather icon-package"></i>
                                                <p>چیزی برای نمایش وجود ندارد!</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endcan

                        </div>{{-- /main column --}}

                        {{-- ── Right Sidebar column ── --}}
                        <div class="col-md-4">

                            @can('statistics.users')
                                {{-- Weekly Views Chart --}}
                                <div class="d-card">
                                    <div class="d-chart-head">
                                        <div class="d-chart-head-icon">
                                            <i class="feather icon-eye"></i>
                                        </div>
                                        <p class="d-chart-head-title">بازدیدهای این هفته</p>
                                    </div>
                                    <div class="card-content">
                                        <div id="line-area-chart-1"></div>
                                    </div>
                                </div>

                                {{-- Weekly Visitors Chart --}}
                                <div class="d-card">
                                    <div class="d-chart-head">
                                        <div class="d-chart-head-icon d-chart-head-icon--red">
                                            <i class="feather icon-user" style="color:var(--d-red)"></i>
                                        </div>
                                        <p class="d-chart-head-title">بازدیدکنندگان این هفته</p>
                                    </div>
                                    <div class="card-content">
                                        <div id="line-area-chart-3"></div>
                                    </div>
                                </div>
                            @endcan

                            @can('comments.index')
                                {{-- Latest Reviews --}}
                                <div class="d-card">
                                    <div class="d-card-head">
                                        <h5 class="d-card-head-title">
                                            <i class="feather icon-message-square icon--green"></i> آخرین دیدگاه‌ها
                                        </h5>
                                        <a href="{{ route('admin.reviews.index') }}" class="d-card-action">
                                            مشاهده همه <i class="feather icon-arrow-left" style="font-size:12px"></i>
                                        </a>
                                    </div>
                                    @if(count($reviews))
                                        @foreach($reviews as $review)
                                            @php
                                                $rs = match($review->status) {
                                                    'pending'  => ['warning', 'منتظر تایید'],
                                                    'accepted' => ['success', 'منتشر شده'],
                                                    default    => ['danger', 'تایید نشده'],
                                                };
                                            @endphp
                                            <div class="d-list-item">
                                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                                                    <a target="_blank" href="{{ route('admin.users.show', ['user' => $review->user]) }}">
                                                        <img class="d-avatar-img" src="{{ $review->user->imageUrl }}" alt="Avatar">
                                                    </a>
                                                    <div style="flex:1;min-width:0;">
                                                        <a class="d-comment-name" target="_blank" href="{{ route('admin.users.show', ['user' => $review->user]) }}">
                                                            {{ $review->user->first_name ? $review->user->fullname : $review->user->username }}
                                                        </a>
                                                        <div class="d-comment-time">{{ jdate($review->created_at)->ago() }}</div>
                                                    </div>
                                                    <span class="d-badge d-badge--{{ $rs[0] }}">{{ $rs[1] }}</span>
                                                </div>
                                                <div class="d-comment-body">{{ short_content($review->body, 20, false) }}</div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="d-empty">
                                            <i class="feather icon-message-square"></i>
                                            <p>هنوز دیدگاهی ثبت نشده است.</p>
                                        </div>
                                    @endif
                                    <a href="{{ route('admin.reviews.index') }}" class="d-card-footer">
                                        <i class="feather icon-message-square" style="font-size:14px"></i> مشاهده همه دیدگاه‌ها
                                    </a>
                                </div>

                                {{-- Latest Questions --}}
                                <div class="d-card">
                                    <div class="d-card-head">
                                        <h5 class="d-card-head-title">
                                            <i class="feather icon-help-circle icon--blue"></i> آخرین پرسش‌ها
                                        </h5>
                                        <a href="{{ route('admin.comments.products') }}" class="d-card-action">
                                            مشاهده همه <i class="feather icon-arrow-left" style="font-size:12px"></i>
                                        </a>
                                    </div>
                                    @if(count($questions))
                                        @foreach($questions as $question)
                                            @php
                                                $qs = match($question->status) {
                                                    'pending'  => ['warning', 'منتظر تایید'],
                                                    'accepted' => ['success', 'منتشر شده'],
                                                    default    => ['danger', 'تایید نشده'],
                                                };
                                            @endphp
                                            <div class="d-list-item">
                                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                                                    <a target="_blank" href="{{ route('admin.users.show', ['user' => $question->user]) }}">
                                                        <img class="d-avatar-img" src="{{ $question->user->imageUrl }}" alt="Avatar">
                                                    </a>
                                                    <div style="flex:1;min-width:0;">
                                                        <a class="d-comment-name" target="_blank" href="{{ route('admin.users.show', ['user' => $question->user]) }}">
                                                            {{ $question->user->first_name ? $question->user->fullname : $question->user->username }}
                                                        </a>
                                                        <div class="d-comment-time">{{ jdate($question->created_at)->ago() }}</div>
                                                    </div>
                                                    <span class="d-badge d-badge--{{ $qs[0] }}">{{ $qs[1] }}</span>
                                                </div>
                                                <div class="d-comment-body">{{ $question->body }}</div>
                                                @if($question->product())
                                                    <div style="margin-top:6px;">
                                                        <a target="_blank" href="{{ route('front.products.show', ['product' => $question->product()]) }}" class="d-card-action" style="font-size:11px;">
                                                            <i class="feather icon-external-link" style="font-size:11px"></i> نمایش محصول
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="d-empty">
                                            <i class="feather icon-help-circle"></i>
                                            <p>هنوز پرسشی ثبت نشده است.</p>
                                        </div>
                                    @endif
                                    <a href="{{ route('admin.comments.products') }}" class="d-card-footer">
                                        <i class="feather icon-help-circle" style="font-size:14px"></i> مشاهده همه پرسش‌ها
                                    </a>
                                </div>
                            @endcan

                            @can('statistics.users')
                                {{-- Active Sellers --}}
                                <div class="d-card">
                                    <div class="d-card-head">
                                        <h5 class="d-card-head-title">
                                            <i class="feather icon-briefcase icon--amber"></i> فروشندگان فعال
                                        </h5>
                                    </div>
                                    @if(count($active_sellers))
                                        @foreach($active_sellers as $active_seller)
                                            <div class="d-user-item">
                                                <img class="d-avatar-img" src="{{ $active_seller->seller->imageUrl }}" alt="{{ $active_seller->seller->business_name }}">
                                                <div style="flex:1;min-width:0;">
                                                    <div class="d-user-name">{{ $active_seller->seller->business_name }}</div>
                                                    <div class="d-user-meta">
                                                        @if(in_array(jdate($active_seller->created_at)->ago(), ['0 ثانیه پیش', '1 ثانیه پیش']))
                                                            <span class="d-online">آنلاین</span>
                                                        @else
                                                            {{ jdate($active_seller->created_at)->ago() }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <a target="_blank" href="{{ route('admin.sellers.show', ['seller' => $active_seller->seller]) }}" class="d-btn">
                                                    <i class="feather icon-user"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="d-empty">
                                            <i class="feather icon-briefcase"></i>
                                            <p>چیزی برای نمایش وجود ندارد.</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Active Users --}}
                                <div class="d-card">
                                    <div class="d-card-head">
                                        <h5 class="d-card-head-title">
                                            <i class="feather icon-users icon--blue"></i> مشتریان فعال
                                        </h5>
                                    </div>
                                    @if(count($active_users))
                                        @foreach($active_users as $active_user)
                                            <div class="d-user-item">
                                                <img class="d-avatar-img" src="{{ $active_user->user->imageUrl }}" alt="{{ $active_user->user->username }}">
                                                <div style="flex:1;min-width:0;">
                                                    <div class="d-user-name">
                                                        {{ $active_user->user->first_name ? $active_user->user->fullname : $active_user->user->username }}
                                                    </div>
                                                    <div class="d-user-meta">
                                                        @if(in_array(jdate($active_user->created_at)->ago(), ['0 ثانیه پیش', '1 ثانیه پیش']))
                                                            <span class="d-online">آنلاین</span>
                                                        @else
                                                            {{ jdate($active_user->created_at)->ago() }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <a target="_blank" href="{{ route('admin.users.show', ['user' => $active_user->user]) }}" class="d-btn">
                                                    <i class="feather icon-user"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="d-empty">
                                            <i class="feather icon-users"></i>
                                            <p>چیزی برای نمایش وجود ندارد.</p>
                                        </div>
                                    @endif
                                </div>
                            @endcan

                        </div>{{-- /sidebar column --}}

                    </div>{{-- /d-grid --}}
                </div>{{-- /d-body --}}

                {{-- ═══ Installment Dashboard Widget ═══ --}}
                {{-- ═══ Module Dashboard Widgets (Dynamic) ═══ --}}
                @php
                    $dashboardWidgets = class_exists(\App\Services\ModuleWidgetRegistry::class)
                        ? \App\Services\ModuleWidgetRegistry::all()
                        : [];
                @endphp
                @if(!empty($dashboardWidgets))
                    <div class="row mt-3">
                        <div class="col-12">
                            @foreach($dashboardWidgets as $widgetView)
                                @if(view()->exists($widgetView))
                                    @include($widgetView)
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>{{-- /content-body --}}
        </div>{{-- /content-wrapper --}}
    </div>{{-- /d-wrap --}}

@endsection

@include('back.partials.plugins', ['plugins' => ['apexcharts', 'persian-datepicker']])

@if(function_exists('module_is_active') && module_is_active('CreditPay'))
    @push('scripts')
        <script src="{{ module_asset('CreditPay', 'js/installment_statistics.js') }}?v=1"></script>
    @endpush
@endif

@push('scripts')

    {{-- Tab switching — replaces Bootstrap tab toggling for d-tab buttons --}}
    <script>
        document.querySelectorAll('[data-toggle="tab"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                // deactivate siblings
                btn.closest('[role="tablist"]').querySelectorAll('.d-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                btn.classList.add('active');

                // hide all panes
                document.querySelectorAll('.tab-pane').forEach(function(p) {
                    p.classList.remove('show', 'active');
                });

                // show target
                var target = document.querySelector(btn.dataset.target);
                if (target) {
                    target.classList.add('show', 'active');
                }
            });

            $('li.nav-link[data-period="monthly"]').click();
        });

        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                const element = document.querySelector('li.nav-link[data-period="monthly"]');
                if (element) {
                    element.click();
                }
            }, 300); // ۳۰۰ میلی‌ثانیه تأخیر
        });
    </script>

    <script>
        @php
            $data   = viewers_data(7);
            $labels = array_keys($data);
            $views  = array_values($data);

        @endphp

        var viewerChartLabels = [{!! array_to_string($labels) !!}];
        var ViewerChartData   = [{!! array_to_string($views) !!}];

        @php
            $data   = ip_data(7);
            $labels = array_keys($data);
            $views  = array_values($data);
        @endphp

        var ipChartLabels = [{!! array_to_string($labels) !!}];
        var ipChartData   = [{!! array_to_string($views) !!}];
    </script>

    <script src="{{ asset('back/assets/js/pages/statistics/orders.js') }}?v=2"></script>
    <script src="{{ asset('back/assets/js/pages/dashboard-ecommerce.js') }}"></script>
@endpush

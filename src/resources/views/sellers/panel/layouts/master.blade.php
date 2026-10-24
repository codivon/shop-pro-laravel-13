{{-- لایوت پنل فروشنده سانیار (فاز G) — الگوی پنل کاربر فاز F: سایدبار آفسنتی + کارت فروشنده
    $active (اختیاری) → آیتم فعال منو — routeIs بین صفحات --}}
@extends('front::layouts.master')

@php
    $panel_menu = [
        ['route' => 'seller.dashboard', 'active' => 'dashboard', 'icon' => 'bi-speedometer2', 'label' => 'داشبورد'],
        ['header' => 'محصولات'],
        ['route' => 'seller.products.find', 'active' => 'products-find', 'icon' => 'bi-search', 'label' => 'جستجو یا درج محصول'],
        ['route' => 'seller.products.index', 'active' => 'products', 'icon' => 'bi-box-seam', 'label' => 'مدیریت محصولات'],
        ['header' => 'سفارش‌ها'],
        ['route' => 'seller.orders.index', 'active' => 'orders', 'icon' => 'bi-cart-check', 'label' => 'تاریخچه سفارش‌ها'],
        ['route' => 'seller.orders.notCompleted', 'active' => 'orders-not', 'icon' => 'bi-hourglass-split', 'label' => 'محصولات منتظر ارسال'],
        ['header' => 'مالی'],
        ['route' => 'seller.wallet.index', 'active' => 'wallet', 'icon' => 'bi-wallet2', 'label' => 'کیف پول و واریزی‌ها'],
        ['route' => 'seller.commission', 'active' => 'commission', 'icon' => 'bi-percent', 'label' => 'کمیسیون و هزینه‌ها'],
        ['header' => 'حمل‌ونقل'],
        ['route' => 'seller.carriers.index', 'active' => 'carriers', 'icon' => 'bi-truck', 'label' => 'حامل‌های من'],
        ['route' => 'seller.tariffs.index', 'active' => 'tariffs', 'icon' => 'bi-signpost-split', 'label' => 'تعرفه‌های ارسال'],
        ['header' => 'تعامل و پشتیبانی'],
        ['route' => 'seller.questions.index', 'active' => 'questions', 'icon' => 'bi-patch-question', 'label' => 'پرسش و پاسخ'],
        ['route' => 'seller.statistics.index', 'active' => 'statistics', 'icon' => 'bi-graph-up-arrow', 'label' => 'آمار و بازدید'],
        ['route' => 'seller.tickets.index', 'active' => 'tickets', 'icon' => 'bi-life-preserver', 'label' => 'درخواست پشتیبانی'],
        ['route' => 'seller.notifications.index', 'active' => 'notifications', 'icon' => 'bi-bell', 'label' => 'پیام‌ها'],
        ['header' => 'حساب'],
        ['route' => 'seller.profile.index', 'active' => 'profile', 'icon' => 'bi-person-gear', 'label' => 'پروفایل شما'],
    ];
    $panel_active = $active ?? null;
@endphp

@section('content')
    {{-- بردکرامب پنل فروشنده --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">پنل فروشندگان</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-20">
        <div class="container-fluid">

            {{-- دکمه شناور منو — فقط موبایل --}}
            <div class="custom-filter d-lg-none d-block">
                <button class="btn btn-filter-float border-0 main-color-two-bg shadow-box px-4 rounded-3 position-fixed"
                        style="z-index: 999; bottom: 80px;" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#saniarSellerPanel" aria-controls="saniarSellerPanel">
                    <i class="bi bi-list font-20 fw-bold text-white"></i>
                    <span class="d-block font-14 text-white">منو</span>
                </button>
            </div>

            <div class="row gy-3">
                {{-- سایدبار پنل (در موبایل offcanvas) --}}
                <div class="offcanvas-lg offcanvas-start col-lg-3 d-lg-block px-0" tabindex="-1" id="saniarSellerPanel"
                     aria-labelledby="saniarSellerPanelLabel" style="--bs-offcanvas-width: 300px;">
                    <div class="offcanvas-header d-lg-none border-bottom">
                        <h5 class="offcanvas-title" id="saniarSellerPanelLabel">
                            <i class="bi bi-shop main-color-one-color me-1"></i> پنل فروشندگان
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
                    </div>
                    <div class="offcanvas-body p-0 p-lg-2">
                        <div class="panel-side position-sticky top-0">
                            <div class="content-box pb-0">
                                <div class="container-fluid">
                                    {{-- کارت فروشنده (داینامیک) --}}
                                    <div class="panel-box">
                                        <div class="d-flex align-items-center">
                                            @if(seller_info() && seller_info()->logo)
                                                <img class="img-fluid img-profile-panel rounded-circle me-3 shadow-md"
                                                     src="{{ asset(seller_info()->logo) }}"
                                                     alt="{{ seller_info()->business_name ?? 'فروشنده' }}"
                                                     style="width:56px;height:56px;object-fit:cover;">
                                            @else
                                                <span class="main-color-one-bg text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                      style="width:56px;height:56px;">
                                                    <i class="bi bi-shop font-24"></i>
                                                </span>
                                            @endif
                                            <div class="d-grid gap-2">
                                                <h6 class="font-14 main-color-one-color mb-0">پنل فروشندگان</h6>
                                                <h6 class="font-14 mb-0">{{ seller_info()->business_name ?? seller()->email }}</h6>
                                            </div>
                                        </div>
                                        <hr class="my-3">
                                        {{-- کیف پول فروشنده (داینامیک) --}}
                                        <a href="{{ route('seller.wallet.index') }}" class="d-flex align-items-center justify-content-between text-decoration-none text-dark py-2">
                                            <span class="font-14"><i class="bi bi-wallet2 me-1 main-color-one-color"></i> کیف پول</span>
                                            <span class="font-14 fw-bold main-color-one-color">{{ number_format(seller()->getWallet()->balance()) }} <span class="font-12 text-muted">تومان</span></span>
                                        </a>
                                    </div>

                                    {{-- منوی پنل فروشنده --}}
                                    <div class="profile-box">
                                        <nav class="navbar profile-box-nav p-0" aria-label="منوی پنل فروشنده">
                                            <ul class="navbar-nav w-100">
                                                @foreach($panel_menu as $item)
                                                    @if(!empty($item['header']))
                                                        <li class="nav-item">
                                                            <h6 class="font-12 text-muted fw-bold px-2 pt-3 pb-1 mb-0">{{ $item['header'] }}</h6>
                                                        </li>
                                                    @else
                                                        @php $is_active = ($panel_active == $item['active']); @endphp
                                                        <li class="nav-item">
                                                            <a href="{{ route($item['route']) }}"
                                                               class="nav-link d-flex align-items-center py-2 px-2 rounded-3 {{ $is_active ? 'main-color-one-bg text-white' : 'text-dark' }}">
                                                                <i class="bi {{ $item['icon'] }} me-2 {{ $is_active ? 'text-white' : 'main-color-one-color' }}"></i>
                                                                <span class="font-14">{{ $item['label'] }}</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                                <li class="nav-item">
                                                    <a href="{{ route('seller.logout') }}"
                                                       class="nav-link d-flex align-items-center py-2 px-2 rounded-3 text-danger">
                                                        <i class="bi bi-box-arrow-left me-2"></i>
                                                        <span class="font-14">خروج از پنل</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- محتوای پنل --}}
                <div class="col-lg-9">
                    @yield('seller-content')
                </div>
            </div>
        </div>
    </div>
@endsection

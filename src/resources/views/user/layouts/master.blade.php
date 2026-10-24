{{-- لایوت پنل کاربر سانیار — $active (اختیاری برای حالت فعال منو)؛ $user و $random_products از کامپوزر تم --}}
@extends('front::layouts.master')

@php
    $account_menu = [
        ['route' => 'front.user.profile', 'active' => 'profile', 'icon' => 'bi-person', 'label' => 'پروفایل'],
        ['route' => 'front.orders.index', 'active' => 'orders', 'icon' => 'bi-cart-check', 'label' => 'همه سفارش‌ها'],
        ['route' => 'front.favorites.index', 'active' => 'favorites', 'icon' => 'bi-heart', 'label' => 'علاقه‌مندی‌ها'],
        ['route' => 'front.wallet.index', 'active' => 'wallet', 'icon' => 'bi-wallet2', 'label' => 'کیف پول'],
        ['route' => 'front.addresses.index', 'active' => 'addresses', 'icon' => 'bi-pin-map', 'label' => 'آدرس‌های من'],
        ['route' => 'front.notifications.index', 'active' => 'notifications', 'icon' => 'bi-bell', 'label' => 'پیام‌ها و اطلاعیه‌ها'],
        ['route' => 'front.comments.index', 'active' => 'comments', 'icon' => 'bi-chat-dots', 'label' => 'نظرات من'],
        ['route' => 'front.tickets.index', 'active' => 'tickets', 'icon' => 'bi-life-preserver', 'label' => 'درخواست پشتیبانی'],
    ];
@endphp

@section('content')
    {{-- بردکرامب پنل کاربر --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">حساب کاربری من</li>
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
                        data-bs-target="#saniarUserPanel" aria-controls="saniarUserPanel">
                    <i class="bi bi-list font-20 fw-bold text-white"></i>
                    <span class="d-block font-14 text-white">منو</span>
                </button>
            </div>

            <div class="row gy-3">
                {{-- سایدبار حساب (در موبایل offcanvas) --}}
                <div class="offcanvas-lg offcanvas-start col-lg-3 d-lg-block px-0" tabindex="-1" id="saniarUserPanel"
                     aria-labelledby="saniarUserPanelLabel" style="--bs-offcanvas-width: 300px;">
                    <div class="offcanvas-header d-lg-none border-bottom">
                        <h5 class="offcanvas-title" id="saniarUserPanelLabel">
                            <i class="bi bi-person-circle main-color-one-color me-1"></i> حساب کاربری
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
                    </div>
                    <div class="offcanvas-body p-0 p-lg-2">
                        <div class="panel-side position-sticky top-0">
                            <div class="content-box pb-0">
                                <div class="container-fluid">
                                    {{-- کارت کاربر (داینامیک) --}}
                                    <div class="panel-box">
                                        <div class="d-flex align-items-center">
                                            @if($user && $user->profile)
                                                <img class="img-fluid img-profile-panel rounded-circle me-3 shadow-md"
                                                     src="{{ asset($user->profile) }}" alt="{{ $user->fullname ?? 'کاربر' }}"
                                                     style="width:56px;height:56px;object-fit:cover;">
                                            @else
                                                <span class="main-color-one-bg text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                      style="width:56px;height:56px;">
                                                    <i class="bi bi-person-fill font-24"></i>
                                                </span>
                                            @endif
                                            <div class="d-grid gap-2">
                                                <h6 class="font-14 main-color-one-color mb-0">حساب کاربری من</h6>
                                                <h6 class="font-14 mb-0">{{ $user->fullname ?? $user->mobile }}</h6>
                                            </div>
                                        </div>
                                        <hr class="my-3">
                                        {{-- کیف پول (داینامیک) --}}
                                        <a href="{{ route('front.wallet.index') }}" class="d-flex align-items-center justify-content-between text-decoration-none text-dark py-2">
                                            <span class="font-14"><i class="bi bi-wallet2 me-1 main-color-one-color"></i> کیف پول</span>
                                            <span class="font-14 fw-bold main-color-one-color">{{ number_format($user->getWallet()->balance()) }} <span class="font-12 text-muted">تومان</span></span>
                                        </a>
                                    </div>

                                    {{-- منوی حساب --}}
                                    <div class="profile-box">
                                        <nav class="navbar profile-box-nav p-0" aria-label="منوی حساب کاربری">
                                            <ul class="navbar-nav w-100">
                                                @foreach($account_menu as $item)
                                                    @php
                                                        $is_active = (@$active == $item['active']);
                                                    @endphp
                                                    <li class="nav-item">
                                                        <a href="{{ route($item['route']) }}"
                                                           class="nav-link d-flex align-items-center py-2 px-2 rounded-3 {{ $is_active ? 'main-color-one-bg text-white' : 'text-dark' }}">
                                                            <i class="bi {{ $item['icon'] }} me-2 {{ $is_active ? 'text-white' : 'main-color-one-color' }}"></i>
                                                            <span class="font-14">{{ $item['label'] }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                                <li class="nav-item">
                                                    <form action="{{ route('logout') }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="nav-link d-flex align-items-center py-2 px-2 rounded-3 text-danger w-100 border-0 bg-transparent">
                                                            <i class="bi bi-box-arrow-left me-2"></i>
                                                            <span class="font-14">خروج از حساب</span>
                                                        </button>
                                                    </form>
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
                    @yield('user-content')
                </div>
            </div>
        </div>
    </div>
@endsection

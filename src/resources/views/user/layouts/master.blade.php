@extends('front::layouts.master')

@section('content')

    {{-- پنل کاربر — ریسکین‌شده با کامپوننت‌های قالب سانیار (user-panel.html) --}}
    <div class="content">
        <div class="container-fluid">

            {{-- start side-menu in mobile --}}
            <div class="custom-filter d-lg-none d-block">
                <button class="btn btn-filter-float border-0 main-color-two-bg shadow-box px-4 rounded-3 position-fixed"
                        style="z-index: 999;bottom:80px;" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasUserPanel" aria-controls="offcanvasUserPanel">
                    <i class="bi bi-list font-20 fw-bold text-white"></i>
                    <span class="d-block font-14 text-white">منو</span>
                </button>

                <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasUserPanel"
                     aria-labelledby="offcanvasUserPanelLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="offcanvasUserPanelLabel">منو</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <div class="panel-side">
                            <div class="content-box pb-0">
                                <div class="container-fluid">

                                    <div class="panel-box">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ auth('web')->user()->image_url ?? theme_asset('image/user.png') }}"
                                                 class="img-fluid img-profile-panel rounded-circle me-3 shadow-md" alt="">
                                            <div class="d-grid gap-2">
                                                <h6 class="font-14 main-color-one-color">حساب کاربری من</h6>
                                                <h6 class="font-14">{{ $user->fullname }}</h6>
                                                <h6 class="font-14 text-muted">{{ $user->mobile }}</h6>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="{{ route('front.wallet.index') }}" class="text-decoration-none">
                                        <div class="panel-box mt-3">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-wallet2 main-color-one-color font-20 me-2"></i>
                                                    <h6 class="font-14 mb-0">کیف پول</h6>
                                                </div>
                                                <div class="d-flex align-items-baseline">
                                                    <strong class="font-14 ms-1" title="{{convert_number($user->getWallet()->balance())}}">{{ number_format($user->getWallet()->balance()) }}</strong>
                                                    <span class="font-12 text-muted">تومان</span>
                                                </div>
                                            </div>
                                            <p class="font-12 text-muted mb-0 mt-2">افزایش موجودی <i class="bi bi-chevron-left font-12"></i></p>
                                        </div>
                                    </a>

                                    <div class="profile-box mt-3">
                                        <nav class="navbar profile-box-nav">
                                            <ul class="navbar-nav flex-column">
                                                <li class="nav-item @if(@$active=="profile") active @endif"><a href="{{ route('front.user.profile') }}" class="nav-link">
                                                        <i class="bi bi-house-door"></i>پروفایل</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="orders") active @endif"><a href="{{ route('front.orders.index') }}" class="nav-link">
                                                        <i class="bi bi-cart-check"></i>همه سفارش ها</a>
                                                </li>

                                                @if(class_exists(\App\Services\FrontModuleMenuRegistry::class))
                                                    @foreach(\App\Services\FrontModuleMenuRegistry::all() as $menuView)
                                                        @if(view()->exists($menuView))
                                                            @include($menuView)
                                                        @endif
                                                    @endforeach
                                                @endif

                                                <li class="nav-item @if(@$active=="wallet") active @endif"><a href="{{ route('front.wallet.index') }}" class="nav-link">
                                                        <i class="bi bi-credit-card"></i>کیف پول</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="favorites") active @endif"><a href="{{ route('front.favorites.index') }}" class="nav-link">
                                                        <i class="bi bi-heart"></i>لیست علاقه مندی ها</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="comments") active @endif">
                                                    <a href="{{ route('front.comments.index') }}" class="nav-link">
                                                        <i class="bi bi-chat-dots"></i>نقد و نظرات</a>
                                                </li>
                                                {{-- فاز ۹۲: «کد های تخفیف معرفی» به پکیج باشگاه مشتریان منتقل شد — بخش معرفی اکنون در صفحهٔ باشگاه مشتریان پنل مشتری است. --}}
                                                @if(false)
                                                    <li>
                                                        <a href="{{ route('front.user.referrals.index') }}" class="nav-link">
                                                            <i class="bi bi-gift"></i>کد های تخفیف معرفی
                                                        </a>
                                                    </li>
                                                @endif
                                                <li class="nav-item @if(@$active=="messages") active @endif">
                                                    <a href="{{ route('front.messages.index') }}" class="nav-link">
                                                        <i class="bi bi-bell"></i>پیام ها و اطلاعیه ها</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="addresses") active @endif"><a href="{{ route('front.addresses.index') }}" class="nav-link">
                                                        <i class="bi bi-pin-map"></i>آدرس ها</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="user-history") active @endif"><a href="{{route('front.user.user-history')}}" class="nav-link"><i class="bi bi-clock-history"></i>بازدید های اخیر</a></li>
                                                <li class="nav-item @if(@$active=="tickets") active @endif"><a href="{{route('front.tickets.index')}}" class="nav-link"><i class="bi bi-question-circle"></i>تیکت های شما</a></li>
                                                <li class="nav-item @if(@$active=="profileEdit") active @endif"><a href="{{ route('front.user.profile.edit') }}" class="nav-link">
                                                        <i class="bi bi-person-gear"></i>اطلاعات شخصی</a>
                                                </li>
                                                <li class="nav-item"><a href="{{ route('logout') }}" class="nav-link"><i class="bi bi-arrow-right-square"></i>خروج از حساب کاربری</a></li>
                                            </ul>
                                        </nav>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- end side-menu in mobile --}}

            <div class="dashboard-panel">
                <div class="row gy-3">

                    {{-- start sidebar --}}
                    <div class="col-lg-3 d-lg-block d-none">
                        <div class="panel-side">
                            <div class="content-box pb-0">
                                <div class="container-fluid">

                                    <div class="panel-box">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ auth('web')->user()->image_url ?? theme_asset('image/user.png') }}"
                                                 class="img-fluid img-profile-panel rounded-circle me-3 shadow-md" alt="">
                                            <div class="d-grid gap-2">
                                                <h6 class="font-14 main-color-one-color">حساب کاربری من</h6>
                                                <h6 class="font-14">{{ $user->fullname }}</h6>
                                                <h6 class="font-14 text-muted">{{ $user->mobile }}</h6>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="{{ route('front.wallet.index') }}" class="text-decoration-none">
                                        <div class="panel-box mt-3">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-wallet2 main-color-one-color font-20 me-2"></i>
                                                    <h6 class="font-14 mb-0">کیف پول</h6>
                                                </div>
                                                <div class="d-flex align-items-baseline">
                                                    <strong class="font-14 ms-1" title="{{convert_number($user->getWallet()->balance())}}">{{ number_format($user->getWallet()->balance()) }}</strong>
                                                    <span class="font-12 text-muted">تومان</span>
                                                </div>
                                            </div>
                                            <p class="font-12 text-muted mb-0 mt-2">افزایش موجودی <i class="bi bi-chevron-left font-12"></i></p>
                                        </div>
                                    </a>

                                    <div class="profile-box mt-3">
                                        <nav class="navbar profile-box-nav">
                                            <ul class="navbar-nav flex-column">
                                                <li class="nav-item @if(@$active=="profile") active @endif"><a href="{{ route('front.user.profile') }}" class="nav-link">
                                                        <i class="bi bi-house-door"></i>پروفایل</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="orders") active @endif"><a href="{{ route('front.orders.index') }}" class="nav-link">
                                                        <i class="bi bi-cart-check"></i>همه سفارش ها</a>
                                                </li>

                                                @if(class_exists(\App\Services\FrontModuleMenuRegistry::class))
                                                    @foreach(\App\Services\FrontModuleMenuRegistry::all() as $menuView)
                                                        @if(view()->exists($menuView))
                                                            @include($menuView)
                                                        @endif
                                                    @endforeach
                                                @endif

                                                <li class="nav-item @if(@$active=="wallet") active @endif"><a href="{{ route('front.wallet.index') }}" class="nav-link">
                                                        <i class="bi bi-credit-card"></i>کیف پول</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="favorites") active @endif"><a href="{{ route('front.favorites.index') }}" class="nav-link">
                                                        <i class="bi bi-heart"></i>لیست علاقه مندی ها</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="comments") active @endif">
                                                    <a href="{{ route('front.comments.index') }}" class="nav-link">
                                                        <i class="bi bi-chat-dots"></i>نقد و نظرات</a>
                                                </li>
                                                {{-- فاز ۹۲: «کد های تخفیف معرفی» به پکیج باشگاه مشتریان منتقل شد — بخش معرفی اکنون در صفحهٔ باشگاه مشتریان پنل مشتری است. --}}
                                                @if(false)
                                                    <li>
                                                        <a href="{{ route('front.user.referrals.index') }}" class="nav-link">
                                                            <i class="bi bi-gift"></i>کد های تخفیف معرفی
                                                        </a>
                                                    </li>
                                                @endif
                                                <li class="nav-item @if(@$active=="messages") active @endif">
                                                    <a href="{{ route('front.messages.index') }}" class="nav-link">
                                                        <i class="bi bi-bell"></i>پیام ها و اطلاعیه ها</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="addresses") active @endif"><a href="{{ route('front.addresses.index') }}" class="nav-link">
                                                        <i class="bi bi-pin-map"></i>آدرس ها</a>
                                                </li>
                                                <li class="nav-item @if(@$active=="user-history") active @endif"><a href="{{route('front.user.user-history')}}" class="nav-link"><i class="bi bi-clock-history"></i>بازدید های اخیر</a></li>
                                                <li class="nav-item @if(@$active=="tickets") active @endif"><a href="{{route('front.tickets.index')}}" class="nav-link"><i class="bi bi-question-circle"></i>تیکت های شما</a></li>
                                                <li class="nav-item @if(@$active=="profileEdit") active @endif"><a href="{{ route('front.user.profile.edit') }}" class="nav-link">
                                                        <i class="bi bi-person-gear"></i>اطلاعات شخصی</a>
                                                </li>
                                                <li class="nav-item"><a href="{{ route('logout') }}" class="nav-link"><i class="bi bi-arrow-right-square"></i>خروج از حساب کاربری</a></li>
                                            </ul>
                                        </nav>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- end sidebar --}}

                    {{-- start main content --}}
                    <div class="col-lg-9">
                        @yield('user-content')
                    </div>
                    {{-- end main content --}}

                </div>
            </div>
        </div>
    </div>

    @yield('user-content-bottom')

    @if($random_products->count())
        <div class="content mt-4">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid py-3">
                        <div class="title title-panel d-flex align-items-baseline">
                            <i class="bi bi-bag-heart font-25 main-color-one-color"></i>
                            <h6 class="font-16 ms-2">محصولات پیشنهادی برای شما</h6>
                        </div>
                        <div class="product-carousel owl-carousel owl-theme owl-rtl owl-loaded owl-drag">
                            <div class="owl-stage-outer">
                                <div class="owl-stage"
                                     style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                    @php $i=1; @endphp
                                    @foreach ($random_products as $product)
                                        @include('front::partials.product-block', ['product' => $product])
                                        @php $i++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection

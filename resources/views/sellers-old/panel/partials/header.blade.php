<div class="content-overlay"></div>
{{--<div class="header-navbar-shadow"></div>--}}
<div class="w-100  floating-nav padding-20 position-fixed pb-0 zindex-1 bg-color-f8">
    <nav class="header-navbar navbar-expand-lg navbar navbar-with-menu navbar-light navbar-shadow seller-header-panel">
        <div class="navbar-wrapper">
            <div class="navbar-container ">
                <div class="d-inline-block  w-100" id="navbar-mobile">
                    <div class="mr-auto float-right bookmark-wrapper d-flex align-items-center">
                        <ul class="nav navbar-nav">
                            <li class="nav-item  d-xl-none mr-auto">
                                <a class="nav-link nav-menu-main menu-toggle hidden-xs">
                                    <i class="ficon feather icon-menu"></i>
                                </a>
                            </li>
                        </ul>

                    </div>
                    <div class="d-flex justify-content-sm-between menu-top">
                        <ul class="nav navbar-nav  align-items-center new-navbar-menu">


                            {{-- @include('sellers-old.panel.partials.notifications')--}}
                            <li class="dropdown dropdown-user nav-item">
                                <a class="dropdown-item nav-link" href="{{ route('seller.dashboard') }}">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span class="menu-title">داشبورد</span>
                                </a>
                            </li>
                            <li class="dropdown dropdown-user nav-item">
                                {{--base menu--}}
                                <a class="dropdown-toggle nav-link dropdown-item " data-toggle="dropdown">
                                    <i class="fab fa-product-hunt"></i>
                                    <span class="menu-title">محصولات</span>
                                    <i class="fa-solid fa-chevron-down font-size-10 mr-0_5"></i>
                                </a>

                                {{--child menu--}}
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div class="dropdown-menu-div">
                                        <a class="dropdown-item" href="{{ route('seller.products.find') }}"><span class="menu-item">جستجو یا درج محصول</span></a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="{{ route('seller.products.index') }}"><span class="menu-item"> مدیریت محصولات</span></a>

                                    </div>
                                </div>
                            </li>

                            <li class="dropdown dropdown-user nav-item">
                                {{--base menu--}}
                                <a class="dropdown-toggle nav-link dropdown-user-link " data-toggle="dropdown">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                    <span class="menu-title"> سفارش‌ها</span>
                                    <i class="fa-solid fa-chevron-down font-size-10 mr-0_5"></i>
                                </a>
                                {{--child menu--}}
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div class="dropdown-menu-div">
                                      {{--  <a class="dropdown-item" href="{{ route('seller.orders.index') }}"><span class="menu-item">مدیریت سفارش‌های جاری</span></a>
                                        <div class="dropdown-divider"></div>--}}
                                        <a class="dropdown-item" href="{{ route('seller.orders.index') }}"><span class="menu-item">تاریخچه سفارش‌ها</span></a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="{{ route('seller.orders.notCompleted') }}"><span class="menu-item">محصولات منتظر ارسال</span></a>

                                    </div>
                                </div>
                            </li>

                            <li class="dropdown dropdown-user nav-item">
                                {{--base menu--}}
                                <a class="dropdown-toggle nav-link dropdown-user-link " data-toggle="dropdown">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                    <span class="menu-title"> مالی</span>
                                    <i class="fa-solid fa-chevron-down font-size-10 mr-0_5"></i>
                                </a>
                                {{--child menu--}}
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div class="dropdown-menu-div">
                                        <a class="dropdown-item" href="{{ route('seller.wallet.index') }}"><span class="menu-item">کیف پول، واریزی ها</span></a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="{{route('seller.commission')}}"><span class="menu-item">کمیسیون و هزینه‌ها</span></a>

                                    </div>
                                </div>
                            </li>

                            <li class="dropdown dropdown-user nav-item">
                                <a class="dropdown-item nav-link" href="{{ route('seller.carriers.index') }}">
                                    <i class="fa-solid fa-truck-fast"></i>
                                    <span class="menu-title">حمل و نقل </span>
                                </a>
                            </li>

                            <li class="dropdown dropdown-user nav-item">
                                <a class="dropdown-item nav-link" href="{{ route('seller.tickets.index') }}">
                                    <i class="fas fa-headset"></i>
                                    <span class="menu-title">درخواست پشتیبانی</span>
                                </a>
                            </li>
                        </ul>

                        <ul class="nav navbar-nav align-items-center account-menu-top">


                            {{-- @include('sellers-old.panel.partials.notifications')--}}
                            <li class="dropdown dropdown-user nav-item mr-2">
                                <a class="dropdown-toggle nav-link dropdown-user-link p-0" data-toggle="dropdown">
                                    <div class="user-nav d-sm-flex d-none"><span class="user-name text-bold-600"></span></div>
                                        <span class="menu-title">{{seller_info()->business_name}} خوش آمدید ... </span>
                                    <i class="fa-solid fa-chevron-down font-size-10 mr-0_5"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div class="dropdown-item-account-name d-flex p-0">
                                        @if(seller_info()->logo)
                                            <img class="round" src="{{ seller_info()->logo ? asset(seller_info()->logo) : null  }}" alt="avatar" height="40" width="40">
                                        @else
                                            <span class="c-profile-nav__avatar"><?= mb_substr(seller_info()->business_name,0,1,'UTF-8') ?></span>
                                        @endif
                                        <p> {{ seller_info()->full_name }}</p>

                                    </div>
                                    <div class="dropdown-divider"></div>

                                    <a class="dropdown-item" href="{{ route('seller.profile.index') }}"><i class="fa-solid fa-user"></i>پروفایل شما</a>
                                    <div class="dropdown-divider"></div>

                                    <a class="dropdown-item position-relative" href="{{ route('seller.notifications.index') }}"><i class="fa-regular fa-envelope"></i> پیام ها
                                        @if(count(seller()->notifications()->where('read',0)->get()))
                                            <span class="badge badge badge-danger notifications-count-number notifications-count-number-header">{{count(seller()->notifications()->where('read',0)->get())}}</span>

                                        @endif
                                    </a>
                                    <div class="dropdown-divider"></div>

                                    <a class="dropdown-item" href="{{ route('seller.logout') }}"><i class="fa-solid fa-power-off"></i> خروج</a>
                                </div>
                            </li>

                            <li class="nav-item mr-auto">
                                <a class="navbar-brand" href="/">
                                    <h2 class="brand-text mb-0">
                                        <img src="{{ option('info_logo_panel_seller', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                                    </h2>
                                </a>
                            </li>
                        </ul>

                    </div>

                </div>
            </div>
        </div>
    </nav>
</div>

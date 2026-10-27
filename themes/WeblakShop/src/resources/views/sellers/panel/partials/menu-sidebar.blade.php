<div class="main-menu menu-fixed menu-accordion menu-shadow menu-light expanded d-none mobile-menu top-0" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto">
                <a class="navbar-brand" href="{{ url('/') }}" target="_blank">
                    <h2 class="brand-text mb-0">مرکز فروشندگان</h2>
                </a></li>
        </ul>
    </div>

    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">

            <li class="{{ active_class('seller.dashboard') }} nav-item">
                <a href="{{ route('seller.dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-title">داشبورد</span>
                </a>
            </li>





            <li class="nav-item has-sub"><a><i class="fab fa-product-hunt"></i><span class="menu-title" > محصولات</span></a>
                    <ul class="menu-content">

                            <li class="{{ active_class('seller.products.find') }}">
                                <a href="{{ route('seller.products.find') }}"><i class="feather icon-circle"></i><span class="menu-item">جستجو یا درج محصول</span></a>
                            </li>

                            <li class="{{ active_class('seller.products.index') }}">
                                <a href="{{ route('seller.products.index') }}"><i class="feather icon-circle"></i><span class="menu-item"> مدیریت محصولات</span></a>
                            </li>

                            {{-- فاز ۱۰۶-۳ — سیگنال‌های تقاضا؛ گیت سوییچ seller_demand_signals (قانون طلایی) --}}
                            @if(\App\Support\MultiVendorSettings::demandSignalsEnabled())
                                <li class="{{ active_class('seller.demand.signals') }}">
                                    <a href="{{ route('seller.demand.signals') }}"><i class="feather icon-circle"></i><span class="menu-item">سیگنال‌های تقاضا</span></a>
                                </li>
                            @endif

                    </ul>
                </li>

            <li class="nav-item has-sub"><a><i class="fa-solid fa-cart-shopping"></i><span class="menu-title"> سفارش‌ها</span></a>
                    <ul class="menu-content">
                       {{-- <li class="{{ active_class('seller.orders.index') }}">
                            <a class="{{ active_class('seller.orders.index') }}" href="{{ route('seller.orders.index') }}"><i class="feather icon-circle"></i><span class="menu-item">مدیریت سفارش‌های جاری</span></a>
                        </li>
--}}
                        <li class="{{ active_class('seller.orders.index') }}">
                            <a class="{{ active_class('seller.orders.index') }}" href="{{ route('seller.orders.index') }}"><i class="feather icon-circle"></i><span class="menu-item">تاریخچه سفارش‌ها</span></a>
                        </li>

                        <li class="{{ active_class('seller.orders.notCompleted') }}">
                            <a class="{{ active_class('seller.orders.notCompleted') }}" href="{{ route('seller.orders.notCompleted') }}"><i class="feather icon-circle"></i><span class="menu-item">محصولات منتظر ارسال</span></a>
                        </li>
                    </ul>
                </li>





                <li class="nav-item has-sub"><a><i class="fa-solid fa-hand-holding-dollar"></i><span class="menu-title" > مالی</span></a>
                    <ul class="menu-content">
                            <li class="{{ active_class('seller.wallet.index') }}">
                                <a href="{{ route('seller.wallet.index') }}"><i class="feather icon-circle"></i><span class="menu-item">کیف پول، واریزی ها</span></a>
                            </li>

                            @if(\App\Support\MultiVendorSettings::settlementStatementEnabled())
                                <li class="{{ active_class('seller.wallet.settlements') }}">
                                    <a href="{{ route('seller.wallet.settlements') }}"><i class="feather icon-circle"></i><span class="menu-item">صورت‌حساب تسویه</span></a>
                                </li>
                            @endif

                            <li class="{{ active_class('seller.commission') }}">
                                <a href="{{ route('seller.commission') }}"><i class="feather icon-circle"></i><span class="menu-item">کمیسیون و هزینه‌ها</span></a>
                            </li>
                    </ul>
                </li>

            <li class="{{ active_class('seller.carriers.index') }} nav-item">
                <a href="{{ route('seller.carriers.index') }}">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span class="menu-title">حمل و نقل</span>
                </a>
            </li>

            <li class="{{ active_class('seller.statistics.index') }} nav-item">
                <a href="{{ route('seller.statistics.index') }}">
                    <i class="feather icon-bar-chart-2"></i>
                    <span class="menu-title">آمار و گزارش فروش</span>
                </a>
            </li>

            <li class="{{ active_class('seller.tickets.index') }} nav-item">
                <a href="{{ route('seller.tickets.index') }}">
                    <i class="fas fa-headset"></i>
                    <span class="menu-title">درخواست پشتیبانی</span>
                </a>
            </li>

            <li class="{{ active_class('seller.profile.index') }} nav-item">
                <a href="{{ route('seller.profile.index') }}">
                    <i class="fa-solid fa-user"></i>
                    <span class="menu-title">پروفایل شما</span>
                </a>
            </li>

            <li class="{{ active_class('seller.notifications.index') }} nav-item">
                <a href="{{ route('seller.notifications.index') }}">
                    <i class="fa-regular fa-envelope"></i>
                    <span class="menu-title">پیام ها</span>
                </a>
            </li>

             <li class="{{ active_class('seller.logout') }} nav-item">
                <a href="{{ route('seller.logout') }}">
                    <i class="fa-solid fa-power-off"></i>
                    <span class="menu-title">خروج</span>
                </a>
            </li>



        </ul>
    </div>
</div>

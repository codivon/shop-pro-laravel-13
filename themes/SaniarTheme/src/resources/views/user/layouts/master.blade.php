@extends('front::layouts.master')

@section('content')

    <!--search-category------------------------->
    <div class="col-lg-3 col-md-4 col-xs-12 float-right">
        <div class="sidebar-wrapper">

        {{--    <div class="box-sidebar">
                <div class="profile-box" style="border: none;">
                    <img src="{{theme_asset('images/profile/1.jpg')}}" class="profile-box-img-banner" alt="profile">
                </div>
            </div>

                @if (option('user_referrals_enable', 0) == 1)
                    <div class="box-sidebar">
                        <div class="profile-box">
                            <p>با دعوت از دوستان تان به {{ option('info_site_title') }}
                                <b>{{ option('owner_referrals_amount', 0) }}</b> درصد کد تخفیف بگیرید.</p>
                            <span>کد معرفی شما:</span><strong class="text-info"> {{ $user->referral_code }}</strong>
                        </div>
                    </div>
                @endif--}}
            <div class="box-sidebar">
                <div class="profile-box">
                    <div class="profile-box-avator">
                        <img src="{{theme_asset('images/svg/user-profile.svg')}}" alt="profile">
                    </div>

                    <div class="profile-box-content">
                        <span class="profile-box-nameuser">{{ $user->fullname }}</span>
                        <span class="profile-box-phone">{{ $user->mobile }}</span>
                    </div>

                    <a href="{{ route('front.wallet.index') }}" class="profile-box-row-arrow">
                        <div class="profile-box-title">کیف پول</div>
                        <div class="profile-box-price" title="{{convert_number($user->getWallet()->balance())}}">
                            <div class="wallet-amount">{{ number_format($user->getWallet()->balance()) }}</div>
                            <div class="profile-box-currency">تومان</div>
                            <i class="fa fa-angle-left"></i>
                        </div>
                        <p class="profile-box-wallet-link">افزایش موجودی</p>
                    </a>

                </div>

                <div class="toggle-box profile-menu-items-mobile">
                    <div class="toggle-box-active">
                        <ul>
                            <li class="has-sub">
                                <a>لیست منوها</a>
                                <ul class="profile-menu-items " style="display: none">
                                    <li>
                                        <a href="{{ route('front.user.profile') }}" class="profile-menu-url @if(@$active=="profile")active-profile @endif"><span
                                                class="mdi mdi-account-outline"></span>پروفایل</a></li>
                                    <li>
                                        <a href="{{ route('front.orders.index') }}" class="profile-menu-url @if(@$active=="orders")active-profile @endif"><span
                                                class="mdi mdi-basket"></span>همه سفارش ها</a></li>
                                    <li>
                                        <a href="{{ route('front.wallet.index') }}" class="profile-menu-url @if(@$active=="wallet")active-profile @endif"><span class="mdi mdi-credit-card-outline"></span>کیف پول</a>
                                    </li>

                                    @if(class_exists(\App\Services\FrontModuleMenuRegistry::class))
                                        @foreach(\App\Services\FrontModuleMenuRegistry::all() as $menuView)
                                            @if(view()->exists($menuView))
                                                @include($menuView)
                                            @endif
                                        @endforeach
                                    @endif


                                    <li>
                                        <a href="{{ route('front.favorites.index') }}" class="profile-menu-url @if(@$active=="favorites")active-profile @endif"><span class="mdi mdi-heart-outline"></span>لیست
                                            علاقه مندی ها</a></li>
                                    <li>
                                        <a href="{{ route('front.comments.index') }}" class="profile-menu-url @if(@$active=="comments")active-profile @endif"><span
                                                class="mdi mdi-comment-multiple-outline"></span>نقد و نظرات</a>
                                    </li>
                                    {{-- فاز ۹۲: «کد های تخفیف معرفی» به پکیج باشگاه مشتریان منتقل شد — بخش معرفی اکنون در صفحهٔ باشگاه مشتریان پنل مشتری است. --}}
                                    @if(false)
                                    <li>
                                        <a href="{{ route('front.user.referrals.index') }}" class="profile-menu-url @if(@$active=="referrals")active-profile @endif"><span
                                                class="mdi mdi-comment-multiple-outline"></span>
                                            کد های تخفیف معرفی
                                        </a>
                                    </li>
                                    @endif
                                    <li>
                                        <a href="{{ route('front.messages.index') }}" class="profile-menu-url @if(@$active=="messages")active-profile @endif"><span
                                                class="mdi mdi-comment-multiple-outline"></span>پیام ها و اطلاعیه ها</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('front.addresses.index') }}" class="profile-menu-url @if(@$active=="addresses")active-profile @endif"><span class="mdi mdi-map-marker-outline"></span>آدرس
                                            ها</a>
                                    </li>
                                    <li>
                                        <a href="{{route('front.user.user-history')}}" class="profile-menu-url @if(@$active=="user-history")active-profile @endif"><span class="mdi mdi-history"></span>بازدید های اخیر</a>
                                    </li>
                                    <li>
                                        <a href="{{route('front.tickets.index')}}" class="profile-menu-url @if(@$active=="tickets")active-profile @endif"><span class="mdi mdi-ticket-outline"></span>تیکت های شما</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('front.user.profile.edit') }}" class="profile-menu-url  @if(@$active=="profileEdit")active-profile @endif"><span
                                                class="mdi mdi-account-circle"></span>اطلاعات شخصی</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('logout') }}" class="profile-menu-url"><span
                                                class="mdi mdi-power"></span>خروج</a>
                                    </li>
                                </ul>
                            </li>

                        </ul>
                    </div>
                </div>

                <ul class="profile-menu-items profile-menu-items-desktop">
                    <li><a href="{{ route('front.user.profile') }}" class="profile-menu-url @if(@$active=="profile")active-profile @endif"><span
                                class="mdi mdi-account-outline"></span>پروفایل</a></li>
                    <li><a href="{{ route('front.orders.index') }}" class="profile-menu-url @if(@$active=="orders")active-profile @endif"><span
                                class="mdi mdi-basket"></span>همه سفارش ها</a></li>

                    @if(class_exists(\App\Services\FrontModuleMenuRegistry::class))
                        @foreach(\App\Services\FrontModuleMenuRegistry::all() as $menuView)
                            @if(view()->exists($menuView))
                                @include($menuView)
                            @endif
                        @endforeach
                    @endif

                    <li><a href="{{ route('front.wallet.index') }}" class="profile-menu-url @if(@$active=="wallet")active-profile @endif"><span class="mdi mdi-credit-card-outline"></span>کیف پول</a></li>

                    <li><a href="{{ route('front.favorites.index') }}" class="profile-menu-url @if(@$active=="favorites")active-profile @endif"><span class="mdi mdi-heart-outline"></span>لیست
                            علاقه مندی ها</a></li>
                    <li>
                        <a href="{{ route('front.comments.index') }}" class="profile-menu-url @if(@$active=="comments")active-profile @endif"><span
                                class="mdi mdi-comment-multiple-outline"></span>نقد و نظرات</a>
                    </li>
                    {{-- فاز ۹۲: «کد های تخفیف معرفی» به پکیج باشگاه مشتریان منتقل شد — بخش معرفی اکنون در صفحهٔ باشگاه مشتریان پنل مشتری است. --}}
                    @if(false)
                    <li>
                        <a href="{{ route('front.user.referrals.index') }}" class="profile-menu-url @if(@$active=="referrals")active-profile @endif"><span
                                class="mdi mdi-lan"></span>   کد های تخفیف معرفی</a>
                    </li>
                    @endif
                    <li>
                        <a href="{{ route('front.messages.index') }}" class="profile-menu-url @if(@$active=="messages")active-profile @endif"><span
                                class="mdi mdi-bell"></span>پیام ها و اطلاعیه ها</a>
                    </li>
                    <li><a href="{{ route('front.addresses.index') }}" class="profile-menu-url @if(@$active=="addresses")active-profile @endif"><span class="mdi mdi-map-marker-outline"></span>آدرس
                            ها</a></li>
                    <li><a href="{{route('front.user.user-history')}}" class="profile-menu-url @if(@$active=="user-history")active-profile @endif"><span class="mdi mdi-history"></span>بازدید های اخیر</a></li>
                    <li><a href="{{route('front.tickets.index')}}" class="profile-menu-url @if(@$active=="tickets")active-profile @endif"><span class="mdi mdi-ticket-outline"></span>تیکت های شما</a></li>
                    <li><a href="{{ route('front.user.profile.edit') }}" class="profile-menu-url  @if(@$active=="profileEdit")active-profile @endif"><span
                                class="mdi mdi-account-circle"></span>اطلاعات شخصی</a></li>
                    <li><a href="{{ route('logout') }}" class="profile-menu-url"><span
                                class="mdi mdi-power"></span>خروج</a></li>
                </ul>
            </div>

        </div>
    </div>
    <div class="col-lg-9 col-md-8 col-xs-12 pull-right">
    @yield('user-content')
    </div>
    @yield('user-content-bottom')
    @if($random_products->count())
        <div class="col-lg-12 col-md-12 col-xs-12 pull-right">
            <div class="row">
                <div class="col-12">
                    <div class="widget widget-product card">
                        <header class="card-header">
                            <span class="title-one">محصولات پیشنهادی برای شما</span>
                        </header>
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
    <!--search-category------------------------->
  {{--  <!-- Start main-content -->
    <main class="main-content dt-sl mt-4 mb-3">
        <div class="container main-container">
            <div class="row">

                <!-- Start Sidebar -->
                <div class="col-xl-3 col-lg-4 col-md-4 col-sm-12 sticky-sidebar">
                    <div class="profile-sidebar dt-sl">
                        <div class="dt-sl dt-sn mb-3">
                            <div class="profile-sidebar-header dt-sl">
                                <div class="profile-avatar float-right">
                                    <img data-src="{{ theme_asset('img/theme/avatar.png') }}" alt="">
                                </div>
                                <div class="profile-header-content mr-3 mt-2 float-right">
                                    <span class="d-block profile-username">{{ $user->fullname }}</span>
                                    <span class="d-block profile-phone">{{ $user->username }}</span>
                                </div>
                                <div title="{{ convert_number($user->getWallet()->balance()) . ' تومان' }}" class="profile-point mt-3 mb-2 dt-sl">
                                    <span class="value-profile-point">موجودی کیف پول:</span>
                                    <div class="float-left label-profile-point"><strong class="">{{ number_format($user->getWallet()->balance()) }}</strong> تومان</div>
                                </div>

                                <div class="profile-link mt-2 dt-sl">
                                    <div class="row">
                                        <div class="col-6 text-center">
                                            <a href="{{ route('front.user.password') }}">
                                                <i class="mdi mdi-lock-reset"></i>
                                                <span class="d-block">تغییر رمز</span>
                                            </a>
                                        </div>
                                        <div class="col-6 text-center">
                                            <a href="{{ route('logout') }}">
                                                <i class="mdi mdi-logout-variant"></i>
                                                <span class="d-block">خروج از حساب</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dt-sl dt-sn mb-3">
                            <div class="profile-menu-section dt-sl">
                                <div class="label-profile-menu mt-2 mb-2">
                                    <span>حساب کاربری شما</span>
                                </div>
                                <div class="profile-menu">
                                    <ul>
                                        <li>
                                            <a href="{{ route('front.user.profile') }}" class="{{ active_class('front.user.profile') }}">
                                                <i class="mdi mdi-account-circle-outline"></i>
                                                پروفایل
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('front.wallet.index') }}" class="{{ active_class('front.wallet.index') }}">
                                                <i class="mdi mdi-credit-card-outline"></i>
                                                کیف پول
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('front.orders.index') }}" class="{{ active_class('front.orders.index') }}">
                                                <i class="mdi mdi-basket"></i>
                                                همه سفارش ها
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('front.user.comments') }}" class="{{ active_class('front.user.comments') }}">
                                                <i class="mdi mdi-glasses"></i>
                                                دیدگاه های شما
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('front.tickets.index') }}" class="{{ active_class('front.tickets.index') }}">
                                                <i class="mdi mdi-ticket-outline"></i>
                                                تیکت های شما
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('front.favorites.index') }}" class="{{ active_class('front.favorites.index') }}">
                                                <i class="mdi mdi-heart-outline"></i>
                                                لیست علاقمندی ها
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('front.user.profile.edit') }}" class="{{ active_class('front.user.profile.edit') }}">
                                                <i class="mdi mdi-account-edit-outline"></i>
                                                اطلاعات شخصی
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Sidebar -->

                @yield('user-content')

            </div>

            @if($random_products->count())
                <section class="slider-section dt-sl mt-5 mb-5">
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="section-title text-sm-title title-wide no-after-title-wide">
                                <h2>محصولات پیشنهادی برای شما</h2>
                            </div>
                        </div>

                        <!-- Start Product-Slider -->
                        <div class="col-12 px-res-0">
                            <div class="product-carousel carousel-md owl-carousel owl-theme">
                                @foreach ($random_products as $product)
                                    @include('front::partials.product-block')
                                @endforeach
                            </div>
                        </div>
                        <!-- End Product-Slider -->

                    </div>
                </section>
            @endif

        </div>
    </main>
    <!-- End main-content -->--}}

@endsection

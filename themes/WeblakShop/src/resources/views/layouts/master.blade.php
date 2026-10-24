@php
    $cart =  get_cart();
@endphp
    <!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="16x16" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}"">

    {{-- SEO & Robots --}}
    <meta name="robots" content="index, follow" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="Auth" content="@auth Yes @else No @endauth">

    {{-- Canonical URL (مهم) --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph (پایه - برای صفحه اصلی) --}}
    <meta property="og:title" content="@isset($title){{ $title }} | @endisset{{ option('info_site_title', 'او پی شاپ') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ option('info_site_title', 'او پی شاپ') }}">
    <meta property="og:description" content="{{ option('info_short_description') }}">

    {{-- Default OG Image (فقط در صورتی که صفحه اختصاصی مقدار ندهد) --}}
    @hasSection('og_image')
        @yield('og_image')
    @else
        <meta property="og:image" content="{{ theme_asset('demo/demo-link.png') }}">
    @endif
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@isset($title){{ $title }} | @endisset{{ option('info_site_title', 'او پی شاپ') }}">
    <meta name="twitter:description" content="{{ option('info_short_description') }}">
    <meta name="twitter:image" content="{{ theme_asset('demo/demo-link.png') }}">

    {{-- Dynamic Meta Tags from Pages --}}
    @stack('meta')

    {{-- Title --}}
    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ option('info_site_title', 'او پی شاپ') }}</title>

    {{-- Styles --}}
    @stack('befor-styles')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <link rel="stylesheet" href="{{ theme_asset('css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/materialdesignicons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">

    {!! option('info_header_codes', '') !!}

    @if(auth('adminPanel')->check())
        <link rel="stylesheet" href="{{ theme_asset('css/admin-loggedin-navbar.css') }}">
    @endif

    @stack('styles')

    {{-- GTM & Meta Pixel --}}
    @php
        $gtm_id = option('info_gtm_id', '');
        $meta_pixel_id = option('info_meta_pixel', '');
    @endphp

    @if(!empty($gtm_id))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0], j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm_id }}');</script>
    @endif

    @if(!empty($meta_pixel_id))
        <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init', '{{ $meta_pixel_id }}');fbq('track', 'PageView');</script>
        <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $meta_pixel_id }}&ev=PageView&noscript=1"/></noscript>
    @endif


    @if(function_exists('module_is_active') && module_is_active('SeoAudit'))
        {!! app(\Modules\SeoAudit\Services\BreadcrumbsService::class)->render() !!}
    @endif


</head>

<body>
@if(auth('adminPanel')->check())
    <nav class="navbar navbar-expand-sm bg-dark navbar-dark main-menu dt-sl bg-dark navbar-dark admin-loggedin-navbar">
        <div class="container-fluid">

            <ul class="list float-right hidden-md new-list-menu">

                {{-- منوی محصولات --}}
                @if(auth('adminPanel')->user()->can('products'))
                    <li class="list-item list-item-has-children menu-col-1">
                        <a class="nav-link" >محصولات</a>
                        <ul class="sub-menu nav">
                            @if(auth('adminPanel')->user()->can('products.index'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.products.index') }}"><i
                                            class="fa-solid fa-list"></i> لیست محصولات</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('products.create'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.products.create') }}"><i
                                            class="fa-solid fa-plus"></i> ایجاد محصول جدید</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('products.category'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.categories.product') }}"><i
                                            class="fa-solid fa-tags"></i> دسته بندی ها</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('products.brands'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.brands.index') }}"><i
                                            class="fa-solid fa-trademark"></i> برندها</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('products.comments'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.comments.products') }}"><i
                                            class="fa-solid fa-comment"></i> دیدگاه ها</a>
                                </li>
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.reviews.index') }}"><i
                                            class="fa-solid fa-question-circle"></i> پرسش و پاسخ ها</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- منوی مقالات --}}
                @if(auth('adminPanel')->user()->can('posts'))
                    <li class="list-item list-item-has-children menu-col-1">
                        <a class="nav-link" >مقالات</a>
                        <ul class="sub-menu nav">
                            @if(auth('adminPanel')->user()->can('posts.index'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.posts.index') }}"><i
                                            class="fa-solid fa-newspaper"></i> لیست مقالات</a>
                                </li>
                            @endif

                            @if(auth('adminPanel')->user()->can('posts.create'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.posts.create') }}"><i
                                            class="fa-solid fa-plus"></i> ایجاد مقاله جدید</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('posts.category'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.categories.post') }}"><i
                                            class="fa-solid fa-tags"></i> دسته بندی ها</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('posts.comments'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.comments.posts') }}"><i
                                            class="fa-solid fa-comment"></i> دیدگاه ها</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if(auth('adminPanel')->user()->can('settings.information'))
                    <li class="list-item">
                        <a class="nav-link" href="{{ route('np.settings.information') }}"> تنظیمات</a>
                    </li>
                @endif
                @if(auth('adminPanel')->user()->can('themes.settings'))
                    <li class="list-item">
                        <a class="nav-link" href="{{ route('np.widgets.index') }}"> ویرایش صفحه</a>
                    </li>
                @endif

            </ul>

            {{-- منوی سمت چپ (آواتار و منوی کشویی) --}}

            <ul class="dropdown-user-menu list float-right hidden-md new-list-menu">
                <li class="list-item list-item-has-children menu-col-1">
                    <a class="navbar-brand "  role="button" id="adminDropdown" data-bs-toggle="dropdown"
                       aria-expanded="false">
                        <img src="{{ auth('adminPanel')->user()->image_url }}" alt="Avatar Logo" class="rounded-pill">
                        <span class="dropdown-username">{{auth('adminPanel')->user()->full_name}}</span>
                    </a>
                    <ul class="sub-menu nav">
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.dashboard') }}"><i
                                    class="fa-solid fa-chart-line"></i> پیشخوان</a>
                        </li>
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.profile') }}"><i
                                    class="fa-solid fa-user-edit"></i>ویرایش پروفایل</a>
                        </li>
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.sessions.index') }}"><i
                                    class="fa-solid fa-desktop"></i> نشست های فعال</a>
                        </li>
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.notifications.index') }}"><i
                                    class="fa-solid fa-bell"></i> اعلان ها</a>
                        </li>
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.logout') }}"><i
                                    class="fa-solid fa-sign-out-alt"></i> خروج از حساب کاربری</a>
                        </li>

                    </ul>
                </li>
            </ul>

        </div>
    </nav>
@endif
@if(count(highest_banner()))
    @foreach(highest_banner() as $banner)
        <div class='highest-banner' style=" height: auto; ">
            <a href="" class="image-data-src"><img
                    data-src="{{ $banner->image ? asset($banner->image) : asset('/no-image-product.svg') }}"
                    src="{{ theme_asset('images/600-600.png') }}" alt="{{ $banner->title }}"></a>
        </div>
    @endforeach

@endif

<header class="header-main-page">
    <div class="d-block">
        <div>
            <div class="col-lg-8 col-md-8 col-xs-12 pull-right">
                <div class="header-right">
                    <div class="logo">
                        <a href="{{ route('front.index') }}">
                            <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}"
                                 alt="{{ option('info_site_title', 'او پی شاپ') }}">
                        </a>
                    </div>

                    <div class="col-lg-8 col-md-12 col-xs-12 pull-right">
                        <div class="search-header search-box search-area">
                            <form action="{{ route('front.products.index') }}" class="search" id="search-form">
                                <input type="text" name="q" id="search-input" class="header-search-input"
                                       autocomplete="off" placeholder="نام کالای مورد نظر خود را جستجو کنید…">
                                <button class="btn-search" id="search-header-btn-search" type="submit"><img
                                        src="{{theme_asset('images/search.png')}}"
                                        alt="search"></button>
                            </form>


                            <div class="search-result" id="search-result">

                            </div>
                            <div class="search-result search-result-fixed">
                                <hr class="first-hr">
                                <div class="search-results-list js-search-ad-banner">
                                    <div class="main-slider d-contents">
                                        <div class="main-slider-container">
                                            <div id="carouselExampleIndicators2" class="carousel slide"
                                                 data-ride="carousel2" style="max-height: 189px;">
                                                @if(count(get_slider_search())>1)
                                                    <ol class="carousel-indicators">
                                                        @for($i=1;$i<= count(get_slider_search());$i++)
                                                            <li data-target="#carouselExampleIndicators2"
                                                                data-slide-to="{{$i}}"
                                                                class="@if($i==0)active @endif"></li>
                                                        @endfor
                                                    </ol>
                                                @endif
                                                <div class="carousel-inner">
                                                    @foreach(get_slider_search() as $slider_search)
                                                        <div class="carousel-item @if ($loop->first)active @endif">
                                                            <img class="d-block w-100"
                                                                 src="{{asset($slider_search->image)}}"
                                                                 alt="{{$slider_search->title ?: $slider_search->image}}">
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if(count(get_slider_search())>1)
                                                    <a class="carousel-control-prev" href="#carouselExampleIndicators2"
                                                       role="button"
                                                       data-slide="prev">
                                                        <span class="fa fa-angle-left" aria-hidden="true"></span>
                                                        <span class="sr-only">Previous</span>
                                                    </a>
                                                    <a class="carousel-control-next" href="#carouselExampleIndicators2"
                                                       role="button"
                                                       data-slide="next">
                                                        <span class="fa fa-angle-right" aria-hidden="true"></span>
                                                        <span class="sr-only">Next</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-3 mb-2">
                                    نام کالای مورد نظر خود را جستجو کنید…
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-lg-4 col-md-4 col-xs-12 pull-left">
                <div class="header-left d-flex flex-row-reverse">

                    @include('front::partials.cart')

                    @if(auth('web')->check())

                        <a class="login-link">
                            <div class="btn-login">
                                <span class="mdi mdi-account"></span>
                                پروفایل من
                                <div class="dropdown-menu-login">
                                    <a href="{{ route('front.user.profile') }}" class="header-profile-dropdown-account">
                                        <div class="header-profile-dropdown-user">
                                            <div class="header-profile-dropdown-user-img">
                                                <img src="{{theme_asset('images/svg/user-profile.svg')}}" alt="profile">
                                            </div>
                                            <div class="header-profile-dropdown-user-info">
                                                <p class="header-profile-dropdown-user-name">@if(auth('web')->user()->fullname!== null)
                                                        {{auth('web')->user()->fullname}}
                                                    @else
                                                        {{auth('web')->user()->mobile}}
                                                    @endif
                                                    <span class="header-profile-dropdown-user-profile-link">مشاهده حساب
                                                        کاربری</span>
                                                </p>
                                            </div>

                                            <div class="header-profile-dropdown-account">
                                                <div class="header-profile-dropdown-account-item">
                                                    <span class="header-profile-dropdown-account-item-title">کیف
                                                        پول</span>
                                                    <div class="header-profile-dropdown-account-item-amount">
                                                        <span
                                                            class="header-profile-dropdown-account-item-amount-number">{{ !auth('web')->user()->isAdmin() ? number_format(auth('web')->user()->getWallet()->balance()) : '0' }}
                                                        </span>
                                                        تومان
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </a>
                                    <div class="header-profile-dropdown-actions">
                                        <div class="header-profile-dropdown-action-container">
                                            <a href="{{ route('front.orders.index') }}"
                                               class="header-profile-dropdown-action-link">سفارش‌های من</a>
                                        </div>
                                        <div class="header-profile-dropdown-action-container">
                                            <a href="{{ route('front.favorites.index') }}"
                                               class="header-profile-dropdown-action-link">لیست علاقه مندی</a>
                                        </div>
                                        <div class="header-profile-dropdown-action-container">
                                            <a href="{{ route('logout') }}" class="header-profile-dropdown-action-link">خروج
                                                از حساب
                                                کاربری</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                        @include('front::partials.notifications')
                    @else
                        <a href="{{ route('login') }}" class="login-link">
                            <div class="btn-login">
                                <span class="mdi mdi-login"></span>
                                ورود | ثبت نام
                            </div>
                        </a>
                    @endif
                    <!-- Example single danger button -->
                    <!-- responsive header-->
                    <div class="responsive-header-left">
                        <div class="btn-login-responsive">
                            @if($cart && $cart->products()->count())
                                <span class="cart-count">{{@$cart->products()->count()}}</span>
                            @else
                                <span class="cart-count">0</span>
                            @endif
                        </div>

                        @if(auth('web')->check())
                        <div class="btn-login-responsive">

                            @include('front::partials.notifications')

                        </div>
                        @endif

                        <a href='{{route('front.cart')}}' class="mini-cart-header-responsive">
                            <span class="mdi mdi-basket"></span>
                            @include('front::partials.cart')
                        </a>

                        <div class="question-faq btn-login-responsive">

                            @if(auth('web')->check())
                                <span class="mdi mdi-account"></span>
                                <div class="dropdown-menu-login">
                                    <div class="header-profile-dropdown-account">
                                        <a href="{{ route('front.user.profile') }}">
                                            <div class="header-profile-dropdown-user">
                                                <div class="header-profile-dropdown-user-img">
                                                    <img src="{{theme_asset('images/svg/user-profile.svg')}}"
                                                         alt="profile">
                                                </div>
                                                <div class="header-profile-dropdown-user-info">
                                                    <p class="header-profile-dropdown-user-name">@if(auth('web')->user()->fullname!=" ")
                                                            {{auth('web')->user()->fullname}}
                                                        @else
                                                            {{auth('web')->user()->mobile}}
                                                        @endif
                                                        <a href="{{ route('front.user.profile') }}"
                                                           class="header-profile-dropdown-user-profile-link">مشاهده
                                                            حساب کاربری</a>
                                                    </p>
                                                </div>
                                                <div class="header-profile-dropdown-account">
                                                    <div class="header-profile-dropdown-account-item">
                                                    <span class="header-profile-dropdown-account-item-title">کیف
                                                        پول</span>
                                                        <div class="header-profile-dropdown-account-item-amount">
                                                        <span
                                                            class="header-profile-dropdown-account-item-amount-number">۰
                                                        </span>
                                                            تومان
                                                        </div>
                                                    </div>
                                                    <div class="header-profile-dropdown-account-item">
                                                    <span
                                                        class="header-profile-dropdown-account-item-title">دیجی‌کلاب</span>
                                                        <div class="header-profile-dropdown-account-item-amount">
                                                        <span
                                                            class="header-profile-dropdown-account-item-amount-number">۰
                                                        </span>
                                                            تومان
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>

                                    </div>
                                    <div class="header-profile-dropdown-actions">
                                        <div class="header-profile-dropdown-action-container">
                                            <a href="{{ route('front.orders.index') }}"
                                               class="header-profile-dropdown-action-link">سفارش‌های من</a>
                                        </div>
                                        <div class="header-profile-dropdown-action-container">
                                            <a href="{{ route('front.favorites.index') }}"
                                               class="header-profile-dropdown-action-link">لیست علاقه مندی</a>
                                        </div>
                                        <div class="header-profile-dropdown-action-container">
                                            <a href="{{ route('logout') }}" class="header-profile-dropdown-action-link">خروج
                                                از حساب
                                                کاربری</a>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('login') }}" style="color: #424750;">
                                    <span class="mdi mdi-account"></span>
                                </a>

                            @endif
                        </div>


                    </div>
                    <!-- responsive header-->
                </div>
            </div>
        </div>
        <!-- Start megamenu-->


        @include('front::partials.menu.menu')

    </div>
    <!--        End megamenu------------------->


    <!--    responsive-megamenu-mobile----------------->
    <nav class="sidebar">
        <div class="nav-header">
            <div class="header-cover"></div>
            <div class="logo-wrap">
                <a href="{{ route('front.index') }}">
                    <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}"
                         alt="{{ option('info_site_title', 'او پی شاپ') }}">
                </a>
            </div>
        </div>
        @include('front::partials.mobile-menu.menu')

    </nav>
    <div class="nav-btn">
        <span class="linee1"></span>
        <span class="linee2"></span>
        <span class="linee3"></span>
    </div>
    <div class="overlay"></div>
    <!--    responsive-megamenu-mobile----------------->
</header>
<div class="nav-categories-overlay"></div>
<div class="overlay-search-box"></div>

<!--    Start Main Slider -------------------->
@yield('content')

@include('front::partials.floating-widget')
<!--   Footer---------------------------->
@include('front::partials.footer')
<!--   Footer---------------------------->
<script>
    var BASE_URL = "{{ route('front.index') }}";
    var IS_RTL = {{ $current_local['direction'] == 'rtl' ? 1 : 0 }};
    var site_title = "{{ option('info_site_title', 'او پی شاپ') }}";
</script>

<script src="{{theme_asset('js/jquery-3.2.1.min.js')}}"></script>
@stack('scripts-top-js')
<script src="{{theme_asset('js/owl.carousel.min.js')}}"></script>
<script src="{{theme_asset('js/jquery.countdown.min.js')}}"></script>
<script src="{{theme_asset('js/popper.min.js')}}"></script>
<script src="{{ theme_asset("js/plugins/sweetalert2.all.min.js") }}"></script>
<script src="{{ theme_asset("js/plugins/jquery.blockUI.js") }}"></script>
<script src="{{ theme_asset("js/plugins/toastr/toastr.min.js") }}"></script>
<script src="{{ theme_asset('js/vendor/jquery.fancybox.min.js') }}"></script>
<script src="{{ theme_asset('js/vendor/jquery.lazyloadxt.min.js') }}"></script>
<script src="{{theme_asset('js/bootstrap.js')}}"></script>
@stack('scripts')
<script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
<script src="{{ theme_asset("js/scripts.js") }}?v=11"></script>
<script src="{{theme_asset('js/main.js')}}"></script>


{!! option('info_scripts') !!}
<script>
    var imgWalt = jQuery('.image-data-src img').get().map(function(el) {
        el.src = el.dataset.src;
    });


</script>
@if(session('toast-success'))
    <script>
        showCustomToast('{{session('toast-success')}}','success');
    </script>
@endif
@if(session('toast-error'))
    <script>
        showCustomToast('{{session('toast-error')}}','error');
    </script>
@endif
@if(session('toast-warning'))
    <script>
        showCustomToast('{{session('toast-warning')}}','warning');
    </script>
@endif

@include('front::partials.popup-message')
</body>

</html>

@php
    session()->forget('showOrderResultInfo');
    /*session()->forget('show-seller-business-details');
    session()->forget('show-seller-verification-form');
    session()->forget('show-seller-documents');
    session()->forget('show-seller-checkout');*/
@endphp
@php session()->forget('toast-success'); @endphp
@php session()->forget('toast-error'); @endphp
@php session()->forget('toast-warning'); @endphp

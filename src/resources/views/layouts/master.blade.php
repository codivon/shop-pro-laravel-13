@php
    $cart = get_cart();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- فاز ۱۵۶ — حالت روز/شب: مقداردهی قبل از رندر برای جلوگیری از فلش --}}
    <script src="{{ theme_asset('js/theme-init.js') }}"></script>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">

    {{-- SEO & Robots --}}
    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="Auth" content="@auth('web') Yes @else No @endauth">
    <meta name="description" content="{{ $description ?? option('info_short_description') }}">

    {{-- Canonical URL (مهم) --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph (پایه - برای صفحه اصلی) --}}
    <meta property="og:title" content="@isset($title){{ $title }} | @endisset{{ option('info_site_title', 'او پی شاپ') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ option('info_site_title', 'او پی شاپ') }}">
    <meta property="og:description" content="{{ $description ?? option('info_short_description') }}">
    @php $ogImage = $og_image ?? option('info_logo', theme_asset('image/logo.png')); @endphp
    <meta property="og:image" content="{{ $ogImage }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@isset($title){{ $title }} | @endisset{{ option('info_site_title', 'او پی شاپ') }}">
    <meta name="twitter:description" content="{{ $description ?? option('info_short_description') }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    {{-- Dynamic Meta Tags from Pages --}}
    @stack('meta')

    {{-- Title --}}
    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ option('info_site_title', 'او پی شاپ') }}</title>

    {{-- اندپوینت جستجوی زنده برای JS هدر --}}
    <meta name="saniar-ajax-search" content="{{ route('front.products.index') }}">

    {{-- Styles --}}
    @stack('befor-styles')

    {{-- فاز ۱۳۹ — خودمیزبانی FA: cdnjs روی IPهای ایران کند است و رندر را بلاک می‌کرد --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    {{-- سانیار — CSS تمپلیت (ترتیب دقیق مطابق index.html) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('font/bootstrap-icon/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/timer/timer.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/responsive.css') }}">

    {{-- فاز ۱۵۷ — اسلایدر owl در ویجت‌های پنل کاربری استفاده می‌شود؛
         بدون این CSS آفست افقی تمام‌صفحه ایجاد می‌شد --}}
    <link rel="stylesheet" href="{{ theme_asset('css/owl.carousel.min.css') }}">

    {{-- فاز ۱۵۵ — اصلاحات ظاهری ریز (هدر/محصول/صفحات) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/theme-header.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/theme-product.css') }}?v=17">
    <link rel="stylesheet" href="{{ theme_asset('css/theme-pages.css') }}">

    {{-- فاز ۱۵۶ — حالت شب (دارک مود) — بعد از همهٔ استایل‌ها تا بتواند بازنویسی کند --}}
    <link rel="stylesheet" href="{{ theme_asset('css/theme-dark.css') }}?v=18">

    {{-- CSS عملکردی مشترک (توست‌ها + آیکون‌های منوهای داینامیک) --}}
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/rasta-contact/style.css') }}">

    {!! option('info_header_codes', '') !!}

    @if(auth('adminPanel')->check())
        <link rel="stylesheet" href="{{ theme_asset('css/admin-loggedin-navbar.css') }}">
    @endif

    {{-- آیکون‌های اعلان‌ها (mdi) — فقط برای کاربر وارد شده تا رندر هدر کند نشود --}}
    @if(auth('web')->check())
        <link rel="stylesheet" href="{{ theme_asset('css/materialdesignicons.css') }}">
    @endif

    @stack('styles')

    {{-- GTM & Meta Pixel --}}
    @php
        $gtm_id = option('info_gtm_id', '');
        $meta_pixel_id = option('info_meta_pixel', '');
    @endphp

    @if(!empty($gtm_id))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm_id }}');</script>
    @endif

    @if(!empty($meta_pixel_id))
        <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $meta_pixel_id }}');fbq('track','PageView');</script>
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
                    <a class="navbar-brand" role="button" aria-haspopup="true" aria-expanded="false">
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

<header>
    <div class="container-fluid">
        <div class="row align-items-center">

            {{-- لوگو + منوی موبایل --}}
            <div class="col-lg-2 col-6 order-lg-1 order-1">
                <div class="d-flex align-items-center">
                    <div class="responsive-menu d-lg-none d-block">
                        <button class="btn border-0 p-0 btn-responsive-menu" type="button"
                                data-bs-toggle="offcanvas" data-bs-target="#responsiveMenu"
                                aria-controls="responsive menu">
                            <i class="bi bi-list font-30"></i>
                        </button>
                        <div class="offcanvas offcanvas-start" tabindex="-1" id="responsiveMenu"
                             aria-labelledby="responsive menu">
                            <div class="offcanvas-header">
                                <h5 class="offcanvas-title" id="offcanvasRightLabel">{{ option('info_site_title', 'فروشگاه سانیار') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                            </div>
                            <div class="offcanvas-body">
                                <a href="{{ route('front.index') }}" class="text-center d-block mb-3">
                                    <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}" class="img-fluid" width="200">
                                </a>
                                <div class="header-bottom-form mb-4 w-100">
                                    <div class="search-form">
                                        <form action="{{ route('front.products.index') }}" id="search-form-mobile">
                                            <div class="search-filed">
                                                <input type="text" name="q" placeholder="جستجوی محصولات ..."
                                                       class="form-control search-input">
                                                <button type="submit"
                                                        class="btn search-btn main-color-one-bg rounded-pill"><i
                                                        class="bi bi-search"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @include('front::partials.mobile-menu.menu')
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('front.index') }}">
                        <div class="logo">
                            <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}"
                                 alt="{{ option('info_site_title', 'او پی شاپ') }}" class="img-fluid">
                        </div>
                    </a>
                </div>
            </div>
            {{-- پایان لوگو --}}

            {{-- اکشن موبایل (سبد + حساب) --}}
            <div class="d-lg-none d-block col-6 order-lg-5 order-2">
                <div class="d-flex align-items-center justify-content-end">
                    {{-- فاز ۱۵۶ — کلید روز/شب (موبایل) --}}
                    <button type="button" class="theme-toggle-btn me-2 shadow-sm"
                            aria-pressed="false" aria-label="تغییر حالت روز و شب"
                            title="تغییر حالت روز و شب">
                        <i class="bi bi-sun icon-sun"></i>
                        <i class="bi bi-moon-stars icon-moon"></i>
                    </button>
                    <a class="btn btn-light no-highlight btn-lg btn-action-cart shadow-sm"
                       data-bs-toggle="offcanvas" href="#offcanvasCart" role="button"
                       aria-controls="offcanvasCart">
                        <i class="bi bi-basket font-30"></i>
                        <span class="header-cart-counter main-color-one-bg d-inline-block rounded-pill">
                            {{ $cart ? $cart->products()->count() : 0 }}
                        </span>
                    </a>
                    <div class="dropdown ms-3">
                        @if(auth('web')->check())
                            <a href="#" data-bs-toggle="dropdown" aria-expanded="false" role="button"
                               class="btn btn-white header-register border-0 rounded-pill">
                                <figure class="avatar">
                                    <img src="{{ auth('web')->user()->image_url ?? theme_asset('image/user.png') }}" alt="user">
                                </figure>
                            </a>
                            @include('front::partials.user-menu')
                        @else
                            <a href="{{ route('login') }}" role="button"
                               class="btn btn-white header-register border-0 rounded-pill">
                                <i class="bi bi-person font-20"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            {{-- پایان اکشن موبایل --}}

            {{-- جستجو --}}
            <div class="col-lg-6 order-lg-2 d-lg-block d-none">
                <div class="search-form search-header">
                    <form action="{{ route('front.products.index') }}" id="search-form">
                        <div class="search-filed">
                            <input type="text" name="q" id="search-input" class="form-control search-input"
                                   autocomplete="off" placeholder="جستجوی محصولات ...">
                            <button type="submit" id="search-header-btn-search"
                                    class="btn search-btn main-color-one-bg rounded-pill"><i class="bi bi-search"></i></button>
                        </div>
                    </form>

                    {{-- نتایج جستجوی زنده — فقط با محتوا نمایش داده می‌شود --}}
                    <div class="search-result" id="search-result"></div>
                </div>
            </div>
            {{-- پایان جستجو --}}

            {{-- ورود / حساب کاربری + علاقه مندی + سبد --}}
            <div class="col-lg-4 order-lg-3 d-lg-block d-none">
                <div class="d-flex align-items-center justify-content-end">
                    @if(auth('web')->check())
                        <div class="btn-group header-btn-group shadow-sm rounded-pill">
                            <div class="dropdown">
                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false" role="button"
                                   class="btn btn-white header-register border-0 rounded-pill">
                                    <i class="bi bi-person-circle font-20 text-muted me-1"></i>
                                    <span>@if(auth('web')->user()->fullname !== null && auth('web')->user()->fullname != ''){{ auth('web')->user()->fullname }}@else{{ auth('web')->user()->mobile }}@endif</span>
                                </a>
                                @include('front::partials.user-menu')
                            </div>
                        </div>

                        @include('front::partials.notifications')
                    @else
                        <div class="btn-group header-btn-group shadow-sm rounded-pill">
                            <div class="dropdown">
                                <a href="{{ route('register') }}" class="btn btn-white header-register border-0 rounded-pill">
                                    <i class="bi bi-person-circle font-20 text-muted me-1"></i>
                                    <span>ثبت نام</span>
                                </a>
                            </div>
                            <a href="{{ route('login') }}" class="btn btn-white header-login border-0 rounded-pill">ورود</a>
                        </div>
                    @endif

                    {{-- فاز ۱۵۶ — کلید روز/شب: جایگزین آیکون علاقه‌مندی هدر --}}
                    <button type="button" class="theme-toggle-btn shadow-sm"
                            aria-pressed="false" aria-label="تغییر حالت روز و شب"
                            title="تغییر حالت روز و شب">
                        <i class="bi bi-sun icon-sun"></i>
                        <i class="bi bi-moon-stars icon-moon"></i>
                    </button>

                    <div class="header-cart ms-1">
                        <a class="btn header-cart-toggle rounded-pill btn-white border-0 shadow-sm"
                           data-bs-toggle="offcanvas" href="#offcanvasCart" role="button"
                           aria-controls="offcanvasCart">
                            <i class="bi bi-shop font-20 text-muted me-1"></i>
                            <span class="header-cart-counter main-color-one-bg d-inline-block rounded-pill">
                                {{ $cart ? $cart->products()->count() : 0 }}
                            </span>
                        </a>
                    </div>
                </div>
            </div>
            {{-- پایان ورود / حساب --}}

        </div>
    </div>
</header>

{{-- شروع مگا منو --}}
@include('front::partials.menu.menu')
{{-- پایان مگا منو --}}

@yield('content')

{{-- ویجت شناور (تماس / ابزارک‌های ادمین) --}}
@include('front::partials.floating-widget')

{{-- فوتر --}}
@include('front::partials.footer')

{{-- منوی پایین موبایل --}}
<div class="mobile-footer d-lg-none d-flex">
    <div class="parent">
        <div class="item" onclick="topFunction()">
            <i class="bi bi-chevron-up font-20"></i>
        </div>
        <div class="item">
            <a href="{{ route('front.index') }}">
                <i class="bi bi-house font-20"></i>
            </a>
        </div>
        <div class="item item-float">
            <a data-bs-toggle="offcanvas" href="#offcanvasCart" role="button"
               aria-controls="offcanvasCart">
                <i class="bi bi-bag font-20"></i>
            </a>
        </div>
        <div class="item">
            <a href="{{ route('front.products.index') }}">
                <i class="bi bi-archive"></i>
            </a>
        </div>
        <div class="item">
            <a href="{{ auth('web')->check() ? route('front.user.profile') : route('login') }}">
                <i class="bi bi-person"></i>
            </a>
        </div>
    </div>
</div>

{{-- سبد خرید کشویی (offcanvas) --}}
@include('front::partials.cart')

{{-- فاز ۱۵۶ — دکمهٔ «برگشت به بالا» — مستطیلی، لبهٔ چپ، وسط ارتفاع صفحه --}}
<button type="button" id="back-to-top" aria-label="برگشت به بالا">
    <i class="bi bi-arrow-up-short"></i>
    <span>برگشت به بالا</span>
</button>

<script>
    var BASE_URL = "{{ route('front.index') }}";
    var IS_RTL = {{ $current_local['direction'] == 'rtl' ? 1 : 0 }};
    var site_title = "{{ option('info_site_title', 'او پی شاپ') }}";
</script>

{{-- سانیار — اسکریپت‌های تمپلیت (ترتیب دقیق مطابق index.html) --}}
<script src="{{theme_asset('js/jquery.min.js')}}"></script>
@stack('scripts-top-js')
<script src="{{theme_asset('js/bootstrap.bundle.min.js')}}"></script>
<script src="{{theme_asset('plugin/waves/waves.min.js')}}"></script>
<script src="{{theme_asset('plugin/swiper/swiper-bundle.min.js')}}"></script>
<script src="{{theme_asset('plugin/timer/timer.js')}}"></script>
<script src="{{theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js')}}"></script>
<script src="{{theme_asset('js/app.js')}}"></script>
<script src="{{theme_asset('js/jquery-app.js')}}"></script>

{{-- سانیار — پلاگین‌های جانبی تمپلیت --}}
{{-- فاز ۱۵۶ — پلاگین go-to-top حذف شد؛ دکمهٔ مستطیلی «برگشت به بالا» در theme-header.js --}}
<script src="{{theme_asset('plugin/rasta-contact/script.js')}}"></script>
<script src="{{theme_asset('js/saniar-init.js')}}"></script>
<script src="{{theme_asset('js/theme-header.js')}}?v=18"></script>

{{-- اسکریپت‌های عملکردی فروشگاه --}}
<script src="{{ theme_asset("js/plugins/sweetalert2.all.min.js") }}"></script>
<script src="{{ theme_asset("js/plugins/jquery.blockUI.js") }}"></script>
<script src="{{ theme_asset("js/plugins/toastr/toastr.min.js") }}"></script>
<script src="{{ theme_asset('js/vendor/jquery.fancybox.min.js') }}"></script>
<script src="{{ theme_asset('js/vendor/jquery.lazyloadxt.min.js') }}"></script>
<script src="{{theme_asset('js/jquery.countdown.min.js')}}"></script>
<script src="{{theme_asset('js/owl.carousel.min.js')}}"></script>
<script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
<script src="{{ theme_asset("js/scripts.js") }}?v=11"></script>
<script src="{{theme_asset('js/main.js')}}?v=17"></script>
@stack('scripts')

{!! option('info_scripts') !!}

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
@endphp
@php session()->forget('toast-success'); @endphp
@php session()->forget('toast-error'); @endphp
@php session()->forget('toast-warning'); @endphp

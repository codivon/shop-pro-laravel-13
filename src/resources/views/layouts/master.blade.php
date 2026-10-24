@php
    $cart = get_cart();
    $cart_count = $cart ? $cart->products()->count() : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">

    {{-- SEO & Robots --}}
    <meta name="robots" content="index, follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="@isset($title){{ $title }} | @endisset{{ option('info_site_title', config('front.demo.name')) }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ option('info_site_title', config('front.demo.name')) }}">
    <meta property="og:description" content="{{ option('info_short_description') }}">
    @hasSection('og_image')
        @yield('og_image')
    @else
        <meta property="og:image" content="{{ option('info_logo', theme_asset('image/logo.png')) }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@isset($title){{ $title }} | @endisset{{ option('info_site_title', config('front.demo.name')) }}">
    <meta name="twitter:description" content="{{ option('info_short_description') }}">
    <meta name="twitter:image" content="{{ option('info_logo', theme_asset('image/logo.png')) }}">

    {{-- Dynamic Meta Tags from Pages --}}
    @stack('meta')

    {{-- سانیار فاز C: اندپوینت جستجوی زنده برای JS هدر --}}
    <meta name="saniar-ajax-search" content="{{ route('front.products.ajax_search') }}">

    {{-- Title --}}
    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ option('info_site_title', config('front.demo.name')) }}</title>

    {{-- Styles --}}
    @stack('befor-styles')

    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('font/bootstrap-icon/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/timer/timer.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/responsive.css') }}">
    {{-- سانیار — فاز B: استایل‌های ویجت‌های صفحه اصلی (تمام‌صفحه/رنگی/شگفت‌انگیز) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/widgets.css') }}">
    {{-- سانیار فاز C: استایل‌های جستجو (دراپ‌داون زنده + صفحه نتایج) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/search.css') }}">
    {{-- سانیار فاز D: کامپوننت‌های مشترک (toast، حالت لودینگ) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/go-to-top/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/rasta-contact/style.css') }}">

    {!! option('info_header_codes', '') !!}

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

    {{-- نوار دسترسی سریع ادمین (فقط وقتی ادمین لاگین است) --}}
    @auth('adminPanel')
        <nav class="navbar navbar-expand-sm bg-dark navbar-dark py-1">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    @can('products')
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button">محصولات</a>
                            <ul class="dropdown-menu">
                                @can('products.index')
                                    <li><a class="dropdown-item" href="{{ route('np.products.index') }}"><i class="bi bi-list-ul me-1"></i> لیست محصولات</a></li>
                                @endcan
                                @can('products.create')
                                    <li><a class="dropdown-item" href="{{ route('np.products.create') }}"><i class="bi bi-plus-lg me-1"></i> ایجاد محصول جدید</a></li>
                                @endcan
                                @can('products.category')
                                    <li><a class="dropdown-item" href="{{ route('np.categories.product') }}"><i class="bi bi-tags me-1"></i> دسته بندی ها</a></li>
                                @endcan
                                @can('products.brands')
                                    <li><a class="dropdown-item" href="{{ route('np.brands.index') }}"><i class="bi bi-award me-1"></i> برندها</a></li>
                                @endcan
                            </ul>
                        </li>
                    @endcan
                    @can('posts')
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button">مقالات</a>
                            <ul class="dropdown-menu">
                                @can('posts.index')
                                    <li><a class="dropdown-item" href="{{ route('np.posts.index') }}"><i class="bi bi-newspaper me-1"></i> لیست مقالات</a></li>
                                @endcan
                                @can('posts.create')
                                    <li><a class="dropdown-item" href="{{ route('np.posts.create') }}"><i class="bi bi-plus-lg me-1"></i> ایجاد مقاله جدید</a></li>
                                @endcan
                            </ul>
                        </li>
                    @endcan
                    @can('settings.information')
                        <li class="nav-item"><a class="nav-link" href="{{ route('np.settings.information') }}">تنظیمات</a></li>
                    @endcan
                    @can('themes.settings')
                        <li class="nav-item"><a class="nav-link" href="{{ route('np.widgets.index') }}">ویرایش صفحه</a></li>
                    @endcan
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button">
                            <img src="{{ auth('adminPanel')->user()->image_url }}" alt="" class="rounded-circle" width="24" height="24">
                            {{ auth('adminPanel')->user()->full_name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('np.dashboard') }}"><i class="bi bi-speedometer2 me-1"></i> پیشخوان</a></li>
                            <li><a class="dropdown-item" href="{{ route('np.themes.index') }}"><i class="bi bi-brush me-1"></i> قالب‌ها</a></li>
                            <li><a class="dropdown-item" href="{{ route('np.logout') }}"><i class="bi bi-box-arrow-left me-1"></i> خروج</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    @endauth

    {{-- بنر بالای هدر --}}
    @php $highest_banners = highest_banner(); @endphp
    @if(count($highest_banners))
        @foreach($highest_banners as $banner)
            <a href="{{ $banner->link }}">
                <img src="{{ $banner->image ? asset($banner->image) : theme_asset('image/banner-3.webp') }}"
                     alt="{{ $banner->title }}" class="img-fluid w-100">
            </a>
        @endforeach
    @endif

    <header>
        <div class="container-fluid">
            <div class="row align-items-center">

                {{-- logo --}}
                <div class="col-lg-2 col-6 order-lg-1 order-1">
                    <div class="d-flex align-items-center">
                        <div class="responsive-menu d-lg-none d-block">
                            <button class="btn border-0 p-0 btn-responsive-menu" type="button"
                                    data-bs-toggle="offcanvas" data-bs-target="#responsiveMenu"
                                    aria-controls="responsiveMenu">
                                <i class="bi bi-list font-30"></i>
                            </button>
                            @include('front::partials.mobile-menu.menu')
                        </div>
                        <a href="{{ route('front.index') }}" aria-label="صفحه اصلی — سانیار">
                            <div class="logo">
                                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}"
                                     alt="{{ option('info_site_title', config('front.demo.name')) }}" class="img-fluid">
                            </div>
                        </a>
                    </div>
                </div>
                {{-- end logo --}}

                {{-- action (mobile) --}}
                <div class="d-lg-none d-block col-6 order-lg-5 order-2">
                    <div class="d-flex align-items-center justify-content-end">
                        <a class="btn btn-light no-highlight btn-lg btn-action-cart shadow-sm"
                           data-bs-toggle="offcanvas" href="#offcanvasCart" role="button"
                           aria-controls="offcanvasCart">
                            <i class="bi bi-basket font-30"></i>
                            <span class="header-cart-counter main-color-one-bg d-inline-block rounded-pill">
                                {{ $cart_count }}
                            </span>
                        </a>
                        @auth('web')
                            <div class="dropdown ms-3">
                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false" role="button"
                                   class="btn btn-white header-register border-0 rounded-pill">
                                    <figure class="avatar">
                                        <img src="{{ auth('web')->user()->image_url }}" alt="{{ auth('web')->user()->fullname ?? auth('web')->user()->mobile }}">
                                    </figure>
                                </a>
                                @include('front::partials.user-menu')
                            </div>
                        @else
                            <div class="dropdown ms-3">
                                <a href="{{ route('login') }}" role="button"
                                   class="btn btn-white header-register border-0 rounded-pill">
                                    <i class="bi bi-person-circle font-20 text-muted me-1"></i>
                                    <span>ورود</span>
                                </a>
                            </div>
                        @endauth
                    </div>
                </div>
                {{-- end action --}}

                {{-- form search (desktop) --}}
                <div class="col-lg-6 order-lg-2 d-lg-block d-none">
                    <div class="search-form">
                        <form action="{{ route('front.products.index') }}" method="GET">
                            <div class="search-filed">
                                <input type="text" name="q" value="{{ request('q') }}" placeholder="جستجوی محصولات ..." class="form-control search-input" autocomplete="off">
                                <button type="submit" class="btn search-btn main-color-one-bg rounded-pill"><i class="bi bi-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
                {{-- end form search --}}

                {{-- auth (desktop) --}}
                <div class="col-lg-4 order-lg-3 d-lg-block d-none">
                    <div class="d-flex align-items-center justify-content-end">
                        @auth('web')
                            <div class="btn-group header-btn-group shadow-sm rounded-pill">
                                <div class="dropdown">
                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false" role="button"
                                       class="btn btn-white header-register border-0 rounded-pill">
                                        <i class="bi bi-person-circle font-20 text-muted me-1"></i>
                                        <span>{{ auth('web')->user()->fullname ?? auth('web')->user()->mobile }}</span>
                                    </a>
                                    @include('front::partials.user-menu')
                                </div>
                            </div>
                        @else
                            <div class="btn-group header-btn-group shadow-sm rounded-pill">
                                <div class="dropdown">
                                    <a href="{{ route('register') }}" role="button"
                                       class="btn btn-white header-register border-0 rounded-pill">
                                        <i class="bi bi-person-circle font-20 text-muted me-1"></i>
                                        <span>ثبت نام</span>
                                    </a>
                                </div>
                                <a href="{{ route('login') }}" class="btn btn-white header-login border-0 rounded-pill">ورود</a>
                            </div>
                        @endauth
                        <div class="header-whishlist shadow-sm">
                            <a href="{{ route('front.favorites.index') }}" aria-label="علاقه‌مندی‌ها">
                                <i class="bi bi-bookmark-check font-20 text-muted"></i>
                            </a>
                        </div>
                        <div class="header-cart ms-1">
                            <a class="btn header-cart-toggle rounded-pill btn-white border-0 shadow-sm"
                               data-bs-toggle="offcanvas" href="#offcanvasCart" role="button"
                               aria-controls="offcanvasCart" aria-label="سبد خرید">
                                <i class="bi bi-shop font-20 text-muted me-1"></i>
                                <span class="header-cart-counter main-color-one-bg d-inline-block rounded-pill">
                                    {{ $cart_count }}
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                {{-- end auth --}}

            </div>
        </div>
    </header>

    {{-- mega menu (desktop) --}}
    @include('front::partials.menu.menu')

    <main>
        {{-- پیام‌های فلش --}}
        @if(session('message'))
            <div class="container-fluid mt-2">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="container-fluid mt-2">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('front::partials.footer')

    {{-- mobile footer bar --}}
    <div class="mobile-footer d-lg-none d-flex">
        <div class="parent">
            <div class="item" onclick="topFunction()">
                <i class="bi bi-chevron-up font-20"></i>
            </div>
            <div class="item">
                <a href="{{ route('front.index') }}" aria-label="صفحه اصلی">
                    <i class="bi bi-house font-20"></i>
                </a>
            </div>
            <div class="item item-float">
                <a data-bs-toggle="offcanvas" href="#offcanvasCart" role="button" aria-controls="offcanvasCart" aria-label="سبد خرید">
                    <i class="bi bi-bag font-20"></i>
                </a>
            </div>
            <div class="item">
                <a href="{{ route('front.products.specials') }}" aria-label="پیشنهادهای ویژه">
                    <i class="bi bi-stars"></i>
                </a>
            </div>
            <div class="item">
                <a href="{{ auth('web')->check() ? route('front.user.profile') : route('login') }}" aria-label="حساب کاربری">
                    <i class="bi bi-person"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- cart canvas --}}
    @include('front::partials.cart')

    <div class="float-btn">
        <div class="container-fluid">
            {{-- go to top --}}
            <div class="progress-wrap d-lg-block d-none">
                <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
                    <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
                </svg>
            </div>
            {{-- contact us floating --}}
            <div id="btncollapzion" class=" btn_collapzion"></div>
            <div class="" id="contactOverlay"></div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="{{ theme_asset('js/jquery.min.js') }}"></script>
    <script src="{{ theme_asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ theme_asset('plugin/waves/waves.min.js') }}"></script>
    <script src="{{ theme_asset('plugin/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ theme_asset('plugin/timer/timer.js') }}"></script>
    <script src="{{ theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js') }}"></script>
    <script src="{{ theme_asset('js/app.js') }}"></script>
    <script src="{{ theme_asset('js/jquery-app.js') }}"></script>
    {{-- سانیار فاز C: جستجوی زنده هدر --}}
    <script src="{{ theme_asset('js/live-search.js') }}"></script>
    <script src="{{ theme_asset('plugin/go-to-top/script.js') }}"></script>
    <script src="{{ theme_asset('plugin/rasta-contact/script.js') }}"></script>

    @php
        $floating_contact = [
            ['label' => 'پشتیبانی تلفنی', 'url' => 'tel:' . preg_replace('/[^0-9+]/', '', option('info_tel', '')), 'icon' => 'bi bi-telephone'],
            ['label' => 'پشتیبانی تلگرام', 'url' => option('social_telegram', '#'), 'icon' => 'bi bi-telegram'],
            ['label' => 'پشتیبانی واتس آپ', 'url' => option('social_whatsapp', '#'), 'icon' => 'bi-whatsapp'],
        ];
    @endphp
    <script>
        $('#btncollapzion').Collapzion({
            _child_attribute: {!! json_encode($floating_contact, JSON_UNESCAPED_UNICODE) !!},
        });
    </script>

    {!! option('info_scripts', '') !!}

    {{-- توست‌های فلش سشن (فاز F — کیف پول/برداشت و سایر جریان‌های سروری) --}}
    @if(session('toast-success') || session('toast-error'))
        <script>
            (function () {
                function show(msg, type) {
                    var $t = jQuery('#saniar-toast');
                    if (!$t.length) {
                        $t = jQuery('<div id="saniar-toast" class="saniar-toast"></div>').appendTo('body');
                    }
                    $t.text(msg)
                        .removeClass('saniar-toast-error saniar-toast-success')
                        .addClass(type === 'error' ? 'saniar-toast-error' : 'saniar-toast-success')
                        .addClass('saniar-toast-show');
                    setTimeout(function () { $t.removeClass('saniar-toast-show'); }, 4000);
                }
                jQuery(function () {
                    @if(session('toast-success'))
                        show(@json(session('toast-success')), 'success');
                    @endif
                    @if(session('toast-error'))
                        show(@json(session('toast-error')), 'error');
                    @endif
                });
            })();
        </script>
        @php session()->forget(['toast-success', 'toast-error']); @endphp
    @endif

    @stack('scripts')
</body>

</html>

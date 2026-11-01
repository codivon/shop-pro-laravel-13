<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="16x16" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}"">

    <title>   @isset($title)
            {{ $title }} |
        @endisset

        {{ option('info_site_title', 'او پی شاپ') }}</title>
    <!--    font-->
    {{-- فاز ۱۳۹ — خودمیزبانی FA: cdnjs روی IPهای ایران کند است و رندر را بلاک می‌کرد --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{theme_asset('css/font-awesome.min.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css')}}">
    <!--    font-->
    <link rel="stylesheet" href="{{theme_asset('css/bootstrap.css')}}">
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{theme_asset('css/style.css')}}">
    @stack('styles')
</head>

<body>
<!--shopping-->
<header class="shopping-page">
    <div class="container">
        <div class="header-shopping-logo">
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">
            </a>
        </div>

    </div>

    <div class="container">
        <div class="row">
            <ul class="checkout-steps">
                @yield('cart-header')

            </ul>
        </div>
    </div>
</header>

<div class="main-shopping">
    @yield('content')

</div>
@include('front::partials.footer')
<script>
    var BASE_URL = "{{ route('front.index') }}";
    var IS_RTL = {{ $current_local['direction'] == 'rtl' ? 1 : 0 }};
</script>
<!--shopping-->
<script src="{{theme_asset('js/jquery-3.2.1.min.js')}}"></script>
@stack('scripts-top-js')
<script src="{{theme_asset('js/owl.carousel.min.js')}}"></script>
<script src="{{theme_asset('js/jquery.countdown.min.js')}}"></script>
<script src="{{theme_asset('js/popper.min.js')}}"></script>


<script type="text/javascript" src="{{ theme_asset('js/plugins/map.ir/mapp.env.js') }}"></script>
<script type="text/javascript" src="{{ theme_asset('js/plugins/map.ir/mapp.min.js') }}"></script>
<script src="{{theme_asset('js/pages/addresses/map-select-scripts.js')}}"></script>

<script src="{{ theme_asset("js/plugins/sweetalert2.all.min.js") }}"></script>
<script src="{{ theme_asset("js/plugins/jquery.blockUI.js") }}"></script>
<script src="{{ theme_asset("js/plugins/toastr/toastr.min.js") }}"></script>
<script src="{{ theme_asset('js/vendor/jquery.fancybox.min.js') }}"></script>
<script src="{{theme_asset('js/bootstrap.js')}}"></script>
@stack('scripts')
<script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
<script src="{{ theme_asset("js/scripts.js") }}?v=11"></script>
<script src="{{theme_asset('js/main.js')}}"></script>
<script src="{{theme_asset('js/theia-sticky-sidebar.min.js')}}"></script>


{!! option('info_scripts') !!}


</body>

</html>

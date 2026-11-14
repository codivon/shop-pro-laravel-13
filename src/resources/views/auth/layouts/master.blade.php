<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f7858d">
    <meta name="msapplication-navbutton-color" content="#f7858d">
    <meta name="apple-mobile-web-app-status-bar-style" content="#f7858d">
    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="16x16" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">

    <title>
        @isset($title)
            {{ $title }} |
        @endisset

        {{ option('info_site_title', 'او پی شاپ') }}
    </title>

    {{-- فاز ۱۵۷ — حالت روز/شب: مقداردهی قبل از رندر برای جلوگیری از فلش --}}
    <script src="{{ theme_asset('js/theme-init.js') }}"></script>

    {{-- قالب سانیار — همان فایل‌های css صفحات قالب (register.html) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('font/bootstrap-icon/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/timer/timer.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/auth.css') }}">

    {{-- فاز ۱۵۷ — دارک مود در صفحات ورود/ثبت‌نام --}}
    <link rel="stylesheet" href="{{ theme_asset('css/theme-dark.css') }}">

    @yield('style')
    @stack('styles')
</head>

<body class="bg-auth">

    {{-- کارت احراز هویت — ساختار register.html قالب سانیار --}}
    <div class="content vh-100">
        <div class="container-fluid h-100">
            <div class="auth h-100 d-flex align-items-center">
                <div class="container-fluid">
                    <div class="auth-items">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- این br ها برای ایجاد اسکرول در صفحه هستند که تمام اجزای فرم به بدرستی نمایش داده شود --}}
    <br><br><br><br><br><br><br><br>

    <script src="{{theme_asset('js/jquery.min.js')}}"></script>
    <script src="{{theme_asset('js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{theme_asset('plugin/waves/waves.min.js')}}"></script>
    <script src="{{theme_asset('plugin/swiper/swiper-bundle.min.js')}}"></script>
    <script src="{{theme_asset('plugin/timer/timer.js')}}"></script>
    <script src="{{theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js')}}"></script>
    <script src="{{theme_asset('js/app.js')}}"></script>
    <script src="{{theme_asset('js/jquery-app.js')}}"></script>
    <script src="{{ theme_asset('js/vendor/ResizeSensor.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery.blockUI.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/sweetalert2.all.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
    <script src="{{ theme_asset("js/scripts.js") }}?v=11"></script>
    <script src="{{theme_asset('js/jquery.countdown.min.js')}}"></script>
    <script src="{{theme_asset('js/owl.carousel.min.js')}}"></script>
    <script src="{{theme_asset('js/main.js')}}"></script>

    <script>
        var BASE_URL = "{{ route('front.index') }}";
    </script>

    {!! option('info_scripts') !!}
    @stack('scripts')
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
</body>

</html>

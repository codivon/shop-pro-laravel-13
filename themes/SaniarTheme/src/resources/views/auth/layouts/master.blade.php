<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#f7858d">
    <meta name="msapplication-navbutton-color" content="#f7858d">
    <meta name="apple-mobile-web-app-status-bar-style" content="#f7858d">
    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="16x16" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}"">

    <title>
        @isset($title)
            {{ $title }} |
        @endisset

        {{ option('info_site_title', 'او پی شاپ') }}
    </title>
    <!-- Bootstrap -->
    <link rel="stylesheet" href="{{theme_asset('css/font-awesome.min.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css.map')}}">
    <!--    font-->
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{theme_asset('css/bootstrap.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/owl.carousel.min.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/style.css')}}">
    <style>
        .invalid-feedback{
            display: inline-block;
            text-align: right;
            font-size: 11px;
        }
    </style>
    @yield('style')
    @stack('styles')
</head>

<body>

    <div id="main">


        @yield('content')



    </div>

    <script src="{{theme_asset('js/jquery-3.2.1.min.js')}}"></script>
    <script src="{{theme_asset('js/owl.carousel.min.js')}}"></script>
    <script src="{{theme_asset('js/jquery.countdown.min.js')}}"></script>
    <script src="{{theme_asset('js/popper.min.js')}}"></script>
    <script src="{{theme_asset('js/bootstrap.js')}}"></script>
    <script src="{{ theme_asset('js/vendor/ResizeSensor.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery.blockUI.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/sweetalert2.all.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
    <script src="{{ theme_asset("js/scripts.js") }}?v=11"></script>
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

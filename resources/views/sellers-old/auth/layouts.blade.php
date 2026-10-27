<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <!-- viewport meta -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ option('info_icon', theme_asset('images/favicon-32x32.png')) }}">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="index, follow"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="Auth" content="@auth{{'Yes'}}@else{{'No'}}@endauth">

@stack('meta')
<!-- Favicon Icon -->

    <title>
        @isset($title)
            {{ $title }} |
        @endisset

        {{ option('info_site_title', 'او پی شاپ') }}
    </title>
@stack('befor-styles')
<!--    font-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{theme_asset('css/font-awesome.min.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css.map')}}">
    <!--    font-->
    <script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{theme_asset('css/bootstrap.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/owl.carousel.min.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/style.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/sellers.css')}}">
    @stack('styles')
</head>

<body>

@yield('content')
<script>
    var BASE_URL = "{{ route('front.index') }}";
    var IS_RTL = {{ $current_local['direction'] == 'rtl' ? 1 : 0 }};
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
<script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{theme_asset('js/bootstrap.js')}}"></script>

@stack('scripts')
<script src="{{ theme_asset("js/scripts.js") }}?v=11"></script>
<script src="{{theme_asset('js/main.js')}}"></script>


{!! option('info_scripts') !!}
<script>
    var imgWalt = jQuery(".image-data-src img").get().map(function(el) {
        el.src=el.dataset.src
    });


</script>
</body>

</html>


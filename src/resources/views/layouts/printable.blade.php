{{-- لایه چاپ مستقل سانیار (فاز I) — بدون وابستگی به assetهای دفتر پشتیبان
    محتوا از @yield('print-content') | اسکریپت خودچاپ از js/pages/print.js --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('print-title', 'چاپ') | {{ option('info_site_title', config('front.demo.name')) }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/print.css') }}?v=1">
    @stack('print-styles')
</head>
<body data-saniar-print>
    @yield('print-content')

    <div class="container p-0 my-2 d-print-none">
        <div class="row">
            <div class="col-12 text-start">
                <button type="button" class="btn btn-outline-secondary saniar-print-btn" data-print-btn>
                    <i class="bi bi-printer"></i> چاپ
                </button>
            </div>
        </div>
    </div>

    <script src="{{ theme_asset('js/pages/print.js') }}?v=1"></script>
    @stack('print-scripts')
</body>
</html>

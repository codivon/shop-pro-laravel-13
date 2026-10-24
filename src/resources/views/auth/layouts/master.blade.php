{{-- لایوت صفحات احراز هویت سانیار — کارت وسط‌چین بدون هدر/فوتر (مطابق مرجع register.html) --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">
    <title>@yield('title', 'ورود') | {{ option('info_site_title', config('front.demo.name')) }}</title>

    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('font/bootstrap-icon/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/responsive.css') }}">
    {{-- فاز C: استایل فرم‌های auth سانیار (لیبل شناور، دکمه اصلی) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/auth.css') }}">
    @stack('styles')

    {!! option('info_header_codes', '') !!}
</head>
<body class="bg-auth">
    <div class="content vh-100">
        <div class="container-fluid h-100">
            <div class="auth h-100 d-flex align-items-center">
                <div class="container-fluid">
                    <div class="auth-items">
                        <div class="row justify-content-center">
                            <div class="col-lg-4">
                                @yield('content')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ theme_asset('js/jquery.min.js') }}"></script>
    <script src="{{ theme_asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ theme_asset('plugin/waves/waves.min.js') }}"></script>
    @stack('scripts')

    {{-- توست ساده برای پیام‌های سشن (مثل «رمز عبور با موفقیت تغییر کرد») — فاز E --}}
    @if(session('toast-success'))
        <div class="auth-toast auth-toast-success" role="status">{{ session('toast-success') }}</div>
    @endif
    @if(session('toast-error'))
        <div class="auth-toast auth-toast-error" role="alert">{{ session('toast-error') }}</div>
    @endif
</body>
</html>

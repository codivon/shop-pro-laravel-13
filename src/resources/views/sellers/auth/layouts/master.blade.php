{{-- لایوت احراز هویت فروشنده سانیار (فاز G) — همان کارت auth با عرض متغیر + نوار مراحل ثبت‌نام
    متغیرهای اختیاری: $auth_step (شماره مرحله فعلی ثبت‌نام ۱..۳)، $auth_card_class (کلاس عرض کارت) --}}

@php
    $auth_step = $auth_step ?? 0;
    $auth_steps = [
        1 => ['icon' => 'bi-file-earmark-text', 'label' => 'اطلاعات فروشنده'],
        2 => ['icon' => 'bi-cloud-arrow-up', 'label' => 'بارگذاری مدارک'],
        3 => ['icon' => 'bi-hand-thumbs-up', 'label' => 'اتمام ثبت‌نام'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">
    <title>@yield('title', 'مرکز فروشندگان') | {{ option('info_site_title', config('front.demo.name')) }}</title>

    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('font/bootstrap-icon/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}">
    @stack('styles')

    {!! option('info_header_codes', '') !!}
</head>
<body class="bg-auth">
<div class="content vh-100 overflow-auto">
    <div class="container-fluid h-100">
        <div class="auth h-100 d-flex align-items-center">
            <div class="container-fluid">
                <div class="auth-items">
                    <div class="row justify-content-center">
                        <div class="{{ $auth_card_class ?? 'col-lg-5' }}">
                            @if($auth_step)
                                {{-- نوار سه‌مرحله‌ای ثبت‌نام فروشنده --}}
                                <nav class="seller-auth-steps" aria-label="مراحل ثبت‌نام فروشنده">
                                    @foreach($auth_steps as $i => $step)
                                        @php $state = $i < $auth_step ? 'is-done' : ($i == $auth_step ? 'is-current' : ''); @endphp
                                        <span class="auth-step {{ $state }}">
                                            <span class="step-dot">
                                                <i class="bi {{ $i < $auth_step ? 'bi-check' : $step['icon'] }}"></i>
                                            </span>
                                            <span>{{ $i }}. {{ $step['label'] }}</span>
                                        </span>
                                        @if($i < 3)<span class="step-sep" aria-hidden="true"></span>@endif
                                    @endforeach
                                </nav>
                            @endif
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
<script src="{{ theme_asset('js/pages/sellers/seller-auth.js') }}?v=1"></script>
@stack('scripts')

{{-- توست سشن (فاز E) --}}
@if(session('toast-success'))
    <div class="auth-toast auth-toast-success" role="status">{{ session('toast-success') }}</div>
@endif
@if(session('toast-error'))
    <div class="auth-toast auth-toast-error" role="alert">{{ session('toast-error') }}</div>
@endif
</body>
</html>

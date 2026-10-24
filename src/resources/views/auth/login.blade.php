@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $params = null;
    if (request('ref')) {
        $params = "?ref=" . request('ref');
    }
@endphp

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">ورود / ثبت نام</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>
        <form action="{{ route('front.user.CheckMobileEmail') . $params }}" id="form-auth" method="post">
            @csrf

            {{-- فاز ۴۴ میزبان — تلهٔ زمان (زمان رندر فرم) --}}
            <input type="hidden" name="rendered_at" value="{{ time() }}">

            {{-- فاز ۴۴ میزبان — هانی‌پات: فیلد مخفی که فقط ربات‌ها پر می‌کنند --}}
            @if(\App\Services\SecuritySettings::honeypotEnabled())
                <div style="position:absolute !important;height:0 !important;width:0 !important;margin:0 !important;padding:0 !important;overflow:hidden;opacity:0;" aria-hidden="true" tabindex="-1">
                    <label for="website_url">وب‌سایت شما</label>
                    <input id="website_url" type="text" name="website_url" tabindex="-1" autocomplete="off">
                    <label for="confirm_email">تأیید ایمیل</label>
                    <input id="confirm_email" type="text" name="confirm_email" tabindex="-1" autocomplete="off">
                </div>
            @endif

            <div class="comment-item mb-3">
                <input type="text" class="form-control" id="username" name="username"
                       placeholder=" " value="{{ old('username') }}" autofocus required
                       aria-label="شماره موبایل یا ایمیل">
                <label for="username" class="form-label label-float">شماره تلفن یا ایمیل خود را وارد کنید</label>
            </div>
            @error('username')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror

            {{-- فاز ۴۴ میزبان — کپچای ورود مشتری (در صورت فعال بودن از تنظیمات امنیتی) --}}
            @if(\App\Services\SecuritySettings::captchaEnabled('customer_login'))
                <div class="mt-3">
                    <x-captcha />
                </div>
            @endif

            <div class="form-group step-one">
                <button type="submit" class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    ورود / ثبت نام
                </button>
            </div>
        </form>
    </div>

    <p class="loginTermsDesc mt-3">
        با ورود و یا ثبت نام در {{ option('info_site_title', config('front.demo.name')) }} شما
        <a class="underlined main-color-one-color fw-bold" href="{{ route('front.articles.show', ['slug' => 'rules']) }}">شرایط و قوانین</a>
        استفاده از سرویس‌های سایت و
        <a class="underlined main-color-one-color fw-bold" href="{{ route('front.articles.show', ['slug' => 'privacy-polices']) }}">قوانین حریم خصوصی</a>
        آن را می‌پذیرید.
    </p>
@endsection

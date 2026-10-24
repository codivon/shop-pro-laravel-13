{{-- ورود فروشنده (فاز G) — POST: seller.login_check → JSON {status, message, redirect} --}}
@extends('front::sellers.auth.layouts.master', ['title' => 'ورود به مرکز فروشندگان'])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-0 bg-white seller-auth-card">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">ورود فروشندگان</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <form action="{{ route('seller.login_check') }}" method="post" data-ajax novalidate>
            @csrf
            <div class="comment-item mb-3">
                <input type="text" class="form-control" id="username" name="username"
                       placeholder=" " value="{{ old('username') }}" autofocus required
                       aria-label="شماره موبایل یا ایمیل">
                <label for="username" class="form-label label-float">شماره موبایل یا ایمیل</label>
            </div>
            @error('username')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror

            <div class="comment-item mb-3">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder=" " required autocomplete="current-password" aria-label="رمز عبور">
                <label for="password" class="form-label label-float">رمز عبور</label>
            </div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('username') ? 'checked' : '' }}>
                <label class="form-check-label font-14 text-muted" for="remember">مرا به خاطر داشته باش</label>
            </div>

            {{-- جعبه خطا (AJAX) --}}
            <div class="auth-form-errors is-empty" role="alert"></div>

            <div class="form-group">
                <button type="submit" class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    ورود به پنل فروشندگان
                </button>
            </div>
        </form>

        <div class="text-center border-top pt-3 mt-3">
            <span class="font-14 text-muted">هنوز ثبت‌نام نکرده‌اید؟</span>
            <a href="{{ route('seller.registration') }}" class="font-14 fw-bold main-color-one-color">همین حالا ثبت‌نام کنید</a>
        </div>
    </div>

    <p class="loginTermsDesc mt-3">
        <a class="underlined main-color-one-color fw-bold" href="{{ route('front.index') }}">بازگشت به فروشگاه</a>
    </p>
@endsection

{{-- ثبت‌نام فروشنده — مرحله شروع (فاز G) — POST: seller.registration_new_seller → JSON {status, redirect} --}}
@extends('front::sellers.auth.layouts.master', ['title' => 'ثبت‌نام فروشنده', 'auth_step' => 1])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-0 bg-white seller-auth-card">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">ثبت‌نام فروشنده</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <form action="{{ route('seller.registration_new_seller') }}" method="post" data-ajax novalidate>
            @csrf
            <div class="comment-item mb-3">
                <input type="email" class="form-control" id="email" name="email"
                       placeholder=" " value="{{ old('email') }}" required aria-label="ایمیل">
                <label for="email" class="form-label label-float">ایمیل</label>
            </div>
            @error('email')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror

            <div class="comment-item mb-3">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder=" " required autocomplete="new-password" aria-label="رمز عبور">
                <label for="password" class="form-label label-float">رمز عبور</label>
            </div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror

            <div class="comment-item mb-3">
                <input type="tel" inputmode="numeric" class="form-control mobile" id="mobile" name="mobile"
                       placeholder=" " value="{{ old('mobile') }}" required aria-label="شماره موبایل">
                <label for="mobile" class="form-label label-float">شماره موبایل (۰۹...)</label>
            </div>
            @error('mobile')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror

            <div class="auth-form-errors is-empty" role="alert"></div>

            <div class="form-group">
                <button type="submit" class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    ثبت‌نام و دریافت کد تایید
                </button>
            </div>
        </form>

        <div class="seller-auth-note mt-3">
            <i class="bi bi-info-circle-fill main-color-one-color ms-1"></i>
            اگر قبلا مراحل ثبت‌نام را نیمه‌تمام گذاشته‌اید،
            <a href="{{ route('seller.login') }}" class="fw-bold main-color-one-color">«ادامه ثبت‌نام»</a>
            را بزنید و با همان ایمیل و رمز عبور، ثبت‌نام را نهایی کنید.
        </div>

        <div class="text-center border-top pt-3 mt-3">
            <span class="font-14 text-muted">قبلا ثبت‌نام کرده‌اید؟</span>
            <a href="{{ route('seller.login') }}" class="font-14 fw-bold main-color-one-color">وارد شوید</a>
        </div>
    </div>
@endsection

@extends('front::auth.layouts.master', ['title' => 'بازیابی رمز عبور'])

@section('content')
    <a href="{{ route('login') }}" class="auth-back-link">
        <i class="bi bi-arrow-right"></i> بازگشت به صفحه ورود
    </a>

    <div class="auth-form shadow-xl rounded-3 mt-2 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">بازیابی رمز عبور</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="alert alert-info" role="status">
            <i class="bi bi-key"></i>
            شماره موبایل حساب خود را وارد کنید تا کد تایید برایتان ارسال شود.
        </div>

        <form id="forgot-password-form" method="POST"
              action="{{ route('password.send') }}"
              data-ajax
              data-redirect="{{ route('one-time-login') }}"
              data-append-mobile>
            @csrf

            <div class="comment-item mb-3">
                <input type="tel" inputmode="numeric" class="form-control" id="mobile" name="mobile"
                       placeholder=" " value="{{ old('mobile') }}" autofocus required
                       aria-label="شماره موبایل">
                <label for="mobile" class="form-label label-float">شماره موبایل</label>
            </div>

            <x-captcha />

            <div class="form-group mt-4">
                <button type="submit"
                        class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    ارسال کد تایید
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/auth.js') }}?v=1"></script>
@endpush

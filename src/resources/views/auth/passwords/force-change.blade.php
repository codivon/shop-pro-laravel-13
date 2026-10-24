@extends('front::auth.layouts.master', ['title' => 'تغییر رمز عبور'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
@endphp

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">تغییر رمز عبور</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="alert alert-info" role="status">
            <i class="bi bi-shield-lock"></i>
            شما با کد یکبار مصرف وارد شده‌اید؛ برای امنیت حساب، لطفاً رمز عبور جدیدی برای حساب خود تعیین کنید.
        </div>

        <form id="force-password-change-form" method="POST"
              action="{{ route('front.user.force-update-password') }}"
              data-ajax>
            @csrf

            <div class="comment-item position-relative mb-3">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder=" " minlength="6" required autocomplete="new-password"
                       aria-label="رمز عبور جدید">
                <label for="password" class="form-label label-float">رمز عبور جدید</label>
                <button type="button" class="toggle-password" aria-label="نمایش رمز عبور">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>

            <div class="comment-item mb-3">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                       placeholder=" " minlength="6" required autocomplete="new-password"
                       aria-label="تکرار رمز عبور جدید">
                <label for="password_confirmation" class="form-label label-float">تکرار رمز عبور جدید</label>
            </div>

            <div class="form-group mt-4">
                <button type="submit"
                        class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    <i class="bi bi-check2-circle"></i> ثبت رمز عبور جدید
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        window.authRedirectUrl = '{{ $redirect_url }}';
    </script>
    <script src="{{ theme_asset('js/pages/auth.js') }}?v=1"></script>
@endpush

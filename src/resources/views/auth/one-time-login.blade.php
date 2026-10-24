@extends('front::auth.layouts.master', ['title' => 'ورود با کد تایید'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
    $type = request()->type;
    $back_url = $type == 'login' ? route('login-with-code.request') : route('password.request');
    $action = $type == 'login' ? route('login-with-code.confirm') : route('one-time-login');
    $is_forgot = $type != 'login';
@endphp

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">
                {{ $is_forgot ? 'بازیابی رمز عبور' : 'ورود با کد تایید' }}
            </h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="alert alert-success text-center" role="status">
            <i class="bi bi-chat-dots"></i>
            کد تایید ۵ رقمی به شماره
            <span class="auth-number-chip">{{ $user->username }}</span>
            ارسال شد.
        </div>

        <a href="{{ $back_url }}" class="auth-back-link">
            <i class="bi bi-arrow-right"></i> ویرایش شماره موبایل
        </a>

        <form id="one-time-login-form" method="POST"
              action="{{ $action }}"
              data-ajax
              data-otp-autosubmit>
            @csrf
            <input name="mobile" type="hidden" value="{{ $user->username }}">

            @include('front::auth.partials.otp', ['resend_action' => $back_url])

            <div class="form-group mt-4">
                <button type="submit"
                        class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    <i class="bi bi-check2-circle"></i>
                    {{ $is_forgot ? 'تایید و ادامه' : 'ورود به حساب کاربری' }}
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

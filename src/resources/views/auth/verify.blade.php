@extends('front::auth.layouts.master', ['title' => 'تایید شماره همراه'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
@endphp

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">تایید شماره همراه</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="alert alert-success text-center" role="status">
            <i class="bi bi-chat-dots"></i>
            کد تایید ۵ رقمی به شماره
            <span class="auth-number-chip">{{ auth('web')->user()->username }}</span>
            ارسال شد.
        </div>

        <a href="{{ route('front.verify.showChangeUsername') }}" class="auth-back-link">
            <i class="bi bi-pencil-square"></i> ویرایش شماره موبایل
        </a>

        <form id="verify-username-form" method="POST"
              action="{{ route('front.verify.verifyCode') }}"
              data-ajax
              data-otp-autosubmit>
            @csrf

            @include('front::auth.partials.otp', ['resend_action' => route('front.verify.showVerify')])

            <div class="form-group mt-4">
                <button type="submit"
                        class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    <i class="bi bi-check2-circle"></i> تایید شماره همراه
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

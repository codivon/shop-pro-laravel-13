@extends('front::auth.layouts.master', ['title' => 'تایید شماره موبایل'])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">تایید شماره موبایل</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="alert alert-success text-center" role="status">
            <i class="bi bi-chat-dots"></i>
            حساب کاربری با شماره
            <span class="auth-number-chip">{{ session('ShowPasswordForm_Mobile_Email') }}</span>
            وجود ندارد. برای ساخت حساب جدید، کد تایید ۵ رقمی به این شماره ارسال شد.
        </div>

        <a href="{{ route('login') }}" class="auth-back-link">
            <i class="bi bi-arrow-right"></i> ویرایش شماره موبایل
        </a>

        @if ($errors->any())
            <div class="auth-form-errors" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>{{ $errors->all()[0] }}</span>
            </div>
        @endif

        <form action="{{ route('front.user.Register_Mobile') }}" id="register-with-code-form" method="post"
              data-otp-autosubmit>
            @csrf
            <input name="mobile" type="hidden" value="{{ session('ShowPasswordForm_Mobile_Email') }}">

            @include('front::auth.partials.otp', ['resend_action' => route('login')])

            <div class="form-group mt-4">
                <button type="submit"
                        class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    تایید و ادامه
                    <i class="bi bi-arrow-left"></i>
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

@push('scripts')
    <script src="{{ theme_asset('js/pages/auth.js') }}?v=1"></script>
@endpush

@php session()->forget('ShowPasswordForm_Mobile_Email'); @endphp

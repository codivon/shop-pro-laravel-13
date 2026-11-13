@extends('front::auth.layouts.master', ['title' => 'تایید شماره همراه'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت تایید کد — ساختار sending-code.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font">تایید شماره همراه</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="120" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                <div class="alert text-center alert-success font-13">
                    برای شماره همراه {{ auth('web')->user()->username }} کد تایید ارسال گردید
                    <br>
                    <a href="{{ route('front.verify.showChangeUsername') }}" class="form-edit-number">
                        ویرایش شماره
                    </a>
                </div>

                <form id="verify-username-form" action="{{ route('front.verify.verifyCode') }}">
                    @csrf
                    <div class="form-row">
                        <div class="numbers-verify form-content form-content1 w-100">
                            <input name="verify_code" class="activation-code-input" placeholder="کد تایید را وارد کنید">
                        </div>
                    </div>
                    <div class="form-row mt-4 text-center">
                        <span class="font-13">دریافت مجدد کد تایید</span> (<p id="countdown-verify-end" class="d-inline"></p>)
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn-login w-100 btn main-color-one-bg text-white rounded-3 border-0 py-3">
                            تایید شماره همراه
                            <i class="bi bi-check-lg"></i>
                        </button>
                    </div>
                </form>

            </div>

            <p class="loginTermsDesc mt-3">با ورود و یا ثبت نام در {{ option('info_site_title', 'او پی شاپ') }} شما <a class="underlined main-color-one-color fw-bold" href="/rules/">شرایط و
                قوانین</a> استفاده از سرویس‌های سایت و <a class="underlined main-color-one-color fw-bold" href="/privacy-polices/">قوانین حریم
                خصوصی</a> آن را می‌پذیرید.</p>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ $redirect_url }}';
        var resend_time = {{ $resend_time }};
    </script>

    <script src="{{ theme_asset('js/vendor/countdown.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/verify.js?v=3') }}"></script>
@endpush

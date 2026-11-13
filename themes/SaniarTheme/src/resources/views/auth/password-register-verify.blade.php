@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
   $type = request()->type;
   $back_url = $type == 'login';
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت ثبت‌نام با کد تایید — ساختار sending-code.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font">کد تایید را وارد کنید</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="120" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                <form id="register-with-code-form" action="{{route('front.user.Register_Mobile')}}" method="post">
                    @csrf
                    <div class="alert text-center alert-success font-13">
                        حساب کاربری با شماره موبایل {{session('ShowPasswordForm_Mobile_Email')}} وجود ندارد. برای ساخت حساب جدید،کد تایید برای این شماره ارسال گردید.
                    </div>

                    <input name="mobile" type="hidden" value="{{session('ShowPasswordForm_Mobile_Email')}}">
                    <div class="form-row">
                        <div class="numbers-verify form-content form-content1 w-100">
                            <input name="verify_code" class="activation-code-input" placeholder="کد تایید را وارد کنید">
                        </div>
                    </div>
                    <div class="form-row mt-4 text-center">
                        <span class="font-13">دریافت مجدد کد تایید</span> (<p data-action="{{ $back_url }}" id="countdown-verify-end" class="d-inline"></p>)
                    </div>

                    @if ($errors->any())
                        <span class="invalid-feedback" role="alert" style="margin: 10px 0;text-align: center">
                                            <strong>{{ $errors->all()[0] }}</strong>
                                        </span>

                    @endif

                    <div class="form-group mt-3">
                        <button type="submit" class="btn-login w-100 btn main-color-one-bg text-white rounded-3 border-0 py-3">
                            ادامه
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
    <script src="{{theme_asset('js/countdown.min.js')}}"></script>
    <script src="{{ theme_asset('js/pages/password-register-verify.js?v=3') }}"></script>
@endpush

@php session()->forget('ShowPasswordForm_Mobile_Email'); @endphp

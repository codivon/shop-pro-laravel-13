@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
   $type = request()->type;
   $back_url = $type == 'login';
@endphp

@section('style')
    <style>
        .dk-btn:before{
            right: -97px;
        }
        #main .account-box .content-account {
            padding: 10px 30px 30px;
        }
        .message-light{
            padding: 15px 30px;
        }
        .form-account label{
            margin-bottom: 19px;
            font-size: 13px;
        }
    </style>
@endsection
@section('content')
    <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
        <div class="account-box form-ui">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

            </a>
            <span class="account-head-line">کد تایید را وارد کنید</span>

            <form id="register-with-code-form" action="{{route('front.user.Register_Mobile')}}" method="post"  class="message-light">
                @csrf
                <div class="form-account">
                    <label>
                        حساب کاربری با شماره موبایل {{session('ShowPasswordForm_Mobile_Email')}} وجود ندارد. برای ساخت حساب جدید،کد تایید برای این شماره ارسال گردید.
                    </label>

                </div>
                <input name="mobile" type="hidden" value="{{session('ShowPasswordForm_Mobile_Email')}}">
                <div class="form-row">
                    <div class="numbers-verify form-content form-content1 w-100">
                        <input name="verify_code" class="activation-code-input" placeholder="کد تایید را وارد کنید">
                    </div>
                </div>
                <div class="form-row mt-4 ">
                    <span class="form-account-row">دریافت مجدد کد تایید</span> (<p data-action="{{ $back_url }}" id="countdown-verify-end"></p>)
                </div>

                @if ($errors->any())
                    <span class="invalid-feedback" role="alert" style="margin: 10px 0;text-align: center">
                                        <strong>{{ $errors->all()[0] }}</strong>
                                    </span>

                @endif

                <div class="parent-btn">
                    <button type="submit" class="dk-btn dk-btn-info text-center mt-5">
                        ادامه
                    </button>
                </div>
            </form>
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

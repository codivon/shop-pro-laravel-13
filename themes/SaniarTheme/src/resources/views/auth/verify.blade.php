@extends('front::auth.layouts.master', ['title' => 'تایید شماره همراه'])

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
@endphp

@section('content')
    <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
        <div class="account-box">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

            </a>
            <div class="message-light">
                <div class="massege-light">
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
                    <div class="form-row mt-4 ">
                        <span class="form-account-row">دریافت مجدد کد تایید</span> (<p id="countdown-verify-end"></p>)
                    </div>

                    <div class="parent-btn mt-1">
                        <button class="dk-btn dk-btn-info">
                            تایید شماره همراه
                            <i class="mdi mdi-check sign-in"></i>
                        </button>
                    </div>
                </form>
            </div>

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

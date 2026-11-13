@extends('front::auth.layouts.master', ['title' => 'ورود با رمز یکبار مصرف'])

@section('content')
    <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
        <div class="account-box">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

            </a>
            <span class="account-head-line">ورود با رمز یکبار مصرف</span>
            <div class="content-account pb-5">
                <form id="login-with-code-form" data-redirect="{{ route('one-time-login', ['type' => 'login']) }}" action="{{ route('login-with-code.send') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="mobile">شماره موبایل خود را وارد کنید</label>
                        <input type="text" name="mobile" id="mobile" class="input-email-account" placeholder="">
                    </div>
                    <div class="row pr-4">
                        <x-captcha />
                    </div>

                    <div class="parent-btn">
                        <button class="dk-btn dk-btn-info">
                            درخواست کد تایید
                            <i class="mdi mdi-lock sign-in"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="account-footer">

                <a href="{{ route('login') }}" class="btn-link-register">ورود با رمز عبور</a>
            </div>
        </div>
    </div>


@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/login-with-code.js') }}"></script>
@endpush

@extends('front::auth.layouts.master', ['title' => 'فراموشی رمز عبور'])

@section('content')
    <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
        <div class="account-box">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

            </a>
            <span class="account-head-line">یاد آوری کلمه عبور</span>
            <div class="content-account pb-5">
                <form id="forgot-password-form" data-redirect="{{ route('one-time-login') }}" action="{{ route('password.send') }}" method="POST">
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
        </div>
    </div>



@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/forgot-password.js') }}?v=2"></script>
@endpush

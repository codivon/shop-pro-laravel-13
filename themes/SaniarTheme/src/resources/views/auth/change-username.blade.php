@extends('front::auth.layouts.master', ['title' => 'تغییر شماره همراه'])

@section('content')
    <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
        <div class="account-box">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

            </a>
            <span class="account-head-line">تغییر شماره همراه</span>
            <div class="content-account">
                <form id="change-username-form" data-redirect="{{ route('front.verify.showVerify') }}" action="{{ route('front.verify.changeUsername') }}" method="POST">
                    @csrf
                    <label for="email-phone">شماره موبایل</label>
                    <input type="text" id="email-phone" name="mobile" class="input-email-account" value="{{ auth('web')->user()->username }}" placeholder="  شماره موبایل خود را وارد نمایید">


                    <div class="parent-btn">
                        <button type="submit" class="dk-btn dk-btn-info">
                            ورود به دیجی استور
                            <i class="fa fa-sign-in sign-in"></i>
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/change-username.js') }}"></script>
@endpush

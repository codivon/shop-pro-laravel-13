@extends('front::auth.layouts.master', ['title' => 'تغییر رمز عبور'])

@section('content')
    <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
        <div class="account-box">
            <a href="/" class="logo-account">
                <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

            </a>

            <span class="account-head-line">تغییر رمز عبور</span>
            <div class="content-account">
                <div class="massege-light">
                    شما باید رمز عبور خود را تغییر دهید!
                </div>
                <form id="force-password-change-form" action="{{ route('front.user.force-update-password') }}" method="POST">
                    @csrf
                    <label for="password">رمز عبور جدید</label>
                    <input type="password" name="password" id="password" class="input-password" placeholder="">

                    <label for="password-new-again">تکرار رمز عبور جدید</label>
                    <input type="password" name="password_confirmation" id="password-new-again" class="input-password" placeholder="">

                    <div class="parent-btn">
                        <button type="submit" class="dk-btn dk-btn-info">
                            تغییر رمز عبور
                            <i class="fa fa-refresh sign-in"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
@endphp

@push('scripts')
    <script>
        var redirect_url = '{{ $redirect_url }}';
    </script>

    <script src="{{ theme_asset('js/pages/force-password-change.js') }}"></script>
@endpush

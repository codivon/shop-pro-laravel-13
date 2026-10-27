@extends('sellers-old.auth.layouts', ['title' => 'ورود با کد تایید'])



@section('content')
    <div class="new-login_seller">
        <div class='row w-100 m-0'>

            <div class='col-md-4 p-0'>
                <div class="new-login_seller-sidebar">
            <div class="new-login_seller_sidebar-content">
                <header>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}"
                             alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                    <h1>ثبت‌نام در مرکز فروشندگان</h1>
                </header>

                <ul class="c-reg-steps d-flex-r">
                    <li class="c-reg-steps__item">

                        <div class="c-reg-steps__icon c-reg-steps__icon--info c-reg-steps__icon--current">
                            <i class="fa-solid fa-file-pen"></i>
                        </div>
                        <h2 class="c-reg-steps__header">۱. اطلاعات فروشنده</h2>
                        <p class="c-reg-steps__description">اطلاعات شخصی فروشنده، اطلاعات تجاری، اطلاعات تماس</p>
                    </li>
                    <li class="c-reg-steps__item c-reg-steps__item--next">

                        <div class="c-reg-steps__icon c-reg-steps__icon--documents">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <h2 class="c-reg-steps__header">۲. بارگذاری مدارک</h2>
                        <p class="c-reg-steps__description">اطلاعات مربوط به مالیات بر ارزش افزوده، تصویر مدارک شخصی و تجاری</p>
                    </li>
                    <li class="c-reg-steps__item c-reg-steps__item--next">

                        <div class="c-reg-steps__icon c-reg-steps__icon--checkout">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h2 class="c-reg-steps__header">۳. اتمام ثبت نام</h2>
                        <p class="c-reg-steps__description">به جمع فروشندگان {{ option('info_site_title', 'او پی شاپ') }} خوش آمدید.</p>
                    </li>
                </ul>
            </div>
        </div>
            </div>

            <div class='col-md-8' id="main">
                <div class="new-login_seller_main" style=' max-width: 419px;'>
                <div class="account-box">

            <div class="message-light">
                <div class="massege-light">
                    برای شماره همراه {{ $seller->mobile }} کد تایید ارسال گردید
                    <br>
                    <a href="{{ route('seller.registration') }}" class="form-edit-number">
                        ویرایش شماره
                    </a>
                </div>
                <form id="seller-register-mobile" action="{{ route('seller.registration_mobile_check') }}">
                    @csrf
                    <input name="mobile" type="hidden" value="{{ $seller->mobile }}">
                    <div class="form-row ">
                        <div class="numbers-verify form-content form-content1 w-100 form-group">
                            <input name="verify_code" class="activation-code-input" placeholder="کد تایید را وارد کنید">
                        </div>
                    </div>
                    <div class="form-row mt-4 ">
                        <span class="form-account-row">دریافت مجدد کد تایید</span> (<p data-action="" id="countdown-seller-verify-end"></p>)
                    </div>

                    <div class="parent-btn mt-1 mb-2">
                        <button class="dk-btn dk-btn-info mt-5">
                            تایید شماره همراه
                            <i class="mdi mdi-check sign-in"></i>
                        </button>
                    </div>
                </form>
            </div>

        </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        var redirect_url = '{{ @$redirect_url }}';
        var resend_time = {{ $resend_time }};
    </script>

    <script src="{{ theme_asset('js/vendor/countdown.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/sellers/register/registration-mobile.js') }}"></script>
@endpush

@extends('sellers-old.auth.layouts', ['title' => 'ثبت نام فروشنده'])


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
                            <div class="content-account">
                                <form action="{{route('seller.registration_new_seller')}}" id="seller-register-level1-form" method="post" novalidate="novalidate">
                                    @csrf
                                    <div class="form-group">
                                        <label for="email-phone"> ایمیل </label>
                                        <input type="text" name="email" class="input-email-account" placeholder=" ایمیل خود را وارد کنید" >
                                    </div>

                                    <div class="form-group">
                                        <label for="password">رمز عبور</label>
                                        <input type="password" id="password" name="password" class="input-password" placeholder="رمز عبور خود را وارد کنید" autocomplete="new-password">
                                    </div>
                                    <div class="form-group">
                                        <label for="email-phone">  شماره موبایل</label>
                                        <input type="text" name="mobile" class="input-email-account mobile" placeholder="09" >
                                    </div>
                                    <div class="parent-btn">
                                        <button type="submit" class="dk-btn dk-btn-info">
                                            ثبت نام
                                            <i class="mdi mdi-account-plus-outline sign-in"></i>
                                        </button>
                                    </div>

                                </form>
                                <div class="massege-light text-right p-3">توجه: اگر قبلا مراحل ثبت‌نام را نیمه‌تمام گذاشته‌اید، <br>
                                    <a href="{{route('seller.login')}}">«ادامه ثبت‌نام»</a>
                                    را بزنید و با همان ایمیل و رمز عبور، ثبت‌نام را نهایی کنید.</div>
                            </div>

                            <div class="account-footer mt-0">
                                <span>قبلا ثبت‌نام کرده‌اید؟</span>
                                <a href="{{route('seller.login')}}" class="btn-link-register">وارد شویــد</a>
                            </div>
                        </div>
                </div>
            </div>
        </div>



    </div>
@endsection
@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/register/registration-new-seller.js') }}"></script>
@endpush

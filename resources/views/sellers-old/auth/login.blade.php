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
                        <h1>به مرکز فروشندگان {{ option('info_site_title', 'او پی شاپ') }}
                        <br>
                            خوش آمدید!
                        </h1>
                    </header>

                    <div class="c-new-login__sidebar-center">
                        <img src="{{theme_asset('img/ccb24d55.png')}}" alt="" class="c-new-login__sidebar-img">
                    </div>
                </div>
            </div>
            </div>
            <div class='col-md-8' id="main">
                <div class="new-login_seller_main">
                <div class="col-lg-6 col-md-6 col-xs-12 mx-auto mb-5 d-inline-block">
                    <div class="account-box">
                        <div class="content-account">
                            <form action="{{route('seller.login_check')}}" id="seller-register-level1-form" method="post" novalidate="novalidate">
                                @csrf
                                <div class="form-group">
                                    <label for="email-phone">سلام!<br>

                                        لطفا شماره موبایل یا ایمیل خود را وارد کنید</label>
                                    <input type="text" name="username" class="input-email-account" placeholder="شماره موبایل یا ایمیل خود را وارد کنید" >
                                </div>

                                <div class="form-group">
                                    <label for="password">رمز عبور</label>
                                    <input type="password" id="password" name="password" class="input-password" placeholder="رمز عبور خود را وارد کنید" autocomplete="new-password">
                                </div>
                                <div class="form-auth-row d-inline-block w-100 mb-0">
                                    <label for="remember" class="ui-checkbox ">
                                        <input type="checkbox"  name="remember" {{ old('username') ? 'checked' : '' }} id="remember">
                                        <span class="ui-checkbox-check"></span>
                                    </label>
                                    <label for="remember" class="remember-me cursor-pointer">مرا به خاطر داشته باش</label>
                                </div>

                                <div class="parent-btn mt-2 mb-2">
                                    <button type="submit" class="dk-btn dk-btn-info">
                                       ورود
                                    </button>
                                </div>

                            </form>

                        </div>

                        <div class="account-footer mt-0">
                            <span>هنوز ثبت نام نکرده‌اید؟</span>
                            <a href="{{route('seller.registration')}}" class="btn-link-register">همین حالا ثبت نام کنید</a>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/login/login.js') }}"></script>
@endpush

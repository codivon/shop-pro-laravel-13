@extends('front::auth.layouts.master', ['title' => 'تغییر رمز عبور'])


@section('content')

        <div class="col-lg-4 col-md-6 col-xs-12 mx-auto">
            <div class="account-box form-ui ">
                <a href="/" class="logo-account">
                    <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}">

                </a>
                <span class="account-head-line">تغییر رمز عبور</span>
                <div class="content-account">
                    <form id="reset-form" action="{{ route('front.user.password.update') }}" method="POST">
                        @csrf
                        @method('put')
                        <div class="form-group">
                            <label for="password-old">رمز عبور قبلی</label>
                            <input type="password" name="prev_password" id="password-old" class="input-password" placeholder="">
                        </div>

                        <div class="form-group">
                        <label for="password">رمز عبور جدید</label>
                        <input type="password" id="password" name="password" class="input-password" placeholder="">
                        </div>

                        <div class="form-group">
                        <label for="password-new-again">تکرار رمز عبور جدید</label>
                        <input type="password" id="password-new-again" name="password_confirmation" class="input-password" placeholder="">
                        </div>

                            <div class="parent-btn">
                            <button class="dk-btn dk-btn-info">
                                تغییر رمز عبور
                                <i class="fa fa-refresh sign-in"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ route("front.user.profile") }}';
    </script>
    {{-- فاز ۱۳۹ — خط blockUI از GitHub حذف شد: URL به صفحهٔ HTML اشاره می‌کرد (نه JS) و
         layout پنل احراز هویت (auth/layouts/master) از قبل نسخهٔ محلی js/plugins/jquery.blockUI.js را لود می‌کند --}}
    <script src="{{ theme_asset('js/pages/reset.js') }}"></script>
@endpush

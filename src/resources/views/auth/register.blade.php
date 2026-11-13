@extends('front::auth.layouts.master', ['title' => 'ثبت نام در سایت'])

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت ثبت‌نام — ساختار register.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font">ثبت نام در سایت</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="120" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                <form id="register-form" action="{{ route('register') }}" method="POST">
                    @csrf

                    <div class="comment-item mb-3">
                        <input type="text" name="first_name" class="form-control" placeholder=" ">
                        <label class="form-label label-float">نام خود را وارد نمایید</label>
                    </div>

                    <div class="comment-item mb-3">
                        <input type="text" name="last_name" class="form-control" placeholder=" ">
                        <label class="form-label label-float">نام خانوادگی خود را وارد نمایید</label>
                    </div>

                    <div class="comment-item mb-3">
                        <input type="text" name="username" class="form-control" placeholder=" ">
                        <label class="form-label label-float">شماره موبایل خود را وارد نمایید</label>
                    </div>

                    <div class="comment-item mb-3">
                        <input id="password" type="password" name="password" class="form-control" placeholder=" ">
                        <label class="form-label label-float">رمز عبور خود را وارد نمایید</label>
                    </div>

                    <div class="comment-item mb-3">
                        <input type="password" name="password_confirmation" class="form-control" placeholder=" ">
                        <label class="form-label label-float">تکرار رمز عبور خود را وارد نمایید</label>
                    </div>

                    @if(\App\Services\SecuritySettings::captchaEnabled('register'))
                        <div class="row mb-3">
                            <x-captcha />
                        </div>
                    @endif

                    <div class="form-group">
                        <button type="submit" class="main-color-one-bg py-3 btn w-100 rounded-3 border-0">
                            <i class="bi bi-person-plus me-1 text-white"></i>
                            ثبت نام در سایت
                        </button>
                    </div>
                    <div class="form-footer text-center mt-3">
                        <span class="d-block fw-bold font-14">قبلا ثبت نام کرده اید؟</span>
                        <a href="{{ route('login') }}" class="d-inline-block mt-2 main-color-one-color">وارد شوید</a>
                    </div>
                </form>

            </div>

            <p class="loginTermsDesc mt-3">با ورود و یا ثبت نام در {{ option('info_site_title', 'او پی شاپ') }} شما <a class="underlined main-color-one-color fw-bold" href="/rules/">شرایط و
                قوانین</a> استفاده از سرویس‌های سایت و <a class="underlined main-color-one-color fw-bold" href="/privacy-polices/">قوانین حریم
                خصوصی</a> آن را می‌پذیرید.</p>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ request("redirect") ?: Redirect::intended()->getTargetUrl() }}';
    </script>

    <script src="{{ theme_asset('js/pages/register.js') }}?v=2"></script>
@endpush

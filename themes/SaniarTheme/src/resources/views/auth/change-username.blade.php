@extends('front::auth.layouts.master', ['title' => 'تغییر شماره همراه'])

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت تغییر شماره همراه — ساختار register.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font">تغییر شماره همراه</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="120" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                <form id="change-username-form" data-redirect="{{ route('front.verify.showVerify') }}" action="{{ route('front.verify.changeUsername') }}" method="POST">
                    @csrf
                    <div class="comment-item mb-3">
                        <input type="text" id="email-phone" name="mobile" class="form-control" value="{{ auth('web')->user()->username }}" placeholder=" ">
                        <label for="email-phone" class="form-label label-float">شماره موبایل خود را وارد نمایید</label>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="main-color-one-bg py-3 btn w-100 rounded-3 border-0">
                            ادامه
                            <i class="bi bi-box-arrow-in-left text-white"></i>
                        </button>
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
    <script src="{{ theme_asset('js/pages/change-username.js') }}"></script>
@endpush

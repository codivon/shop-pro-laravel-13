@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $redirect_url = request("redirect") ?: Redirect::intended()->getTargetUrl();
@endphp

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت خوش‌آمدگویی — ساختار register.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font">به {{ option('info_site_title', 'او پی شاپ') }} خوش آمدید</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="120" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                <div class="text-center mb-4">
                    <i class="bi bi-person-check main-color-one-color font-40"></i>
                    <h2 class="h5 fw-bold mt-3">حساب کاربری شما در {{ option('info_site_title', 'او پی شاپ') }} ساخته شد</h2>
                    <p class="text-muted font-13 mt-2">اکنون می‌توانید به صفحه‌ای که در آن بودید بازگردید و یا با تکمیل اطلاعات حساب کاربری
                        خود به کلیه امکانات و
                        سرویس‌های {{ option('info_site_title', 'او پی شاپ') }} و سرویس‌های وابسته به آن دسترسی داشته باشید</p>
                </div>

                <div class="form-group">
                    <a href="{{route('front.user.profile.edit')}}" class="d-block">
                        <button type="button" class="main-color-one-bg py-3 btn w-100 rounded-3 border-0">
                            تکمیل حساب کاربری
                            <i class="bi bi-person text-white"></i>
                        </button>
                    </a>
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('front.index') }}" class="main-color-one-color fw-bold font-14">بازگشت به صفحه‌ای اصلی</a>
                </div>

            </div>

            <p class="loginTermsDesc mt-3">با ورود و یا ثبت نام در {{ option('info_site_title', 'او پی شاپ') }} شما <a class="underlined main-color-one-color fw-bold" href="/rules/">شرایط و
                قوانین</a> استفاده از سرویس‌های سایت و <a class="underlined main-color-one-color fw-bold" href="/privacy-polices/">قوانین حریم
                خصوصی</a> آن را می‌پذیرید.</p>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ $redirect_url }}';
    </script>

@endpush

@php session()->forget('ShowPasswordForm_Mobile_Email'); @endphp

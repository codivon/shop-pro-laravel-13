@extends('front::auth.layouts.master', ['title' => 'تغییر رمز عبور'])

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت تغییر اجباری رمز عبور — ساختار register.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font">تغییر رمز عبور</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="120" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                <div class="alert text-center alert-warning font-13">
                    شما باید رمز عبور خود را تغییر دهید!
                </div>

                <form id="force-password-change-form" action="{{ route('front.user.force-update-password') }}" method="POST">
                    @csrf
                    <div class="comment-item mb-3">
                        <input type="password" name="password" id="password" class="form-control" placeholder=" ">
                        <label for="password" class="form-label label-float">رمز عبور جدید</label>
                    </div>

                    <div class="comment-item mb-3">
                        <input type="password" name="password_confirmation" id="password-new-again" class="form-control" placeholder=" ">
                        <label for="password-new-again" class="form-label label-float">تکرار رمز عبور جدید</label>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="main-color-one-bg py-3 btn w-100 rounded-3 border-0">
                            تغییر رمز عبور
                            <i class="bi bi-arrow-repeat text-white"></i>
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

@php
    $redirect_url = Redirect::intended()->getTargetUrl();
@endphp

@push('scripts')
    <script>
        var redirect_url = '{{ $redirect_url }}';
    </script>

    <script src="{{ theme_asset('js/pages/force-password-change.js') }}"></script>
@endpush

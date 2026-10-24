@extends('front::auth.layouts.master', ['title' => 'تغییر شماره موبایل'])

@section('content')
    <a href="{{ route('front.verify.showVerify') }}" class="auth-back-link">
        <i class="bi bi-arrow-right"></i> بازگشت به صفحه تایید
    </a>

    <div class="auth-form shadow-xl rounded-3 mt-2 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">تغییر شماره موبایل</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="alert alert-info" role="status">
            <i class="bi bi-phone"></i>
            شماره موبایل جدید خود را وارد کنید. پس از ثبت، کد تایید به شماره جدید ارسال می‌شود.
        </div>

        <form id="change-username-form" method="POST"
              action="{{ route('front.verify.changeUsername') }}"
              data-ajax
              data-redirect="{{ route('front.verify.showVerify') }}">
            @csrf

            <div class="comment-item mb-3">
                <input type="tel" inputmode="numeric" class="form-control" id="mobile" name="mobile"
                       placeholder=" " value="{{ auth('web')->user()->username }}" autofocus required
                       aria-label="شماره موبایل جدید">
                <label for="mobile" class="form-label label-float">شماره موبایل جدید</label>
            </div>

            <div class="form-group mt-4">
                <button type="submit"
                        class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    <i class="bi bi-check2"></i> ثبت و ارسال کد تایید
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/auth.js') }}?v=1"></script>
@endpush

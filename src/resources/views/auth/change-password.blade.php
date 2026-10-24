@extends('front::auth.layouts.master', ['title' => 'تعیین رمز عبور جدید'])

@php
    $back_url = route('password.request');
@endphp

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">تعیین رمز عبور جدید</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <div class="alert alert-info" role="status">
            <i class="bi bi-phone"></i>
            کد تایید به شماره
            <span class="auth-number-chip">{{ $user->username }}</span>
            ارسال شد. کد و رمز جدید را وارد کنید.
        </div>

        <a href="{{ $back_url }}" class="auth-back-link">
            <i class="bi bi-arrow-right"></i> ویرایش شماره موبایل
        </a>

        @if ($errors->any())
            <div class="auth-form-errors" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>{{ $errors->all()[0] }}</span>
            </div>
        @endif

        <form id="change-password-form" method="POST"
              action="{{ route('password.change-password') }}">
            @csrf
            <input name="mobile" type="hidden" value="{{ $user->username }}">

            <label class="form-label fw-bold font-13 mb-1" for="otp-input">کد تایید ۵ رقمی</label>
            @include('front::auth.partials.otp', ['resend_action' => $back_url])

            <div class="comment-item position-relative mb-3 mt-4">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder=" " minlength="8" required autocomplete="new-password"
                       aria-label="رمز عبور جدید">
                <label for="password" class="form-label label-float">رمز عبور جدید (حداقل ۸ کاراکتر)</label>
                <button type="button" class="toggle-password" aria-label="نمایش رمز عبور">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>

            <div class="comment-item mb-3">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                       placeholder=" " minlength="8" required autocomplete="new-password"
                       aria-label="تکرار رمز عبور جدید">
                <label for="password_confirmation" class="form-label label-float">تکرار رمز عبور جدید</label>
            </div>

            <div class="form-group mt-4">
                <button type="submit"
                        class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    <i class="bi bi-shield-lock"></i> تغییر رمز و ورود
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/auth.js') }}?v=1"></script>
@endpush

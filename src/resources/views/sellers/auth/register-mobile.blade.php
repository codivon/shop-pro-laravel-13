{{-- تایید موبایل فروشنده (فاز G) — POST: seller.registration_mobile_check → JSON {status, redirect}
    قرارداد SellerController@registration_mobile : $seller, $resend_time --}}
@extends('front::sellers.auth.layouts.master', ['title' => 'تایید شماره موبایل', 'auth_step' => 1])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-0 bg-white seller-auth-card">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">تایید شماره موبایل</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <p class="font-14 text-center text-muted mb-1">
            کد تایید ۵ رقمی به شماره
            <span class="fw-bold text-dark">{{ $seller->mobile }}</span>
            ارسال شد.
        </p>
        <p class="text-center mb-4">
            <a href="{{ route('seller.registration') }}" class="font-13 main-color-one-color">
                <i class="bi bi-pencil-square ms-1"></i>ویرایش شماره موبایل
            </a>
        </p>

        <form action="{{ route('seller.registration_mobile_check') }}" method="post"
              data-ajax data-otp-autosubmit novalidate>
            @csrf
            <input type="hidden" name="mobile" value="{{ $seller->mobile }}">

            {{-- پنج خانه OTP + شمارش معکوس (پارشال مشترک فاز E) --}}
            @include('front::auth.partials.otp', [
                'resend_time' => $resend_time,
                'resend_action' => route('seller.get-new-code-for-seller'),
            ])

            <div class="auth-form-errors is-empty" role="alert"></div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    تایید و ادامه
                    <i class="bi bi-arrow-left ms-1"></i>
                </button>
            </div>
        </form>
    </div>
@endsection

@php
    $title = 'صفحه مورد نظر یافت نشد';
@endphp
@extends('front::layouts.master')

@push('meta')
    <meta name="robots" content="noindex, follow">
    <meta name="description" content="صفحه مورد نظر یافت نشد — به نظر می‌رسد این صفحه حذف شده یا آدرس آن تغییر کرده است.">
@endpush

@section('content')
    <div class="py-20 my-5">
        <div class="container-fluid">
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-6 text-center">
                    <img src="{{ theme_asset('image/404-Error.svg') }}" alt="صفحه ۴۰۴ - یافت نشد" class="img-fluid mb-4">
                    <h2 class="fw-bold mb-3">صفحه مورد نظر یافت نشد!</h2>
                    <p class="text-muted mb-4">
                        به نظر می‌رسد این صفحه حذف شده یا آدرس آن تغییر کرده است.
                        نگران نباشید، از دکمه‌های زیر استفاده کنید.
                    </p>
                    <a href="{{ route('front.index') }}" class="btn main-color-one-bg text-white border-0 rounded-pill px-4">
                        <i class="bi bi-house me-1"></i> بازگشت به صفحه اصلی
                    </a>
                    <a href="{{ route('front.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4 ms-2">
                        <i class="bi bi-basket me-1"></i> مشاهده محصولات
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

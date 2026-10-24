{{-- نتیجه پرداخت سفارش — قرارداد کنترلر: $order (App\Models\Order)
    موفق: status=='paid' ؛ ناموفق: session('transaction-error') یا status=='unpaid' ؛ خطا: session('error')
--}}
@extends('front::layouts.master', ['title' => 'نتیجه پرداخت سفارش'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/cart.css') }}">
@endpush

@section('content')
    {{-- بردکرامب --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('front.cart') }}" class="font-14 text-muted">سبد خرید</a></li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">نتیجه پرداخت</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            @php
                $isSuccess = $order->status == 'paid';
                $isPending = !$isSuccess && (session('transaction-error') || $order->status == 'unpaid');
                $isError   = !$isSuccess && !$isPending;
            @endphp

            @if($isSuccess)
                @include('front::carts.partials.steps', ['step' => 3, 'done' => 2])
            @else
                @include('front::carts.partials.steps', ['step' => 3, 'done' => 1])
            @endif

            @if($isSuccess)
                {{-- ================= موفق ================= --}}
                <div class="payment-status">
                    <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="title">
                        <h3>پرداخت شما با موفقیت انجام شد</h3>
                        <p>از خرید شما متشکریم :) سفارش {{ $order->id }} ثبت و در حال پردازش است.</p>
                    </div>

                    <div class="pay-table">
                        <div class="pay-table-title"><h5 class="font-18">مشخصات پرداختی</h5></div>
                        <div class="pay-table-item">
                            <h6 class="font-16">شماره سفارش</h6>
                            <p class="mb-0">{{ $order->id }}</p>
                        </div>
                        <div class="pay-table-item">
                            <h6 class="font-16">تاریخ</h6>
                            <p class="mb-0">{{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') }}</p>
                        </div>
                        @php $totalQuantity = $order->items->sum('quantity'); @endphp
                        <div class="pay-table-item">
                            <h6 class="font-16">تعداد کالا</h6>
                            <p class="mb-0">{{ $totalQuantity }} عدد</p>
                        </div>
                        @if($order->discount_amount > 0)
                            <div class="pay-table-item">
                                <h6 class="font-16 text-danger">مقدار تخفیف</h6>
                                <p class="mb-0 text-danger">{{ number_format($order->discount_amount) }} تومان</p>
                            </div>
                        @endif
                        <div class="pay-table-item">
                            <h6 class="font-16 main-color-three-color">مقدار پرداختی</h6>
                            <p class="mb-0 main-color-three-color">{{ number_format($order->price) }} تومان</p>
                        </div>
                    </div>

                    <div class="pay-table">
                        <div class="pay-table-title"><h5 class="font-18">وضعیت سفارش</h5></div>
                        <div class="pay-table-item">
                            <h6 class="font-16">گیرنده</h6>
                            <p class="mb-0">{{ $order->name }}</p>
                        </div>
                        <div class="pay-table-item">
                            <h6 class="font-16">شماره همراه</h6>
                            <p class="mb-0" dir="ltr">{{ $order->mobile }}</p>
                        </div>
                        <div class="pay-table-item">
                            <h6 class="font-16">وضعیت پرداخت</h6>
                            <p class="mb-0 main-color-three-color">پرداخت شده</p>
                        </div>
                        <div class="pay-table-item">
                            <h6 class="font-16">وضعیت سفارش</h6>
                            <p class="mb-0">در حال پردازش</p>
                        </div>
                    </div>
                </div>

                <div class="text-center my-4 d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('front.orders.show', $order) }}" class="btn d-inline-block main-color-three-bg border-0 rounded-3 px-4">پیگیری سفارش</a>
                    <a href="{{ route('front.orders.print', $order) }}" target="_blank" rel="noopener" class="btn d-inline-block border-0 main-color-one-bg rounded-3 px-4">
                        <i class="bi bi-printer"></i> چاپ فاکتور
                    </a>
                    <a href="{{ route('front.index') }}" class="btn d-inline-block btn-light rounded-3 px-4">بازگشت به فروشگاه</a>
                </div>

            @elseif($isPending)
                {{-- ================= ناموفق / در انتظار پرداخت ================= --}}
                <div class="payment-status fail-pay">
                    <div class="icon"><i class="bi bi-emoji-frown-fill"></i></div>
                    <div class="title">
                        <h3>پرداخت شما ناموفق بود</h3>
                        <p>سفارش {{ $order->id }} در سیستم ثبت شد اما پرداخت تکمیل نشد؛ می‌توانید دوباره تلاش کنید.</p>
                    </div>

                    <div class="pay-table">
                        <div class="pay-table-title"><h5 class="font-18">وضعیت سفارش</h5></div>
                        <div class="pay-table-item">
                            <h6 class="font-16">شماره سفارش</h6>
                            <p class="mb-0">{{ $order->id }}</p>
                        </div>
                        <div class="pay-table-item">
                            <h6 class="font-16">تاریخ ثبت</h6>
                            <p class="mb-0">{{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') }}</p>
                        </div>
                        <div class="pay-table-item">
                            <h6 class="font-16">قیمت کل</h6>
                            <p class="mb-0">{{ number_format($order->price) }} تومان</p>
                        </div>
                        <div class="pay-table-item">
                            <h6 class="font-16">وضعیت پرداخت</h6>
                            <p class="mb-0 text-danger">پرداخت نشده</p>
                        </div>
                    </div>
                </div>

                <div class="text-center my-4 d-flex flex-wrap justify-content-center gap-2">
                    @if($order->status == 'unpaid')
                        <a href="{{ route('front.orders.pay', $order) }}" class="btn d-inline-block main-color-one-bg border-0 rounded-3 px-4">پرداخت مجدد</a>
                    @else
                        <a href="{{ route('front.cart') }}" class="btn d-inline-block main-color-one-bg border-0 rounded-3 px-4">ثبت سفارش مجدد</a>
                    @endif
                    <a href="{{ route('front.orders.show', $order) }}" class="btn d-inline-block btn-light rounded-3 px-4">پیگیری سفارش</a>
                </div>

                <div class="content-box mb-4">
                    <div class="container-fluid py-3">
                        <p class="font-14 mb-0 text-muted">
                            <i class="bi bi-info-circle main-color-one-color"></i>
                            سفارش شما تا زمان پرداخت رزرو نمی‌شود؛ در صورت اتمام موجودی، مبلغ پرداختی به‌طور کامل بازگشت داده می‌شود.
                        </p>
                    </div>
                </div>

            @else
                {{-- ================= خطا / لغو ================= --}}
                <div class="payment-status fail-pay">
                    <div class="icon"><i class="bi bi-x-circle-fill"></i></div>
                    <div class="title">
                        <h3>ثبت سفارش با خطا مواجه شد</h3>
                        <p>{{ session('error', 'متأسفانه فرآیند سفارش تکمیل نشد؛ در صورت کسر مبلغ، تا ۷۲ ساعت آینده بازگشت داده می‌شود.') }}</p>
                    </div>
                </div>

                <div class="text-center my-4 d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('front.cart') }}" class="btn d-inline-block main-color-one-bg border-0 rounded-3 px-4">بازگشت به سبد خرید</a>
                    <a href="{{ route('front.index') }}" class="btn d-inline-block btn-light rounded-3 px-4">صفحه اصلی</a>
                </div>
            @endif
        </div>
    </div>
@endsection

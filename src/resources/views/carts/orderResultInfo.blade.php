{{-- فاز ۱۷۱ — نتیجهٔ پرداخت هم روی لایهٔ سادهٔ چک‌اوت (بدون هدر/فوتر اصلی) --}}
@extends('front::layouts.checkout')

@section('checkout-back-url', route('front.index'))

@section('content')
    <!-- Start main-content -->
    <main class="main-content">

        <!-- start breadcroumb -->
        <div class="bread-crumb pt-3">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid">
                        <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('front.index') }}"
                                        class="font-14 text-muted">خانه</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('front.cart') }}"
                                        class="font-14 text-muted">سبد خرید</a></li>
                                <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">
                                    نتیجه پرداخت</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end breadcroumb -->

        <!-- start content -->
        <div class="content">
            {{-- فاز ۱۷۱ — نوار مراحل حذف شد --}}
            <div class="container-fluid">

                @php
                    $quantity = [];
                    foreach ($order->items as $item) {
                        $quantity[] = $item->quantity;
                    }
                @endphp

                @if(session('message') == 'ok' or $order->status == 'paid')
                    {{-- پرداخت موفق — مطابق success-payment.html --}}
                    <div class="payment-status">
                        <div class="icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="title">
                            <h3>پرداخت شما با موفقیت انجام شد</h3>
                            <p>سفارش {{ $order->id }} با موفقیت پرداخت و در سیستم ثبت شد.</p>
                        </div>

                        <div class="pay-table">
                            <div class="pay-table-title">
                                <h5 class="font-18">وضعیت سفارش</h5>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">شماره سفارش</h6>
                                <p class="mb-0">{{ $order->id }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">نام تحویل گیرنده</h6>
                                <p class="mb-0">{{ $order->name }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">شماره تماس</h6>
                                <p class="mb-0">{{ $order->mobile }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">تعداد مرسوله</h6>
                                <p class="mb-0">{{ array_sum($quantity) }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16 main-color-three-color">مبلغ کل</h6>
                                <p class="mb-0 main-color-three-color">{{ number_format($order->price) }} تومان</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16 main-color-three-color">وضعیت پرداخت</h6>
                                <p class="mb-0 main-color-three-color">پرداخت شده</p>
                            </div>
                        </div>

                        <div class="pay-table">
                            <div class="pay-table-title">
                                <h5 class="font-18">جزییات ارسال</h5>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">وضعیت سفارش</h6>
                                <p class="mb-0 main-color-three-color">
                                    @if($order->shipping_status=="w-pending")
                                        در انتظار بررسی
                                    @elseif ($order->shipping_status=="pending")
                                        در حال بررسی
                                    @elseif ($order->shipping_status=="waiting")
                                        منتظر ارسال
                                    @elseif ($order->shipping_status=="sent")
                                        ارسال شده
                                    @elseif ($order->shipping_status=="canceled")
                                        ارسال لغو شده است
                                    @endif
                                </p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">آدرس</h6>
                                <p class="mb-0">{{ $order->address }}</p>
                            </div>
                        </div>

                        <p class="font-14 text-muted mt-3 mb-0">
                            پرداخت با موفقیت انجام شد. سفارش شما با موفقیت ثبت شد و در زمان تعیین شده برای شما ارسال
                            خواهد شد.
                            <br>
                            سفارش شما هم اکنون
                            <span class="main-color-one-color">در حال پردازش</span>
                            است. جزئیات این سفارش را می توانید با کلیک بر روی دکمه زیر مشاهده نمایید.
                        </p>
                    </div>
                @elseif(session('transaction-error') or $order->status == 'unpaid')
                    {{-- پرداخت ناموفق — مطابق fail-payment.html --}}
                    <div class="payment-status fail-pay">
                        <div class="icon">
                            <i class="bi bi-emoji-frown-fill"></i>
                        </div>
                        <div class="title">
                            <h3>پرداخت شما ناموفق بود</h3>
                            <p>سفارش {{ $order->id }} در سیستم ثبت شد اما پرداخت ناموفق بود</p>
                        </div>

                        <div class="pay-table">
                            <div class="pay-table-title">
                                <h5 class="font-18">وضعیت سفارش</h5>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">شماره سفارش</h6>
                                <p class="mb-0">{{ $order->id }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">نام تحویل گیرنده</h6>
                                <p class="mb-0">{{ $order->name }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">شماره تماس</h6>
                                <p class="mb-0">{{ $order->mobile }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">تعداد مرسوله</h6>
                                <p class="mb-0">{{ array_sum($quantity) }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">مبلغ کل</h6>
                                <p class="mb-0">{{ number_format($order->price) }} تومان</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16 text-danger">وضعیت پرداخت</h6>
                                <p class="mb-0 text-danger">پرداخت نشده</p>
                            </div>
                        </div>

                        <div class="pay-table">
                            <div class="pay-table-title">
                                <h5 class="font-18">جزییات ارسال</h5>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">وضعیت سفارش</h6>
                                <p class="mb-0">
                                    @if($order->shipping_status=="w-pending")
                                        در انتظار بررسی
                                    @elseif ($order->shipping_status=="pending")
                                        در حال بررسی
                                    @elseif ($order->shipping_status=="waiting")
                                        منتظر ارسال
                                    @elseif ($order->shipping_status=="sent")
                                        ارسال شده
                                    @elseif ($order->shipping_status=="canceled")
                                        ارسال لغو شده است
                                    @endif
                                </p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">آدرس</h6>
                                <p class="mb-0">{{ $order->address }}</p>
                            </div>
                        </div>

                        <p class="font-14 text-muted mt-3 mb-0">
                            <span class="text-warning d-block mb-1">برای جلوگیری از لغو سیستمی سفارش، تا 24 ساعت آینده
                                پرداخت را انجام دهید.</span>
                            چنانچه طی این فرایند مبلغی از حساب شما کسر شده است، طی 72 ساعت آینده به حساب شما باز خواهد
                            گشت.
                        </p>
                    </div>
                @elseif(session('error'))
                    <div class="alert alert-danger mt-4 text-center" role="alert">
                        <strong>{{ session('error') }}</strong>
                    </div>

                    <div class="payment-status fail-pay">
                        <div class="icon">
                            <i class="bi bi-emoji-frown-fill"></i>
                        </div>
                        <div class="title">
                            <h3>پرداخت شما ناموفق بود</h3>
                            <p>سفارش {{ $order->id }} در سیستم ثبت نشد</p>
                        </div>
                    </div>
                @else
                    {{-- سایر وضعیت‌ها (لغو شده) --}}
                    <div class="payment-status fail-pay">
                        <div class="icon">
                            <i class="bi bi-emoji-frown-fill"></i>
                        </div>
                        <div class="title">
                            <h3>این سفارش لغو شده است</h3>
                            <p>سفارش {{ $order->id }} لغو شده است</p>
                        </div>

                        <div class="pay-table">
                            <div class="pay-table-title">
                                <h5 class="font-18">وضعیت سفارش</h5>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">شماره سفارش</h6>
                                <p class="mb-0">{{ $order->id }}</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16">مبلغ کل</h6>
                                <p class="mb-0">{{ number_format($order->price) }} تومان</p>
                            </div>
                            <div class="pay-table-item">
                                <h6 class="font-16 text-danger">وضعیت پرداخت</h6>
                                <p class="mb-0 text-danger">لغو شده</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="text-center my-4">
                    <a href="{{ route('front.orders.show', $order) }}"
                        class="btn d-inline-block main-color-one-bg border-0 rounded-3 mx-auto">
                        پیگیری سفارش
                        <i class="bi bi-bag-check ms-1"></i>
                    </a>
                </div>

            </div>
        </div>
        <!-- end content -->
    </main>
    <!-- End main-content -->
@endsection

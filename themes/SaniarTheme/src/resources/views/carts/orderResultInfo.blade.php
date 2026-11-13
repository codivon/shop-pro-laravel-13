@extends('front::carts.layout')
@push('style')
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css.map')}}">
@endpush

@section('cart-header')
    <li class="is-completed is-completed-active">
        <a href="{{route('front.checkout')}}" class="checkout-steps-item-link active-link-shopping">
            <span>اطلاعات ارسال و پرداخت</span>
        </a>
    </li>
    <li class="is-active ">
        <a class="checkout-steps-item-link active-link-shopping">
            <span>اتمام خرید و ارسال</span>
        </a>
    </li>
@endsection

@section('content')

    <div class="main-shopping">
        <div class="col-12 text-center">
            <div class="complate-page-container">


                @if(session('message') == 'ok' or $order->status == 'paid')
                <div class="success-checkout">
                    <div class="container">
                        <div class="icon-success">
                            <span class="fa fa-check"></span>
                        </div>
                        <div class="order-success">
                            سفارش
                            <a class="order-code">{{ $order->id }}</a>
                            با موفقیت پرداخت و در سیستم ثبت شد.
                            <span class="order-ready-post">پرداخت با موفقیت انجام شد. سفارش شما با موفقیت ثبت شد و در
                                زمان تعیین شده برای شما ارسال خواهد شد.
                                <br>
                                از اینکه دیجی استور را برای خرید انتخاب کردید از شما سپاسگزاریم.
                            </span>
                        </div>
                    </div>
                </div>
                @elseif(session('transaction-error') or $order->status == 'unpaid')
                    <div class="success-checkout">
                        <div class="container">
                            <div class="icon-success warning">
                                <span class="fa fa-close"></span>
                            </div>
                            <div class="order-success">
                                سفارش
                                <a class="order-code">{{ $order->id }}</a>
                                در سیستم ثبت شد اما پرداخت ناموفق بود

                                <span class="text-warning">برای جلوگیری از لغو سیستمی سفارش،تا 24 ساعت آینده پرداخت را انجام
                                دهید.</span>

                                <span class="order-ready-post">چنانچه طی این فرایند مبلغی از حساب شما کسر شده است،طی 72 ساعت
                                آینده به حساب شما باز خواهد گشت.</span>
                            </div>
                        </div>
                    </div>
                @elseif(session('error'))
                    <div class="col-lg-12 text-center">
                        <div class="alert alert-danger mt-4" role="alert">
                            <strong>{{ session('error') }}</strong>.
                        </div>
                    </div>
                @endif


                <div class="checkout-order-info">
                    <div class="order-info">
                        <div class="order-code">
                            کد سفارش :
                            <span>{{ $order->id }}</span>
                        </div>
                        @if($order->status == 'paid')
                        <div class="checkout-process-order-info">
                            سفارش شما با موفقیت در سیستم ثبت شد و هم اکنون
                            <a class="processing">در حال پردازش</a>
                            است.جزئیات این سفارش را می توانید
                            با کلیک بر روی دکمه
                            <a href="{{route('front.orders.show',$order)}}" class="link-border">پیگیری سفارش</a>
                            مشاهده نمایید.
                        </div>
                        @elseif($order->status == 'unpaid')
                            <div class="checkout-process-order-info">
                                سفارش شما ثبت شد اما پرداخت ناموفق بود

                                .جزئیات این سفارش را می توانید
                                با کلیک بر روی دکمه
                                <a href="{{route('front.orders.show',$order)}}" class="link-border">پیگیری سفارش</a>
                                مشاهده نمایید.
                            </div>
                        @endif
                        <div class="parent-btn btn-following-order">
                            <a style="width: 260px" href="{{route('front.orders.show',$order)}}" class="dk-btn dk-btn-info">
                                پیگیری سفارش
                                <i class="fa fa-shopping-bag sign-in"></i>
                            </a>
                        </div>
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th scope="col">نام تحویل گیرنده: {{$order->name}}</th>
                                <th scope="col"> شماره تماس :
                                    {{$order->mobile}}
                                </th>
                            </tr>
                            </thead>
                            <?php
                            $quantity=[];
                            foreach ($order->items as $item){
                                $quantity[]=$item->quantity;
                            }
                            ?>

                            <tbody>
                            <tr>
                                <td>تعداد مرسوله :
                                {{array_sum($quantity)}}
                                </td>
                                <td>مبلغ کل :
                                    {{ number_format($order->price) }} تومان
                                </td>
                            </tr>
                            <tr>
                                <td>وضعیت پرداخت :
                                    @if($order->status == 'paid')
                                        <div class="text-body1-strong text-body2-strong-lg color-green d-contents">پرداخت شده</div>
                                    @elseif($order->status == 'unpaid')
                                        <div class="text-body1-strong text-body2-strong-lg color-red d-contents">پرداخت نشده ( انتخاب درگاه
                                            پرداخت )
                                        </div>
                                    @else
                                        <div class="text-body1-strong text-body2-strong-lg color-red d-contents">لغو شده</div>
                                    @endif
                                </td>
                                <td>وضعیت سفارش :
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
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">آدرس : {{$order->address}}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{theme_asset('js/theia-sticky-sidebar.min.js')}}"></script>
    <script src="{{ theme_asset('js/pages/cart.js') }}?v=3"></script>
@endpush

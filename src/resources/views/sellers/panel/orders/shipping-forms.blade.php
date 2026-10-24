{{-- چاپ گروهی برچسب‌های مرسوله — سانیار فاز I
    قرارداد OrderSellerController@printAllShippingForms : $orders (پرداخت‌شدهٔ همین فروشنده)
    فقط سفارش‌های دارای محصول فیزیکی برچسب ارسال دارند --}}
@extends('front::layouts.printable', ['print-title' => 'برچسب‌های مرسوله'])

@section('print-content')
    @forelse($orders->filter(fn($order) => $order->hasPhysicalProduct()) as $order)
        @include('front::sellers.panel.orders.partials.shipping-form', ['order' => $order])
    @empty
        <div class="alert alert-warning font-14 m-2">مرسولهٔ فیزیکیِ پرداخت‌شده‌ای برای چاپ انتخاب نشده است.</div>
    @endforelse
@endsection

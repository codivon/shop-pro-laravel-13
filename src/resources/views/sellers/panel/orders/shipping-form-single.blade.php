{{-- برچسب تک‌مرسوله — سانیار فاز I | قرارداد OrderSellerController@shippingForm : $order --}}
@extends('front::layouts.printable', ['print-title' => 'برچسب مرسوله #' . $order->id])

@section('print-content')
    @if($order->hasPhysicalProduct())
        @include('front::sellers.panel.orders.partials.shipping-form', ['order' => $order])
    @else
        <div class="alert alert-info font-14 m-2">این سفارش محصول فیزیکی ندارد و برچسب ارسال لازم نیست.</div>
    @endif
@endsection

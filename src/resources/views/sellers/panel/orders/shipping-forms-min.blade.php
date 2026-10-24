{{-- چاپ گروهی لیبل‌های پستی کوچک (۱۰×۷) — سانیار فاز I
    قرارداد OrderSellerController@printAllShippingFormsMin : $orders --}}
@extends('front::layouts.printable', ['print-title' => 'لیبل‌های پستی'])

@section('print-content')
    @forelse($orders->filter(fn($order) => $order->hasPhysicalProduct()) as $order)
        @include('front::sellers.panel.orders.partials.shipping-form-min', ['order' => $order])
    @empty
        <div class="alert alert-warning font-14 m-2">مرسولهٔ فیزیکیِ پرداخت‌شده‌ای برای چاپ انتخاب نشده است.</div>
    @endforelse
@endsection

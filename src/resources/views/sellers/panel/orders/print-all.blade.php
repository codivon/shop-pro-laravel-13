{{-- چاپ گروهی فاکتورها — سانیار فاز I
    قرارداد OrderSellerController@printAll : $orders (فقط سفارش‌های پرداخت‌شدهٔ همین فروشنده)
    برخلاف WeblakShop سفارش‌های دانلودی هم فاکتور دارند --}}
@extends('front::layouts.printable', ['print-title' => 'فاکتورهای گروهی'])

@section('print-content')
    @forelse($orders as $order)
        @include('front::sellers.panel.orders.partials.factor', ['order' => $order])
    @empty
        <div class="alert alert-warning font-14 m-2">سفارشِ پرداخت‌شده‌ای برای چاپ انتخاب نشده است.</div>
    @endforelse
@endsection

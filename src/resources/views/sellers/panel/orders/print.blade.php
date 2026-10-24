{{-- چاپ فاکتور تک‌سفارش — سانیار فاز I (لایه چاپ مستقل تم) --}}
@extends('front::layouts.printable', ['print-title' => 'فاکتور سفارش #' . $order->id])

@section('print-content')
    @include('front::sellers.panel.orders.partials.factor', ['order' => $order])
@endsection

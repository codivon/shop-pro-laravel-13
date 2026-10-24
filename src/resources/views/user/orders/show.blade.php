{{-- پیگیری سفارش — قرارداد OrderController@show : $order, $gateways, $wallet, $active --}}
@extends('front::user.layouts.master', ['title' => 'سفارش ' . $order->id])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/checkout.css') }}">
@endpush

@section('user-content')
    @php
        $isPaid = $order->status == 'paid';
        $totalQuantity = $order->items->sum('quantity');
    @endphp

    {{-- خطای تراکنش ناموفق (از مسیر پرداخت) --}}
    @if(session('transaction-error'))
        <div class="alert alert-danger rounded-3 font-14" role="alert">
            <i class="bi bi-x-circle"></i> {{ session('transaction-error') }}
        </div>
    @endif

    {{-- هدر سفارش --}}
    <div class="content-box mb-4">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pb-3 mb-3">
                <h5 class="font-16 mb-0">
                    <i class="bi bi-box-seam main-color-one-color ms-1"></i>
                    سفارش شماره {{ $order->id }}
                </h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($isPaid)
                        <span class="badge main-color-three-bg rounded-pill px-3 py-2">پرداخت شده</span>
                    @elseif($order->status == 'unpaid')
                        <span class="badge danger-label rounded-pill px-3 py-2">پرداخت نشده</span>
                    @else
                        <span class="badge bg-secondary rounded-pill px-3 py-2">لغو شده</span>
                    @endif
                    <span class="font-12 text-muted">
                        {{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('l j F Y — H:i') }}
                    </span>
                </div>
            </div>

            <div class="row g-3 font-14">
                <div class="col-md-3 col-6">
                    <p class="text-muted mb-1">مبلغ کل</p>
                    <p class="fw-bold mb-0">{{ number_format($order->price) }} تومان</p>
                </div>
                <div class="col-md-3 col-6">
                    <p class="text-muted mb-1">تعداد کالا</p>
                    <p class="fw-bold mb-0">{{ $totalQuantity }} عدد</p>
                </div>
                @if($order->discount_amount > 0)
                    <div class="col-md-3 col-6">
                        <p class="text-muted mb-1">تخفیف</p>
                        <p class="fw-bold mb-0 text-danger">{{ number_format($order->discount_amount) }} تومان</p>
                    </div>
                @endif
                @if($order->shipping_cost > 0)
                    <div class="col-md-3 col-6">
                        <p class="text-muted mb-1">هزینه ارسال</p>
                        <p class="fw-bold mb-0">{{ number_format($order->shipping_cost) }} تومان</p>
                    </div>
                @endif
            </div>

            @if($isPaid)
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <a href="{{ route('front.orders.print', $order) }}" target="_blank" rel="noopener"
                       class="btn btn-sm border-0 main-color-one-bg rounded-3 px-3">
                        <i class="bi bi-printer"></i> چاپ فاکتور
                    </a>
                    <a href="{{ route('front.products.index') }}" class="btn btn-sm btn-light rounded-3 px-3">خرید مجدد</a>
                </div>
            @endif
        </div>
    </div>

    {{-- پرداخت سفارش پرداخت‌نشده --}}
    @if($order->status == 'unpaid')
        <div class="content-box mb-4">
            <div class="container-fluid">
                <h5 class="font-16 mb-3"><i class="bi bi-credit-card-2-front-fill main-color-one-color ms-1"></i>پرداخت سفارش</h5>
                <p class="font-12 text-muted">روش پرداخت را انتخاب کنید:</p>

                <div class="row g-2" id="order-pay-gateways">
                    @if($wallet && $wallet->balance >= $order->price)
                        <div class="col-md-4">
                            <label class="bank-item w-100 h-100">
                                <i class="bi bi-wallet2 main-color-one-color font-25"></i>
                                <span class="d-flex flex-column mx-2">
                                    <span class="font-14">کیف پول</span>
                                    <span class="font-12 text-muted">موجودی: {{ number_format($wallet->balance) }} تومان</span>
                                </span>
                                <input type="radio" class="d-none pay-gateway-radio" name="pay-gateway" value="wallet" data-pay-url="{{ route('front.orders.pay', $order) }}?gateway=wallet">
                            </label>
                        </div>
                    @endif
                    @foreach($gateways as $gateway)
                        <div class="col-md-4">
                            <label class="bank-item w-100 h-100 {{ $loop->first ? 'active' : '' }}">
                                <i class="bi bi-credit-card-2-back main-color-one-color font-25"></i>
                                <span class="d-flex flex-column mx-2">
                                    <span class="font-14">پرداخت اینترنتی {{ $gateway->name }}</span>
                                </span>
                                <input type="radio" class="d-none pay-gateway-radio" name="pay-gateway" value="{{ $gateway->key }}"
                                       data-pay-url="{{ route('front.orders.pay', $order) }}?gateway={{ $gateway->key }}"
                                       {{ $loop->first ? 'checked' : '' }}>
                            </label>
                        </div>
                    @endforeach
                </div>

                <button type="button" id="order-pay-btn" class="btn border-0 main-color-two-bg rounded-3 px-4 mt-3"
                        data-base-url="{{ route('front.orders.pay', $order) }}">
                    <i class="bi bi-shield-check"></i> پرداخت {{ number_format($order->price) }} تومان
                </button>
            </div>
        </div>
    @endif

    {{-- اقلام سفارش --}}
    <div class="content-box mb-4">
        <div class="container-fluid">
            <h5 class="font-16 mb-3 border-bottom pb-2"><i class="bi bi-basket main-color-one-color ms-1"></i>کالاهای سفارش</h5>

            @foreach($order->items as $item)
                <div class="d-flex align-items-start border-bottom py-3">
                    <div class="flex-grow-1">
                        <a href="{{ $item->product ? route('front.products.show', ['product' => $item->product]) : '#' }}" class="text-dark text-decoration-none">
                            <h6 class="font-14 mb-1">{{ $item->title }}</h6>
                        </a>
                        <div class="d-flex align-items-center flex-wrap font-12 text-muted">
                            <span class="ms-3">تعداد: {{ $item->quantity }}</span>
                            @if($item->real_price != $item->price)
                                <span class="ms-3">واحد: {{ number_format($item->price) }} تومان <del class="ms-1">{{ number_format($item->real_price) }}</del></span>
                            @else
                                <span class="ms-3">واحد: {{ number_format($item->price) }} تومان</span>
                            @endif
                            @if($item->carrier)
                                <span class="ms-3"><i class="bi bi-truck"></i> {{ $item->carrier->title }}</span>
                            @endif
                            @if($item->delivery_date)
                                <span class="ms-3"><i class="bi bi-calendar-check"></i>
                                    {{ \Morilog\Jalali\Jalalian::fromDateTime($item->delivery_date)->format('Y/m/d') }}
                                </span>
                            @endif
                            @if($item->product_type == 'download' || $item->is_downloadable)
                                <span class="ms-3 badge main-color-one-bg text-white rounded-pill">دانلودی</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <p class="font-14 fw-bold mb-0">{{ number_format($item->price * $item->quantity) }} تومان</p>
                    </div>
                </div>
            @endforeach

            <div class="d-flex justify-content-end pt-3">
                <table class="table table-sm w-50 font-14 mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted text-start">جمع کالاها</td>
                            <td class="text-end fw-bold">{{ number_format($order->items->sum(fn($i) => $i->real_price * $i->quantity)) }} تومان</td>
                        </tr>
                        @if($order->discount_amount > 0)
                            <tr>
                                <td class="text-muted text-start">تخفیف</td>
                                <td class="text-end text-danger">{{ number_format($order->discount_amount) }} تومان</td>
                            </tr>
                        @endif
                        @if($order->shipping_cost > 0)
                            <tr>
                                <td class="text-muted text-start">هزینه ارسال</td>
                                <td class="text-end">{{ number_format($order->shipping_cost) }} تومان</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="fw-bold text-start">مبلغ قابل پرداخت</td>
                            <td class="text-end fw-bold main-color-one-color">{{ number_format($order->price) }} تومان</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- اطلاعات ارسال --}}
    @if($order->address)
        <div class="content-box mb-4">
            <div class="container-fluid">
                <h5 class="font-16 mb-3 border-bottom pb-2"><i class="bi bi-geo-alt-fill main-color-one-color ms-1"></i>اطلاعات ارسال</h5>
                <div class="row g-3 font-14">
                    <div class="col-md-4 col-6">
                        <p class="text-muted mb-1">گیرنده</p>
                        <p class="fw-bold mb-0">{{ $order->name }}</p>
                    </div>
                    <div class="col-md-4 col-6">
                        <p class="text-muted mb-1">شماره همراه</p>
                        <p class="fw-bold mb-0" dir="ltr">{{ $order->mobile }}</p>
                    </div>
                    <div class="col-md-4 col-6">
                        <p class="text-muted mb-1">کد پستی</p>
                        <p class="fw-bold mb-0" dir="ltr">{{ $order->postal_code ?? '—' }}</p>
                    </div>
                    <div class="col-12">
                        <p class="text-muted mb-1">نشانی</p>
                        <p class="mb-0">
                            {{ $order->province ? $order->province->name . '، ' : '' }}{{ $order->city ? $order->city->name . '، ' : '' }}{{ $order->address }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/carts/order-show.js') }}"></script>
@endpush

{{-- جزئیات تراکنش کیف پول — سانیار فاز I
    قرارداد WalletSellerController@show : $history (WalletHistory) --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'جزئیات تراکنش #' . $history->id, 'active' => 'wallet'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-receipt-coupon main-color-one-color ms-1"></i>جزئیات تراکنش #{{ $history->id }}</h5>
                    <p class="font-13 text-muted mb-0">{{ jdate($history->created_at)->format('Y/m/d - H:i') }}</p>
                </div>
                <a href="{{ route('seller.wallet.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                    <i class="bi bi-arrow-right ms-1"></i>بازگشت به کیف پول
                </a>
            </div>
        </div>
    </div>

    <div class="content-box">
        <div class="container-fluid">
            <div class="table-responsive py-2">
                <table class="table mb-2 saniar-wallet-detail">
                    <tbody class="font-13">
                    <tr>
                        <th scope="row" class="text-muted" style="min-width:180px;">شناسه تراکنش</th>
                        <td class="fw-bold">#{{ $history->id }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">مبلغ</th>
                        <td class="fw-bold {{ $history->type == 'deposit' ? 'text-success' : 'text-danger' }}">{{ number_format($history->amount) }} تومان</td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">نوع تراکنش</th>
                        <td>
                            @if($history->withdraw)
                                <span class="badge rounded-pill danger-label">برداشت وجه</span>
                            @elseif($history->type == 'deposit')
                                <span class="badge rounded-pill success-label">افزایش اعتبار</span>
                            @else
                                <span class="badge rounded-pill warning-label">کاهش اعتبار</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">تاریخ تراکنش</th>
                        <td>{{ jdate($history->created_at)->format('Y/m/d H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">وضعیت</th>
                        <td>
                            @if($history->status == 'success')
                                <span class="badge rounded-pill success-label">موفق</span>
                                @if($history->withdraw)
                                    @if($history->status_pay == 'waiting')<span class="badge rounded-pill warning-label">در انتظار پرداخت</span>
                                    @elseif($history->status_pay == 'pay')<span class="badge rounded-pill success-label">پرداخت شده</span>
                                    @elseif($history->status_pay == 'unpay')<span class="badge rounded-pill danger-label">پرداخت نشده</span>
                                    @elseif($history->status_pay == 'unpay-refund')<span class="badge rounded-pill danger-label">پرداخت نشده و مبلغ عودت داده شد</span>
                                    @endif
                                @endif
                            @else
                                <span class="badge rounded-pill danger-label">ناموفق</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">توضیحات</th>
                        <td>{!! $history->description !!}</td>
                    </tr>
                    @if($history->status_pay == 'pay' && $history->trackingId)
                        <tr>
                            <th scope="row" class="text-muted">شماره پیگیری (تراکنش)</th>
                            <td dir="ltr">{{ $history->trackingId }}</td>
                        </tr>
                    @endif
                    @if($history->order)
                        <tr>
                            <th scope="row" class="text-muted">شماره سفارش</th>
                            <td>
                                <a href="{{ route('seller.orders.show', ['order' => $history->order]) }}" class="main-color-one-color fw-bold text-decoration-none">
                                    #{{ $history->order->id }} <i class="bi bi-box-arrow-up-left font-12"></i>
                                </a>
                            </td>
                        </tr>
                    @endif
                    @if($history->transaction)
                        <tr>
                            <th scope="row" class="text-muted">شماره تراکنش درگاه</th>
                            <td dir="ltr">{{ $history->transaction->transId ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row" class="text-muted">شماره پیگیری درگاه</th>
                            <td dir="ltr">{{ $history->transaction->traceNumber ?? '—' }}</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

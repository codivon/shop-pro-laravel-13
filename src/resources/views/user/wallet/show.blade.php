{{-- پارشال AJAX جزئیات تراکنش — WalletController@show : $history (بارگذاری در #history-detail) --}}
<div class="table-responsive">
    <table class="table align-middle">
        <tbody>
        <tr>
            <th scope="row" class="font-14 text-muted" style="min-width:160px;">آیدی تراکنش</th>
            <td class="font-14">#{{ $history->id }}</td>
        </tr>
        <tr>
            <th scope="row" class="font-14 text-muted">مبلغ</th>
            <td class="font-14 fw-bold">{{ number_format($history->amount) }} تومان</td>
        </tr>
        <tr>
            <th scope="row" class="font-14 text-muted">نوع تراکنش</th>
            <td class="font-14">
                @if($history->withdraw)
                    <span class="badge bg-danger rounded-pill">برداشت وجه</span>
                @elseif($history->type == 'deposit')
                    <span class="badge bg-success rounded-pill">افزایش اعتبار</span>
                @else
                    <span class="badge bg-secondary rounded-pill">کاهش اعتبار</span>
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row" class="font-14 text-muted">تاریخ تراکنش</th>
            <td class="font-14 ltr-inline">{{ \Morilog\Jalali\Jalalian::fromDateTime($history->created_at)->format('Y/m/d H:i') }}</td>
        </tr>
        <tr>
            <th scope="row" class="font-14 text-muted">وضعیت</th>
            <td class="font-14">
                @if($history->status == 'success')
                    <span class="badge bg-success rounded-pill">موفق</span>
                    @if($history->withdraw)
                        @if($history->status_pay == 'waiting')
                            <span class="badge bg-warning text-dark rounded-pill">در انتظار پرداخت</span>
                        @elseif($history->status_pay == 'pay')
                            <span class="badge bg-success rounded-pill">پرداخت شده</span>
                        @elseif($history->status_pay == 'unpay')
                            <span class="badge bg-danger rounded-pill">پرداخت نشده</span>
                        @endif
                    @endif
                @else
                    <span class="badge bg-danger rounded-pill">ناموفق</span>
                @endif
            </td>
        </tr>
        <tr>
            <th scope="row" class="font-14 text-muted">توضیحات</th>
            <td class="font-14">{!! $history->description !!}</td>
        </tr>
        @if($history->status_pay == 'pay' && $history->trackingId != null)
            <tr>
                <th scope="row" class="font-14 text-muted">شماره پیگیری (تراکنش)</th>
                <td class="font-14 ltr-inline">{{ $history->trackingId }}</td>
            </tr>
        @endif
        @if ($history->order)
            <tr>
                <th scope="row" class="font-14 text-muted">شماره سفارش</th>
                <td class="font-14">
                    <a target="_blank" rel="noopener" class="main-color-one-color fw-bold"
                       href="{{ route('front.orders.show', ['order' => $history->order]) }}">#{{ $history->order->id }}</a>
                </td>
            </tr>
        @endif
        @if ($history->transaction)
            <tr>
                <th scope="row" class="font-14 text-muted">شماره تراکنش</th>
                <td class="font-14 ltr-inline">{{ $history->transaction->transId }}</td>
            </tr>
            <tr>
                <th scope="row" class="font-14 text-muted">شماره پیگیری</th>
                <td class="font-14 ltr-inline">{{ $history->transaction->traceNumber }}</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

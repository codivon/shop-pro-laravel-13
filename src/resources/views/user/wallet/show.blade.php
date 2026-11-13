<div class="table-responsive">
    {{-- جزئیات تراکنش کیف پول — بارگذاری آجاکسی در #history-detail --}}
    <table class="table main-table">
        <tbody>
            <tr>
                <th scope="row" style="min-width: 200px;">آیدی</th>
                <td>{{ $history->id }}</td>
            </tr>

            <tr>
                <th scope="row">مبلغ</th>
                <td>{{ number_format($history->amount) }} تومان</td>
            </tr>
            <tr>
                <th scope="row">نوع تراکنش</th>
                <td>
                    @if($history->withdraw)
                        برداشت وجه
                        <span class="badge bg-danger ms-1"><i class="bi bi-arrow-left"></i></span>
                    @else
                        @if ($history->type == 'deposit')
                            افزایش اعتبار
                            <span class="badge bg-success ms-1"><i class="bi bi-arrow-up"></i></span>
                        @else
                            کاهش اعتبار
                            <span class="badge bg-danger ms-1"><i class="bi bi-arrow-down"></i></span>
                        @endif
                    @endif
                </td>
            </tr>

            <tr>
                <th scope="row">تاریخ تراکنش</th>
                <td class="ltr">{{ jdate($history->created_at) }}</td>
            </tr>
            <tr>
                <th scope="row">وضعیت</th>
                <td>
                    @if($history->status == 'success')
                        <span class="badge bg-success">موفق</span>
                        @if($history->withdraw)
                            @if($history->status_pay=="waiting")
                                <span class="badge bg-warning">در انتظار پرداخت</span>
                            @elseif($history->status_pay=="pay")
                                <span class="badge bg-success">پرداخت شده</span>
                            @elseif($history->status_pay=="unpay")
                                <span class="badge bg-danger">پرداخت نشده</span>
                            @endif

                        @endif
                    @else
                        <span class="badge bg-danger">ناموفق</span>
                    @endif
                </td>
            </tr>

            <tr>
                <th scope="row">توضیحات</th>
                <td>{!! $history->description !!}</td>
            </tr>
            @if($history->status_pay=="pay" and $history->trackingId!=null)
                <tr>
                    <th scope="row">شماره پیگیری (تراکنش)</th>
                    <td>{{ $history->trackingId }}</td>
                </tr>
            @endif
            @if ($history->order)
                <tr>
                    <th scope="row">شماره سفارش</th>
                    <td><a target="_blank" href="{{ route('front.orders.show', ['order' => $history->order]) }}">{{ $history->order->id }}</a></td>
                </tr>
            @endif

            @if ($history->transaction)
                <tr>
                    <th scope="row">شماره تراکنش</th>
                    <td>{{ $history->transaction->transId }}</td>
                </tr>
                <tr>
                    <th scope="row">شماره پیگیری</th>
                    <td>{{ $history->transaction->traceNumber }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

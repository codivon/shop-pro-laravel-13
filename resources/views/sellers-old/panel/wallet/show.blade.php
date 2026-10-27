<div class="table-responsive ">
    <table class="table">
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
                        <div class="badge badge-danger ml-1">
                            <i class="feather icon-arrow-left"></i>
                        </div>
                    @else
                        @if ($history->type == 'deposit')
                            افزایش اعتبار
                            <div class="badge badge-success ml-1">
                                <i class="feather icon-arrow-up"></i>
                            </div>
                        @else
                            کاهش اعتبار
                            <div class="badge badge-danger ml-1">
                                <i class="feather icon-arrow-down"></i>
                            </div>
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
                        <div class="badge badge-success">موفق</div>
                        @if($history->withdraw)
                            @if($history->status_pay=="waiting")
                                <div class="badge badge-warning">در انتظار پرداخت</div>
                            @elseif($history->status_pay=="pay")
                                <div class="badge badge-success">پرداخت شده</div>
                            @elseif($history->status_pay=="unpay")
                                <div class="badge badge-danger">پرداخت نشده</div>
                            @endif

                        @endif
                    @else
                        <div class="badge badge-danger">ناموفق</div>
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
                    <td><a target="_blank" href="{{ route('seller.orders.show', ['order' => $history->order]) }}">{{ $history->order->id }}</a></td>
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

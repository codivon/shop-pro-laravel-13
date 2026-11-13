@extends('front::user.layouts.master')

@section('user-content')
<div class="headline-profile">
    <span>مرجوعی‌های من</span>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="profile-stats">
    <div class="table-orders">
        <table class="table">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>شماره سفارش</th>
                    <th>محصول</th>
                    <th>نوع پرداخت</th>
                    <th>دلیل</th>
                    <th>بازگشت به کیف پول</th>
                    <th>بازگشت اعتبار</th>
                    <th>مبلغ کل</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th>جزئیات</th>
                </tr>
            </thead>
            <tbody>
                @if($returns->count())
                @foreach($returns as $return)
                @php
                    $statusInfo = \App\Models\ReturnRequest::statusLabels()[$return->status] ?? ['label' => $return->status, 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-circle'];
                    $ptInfo = \App\Models\ReturnRequest::paymentTypeLabels()[$return->payment_type] ?? ['label' => $return->payment_type, 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-circle'];
                @endphp
                <tr>
                    <td>{{ $return->id }}</td>
                    <td><a href="{{ route('front.orders.show', $return->order_id) }}">#{{ $return->order_id }}</a></td>
                    <td>
                        @if($return->orderItem?->product)
                            <a href="{{ route('front.products.show', $return->orderItem->product) }}">{{ \Illuminate\Support\Str::limit($return->orderItem->product->title, 30) }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <span style="background:{{ $ptInfo['bg'] }};color:{{ $ptInfo['color'] }};padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;">
                            <i class="fas {{ $ptInfo['icon'] }}"></i> {{ $ptInfo['label'] }}
                        </span>
                    </td>
                    <td>{{ $return->reason?->title ?? '—' }}</td>
                    <td class="text-success">
                        @if($return->wallet_refund_amount > 0)
                            {{ number_format($return->wallet_refund_amount) }} ت
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-primary">
                        @if($return->credit_restore_amount > 0)
                            {{ number_format($return->credit_restore_amount) }} ت
                        @else
                            —
                        @endif
                    </td>
                    <td><strong>{{ number_format($return->refund_amount) }} ت</strong></td>
                    <td>
                        <span style="background:{{ $statusInfo['bg'] }};color:{{ $statusInfo['color'] }};padding:3px 10px;border-radius:999px;font-size:0.78rem;font-weight:600;">
                            <i class="fas {{ $statusInfo['icon'] }}"></i> {{ $statusInfo['label'] }}
                        </span>
                    </td>
                    <td>{{ jdate($return->created_at)->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('front.returns.show', $return) }}" class="text-info">
                            <i class="mdi mdi-chevron-left"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
                @else
                <tr><td colspan="11" class="text-center text-muted py-4">هیچ درخواست مرجوعی ثبت نشده است.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="pager">
    {{ $returns->links("pagination::bootstrap-4") }}
</div>
@endsection

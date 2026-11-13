@extends('front::user.layouts.master')

@push('styles')
    {{-- آیکون‌های وضعیت مرجوعی از مدل با کلاس‌های font-awesome می‌آیند --}}
    <link rel="stylesheet" href="{{ theme_asset('css/font-awesome.min.css') }}">
@endpush

@section('user-content')

    {{-- مرجوعی‌های من — ریسکین‌شده با جدول قالب سانیار --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">مرجوعی‌های من</h4>
                    </div>
                </div>
                <div class="ui-box-item-desc p-0">

                    @if(session('success'))
                        <div class="alert alert-success rounded-0 mb-0">{{ session('success') }}</div>
                    @endif

                    <div class="responsive-table p-0">
                        <table class="table main-table rounded-0">
                            <thead class="text-bg-dark bg-opacity-75 text-center">
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
                            <tbody class="text-center">
                                @if($returns->count())
                                @foreach($returns as $return)
                                @php
                                    $statusInfo = \App\Models\ReturnRequest::statusLabels()[$return->status] ?? ['label' => $return->status, 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-circle'];
                                    $ptInfo = \App\Models\ReturnRequest::paymentTypeLabels()[$return->payment_type] ?? ['label' => $return->payment_type, 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-circle'];
                                @endphp
                                <tr>
                                    <td class="font-14">{{ $return->id }}</td>
                                    <td class="font-14"><a href="{{ route('front.orders.show', $return->order_id) }}">#{{ $return->order_id }}</a></td>
                                    <td class="font-14">
                                        @if($return->orderItem?->product)
                                            <a href="{{ route('front.products.show', $return->orderItem->product) }}">{{ \Illuminate\Support\Str::limit($return->orderItem->product->title, 30) }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="font-14">
                                        <span class="rounded-pill font-12 fw-bold" style="background:{{ $ptInfo['bg'] }};color:{{ $ptInfo['color'] }};padding:3px 10px;display:inline-block;">
                                            <i class="fas {{ $ptInfo['icon'] }}"></i> {{ $ptInfo['label'] }}
                                        </span>
                                    </td>
                                    <td class="font-14">{{ $return->reason?->title ?? '—' }}</td>
                                    <td class="font-14 text-success">
                                        @if($return->wallet_refund_amount > 0)
                                            {{ number_format($return->wallet_refund_amount) }} ت
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="font-14 main-color-one-color">
                                        @if($return->credit_restore_amount > 0)
                                            {{ number_format($return->credit_restore_amount) }} ت
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="font-14"><strong>{{ number_format($return->refund_amount) }} ت</strong></td>
                                    <td class="font-14">
                                        <span class="rounded-pill fw-bold" style="background:{{ $statusInfo['bg'] }};color:{{ $statusInfo['color'] }};padding:3px 10px;display:inline-block;">
                                            <i class="fas {{ $statusInfo['icon'] }}"></i> {{ $statusInfo['label'] }}
                                        </span>
                                    </td>
                                    <td class="font-14">{{ jdate($return->created_at)->format('Y/m/d') }}</td>
                                    <td class="font-14">
                                        <a href="{{ route('front.returns.show', $return) }}" class="btn btn-sm border-0 main-color-one-bg waves-effect waves-light">
                                            <i class="bi bi-chevron-left text-white"></i>
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

                    <div class="my-paginate mt-4 pb-3">
                        <nav aria-label="Page navigation">
                            {{ $returns->links("pagination::bootstrap-4") }}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

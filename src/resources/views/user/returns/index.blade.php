{{-- مرجوعی‌های من — ReturnController@index : $returns (ReturnRequest با statusLabels/paymentTypeLabels), $active --}}
@extends('front::user.layouts.master', ['title' => 'مرجوعی‌های من'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel mb-3">
                <h2 class="font-16 mb-0">
                    <i class="bi bi-arrow-return-left main-color-one-color me-1"></i>
                    مرجوعی‌های <span class="main-color-one-color">من</span>
                </h2>
            </div>

            @if(session('success'))
                <div class="alert alert-success rounded-3 font-14" role="alert">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                </div>
            @endif

            <div class="responsive-table p-0">
                <table class="table main-table rounded-0">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">شماره سفارش</th>
                        <th scope="col">محصول</th>
                        <th scope="col">نوع پرداخت</th>
                        <th scope="col">بازگشت به کیف پول</th>
                        <th scope="col">مبلغ کل برگشتی</th>
                        <th scope="col">وضعیت</th>
                        <th scope="col">تاریخ</th>
                        <th scope="col">جزئیات</th>
                    </tr>
                    </thead>
                    <tbody class="text-center">
                    @if($returns->count())
                        @foreach($returns as $return)
                            @php
                                // آیکن‌های میزبان fa-* هستند → به bi-* سانیار تبدیل می‌شوند
                                $statusInfo = \App\Models\ReturnRequest::statusLabels()[$return->status] ?? ['label' => $return->status, 'icon' => 'fa-circle'];
                                $ptInfo = \App\Models\ReturnRequest::paymentTypeLabels()[$return->payment_type] ?? ['label' => $return->payment_type, 'icon' => 'fa-circle'];
                                $statusIcon = str_replace(['fa-money-bill-wave', 'fa-'], ['bi-cash', 'bi-'], $statusInfo['icon']);
                                $ptIcon = str_replace(['fa-money-bill-wave', 'fa-'], ['bi-cash', 'bi-'], $ptInfo['icon']);
                            @endphp
                            <tr>
                                <td class="font-14">{{ $return->id }}</td>
                                <td class="font-14">
                                    <a href="{{ route('front.orders.show', $return->order_id) }}" class="main-color-one-color fw-bold">#{{ $return->order_id }}</a>
                                </td>
                                <td class="font-14">
                                    @if($return->orderItem?->product)
                                        <a href="{{ route('front.products.show', $return->orderItem->product) }}" class="text-dark">
                                            {{ \Illuminate\Support\Str::limit($return->orderItem->product->title, 24) }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="font-14"><span class="badge bg-light text-dark border rounded-pill"><i class="bi {{ $ptIcon }} main-color-one-color me-1"></i>{{ $ptInfo['label'] }}</span></td>
                                <td class="font-14 text-success">
                                    @if($return->wallet_refund_amount > 0)
                                        {{ number_format($return->wallet_refund_amount) }} ت
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="font-14 fw-bold">{{ number_format($return->refund_amount) }} ت</td>
                                <td class="font-14">
                                    <span class="badge rounded-pill px-3"
                                          style="background:{{ $statusInfo['bg'] ?? '#f3f4f6' }};color:{{ $statusInfo['color'] ?? '#6b7280' }};">
                                        <i class="bi {{ $statusIcon }} me-1"></i> {{ $statusInfo['label'] }}
                                    </span>
                                </td>
                                <td class="font-14">{{ \Morilog\Jalali\Jalalian::fromDateTime($return->created_at)->format('Y/m/d') }}</td>
                                <td class="font-14">
                                    <a href="{{ route('front.returns.show', $return) }}"
                                       class="btn btn-sm border-0 main-color-one-bg text-white rounded-3">
                                        <i class="bi bi-eye"></i> مشاهده
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="py-5">
                                <i class="bi bi-arrow-return-left text-muted" style="font-size:2.6rem;"></i>
                                <h3 class="font-16 mt-3">درخواست مرجوعی ثبت نشده است</h3>
                                <p class="text-muted font-14 mb-0">در صورت نیاز، از صفحه جزئیات سفارش درخواست مرجوعی ثبت کنید.</p>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($returns->hasPages())
        <div class="my-paginate mt-4">
            {{ $returns->links('front::components.paginate') }}
        </div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

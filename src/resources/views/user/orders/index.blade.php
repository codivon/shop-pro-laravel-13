{{-- لیست سفارش‌ها — OrderController@index : $orders, $active (کوئری وضعیت داخل ویو مطابق قرارداد WeblakShop) --}}
@extends('front::user.layouts.master', ['title' => 'سفارش‌های من'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    @php
        $user = auth('web')->user();
        $allCount      = $user->orders()->count();
        $paidCount     = $user->orders()->where('status', 'paid')->count();
        $unpaidCount   = $user->orders()->where('status', 'unpaid')->count();
        $cancelledCount = $user->orders()->whereIn('status', ['cancelled', 'failed'])->count();
    @endphp

    {{-- تب‌های وضعیت --}}
    <div class="saniar-status-tabs d-flex flex-wrap gap-2 mb-3" role="tablist" aria-label="فیلتر وضعیت سفارش">
        <a href="?status=all" class="saniar-status-tab {{ (request('status', 'all') === 'all') ? 'active' : '' }}">
            <i class="bi bi-list-ul"></i> همه
            @if($allCount > 0)<span class="badge rounded-pill badge-spn">{{ $allCount }}</span>@endif
        </a>
        <a href="?status=paid" class="saniar-status-tab {{ (request('status') === 'paid') ? 'active' : '' }}">
            <i class="bi bi-check-circle"></i> تکمیل شده
            @if($paidCount > 0)<span class="badge rounded-pill badge-spn">{{ $paidCount }}</span>@endif
        </a>
        <a href="?status=unpaid" class="saniar-status-tab {{ (request('status') === 'unpaid') ? 'active' : '' }}">
            <i class="bi bi-hourglass-split"></i> در انتظار پرداخت
            @if($unpaidCount > 0)<span class="badge rounded-pill badge-spn">{{ $unpaidCount }}</span>@endif
        </a>
        <a href="?status=cancelled" class="saniar-status-tab {{ (request('status') === 'cancelled') ? 'active' : '' }}">
            <i class="bi bi-x-circle"></i> لغو شده
            @if($cancelledCount > 0)<span class="badge rounded-pill badge-spn">{{ $cancelledCount }}</span>@endif
        </a>
    </div>

    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel mb-3">
                <h2 class="font-16 mb-0"><i class="bi bi-cart-check main-color-one-color me-1"></i>سفارشات</h2>
            </div>

            <div class="responsive-table p-0">
                <table class="table main-table rounded-0">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">شماره سفارش</th>
                        <th scope="col">تاریخ ثبت سفارش</th>
                        <th scope="col">مبلغ پرداختی</th>
                        <th scope="col">وضعیت سفارش</th>
                        <th scope="col">جزییات</th>
                    </tr>
                    </thead>
                    <tbody class="text-center">
                    @if($orders->count())
                        @foreach ($orders as $order)
                            <tr>
                                <td class="font-14">{{ $orders->firstItem() + $loop->iteration - 1 }}</td>
                                <td class="font-14 fw-bold">{{ $order->id }}</td>
                                <td class="font-14">{{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('l j F Y') }}</td>
                                <td class="font-14">{{ number_format($order->price) }} تومان</td>
                                <td class="font-14">
                                    @if($order->status == 'paid')
                                        <span class="success-label rounded-pill">پرداخت شده</span>
                                    @elseif($order->status == 'unpaid')
                                        <span class="warning-label rounded-pill">در انتظار پرداخت</span>
                                    @else
                                        <span class="danger-label rounded-pill">لغو شده</span>
                                    @endif
                                </td>
                                <td class="font-14">
                                    <a href="{{ route('front.orders.show', ['order' => $order]) }}"
                                       class="btn btn-sm border-0 main-color-one-bg waves-effect waves-light text-white">مشاهده
                                        <i class="bi bi-chevron-left font-16"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="py-5">
                                <i class="bi bi-cart-x text-muted" style="font-size:2.6rem;"></i>
                                <h3 class="font-16 mt-3">هیچ سفارشی یافت نشد</h3>
                                <p class="text-muted font-14">در این وضعیت سفارشی وجود ندارد.</p>
                                <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg text-white rounded-3 px-4">
                                    <i class="bi bi-grid me-1"></i> شروع خرید
                                </a>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($orders->hasPages())
        <div class="my-paginate mt-4">
            {{ $orders->links('front::components.paginate') }}
        </div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

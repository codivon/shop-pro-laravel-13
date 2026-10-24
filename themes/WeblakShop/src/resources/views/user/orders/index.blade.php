@extends('front::user.layouts.master')
@push('styles')
    <link rel="stylesheet" type="text/css" href="{{theme_asset('css/order.css')}}">
    <style>
        .page-contents-order{
            background: unset;
        }

    </style>
@endpush
@section('user-content')


    <div class="page-contents page-contents-order orders-page">

        {{-- ===== Header ===== --}}
        <div class="orders-header">
            <h4>
                <i class="fas fa-shopping-bag"></i>
                سفارش‌های من
            </h4>
        </div>

        {{-- ===== Filter Tabs ===== --}}
        <div class="orders-tabs">
            <a href="?status=all" class="orders-tab {{ (request('status', 'all') === 'all') ? 'active' : '' }}">
                <i class="fas fa-list"></i>
                همه
                @php $allCount = auth('web')->user()->orders()->count(); @endphp
                @if($allCount > 0)
                    <span class="tab-count">{{ $allCount }}</span>
                @endif
            </a>
            <a href="?status=paid" class="orders-tab {{ (request('status') === 'paid') ? 'active' : '' }}">
                <i class="fas fa-check-circle"></i>
                تکمیل شده
                @php $paidCount = auth('web')->user()->orders()->where('status', 'paid')->count(); @endphp
                @if($paidCount > 0)
                    <span class="tab-count">{{ $paidCount }}</span>
                @endif
            </a>
            <a href="?status=unpaid" class="orders-tab {{ (request('status') === 'unpaid') ? 'active' : '' }}">
                <i class="fas fa-clock"></i>
                در انتظار پرداخت
                @php $unpaidCount = auth('web')->user()->orders()->where('status', 'unpaid')->count(); @endphp
                @if($unpaidCount > 0)
                    <span class="tab-count">{{ $unpaidCount }}</span>
                @endif
            </a>
            <a href="?status=cancelled" class="orders-tab {{ (request('status') === 'cancelled') ? 'active' : '' }}">
                <i class="fas fa-times-circle"></i>
                لغو شده
                @php $cancelledCount = auth('web')->user()->orders()->whereIn('status', ['cancelled', 'failed'])->count(); @endphp
                @if($cancelledCount > 0)
                    <span class="tab-count">{{ $cancelledCount }}</span>
                @endif
            </a>
            @php
                $returnedCount = 0;
                if (class_exists(\App\Models\ReturnRequest::class)) {
                    $returnedCount = \App\Models\ReturnRequest::where('user_id', auth('web')->id())
                        ->whereNotIn('status', ['cancelled'])
                        ->distinct('order_id')
                        ->count('order_id');
                }
            @endphp
            @if(class_exists(\App\Models\ReturnRequest::class))
                <a href="?status=returned" class="orders-tab {{ (request('status') === 'returned') ? 'active' : '' }}">
                    <i class="fas fa-undo-alt"></i>
                    مرجوع شده
                    @if($returnedCount > 0)
                        <span class="tab-count">{{ $returnedCount }}</span>
                    @endif
                </a>
            @endif
        </div>

        @php
            $query = auth('web')->user()->orders()->latest();
            if (request('status') === 'paid') {
                $query->where('status', 'paid');
            } elseif (request('status') === 'unpaid') {
                $query->where('status', 'unpaid');
            } elseif (request('status') === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'failed']);
            } elseif (request('status') === 'returned' && class_exists(\App\Models\ReturnRequest::class)) {
                $returnedOrderIds = \App\Models\ReturnRequest::where('user_id', auth('web')->id())
                    ->whereNotIn('status', ['cancelled'])
                    ->pluck('order_id')
                    ->unique()
                    ->toArray();
                $query->whereIn('id', $returnedOrderIds);
            }
            $orders = $query->paginate(10);
        @endphp

        {{-- ===== Order Cards ===== --}}
        @if($orders->count())
            @foreach ($orders as $order)

                @php
                    // بررسی نوع پرداخت
                    $isInstallment = false;
                    $isCredit = false;
                    if (function_exists('module_is_active') && module_is_active('InstallmentPayment')) {
                        $isInstallment = \Modules\InstallmentPayment\Models\InstallmentPlan::where('order_id', $order->id)->exists();
                    }
                    if (function_exists('module_is_active') && module_is_active('CreditPay')) {
                        $isCredit = \Modules\CreditPay\Models\CreditOrder::where('order_id', $order->id)->exists();
                    }

                    // بررسی مرجوعی
                    $hasReturn = false;
                    if (class_exists(\App\Models\ReturnRequest::class)) {
                        $hasReturn = \App\Models\ReturnRequest::where('order_id', $order->id)
                            ->whereNotIn('status', ['cancelled'])
                            ->exists();
                    }

                    // محصولات سفارش
                    $orderProducts = $order->items->take(4);
                    $totalProducts = $order->items->count();
                    $remainingProducts = max(0, $totalProducts - 4);
                @endphp

                <div class="order-card">
                    {{-- Header --}}
                    <div class="order-card-header">
                        <div class="order-card-header-left">
                            <a href="{{ route('front.orders.show', ['order' => $order]) }}">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <h6>سفارش #{{ $order->id }}</h6>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if($hasReturn)
                                <span class="return-indicator">
                            <i class="fas fa-undo-alt"></i> مرجوعی
                        </span>
                            @endif
                            @if($order->status == 'paid')
                                <span class="order-status-badge paid">
                                <i class="fas fa-check-circle"></i> تحویل شده
                            </span>
                            @elseif($order->status == 'unpaid')
                                <span class="order-status-badge unpaid">
                                <i class="fas fa-clock"></i> پرداخت نشده
                            </span>
                            @else
                                <span class="order-status-badge cancelled">
                                <i class="fas fa-times-circle"></i> لغو شده
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="order-card-body">
                        {{-- Info Row --}}
                        <div class="order-info-row">
                            <div class="order-info-item">
                                <i class="far fa-calendar"></i>
                                <span>{{ jdate($order->created_at)->format('%d %B %Y') }}</span>
                            </div>
                            <div class="order-info-divider"></div>
                            <div class="order-info-item">
                                <i class="fas fa-box"></i>
                                <span>{{ $totalProducts }} کالا</span>
                            </div>
                            <div class="order-info-divider"></div>
                            @if($isInstallment)
                                <span class="payment-type-badge installment">
                            <i class="fas fa-money-check-alt"></i> اقساطی
                        </span>
                            @elseif($isCredit)
                                <span class="payment-type-badge credit">
                            <i class="fas fa-credit-card"></i> اعتباری
                        </span>
                            @elseif ($order->walletHistory)
                                <span class="payment-type-badge wallet">
                            <i class="fas fa-wallet"></i> کیف پول
                        </span>
                            @else
                                <span class="payment-type-badge gateway">
                            <i class="fas fa-credit-card"></i> اینترنتی
                        </span>
                            @endif
                        </div>

                        {{-- Product Thumbnails --}}
                        <div class="order-products">
                            @foreach($orderProducts as $item)
                                <div class="order-product-thumb">
                                    @if($item->product && $item->product->image)
                                        <img src="{{ asset($item->product->image) }}" alt="{{ $item->title }}">
                                    @else
                                        <div class="order-product-thumb-more">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            @if($remainingProducts > 0)
                                <div class="order-product-thumb order-product-thumb-more">
                                    +{{ $remainingProducts }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="order-card-footer">
                        <div class="order-total">
                            <span class="label">مبلغ قابل پرداخت:</span>
                            <span class="value">{{ number_format($order->price) }} تومان</span>
                            @if($order->totalDiscount() != 0)
                                <span style="color:#10b981;font-size:0.75rem;">
                            (تخفیف: {{ number_format($order->totalDiscount()) }} ت)
                        </span>
                            @endif
                        </div>
                        <div class="order-actions">
                            @if($order->status == 'unpaid')
                                <a href="{{ route('front.orders.pay', ['order' => $order]) }}"
                                   class="order-action-btn order-action-btn-primary">
                                    <i class="fas fa-credit-card"></i> پرداخت
                                </a>
                            @endif
                            <a href="{{ route('front.orders.show', ['order' => $order]) }}"
                               class="order-action-btn order-action-btn-outline">
                                <i class="fas fa-eye"></i> جزئیات
                            </a>
                        </div>
                    </div>
                </div>

            @endforeach

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-4">
                {{ $orders->links("pagination::bootstrap-4") }}
            </div>

        @else
            {{-- Empty State --}}
            <div class="orders-empty">
                <i class="fas fa-shopping-bag"></i>
                <h6>هیچ سفارشی یافت نشد</h6>
                <p>شما هنوز سفارشی ثبت نکرده‌اید.</p>
                <a href="{{ route('front.products.index') }}" class="order-action-btn order-action-btn-primary">
                    <i class="fas fa-store"></i> شروع خرید
                </a>
            </div>
        @endif

    </div>

@endsection

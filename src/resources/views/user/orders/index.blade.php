@extends('front::user.layouts.master')

@section('user-content')

    {{-- سفارش‌های من — ریسکین‌شده با کامپوننت سفارش‌های قالب سانیار (order.html / order-user-panel.html) --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h4 class="fw-bold">سفارش‌های من</h4>

                        {{-- ===== Filter Tabs ===== --}}
                        <div class="d-flex flex-wrap align-items-center">
                            @php $allCount = auth('web')->user()->orders()->count(); @endphp
                            <a href="?status=all" class="btn btn-sm rounded-pill me-2 mb-2 {{ (request('status', 'all') === 'all') ? 'main-color-one-bg text-white' : 'btn-light' }}">
                                <i class="bi bi-list"></i>
                                همه
                                @if($allCount > 0)
                                    <span class="badge rounded-pill badge-spn">{{ $allCount }}</span>
                                @endif
                            </a>
                            @php $paidCount = auth('web')->user()->orders()->where('status', 'paid')->count(); @endphp
                            <a href="?status=paid" class="btn btn-sm rounded-pill me-2 mb-2 {{ (request('status') === 'paid') ? 'main-color-one-bg text-white' : 'btn-light' }}">
                                <i class="bi bi-check-circle"></i>
                                تکمیل شده
                                @if($paidCount > 0)
                                    <span class="badge rounded-pill badge-spn">{{ $paidCount }}</span>
                                @endif
                            </a>
                            @php $unpaidCount = auth('web')->user()->orders()->where('status', 'unpaid')->count(); @endphp
                            <a href="?status=unpaid" class="btn btn-sm rounded-pill me-2 mb-2 {{ (request('status') === 'unpaid') ? 'main-color-one-bg text-white' : 'btn-light' }}">
                                <i class="bi bi-clock"></i>
                                در انتظار پرداخت
                                @if($unpaidCount > 0)
                                    <span class="badge rounded-pill badge-spn">{{ $unpaidCount }}</span>
                                @endif
                            </a>
                            @php $cancelledCount = auth('web')->user()->orders()->whereIn('status', ['cancelled', 'failed'])->count(); @endphp
                            <a href="?status=cancelled" class="btn btn-sm rounded-pill me-2 mb-2 {{ (request('status') === 'cancelled') ? 'main-color-one-bg text-white' : 'btn-light' }}">
                                <i class="bi bi-x-circle"></i>
                                لغو شده
                                @if($cancelledCount > 0)
                                    <span class="badge rounded-pill badge-spn">{{ $cancelledCount }}</span>
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
                                <a href="?status=returned" class="btn btn-sm rounded-pill me-2 mb-2 {{ (request('status') === 'returned') ? 'main-color-one-bg text-white' : 'btn-light' }}">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    مرجوع شده
                                    @if($returnedCount > 0)
                                        <span class="badge rounded-pill badge-spn">{{ $returnedCount }}</span>
                                    @endif
                                </a>
                            @endif
                        </div>
                    </div>
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

                <div class="ui-box-item-desc p-0">
                    {{-- ===== Order Cards ===== --}}
                    @if($orders->count())
                        <div class="orders">
                            @foreach ($orders as $order)

                                @php
                                    // بررسی نوع پرداخت — فاز ۸۸: پکیج ادغامی CreditPay (پرداخت قسطی و اعتباری) جایگزین پکیج قدیمی اقساطی شد
                                    $isInstallment = false;
                                    $isCredit = false;
                                    if (function_exists('module_is_active') && module_is_active('CreditPay')) {
                                        $isInstallment = \Modules\CreditPay\Models\InstallmentPlan::where('order_id', $order->id)->exists();
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

                                <div class="order-item">
                                    {{-- Status + Link --}}
                                    <div class="order-item-status">
                                        <div class="order-item-status-item">
                                            @if($hasReturn)
                                                <p><i class="bi bi-arrow-counterclockwise text-warning"></i> <span>مرجوعی</span></p>
                                            @endif
                                            @if($order->status == 'paid')
                                                <p><i class="bi bi-bag-check-fill main-color-three-color"></i> <span>تحویل شده</span></p>
                                            @elseif($order->status == 'unpaid')
                                                <p><i class="bi bi-clock-fill text-warning"></i> <span>پرداخت نشده</span></p>
                                            @else
                                                <p><i class="bi bi-x-circle-fill text-danger"></i> <span>لغو شده</span></p>
                                            @endif
                                        </div>
                                        <div class="order-item-status-item">
                                            <a href="{{ route('front.orders.show', ['order' => $order]) }}">
                                                <p><i class="bi bi-arrow-left pointer text-dark"></i></p>
                                            </a>
                                        </div>
                                    </div>

                                    {{-- Info Row --}}
                                    <div class="order-item-detail">
                                        <ul class="nav">
                                            <li class="nav-item text-muted">{{ jdate($order->created_at)->format('%d %B %Y') }}</li>
                                            <li class="nav-item">
                                                <span class="text-mute">کد سفارش</span>
                                                <strong>#{{ $order->id }}</strong>
                                            </li>
                                            <li class="nav-item">
                                                <span class="text-mute">{{ $totalProducts }} کالا</span>
                                                @if($isInstallment)
                                                    <strong><i class="bi bi-cash-coin"></i> اقساطی</strong>
                                                @elseif($isCredit)
                                                    <strong><i class="bi bi-credit-card"></i> اعتباری</strong>
                                                @elseif ($order->walletHistory)
                                                    <strong><i class="bi bi-wallet2"></i> کیف پول</strong>
                                                @else
                                                    <strong><i class="bi bi-credit-card"></i> اینترنتی</strong>
                                                @endif
                                            </li>
                                            <li class="nav-item">
                                                <span class="text-mute">مبلغ قابل پرداخت</span>
                                                <strong>{{ number_format($order->price) }} تومان</strong>
                                            </li>
                                        </ul>
                                    </div>

                                    {{-- Product Thumbnails --}}
                                    <div class="order-item-product-list">
                                        @foreach($orderProducts as $item)
                                            <div class="order-item-product-list-item">
                                                @if($item->product && $item->product->image)
                                                    <img src="{{ asset($item->product->image) }}" alt="{{ $item->title }}" class="img-thumbnail" width="70" height="70">
                                                @else
                                                    <div class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if($remainingProducts > 0)
                                            <div class="order-item-product-list-item">
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center text-muted" style="width: 70px; height: 70px;">
                                                    +{{ $remainingProducts }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Footer --}}
                                    <div class="order-item-show">
                                        @if($order->totalDiscount() != 0)
                                            <p class="text-success font-12">تخفیف: {{ number_format($order->totalDiscount()) }} ت</p>
                                        @endif
                                        <div class="d-flex align-items-center justify-content-end flex-wrap">
                                            @if($order->status == 'unpaid')
                                                <a href="{{ route('front.orders.pay', ['order' => $order]) }}"
                                                   class="btn btn-sm border-0 main-color-one-bg waves-effect waves-light ms-2 mb-1">
                                                    <i class="bi bi-credit-card text-white"></i> پرداخت
                                                </a>
                                            @endif
                                            <a href="{{ route('front.orders.show', ['order' => $order]) }}"
                                               class="btn btn-sm border-0 main-color-three-bg waves-effect waves-light mb-1">
                                                <i class="bi bi-card-list text-white"></i> جزئیات
                                            </a>
                                        </div>
                                    </div>
                                </div>

                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="my-paginate mt-4 pb-3">
                            <nav aria-label="Page navigation">
                                {{ $orders->links("pagination::bootstrap-4") }}
                            </nav>
                        </div>

                    @else
                        {{-- Empty State --}}
                        <div class="text-center py-5">
                            <i class="bi bi-bag main-color-one-color font-40"></i>
                            <h6 class="mt-3">هیچ سفارشی یافت نشد</h6>
                            <p class="text-muted">شما هنوز سفارشی ثبت نکرده‌اید.</p>
                            <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg waves-effect waves-light">
                                <i class="bi bi-shop text-white"></i> شروع خرید
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

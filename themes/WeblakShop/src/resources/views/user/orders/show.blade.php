@extends('front::user.layouts.master')
@push('styles')
    <link rel="stylesheet" type="text/css" href="{{theme_asset('css/order.css')}}">
@endpush
@section('user-content')

    @php
        // گروه‌بندی آیتم‌های سفارش بر اساس فروشنده
        $sellerGroups = [];
        $totalPrice = 0;
        $totalShippingCost = 0;
        $totalDiscount = 0;
        $downloadGroup = [];
        foreach ($order->items as $item) {

             if ($item->product && $item->product->isDownload()) {
            $downloadGroup[] = $item;
            continue;
            }

            $sellerId = $item->seller_id;
            $groupId = $sellerId ? 'seller_' . $sellerId : 'store';

            if (!isset($sellerGroups[$groupId])) {
                $seller = $sellerId ? \App\Models\Seller::find($sellerId) : null;
                $sellerGroups[$groupId] = [
                    'seller_id' => $sellerId,
                    'seller_name' => $seller ? ($seller->seller_info->business_name ?? $seller->name) : 'فروشگاه',
                    'seller_logo' => $seller && $seller->seller_info ? asset($seller->seller_info->logo) : null,
                    'items' => [],
                    'subtotal' => 0,
                    'shipping_cost' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'shipping_status' => $item->shipping_status,
                    'tracking_code' => $item->tracking_code ?? null,
                    'carrier_name' => $item->carrier ? $item->carrier->title : null,
                    'delivery_date' => $item->delivery_date ?? null,
                    'cancel_reason' => $item->cancel_reason ?? null,
                    'refunded' => $item->refunded ?? false
                ];
            }

            $itemTotalPrice = $item->real_price * $item->quantity;
            $itemDiscount = ($item->real_price - $item->price) * $item->quantity;

            $sellerGroups[$groupId]['items'][] = $item;
            $sellerGroups[$groupId]['subtotal'] += $itemTotalPrice;
            $sellerGroups[$groupId]['discount'] += $itemDiscount;
            $sellerGroups[$groupId]['total'] += $itemTotalPrice;
            $sellerGroups[$groupId]['shipping_cost'] = $item->shipping_cost ?? 0;

            $totalPrice += $itemTotalPrice;
            $totalDiscount += $itemDiscount;
            $totalShippingCost += $item->shipping_cost ?? 0;
        }

         // اگر محصولات دانلودی وجود دارند، آنها را به عنوان یک گروه جداگانه اضافه کن
    if (!empty($downloadGroup)) {
        $sellerGroups['download'] = [
            'seller_id' => null,
            'seller_name' => 'محصولات دانلودی',
            'seller_logo' => null,
            'items' => $downloadGroup,
            'subtotal' => 0,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 0,
            'shipping_status' => 'delivered',
            'tracking_code' => null,
            'carrier_name' => 'دانلودی',
            'delivery_date' => null,
            'cancel_reason' => null,
            'refunded' => false,
            'is_download_group' => true
        ];

        foreach ($downloadGroup as $item) {
            $itemTotalPrice = $item->real_price * $item->quantity;
            $itemDiscount = ($item->real_price - $item->price) * $item->quantity;

            $sellerGroups['download']['subtotal'] += $itemTotalPrice;
            $sellerGroups['download']['discount'] += $itemDiscount;
            $sellerGroups['download']['total'] += $itemTotalPrice;

            $totalPrice += $itemTotalPrice;
            $totalDiscount += $itemDiscount;
        }
    }

        $finalPayable = $totalPrice - $totalDiscount + $totalShippingCost;
        $hasPhysicalProduct = $order->hasPhysicalProduct();
        $NoDownload = [];
        foreach ($order->items as $item) {
            if ($item->product && $item->product->isDownload() && $item->get_price && $item->get_price->isDownloadable()) {
                // قابل دانلود است
            } else {
                $NoDownload[] = $item->id;
            }
        }

        // ========== بررسی اقساطی ==========
        $installmentPlan = null;
        if (function_exists('module_is_active') && module_is_active('InstallmentPayment')) {
            $installmentPlan = \Modules\InstallmentPayment\Models\InstallmentPlan::where('order_id', $order->id)->first();
        }
        $isInstallment = (bool) $installmentPlan;
        $displayPayable = $isInstallment ? $installmentPlan->down_payment : $finalPayable;

        // ========== برچسب‌های وضعیت مرجوعی ==========
        $returnStatusLabels = [
            'pending'   => ['label' => 'در حال بررسی مرجوعی', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'icon' => 'fa-clock'],
            'approved'  => ['label' => 'تایید اولیه مرجوعی', 'color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'fa-check-circle'],
            'received'  => ['label' => 'محصول دریافت شد', 'color' => '#8b5cf6', 'bg' => '#ede9fe', 'icon' => 'fa-box'],
            'completed' => ['label' => 'مرجوع شد', 'color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'fa-check-double'],
            'rejected'  => ['label' => 'مرجوعی رد شد', 'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
            'cancelled' => ['label' => 'مرجوعی لغو شد', 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-ban'],
        ];
    @endphp

    <section class="page-contents page-contents-order">
        <div class="container-fluid px-0">

            {{-- پیام‌ها --}}
            @if(session('message') == 'ok')
                <div class="alert alert-success rounded-3 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="mdi mdi-check-circle fs-4 me-2"></i>
                        <div>
                            سفارش شما با شماره سفارش <strong>{{ $order->id }}</strong> با موفقیت در سیستم ثبت شد.
                        </div>
                    </div>
                </div>
            @elseif(session('transaction-error'))
                <div class="alert alert-warning rounded-3 mb-4">
                    <div class="d-flex align-items-start">
                        <i class="mdi mdi-alert-circle fs-4 me-2"></i>
                        <div>
                            <strong>سفارش با شماره {{ $order->id }} ثبت شد اما پرداخت ناموفق بود.</strong>
                            <p class="mb-0 mt-1 small">برای جلوگیری از لغو سیستمی سفارش، تا ۱ ساعت پس از ثبت سفارش پرداخت را انجام دهید.</p>
                            <p class="mb-0 small text-muted">چنانچه طی این فرایند مبلغی از حساب شما کسر شده است، طی ۷۲ ساعت آینده به حساب شما باز خواهد گشت.</p>
                            @if($order->status == 'unpaid')
                                <a href="{{ route('front.orders.pay', ['order' => $order]) . '?gateway=' . ($order->gatewayRelation ? $order->gatewayRelation->key : 'wallet') }}"
                                   class="btn btn-primary btn-sm mt-2">
                                    پرداخت سفارش
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif(session('error'))
                <div class="alert alert-danger rounded-3 mb-4">
                    <i class="mdi mdi-close-circle me-2"></i>
                    <strong>{{ session('error') }}</strong>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success rounded-3 mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>{{ session('success') }}</strong>
                </div>
            @endif

            {{-- کارت اصلی سفارش --}}
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('front.orders.index') }}" class="text-dark me-3">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <h5 class="mb-0 fw-bold">جزئیات سفارش #{{ $order->id }}</h5>
                    </div>
                    <a href="{{ route('front.orders.print', $order->id) }}" class="text-secondary" target="_blank">
                        <i class="fas fa-print me-1"></i> مشاهده فاکتور
                    </a>
                </div>

                <div class="card-body">
                    {{-- اطلاعات کلی سفارش --}}
                    <div class="row g-3 pb-3 mb-3 border-bottom">
                        <div class="col-md-3 col-6">
                            <div class="text-muted small">کد پیگیری سفارش</div>
                            <div class="fw-bold">{{ $order->id }}</div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-muted small">تاریخ ثبت سفارش</div>
                            <div class="fw-bold">{{ jdate($order->created_at)->format('%d %B %Y') }}</div>
                        </div>
                        @if(count($NoDownload))
                            <div class="col-md-3 col-6">
                                <div class="text-muted small">تحویل گیرنده</div>
                                <div class="fw-bold">{{ $order->name }}</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-muted small">شماره موبایل</div>
                                <div class="fw-bold">{{ $order->mobile }}</div>
                            </div>
                        @endif
                    </div>

                    @if(count($NoDownload))
                        <div class="mb-3 text-right">
                            <div class="text-muted small">آدرس تحویل</div>
                            <div class="">{{ $order->address }}</div>
                        </div>
                    @endif

                    {{-- جمع‌بندی مالی --}}
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <div class="text-muted small">مبلغ کل کالاها</div>
                                <div class="fw-bold">{{ number_format($totalPrice) }} تومان</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-muted small">تخفیف</div>
                                @if($totalDiscount)
                                    <div class="fw-bold text-success">{{ number_format($totalDiscount) }} تومان</div>
                                @else
                                    -
                                @endif

                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-muted small">هزینه ارسال</div>
                                <div class="fw-bold">
                                    @if($totalShippingCost > 0)
                                        {{ number_format($totalShippingCost) }} تومان
                                    @elseif($order->carrier && $order->carrier->carrige_forward)
                                        پس کرایه
                                    @else
                                        رایگان
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-muted small">نوع پرداخت</div>
                                <div class="fw-bold">
                                    @if($isInstallment)
                                        <span class="badge bg-info"><i class="fas fa-money-check-alt"></i> اقساطی</span>
                                    @elseif ($order->walletHistory)
                                        کیف پول
                                    @else
                                        پرداخت اینترنتی
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3 pt-2 border-top">
                            <div class="col-md-6 col-6">
                                <div class="text-muted small">وضعیت پرداخت</div>
                                <div>
                                    @if($order->status == 'paid')
                                        <span class="badge bg-success">پرداخت شده</span>

                                        @if($isInstallment && $installmentPlan->isActive())
                                            <small class="text-muted d-block mt-1">پیش‌پرداخت انجام شد - طرح اقساطی فعال</small>
                                        @endif
                                    @elseif($order->status == 'unpaid')
                                        @if($isInstallment)
                                            <span class="badge bg-warning">در انتظار پیش‌پرداخت</span>
                                            <small class="text-muted d-block mt-1">مهلت پرداخت: تا ۱ ساعت (در غیر این صورت سفارش لغو می‌شود)</small>
                                        @else
                                            <span class="badge bg-danger">پرداخت نشده</span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">لغو شده</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 col-6">
                                <div class="text-muted small">
                                    @if($isInstallment)
                                        مبلغ پیش‌پرداخت (الان پرداخت می‌شود)
                                    @else
                                        مبلغ قابل پرداخت
                                    @endif
                                </div>
                                <div class="fw-bold fs-5 text-primary">{{ number_format($displayPayable) }} تومان</div>
                                @if($isInstallment)
                                    <small class="text-muted d-block">از مجموع {{ number_format($installmentPlan->total_payable) }} تومان</small>
                                @endif
                            </div>
                        </div>

                        {{-- ======== نمایش اطلاعات طرح اقساطی ======== --}}
                        @if($isInstallment)
                            @include('installment-payment::front.order_installment_info', ['order' => $order])
                        @endif

                        {{-- دکمه پرداخت سفارش --}}
                        @if($order->status == 'unpaid' && $isInstallment)
                            {{-- دکمه پرداخت پیش‌پرداخت برای سفارش اقساطی --}}
                            <div class="mt-3">
                                <form action="{{ route('front.orders.pay', ['order' => $order]) }}" method="GET" class="row g-2 align-items-end">
                                    <div class="col-md-4 col-7">
                                        <select class="form-select form-select-sm" name="gateway" required>
                                            <option value="">انتخاب روش پرداخت پیش‌پرداخت</option>
                                            @if ($wallet->balance >= $order->price)
                                                <option value="wallet">پرداخت با کیف پول (موجودی: {{ number_format($wallet->balance) }} ت)</option>
                                            @elseif ($wallet->balance)
                                                <option value="wallet">شارژ و پرداخت با کیف پول</option>
                                            @endif
                                            @foreach ($gateways as $gateway)
                                                <option value="{{ $gateway->key }}">پرداخت با {{ $gateway->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-5">
                                        <button type="submit" class="btn btn-warning btn-sm w-100">
                                            <i class="fas fa-credit-card"></i> پرداخت پیش‌پرداخت
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @elseif($order->status == 'unpaid')
                            {{-- دکمه پرداخت عادی برای سفارش غیراقساطی --}}
                            <div class="mt-3">
                                <form action="{{ route('front.orders.pay', ['order' => $order]) }}" method="GET" class="row g-2 align-items-end">
                                    <div class="col-md-4 col-7">
                                        <select class="form-select form-select-sm" name="gateway" required>
                                            <option value="">انتخاب درگاه پرداخت</option>
                                            @if ($wallet->balance >= $order->price)
                                                <option value="wallet">پرداخت با کیف پول</option>
                                            @elseif ($wallet->balance)
                                                <option value="wallet">شارژ و پرداخت با کیف پول</option>
                                            @endif
                                            @foreach ($gateways as $gateway)
                                                <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-5">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">پرداخت سفارش</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>

                    @if(function_exists('module_is_active') && module_is_active('CreditPay'))
                        @include('credit-pay::front.order_credit_info', ['order' => $order])
                    @endif

                    {{-- ========== مرسوله‌ها ========== --}}
                    @foreach($sellerGroups as $groupId => $group)

                        @php
                            $statusSteps = [
                                'w-pending' => ['label' => 'در انتظار بررسی', 'step' => 1, 'color' => '#ffc107', 'icon' => 'fa-clock'],
                                'pending' => ['label' => 'در حال بررسی', 'step' => 2, 'color' => '#fd7e14', 'icon' => 'fa-magnifying-glass'],
                                'processing' => ['label' => 'در حال پردازش', 'step' => 3, 'color' => '#0d6efd', 'icon' => 'fa-gear'],
                                'waiting' => ['label' => 'منتظر ارسال', 'step' => 4, 'color' => '#0dcaf0', 'icon' => 'fa-hourglass-half'],
                                'sent' => ['label' => 'ارسال شد', 'step' => 5, 'color' => '#6f42c1', 'icon' => 'fa-truck-fast'],
                                'post-sent' => ['label' => 'تحویل به پست', 'step' => 6, 'color' => '#20c997', 'icon' => 'fa-box'],
                                'delivered' => ['label' => 'تحویل داده شد', 'step' => 7, 'color' => '#198754', 'icon' => 'fa-check-circle'],
                                'canceled' => ['label' => 'لغو شد', 'step' => 0, 'color' => '#dc3545', 'icon' => 'fa-ban']
                            ];

                            $isDownloadGroup = $group['is_download_group'] ?? false;
                            $currentStatus = $group['shipping_status'];
                            $currentStep = $statusSteps[$currentStatus]['step'] ?? 1;
                            $maxStep = 7;
                            $progressPercent = ($currentStep / $maxStep) * 100;
                            $progressColor = $statusSteps[$currentStatus]['color'] ?? '#ffc107';
                            $isCanceled = ($currentStatus == 'canceled');
                        @endphp


                        <div class="card border rounded-3 mb-4 overflow-hidden">
                            {{-- هدر مرسوله --}}
                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center gap-2">
                                    @if($group['seller_logo'])
                                        <img src="{{ $group['seller_logo'] }}" width="32" height="32" class="rounded-circle object-fit-cover">
                                    @else
                                        <i class="fas fa-store fs-4 text-secondary"></i>
                                    @endif
                                    <div>
                                        <span class="fw-bold">{{ $group['seller_name'] }}</span>
                                        <span class="badge bg-secondary ms-2">مرسوله {{ $loop->iteration }}</span>
                                    </div>
                                </div>

                                @if($order->status == 'paid')
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge" style="background-color: {{ $progressColor }};">{{ $statusSteps[$currentStatus]['label'] }}</span>
                                    </div>
                                @endif

                            </div>

                            <div class="card-body p-3">
                                {{-- اطلاعات ارسال --}}
                                <div class="row g-3 small mb-3 pb-2 border-bottom">
                                    <div class="col-md-3 col-6">
                                        <div class="text-muted">هزینه ارسال</div>
                                        <div class="fw-bold">
                                            @if($group['shipping_cost'] > 0)
                                                {{ number_format($group['shipping_cost']) }} تومان
                                            @else
                                                رایگان
                                            @endif
                                        </div>
                                    </div>
                                    @if($group['carrier_name'])
                                        <div class="col-md-3 col-6">
                                            <div class="text-muted">روش ارسال</div>
                                            <div>{{ $group['carrier_name'] }}</div>
                                        </div>
                                    @endif
                                    @if($group['delivery_date'])
                                        @php
                                            $deliveryDate = null;
                                            if($group['delivery_date']) {
                                                try {
                                                    $deliveryDate = jdate($group['delivery_date'])->format('%d %B %Y');
                                                } catch(Exception $e) {
                                                    $deliveryDate = $group['delivery_date'];
                                                }
                                            }
                                        @endphp
                                        <div class="col-md-3 col-6">
                                            <div class="text-muted">تاریخ تحویل</div>
                                            <div>{{ $deliveryDate }}</div>
                                        </div>
                                    @endif
                                    @if($group['tracking_code'])
                                        <div class="col-md-3 col-6">
                                            <div class="text-muted">کد رهگیری</div>
                                            <div class="fw-bold">{{ $group['tracking_code'] }}</div>
                                        </div>
                                    @endif
                                </div>

                                @if($group['cancel_reason'])
                                    <div class="alert alert-warning py-2 small mb-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>دلیل لغو:</strong> {{ $group['cancel_reason'] }}
                                    </div>
                                @endif

                                @if($group['refunded'])
                                    <div class="alert alert-info py-2 small mb-3">
                                        <i class="fas fa-check-circle me-1"></i>
                                        وجه این سفارش به کیف پول شما برگشت داده شده است.
                                    </div>
                                @endif

                                {{-- جمع مرسوله --}}
                                <div class="row g-3 small mb-3 pb-2 border-bottom">
                                    <div class="col-4">
                                        <div class="text-muted">جمع قیمت کالاها</div>
                                        <div>{{ number_format($group['subtotal']) }} تومان</div>
                                    </div>
                                    @if($group['discount'] > 0)
                                        <div class="col-4">
                                            <div class="text-muted">تخفیف</div>
                                            <div class="text-success">-{{ number_format($group['discount']) }} تومان</div>
                                        </div>
                                    @endif
                                    <div class="col-4">
                                        <div class="text-muted">جمع نهایی مرسوله</div>
                                        <div class="fw-bold text-primary">{{ number_format($group['total'] + $group['shipping_cost']-$group['discount']) }} تومان</div>
                                    </div>
                                </div>

                                {{-- لیست محصولات --}}
                                @foreach($group['items'] as $item)

                                    @php
                                        $itemReturnStatus = $item->return_status ?? 'none';
                                        $itemReturnInfo = $returnStatusLabels[$itemReturnStatus] ?? null;
                                        $canRequestReturn = ($order->status == 'paid'
                                            && $item->shipping_status == 'delivered'
                                            && $itemReturnStatus === 'none'
                                            && class_exists(\App\Models\ReturnRequest::class)
                                            && \App\Models\ReturnRequest::isWithinReturnPeriod($item->id));
                                    @endphp
                                    <div class="d-flex gap-3 py-2 border-bottom align-items-start {{ $itemReturnStatus !== 'none' ? 'rounded-3 p-2 mb-2' : '' }}"
                                         style="@if($itemReturnInfo) background:{{ $itemReturnInfo['bg'] }}; border:1px solid {{ $itemReturnInfo['color'] }}33; @endif">
                                        <div class="flex-shrink-0">
                                            @if($item->product)
                                                <a href="{{route('front.products.show',$item->product)}}">
                                                    <img src="{{ $item->product->image ? asset($item->product->image) : '/empty.svg' }}"
                                                         class="rounded-3" style="width: 80px; height: 80px; object-fit: cover;">
                                                </a>

                                            @else
                                                <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1 text-md-right"><a class=" text-black" href="{{route('front.products.show',$item->product)}}">{{ $item->title }}</a>
                                                @if($item->product && $item->product->isPreOrder())
                                                    {{-- فاز ۸۱ — نشان پیش‌فروش در سفارش مشتری --}}
                                                    <span class="badge badge-info align-middle ms-1" style="background-color: #17a2b8; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 0.65rem;">{{ option('dt_pre_order_badge_text', 'پیش‌فروش') }}</span>
                                                @endif
                                            </h6>

                                            {{-- نشان وضعیت مرجوعی --}}
                                            @if($itemReturnInfo)
                                                <div style="margin-bottom:8px;padding:4px 10px;background:{{ $itemReturnInfo['color'] }};color:#fff;border-radius:8px;font-size:0.72rem;font-weight:600;display:inline-block;">
                                                    <i class="fas {{ $itemReturnInfo['icon'] }}"></i> {{ $itemReturnInfo['label'] }}
                                                </div>
                                            @endif

                                            <div class="d-flex flex-column mt-3">
                                                @if($item->attributes)
                                                    @php
                                                        $attributes = json_decode($item->attributes, true);

                                                        // استخراج رنگ از آرایه
                                                        $colorAttributes = [];
                                                        $otherAttributes = [];

                                                        foreach ($attributes as $groupName => $groupAttributes) {
                                                            if ($groupName == 'رنگ') {
                                                                $colorAttributes = [$groupName => $groupAttributes];
                                                            } else {
                                                                $otherAttributes[$groupName] = $groupAttributes;
                                                            }
                                                        }

                                                        // ترکیب نهایی: رنگ اول، سپس بقیه
                                                        $sortedAttributes = array_merge($colorAttributes, $otherAttributes);
                                                    @endphp

                                                    <div>
                                                        @foreach($sortedAttributes as $groupName => $groupAttributes)
                                                            @foreach($groupAttributes as $attribute)
                                                                @if($groupName == 'رنگ')
                                                                    <span class="order-product-attribute d-flex align-items-center gap-1">
                    <span class="order-product-color d-print-none"
                          style="display: inline-block; width: 16px; height: 16px; border-radius: 25%; background-color: {{ $attribute['value'] }};"></span>
                    <span>{{ $groupName }}: {{ $attribute['name'] }}</span>
                </span>
                                                                @elseif($groupName == 'گارانتی')
                                                                    <span class="order-product-attribute">
                    <i class="fas fa-shield-alt me-1"></i>
                    {{ $groupName }}: {{ $attribute['name'] }}
                </span>
                                                                @elseif($groupName == 'سایز')
                                                                    <span class="order-product-attribute">
                    <i class="fas fa-ruler me-1"></i>
                    {{ $groupName }}: {{ $attribute['name'] }}
                </span>
                                                                @else
                                                                    <span class="order-product-attribute">
                    {{ $groupName }}: {{ $attribute['name'] }}
                </span>
                                                                @endif
                                                            @endforeach
                                                        @endforeach

                                                    </div>
                                                @endif
                                                <div class="d-flex flex-wrap gap-3 small">
                                                    <div>
                                                        <span class="text-muted">تعداد:</span>
                                                        <span class="fw-medium">{{ $item->quantity }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted">قیمت واحد:</span>
                                                        <span class="fw-medium">{{ number_format($item->real_price) }} تومان</span>
                                                    </div>
                                                    @if($item->discount > 0)
                                                        <div>
                                                            <span class="text-muted">تخفیف:</span>
                                                            <span class="fw-medium text-success">{{ $item->discount }}%</span>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <span class="text-muted">قیمت نهایی:</span>
                                                        <span class="fw-bold">{{ number_format($item->price * $item->quantity) }} تومان</span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        {{-- ===== کارت مرجوعی (مشابه دیجی‌کالا) ===== --}}
                                        @if(class_exists(\App\Models\ReturnRequest::class))
                                            <div class="w-100 mt-2">
                                                @if($itemReturnStatus === 'none' && $canRequestReturn)
                                                    {{-- محصول قابل مرجوعی است --}}
                                                    <div style="border:1px solid #d1fae5;border-radius:12px;overflow:hidden;">
                                                        <div style="padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                                                            <div style="display:flex;align-items:center;gap:8px;">
                                                                <div style="width:28px;height:28px;border-radius:50%;background:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                                    <i class="fas fa-check text-white" style="font-size:0.7rem;"></i>
                                                                </div>
                                                                <div>
                                                                    <span style="color:#10b981;font-size:0.82rem;font-weight:700;">قابل بازگشت توسط مشتری</span>
                                                                    <div style="font-size:0.72rem;color:#64748b;">مهلت مرجوعی: {{ option('return_days_limit',7) }} روز پس از تحویل</div>
                                                                </div>
                                                            </div>
                                                            <a href="{{ route('front.returns.create', ['order' => $order, 'orderItem' => $item]) }}"
                                                               style="background:#10b981;color:#fff;padding:6px 16px;border-radius:8px;font-size:0.78rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                                                                <i class="fas fa-undo-alt"></i> درخواست مرجوعی
                                                            </a>
                                                        </div>
                                                        <div style="padding:8px 14px;background:#f0fdf4;border-top:1px solid #d1fae5;">
                                                            <small style="color:#64748b;font-size:0.72rem;display:flex;align-items:center;gap:4px;">
                                                                <i class="fas fa-info-circle"></i>
                                                                برای آگاهی از شرایط مرجوعی، قوانین بازگشت کالا را مطالعه کنید.
                                                            </small>
                                                        </div>
                                                    </div>

                                                @elseif($itemReturnStatus === 'none' && !$canRequestReturn)
                                                    {{-- مهلت مرجوعی تمام شده --}}
                                                    <div style="border:1px solid #f1f5f9;border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:8px;">
                                                        <div style="width:28px;height:28px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                            <i class="fas fa-clock text-muted" style="font-size:0.72rem;"></i>
                                                        </div>
                                                        <div>
                                                            <span style="color:#94a3b8;font-size:0.8rem;font-weight:600;">مهلت مرجوعی به پایان رسیده است</span>
                                                            <div style="font-size:0.72rem;color:#94a3b8;">در صورت وجود مشکل با پشتیبانی تماس بگیرید.</div>
                                                        </div>
                                                    </div>

                                                @elseif($itemReturnStatus !== 'none')
                                                    {{-- درخواست مرجوعی ثبت شده --}}
                                                    <div style="border:1px solid {{ $itemReturnInfo['color'] }}33;border-radius:12px;overflow:hidden;background:{{ $itemReturnInfo['bg'] }};">
                                                        <div style="padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                                                            <div style="display:flex;align-items:center;gap:8px;">
                                                                <div style="width:28px;height:28px;border-radius:50%;background:{{ $itemReturnInfo['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                                    <i class="fas {{ $itemReturnInfo['icon'] }} text-white" style="font-size:0.72rem;"></i>
                                                                </div>
                                                                <div>
                                                                    <span style="color:{{ $itemReturnInfo['color'] }};font-size:0.82rem;font-weight:700;">{{ $itemReturnInfo['label'] }}</span>
                                                                    @if($item->returnRequest?->reason)
                                                                        <div style="font-size:0.72rem;color:#64748b;">دلیل: {{ $item->returnRequest->reason->title }}</div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            @if($item->returnRequest)
                                                                <a href="{{ route('front.returns.show', $item->returnRequest) }}"
                                                                   style="background:{{ $itemReturnInfo['color'] }};color:#fff;padding:6px 16px;border-radius:8px;font-size:0.78rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                                                                    <i class="fas fa-eye"></i> مشاهده جزئیات
                                                                </a>
                                                            @endif
                                                        </div>
                                                        @if($itemReturnStatus === 'completed' && $item->returnRequest?->refund_to_wallet)
                                                            <div style="padding:6px 14px;border-top:1px solid {{ $itemReturnInfo['color'] }}22;">
                                                                <small style="color:#10b981;font-size:0.72rem;display:flex;align-items:center;gap:4px;">
                                                                    <i class="fas fa-wallet"></i>
                                                                    مبلغ {{ number_format($item->returnRequest->refund_amount) }} تومان به کیف پول شما برگشت داده شد.
                                                                </small>
                                                            </div>
                                                        @endif
                                                        @if($itemReturnStatus === 'rejected' && $item->returnRequest?->rejection_reason)
                                                            <div style="padding:6px 14px;border-top:1px solid {{ $itemReturnInfo['color'] }}22;">
                                                                <small style="color:#ef4444;font-size:0.72rem;display:flex;align-items:center;gap:4px;">
                                                                    <i class="fas fa-info-circle"></i>
                                                                    دلیل رد: {{ $item->returnRequest->rejection_reason }}
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @if ($item->product && $item->product->isDownload() && $item->get_price && $item->get_price->isDownloadable())
                                        <div class="mt-3 mr-auto">
                                            <a href="{{ $item->get_price->downloadLink() }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download me-1"></i> دانلود محصول
                                            </a>
                                        </div>
                                    @endif
                            </div>
                            @endforeach
                        </div>

                        {{-- فقط برای محصولات فیزیکی وضعیت ارسال نمایش داده شود --}}
                        @if(!($isDownloadGroup ?? false) and $order->status == 'paid')
                            <div class="container-fluid">
                                <div class="order-status-container mb-3">

                                    @if($isCanceled)
                                        {{-- وضعیت لغو شده --}}
                                        <div class="card bg-danger bg-opacity-10 border-0 p-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-ban text-danger fs-5"></i>
                                                <span class="text-white fw-bold">سفارش لغو شده است</span>
                                            </div>
                                            @if($group['cancel_reason'])
                                                <div class="mt-1 small text-muted">
                                                    <i class="fas fa-info-circle me-1"></i> دلیل: {{ $group['cancel_reason'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @else

                                        {{-- نمایش وضعیت عادی --}}
                                        <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                                            <span class="small text-muted">وضعیت سفارش</span>
                                        </div>

                                        {{-- نوار پیشرفت --}}
                                        <div class="progress mb-3" style="height: 8px; background-color: #e2e8f0; border-radius: 10px;">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: {{ $progressPercent }}%; background-color: {{ $progressColor }}; border-radius: 10px; transition: width 0.5s ease;">
                                            </div>
                                        </div>

                                        {{-- مراحل با نام کامل --}}
                                        <div class="row g-0 text-center">
                                            @foreach(['w-pending', 'pending', 'processing', 'waiting', 'sent', 'post-sent', 'delivered'] as $index => $stepKey)
                                                @php
                                                    $stepNumber = $statusSteps[$stepKey]['step'];
                                                    $isCompleted = $stepNumber < $currentStep;
                                                    $isActive = $stepNumber == $currentStep;
                                                    $stepLabel = $statusSteps[$stepKey]['label'];
                                                    $stepIcon = $statusSteps[$stepKey]['icon'];
                                                @endphp
                                                <div class="col" style="flex: 1;">
                                                    <div class="position-relative">
                                                        {{-- دایره وضعیت --}}
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1"
                                                             style="width: 24px; height: 24px;
                                background-color: {{ $isCompleted ? $progressColor : ($isActive ? $progressColor : '#f1f5f9') }};
                                border: 2px solid {{ $isCompleted || $isActive ? $progressColor : '#cbd5e1' }};">
                                                            @if($isCompleted)
                                                                <i class="fas fa-check text-white" style="font-size: 10px;"></i>
                                                            @elseif($isActive)
                                                                <i class="fas {{ $stepIcon }} text-white" style="font-size: 10px;"></i>
                                                            @else
                                                                <i class="fas {{ $stepIcon }} text-muted" style="font-size: 10px;"></i>
                                                            @endif
                                                        </div>

                                                        {{-- متن مرحله --}}
                                                        <div class="small {{ $isActive ? 'fw-bold' : 'text-muted' }} d-none d-sm-block"
                                                             style="color: {{ $isActive ? $progressColor : 'inherit' }};">
                                                            {{ $stepLabel }}
                                                        </div>

                                                        {{-- متن کوتاه برای موبایل --}}
                                                        <div class="small {{ $isActive ? 'fw-bold' : 'text-muted' }} d-sm-none"
                                                             style="color: {{ $isActive ? $progressColor : 'inherit' }};">
                                                            @switch($stepKey)
                                                                @case('w-pending') انتظار @break
                                                                @case('pending') بررسی @break
                                                                @case('processing') پردازش @break
                                                                @case('waiting') آماده @break
                                                                @case('sent') ارسال @break
                                                                @case('post-sent') پست @break
                                                                @case('delivered') تحویل @break
                                                            @endswitch
                                                        </div>

                                                        {{-- نشانگر مرحله فعال --}}
                                                        @if($isActive)
                                                            <div class="small text-success mt-1">
                                                                <i class="fas fa-spinner fa-pulse"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- درصد پیشرفت --}}
                                        <div class="text-center mt-2">
                                            <small class="text-muted">پیشرفت سفارش: {{ round($progressPercent) }}%</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                </div>
                @endforeach

                {{-- تاریخچه تراکنش‌ها --}}
                @if ($order->transactions->count())
                    <div class="card border rounded-3 overflow-hidden">
                        <div class="card-header bg-light py-2" data-bs-toggle="collapse" data-bs-target="#transactions" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-chevron-down small"></i>
                                <span class="fw-bold">تاریخچه تراکنش‌ها</span>
                                <span class="badge bg-secondary">{{ $order->transactions->count() }}</span>
                            </div>
                        </div>
                        <div id="transactions" class="collapse">
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    @foreach($order->transactions()->latest()->get() as $transaction)
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($transaction->status)
                                                        <i class="fas fa-check-circle text-success fs-5"></i>
                                                    @else
                                                        <i class="fas fa-times-circle text-danger fs-5"></i>
                                                    @endif
                                                    <div>
                                                        <div class="fw-bold">
                                                            @if($transaction->status)
                                                                پرداخت موفق
                                                            @else
                                                                پرداخت ناموفق
                                                            @endif
                                                        </div>
                                                        <div class="small text-muted">{{ jdate($transaction->created_at)->format('%d %B %Y H:i:s') }}</div>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-3 small">
                                                    <div>
                                                        <span class="text-muted">درگاه:</span>
                                                        <span>{{ \App\Models\Gateway::find($transaction->gateway_id)->name ?? '-' }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted">شماره پیگیری:</span>
                                                        <span>{{ $transaction->id }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted">مبلغ:</span>
                                                        <span class="fw-bold">{{ number_format($transaction->amount) }} تومان</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>



@endsection

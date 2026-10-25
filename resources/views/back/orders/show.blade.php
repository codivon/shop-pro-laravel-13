@extends('back.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('back/assets/css/pages/order.css') }}">
@endpush

@section('content')

    @php
        use Illuminate\Support\Str;

        // ---------- مپ‌های وضعیت ----------
        $paymentLabels = ['paid' => 'پرداخت شده', 'unpaid' => 'پرداخت نشده', 'canceled' => 'لغو شده'];
        $shippingLabels = [
            'w-pending'  => 'در انتظار بررسی', 'pending' => 'در حال بررسی', 'processing' => 'در حال پردازش',
            'waiting'    => 'منتظر ارسال', 'sent' => 'ارسال شد', 'post-sent' => 'تحویل به پست',
            'delivered'  => 'تحویل داده شد', 'canceled' => 'لغو شد',
        ];
        $statusSteps = ['w-pending', 'pending', 'processing', 'waiting', 'sent', 'post-sent', 'delivered'];

        // ---------- محاسبات کل سفارش (جمع همه آیتم‌ها) ----------
        $grandTotal = 0; $grandDiscount = 0; $grandShipping = 0; $grandPayable = 0;

        $orderTotals = [];
        foreach ($orderItems as $oi) {
            $amount = 0; $discount = 0;
            foreach ($oi->products() as $p) {
                $price   = $p->real_price * $p->quantity;
                $disc    = $price * ($p->discount ?? 0) / 100;
                $amount += $price - $disc;
                $discount += $disc;
            }
            $shipping = $oi->shipping_cost ?? 0;
            $orderTotals[$oi->id] = ['amount' => $amount, 'discount' => $discount, 'shipping' => $shipping, 'payable' => $amount + $shipping];
            $grandTotal += $amount; $grandDiscount += $discount; $grandShipping += $shipping;
        }
        $grandPayable = $grandTotal + $grandShipping;

        $isInstallment = (bool) $installmentPlan;

        $returnStatusMeta = [
            'pending'             => ['مرجوعی در حال بررسی', '#f59e0b', '#fffbeb'],
            'approved'            => ['مرجوعی تایید شد', '#3b82f6', '#dbeafe'],
            'shipped_by_customer' => ['محصول ارسال شد', '#8b5cf6', '#ede9fe'],
            'received'            => ['محصول دریافت شد', '#06b6d4', '#cffafe'],
            'reshipped'           => ['ارسال مجدد', '#6366f1', '#e0e7ff'],
            'completed'           => ['مرجوعی تکمیل شد', '#10b981', '#d1fae5'],
            'rejected'            => ['مرجوعی رد شد', '#ef4444', '#fee2e2'],
            'cancelled'           => ['مرجوعی لغو شد', '#6b7280', '#f3f4f6'],
            'failed'              => ['مرجوعی ناموفق', '#dc2626', '#fef2f2'],
        ];
    @endphp
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb no-border">
                                    <li class="breadcrumb-item">مدیریت
                                    </li>
                                    <li class="breadcrumb-item">مدیریت سفارشات
                                    </li>
                                    <li class="breadcrumb-item active">لیست سفارشات
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="content-body">
    <div class="ov-app">
        {{-- ================= هدر ================= --}}
        <div class="ov-header">
            <div class="ov-header__right">
                <div class="ov-breadcrumb">
                    <span>پنل مدیریت</span><i>›</i>
                    <a href="{{ route('admin.orders.index') }}">سفارش‌ها</a><i>›</i>
                    <span class="active">سفارش #{{ $order->id }}</span>
                </div>
                <h1 class="ov-title">
                    سفارش <span>#{{ $order->id }}</span>
                    @if($isInstallment)
                        <span class="ov-tag" style="--tc:#1e40af;--tbg:#dbeafe;">💰 اقساطی</span>
                    @endif
                    @if($order->status == 'paid')
                        <span class="ov-tag" style="--tc:#047857;--tbg:#d1fae5;">✓ پرداخت شده</span>
                    @elseif($order->status == 'canceled')
                        <span class="ov-tag" style="--tc:#b91c1c;--tbg:#fee2e2;">لغو شده</span>
                    @else
                        <span class="ov-tag" style="--tc:#92400e;--tbg:#fef3c7;">پرداخت نشده</span>
                    @endif
                </h1>
                <div class="ov-header__meta">
                    <span><i class="feather icon-calendar"></i> {{ jdate($order->created_at)->format('Y/m/d - H:i') }}</span>
                    <span><i class="feather icon-user"></i> {{ $order->name ?? $order->user?->fullname ?? '—' }}</span>
                    <span><i class="feather icon-package"></i> {{ $orderItems->count() }} مرسوله</span>
                </div>
            </div>

            <div class="ov-header__actions">
                <a href="{{ route('admin.orders.print', ['order' => $order, 'seller_id' => $orderItems->first()?->seller_id]) }}"
                   target="_blank" class="ov-btn ov-btn--ghost">
                    <i class="feather icon-printer"></i> چاپ
                </a>
                <a href="{{ route('admin.orders.index') }}" class="ov-btn ov-btn--ghost">
                    <i class="feather icon-arrow-right"></i> بازگشت
                </a>
            </div>
        </div>

        {{-- ================= کارت‌های خلاصه ================= --}}
        <div class="ov-stats">
            <div class="ov-stat">
                <div class="ov-stat__icon" style="--c1:#34D399;--c2:#059669;"><i class="feather icon-credit-card"></i></div>
                <div>
                    <span class="ov-stat__value">{{ number_format($isInstallment ? $installmentPlan->total_payable : $grandPayable) }} <small>تومان</small></span>
                    <span class="ov-stat__label">{{ $isInstallment ? 'مبلغ نهایی (اقساطی)' : 'مبلغ قابل پرداخت کل' }}</span>
                </div>
            </div>
            <div class="ov-stat">
                <div class="ov-stat__icon" style="--c1:#818CF8;--c2:#4F46E5;"><i class="feather icon-box"></i></div>
                <div>
                    <span class="ov-stat__value">{{ number_format($orderItems->sum(function($oi) { return $oi->products()->sum('quantity'); })) }}</span>
                    <span class="ov-stat__label">تعداد کل اقلام</span>
                </div>
            </div>
            <div class="ov-stat">
                <div class="ov-stat__icon" style="--c1:#FB7185;--c2:#E11D48;"><i class="feather icon-percent"></i></div>
                <div>
                    <span class="ov-stat__value">{{ number_format($grandDiscount) }} <small>تومان</small></span>
                    <span class="ov-stat__label">جمع تخفیف‌ها</span>
                </div>
            </div>
            <div class="ov-stat">
                <div class="ov-stat__icon" style="--c1:#60A5FA;--c2:#2563EB;"><i class="feather icon-truck"></i></div>
                <div>
                    <span class="ov-stat__value">{{ number_format($grandShipping) }} <small>تومان</small></span>
                    <span class="ov-stat__label">هزینه ارسال</span>
                </div>
            </div>
        </div>

        <div class="ov-layout">
            {{-- ============ ستون اصلی ============ --}}
            <div class="ov-main">

                {{-- ---------- مرسوله‌ها ---------- --}}
                @foreach($orderItems as $oi)
                    @php
                        $stepIndex = array_search($oi->shipping_status, $statusSteps);
                        $scMeta = match($oi->shipping_status) {
                            'sent', 'delivered' => ['#059669', '#ecfdf5'],
                            'canceled'          => ['#dc2626', '#fef2f2'],
                            default             => ['#d97706', '#fffbeb'],
                        };
                        $t = $orderTotals[$oi->id];
                    @endphp

                    <section class="ov-card ov-item-card">
                        <header class="ov-item-head">
                            <div class="ov-item-head__right">
                                <span class="ov-item__icon"><i class="feather icon-package"></i></span>
                                <div>
                                    <h3>مرسوله #{{ $oi->id }}</h3>
                                    <span class="ov-item__sub">
                                    {{ $oi->products()->count() }} محصول
                                    @if($oi->seller)
                                            • فروشنده: {{ $oi->seller->fullname ?? $oi->seller->name ?? '' }}
                                        @endif
                                </span>
                                </div>
                            </div>

                            <div class="ov-item-head__left">
                            <span class="ov-tag" style="--tc:{{ $scMeta[0] }};--tbg:{{ $scMeta[1] }};">
                                {{ $shippingLabels[$oi->shipping_status] ?? $oi->shipping_status }}
                            </span>

                                @if($oi->refunded || $oi->return_status !== 'none')
                                    @php
                                        $rm = $returnStatusMeta[$oi->return_status] ?? ['مرجوع شده', '#f59e0b', '#fffbeb'];
                                    @endphp
                                    <span class="ov-tag" style="--tc:{{ $rm[1] }};--tbg:{{ $rm[2] }};">
                                    <i class="feather icon-rotate-ccw"></i> {{ $rm[0] }}
                                </span>
                                @endif

                                {{-- لینک ورود به صفحه آیتم --}}
                                <a href="{{ route('admin.orders.show-item', $oi) }}"
                                   class="ov-btn ov-btn--sm ov-btn--primary">
                                    <i class="feather icon-eye"></i> مشاهده مرسوله
                                </a>
                            </div>
                        </header>

                        {{-- نوار پیشرفت وضعیت --}}
                        @if($oi->shipping_status != 'canceled' && $stepIndex !== false)
                            <div class="ov-steps">
                                @foreach($statusSteps as $i => $step)
                                    <div class="ov-step {{ $i < $stepIndex ? 'is-done' : ($i == $stepIndex ? 'is-current' : '') }}">
                                        <span class="ov-step__dot"></span>
                                        <span class="ov-step__label">{{ $shippingLabels[$step] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($oi->shipping_status == 'canceled')
                            <div class="ov-alert ov-alert--danger">
                                <i class="feather icon-x-circle"></i>
                                این مرسوله لغو شده است.
                            </div>
                        @endif

                        {{-- محصولات مرسوله --}}
                        <div class="ov-prods">
                            @foreach($oi->products() as $item)
                                @php
                                    $price = $item->real_price * $item->quantity;
                                    $disc  = $price * ($item->discount ?? 0) / 100;
                                    $final = $price - $disc;
                                @endphp
                                <a href="{{ route('front.products.show', $item->product) }}" target="_blank" class="ov-prod">
                                <span class="ov-prod__img">
                                    <img src="{{ $item->product?->image ? asset($item->product->image) : asset('empty.svg') }}" alt="">
                                </span>
                                    <span class="ov-prod__body">
                                    <span class="ov-prod__title">{{ $item->title }}
                                        @if($item->product && $item->product->isPreOrder())
                                            {{-- فاز ۸۱ — نشان پیش‌فروش در سفارش پنل ادمین --}}
                                            <span class="badge" style="background-color: #17a2b8; color: #fff; font-size: 0.65rem; border-radius: 8px; vertical-align: middle;">{{ option('dt_pre_order_badge_text', 'پیش‌فروش') }}</span>
                                        @endif
                                    </span>
                                    @if($item->product?->title_fa)
                                            <span class="ov-prod__fa">{{ $item->product->title_fa }}</span>
                                        @endif
                                    <span class="ov-prod__meta">
                                        <span class="ov-qty">× {{ number_format($item->quantity) }}</span>
                                        @if($item->discount)
                                            <span class="ov-off">{{ $item->discount }}%</span>
                                        @endif
                                    </span>
                                </span>
                                    <span class="ov-prod__price">
                                    {{ number_format($final) }} <small>تومان</small>
                                    @if($item->discount)
                                            <del>{{ number_format($price) }}</del>
                                        @endif
                                </span>
                                </a>
                            @endforeach
                        </div>

                        {{-- جمع مرسوله --}}
                        <footer class="ov-item-foot">
                            <span>جمع محصولات: <b>{{ number_format($t['amount']) }}</b> تومان</span>
                            @if($t['shipping'] > 0)
                                <span>ارسال: <b>{{ number_format($t['shipping']) }}</b> تومان</span>
                            @endif
                            @if($t['discount'] > 0)
                                <span class="ov-disc">تخفیف: <b>{{ number_format($t['discount']) }}</b> تومان</span>
                            @endif
                            <span class="ov-item-foot__total">جمع مرسوله: <b>{{ number_format($t['payable']) }}</b> تومان</span>
                        </footer>
                    </section>
                @endforeach

                {{-- ---------- توضیحات ---------- --}}
                @if($order->description)
                    <section class="ov-card">
                        <h2 class="ov-card__title"><i class="feather icon-edit-3"></i> توضیحات سفارش</h2>
                        <div class="ov-desc">{{ $order->description }}</div>
                    </section>
                @endif
            </div>

            {{-- ============ ستون کناری ============ --}}
            <aside class="ov-side">

                {{-- جمع‌بندی مالی --}}
                <section class="ov-card ov-summary">
                    <h2 class="ov-card__title"><i class="feather icon-file-text"></i> صورت‌حساب</h2>
                    <div class="ov-sum-rows">
                        <div class="ov-sum-row"><span>جمع محصولات</span><b>{{ number_format($grandTotal) }} ت</b></div>
                        @if($grandDiscount > 0)
                            <div class="ov-sum-row ov-sum-row--disc"><span>تخفیف</span><b>- {{ number_format($grandDiscount) }} ت</b></div>
                        @endif
                        <div class="ov-sum-row"><span>هزینه ارسال</span><b>{{ number_format($grandShipping) }} ت</b></div>
                        @if($isInstallment)
                            <div class="ov-sum-row"><span>پیش‌پرداخت</span><b>{{ number_format($installmentPlan->down_payment) }} ت</b></div>
                            <div class="ov-sum-row"><span>باقی‌مانده اقساط</span><b>{{ number_format($installmentPlan->remainingAmount()) }} ت</b></div>
                        @endif
                    </div>
                    <div class="ov-sum-total">
                        <span>{{ $isInstallment ? 'مبلغ نهایی (اقساطی)' : 'قابل پرداخت' }}</span>
                        <b>{{ number_format($isInstallment ? $installmentPlan->total_payable : $grandPayable) }} <small>تومان</small></b>
                    </div>
                    @if($isInstallment)
                        <div class="ov-progress">
                            <div class="ov-progress__head">
                                <span>پیشرفت طرح</span><span>{{ $installmentPlan->progressPercent() }}٪</span>
                            </div>
                            <div class="ov-progress__bar"><span style="width:{{ $installmentPlan->progressPercent() }}%"></span></div>
                        </div>
                    @endif
                </section>

                {{-- خریدار --}}
                <section class="ov-card">
                    <h2 class="ov-card__title"><i class="feather icon-user"></i> خریدار</h2>
                    <div class="ov-kv">
                        <div class="ov-kv__row"><span>نام و نام خانوادگی</span><b>{{ $order->name ?? '—' }}</b></div>
                        <div class="ov-kv__row"><span>نام کاربری</span><b>{{ $order->user?->username ?? '—' }}</b></div>
                        <div class="ov-kv__row"><span>موبایل</span><b class="ov-ltr">{{ $order->mobile ?? '—' }}</b></div>
                        <div class="ov-kv__row"><span>ایمیل</span><b class="ov-ltr">{{ $order->user?->email ?? '—' }}</b></div>
                        <div class="ov-kv__row"><span>نحوه پرداخت</span>
                            <b>{{ $isInstallment ? 'اقساطی' : ($order->status == 'paid' ? ($order->gateway == 'wallet' ? 'کیف پول' : $order->gateway) : '—') }}</b>
                        </div>
                    </div>
                    @if($order->user)
                        <a href="{{ route('admin.users.show', $order->user) }}" class="ov-btn ov-btn--ghost ov-btn--block">
                            <i class="feather icon-external-link"></i> پروفایل کاربر
                        </a>
                    @endif
                </section>

                {{-- آدرس --}}
                <section class="ov-card">
                    <h2 class="ov-card__title"><i class="feather icon-map-pin"></i> آدرس ارسال</h2>
                    @if($order->hasPhysicalProduct())
                        <div class="ov-kv">
                            <div class="ov-kv__row"><span>استان / شهر</span><b>{{ $order->province?->name }} — {{ $order->city?->name }}</b></div>
                            <div class="ov-kv__row"><span>کد پستی</span><b class="ov-ltr">{{ $order->postal_code ?? '—' }}</b></div>
                            <div class="ov-kv__row"><span>آدرس</span><b style="line-height:1.7">{{ $order->address ?? '—' }}</b></div>
                            <div class="ov-kv__row"><span>شیوه تحویل</span><b>{{ $order->carrier?->title ?? 'پیک' }}</b></div>
                            <div class="ov-kv__row"><span>تاریخ تحویل</span>
                                <b>{{ $orderItems->first()?->delivery_date ? jdate($orderItems->first()->delivery_date)->format('Y/m/d') : '—' }}</b>
                            </div>
                        </div>
                        @if($order->location)
                            <button type="button" class="ov-btn ov-btn--ghost ov-btn--block show-location-btn"
                                    data-toggle="modal" data-target="#locationMapModal">
                                <i class="feather icon-map"></i> نمایش روی نقشه
                            </button>
                        @endif
                    @else
                        <p class="ov-muted">این سفارش محصول فیزیکی ندارد.</p>
                    @endif
                </section>
            </aside>
        </div>
    </div>
            </div>
        </div>
    </div>


@endsection

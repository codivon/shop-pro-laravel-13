@extends('front::user.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user/returns.css') }}">
    {{-- آیکون‌های وضعیت مرجوعی از مدل با کلاس‌های font-awesome می‌آیند --}}
    <link rel="stylesheet" href="{{ theme_asset('css/font-awesome.min.css') }}">
@endpush

@section('user-content')

@php
    // محاسبه breakdown برای نمایش به کاربر
    // یک نمونه موقت می‌سازیم تا محاسبات رو انجام بده
    $tempReturn = new \App\Models\ReturnRequest([
        'order_id'      => $order->id,
        'order_item_id' => $orderItem->id,
    ]);
    $tempReturn->setRelation('order', $order);
    $tempReturn->setRelation('orderItem', $orderItem);
    $breakdown = $tempReturn->calculateRefundBreakdown();
    $paymentType = $breakdown['payment_type'];
    $walletRefund = $breakdown['wallet_refund_amount'];
    $creditRestore = $breakdown['credit_restore_amount'];
    $totalRefund = $breakdown['refund_amount'];

    $paymentTypeLabels = \App\Models\ReturnRequest::paymentTypeLabels();
    $ptInfo = $paymentTypeLabels[$paymentType] ?? ['label' => $paymentType, 'color' => '#6b7280', 'icon' => 'fa-circle'];
@endphp

<div class="returns-page">

    <div class="returns-header">
        <a href="{{ route('front.orders.show', $order) }}"><i class="bi bi-arrow-right"></i></a>
        <h4><i class="fas fa-undo-alt"></i> درخواست مرجوعی</h4>
    </div>

    @if(session('error'))
    <div class="return-alert return-alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    {{-- Product Banner --}}
    <div class="return-form-card">
        <div class="return-form-card-header">
            <h5><i class="fas fa-box"></i> محصول مرجوعی</h5>
        </div>
        <div class="return-form-card-body">
            <div class="return-product-banner">
                @if($orderItem->product?->image)
                <img src="{{ asset($orderItem->product->image) }}" alt="">
                @else
                <div style="width:70px;height:70px;border-radius:10px;background:#f8fafc;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-image text-muted"></i>
                </div>
                @endif
                <div class="return-product-banner-info">
                    <h6>{{ $orderItem->title }}</h6>
                    <small>سفارش #{{ $order->id }} · تعداد: {{ $orderItem->quantity }} · مبلغ: {{ number_format($orderItem->price * $orderItem->quantity) }} ت</small>
                </div>
            </div>

            {{-- نوع پرداخت --}}
            <div class="payment-type-badge" style="background:{{ $ptInfo['bg'] ?? '#f3f4f6' }};color:{{ $ptInfo['color'] ?? '#6b7280' }};">
                <i class="fas {{ $ptInfo['icon'] ?? 'fa-circle' }}"></i>
                نوع پرداخت سفارش: <strong>{{ $ptInfo['label'] ?? $paymentType }}</strong>
            </div>

            {{-- Summary - نمایش تفکیک مبالغ --}}
            <div class="return-summary-card">
                <div class="return-summary-row">
                    <span class="label">مبلغ محصول (با تخفیف)</span>
                    <span class="value">{{ number_format($orderItem->price * $orderItem->quantity) }} ت</span>
                </div>

                @if($paymentType === 'cash')
                    {{-- سفارش نقدی --}}
                    <div class="return-summary-row">
                        <span class="label">بازگشت به کیف پول</span>
                        <span class="value text-success">{{ number_format($walletRefund) }} ت</span>
                    </div>
                @elseif($paymentType === 'credit')
                    {{-- سفارش اعتباری --}}
                    <div class="return-summary-row">
                        <span class="label">بازگشت به کیف پول (پرداخت‌شده شما)</span>
                        <span class="value text-success">{{ number_format($walletRefund) }} ت</span>
                    </div>
                    <div class="return-summary-row">
                        <span class="label">بازگشت به اعتبار (مسترد شده)</span>
                        <span class="value text-primary">{{ number_format($creditRestore) }} ت</span>
                    </div>
                @elseif($paymentType === 'installment')
                    {{-- سفارش اقساطی --}}
                    <div class="return-summary-row">
                        <span class="label">بازگشت به کیف پول (پرداخت‌شده شما)</span>
                        <span class="value text-success">{{ number_format($walletRefund) }} ت</span>
                    </div>
                @endif

                <div class="return-summary-row">
                    <span class="label">هزینه ارسال</span>
                    <span class="value text-muted">قابل بازگشت نیست</span>
                </div>
                <div class="return-summary-row total">
                    <span class="label">مبلغ کل قابل برگشت</span>
                    <span class="value">{{ number_format($totalRefund) }} ت</span>
                </div>
            </div>

            @if($paymentType !== 'cash')
            {{-- هشدار برای سفارشات اعتباری/اقساطی --}}
            <div class="return-alert return-alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>نکته مهم درباره مرجوعی سفارشات {{ $ptInfo['label'] ?? $paymentType }}:</strong>
                    <ul class="mb-0 mt-1" style="padding-right:16px;">
                        @if($paymentType === 'credit')
                            <li>مبلغ پرداخت‌شده شما (قسط اول + اقساط پرداخت‌شده) × سهم این آیتم به کیف پول شما برمی‌گردد.</li>
                            <li>اعتبار استفاده‌شده برای این آیتم به حساب اعتباری شما برمی‌گردد.</li>
                            <li>اقساط پرداخت‌نشده باقی می‌مانند و باید آن‌ها را پرداخت کنید.</li>
                        @elseif($paymentType === 'installment')
                            <li>مبلغ پرداخت‌شده شما (پیش‌پرداخت + اقساط پرداخت‌شده) × سهم این آیتم به کیف پول شما برمی‌گردد.</li>
                            <li>اقساط پرداخت‌نشده باقی می‌مانند و باید آن‌ها را پرداخت کنید.</li>
                        @endif
                        <li>هزینه ارسال هرگز قابل بازگشت نیست.</li>
                    </ul>
                </div>
            </div>
            @endif

            <form action="{{ route('front.returns.store', ['order' => $order, 'orderItem' => $orderItem]) }}" method="POST" enctype="multipart/form-data" id="return-form">
                @csrf

                {{-- Reason --}}
                <div class="return-form-group">
                    <label class="return-form-label">دلیل مرجوعی <span class="required">*</span></label>
                    <select name="return_reason_id" class="return-form-select" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($reasons as $reason)
                        <option value="{{ $reason->id }}" @if(old('return_reason_id') == $reason->id) selected @endif>{{ $reason->title }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Description --}}
                <div class="return-form-group">
                    <label class="return-form-label">توضیحات <span class="required">*</span></label>
                    <textarea name="description" class="return-form-textarea" rows="4" required placeholder="مشکل محصول را به‌طور کامل توضیح دهید...">{{ old('description') }}</textarea>
                </div>

                {{-- Images --}}
                <div class="return-form-group">
                    <label class="return-form-label">تصاویر محصول <small style="color:#94a3b8;font-weight:400;">(اختیاری - حداکثر ۵ تصویر)</small></label>
                    <div class="return-file-upload" id="file-upload-area">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="return-file-upload-text">تصاویر را اینجا بکشید یا کلیک کنید</div>
                        <div class="return-file-upload-hint">jpg, png, gif - حداکثر ۵MB هر تصویر</div>
                        <input type="file" name="images[]" multiple accept="image/*" id="file-input">
                    </div>
                    <div class="return-image-previews" id="image-previews"></div>
                </div>

                {{-- Warning --}}
                <div class="return-alert return-alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        با ثبت این درخواست، وضعیت محصول به «در حال بررسی مرجوعی» تغییر می‌کند.
                        پس از تایید نهایی ادمین، مبالغ فوق به شما برگشت داده می‌شود.
                        هزینه ارسال قابل بازگشت نیست.
                    </div>
                </div>

                {{-- Buttons --}}
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="return-submit-btn">
                        <i class="fas fa-paper-plane"></i> ثبت درخواست مرجوعی
                    </button>
                    <a href="{{ route('front.orders.show', $order) }}" class="return-cancel-btn">
                        <i class="fas fa-times"></i> انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/returns-create.js') }}"></script>
@endpush

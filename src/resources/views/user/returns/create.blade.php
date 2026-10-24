{{-- ثبت درخواست مرجوعی — ReturnController@create : $order, $orderItem, $reasons + breakdown (مطابق قرارداد WeblakShop) --}}
@extends('front::user.layouts.master', ['title' => 'درخواست مرجوعی'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    @php
        // محاسبه breakdown برای نمایش به کاربر (هم‌الگوی WeblakShop)
        $tempReturn = new \App\Models\ReturnRequest([
            'order_id'      => $order->id,
            'order_item_id' => $orderItem->id,
        ]);
        $tempReturn->setRelation('order', $order);
        $tempReturn->setRelation('orderItem', $orderItem);
        $breakdown    = $tempReturn->calculateRefundBreakdown();
        $paymentType  = $breakdown['payment_type'];
        $walletRefund = $breakdown['wallet_refund_amount'];
        $creditRestore = $breakdown['credit_restore_amount'] ?? 0;
        $totalRefund  = $breakdown['refund_amount'];
        $ptInfo = \App\Models\ReturnRequest::paymentTypeLabels()[$paymentType] ?? ['label' => $paymentType, 'icon' => 'bi-circle'];
    @endphp

    <div class="content-box mb-3">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('front.orders.show', $order) }}" class="btn btn-sm btn-light border rounded-3" aria-label="بازگشت به سفارش">
                    <i class="bi bi-arrow-right"></i>
                </a>
                <h2 class="font-16 mb-0">
                    <i class="bi bi-arrow-return-left main-color-one-color me-1"></i>
                    درخواست مرجوعی
                </h2>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger rounded-3 font-14" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
        </div>
    @endif

    <div class="content-box">
        <div class="container-fluid">
            {{-- بنر محصول --}}
            <div class="d-flex align-items-center gap-3 flex-wrap border-bottom pb-3 mb-3">
                @if($orderItem->product?->image)
                    <img src="{{ asset($orderItem->product->image) }}" alt="{{ $orderItem->title }}" width="72" height="72" class="rounded-3" loading="lazy">
                @else
                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-light" style="width:72px;height:72px;">
                        <i class="bi bi-image text-muted"></i>
                    </span>
                @endif
                <div class="flex-grow-1 min-w-0">
                    <h3 class="font-14 fw-bold mb-1 text-overflow-1">{{ $orderItem->title }}</h3>
                    <small class="font-12 text-muted">
                        سفارش #{{ $order->id }} · تعداد: {{ $orderItem->quantity }} ·
                        مبلغ: {{ number_format($orderItem->price * $orderItem->quantity) }} تومان
                    </small>
                </div>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2 font-12">
                    <i class="bi {{ $ptInfo['icon'] ?? 'bi-credit-card' }} main-color-one-color me-1"></i>
                    نوع پرداخت: {{ $ptInfo['label'] ?? $paymentType }}
                </span>
            </div>

            {{-- تفکیک مبالغ --}}
            <div class="saniar-summary-card rounded-3 p-3 mb-3">
                <div class="d-flex justify-content-between font-14 py-1">
                    <span class="text-muted">مبلغ محصول (با تخفیف)</span>
                    <strong>{{ number_format($orderItem->price * $orderItem->quantity) }} ت</strong>
                </div>
                @if($paymentType === 'cash')
                    <div class="d-flex justify-content-between font-14 py-1">
                        <span class="text-muted">بازگشت به کیف پول</span>
                        <strong class="text-success">{{ number_format($walletRefund) }} ت</strong>
                    </div>
                @else
                    <div class="d-flex justify-content-between font-14 py-1">
                        <span class="text-muted">بازگشت به کیف پول (پرداخت‌شده شما)</span>
                        <strong class="text-success">{{ number_format($walletRefund) }} ت</strong>
                    </div>
                    @if($paymentType === 'credit')
                        <div class="d-flex justify-content-between font-14 py-1">
                            <span class="text-muted">بازگشت به اعتبار (مسترد شده)</span>
                            <strong class="text-primary">{{ number_format($creditRestore) }} ت</strong>
                        </div>
                    @endif
                @endif
                <div class="d-flex justify-content-between font-14 py-1">
                    <span class="text-muted">هزینه ارسال</span>
                    <span class="text-muted">قابل بازگشت نیست</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between font-15">
                    <span class="fw-bold">مبلغ کل قابل برگشت</span>
                    <strong class="main-color-one-color">{{ number_format($totalRefund) }} ت</strong>
                </div>
            </div>

            @if($paymentType !== 'cash')
                <div class="alert alert-info rounded-3 font-13 line-height-2" role="alert">
                    <strong><i class="bi bi-info-circle me-1"></i>نکته مهم درباره مرجوعی سفارشات {{ $ptInfo['label'] ?? $paymentType }}:</strong>
                    <ul class="mb-0 mt-1" style="padding-right:16px;">
                        <li>مبلغ پرداخت‌شده شما × سهم این آیتم به کیف پول شما برمی‌گردد.</li>
                        @if($paymentType === 'credit')
                            <li>اعتبار استفاده‌شده برای این آیتم به حساب اعتباری شما برمی‌گردد.</li>
                        @endif
                        <li>اقساط پرداخت‌نشده باقی می‌مانند و باید آن‌ها را پرداخت کنید.</li>
                        <li>هزینه ارسال هرگز قابل بازگشت نیست.</li>
                    </ul>
                </div>
            @endif

            <form action="{{ route('front.returns.store', ['order' => $order, 'orderItem' => $orderItem]) }}"
                  method="POST" enctype="multipart/form-data" id="return-form" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label font-14 fw-bold" for="return_reason_id">دلیل مرجوعی <span class="text-danger">*</span></label>
                    <select name="return_reason_id" id="return_reason_id" class="form-select rounded-3" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason->id }}" @if(old('return_reason_id') == $reason->id) selected @endif>{{ $reason->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label font-14 fw-bold" for="return-description">توضیحات <span class="text-danger">*</span></label>
                    <textarea name="description" id="return-description" class="form-control rounded-3" rows="4" required
                              placeholder="مشکل محصول را به‌طور کامل توضیح دهید...">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label font-14 fw-bold" for="return-images">
                        تصاویر محصول <small class="text-muted font-12">(اختیاری - حداکثر ۵ تصویر)</small>
                    </label>
                    <input type="file" name="images[]" multiple accept="image/*" id="return-images" class="form-control rounded-3">
                    <div id="return-image-previews" class="d-flex flex-wrap gap-2 mt-2"></div>
                </div>

                <div class="alert alert-warning rounded-3 font-13 line-height-2" role="alert">
                    <i class="bi bi-info-circle me-1"></i>
                    با ثبت این درخواست، وضعیت محصول به «در حال بررسی مرجوعی» تغییر می‌کند.
                    پس از تایید نهایی ادمین، مبالغ فوق به شما برگشت داده می‌شود. هزینه ارسال قابل بازگشت نیست.
                </div>

                <div id="return-form-error" class="font-12 text-danger d-none mb-2" role="alert"></div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn border-0 main-color-one-bg text-white rounded-3 px-5">
                        <i class="bi bi-send me-1"></i> ثبت درخواست مرجوعی
                    </button>
                    <a href="{{ route('front.orders.show', $order) }}" class="btn btn-light border rounded-3 px-4">انصراف</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush

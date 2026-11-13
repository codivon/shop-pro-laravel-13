@extends('front::user.layouts.master')

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

<style>
    .returns-page { padding: 0; }
    .returns-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
    .returns-header a { color: #64748b; font-size: 1.2rem; text-decoration: none; }
    .returns-header a:hover { color: #1e293b; }
    .returns-header h4 { font-size: 1.3rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
    .returns-header h4 i { color: #f59e0b; }

    .payment-type-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;
        background: {{ $ptInfo['bg'] ?? '#f3f4f6' }};
        color: {{ $ptInfo['color'] ?? '#6b7280' }};
        margin-bottom: 16px;
    }

    .return-form-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 16px; border: 1px solid #f1f5f9; }
    .return-form-card-header { padding: 16px 20px; border-bottom: 1px solid #f8fafc; background: #fcfcfd; }
    .return-form-card-header h5 { margin: 0; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .return-form-card-header h5 i { color: #f59e0b; }
    .return-form-card-body { padding: 20px; }

    .return-product-banner { display: flex; gap: 14px; align-items: center; padding: 14px; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; margin-bottom: 20px; }
    .return-product-banner img { width: 70px; height: 70px; border-radius: 10px; object-fit: cover; flex-shrink: 0; border: 2px solid #e2e8f0; }
    .return-product-banner-info h6 { margin: 0 0 4px; font-size: 0.92rem; font-weight: 700; color: #1e293b; }
    .return-product-banner-info small { font-size: 0.78rem; color: #64748b; }

    .return-form-group { margin-bottom: 20px; }
    .return-form-label { display: block; font-size: 0.85rem; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
    .return-form-label .required { color: #ef4444; }
    .return-form-select { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.88rem; color: #1e293b; background: #fff; transition: border-color 0.2s; appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: left 14px center; background-size: 16px; padding-left: 40px; }
    .return-form-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .return-form-textarea { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.88rem; color: #1e293b; resize: vertical; min-height: 100px; transition: border-color 0.2s; }
    .return-form-textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

    .return-file-upload { position: relative; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 28px; text-align: center; transition: all 0.2s; cursor: pointer; background: #f8fafc; }
    .return-file-upload:hover { border-color: #6366f1; background: #eef2ff; }
    .return-file-upload i { font-size: 2rem; color: #94a3b8; margin-bottom: 8px; }
    .return-file-upload:hover i { color: #6366f1; }
    .return-file-upload-text { font-size: 0.85rem; color: #64748b; font-weight: 600; }
    .return-file-upload-hint { font-size: 0.72rem; color: #94a3b8; margin-top: 4px; }
    .return-file-upload input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

    .return-image-previews { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
    .return-image-preview { position: relative; width: 80px; height: 80px; border-radius: 10px; overflow: hidden; border: 2px solid #e2e8f0; }
    .return-image-preview img { width: 100%; height: 100%; object-fit: cover; }
    .return-image-preview-remove { position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; border-radius: 50%; background: rgba(239,68,68,0.9); color: #fff; border: none; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }

    .return-alert { border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; font-size: 0.82rem; display: flex; gap: 8px; align-items: flex-start; }
    .return-alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
    .return-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .return-alert-info { background: #eef2ff; border: 1px solid #c7d2fe; color: #3730a3; }

    .return-summary-card { background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #a7f3d0; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; }
    .return-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 0.85rem; }
    .return-summary-row .label { color: #64748b; }
    .return-summary-row .value { font-weight: 700; color: #1e293b; }
    .return-summary-row.total { border-top: 1px dashed #a7f3d0; margin-top: 6px; padding-top: 8px; }
    .return-summary-row.total .value { font-size: 1.1rem; color: #10b981; }
    .return-summary-row .text-success { color: #10b981; }
    .return-summary-row .text-primary { color: #6366f1; }
    .return-summary-row .text-muted { color: #94a3b8; }

    .return-submit-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 10px; font-size: 0.9rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; box-shadow: 0 4px 12px rgba(245,158,11,0.25); }
    .return-submit-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(245,158,11,0.35); }
    .return-cancel-btn { display: inline-flex; align-items: center; gap: 6px; padding: 12px 28px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; text-decoration: none; border: 1.5px solid #e2e8f0; color: #64748b; background: #fff; transition: all 0.15s; }
    .return-cancel-btn:hover { background: #f8fafc; color: #475569; }
</style>

<div class="returns-page">

    <div class="returns-header">
        <a href="{{ route('front.orders.show', $order) }}"><i class="fas fa-arrow-right"></i></a>
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
            <div class="payment-type-badge">
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

<script>
(function () {
    var fileInput = document.getElementById('file-input');
    var previewContainer = document.getElementById('image-previews');
    var uploadArea = document.getElementById('file-upload-area');
    var maxFiles = 5;

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            previewContainer.innerHTML = '';
            var files = Array.from(this.files).slice(0, maxFiles);

            if (files.length > maxFiles) {
                alert('حداکثر ' + maxFiles + ' تصویر می‌توانید آپلود کنید.');
                this.value = '';
                return;
            }

            files.forEach(function (file, index) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var preview = document.createElement('div');
                    preview.className = 'return-image-preview';
                    preview.innerHTML = '<img src="' + e.target.result + '">' +
                        '<button type="button" class="return-image-preview-remove" data-index="' + index + '"><i class="fas fa-times"></i></button>';
                    previewContainer.appendChild(preview);

                    preview.querySelector('.return-image-preview-remove').addEventListener('click', function () {
                        preview.remove();
                    });
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Drag and drop
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.style.borderColor = '#6366f1';
            this.style.background = '#eef2ff';
        });
        uploadArea.addEventListener('dragleave', function (e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
        });
        uploadArea.addEventListener('drop', function (e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
            if (fileInput) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }
})();
</script>

@endsection

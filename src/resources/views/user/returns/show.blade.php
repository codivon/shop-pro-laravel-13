@extends('front::user.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user/returns.css') }}">
    {{-- آیکون‌های وضعیت مرجوعی از مدل با کلاس‌های font-awesome می‌آیند --}}
    <link rel="stylesheet" href="{{ theme_asset('css/font-awesome.min.css') }}">
@endpush

@section('user-content')

<div class="returns-page">

    <div class="returns-header">
        <a href="{{ route('front.returns.index') }}"><i class="bi bi-arrow-right"></i></a>
        <h4><i class="fas fa-undo-alt"></i> جزئیات مرجوعی #{{ $returnRequest->id }}</h4>
    </div>

    @if(session('success'))
    <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;gap:8px;align-items:flex-start;">
        <i class="fas fa-check-circle" style="color:#10b981;font-size:1.1rem;"></i>
        <div style="font-size:0.85rem;color:#065f46;">{{ session('success') }}</div>
    </div>
    @endif

    @php
        $statusInfo = \App\Models\ReturnRequest::statusLabels()[$returnRequest->status] ?? ['label' => $returnRequest->status, 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-circle'];
        $item = $returnRequest->orderItem;
        $ptInfo = \App\Models\ReturnRequest::paymentTypeLabels()[$returnRequest->payment_type] ?? ['label' => $returnRequest->payment_type, 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-circle'];
    @endphp

    <div class="return-card">
        <div class="return-card-header">
            <div class="return-card-header-left">
                <a href="{{ route('front.returns.index') }}"><i class="fas fa-chevron-left"></i></a>
                <h6>مرجوعی #{{ $returnRequest->id }}</h6>
            </div>
            <span class="return-status-badge" style="background:{{ $statusInfo['bg'] }};color:{{ $statusInfo['color'] }};">
                <i class="fas {{ $statusInfo['icon'] }}"></i> {{ $statusInfo['label'] }}
            </span>
        </div>

        <div class="return-card-body">
            {{-- Product Info --}}
            <div class="return-product-info">
                <div class="return-product-thumb">
                    @if($item?->product?->image)
                    <img src="{{ asset($item->product->image) }}" alt="">
                    @else
                    <div class="return-product-thumb-placeholder"><i class="fas fa-image"></i></div>
                    @endif
                </div>
                <div>
                    <div class="return-product-name">{{ $item?->title ?? '—' }}</div>
                    <div class="return-product-meta">
                        <span><i class="fas fa-hashtag"></i> سفارش #{{ $returnRequest->order_id }}</span>
                        <span><i class="fas fa-cube"></i> تعداد: {{ $item?->quantity ?? 0 }}</span>
                        <span class="payment-type-chip" style="background:{{ $ptInfo['bg'] }};color:{{ $ptInfo['color'] }};">
                            <i class="fas {{ $ptInfo['icon'] }}"></i> {{ $ptInfo['label'] }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Info Grid --}}
            <div class="return-info-grid">
                <div class="return-info-item">
                    <div class="return-info-label">دلیل مرجوعی</div>
                    <div class="return-info-value">{{ $returnRequest->reason?->title ?? '—' }}</div>
                </div>
                <div class="return-info-item">
                    <div class="return-info-label">مبلغ محصول</div>
                    <div class="return-info-value">{{ number_format($returnRequest->total_item_amount) }} ت</div>
                </div>
                <div class="return-info-item">
                    <div class="return-info-label">تاریخ درخواست</div>
                    <div class="return-info-value">{{ jdate($returnRequest->created_at)->format('Y/m/d H:i') }}</div>
                </div>
            </div>

            {{-- Refund Breakdown --}}
            <div class="refund-breakdown-card">
                <div style="font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-wallet text-success"></i> جزئیات مبلغ بازگشتی
                </div>
                <div class="refund-breakdown-row">
                    <span class="label">مبلغ محصول (با تخفیف)</span>
                    <span class="value">{{ number_format($returnRequest->total_item_amount) }} ت</span>
                </div>

                @if($returnRequest->wallet_refund_amount > 0)
                <div class="refund-breakdown-row">
                    <span class="label">بازگشت به کیف پول</span>
                    <span class="value text-success">{{ number_format($returnRequest->wallet_refund_amount) }} ت</span>
                </div>
                @endif

                @if($returnRequest->credit_restore_amount > 0)
                <div class="refund-breakdown-row">
                    <span class="label">بازگشت به اعتبار</span>
                    <span class="value text-primary">{{ number_format($returnRequest->credit_restore_amount) }} ت</span>
                </div>
                @endif

                <div class="refund-breakdown-row">
                    <span class="label">هزینه ارسال</span>
                    <span class="value text-muted">قابل بازگشت نیست</span>
                </div>

                <div class="refund-breakdown-row total">
                    <span class="label">مبلغ کل قابل برگشت</span>
                    <span class="value">{{ number_format($returnRequest->refund_amount) }} ت</span>
                </div>
            </div>

            @if($returnRequest->payment_type !== 'cash')
            <div style="background:{{ $ptInfo['bg'] }};border:1px solid {{ $ptInfo['color'] }}33;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:0.82rem;color:{{ $ptInfo['color'] }};">
                <i class="fas {{ $ptInfo['icon'] }}"></i>
                <strong>این یک سفارش {{ $ptInfo['label'] }} است:</strong>
                <ul class="mb-0 mt-1" style="padding-right:16px;">
                    @if($returnRequest->payment_type === 'credit')
                        <li>مبلغ پرداخت‌شده شما (قسط اول + اقساط پرداخت‌شده) به کیف پول برمی‌گردد.</li>
                        <li>اعتبار استفاده‌شده برای این آیتم به حساب اعتباری شما برمی‌گردد.</li>
                    @elseif($returnRequest->payment_type === 'installment')
                        <li>مبلغ پرداخت‌شده شما (پیش‌پرداخت + اقساط پرداخت‌شده) به کیف پول برمی‌گردد.</li>
                    @endif
                    <li>هزینه ارسال قابل بازگشت نیست.</li>
                </ul>
            </div>
            @endif

            {{-- Timeline --}}
            <div class="return-timeline">
                <div class="return-timeline-title"><i class="fas fa-route"></i> روند بررسی</div>
                <div class="return-timeline-track">
                    @php
                        $timeline = [];
                        $timeline[] = ['label' => 'ثبت درخواست', 'date' => $returnRequest->created_at, 'status' => 'done'];
                        if ($returnRequest->approved_at) $timeline[] = ['label' => 'تایید اولیه', 'date' => $returnRequest->approved_at, 'status' => 'done'];
                        if ($returnRequest->received_at) $timeline[] = ['label' => 'محصول دریافت شد', 'date' => $returnRequest->received_at, 'status' => 'done'];
                        if ($returnRequest->completed_at) $timeline[] = ['label' => 'تایید نهایی و بازگشت وجه', 'date' => $returnRequest->completed_at, 'status' => 'done'];
                        if ($returnRequest->rejected_at) $timeline[] = ['label' => 'رد درخواست', 'date' => $returnRequest->rejected_at, 'status' => 'rejected'];
                        if ($returnRequest->cancelled_at) $timeline[] = ['label' => 'لغو توسط کاربر', 'date' => $returnRequest->cancelled_at, 'status' => 'rejected'];

                        $lastIndex = count($timeline) - 1;
                    @endphp
                    @foreach($timeline as $index => $step)
                    <div class="return-timeline-item">
                        <div class="return-timeline-dot {{ $index === $lastIndex ? ($step['status'] === 'rejected' ? 'rejected' : 'active') : $step['status'] }}"></div>
                        @if($index < $lastIndex)<div class="return-timeline-line"></div>@endif
                        <div class="return-timeline-content">
                            <div class="return-timeline-label">{{ $step['label'] }}</div>
                            <div class="return-timeline-date">{{ jdate($step['date'])->format('Y/m/d H:i') }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Description --}}
            @if($returnRequest->description)
            <div class="return-description-box">
                <div class="return-description-label"><i class="fas fa-comment-dots"></i> توضیحات شما</div>
                <div class="return-description-text">{{ $returnRequest->description }}</div>
            </div>
            @endif

            {{-- Images --}}
            @if($returnRequest->images->count())
            <div class="return-images-section">
                <div class="return-images-label"><i class="fas fa-images"></i> تصاویر ارسالی ({{ $returnRequest->images->count() }})</div>
                <div class="return-images-gallery">
                    @foreach($returnRequest->images as $image)
                    <a href="{{ Storage::url($image->path) }}" target="_blank" class="return-image-item">
                        <img src="{{ Storage::url($image->path) }}" alt="">
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Admin Notes --}}
            @if($returnRequest->admin_notes)
            <div class="return-admin-notes">
                <div class="return-admin-notes-label"><i class="fas fa-user-shield"></i> یادداشت مدیریت</div>
                <div class="return-admin-notes-text">{{ $returnRequest->admin_notes }}</div>
            </div>
            @endif

            {{-- Rejection --}}
            @if($returnRequest->rejection_reason)
            <div class="return-rejection-alert">
                <i class="fas fa-times-circle"></i>
                <div><strong>دلیل رد:</strong> {{ $returnRequest->rejection_reason }}</div>
            </div>
            @endif

            {{-- Actions --}}
            @if($returnRequest->canBeCancelled())
            <div class="return-actions">
                <form action="{{ route('front.returns.cancel', $returnRequest) }}" method="POST" onsubmit="return confirm('آیا از لغو درخواست مرجوعی مطمئن هستید؟')">
                    @csrf
                    <button type="submit" class="return-btn return-btn-danger">
                        <i class="fas fa-times"></i> لغو درخواست
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

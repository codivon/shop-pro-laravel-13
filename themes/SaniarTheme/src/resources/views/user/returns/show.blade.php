@extends('front::user.layouts.master')

@section('user-content')

<style>
    .returns-page { padding: 0; }
    .returns-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
    .returns-header a { color: #64748b; font-size: 1.2rem; text-decoration: none; }
    .returns-header a:hover { color: #1e293b; }
    .returns-header h4 { font-size: 1.3rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
    .returns-header h4 i { color: #f59e0b; }

    .return-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 16px; border: 1px solid #f1f5f9; }
    .return-card-header { padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; border-bottom: 1px solid #f8fafc; background: #fcfcfd; }
    .return-card-header-left { display: flex; align-items: center; gap: 10px; }
    .return-card-header-left a { color: #94a3b8; font-size: 1.1rem; text-decoration: none; }
    .return-card-header-left h6 { margin: 0; font-size: 0.95rem; font-weight: 700; color: #1e293b; }
    .return-status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 6px 16px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; white-space: nowrap; }
    .return-card-body { padding: 20px; }

    .return-product-info { display: flex; gap: 14px; align-items: center; padding-bottom: 16px; border-bottom: 1px solid #f8fafc; margin-bottom: 16px; }
    .return-product-thumb { width: 80px; height: 80px; border-radius: 12px; overflow: hidden; border: 2px solid #f1f5f9; flex-shrink: 0; }
    .return-product-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .return-product-thumb-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #94a3b8; }
    .return-product-name { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
    .return-product-meta { font-size: 0.78rem; color: #64748b; display: flex; gap: 8px; flex-wrap: wrap; }

    .return-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .return-info-item { background: #f8fafc; border-radius: 10px; padding: 12px 14px; border: 1px solid #f1f5f9; }
    .return-info-label { font-size: 0.72rem; color: #64748b; margin-bottom: 3px; }
    .return-info-value { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
    .return-info-value.success { color: #10b981; }
    .return-info-value.primary { color: #6366f1; }

    .payment-type-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 12px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;
    }

    .refund-breakdown-card {
        background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
        border: 1px solid #a7f3d0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
    }
    .refund-breakdown-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 0.85rem; }
    .refund-breakdown-row .label { color: #64748b; }
    .refund-breakdown-row .value { font-weight: 700; color: #1e293b; }
    .refund-breakdown-row.total { border-top: 1px dashed #a7f3d0; margin-top: 6px; padding-top: 8px; }
    .refund-breakdown-row.total .value { font-size: 1.1rem; color: #10b981; }
    .refund-breakdown-row .text-success { color: #10b981; }
    .refund-breakdown-row .text-primary { color: #6366f1; }
    .refund-breakdown-row .text-muted { color: #94a3b8; }

    .return-description-box { background: #f8fafc; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; border: 1px solid #f1f5f9; }
    .return-description-label { font-size: 0.75rem; color: #64748b; margin-bottom: 6px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
    .return-description-text { font-size: 0.85rem; color: #334155; line-height: 1.7; }

    .return-images-section { margin-bottom: 16px; }
    .return-images-label { font-size: 0.75rem; color: #64748b; margin-bottom: 8px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
    .return-images-gallery { display: flex; gap: 10px; flex-wrap: wrap; }
    .return-image-item { width: 90px; height: 90px; border-radius: 10px; overflow: hidden; border: 2px solid #f1f5f9; position: relative; transition: transform 0.2s; }
    .return-image-item:hover { transform: scale(1.05); border-color: #c7d2fe; }
    .return-image-item img { width: 100%; height: 100%; object-fit: cover; }

    .return-admin-notes { background: #eef2ff; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; border: 1px solid #c7d2fe; }
    .return-admin-notes-label { font-size: 0.75rem; color: #4338ca; margin-bottom: 6px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
    .return-admin-notes-text { font-size: 0.85rem; color: #3730a3; line-height: 1.7; }

    .return-rejection-alert { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; display: flex; gap: 10px; align-items: flex-start; }
    .return-rejection-alert i { color: #ef4444; font-size: 1.1rem; margin-top: 2px; }
    .return-rejection-alert div { font-size: 0.85rem; color: #991b1b; }
    .return-rejection-alert strong { display: block; margin-bottom: 2px; }

    .return-timeline { margin-bottom: 20px; }
    .return-timeline-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
    .return-timeline-track { position: relative; padding-right: 24px; }
    .return-timeline-item { position: relative; padding-bottom: 20px; padding-right: 20px; }
    .return-timeline-item:last-child { padding-bottom: 0; }
    .return-timeline-dot { position: absolute; right: 0; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: #e2e8f0; border: 3px solid #fff; box-shadow: 0 0 0 2px #e2e8f0; z-index: 1; }
    .return-timeline-dot.active { background: #4f46e5; box-shadow: 0 0 0 2px #c7d2fe; }
    .return-timeline-dot.done { background: #10b981; box-shadow: 0 0 0 2px #a7f3d0; }
    .return-timeline-dot.rejected { background: #ef4444; box-shadow: 0 0 0 2px #fecaca; }
    .return-timeline-line { position: absolute; right: 6px; top: 16px; bottom: -4px; width: 2px; background: #e2e8f0; }
    .return-timeline-item:last-child .return-timeline-line { display: none; }
    .return-timeline-content { font-size: 0.82rem; }
    .return-timeline-label { font-weight: 700; color: #1e293b; margin-bottom: 2px; }
    .return-timeline-date { color: #94a3b8; font-size: 0.72rem; }

    .return-actions { display: flex; gap: 10px; flex-wrap: wrap; padding-top: 16px; border-top: 1px solid #f8fafc; }
    .return-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.15s; }
    .return-btn-danger { background: #fef2f2; color: #ef4444; border: 1.5px solid #fecaca; }
    .return-btn-danger:hover { background: #ef4444; color: #fff; }
    .return-btn-outline { background: transparent; color: #6366f1; border: 1.5px solid #c7d2fe; }
    .return-btn-outline:hover { background: #eef2ff; }
</style>

<div class="returns-page">

    <div class="returns-header">
        <a href="{{ route('front.returns.index') }}"><i class="fas fa-arrow-right"></i></a>
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

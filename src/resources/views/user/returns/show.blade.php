{{-- جزئیات مرجوعی — ReturnController@show : $returnRequest (timeline از approved_at/received_at/completed_at/rejected_at/cancelled_at/shipped_by_customer) --}}
@extends('front::user.layouts.master', ['title' => 'مرجوعی #' . $returnRequest->id])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    @php
        $statusInfo = \App\Models\ReturnRequest::statusLabels()[$returnRequest->status] ?? ['label' => $returnRequest->status, 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-circle'];
        $statusIcon = str_replace(['fa-money-bill-wave', 'fa-'], ['bi-cash', 'bi-'], $statusInfo['icon']);
        $item  = $returnRequest->orderItem;
        $ptInfo = \App\Models\ReturnRequest::paymentTypeLabels()[$returnRequest->payment_type] ?? ['label' => $returnRequest->payment_type, 'color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-circle'];
        $ptIcon = str_replace(['fa-money-bill-wave', 'fa-'], ['bi-cash', 'bi-'], $ptInfo['icon']);

        $timeline = [['label' => 'ثبت درخواست', 'date' => $returnRequest->created_at, 'status' => 'done']];
        if ($returnRequest->approved_at)    $timeline[] = ['label' => 'تایید اولیه — ارسال محصول', 'date' => $returnRequest->approved_at, 'status' => 'done'];
        if ($returnRequest->customer_shipped_at) $timeline[] = ['label' => 'محصول توسط شما ارسال شد', 'date' => $returnRequest->customer_shipped_at, 'status' => 'done'];
        if ($returnRequest->received_at)    $timeline[] = ['label' => 'محصول دریافت شد', 'date' => $returnRequest->received_at, 'status' => 'done'];
        if ($returnRequest->completed_at)   $timeline[] = ['label' => 'تایید نهایی و بازگشت وجه', 'date' => $returnRequest->completed_at, 'status' => 'done'];
        if ($returnRequest->rejected_at)    $timeline[] = ['label' => 'رد درخواست', 'date' => $returnRequest->rejected_at, 'status' => 'rejected'];
        if ($returnRequest->cancelled_at)   $timeline[] = ['label' => 'لغو توسط کاربر', 'date' => $returnRequest->cancelled_at, 'status' => 'rejected'];
        $lastIndex = count($timeline) - 1;
    @endphp

    <div class="content-box mb-3">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('front.returns.index') }}" class="btn btn-sm btn-light border rounded-3" aria-label="بازگشت به لیست مرجوعی">
                    <i class="bi bi-arrow-right"></i>
                </a>
                <h2 class="font-16 mb-0">مرجوعی #{{ $returnRequest->id }}</h2>
            </div>
            <span class="badge rounded-pill px-3 py-2 font-12"
                  style="background:{{ $statusInfo['bg'] }};color:{{ $statusInfo['color'] }};">
                <i class="bi {{ $statusIcon }} me-1"></i> {{ $statusInfo['label'] }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 font-14" role="alert">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    <div class="content-box mb-3">
        <div class="container-fluid">
            {{-- اطلاعات محصول --}}
            <div class="d-flex align-items-center gap-3 flex-wrap border-bottom pb-3 mb-3">
                @if($item?->product?->image)
                    <img src="{{ asset($item->product->image) }}" alt="{{ $item?->title ?? 'محصول' }}" width="72" height="72" class="rounded-3" loading="lazy">
                @else
                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-light" style="width:72px;height:72px;">
                        <i class="bi bi-image text-muted"></i>
                    </span>
                @endif
                <div class="flex-grow-1 min-w-0">
                    <h3 class="font-14 fw-bold mb-1 text-overflow-1">{{ $item?->title ?? '—' }}</h3>
                    <div class="d-flex flex-wrap gap-3 font-12 text-muted">
                        <span><i class="bi bi-hash me-1"></i>سفارش #{{ $returnRequest->order_id }}</span>
                        <span><i class="bi bi-box me-1"></i>تعداد: {{ $item?->quantity ?? 0 }}</span>
                        <span class="badge rounded-pill" style="background:{{ $ptInfo['bg'] }};color:{{ $ptInfo['color'] }};">
                            <i class="bi {{ $ptIcon }} me-1"></i> {{ $ptInfo['label'] }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- مبالغ --}}
            <div class="row g-2 font-14">
                <div class="col-md-4 col-6">
                    <p class="text-muted mb-1 font-12">مبلغ کل برگشتی</p>
                    <strong class="main-color-one-color">{{ number_format($returnRequest->refund_amount) }} تومان</strong>
                </div>
                <div class="col-md-4 col-6">
                    <p class="text-muted mb-1 font-12">بازگشت به کیف پول</p>
                    <strong class="text-success">{{ $returnRequest->wallet_refund_amount > 0 ? number_format($returnRequest->wallet_refund_amount) . ' تومان' : '—' }}</strong>
                </div>
                <div class="col-md-4 col-6">
                    <p class="text-muted mb-1 font-12">بازگشت اعتبار</p>
                    <strong>{{ $returnRequest->credit_restore_amount > 0 ? number_format($returnRequest->credit_restore_amount) . ' تومان' : '—' }}</strong>
                </div>
            </div>

            {{-- روند بررسی (timeline) --}}
            <div class="mt-4">
                <h3 class="font-14 fw-bold mb-3"><i class="bi bi-signpost-split main-color-one-color me-1"></i>روند بررسی</h3>
                <div class="saniar-timeline">
                    @foreach($timeline as $index => $step)
                        <div class="saniar-timeline-item">
                            <span class="saniar-timeline-dot {{ $step['status'] === 'rejected' ? 'rejected' : ($index < $lastIndex ? 'done' : 'active') }}"></span>
                            <div>
                                <div class="font-13 fw-bold">{{ $step['label'] }}</div>
                                <div class="font-12 text-muted">{{ \Morilog\Jalali\Jalalian::fromDateTime($step['date'])->format('Y/m/d — H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- توضیحات کاربر --}}
            @if($returnRequest->description)
                <div class="mt-3 bg-light rounded-3 p-3">
                    <div class="font-13 fw-bold mb-1"><i class="bi bi-chat-dots main-color-one-color me-1"></i>توضیحات شما</div>
                    <p class="font-13 text-muted mb-0">{{ $returnRequest->description }}</p>
                </div>
            @endif

            {{-- تصاویر --}}
            @if($returnRequest->images->count())
                <div class="mt-3">
                    <div class="font-13 fw-bold mb-2">
                        <i class="bi bi-images main-color-one-color me-1"></i>تصاویر ارسالی ({{ $returnRequest->images->count() }})
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($returnRequest->images as $image)
                            <a href="{{ Storage::url($image->path) }}" target="_blank" rel="noopener" class="d-block">
                                <img src="{{ Storage::url($image->path) }}" alt="تصویر مرجوعی {{ $loop->iteration }}" width="72" height="72" class="rounded-3 img-thumbnail" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- یادداشت مدیریت --}}
            @if($returnRequest->admin_notes)
                <div class="alert alert-info rounded-3 font-13 mt-3 mb-0" role="alert">
                    <strong><i class="bi bi-person-shield me-1"></i>یادداشت مدیریت:</strong>
                    {{ $returnRequest->admin_notes }}
                </div>
            @endif

            {{-- دلیل رد --}}
            @if($returnRequest->rejection_reason)
                <div class="alert alert-danger rounded-3 font-13 mt-3 mb-0" role="alert">
                    <i class="bi bi-x-circle me-1"></i>
                    <strong>دلیل رد:</strong> {{ $returnRequest->rejection_reason }}
                </div>
            @endif
        </div>
    </div>

    {{-- عملیات --}}
    @if($returnRequest->canBeCancelled() || ($returnRequest->status == 'approved'))
        <div class="content-box">
            <div class="container-fluid d-flex gap-2 flex-wrap">
                @if($returnRequest->status == 'approved')
                    <form action="{{ route('front.returns.mark_shipped', $returnRequest) }}" method="POST"
                          onsubmit="return confirm('محصول را برای بازگشت ارسال کردید؟');">
                        @csrf
                        <button type="submit" class="btn border-0 main-color-one-bg text-white rounded-3 px-4">
                            <i class="bi bi-truck me-1"></i> محصول را ارسال کردم
                        </button>
                    </form>
                @endif
                @if($returnRequest->canBeCancelled())
                    <form action="{{ route('front.returns.cancel', $returnRequest) }}" method="POST"
                          onsubmit="return confirm('آیا از لغو درخواست مرجوعی مطمئن هستید؟');">
                        @csrf
                        <button type="submit" class="btn btn-danger rounded-3 px-4">
                            <i class="bi bi-x me-1"></i> لغو درخواست
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@php
    // This partial is loaded via AJAX into #history-detail (see index.blade.php).
    // Styles (.ump-detail, .ump-badge, ...) live in index.blade.php @push('styles').
    $item = $message->items()->first();
    $status = $item ? $item->status : 'unseen';
    $isSeen = $status === 'seen';
@endphp

<div class="ump-detail">
    {{-- Head: eyebrow + title + status --}}
    <div class="ump-detail-head">
        <div>
            <span class="ump-detail-eyebrow">{{ trans('front::messages.profile.message-details') }}</span>
            <h3 class="ump-detail-title">{{ $message->title }}</h3>
        </div>
        @if($isSeen)
            <span class="ump-badge seen"><span class="dot"></span> دیده‌شده</span>
        @else
            <span class="ump-badge unseen"><span class="dot"></span> دیده‌نشده</span>
        @endif
    </div>

    {{-- Body: description --}}
    <div class="ump-detail-body">{{ $message->description }}</div>

    {{-- Meta: id / date / status --}}
    <div class="ump-detail-meta">
        <div class="ump-meta-item">
            <span class="lbl">{{ trans('front::messages.wallet.id') }}</span>
            <span class="val">#{{ $message->id }}</span>
        </div>
        <div class="ump-meta-item">
            <span class="lbl">{{ trans('front::messages.wallet.history') }}</span>
            <span class="val ltr">{{ jdate($message->created_at) }}</span>
        </div>
        <div class="ump-meta-item">
            <span class="lbl">{{ trans('front::messages.wallet.state') }}</span>
            <span class="val">@if($isSeen) دیده‌شده @else دیده‌نشده @endif</span>
        </div>
    </div>
</div>

{{-- پارشال AJAX جزئیات پیام — UserController@messages_show : $message (بارگذاری در #message-detail) --}}
@php
    $item = $message->items()->first();
    $isSeen = $item && $item->status === 'seen';
@endphp

<div class="saniar-detail">
    <div class="d-flex align-items-start justify-content-between gap-2 mb-3 flex-wrap">
        <div>
            <span class="font-12 main-color-one-color fw-bold">جزئیات پیام</span>
            <h3 class="font-16 fw-bold mb-0 mt-1">{{ $message->title }}</h3>
        </div>
        <span class="badge {{ $isSeen ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-2 font-12">
            {{ $isSeen ? 'دیده‌شده' : 'دیده‌نشده' }}
        </span>
    </div>

    <div class="saniar-detail-body bg-light rounded-3 p-3 font-14 line-height-2 mb-3">
        {{ $message->description }}
    </div>

    <div class="d-flex flex-wrap gap-2">
        <div class="saniar-meta-item bg-white border rounded-3 px-3 py-2">
            <span class="font-12 text-muted d-block">شناسه</span>
            <strong class="font-14">#{{ $message->id }}</strong>
        </div>
        <div class="saniar-meta-item bg-white border rounded-3 px-3 py-2">
            <span class="font-12 text-muted d-block">تاریخ</span>
            <strong class="font-14 ltr-inline">{{ \Morilog\Jalali\Jalalian::fromDateTime($message->created_at)->format('Y/m/d H:i') }}</strong>
        </div>
        <div class="saniar-meta-item bg-white border rounded-3 px-3 py-2">
            <span class="font-12 text-muted d-block">وضعیت</span>
            <strong class="font-14">{{ $isSeen ? 'دیده‌شده' : 'دیده‌نشده' }}</strong>
        </div>
    </div>
</div>

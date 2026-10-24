{{-- پیام‌های دریافتی — UserController@messages : $messages (title, description, created_at, items()->first()->status), $active --}}
@extends('front::user.layouts.master', ['title' => 'پیام‌های دریافتی'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    @php
        $unreadOnPage = 0;
        foreach ($messages as $m) {
            $it = $m->items()->first();
            if (!$it || $it->status !== 'seen') { $unreadOnPage++; }
        }
    @endphp

    {{-- هدر و خلاصه --}}
    <div class="content-box mb-3">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="saniar-msg-icon d-flex align-items-center justify-content-center rounded-3">
                    <i class="bi bi-envelope-open main-color-one-color font-20"></i>
                </span>
                <div>
                    <h2 class="font-16 mb-0">پیام‌های دریافتی</h2>
                    <p class="font-12 text-muted mb-0">مشاهده و مدیریت پیام‌های دریافتی شما</p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2 font-12">
                    <i class="bi bi-envelope main-color-one-color me-1"></i> {{ $messages->total() }} پیام
                </span>
                @if($unreadOnPage > 0)
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2 font-12">
                        <i class="bi bi-envelope-exclamation me-1"></i> {{ $unreadOnPage }} خوانده‌نشده
                    </span>
                @else
                    <span class="badge bg-success rounded-pill px-3 py-2 font-12">
                        <i class="bi bi-check-all me-1"></i> همه خوانده‌شده
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- لیست پیام‌ها --}}
    <div class="d-flex flex-column gap-2">
        @if($messages->count())
            @foreach ($messages as $message)
                @php
                    $item = $message->items()->first();
                    $isUnread = !$item || $item->status !== 'seen';
                @endphp
                <div class="content-box saniar-msg-card {{ $isUnread ? 'is-unread' : '' }}">
                    <div class="container-fluid d-flex align-items-center gap-2 flex-wrap">
                        <span class="saniar-msg-status d-flex align-items-center justify-content-center rounded-3 flex-shrink-0">
                            <i class="bi {{ $isUnread ? 'bi-envelope' : 'bi-envelope-open' }} font-16"></i>
                        </span>
                        <div class="flex-grow-1 min-w-0">
                            <h3 class="font-14 mb-0 text-truncate fw-bold">{{ $message->title }}</h3>
                            <p class="font-12 text-muted mb-0 ltr-inline">{{ \Morilog\Jalali\Jalalian::fromDateTime($message->created_at)->format('Y/m/d H:i') }}</p>
                        </div>
                        <span class="badge {{ $isUnread ? 'bg-warning text-dark' : 'bg-success' }} rounded-pill px-3 py-2 font-12">
                            {{ $isUnread ? 'دیده‌نشده' : 'دیده‌شده' }}
                        </span>
                        <button type="button" class="btn btn-sm border-0 main-color-one-bg text-white rounded-3 px-3 js-show-message"
                                data-action="{{ route('front.messages.show', ['message' => $message]) }}">
                            مشاهده <i class="bi bi-chevron-left"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        @else
            <div class="content-box">
                <div class="container-fluid text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size:3rem;"></i>
                    <h3 class="font-16 mt-3">چیزی برای نمایش وجود ندارد!</h3>
                    <p class="text-muted font-14 mb-0">پیام جدیدی برای شما ثبت نشده است.</p>
                </div>
            </div>
        @endif
    </div>

    @if($messages->hasPages())
        <div class="my-paginate mt-4">
            {{ $messages->links('front::components.paginate') }}
        </div>
    @endif

    {{-- مودال مشاهده پیام (محتوای AJAX از messages/show) --}}
    <div class="modal fade" id="message-show-modal" tabindex="-1" aria-labelledby="messageShowLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="messageShowLabel">
                        <i class="bi bi-envelope-open main-color-one-color me-1"></i> جزئیات پیام
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div id="message-detail" class="modal-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush

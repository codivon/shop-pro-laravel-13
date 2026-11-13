@extends('front::user.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user/user-messages.css') }}">
@endpush

@section('user-content')

    {{-- پیام ها و اطلاعیه ها — ریسکین‌شده با کامپوننت اطلاعیه قالب سانیار (notification.html) --}}
    <div class="user-msg-page">

        {{-- Header --}}
        <div class="ump-header">
            <div class="ump-header-icon"><i class="bi bi-envelope-paper"></i></div>
            <div>
                <h2>{{ trans('front::messages.profile.all-message') }}</h2>
                <p>مشاهده و مدیریت پیام‌های دریافتی شما</p>
            </div>
        </div>

        @if($messages->count())
            @php
                // Pre-compute statuses in one pass (avoids double queries between summary & list)
                $statuses = [];
                $unreadOnPage = 0;
                foreach ($messages as $m) {
                    $it = $m->items()->first();
                    $st = $it ? $it->status : 'unseen';
                    $statuses[$m->id] = $st;
                    if ($st !== 'seen') { $unreadOnPage++; }
                }
            @endphp

            {{-- Summary chips --}}
            <div class="ump-summary">
                <span class="ump-chip"><i class="bi bi-envelope"></i> <strong>{{ $messages->total() }}</strong> پیام</span>
                @if($unreadOnPage > 0)
                    <span class="ump-chip unread"><i class="bi bi-envelope-exclamation"></i> <strong>{{ $unreadOnPage }}</strong> خوانده‌نشده</span>
                @else
                    <span class="ump-chip"><i class="bi bi-check2-all"></i> همه خوانده‌شده</span>
                @endif
            </div>

            {{-- Messages list --}}
            <div class="ump-list">
                @foreach ($messages as $message)
                    @php
                        $status = $statuses[$message->id] ?? 'unseen';
                        $isUnread = $status !== 'seen';
                    @endphp
                    <div class="ump-card @if($isUnread) is-unread @endif">
                        <div class="ump-card-status">
                            <i class="bi @if($isUnread) bi-envelope-fill @else bi-envelope-open @endif"></i>
                        </div>
                        <div class="ump-card-body">
                            <div class="ump-card-title">{{ $message->title }}</div>
                            <div class="ump-card-date ltr">{{ jdate($message->created_at) }}</div>
                        </div>
                        <div class="ump-card-badge">
                            @if($isUnread)
                                <span class="ump-badge unseen"><span class="dot"></span> دیده‌نشده</span>
                            @else
                                <span class="ump-badge seen"><span class="dot"></span> دیده‌شده</span>
                            @endif
                        </div>
                        <a class="ump-card-link show-history" data-action="{{ route('front.messages.show', ['message' => $message]) }}" data-target="#history-show-modal" onclick="return false;">
                            {{ trans('front::messages.profile.show') }}
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="ump-empty">
                <div class="ump-empty-icon"><i class="bi bi-inbox"></i></div>
                <h5>{{ trans('front::messages.wallet.there-is-nothing-to-show') }}</h5>
                <p>پیام جدیدی برای شما ثبت نشده است.</p>
            </div>
        @endif

        <div class="my-paginate mt-4">
            <nav aria-label="Page navigation">
                {{ $messages->links('front::components.paginate') }}
            </nav>
        </div>

    </div>
@endsection

@include('back.partials.plugins', ['plugins' => ['persian-datepicker','jquery.validate']])

@push('scripts')
    {{-- Show Modal --}}
    <div class="modal fade" id="history-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel21"><i class="bi bi-envelope"></i> {{ trans('front::messages.profile.message-details') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div id="history-detail" class="modal-body"></div>
            </div>
        </div>
    </div>

    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/pages/profile/message.js') }}"></script>
@endpush

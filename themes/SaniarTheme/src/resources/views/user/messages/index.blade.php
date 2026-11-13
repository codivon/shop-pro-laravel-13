@extends('front::user.layouts.master')

@push('styles')
    <style>
        /* Scoped user-messages styles (won't leak into the front theme) */
        .user-msg-page {
            --ump-accent: #0d9488; --ump-accent-600: #0f766e; --ump-accent-50: #f0fdfa;
            --ump-accent-100: #ccfbf1; --ump-accent-200: #99f6e4;
            --ump-text: #1f2937; --ump-muted: #6b7280; --ump-border: #eef0f2; --ump-card: #ffffff;
        }

        /* Header */
        .ump-header { display: flex; align-items: center; gap: .85rem; margin-bottom: 1.1rem; }
        .ump-header-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--ump-accent), var(--ump-accent-600)); color: #fff; box-shadow: 0 8px 18px -10px rgba(13,148,136,.6); flex-shrink: 0; }
        .ump-header-icon i { font-size: 28px; }
        .ump-header h2 { font-size: 1.2rem; font-weight: 800; margin: 0; color: var(--ump-text); line-height: 1.4; }
        .ump-header p { font-size: .8rem; color: var(--ump-muted); margin: .15rem 0 0; }

        /* Summary chips */
        .ump-summary { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
        .ump-chip { display: inline-flex; align-items: center; gap: .4rem; background: #fff; border: 1px solid var(--ump-border); border-radius: 999px; padding: .35rem .85rem; font-size: .78rem; font-weight: 600; color: var(--ump-muted); }
        .ump-chip strong { color: var(--ump-text); font-weight: 800; }
        .ump-chip i { font-size: 16px; color: var(--ump-accent); }
        .ump-chip.unread i { color: #d97706; }

        /* List */
        .ump-list { display: flex; flex-direction: column; gap: .6rem; }

        /* Card */
        .ump-card { position: relative; display: flex; align-items: center; gap: .85rem; background: var(--ump-card); border: 1px solid var(--ump-border); border-radius: 14px; padding: .85rem 1rem; transition: box-shadow .2s, transform .2s, border-color .2s; }
        .ump-card:hover { box-shadow: 0 10px 26px -16px rgba(15,23,42,.25); transform: translateY(-1px); border-color: var(--ump-accent-200); }
        .ump-card.is-unread { background: linear-gradient(90deg, var(--ump-accent-50), #fff 45%); border-color: var(--ump-accent-200); }
        .ump-card.is-unread::before { content: ""; position: absolute; right: 0; top: 14px; bottom: 14px; width: 3px; border-radius: 3px; background: var(--ump-accent); }

        .ump-card-status { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #f4f6f8; color: var(--ump-muted); }
        .ump-card.is-unread .ump-card-status { background: var(--ump-accent-100); color: var(--ump-accent-600); }
        .ump-card-status i { font-size: 22px; }

        .ump-card-body { flex: 1; min-width: 0; }
        .ump-card-title { font-weight: 700; color: var(--ump-text); font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ump-card.is-unread .ump-card-title { font-weight: 800; }
        .ump-card-date { font-size: .75rem; color: var(--ump-muted); margin-top: .15rem; }

        .ump-card-badge { flex-shrink: 0; }
        .ump-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
        .ump-badge .dot { width: 6px; height: 6px; border-radius: 50%; }
        .ump-badge.seen { background: #ecfdf5; color: #047857; } .ump-badge.seen .dot { background: #10b981; }
        .ump-badge.unseen { background: #fffbeb; color: #b45309; } .ump-badge.unseen .dot { background: #f59e0b; }

        .ump-card-link { display: inline-flex; align-items: center; gap: .2rem; flex-shrink: 0; padding: .45rem .75rem; border-radius: 10px; font-size: .78rem; font-weight: 700; color: var(--ump-accent-600); background: var(--ump-accent-50); border: 1px solid var(--ump-accent-200); transition: all .18s; text-decoration: none !important;cursor: pointer }
        .ump-card-link:hover { background: var(--ump-accent); color: #fff !important; border-color: var(--ump-accent); text-decoration: none !important; }
        .ump-card-link i { font-size: 18px; }

        /* Empty state */
        .ump-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5rem; padding: 3rem 1rem; text-align: center; background: #fff; border: 1px dashed var(--ump-border); border-radius: 14px; }
        .ump-empty-icon { width: 78px; height: 78px; border-radius: 20px; background: #f4f6f8; color: #b6bcc4; display: flex; align-items: center; justify-content: center; margin-bottom: .25rem; }
        .ump-empty-icon i { font-size: 40px; }
        .ump-empty h5 { margin: 0; font-weight: 700; color: var(--ump-text); }
        .ump-empty p { margin: 0; font-size: .85rem; color: var(--ump-muted); }

        /* Modal */
        #history-show-modal .modal-content { border: 0; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px -24px rgba(15,23,42,.4); }
        #history-show-modal .modal-header { background: linear-gradient(90deg, var(--ump-accent-50), transparent); border-bottom: 1px solid var(--ump-border); padding: 1.1rem 1.4rem; align-items: center; }
        #history-show-modal .modal-title { font-weight: 800; color: var(--ump-text); display: flex; align-items: center; gap: .5rem; font-size: 1.05rem; }
        #history-show-modal .modal-title i { color: var(--ump-accent); font-size: 22px; }
        #history-show-modal .modal-header .close { padding: .5rem .75rem; font-size: 1.5rem; opacity: .6; }
        #history-show-modal .modal-header .close:hover { opacity: 1; }
        #history-show-modal .modal-body { padding: 0; }

        /* Detail partial (loaded via AJAX) */
        .ump-detail { padding: 1.4rem; }
        .ump-detail-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .ump-detail-eyebrow { font-size: .72rem; font-weight: 700; color: var(--ump-accent-600); letter-spacing: .02em; }
        .ump-detail-title { font-size: 1.15rem; font-weight: 800; color: var(--ump-text); margin: .15rem 0 0; line-height: 1.6; }
        .ump-detail-body { background: #f9fafb; border: 1px solid var(--ump-border); border-radius: 12px; padding: 1rem 1.15rem; color: #374151; line-height: 2; font-size: .92rem; white-space: pre-wrap; margin-bottom: 1rem; }
        .ump-detail-meta { display: flex; flex-wrap: wrap; gap: .6rem; }
        .ump-meta-item { flex: 1; min-width: 130px; display: flex; flex-direction: column; gap: .15rem; background: #fff; border: 1px solid var(--ump-border); border-radius: 10px; padding: .6rem .85rem; }
        .ump-meta-item .lbl { font-size: .72rem; color: var(--ump-muted); }
        .ump-meta-item .val { font-size: .9rem; font-weight: 700; color: var(--ump-text); }

        @media (max-width: 575.98px) {
            .ump-card { flex-wrap: wrap; padding: .8rem; }
            .ump-card-body { order: 3; width: 100%; }
            .ump-card-badge { order: 2; }
            .ump-card-link { order: 4; width: 100%; justify-content: center; }
            .ump-card.is-unread::before { top: 10px; bottom: 10px; }
            .ump-detail-head { flex-direction: column; }
        }
    </style>
@endpush

@section('user-content')
    <!-- Start Content -->
    <div class="user-msg-page col-xl-9 col-lg-8 col-md-8 col-sm-12 headline-profile">

        {{-- Header --}}
        <div class="ump-header">
            <div class="ump-header-icon"><i class="mdi mdi-email-open-multiple-outline"></i></div>
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
                <span class="ump-chip"><i class="mdi mdi-email-multiple-outline"></i> <strong>{{ $messages->total() }}</strong> پیام</span>
                @if($unreadOnPage > 0)
                    <span class="ump-chip unread"><i class="mdi mdi-email-alert-outline"></i> <strong>{{ $unreadOnPage }}</strong> خوانده‌نشده</span>
                @else
                    <span class="ump-chip"><i class="mdi mdi-check-all"></i> همه خوانده‌شده</span>
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
                            <i class="mdi @if($isUnread) mdi-email @else mdi-email-open @endif"></i>
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
                        <a class="ump-card-link show-history" data-action="{{ route('front.messages.show', ['message' => $message]) }}" data-target="#history-show-modal" data-toggle="modal" onclick="return false;">
                            {{ trans('front::messages.profile.show') }}
                            <i class="mdi mdi-chevron-left"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="ump-empty">
                <div class="ump-empty-icon"><i class="mdi mdi-inbox-outline"></i></div>
                <h5>{{ trans('front::messages.wallet.there-is-nothing-to-show') }}</h5>
                <p>پیام جدیدی برای شما ثبت نشده است.</p>
            </div>
        @endif

        <div class="mt-3">
            {{ $messages->links('front::components.paginate') }}
        </div>

    </div>
    <!-- End Content -->
@endsection

@include('back.partials.plugins', ['plugins' => ['persian-datepicker','jquery.validate']])

@push('scripts')
    <!-- Show Modal -->
    <div class="modal fade" id="history-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel21"><i class="mdi mdi-email-outline"></i> {{ trans('front::messages.profile.message-details') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div id="history-detail" class="modal-body"></div>
            </div>
        </div>
    </div>

    <script src="{{ theme_asset('js/pages/profile/message.js') }}"></script>
@endpush

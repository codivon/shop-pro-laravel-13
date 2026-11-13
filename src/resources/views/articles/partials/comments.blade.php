{{-- دیدگاه‌های مقاله — سانیار blog-comments --}}
@php
    // دریافت کوکی لایک‌ها + لیست دیدگاه‌های اصلی
    $likedComments = json_decode(request()->cookie('comment_likes', '[]'), true);
    $mainComments  = $post->mainComments()->orderby('created_at', 'desc')->paginate(10);
@endphp
<div id="article-comments" class="blog-comments">
    <div class="container-fluid">
        <div class="content-box">
            <div class="container-fluid">
                <div class="blog-comment">
                    <div class="row">
                        <div class="col-12">
                            <div class="title-panel">
                                <h6 class="tab-title text-start">
                                    <span>{{ $post->acceptedComments()->count() }}</span>
                                    <span>دیدگاه برای</span>
                                    <span>{{ $post->title }}</span>
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-success hide"></div>

                {{--نظر های تایید نشده--}}
                <div class="user-pending-comments">
                    @if($userPendingComments->count())
                        @foreach($userPendingComments as $comment)
                            <div class="comment-item opacity-50 mb-3" id="comment-{{ $comment->id }}">
                                <div class="comment-item-avatar d-sm-flex d-none">
                                    <div class="comment-item-avatar-img">
                                        <a href="javascript:void(0)" class="rounded-circle">
                                            @if($comment->user)
                                                <img src="{{ $comment->user->image_url }}" class="rounded-circle" width="40" alt="{{ $comment->user->full_name ?? 'ناشناس' }}" loading="lazy">
                                            @else
                                                <img src="{{ $comment->admin->image_url }}" class="rounded-circle" width="40" alt="{{ $comment->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                            @endif
                                        </a>
                                    </div>
                                </div>
                                <div class="comment-item-desc">
                                    <div class="comment-item-avatar d-sm-none d-flex mb-2">
                                        <div class="comment-item-avatar-img">
                                            <a href="javascript:void(0)" class="rounded-circle">
                                                @if($comment->user)
                                                    <img src="{{ $comment->user->image_url }}" class="rounded-circle" width="40" alt="{{ $comment->user->full_name ?? 'ناشناس' }}" loading="lazy">
                                                @else
                                                    <img src="{{ $comment->admin->image_url }}" class="rounded-circle" width="40" alt="{{ $comment->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                                @endif
                                            </a>
                                        </div>
                                    </div>
                                    <div class="comment-item-title">
                                        <div class="comment-date">
                                            <p class="text-muted comment-date-auther">
                                                @if($comment->user)
                                                    {{ $comment->user->full_name ?? 'ناشناس' }}
                                                @else
                                                    {{ $comment->admin->full_name ?? 'ناشناس' }}
                                                @endif
                                            </p>
                                            <p class="text-muted comment-date-speractor">–</p>
                                            <p class="text-muted comment-date-date">{{ jdate($comment->created_at)->format('d F Y') }}</p>
                                        </div>
                                        <small class="alert-warning d-block">
                                            <i class="bi bi-clock"></i> این دیدگاه پس از تایید مدیر نمایش داده می‌شود
                                        </small>
                                    </div>
                                    <div class="comment-item-content">
                                        <p>{{ $comment->body }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                @forelse($mainComments as $comment)
                    <div class="comment-item" id="comment-{{ $comment->id }}">
                        <div class="comment-item-avatar d-sm-flex d-none">
                            <div class="comment-item-avatar-img">
                                <a href="javascript:void(0)" class="rounded-circle">
                                    @if($comment->user)
                                        <img src="{{ $comment->user->image_url }}" class="rounded-circle" width="40" alt="{{ $comment->user->full_name ?? 'ناشناس' }}" loading="lazy">
                                    @else
                                        <img src="{{ $comment->admin->image_url }}" class="rounded-circle" width="40" alt="{{ $comment->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                    @endif
                                </a>
                            </div>
                        </div>
                        <div class="comment-item-desc">
                            <div class="comment-item-avatar d-sm-none d-flex mb-2">
                                <div class="comment-item-avatar-img">
                                    <a href="javascript:void(0)" class="rounded-circle">
                                        @if($comment->user)
                                            <img src="{{ $comment->user->image_url }}" class="rounded-circle" width="40" alt="{{ $comment->user->full_name ?? 'ناشناس' }}" loading="lazy">
                                        @else
                                            <img src="{{ $comment->admin->image_url }}" class="rounded-circle" width="40" alt="{{ $comment->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                        @endif
                                    </a>
                                </div>
                            </div>
                            <div class="comment-item-title">
                                <div class="comment-date">
                                    @php
                                        $full_name = $comment->user
                                            ? $comment->user->full_name
                                            : ($comment->admin->full_name ?? 'ناشناس');
                                    @endphp
                                    <p class="text-muted comment-date-auther">
                                        @if($comment->user)
                                            {{ $comment->user->full_name ?? 'ناشناس' }}
                                        @else
                                            {{ $comment->admin->full_name ?? 'ناشناس' }}
                                        @endif
                                    </p>
                                    <p class="text-muted comment-date-speractor">–</p>
                                    <p class="text-muted comment-date-date">{{ jdate($comment->created_at)->format('d F Y') }}</p>
                                </div>
                                <div class="send-answer btn main-color-one-bg px-4 btn-sm" data-toggle="modal" data-target="#send-answer-model" data-comment-id="{{ $comment->id }}" data-user-name="{{ $full_name }}">
                                    پاسخ
                                </div>
                            </div>
                            <div class="comment-item-content">
                                <p>{{ $comment->body }}</p>

                                {{-- پاسخ‌ها --}}
                                @foreach($comment->comments()->where('status','accepted')->get() as $reply)
                                    <div class="comment-item">
                                        <div class="comment-item-avatar d-sm-flex d-none">
                                            <div class="comment-item-avatar-img">
                                                <a href="javascript:void(0)" class="rounded-circle">
                                                    @if($reply->user)
                                                        <img src="{{ $reply->user->image_url }}" class="rounded-circle" width="40" alt="{{ $reply->user->full_name ?? 'ناشناس' }}" loading="lazy">
                                                    @else
                                                        <img src="{{ $reply->admin->image_url }}" class="rounded-circle" width="40" alt="{{ $reply->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                                    @endif
                                                </a>
                                            </div>
                                        </div>
                                        <div class="comment-item-desc com-response">
                                            <div class="comment-item-avatar d-sm-none d-flex mb-2">
                                                <div class="comment-item-avatar-img">
                                                    <a href="javascript:void(0)" class="rounded-circle">
                                                        @if($reply->user)
                                                            <img src="{{ $reply->user->image_url }}" class="rounded-circle" width="40" alt="{{ $reply->user->full_name ?? 'ناشناس' }}" loading="lazy">
                                                        @else
                                                            <img src="{{ $reply->admin->image_url }}" class="rounded-circle" width="40" alt="{{ $reply->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                                        @endif
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="comment-item-title">
                                                <div class="comment-date">
                                                    <p class="text-muted comment-date-auther">
                                                        @if($reply->user)
                                                            {{ $reply->user->full_name ?? 'ناشناس' }}
                                                        @else
                                                            {{ $reply->admin->full_name ?? 'ناشناس' }}
                                                        @endif
                                                    </p>
                                                    <p class="text-muted comment-date-speractor">–</p>
                                                    <p class="text-muted comment-date-date">{{ jdate($reply->created_at)->format('d F Y') }}</p>
                                                    @if($reply->admin)
                                                        <span class="badge rounded-pill main-color-one-bg text-white">مدیریت</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="comment-item-content">
                                                <p>{{ $reply->body }}</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="is-usefull text-muted font-12 ms-2">آیا این پاسخ مفید بود؟</span>
                                                @php
                                                    $replyUserLiked = isset($likedComments[$reply->id . '_like']);
                                                    $replyUserDisliked = isset($likedComments[$reply->id . '_dislike']);
                                                @endphp
                                                <div class="action d-flex align-items-center">
                                                    <button class="action--child like btn light-btn shadow-sm rounded-3 border-0 btn-sm ms-1" data-action="{{ route("front.articles.comments.like", $reply) }}" data-comment-id="{{ $reply->id }}">
                                                        <i class="bi {{ $replyUserLiked ? 'bi-hand-thumbs-up-fill liked' : 'bi-hand-thumbs-up' }}"></i>
                                                        <span>{{ $reply->likes_count ?? 0 }}</span>
                                                    </button>
                                                    <button class="action--child dislike btn light-btn shadow-sm rounded-3 border-0 btn-sm" data-action="{{ route("front.articles.comments.like", $reply) }}" data-comment-id="{{ $reply->id }}">
                                                        <i class="bi {{ $replyUserDisliked ? 'bi-hand-thumbs-down-fill disliked' : 'bi-hand-thumbs-down' }}"></i>
                                                        <span>{{ $reply->dislikes_count ?? 0 }}</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                {{--پاسخ های تایید نشده--}}
                                <div class="user-pending-replies-comments user-pending-replies-comments-{{ $comment->id }}">
                                    @if(isset($userPendingReplies[$comment->id]) && $userPendingReplies[$comment->id]->count() > 0)
                                        @foreach($userPendingReplies[$comment->id] as $pendingReply)
                                            <div class="comment-item opacity-50" id="comment-{{ $pendingReply->id }}">
                                                <div class="comment-item-avatar d-sm-flex d-none">
                                                    <div class="comment-item-avatar-img">
                                                        <a href="javascript:void(0)" class="rounded-circle">
                                                            @if($pendingReply->user)
                                                                <img src="{{ $pendingReply->user->image_url }}" class="rounded-circle" width="40" alt="{{ $pendingReply->user->full_name ?? 'ناشناس' }}" loading="lazy">
                                                            @else
                                                                <img src="{{ $pendingReply->admin->image_url }}" class="rounded-circle" width="40" alt="{{ $pendingReply->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                                            @endif
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="comment-item-desc com-response">
                                                    <div class="comment-item-avatar d-sm-none d-flex mb-2">
                                                        <div class="comment-item-avatar-img">
                                                            <a href="javascript:void(0)" class="rounded-circle">
                                                                @if($pendingReply->user)
                                                                    <img src="{{ $pendingReply->user->image_url }}" class="rounded-circle" width="40" alt="{{ $pendingReply->user->full_name ?? 'ناشناس' }}" loading="lazy">
                                                                @else
                                                                    <img src="{{ $pendingReply->admin->image_url }}" class="rounded-circle" width="40" alt="{{ $pendingReply->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                                                @endif
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="comment-item-title">
                                                        <div class="comment-date">
                                                            <p class="text-muted comment-date-auther">
                                                                @if($pendingReply->user)
                                                                    {{ $pendingReply->user->full_name ?? 'ناشناس' }}
                                                                @else
                                                                    {{ $pendingReply->admin->full_name ?? 'ناشناس' }}
                                                                @endif
                                                            </p>
                                                            <p class="text-muted comment-date-speractor">–</p>
                                                            <p class="text-muted comment-date-date">{{ jdate($pendingReply->created_at)->format('d F Y') }}</p>
                                                        </div>
                                                        <small class="alert-warning d-block">
                                                            <i class="bi bi-clock"></i> این پاسخ پس از تایید مدیر نمایش داده می‌شود
                                                        </small>
                                                    </div>
                                                    <div class="comment-item-content">
                                                        <p>{{ $pendingReply->body }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <span class="is-usefull text-muted font-12 ms-2">آیا این نظر مفید بود؟</span>
                                @php
                                    $userLiked = isset($likedComments[$comment->id . '_like']);
                                    $userDisliked = isset($likedComments[$comment->id . '_dislike']);
                                @endphp

                                <div class="action d-flex align-items-center">
                                    <button class="action--child like btn light-btn shadow-sm rounded-3 border-0 btn-sm ms-1" data-action="{{ route("front.articles.comments.like", $comment) }}" data-comment-id="{{ $comment->id }}">
                                        <i class="bi {{ $userLiked ? 'bi-hand-thumbs-up-fill liked' : 'bi-hand-thumbs-up' }}"></i>
                                        <span>{{ $comment->likes_count ?? 0 }}</span>
                                    </button>
                                    <button class="action--child dislike btn light-btn shadow-sm rounded-3 border-0 btn-sm" data-action="{{ route("front.articles.comments.like", $comment) }}" data-comment-id="{{ $comment->id }}">
                                        <i class="bi {{ $userDisliked ? 'bi-hand-thumbs-down-fill disliked' : 'bi-hand-thumbs-down' }}"></i>
                                        <span>{{ $comment->dislikes_count ?? 0 }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3">
                        <p>هنوز دیدگاهی ثبت نشده است.</p>
                    </div>
                @endforelse

                {{-- صفحه‌بندی نظرات --}}
                <div class="w-100">
                    {{ $mainComments->links('front::components.paginate') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- فرم ثبت نظر جدید — سانیار comment-form --}}
<div class="container-fluid mt-3 pb-4">
    <div class="content-box">
        <div class="container-fluid">
            <div class="comment-form">
                <div class="title-panel mb-3">
                    <h6 class="font-18">دیدگاه خود را بنویسید</h6>
                </div>
                <form id="comment-form" method="POST" data-action="{{ route('front.articles.comments.store', $post) }}">
                    @csrf
                    {{-- فاز ۴۴ — حفاظت ربات‌گیر (تلهٔ زمان/هانی‌پات) + کپچای اختیاری --}}
                    {!! \App\Services\CommentProtection::hiddenFields() !!}
                    @if(\App\Services\SecuritySettings::captchaEnabled('comments'))
                        <div class="row mb-2">
                            <x-captcha />
                        </div>
                    @endif
                    <div class="form-group mb-2">
                        <label for="commentContent" class="form-label">دیدگاه <span class="text-danger ms-1">*</span></label>
                        <textarea class="form-control" name="content" id="commentContent" rows="5" placeholder="چه نظری دارید؟"></textarea>
                    </div>
                    <div class="comments---info fs-8 lts-05 mb-3">
                        انجام این عمل به منزله قبول <a href="" target="_blank" class="link">قوانین و شرایط</a> میباشد.
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn main-color-three-bg border-0 px-5 rounded-3">
                            ارسال دیدگاه <i class="bi bi-send ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- مودال پاسخ --}}
<div class="modal fade send-answer-modal" id="send-answer-model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title lts-05 fs-5">
                    <span>در حال پاسخ به:</span> <span class="replay-user-name"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="send-answer-form" class="reply-form" data-comment-id="" data-action="{{ route('front.articles.comments.reply') }}">
                    @csrf
                    <div class="form-group mb-2">
                        <label class="form-label">پاسخ <span class="text-danger ms-1">*</span></label>
                        <textarea class="form-control" name="content" rows="3" placeholder="پاسختان را بنویسید ..."></textarea>
                    </div>
                    <div class="comments---info fs-8 lts-05 mb-3">
                        انجام این عمل به منزله قبول <a href="" target="_blank" class="link">قوانین و شرایط</a> میباشد.
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn main-color-three-bg border-0 px-5 rounded-3">
                            ثبت پاسخ <i class="bi bi-send ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

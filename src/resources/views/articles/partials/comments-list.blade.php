{{--
    سانیار فاز K — لیست نظرات تاییدشدهٔ یک مقاله (رندر اولیه + هدف AJAX)
    متغیر: $comments (LengthAwarePaginator شامل mainComments + replies eager)
--}}
@php $likedComments = json_decode(request()->cookie('comment_likes', '[]'), true) ?: []; @endphp
<div class="comments-list" data-saniar-comments-list>
    @forelse($comments as $comment)
        @php
            $commentAuthor = $comment->user ?: $comment->admin;
            $commentAuthorName = $commentAuthor?->full_name ?? 'کاربر';
            $commentAuthorImage = $commentAuthor?->image_url ?? null;
        @endphp
        <div class="comment-card" id="comment-{{ $comment->id }}">
            <div class="d-flex gap-2">
                <img src="{{ $commentAuthorImage ?? theme_asset('image/logo.png') }}"
                     alt="{{ $commentAuthorName }}" class="comment-avatar rounded-circle" width="46" height="46" loading="lazy">
                <div class="flex-grow-1">
                    <div class="comment-head d-flex align-items-center flex-wrap gap-2">
                        <span class="fw-bold font-14">{{ $commentAuthorName }}</span>
                        @if($comment->admin)
                            <span class="badge rounded-pill admin-badge font-11">تحریریه</span>
                        @endif
                        <span class="text-muted font-12">
                            <i class="bi bi-calendar-event me-1"></i>{{ jdate($comment->created_at)->format('d F Y') }}
                        </span>
                    </div>
                    <p class="comment-body font-14 mb-2 mt-1">{!! nl2br(e($comment->body)) !!}</p>
                    <div class="comment-actions d-flex align-items-center gap-2">
                        <button type="button"
                                class="btn btn-sm comment-like-btn {{ isset($likedComments[$comment->id . '_like']) ? 'active text-success' : '' }}"
                                data-comment-id="{{ $comment->id }}" data-type="like" aria-label="پسندیدن دیدگاه">
                            <i class="bi bi-hand-thumbs-up"></i>
                            <span class="count">{{ number_format($comment->likes_count) }}</span>
                        </button>
                        <button type="button"
                                class="btn btn-sm comment-like-btn {{ isset($likedComments[$comment->id . '_dislike']) ? 'active text-danger' : '' }}"
                                data-comment-id="{{ $comment->id }}" data-type="dislike" aria-label="نپسندیدن دیدگاه">
                            <i class="bi bi-hand-thumbs-down"></i>
                            <span class="count">{{ number_format($comment->dislikes_count) }}</span>
                        </button>
                        <button type="button" class="btn btn-sm comment-reply-toggle" data-bs-toggle="collapse"
                                data-bs-target="#reply-form-{{ $comment->id }}" aria-expanded="false"
                                aria-controls="reply-form-{{ $comment->id }}">
                            <i class="bi bi-reply-ease-fill"></i> پاسخ
                        </button>
                    </div>

                    {{-- فرم پاسخ (تاشو) --}}
                    <div class="collapse mt-2" id="reply-form-{{ $comment->id }}">
                        <form class="reply-form d-flex gap-2 align-items-start" data-parent-id="{{ $comment->id }}">
                            <textarea name="content" rows="2" class="form-control form-control-sm font-14"
                                      placeholder="پاسخ شما ..." required maxlength="1000" aria-label="متن پاسخ"></textarea>
                            <button type="submit" class="btn btn-sm main-color-one-bg text-white border-0 rounded-pill px-3 text-nowrap">
                                ارسال
                            </button>
                        </form>
                    </div>

                    {{-- پاسخ‌های تاییدشده --}}
                    @if($comment->replies->isNotEmpty())
                        <div class="comment-replies mt-3">
                            @foreach($comment->replies as $reply)
                                @php
                                    $replyAuthor = $reply->user ?: $reply->admin;
                                    $replyAuthorName = $replyAuthor?->full_name ?? 'کاربر';
                                    $replyAuthorImage = $replyAuthor?->image_url ?? null;
                                @endphp
                                <div class="comment-card reply" id="comment-{{ $reply->id }}">
                                    <div class="d-flex gap-2">
                                        <img src="{{ $replyAuthorImage ?? theme_asset('image/logo.png') }}"
                                             alt="{{ $replyAuthorName }}" class="comment-avatar rounded-circle" width="36" height="36" loading="lazy">
                                        <div class="flex-grow-1">
                                            <div class="comment-head d-flex align-items-center flex-wrap gap-2">
                                                <span class="fw-bold font-13">{{ $replyAuthorName }}</span>
                                                @if($reply->admin)
                                                    <span class="badge rounded-pill admin-badge font-11">تحریریه</span>
                                                @endif
                                                <span class="text-muted font-12">
                                                    <i class="bi bi-calendar-event me-1"></i>{{ jdate($reply->created_at)->format('d F Y') }}
                                                </span>
                                            </div>
                                            <p class="comment-body font-14 mb-2 mt-1">{!! nl2br(e($reply->body)) !!}</p>
                                            <div class="comment-actions d-flex align-items-center gap-2">
                                                <button type="button"
                                                        class="btn btn-sm comment-like-btn {{ isset($likedComments[$reply->id . '_like']) ? 'active text-success' : '' }}"
                                                        data-comment-id="{{ $reply->id }}" data-type="like" aria-label="پسندیدن پاسخ">
                                                    <i class="bi bi-hand-thumbs-up"></i>
                                                    <span class="count">{{ number_format($reply->likes_count) }}</span>
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm comment-like-btn {{ isset($likedComments[$reply->id . '_dislike']) ? 'active text-danger' : '' }}"
                                                        data-comment-id="{{ $reply->id }}" data-type="dislike" aria-label="نپسندیدن پاسخ">
                                                    <i class="bi bi-hand-thumbs-down"></i>
                                                    <span class="count">{{ number_format($reply->dislikes_count) }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- پاسخ‌های در انتظار تایید کاربرِ همین کامنت --}}
        @if(isset($userPendingReplies) && $userPendingReplies->has($comment->id))
            @foreach($userPendingReplies->get($comment->id) as $pendingReply)
                <div class="comment-card reply pending-comment">
                    <div class="d-flex gap-2">
                        <img src="{{ $pendingReply->user?->image_url ?? theme_asset('image/logo.png') }}"
                             alt="{{ $pendingReply->user?->full_name ?? 'شما' }}" class="comment-avatar rounded-circle" width="36" height="36" loading="lazy">
                        <div class="flex-grow-1">
                            <div class="comment-head d-flex align-items-center flex-wrap gap-2">
                                <span class="fw-bold font-13">{{ $pendingReply->user?->full_name ?? 'شما' }}</span>
                                <span class="badge bg-warning text-dark font-11"><i class="bi bi-clock"></i> در انتظار تایید</span>
                            </div>
                            <p class="comment-body font-14 mb-2 mt-1">{!! nl2br(e($pendingReply->body)) !!}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @empty
        <div class="text-center text-muted py-4 font-14">
            <i class="bi bi-chat-square-text d-block mb-2 font-30"></i>
            هنوز دیدگاهی ثبت نشده — اولین نفر باشید!
        </div>
    @endforelse
</div>

<div id="article-comments" class="section-title mb-2">
    <div class="title no-after">دیدگاه ها</div>
</div>

@php
    // دریافت کوکی لایک‌ها
    $likedComments = json_decode(request()->cookie('comment_likes', '[]'), true);
    $mainComments=$post->mainComments()->orderby('created_at','desc')->paginate(10)
@endphp
<div class="alert alert-success hide">
    <i class=" fas fa-circle-check"></i>

</div>
<div class="comments-container card shadow-1 mb-3">
    <div class="card-body">
        {{--نظر های تایید نشده--}}
<div class="user-pending-comments">
    @if($userPendingComments->count())
        @foreach($userPendingComments as $comment)
            <div class="comment card mb-3" style="opacity: 0.6;" id="comment-{{ $comment->id }}">
                <div class="card-body">
                    <div class="comment--avatar">
                        @if($comment->user)
                            <img class="shadow-1 mb-2" src="{{ $comment->user->image_url }}"
                                 alt="{{ $comment->user->full_name ?? 'ناشناس' }}" width="55" height="55" loading="lazy">
                        @else
                            <img class="shadow-1 mb-2" src="{{ $comment->admin->image_url }}"
                                 alt="{{ $comment->admin->full_name ?? 'ناشناس' }}" width="55" height="55" loading="lazy">
                        @endif
                    </div>
                    <div class="comment--meta">
                        <div class="comment--name">
                            <a href="javascript:void(0)" style="text-decoration: none;">
                                @if($comment->user)
                                    <span>{{ $comment->user->full_name ?? 'ناشناس' }}</span>
                                @else
                                    <span>{{ $comment->admin->full_name ?? 'ناشناس' }}</span>
                                @endif

                                <small class="alert-warning">
                                    <i class="fas fa-clock"></i> این دیدگاه پس از تایید مدیر نمایش داده می‌شود
                                </small>
                            </a>

                        </div>
                        <span class="date">{{ jdate($comment->created_at)->format('d F Y') }}</span>
                        <p class="comment--description mb-3">{{ $comment->body }}</p>
                    </div>
                </div>



            </div>
        @endforeach
    @endif
</div>


        @forelse($mainComments as $comment)
            <div class="comment card mb-3" id="comment-{{ $comment->id }}">
                <div class="card-body">
                    <div class="comment--avatar">
                        @if($comment->user)
                        <img class="shadow-1 mb-2" src="{{ $comment->user->image_url }}"
                             alt="{{ $comment->user->full_name ?? 'ناشناس' }}" width="55" height="55" loading="lazy">
                        @else
                            <img class="shadow-1 mb-2" src="{{ $comment->admin->image_url }}"
                                 alt="{{ $comment->admin->full_name ?? 'ناشناس' }}" width="55" height="55" loading="lazy">
                        @endif
                    </div>
                    <div class="comment--meta">
                        <div class="comment--name">
                            @php
                            $full_name=$comment->user->full_name;
                            @endphp
                            <a href="javascript:void(0)" style="text-decoration: none;">

                                @if($comment->user)

                                    <span>{{ $comment->user->full_name ?? 'ناشناس' }}</span>
                                @else
                                    <span>{{ $comment->admin->full_name ?? 'ناشناس' }}</span>
                                    @php
                                        $full_name=$comment->admin->full_name;
                                    @endphp
                                @endif

                            </a>
                            <div class="send-answer" data-toggle="modal" data-target="#send-answer-model" data-comment-id="{{ $comment->id }}" data-user-name="{{$full_name}}">
                                پاسخ
                            </div>
                        </div>
                        <span class="date">{{ jdate($comment->created_at)->format('d F Y') }}</span>
                        <p class="comment--description mb-3">{{ $comment->body }}</p>
                        {{-- پاسخ‌ها --}}
                        @foreach($comment->comments()->where('status','accepted')->get() as $reply)
                            <div class="comment comment-answer card mb-2">
                                <div class="card-body">
                                    <div class="comment--avatar">
                                        @if($reply->user)
                                        <img class="shadow-1 mb-2" src="{{ $reply->user->image_url }}"
                                             alt="{{ $reply->user->full_name ?? 'ناشناس' }}" width="55" height="55" loading="lazy">
                                        @else
                                            <img class="shadow-1 mb-2" src="{{ $reply->admin->image_url }}"
                                                 alt="{{ $reply->admin->full_name ?? 'ناشناس' }}" width="55" height="55" loading="lazy">
                                        @endif
                                        @if($reply->admin)
                                            <div class="admin--badge">مدیریت</div>
                                        @endif
                                    </div>
                                    <div class="comment--meta">
                                        <div class="comment--name">
                                            @if($reply->user)
                                            <a href="javascript:void(0)" style="text-decoration: none;">
                                                <span>{{ $reply->user->full_name ?? 'ناشناس' }}</span>
                                            </a>
                                            @else
                                                <a href="javascript:void(0)" style="text-decoration: none;">
                                                    <span>{{ $reply->admin->full_name ?? 'ناشناس' }}</span>
                                                </a>
                                            @endif
                                        </div>
                                        <span class="date">{{ jdate($reply->created_at)->format('d F Y') }}</span>
                                        <p class="comment--description mb-3">{{ $reply->body }}</p>
                                        <div class="comment--meta-details">
                                            <span class="is-usefull">آیا این پاسخ مفید بود؟</span>
                                            @php
                                                $replyUserLiked = isset($likedComments[$reply->id . '_like']);
                                                $replyUserDisliked = isset($likedComments[$reply->id . '_dislike']);
                                            @endphp
                                            <div class="action">
                                                <button class="action--child like" data-action="{{ route("front.articles.comments.like", $reply) }}" data-comment-id="{{ $reply->id }}">
                                                    <i class="fa-regular fa-thumbs-up {{ $replyUserLiked ? 'liked fa-solid' : '' }}"></i>
                                                    <span>{{ $reply->likes_count ?? 0 }}</span>
                                                </button>
                                                <button class="action--child dislike" data-action="{{ route("front.articles.comments.like", $reply) }}" data-comment-id="{{ $reply->id }}">
                                                    <i class="fa-regular fa-thumbs-down {{ $replyUserDisliked ? 'liked fa-solid' : '' }}"></i>
                                                    <span> {{ $reply->dislikes_count ?? 0 }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{--پاسخ های تایید نشده--}}
                        <div class="user-pending-replies-comments user-pending-replies-comments-{{$comment->id}}" >
                            @if(isset($userPendingReplies[$comment->id]) && $userPendingReplies[$comment->id]->count() > 0)
                                @foreach($userPendingReplies[$comment->id] as $pendingReply)
                                    <div class="comment comment-answer card mb-2" style="opacity: 0.6;" id="comment-{{ $pendingReply->id }}">
                                        <div class="card-body">
                                            <div class="comment--avatar">
                                                @if($pendingReply->user)
                                                    <img class="shadow-1 mb-2" src="{{ $pendingReply->user->image_url }}"
                                                         alt="{{ $pendingReply->user->full_name ?? 'ناشناس' }}" width="55" height="55" loading="lazy">
                                                @else
                                                    <img class="shadow-1 mb-2" src="{{ $pendingReply->admin->image_url }}"
                                                         alt="{{ $pendingReply->admin->full_name ?? 'ناشناس' }}" width="55" height="55" loading="lazy">
                                                @endif
                                            </div>
                                            <div class="comment--meta">
                                                <div class="comment--name">
                                                    <a href="javascript:void(0)" style="text-decoration: none;">
                                                        @if($pendingReply->user)
                                                            <span>{{ $pendingReply->user->full_name ?? 'ناشناس' }}</span>
                                                        @else
                                                            <span>{{ $pendingReply->admin->full_name ?? 'ناشناس' }}</span>
                                                        @endif

                                                        <small class="alert-warning">
                                                            <i class="fas fa-clock"></i> این دیدگاه پس از تایید مدیر نمایش داده می‌شود
                                                        </small>
                                                    </a>

                                                </div>
                                                <span class="date">{{ jdate($pendingReply->created_at)->format('d F Y') }}</span>
                                                <p class="comment--description mb-3">{{ $pendingReply->body }}</p>
                                            </div>
                                        </div>



                                    </div>
                                @endforeach
                            @endif
                        </div>



                        <div class="comment--meta-details">
                            <span class="is-usefull">آیا این نظر مفید بود؟</span>
                            @php
                                $userLiked = isset($likedComments[$comment->id . '_like']);
                                $userDisliked = isset($likedComments[$comment->id . '_dislike']);
                            @endphp

                            <div class="action">
                                <button class="action--child like" data-action="{{ route("front.articles.comments.like", $comment) }}"  data-comment-id="{{ $comment->id }}">
                                    <i class="fa-regular fa-thumbs-up {{ $userLiked ? 'liked fa-solid' : '' }}"></i>
                                    <span>{{ $comment->likes_count ?? 0 }}</span>
                                </button>
                                <button class="action--child dislike" data-action="{{ route("front.articles.comments.like", $comment) }}" data-comment-id="{{ $comment->id }}">
                                    <i class="fa-regular fa-thumbs-down {{ $userDisliked ? 'liked fa-solid' : '' }}"></i>
                                    <span>{{ $comment->dislikes_count ?? 0 }}</span>
                                </button>
                            </div>
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

{{-- فرم ثبت نظر جدید --}}



<div class="comments-container card shadow-1 mb-4">
    <div class="card-body">
        <form id="comment-form" method="POST" data-action="{{route('front.articles.comments.store',$post)}}" >
            @csrf
            {{-- فاز ۴۴ — حفاظت ربات‌گیر (تلهٔ زمان/هانی‌پات) + کپچای اختیاری --}}
            {!! \App\Services\CommentProtection::hiddenFields() !!}
            @if(\App\Services\SecuritySettings::captchaEnabled('comments'))
            <div class="row pr-4 mb-2">
                <x-captcha />
            </div>
            @endif
            <div class="row">
                <div class="col-12 mb-2">
                    <label class="mb-2">دیدگاه</label>
                    <textarea class="form-control" name="content" rows="3" placeholder="چه نظری دارید؟"></textarea>
                </div>
                <div class="col-12 comments---info fs-8 lts-05 mb-3">
                    انجام این عمل به منزله قبول <a href="" target="_blank" class="link">قوانین و شرایط</a> میباشد.
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        ارسال دیدگاه <i class="fa-regular fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </form>
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
                <button type="button" class="btn modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-regular fa-circle-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="send-answer-form" class="reply-form" data-comment-id="" data-action="{{route('front.articles.comments.reply')}}">
                    @csrf
                    <div class="row">
                        <div class="col-12 mb-2">
                            <label class="mb-2">پاسخ</label>
                            <textarea class="form-control" name="content" rows="3" placeholder="پاسختان را بنویسید ..."></textarea>
                        </div>
                        <div class="col-12 comments---info fs-8 lts-05 mb-3">
                            انجام این عمل به منزله قبول <a href="" target="_blank" class="link">قوانین و شرایط</a> میباشد.
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                ثبت پاسخ <i class="fa-regular fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


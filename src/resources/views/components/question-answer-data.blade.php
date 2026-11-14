{{-- =====================================================================
    فاز ۱۵۸ — بازطراحی کارت‌های پرسش و پاسخ (مدرن و هم‌خوان تم تاریک)
    کلاس‌های متصل به JS عیناً حفظ شده‌اند:
    .comments-list / .article / .question-body / .comment-replay.js-add-answer-btn
    (data-id) / .comments-likes button.btn-like.likes-count & .dislikes-count
    (data-action, data-counter) / .paginationPager
    توجه: comments.js از $(a).parent('.article').find('.question-body') استفاده
    می‌کند — دکمهٔ پاسخ باید داخل .article و کنار .question-body بماند.
    ===================================================================== --}}
<ul class="comments-list qa-list">
    @foreach($questions as $comment)
        <li>
            <section>
                <div class="col-lg-12 col-md-12 col-xs-12 pull-left">
                    <div class="article qa-article">
                        <div class="header d-flex qa-question-row">

                            <div class="faq-header-question">
                                <span class="fa fa-question-circle-o"></span>
                            </div>
                            <div class="qa-question-main">
                                <div class="question-body">
                                    {!! nl2br(htmlentities($comment->body)) !!}
                                </div>
                                <div class="qa-question-meta">
                                    <span><i class="bi bi-person-circle"></i> {{ @$comment->user->fullname ? 'کاربر '. option('info_site_title', 'او پی شاپ') : 'کاربر مهمان' }}</span>
                                    <span><i class="bi bi-calendar3"></i> {{ jdate($comment->created_at)->format('%d %B %Y') }}</span>
                                </div>
                            </div>
                        </div>
                        @php
                            $child_comments = $comment->comments()->where('status', 'accepted')->get()
                        @endphp
                        @foreach($child_comments as $child_comment)
                            <div class="answer qa-answer">
                                <div class="d-flex form-question-answer">
                                    <span><i class="bi bi-reply"></i> پاسخ</span>
                                    <p>
                                        {!! nl2br(htmlentities($child_comment->body)) !!}
                                    </p>
                                </div>
                                <div class="footer qa-answer-footer">
                                    <div class="question-answer-name">
                                        <i class="bi bi-patch-check-fill main-color-one-color"></i>
                                        {{ @$child_comment->user->fullname ?  'کاربر '. option('info_site_title', 'او پی شاپ') : @$child_comment->admin->fullname }}
                                    </div>
                                    <div class="comment-like-container comments-likes">
                                        <span class="like-question">آیا این پاسخ مفید بود؟</span>
                                        <button class="btn-like likes-count" data-action="{{ route('front.comments.like', ['comment' => $child_comment]) }}"
                                                data-counter="{{ $child_comment->likes_count }}"
                                        ><i class="bi bi-hand-thumbs-up"></i> بله</button>
                                        <button class="btn-like dislikes-count"
                                                data-action="{{ route('front.comments.dislike', ['comment' => $child_comment]) }}" data-counter="{{ $child_comment->dislikes_count }}"><i class="bi bi-hand-thumbs-down"></i> خیر</button>
                                    </div>
                                </div>
                            </div>

                        @endforeach
                        @auth
                            <a class="link-border js-add-answer-btn comment-replay qa-reply-btn" data-name="{{ $comment->user->fullname }}" data-id="{{ $comment->id }}"><i class="bi bi-reply"></i> به این
                                پرسش پاسخ
                                دهید </a>
                        @else
                            <a class="link-border js-add-answer-btn comment-replay qa-reply-btn"><i class="bi bi-reply"></i> به این
                                پرسش پاسخ
                                دهید </a>
                        @endauth

                    </div>
                </div>
            </section>
        </li>
    @endforeach
</ul>
<div class="paginationPager">
        {{$questions->links()}}
</div>

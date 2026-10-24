{{-- داده‌های پرسش و پاسخ سانیار (خروجی AJAX getQuestions) — $questions --}}
<ul class="comments-list list-unstyled saniar-questions-list">
    @foreach ($questions as $comment)
        <li class="mb-3">
            <section>
                <div class="content-box">
                    <div class="container-fluid p-3">
                        <div class="header d-flex align-items-start gap-2 mb-2">
                            <span class="flex-shrink-0 main-color-one-bg text-white rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                                <i class="bi bi-question-lg"></i>
                            </span>
                            <div class="question-body font-14">
                                {!! nl2br(htmlentities($comment->body)) !!}
                            </div>
                        </div>

                        @php
                            $child_comments = $comment->comments()->where('status', 'accepted')->get();
                        @endphp
                        @foreach ($child_comments as $child_comment)
                            <div class="answer bg-light rounded-3 p-2 mb-2">
                                <div class="d-flex form-question-answer gap-2">
                                    <span class="badge bg-secondary font-12 flex-shrink-0" style="height:fit-content;">پاسخ</span>
                                    <p class="font-14 mb-1">{!! nl2br(htmlentities($child_comment->body)) !!}</p>
                                </div>
                                <div class="footer d-flex align-items-center flex-wrap gap-2">
                                    <div class="text-muted font-12">
                                        {{ @$child_comment->user->fullname ? 'کاربر ' . option('info_site_title') : @$child_comment->admin->fullname }}
                                    </div>
                                    <div class="comment-like-container d-flex align-items-center gap-2 font-12 text-muted">
                                        پاسخ مفید بود؟
                                        <button class="btn btn-sm btn-light rounded-3 font-12 btn-like likes-count"
                                                data-action="{{ route('front.comments.like', ['comment' => $child_comment]) }}"
                                                data-counter="{{ $child_comment->likes_count }}">
                                            بله ({{ $child_comment->likes_count }})
                                        </button>
                                        <button class="btn btn-sm btn-light rounded-3 font-12 btn-like dislikes-count"
                                                data-action="{{ route('front.comments.dislike', ['comment' => $child_comment]) }}"
                                                data-counter="{{ $child_comment->dislikes_count }}">
                                            خیر ({{ $child_comment->dislikes_count }})
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @auth
                            <a class="link-border font-13 main-color-one-color text-decoration-none js-add-answer-btn comment-replay"
                               data-name="{{ $comment->user->fullname }}" data-id="{{ $comment->id }}" href="javascript:void(0)">
                                <i class="bi bi-reply me-1"></i>به این پرسش پاسخ دهید
                            </a>
                        @else
                            <a class="link-border font-13 text-muted text-decoration-none"
                               href="{{ route('login', ['redirect' => url()->current()]) }}">
                                <i class="bi bi-reply me-1"></i>برای پاسخ وارد شوید
                            </a>
                        @endauth
                    </div>
                </div>
            </section>
        </li>
    @endforeach
</ul>

<div class="saniar-questions-pagination">
    {{ $questions->links('front::components.paginate') }}
</div>

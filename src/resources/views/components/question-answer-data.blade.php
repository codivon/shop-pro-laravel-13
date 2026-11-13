<ul class="comments-list">
    @foreach($questions as $comment)
        <li>
            <section>
                <div class="col-lg-12 col-md-12 col-xs-12 pull-left">
                    <div class="article">
                        <div class="header d-flex">

                            <div class="faq-header-question">
                                <span class="fa fa-question-circle-o"></span>
                            </div>
                            <div class="question-body">
                                {!! nl2br(htmlentities($comment->body)) !!}
                            </div>
                        </div>
                        @php
                            $child_comments = $comment->comments()->where('status', 'accepted')->get()
                        @endphp
                        @foreach($child_comments as $child_comment)
                            <div class="answer">
                                <div class="d-flex form-question-answer">
                                    <span>پاسخ</span>
                                    <p>
                                        {!! nl2br(htmlentities($child_comment->body)) !!}
                                    </p>
                                </div>
                                <div class="footer">
                                    <div class="question-answer-name">{{ @$child_comment->user->fullname ?  'کاربر '. option('info_site_title', 'او پی شاپ') : @$child_comment->admin->fullname }}</div>
                                    <div class="comment-like-container comments-likes">آیا این
                                        نظر
                                        پاسخ مفید
                                        بود؟
                                        <button class="btn-like likes-count" data-action="{{ route('front.comments.like', ['comment' => $child_comment]) }}"
                                                data-counter="{{ $child_comment->likes_count }}"
                                        >بله</button>
                                        <button class="btn-like dislikes-count"
                                                data-action="{{ route('front.comments.dislike', ['comment' => $child_comment]) }}" data-counter="{{ $child_comment->dislikes_count }}">خیر</button>
                                    </div>
                                </div>
                            </div>

                        @endforeach
                        @auth
                            <a class="link-border js-add-answer-btn comment-replay" data-name="{{ $comment->user->fullname }}" data-id="{{ $comment->id }}">به این
                                پرسش پاسخ
                                دهید </a>
                        @else
                            <a class="link-border js-add-answer-btn comment-replay">به این
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

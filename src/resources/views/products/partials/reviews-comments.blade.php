{{-- ============================================================
     فاز ۱۵۸ — کارت دیدگاه‌ها بازطراحی مدرن (هم‌خوان تم تاریک)
     کلاس‌های متصل به JS عیناً حفظ شده‌اند:
     .comments-list / .article / .comments-likes button.btn-like.likes-count /
     .btn-like.dislikes-count (data-action, data-counter) / .paginationPager
     ============================================================ --}}
@php
    /* یکسان‌سازی دو شاخهٔ تکراری: حالت «نظر خریداران» فقط دیدگاه‌های
       دارای suggest را نشان می‌دهد؛ بقیهٔ حالت‌ها همهٔ دیدگاه‌های تأییدشده را. */
    $displayedReviews = $Buyerscomments == 'yes'
        ? $reviews->filter(fn ($review) => (bool) $review->suggest)
        : $reviews;
@endphp

<ul class="comments-list">
    @if ($displayedReviews->count())
        @foreach ($displayedReviews as $review)
            @php
                $fullname = $review->user->fullname;
                if ($fullname) {
                    $fullname = 'کاربر ' . option('info_site_title', 'او پی شاپ');
                }
            @endphp
            <li>
                <section>
                    <div class="article review-article">
                        <div class="header review-header">
                            <div class="review-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="review-header-main">
                                <div class="review-header-line1">
                                    <span class="review-author">{{ $fullname ?: 'کاربر مهمان' }}</span>
                                    @if ($review->suggest)
                                        <b class="badge buyer-badge"><i class="bi bi-patch-check-fill"></i> خریدار</b>
                                    @endif
                                    @if ($review->seller?->seller_info?->business_name)
                                        <b class="badge store-badge">از فروشگاه {{ $review->seller->seller_info->business_name }}</b>
                                    @endif
                                </div>
                                <div class="review-header-line2">
                                    <span class="review-meta">
                                        <i class="bi bi-calendar3"></i>
                                        {{ jdate($review->created_at)->format('%d %B %Y') }}
                                    </span>
                                </div>
                            </div>
                            <div class="review-rating-box">
                                <span class="rating-badge rating-{{ $review->rating }}-0">{{ $review->rating }}.0</span>
                                <span class="review-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                    @endfor
                                </span>
                            </div>
                        </div>

                        @if ($review->title)
                            <span class="review-title">{{ $review->title }}</span>
                        @endif

                        @switch($review->suggest)
                            @case('yes')
                                <div class="user-suggest suggest-yes"><i class="mdi mdi-thumb-up-outline"></i> خریدار این کالا را پیشنهاد می‌کند</div>
                                @break
                            @case('not_sure')
                                <div class="user-suggest suggest-neutral"><i class="mdi mdi-help"></i> مطمئن نیستم</div>
                                @break
                            @case('no')
                                <div class="user-suggest suggest-no"><i class="mdi mdi-thumb-down-outline"></i> خریدار این کالا را پیشنهاد نمی‌کند</div>
                                @break
                        @endswitch

                        <p class="review-body">{!! nl2br(htmlentities($review->body)) !!}</p>

                        @if ($review->points->count() && ($review->points->where('type', 'positive')->count() || $review->points->where('type', 'negative')->count()))
                            <div class="review-points">
                                @foreach ($review->points->where('type', 'positive') as $point)
                                    <span class="review-point point-positive"><i class="bi bi-plus-lg"></i>{{ $point->text }}</span>
                                @endforeach
                                @foreach ($review->points->where('type', 'negative') as $point)
                                    <span class="review-point point-negative"><i class="bi bi-dash-lg"></i>{{ $point->text }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="footer review-footer">
                            <div class="comment-like-container comments-likes">
                                <span class="like-question">آیا این دیدگاه مفید بود؟</span>
                                <button class="btn-like likes-count" data-action="{{ route('front.reviews.like', ['review' => $review]) }}"
                                        data-counter="{{ $review->likes_count }}"><i class="bi bi-hand-thumbs-up"></i> بله</button>
                                <button class="btn-like dislikes-count" data-action="{{ route('front.reviews.dislike', ['review' => $review]) }}"
                                        data-counter="{{ $review->dislikes_count }}"><i class="bi bi-hand-thumbs-down"></i> خیر</button>
                            </div>
                        </div>
                    </div>
                </section>
            </li>
        @endforeach
    @else
        <li>
            <section>
                <div class="article review-article">
                    <p class="mb-0">هنوز هیچ یک از خریداران نظر خود را ارسال نکرده‌اند.</p>
                </div>
            </section>
        </li>
    @endif
</ul>
<div class="paginationPager">
    {{ $reviews->links() }}
</div>

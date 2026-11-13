{{-- ============================================================
     فاز ۱۵۵ — کارت دیدگاه‌ها با طراحی سانیار (هم‌تراز کارت مرجع)
     کلاس‌های متصل به JS عیناً حفظ شده‌اند:
     .comments-list / .article / .comments-likes button.btn-like.likes-count /
     .btn-like.dislikes-count (data-action, data-counter) / .paginationPager
     ============================================================ --}}
@php
    /* فاز ۱۵۵ — یکسان‌سازی دو شاخهٔ تکراری: حالت «نظر خریداران» فقط دیدگاه‌های
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
                    <div class="article">
                        <div class="header">
                            <b class="badge rating-badge rating-{{ $review->rating }}-0">{{ $review->rating }}.0</b>
                            <span class="review-title">{{ $review->title }}</span>
                            <span class="review-meta">
                                توسط {{ $fullname }} در تاریخ {{ jdate($review->created_at)->format('%d %B %Y') }}
                            </span>
                            @if ($review->suggest)
                                <b class="badge buyer-badge">خریدار</b>
                            @endif
                            {{-- فاز ۱۱۸ — نام فروشگاهی که خریدار واقعاً از آن خریده --}}
                            @if ($review->seller?->seller_info?->business_name)
                                <b class="badge store-badge">از فروشگاه {{ $review->seller->seller_info->business_name }}</b>
                            @endif
                        </div>

                        @switch($review->suggest)
                            @case('yes')
                                <div class="user-suggest text-success"><i class="mdi mdi-thumb-up-outline"></i> پیشنهاد می‌کنم</div>
                                @break
                            @case('not_sure')
                                <div class="user-suggest text-muted"><i class="mdi mdi-help"></i> مطمئن نیستم</div>
                                @break
                            @case('no')
                                <div class="user-suggest text-danger"><i class="mdi mdi-thumb-down-outline"></i> پیشنهاد نمی‌کنم</div>
                                @break
                        @endswitch

                        <p>{!! nl2br(htmlentities($review->body)) !!}</p>

                        @if ($review->points->count() && ($review->points->where('type', 'positive')->count() || $review->points->where('type', 'negative')->count()))
                            <div class="content-expert-evaluation-positive">
                                <ul>
                                    @foreach ($review->points->where('type', 'positive') as $point)
                                        <li><b class="badge-positive">+</b>{{ $point->text }}</li>
                                    @endforeach
                                </ul>
                                <ul>
                                    @foreach ($review->points->where('type', 'negative') as $point)
                                        <li><b class="badge-negative">-</b>{{ $point->text }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="footer">
                            <div class="comment-like-container comments-likes">آیا این دیدگاه مفید بود؟
                                <button class="btn-like likes-count" data-action="{{ route('front.reviews.like', ['review' => $review]) }}"
                                        data-counter="{{ $review->likes_count }}">بله</button>
                                <button class="btn-like dislikes-count" data-action="{{ route('front.reviews.dislike', ['review' => $review]) }}"
                                        data-counter="{{ $review->dislikes_count }}">خیر</button>
                            </div>
                        </div>
                    </div>
                </section>
            </li>
        @endforeach
    @else
        <li>
            <section>
                <div class="article">
                    <p class="mb-0">هنوز هیچ یک از خریداران نظر خود را ارسال نکرده‌اند.</p>
                </div>
            </section>
        </li>
    @endif
</ul>
<div class="paginationPager">
    {{ $reviews->links() }}
</div>

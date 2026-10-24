{{-- لیست دیدگاه‌ها سانیار (خروجی AJAX getComments) — $reviews, $Buyerscomments --}}
<ul class="comments-list list-unstyled saniar-comments-list">
    @foreach ($reviews as $review)
        @if($Buyerscomments == "yes" && !$review->suggest)
            @continue
        @endif
        <li class="mb-3">
            <section>
                <div class="content-box">
                    <div class="container-fluid p-3">
                        <div class="header d-flex align-items-center flex-wrap gap-2 mb-2">
                            <div>
                                <span class="badge {{ $review->rating >= 4 ? 'bg-success' : ($review->rating >= 3 ? 'bg-warning text-dark' : 'bg-danger') }} rounded-3 font-12">
                                    {{ $review->rating }}.0
                                </span>
                                <strong class="font-14">{{ $review->title }}</strong>
                            </div>
                            <div class="text-muted font-12">
                                @php
                                    $fullname = $review->user->fullname ?? null;
                                    if ($fullname) { $fullname = 'کاربر ' . option('info_site_title'); }
                                @endphp
                                توسط {{ $fullname ?? 'کاربر' }} در تاریخ {{ jdate($review->created_at)->format('%d %B %Y') }}
                                @if ($review->suggest)
                                    <span class="badge bg-secondary font-11">خریدار</span>
                                @endif
                            </div>
                        </div>

                        @switch($review->suggest)
                            @case('yes')
                                <div class="user-suggest text-success font-13 mb-2">
                                    <i class="bi bi-hand-thumbs-up"></i> پیشنهاد می‌کنم
                                </div>
                                @break
                            @case('not_sure')
                                <div class="user-suggest text-muted font-13 mb-2">
                                    <i class="bi bi-question-circle"></i> مطمئن نیستم
                                </div>
                                @break
                            @case('no')
                                <div class="user-suggest text-danger font-13 mb-2">
                                    <i class="bi bi-hand-thumbs-down"></i> پیشنهاد نمی‌کنم
                                </div>
                                @break
                        @endswitch

                        <p class="font-14 mb-2">{!! nl2br(htmlentities($review->body)) !!}</p>

                        @if ($review->points->count())
                            @if ($review->points->where('type', 'positive')->count() || $review->points->where('type', 'negative')->count())
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6 col-12">
                                        <div class="saniar-points-box bg-success-subtle border border-success-subtle rounded-3 p-2">
                                            <ul class="mb-0 list-unstyled">
                                                @foreach ($review->points->where('type', 'positive') as $point)
                                                    <li class="font-13 text-success-emphasis">
                                                        <b class="me-1">+</b>{{ $point->text }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="saniar-points-box bg-danger-subtle border border-danger-subtle rounded-3 p-2">
                                            <ul class="mb-0 list-unstyled">
                                                @foreach ($review->points->where('type', 'negative') as $point)
                                                    <li class="font-13 text-danger-emphasis">
                                                        <b class="me-1">−</b>{{ $point->text }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div class="footer d-flex align-items-center gap-2">
                            <span class="text-muted font-13">آیا این دیدگاه مفید بود؟</span>
                            <button class="btn btn-sm btn-light rounded-3 font-12 btn-like likes-count {{ $review->likes_count ? 'fw-bold' : '' }}"
                                    data-action="{{ route('front.reviews.like', ['review' => $review]) }}"
                                    data-counter="{{ $review->likes_count }}">
                                <i class="bi bi-hand-thumbs-up me-1"></i>بله ({{ $review->likes_count }})
                            </button>
                            <button class="btn btn-sm btn-light rounded-3 font-12 btn-like dislikes-count {{ $review->dislikes_count ? 'fw-bold' : '' }}"
                                    data-action="{{ route('front.reviews.dislike', ['review' => $review]) }}"
                                    data-counter="{{ $review->dislikes_count }}">
                                <i class="bi bi-hand-thumbs-down me-1"></i>خیر ({{ $review->dislikes_count }})
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </li>
    @endforeach
</ul>

<div class="saniar-comments-pagination">
    {{ $reviews->links('front::components.paginate') }}
</div>

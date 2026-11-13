
<ul class="comments-list">
    @foreach ($reviews as $review)
        @if($Buyerscomments=="yes")
            @if ($review->suggest)
            <li>
                <section>
                    <div class="col-lg-12 col-md-7 col-xs-12 pull-left">
                        <div class="article">
                            <div class="header">
                                <div>
                                    <b class="badge badge-success rating-{{$review->rating}}-0">{{$review->rating}}.0</b>

                                    {{ $review->title }}
                                    @php $fullname=$review->user->fullname;if ($fullname){$fullname="کاربر ".option('info_site_title');};@endphp
                                    <span> توسط {{ $fullname }} در تاریخ {{ jdate($review->created_at)->format('%d %B %Y') }}
                                        @if ($review->suggest)
                                            <b class="badge badge-secondary">خریدار</b>
                                        @endif
                                        {{-- فاز ۱۱۸ — نام فروشگاهی که خریدار واقعاً از آن خریده --}}
                                        @if ($review->seller?->seller_info?->business_name)
                                            <b class="badge badge-info">از فروشگاه {{ $review->seller->seller_info->business_name }}</b>
                                        @endif
                                                                                </span>


                                </div>
                            </div>

                            @switch($review->suggest)
                                @case('yes')
                                <div class="user-suggest text-success" style="font-size: 13px;"><i class="mdi mdi-thumb-up-outline"></i> پیشنهاد می کنم</div>
                                @break
                                @case('not_sure')
                                <div class="user-suggest text-muted" style="font-size: 13px;"><i class="mdi mdi-help"></i> مطمئن نیستم</div>
                                @break
                                @case('no')
                                <div class="user-suggest text-danger" style="font-size: 13px;"><i class="mdi mdi-thumb-down-outline"></i> پیشنهاد نمی کنم</div>
                                @break

                            @endswitch

                            <p>
                                {!! nl2br(htmlentities($review->body)) !!}</p>
                            @if ($review->points->count())
                                <div class="row">
                                    @if ($review->points->where('type', 'positive')->count() or  $review->points->where('type', 'negative')->count())
                                        <div class="col-md-12 col-sm-12 col-12">
                                            <div class="content-expert-evaluation-positive">
                                                <ul>
                                                    @foreach ($review->points->where('type', 'positive') as $point)

                                                        <li><b class="badge-positive">+</b>{{ $point->text }}</li>
                                                    @endforeach
                                                </ul>

                                                <ul>
                                                    @foreach ($review->points->where('type', 'negative') as $point)

                                                        <li>   <b class="badge-negative">-</b>{{ $point->text }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif


                                </div>
                            @endif


                            <div class="footer">
                                <div class="comment-like-container comments-likes">آیا این دیدگاه مفید بود؟
                                    <button class="btn-like likes-count" data-action="{{ route('front.reviews.like', ['review' => $review]) }}"
                                            data-counter="{{ $review->likes_count }}">بله</button>
                                    <button data-action="{{ route('front.reviews.dislike', ['review' => $review]) }}" class="btn-like dislikes-count"
                                            data-counter="{{ $review->dislikes_count }}">خیر</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </li>
            @else

                    <li>
                      <p style="font-size: 13px">
                          هنوز هیچ یک از خریداراران نظر خود را ارسال نکرده اند.
                      </p>

                    </li>

                    <script>
                        $('#Newscomments .paginationPager').addClass('hide');
                    </script>
            @break

            @endif
        @else
            <li>
                <section>
                    <div class="col-lg-12 col-md-7 col-xs-12 pull-left">
                        <div class="article">
                            <div class="header">
                                <div>
                                    <b class="badge badge-success rating-{{$review->rating}}-0">{{$review->rating}}.0</b>

                                    {{ $review->title }}
                                    @php $fullname=$review->user->fullname;if ($fullname){$fullname="کاربر ".option('info_site_title');};@endphp
                                    <span> توسط {{ $fullname }} در تاریخ {{ jdate($review->created_at)->format('%d %B %Y') }}
                                        @if ($review->suggest)
                                            <b class="badge badge-secondary">خریدار</b>
                                        @endif
                                        {{-- فاز ۱۱۸ — نام فروشگاهی که خریدار واقعاً از آن خریده --}}
                                        @if ($review->seller?->seller_info?->business_name)
                                            <b class="badge badge-info">از فروشگاه {{ $review->seller->seller_info->business_name }}</b>
                                        @endif
                                                                                </span>


                                </div>
                            </div>

                            @switch($review->suggest)
                                @case('yes')
                                <div class="user-suggest text-success" style="font-size: 13px;"><i class="mdi mdi-thumb-up-outline"></i> پیشنهاد می کنم</div>
                                @break
                                @case('not_sure')
                                <div class="user-suggest text-muted" style="font-size: 13px;"><i class="mdi mdi-help"></i> مطمئن نیستم</div>
                                @break
                                @case('no')
                                <div class="user-suggest text-danger" style="font-size: 13px;"><i class="mdi mdi-thumb-down-outline"></i> پیشنهاد نمی کنم</div>
                                @break

                            @endswitch

                            <p>
                                {!! nl2br(htmlentities($review->body)) !!}</p>
                            @if ($review->points->count())
                                <div class="row">
                                    @if ($review->points->where('type', 'positive')->count() or  $review->points->where('type', 'negative')->count())
                                        <div class="col-md-12 col-sm-12 col-12">
                                            <div class="content-expert-evaluation-positive">
                                                <ul>
                                                    @foreach ($review->points->where('type', 'positive') as $point)

                                                        <li><b class="badge-positive">+</b>{{ $point->text }}</li>
                                                    @endforeach
                                                </ul>

                                                <ul>
                                                    @foreach ($review->points->where('type', 'negative') as $point)

                                                        <li>   <b class="badge-negative">-</b>{{ $point->text }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif


                                </div>
                            @endif


                            <div class="footer">
                                <div class="comment-like-container comments-likes">آیا این دیدگاه مفید بود؟
                                    <button class="btn-like likes-count" data-action="{{ route('front.reviews.like', ['review' => $review]) }}"
                                            data-counter="{{ $review->likes_count }}">بله</button>
                                    <button data-action="{{ route('front.reviews.dislike', ['review' => $review]) }}" class="btn-like dislikes-count"
                                            data-counter="{{ $review->dislikes_count }}">خیر</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </li>
        @endif

    @endforeach
</ul>
<div class="paginationPager">
    {{$reviews->links()}}
</div>


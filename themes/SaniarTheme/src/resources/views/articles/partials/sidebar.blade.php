{{-- اسلایدر شاید بخوانید --}}
<div class="grid-type-two-container blog-article-image-box-container">
    <div class="section-title section-title-secondary d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center justify-content-between">
            <div class="title"> پیشنهادهایی برای شما</div>
            <a class="more text-gray el-center mr-3 fs-10" href="{{ route('front.articles.index',['sort=editor_pick']) }}">
                بیشتر <i class="fa fa-angle-left mr-2"></i>
            </a>
        </div>
    </div>
    <div class="suggestions-post-carousel-one-block owl-carousel owl-theme owl-rtl owl-loaded owl-drag posts-carousel">
        <div class="owl-stage-outer">
            <div class="owl-stage"
                 style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                @php $i=1; @endphp
                @foreach($suggestions as $suggestion)
                    <div class="owl-item @if($i==1 or $i==2 or $i==3 or $i==4)active @endif"
                         style="width: 309.083px; margin-left: 10px;">
                        <div class=" card article-image shadow-1">
                            <img class="article-image--center-crop"
                                 src="{{ $suggestion->image ? asset($suggestion->image) : asset('/no-image-product.svg') }}"
                                 alt="{{ $suggestion->title }}"
                                 loading="lazy">

                            @if($suggestion->post_type=="video")
                                <div class="post-grid-thumbnail-video-type minimal">
                                    <i class=" fas fa-play"></i>
                                </div>
                            @endif

                            <a class="article-link"
                               href="{{ route('front.articles.show', $post) }}"></a>
                            <div class="article--footer"><h3 class="article-title mb-2">
                                    <a class="link" href="{{ route('front.articles.show', $post) }}">
                                        {{ $suggestion->title }}
                                    </a>
                                </h3>
                                <ul class="article-meta">
                                    <li>
                                        <img
                                            src="{{$post->admin->imageUrl}}"
                                            alt="{{ $suggestion->admin->full_name ?? 'ناشناس' }} " class="article--meta-avatar shadow-1 me-2"
                                            height="32" width="32" loading="lazy"><a
                                            class="article--meta-username lts-05"
                                            href="">{{ $suggestion->admin->full_name ?? 'ناشناس' }}</a>
                                    </li>
                                    <li class="divider"></li>
                                    <li><span class="date">{{ jdate($suggestion->created_at)->format('d F Y') }}</span></li>
                                </ul>
                            </div>
                            @if($suggestion->categories())
                                <span class="category-badge">
                                                <a class="link text-white" href="">{{$suggestion->categories()->first()->title}}</a>
                                            </span>
                            @endif

                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
</div>

{{-- جدیدترین مقالات --}}
<div class="grid-type-one-container grid-article-sidebar-container card shadow-1 mb-4">
    <div class="card-body">
        <div class="sidebar-card-inner-header">
            <div class="inner-card-container--title lts-05">
                <i class="fa-regular fa-clock"></i> جدیدترین مقالات
            </div>
            <span class="divider"></span>
        </div>
        <div class="article-lists">
            @foreach($latestPosts ?? [] as $latestPost)
                <div class="small-article">
                    <div class="grid-thumbnail">
                        <div class="grid-thumbnail--level-one">
                            <div class="grid-thumbnail--level-two">
                                <div class="image" style="background-image: url('{{ asset($latestPost->image) }}');"></div>
                            </div>
                        </div>
                        <a class="article-link" href="{{ route('front.articles.show', $latestPost->slug) }}"></a>
                    </div>
                    <div class="small-article--details">
                        <h3>
                            <a href="{{ route('front.articles.show', $latestPost->slug) }}">{{ $latestPost->title }}</a>
                        </h3>
                        <ul class="article--meta">
                            <li>
                                <a class="link" href="#">
                                    <span class="date">{{ $latestPost->admin->full_name ?? 'ناشناس' }}</span>
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li><span class="date">{{ jdate($latestPost->created_at)->format('d F Y') }}</span></li>
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- هشتگ های داغ --}}
<div class="inner-card-container hashtag-container card shadow-1 mb-4">
    <div class="card-body">
        <div class="sidebar-card-inner-header">
            <div class="inner-card-container--title lts-05">
                <i class="fa-solid fa-hashtag"></i> هشتگ های داغ
            </div>
            <span class="divider"></span>
        </div>
        <ul class="hashtags">
            @foreach($hotTags ?? [] as $tag)
                <li>
                    <a href="{{ route('front.articles.index', ['tag' => $tag->slug]) }}">
                        {{ $tag->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

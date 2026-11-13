{{-- گرید مقالات سانیار (gird-post-blog) — saniar blog-item --}}
<div class="articles-list-container">
    <div class="row gy-3">
        @foreach($posts as $post)
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="blog-item position-relative">
                    <a href="{{ route('front.articles.show', $post->slug) }}">
                        <div class="image">
                            <img src="{{ $post->image ? asset($post->image) : asset('/no-image-product.svg') }}"
                                 alt="{{ $post->title }}" class="img-fluid" loading="lazy">
                        </div>
                        <div class="title">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="title-item">
                                    <i class="bi bi-tag me-1"></i>
                                    <span class="font-12">{{ $post->categories->first()->title ?? 'دسته بندی نشده' }}</span>
                                </div>
                                <div class="title-item">
                                    <i class="bi bi-alarm me-1"></i>
                                    <span class="font-12">{{ jdate($post->created_at)->format('d F Y') }}</span>
                                </div>
                            </div>
                            <h4 class="font-16 text-overflow-1 h4">
                                @if($post->post_type=="video")
                                    <i class="bi bi-play-circle-fill main-color-one-color me-1"></i>
                                @elseif($post->post_type=="podcast")
                                    <i class="bi bi-soundwave main-color-one-color me-1"></i>
                                @endif
                                {{ $post->title }}
                            </h4>
                        </div>
                    </a>

                    {{-- متا (خارج از لینک کارت تا لینک‌های تودرتو نداشته باشیم) --}}
                    <div class="d-flex align-items-center justify-content-between px-3 pb-3 flex-wrap gap-1">
                        <div class="d-flex align-items-center">
                            <img class="rounded-circle me-2" src="{{ $post->admin->imageUrl }}"
                                 alt="{{ $post->admin->full_name ?? 'ناشناس' }}" width="24" height="24" loading="lazy">
                            <a class="text-muted font-12" href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">
                                {{ $post->admin->full_name ?? 'ناشناس' }}
                            </a>
                        </div>
                        @if($post->categories->isNotEmpty())
                            <a class="font-12 main-color-one-color text-decoration-none" href="?cat={{ $post->categories->first()->slug }}">
                                {{ $post->categories->first()->title }}
                            </a>
                        @endif
                        <span class="text-muted font-12">{{ number_format($post->view) }} <i class="bi bi-eye"></i></span>
                    </div>

                    @if($post->is_editor_pick)
                        <span class="badge bg-secondary position-absolute top-0 start-0 m-2"
                              data-bs-toggle="tooltip" data-bs-placement="top"
                              aria-label="انتخاب سردبیر" data-bs-original-title="انتخاب سردبیر">
                            <i class="bi bi-patch-check-fill"></i>
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($posts->isEmpty())
        <div class="content-box">
            <div class="container-fluid">
                <div class="text-center py-5">
                    <p class="mb-0">مقاله‌ای برای نمایش وجود ندارد.</p>
                </div>
            </div>
        </div>
    @endif
</div>

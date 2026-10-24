{{--
    سانیار فاز K — گرید کارت‌های مقاله (هدف درخواست‌های AJAX)
    متغیر: $posts (LengthAwarePaginator) — از ArticleController@index یا getComments نداریم، این پارشال مخصوص مقالات است
--}}
<div class="row gy-3" data-saniar-articles-list>
    @forelse($posts as $post)
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="blog-item">
                <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}" wire:navigate>
                    <div class="image">
                        <img src="{{ $post->image ? asset($post->image) : asset('no-image-product.svg') }}"
                             alt="{{ $post->title }}" class="img-fluid" loading="lazy">
                        @if($post->post_type == 'video')
                            <span class="post-type-badge"><i class="bi bi-play-circle-fill"></i></span>
                        @elseif($post->post_type == 'podcast')
                            <span class="post-type-badge"><i class="bi bi-headphones"></i></span>
                        @endif
                        @if($post->is_editor_pick)
                            <span class="editor-choice-badge" title="انتخاب سردبیر" aria-label="انتخاب سردبیر">
                                <i class="bi bi-star-fill"></i>
                            </span>
                        @endif
                    </div>
                    <div class="title">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="title-item">
                                <i class="bi bi-tag me-1"></i>
                                <span class="font-12">{{ $post->category?->title ?? 'وبلاگ' }}</span>
                            </div>
                            <div class="title-item">
                                <i class="bi bi-alarm me-1"></i>
                                <span class="font-12">{{ jdate($post->created_at)->format('d F Y') }}</span>
                            </div>
                        </div>
                        <h4 class="font-16 text-overflow-2 h4">{{ $post->title }}</h4>
                        <div class="d-flex align-items-center justify-content-between text-muted font-12 mt-1">
                            <span><i class="bi bi-person me-1"></i>{{ $post->admin?->full_name ?? 'تحریریه' }}</span>
                            <span><i class="bi bi-eye me-1"></i>{{ number_format($post->view) }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="content-box">
                <div class="container-fluid text-center py-5">
                    <i class="bi bi-journal-x font-40 text-muted d-block mb-3"></i>
                    <h3 class="font-16 fw-bold mb-2">مقاله‌ای یافت نشد</h3>
                    <p class="text-muted font-14 mb-4">برای عبارت جستجوی شما یا فیلترهای انتخابی مقاله‌ای وجود ندارد.</p>
                    <a href="{{ route('front.articles.index') }}" wire:navigate class="btn main-color-one-bg text-white border-0 rounded-pill px-4">
                        مشاهده همه مقالات
                    </a>
                </div>
            </div>
        </div>
    @endforelse
</div>

{{-- فاز ۱۶۶ — کارت مقاله سانیار (blog-item تمپلیت) — مشترک بین اسلایدر و گرید --}}
@php
    $blogCardDate   = jdate($post->created_at)->format('d F Y');
    $blogCardCat    = $post->category;
    $blogCardIsVideo   = $post->post_type === 'video';
    $blogCardIsPodcast = $post->post_type === 'podcast';
@endphp
<div class="blog-item">
    <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}" aria-label="{{ $post->title }}">
        <div class="image">
            <img src="{{ $post->image ? asset($post->image) : asset('/no-image-product.svg') }}"
                 @if(!($loop ?? null) || ! $loop->first) loading="lazy" decoding="async" @endif
                 class="img-fluid" alt="{{ $post->title }}">
        </div>
        <div class="title">
            <div class="d-flex align-items-center justify-content-between">
                <div class="title-item">
                    @if($blogCardIsVideo)
                        <i class="bi bi-play-circle me-1"></i>
                    @elseif($blogCardIsPodcast)
                        <i class="bi bi-soundwave me-1"></i>
                    @else
                        <i class="bi bi-tag me-1"></i>
                    @endif
                    <span class="font-12">{{ $blogCardCat->title ?? 'مقاله' }}</span>
                </div>
                <div class="title-item">
                    <i class="bi bi-alarm me-1"></i>
                    <span class="font-12">{{ $blogCardDate }}</span>
                </div>
            </div>
            <h4 class="font-16 text-overflow-1 h4">{{ $post->title }}</h4>
        </div>
    </a>
</div>

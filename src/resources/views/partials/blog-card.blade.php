{{-- فاز ۱۶۶ — کارت مقاله سانیار (blog-item تمپلیت) — مشترک بین اسلایدر و گرید --}}
{{-- فاز ۱۶۸ — بازطراحی مدرن مطابق نمونهٔ «برگزیده‌ها»: چیپ دسته‌بندی روی تصویر،
     فالبک تصویری برندشده وقتی مقاله تصویر ندارد، متای تمیز (تاریخ + بازدید) --}}
@php
    $blogCardDate      = jdate($post->created_at)->format('d F Y');
    $blogCardCat       = $post->category;
    $blogCardIsVideo   = $post->post_type === 'video';
    $blogCardIsPodcast = $post->post_type === 'podcast';
@endphp
<div class="blog-item snr-post-card">
    <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}" aria-label="{{ $post->title }}">
        <div class="image">
            @if($post->image)
                <img src="{{ asset($post->image) }}"
                     @if(!($loop ?? null) || ! $loop->first) loading="lazy" decoding="async" @endif
                     class="img-fluid" alt="{{ $post->title }}">
            @else
                <span class="snr-no-image" role="img" aria-label="تصویری برای این مقاله ثبت نشده است">
                    <i class="bi bi-images"></i>
                </span>
            @endif
            <span class="snr-chip">
                @if($blogCardIsVideo)
                    <i class="bi bi-play-btn-fill"></i>
                @elseif($blogCardIsPodcast)
                    <i class="bi bi-soundwave"></i>
                @else
                    <i class="bi bi-tag"></i>
                @endif
                {{ $blogCardCat->title ?? 'مقاله' }}
            </span>
        </div>
        <div class="title">
            <h4 class="h4">{{ $post->title }}</h4>
            <div class="meta-row">
                <span><i class="bi bi-alarm"></i>{{ $blogCardDate }}</span>
                <span><i class="bi bi-eye"></i>{{ number_format((int) ($post->view ?? 0)) }} بازدید</span>
            </div>
        </div>
    </a>
</div>

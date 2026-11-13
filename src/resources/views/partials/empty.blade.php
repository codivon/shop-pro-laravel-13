{{-- حالت خالی — مطابق empty-cart.html قالب سانیار --}}
<div class="content-box shadow-box rounded-3 my-4">
    <div class="text-center p-4 p-lg-5">
        <img src="{{ theme_asset('image/empty-cart.svg') }}" alt="" class="mx-auto d-block img-fluid"
            style="max-width: 260px;">
        <h6 class="font-18 mt-4 mb-2">{{ $message ?? 'چیزی برای نمایش وجود ندارد' }}</h6>
    </div>
</div>

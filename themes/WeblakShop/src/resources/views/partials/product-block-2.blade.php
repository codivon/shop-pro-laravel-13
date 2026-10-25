<div class="owl-item active" style="width: 309.083px; margin-left: 10px;">
    <div class="item">
        <a class="product-thumb  image-data-src" href="{{ route('front.products.show', ['product' => $product]) }}">
            <img class="img-fluid" data-src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $product->title }}">
        </a>
        <h2 class="post-title">
            <a href="{{ route('front.products.show', ['product' => $product]) }}">
                {{ $product->title }}
            </a>
        </h2>
        <div class="price">
            {{-- فاز ۸۱ — نشان پیش‌فروش --}}
            @if($product->isPreOrder())
                <span class="badge badge-info d-inline-block mb-1" style="background-color: #17a2b8; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem;">{{ option('dt_pre_order_badge_text', 'پیش‌فروش') }}</span>
            @endif
            @if($product->getLowestDiscount())
                <div class="discount-item">
                    <span>{{ $product->discount }}٪</span>
                </div>
                <del><span>{{ $product->getLowestDiscount() }}{{--<span>تومان</span>--}}</span></del>
            @endif


            <ins><span>{{ $product->getLowestPrice() }}{{--<span>تومان</span>--}}</span></ins>

        </div>

        @if ($product->isSpecial() && $product->special_end_date && $product->special_end_date->diffInHours(now()) <= 24)
            <div class="countdown-timer text-muted product-special-end-date" countdown data-date="{{ $product->special_end_date->format('D M d Y H:i:s O') }}" style="background: {{$widget->option('block_color')?:'#ef394e'}};">
                <span data-seconds="">0</span> :
                <span data-minutes="">0</span> :
                <span data-hours="">0</span>
                <i class="mdi mdi-clock"></i>
            </div>
        @endif
       {{-- <div class="product-box-timer">
            <span class="fa fa-clock-o"></span>
            <div class="countdown countdown-style-3 h4"
                 data-date-time="10/10/2025 24:00"
                 data-labels='{"label-second": "", "label-minute": "", "label-hour": ""}'>
            </div>
        </div>--}}
    </div>
</div>

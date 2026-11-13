<div class="owl-item @if($i==1 or $i==2 or $i==3 or $i==4)active @endif" style="width: 309.083px; margin-left: 10px;">
    <div class="item">
        <a href="{{ route('front.products.show', ['product' => $product]) }}" class="image-data-src">
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
                <del>
                                                <span>{{ $product->getLowestDiscount() }}
{{--                                                    <span>تومان</span>--}}
                                                </span>
                </del>
            @endif
            <ins>
                                                <span>{{ $product->getLowestPrice() }}
{{--                                                    <span>تومان</span>--}}
                                                </span>
            </ins>
        </div>
    </div>
</div>


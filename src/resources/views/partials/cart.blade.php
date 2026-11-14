{{-- سبد خرید کشویی سانیار (offcanvas) — سایدبار سمت چپ --}}
@php
    $render_cart = $cart ?? get_cart();
    $cart_count = $render_cart ? $render_cart->products()->count() : 0;
@endphp
{{-- data-cart-count: فاز ۱۵۶ — به‌روزرسانی لحظه‌ای شمارندهٔ هدر پس از افزودن ای‌جکسی --}}
{{-- فاز ۱۶۱ — فقط تصویر و عنوان به صفحهٔ محصول لینک می‌شوند؛ حذف و تعداد
     ای‌جکسی‌اند و هیچ بخشی از آیتم کاربر را به صفحهٔ دیگری نمی‌برد --}}
<div class="offcanvas cart-canvas offcanvas-end" tabindex="-1" id="offcanvasCart"
     aria-labelledby="offcanvasCartLabel" data-cart-count="{{ $cart_count }}">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasCartLabel">سبد خرید</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="cart-canvases">
            @if($render_cart && $cart_count)
                @foreach ($render_cart->products as $product)
                    @php
                        $cart_product_price = $product->prices()->find($product->pivot->price_id);
                    @endphp
                    <div class="item js-mini-cart-item" data-pivot="{{ $product->pivot->id }}"
                        data-qty="{{ $product->pivot->quantity }}">
                        <div class="row gy-2">
                            <div class="col-4">
                                <div class="image">
                                    <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                        class="d-block js-mini-cart-link" title="{{ $product->title }}">
                                        <img src="{{ $product->image ? asset($product->image) : theme_asset('image/product/no-image.png') }}"
                                             alt="{{ $product->title }}" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                            <div class="col-8">
                                <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                    class="js-mini-cart-link d-block text-decoration-none">
                                    <div class="title">
                                        <h6 class="font-14">{{ $product->title }}</h6>
                                        <p class="mb-0 text-muted font-12 mt-2 js-mini-qty-label">{{ $product->pivot->quantity }} عدد</p>
                                    </div>
                                </a>
                                <div class="price">
                                    <p class="text-end mb-2 price-off fw-bold js-mini-item-price">{{ number_format($cart_product_price->salePrice() * $product->pivot->quantity) }} تومان</p>
                                    @if($cart_product_price->hasDiscount())
                                        <p class="text-end price-discount js-mini-item-old">{{ number_format($cart_product_price->regularPrice() * $product->pivot->quantity) }} تومان</p>
                                    @endif
                                </div>
                                <div class="action d-flex justify-content-between align-items-center">
                                    <div class="remove bg-light rounded-3">
                                        <button type="button"
                                            class="btn border-0 p-0 bg-transparent js-mini-cart-remove"
                                            data-action="{{ route('front.cart.destroy', $product->pivot->id) }}"
                                            aria-label="حذف از سبد خرید">
                                            <i class="bi bi-x font-25"></i>
                                        </button>
                                    </div>
                                    <div class="counter js-mini-counter d-flex align-items-center gap-1">
                                        <button type="button"
                                            class="btn btn-sm border-0 p-1 js-mini-qty-btn"
                                            data-dir="down" aria-label="کاهش تعداد">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="text" name="count" class="counter text-center js-mini-qty"
                                            value="{{ $product->pivot->quantity }}"
                                            data-min="{{ max(1, (int) cart_min($cart_product_price)) }}"
                                            data-max="{{ max(1, (int) cart_max($cart_product_price)) }}" readonly>
                                        <button type="button"
                                            class="btn btn-sm border-0 p-1 js-mini-qty-btn"
                                            data-dir="up" aria-label="افزایش تعداد">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="item">
                    <div class="factor">
                        <div class="title">
                            <div class="d-flex align-items-center">
                                <img src="{{ theme_asset('image/shopping-bag.png') }}" class="img-fluid" alt="">
                                <h6 class="font-16">سفارش شما</h6>
                            </div>
                        </div>
                        <div class="d-flex mb-3 align-items-center justify-content-between">
                            <p class="fw-bold mb-0">محصول</p>
                            <p class="fw-bold mb-0">قیمت کل</p>
                        </div>
                        @foreach ($render_cart->products as $product)
                            @php
                                $cart_product_price = $product->prices()->find($product->pivot->price_id);
                            @endphp
                            <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                                <p class="mb-0">{{ \Illuminate\Support\Str::limit($product->title, 24) }}</p>
                                <p class="mb-0">{{ number_format($cart_product_price->salePrice() * $product->pivot->quantity) }} تومان</p>
                            </div>
                        @endforeach
                        <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                            <p class="mb-0 fw-bold">جمع کل:</p>
                            <p class="mb-0">{{ number_format($render_cart->discountPrice()) }} تومان</p>
                        </div>
                        <div class="action mt-3 d-flex align-items-center justify-content-center">
                            <a href="{{ route('front.cart') }}" class="btn border-0 main-color-two-bg rounded-3">سبد خرید</a>
                            <a href="{{ route('front.checkout') }}" class="btn border-0 main-color-one-bg rounded-3 ms-2">تسویه حساب</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="item">
                    <div class="cart-canvas-empty text-center py-5">
                        <img src="{{ theme_asset('image/empty-cart.svg') }}" alt="سبد خرید خالی" class="img-fluid mb-3" width="160">
                        <p class="text-muted mb-3">سبد خرید شما خالی است ...</p>
                        <a href="{{ route('front.index') }}" class="btn main-color-one-bg rounded-pill border-0">مشاهده محصولات</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

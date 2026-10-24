@php
    // فاز C سانیار: کنترلر سبد، پارشال را با render_cart رندر می‌کند
    // (کوکی سبد در همان پاسخ ست می‌شود؛ get_cart() در این لحظه هنوز آن را نمی‌بیند)
    $cart = isset($render_cart) ? $render_cart : get_cart();
    $cart_total = 0;
@endphp
<div class="offcanvas cart-canvas offcanvas-end" tabindex="-1" id="offcanvasCart" aria-labelledby="offcanvasCartLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasCartLabel">سبد خرید</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="cart-canvases">
            @if($cart && $cart->products()->count())
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
                        @foreach($cart->products as $product)
                            @php
                                $cart_product_price = $product->prices()->find($product->pivot->price_id);
                                if ($cart_product_price) {
                                    $cart_total += $cart_product_price->salePrice() * $product->pivot->quantity;
                                }
                            @endphp
                            <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                                <p class="mb-0">
                                    <a href="{{ route('front.products.show', ['product' => $product]) }}" class="text-dark text-decoration-none">
                                        {{ $product->title }}
                                    </a>
                                    <span class="text-muted font-12">({{ $product->pivot->quantity }} عدد)</span>
                                </p>
                                <p class="mb-0">{{ number_format($cart_product_price?->salePrice() * $product->pivot->quantity) }} تومان</p>
                            </div>
                        @endforeach
                        <div class="factor-item p-2 rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-between">
                            <p class="mb-0 fw-bold">جمع کل:</p>
                            <p class="mb-0">{{ number_format($cart_total) }} تومان</p>
                        </div>
                        <div class="action mt-3 d-flex align-items-center justify-content-center">
                            <a href="{{ route('front.cart') }}" class="btn border-0 main-color-two-bg rounded-3">سبد خرید</a>
                            <a href="{{ route('front.checkout') }}" class="btn border-0 main-color-one-bg rounded-3 ms-2">تسویه حساب</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="item text-center py-5">
                    <i class="bi bi-basket font-50 text-muted"></i>
                    <p class="text-muted mt-3">سبد خرید شما خالی است</p>
                    <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg rounded-3 mt-2">مشاهده محصولات</a>
                </div>
            @endif
        </div>
    </div>
</div>

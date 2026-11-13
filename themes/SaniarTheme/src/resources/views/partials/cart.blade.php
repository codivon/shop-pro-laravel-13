@php
    $cart = isset($render_cart) ? $render_cart : $cart;

@endphp
<div class="mini-cart-header" id="cart-list-item">

    @if($cart && $cart->products()->count())
        <a href="{{route('front.cart')}}" style="color: #4a5f73;">
            <span class="mdi mdi-basket"></span>
            <span class="cart-count count">{{@$cart->products()->count()}}</span>
        </a>
        <div class="mini-cart-dropdown">
            <div class="header-cart-info-header">
                <div class="header-cart-info-count">{{@$cart->products()->count()}} کالا</div>
                <a href="{{route('front.cart')}}" class="header-cart-info-link">
                    <span>مشاهده سبد خرید</span>
                </a>
            </div>
            <div class="wrapper">
                <div class="scrollbar" id="style-1">
                    <div class="force-overflow">
                        <ul class="header-basket-list">
                            @foreach ($cart->products as $product)
                                <li class="js-mini-cart-item">
                                    <a href="{{ route('front.products.show', ['product' => $product]) }}" class="header-basket-list-item">
                                        <div class="header-basket-list-item-image">
                                            <img src="{{ $product->image ? asset($product->image) : '/empty.svg' }}"
                                                 alt="{{ $product->title }}">
                                        </div>
                                        <div class="header-basket-list-item-content">
                                            <h1 class="header-basket-list-item-title">{{ $product->title }}</h1>
                                            <span class="header-basket-list-item-shipping-type">
                                          @php
                                              $cart_product_price = $product->prices()->find($product->pivot->price_id);
                                          @endphp

                                <div class="pt-1">
                                    {{  number_format($cart_product_price->salePrice() * $product->pivot->quantity)}} تومان</div>
                                        @if($cart_product_price->hasDiscount())
                                            <del class="text-danger">{{ number_format($cart_product_price->regularPrice() * $product->pivot->quantity) }} تومان</del>
                                        @endif
                                    </span>
                                            <div class="header-basket-list-item-footer">
                                                <div class="header-basket-list-item-props">
                                                                    <span class="header-basket-list-item-props-item"> {{$product->pivot->quantity}}
                                                                        عدد</span>
                                                    @foreach ($cart_product_price->get_attributes as $attribute)
                                                        @if ($attribute->group->type == 'color')
                                                    <span class="header-basket-list-item-props-item">
                                                                        <span class="header-basket-list-item-color-badge" style="background: {{ $attribute->value }}"> </span>
                                                                       {{ $attribute->name }}
                                                     </span>
                                                        @endif
                                                    @endforeach

                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="header-cart-info-footer">
                <div class="header-cart-info-total">
                    <span class="header-cart-info-total-text">مبلغ قابل پرداخت</span>
                    <p class="header-cart-info-total-amount">
                        <span class="header-cart-info-total-amount-number ">{{ number_format($cart->discountPrice()) }} </span>
                        <span> تومان</span>
                    </p>
                </div>
                <div>
                    <a href="{{route('front.checkout')}}" class="header-cart-info-submit btn btn-danger">ثبت سفارش</a>
                </div>
            </div>
        </div>
    @else
        <a href="{{route('front.cart')}}" style="color: #4a5f73;">
            <span class="mdi mdi-basket"></span>
            <span class="cart-count count">0</span>
        </a>
        <div class="mini-cart-dropdown">
            <div class="header-cart-info-header">
                <div class="header-cart-info-count">0 کالا</div>
                <a href="{{route('front.cart')}}" class="header-cart-info-link">
                    <span>مشاهده سبد خرید</span>
                </a>
            </div>
            <div class="wrapper">
                <p class="wrapper-empty-card-header">  سبد خرید شما خالی است ...</p>

            </div>

        </div>


    @endif

</div>


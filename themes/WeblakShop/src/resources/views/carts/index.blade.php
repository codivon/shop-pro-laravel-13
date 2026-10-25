@extends('front::layouts.master')
@push('style')
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css')}}">
    <link rel="stylesheet" href="{{theme_asset('css/materialdesignicons.css.map')}}">
@endpush

@section('content')
    <!-- Start main-content -->

            @if($cart && $cart->products()->count())

                <!--  cart------------------>


                <div class="sticky-sidebar"></div>
                <div class="col-12">
                    <div class="page-content">
                        @if(!check_cart_quantity())
                            <div class="message-light-error">
                                موجودی برخی از محصولات اضافه شده به سبد خرید به اتمام رسیده و یا کمتر از تعداد اضافه شده به سبد خرید است،  لطفا سبد خریدتان را ویرایش کنید.
                            </div>

                        @endif
                        <div class="col-lg-12 col-md-12 col-xs-12 pull-right">

                            <div class="checkout-tab">
                                <div class="checkout-tab-pill listing-active-cart">
                                    سبد خرید
                                    <span class="checkout-tab-counter">{{$cart->products()->count()}}</span>
                                </div>
{{--                                <div class="checkout-tab-pill">لیست خرید بعدی</div>--}}
                            </div>
                            <div class="text-left">
                                <button id="update-cart" data-action="{{ route('front.cart') }}" data-redirect="{{ route('front.cart') }}" type="button" class="btn btn-light"> بروزرسانی سبد خرید </button>
                            </div>
                        </div>

                    </div>
                    <div class="cart-tab-main">
                        <div class="col-lg-9 col-md-9 col-xs-12 pull-right">
                            <div class="page-content-cart" >
                                <form  id="cart-update-form" action="{{ route('front.cart') }}" method="POST">
                                    @method('put')
                                    @csrf



                                    @foreach($cart->products as $product)

                                        @php

                                            $price_to_stock = $product->prices()->find($product->pivot->price_id);
                                            // فاز ۷۸ — پیش‌فروش: بدون هشدار کمبود موجودی
                                            $has_stock = $product->pre_order ? ['status' => true] : $price_to_stock->hasStock($product->pivot->quantity);
                                            @endphp
                                        <div class="checkout-body">
                                            <span class="remove-from-cart cursor-pointer remove-card" data-action="{{ route('front.cart.destroy', ['id' => $product->pivot->id]) }}"><i class="mdi mdi-close"></i></span>
                                            <a href="{{ route('front.products.show', ['product' => $product]) }}" class="col-thumb"><img src="{{ $product->image ? asset($product->image) : asset('empty.svg') }}" alt="{{ $product->title }}"></a>

                                            <div class="checkout-col-desc">
                                                <a href="{{ route('front.products.show', ['product' => $product]) }}">
                                                    <h3>
                                                        {{ $product->title }}
                                                        @if (!$product->isPhysical())
                                                            <span class="badge badge-info" style="font-size: 0.7rem; background-color: #17a2b8; color: #fff; padding: 3px 8px; border-radius: 12px;">📥 دانلودی </span>
                                                        @endif
                                                    </h3>
                                                </a>
                                                <div class="checkout-variant-color">
                                                    @if ($product->isPhysical())
                                                        @foreach ($price_to_stock->get_attributes as $attribute)
                                                            @if ($attribute->group->type == 'color')
                                                            <span class="checkout-variant-title"><span class="float-right">{{ $attribute->group->name }}:</span>
                                                                 <span class="order-product-color d-inline-block float-right" style="margin: 7px 5px 0 5px;background-color: {{ $attribute->value }};"></span>
                                                                <span class="float-right">{{ $attribute->name }}</span>
                                                            </span>
                                                            @else
                                                                <span class="checkout-variant-title">{{ $attribute->group->name }} : {{ $attribute->name }}</span>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <span class="checkout-variant-title">{{ $price_to_stock->file->title }}</span>
                                                    @endif
                                                        @if (!$has_stock['status'])
                                                            <small class="text-danger w-100 display-inline-block">{{ $has_stock['message'] }}</small>
                                                        @endif
                                                </div>

                                                @if ($product->isPhysical())
                                                <div class="quantity">
                                                    <input class="quantity cart_quantity count" type="number" min="{{ cart_min($price_to_stock) }}" max="{{ cart_max($price_to_stock) }}" name="product-{{ $product->pivot->id }}" step="1" value="{{ $product->pivot->quantity }}" data-minus-class="mdi mdi-minus" data-remove-class="mdi mdi-delete-outline" required>
                                                    <div class="quantity-nav counter-box">
                                                        <div class="quantity-button quantity-up inc">+</div>
                                                        <div class="quantity-button quantity-down dec">-</div>
                                                    </div>
                                                </div>
                                                @endif
                                                <a class="add-to-sfl float-left d-inline-block">
                                                    <div class="cart-item-product-price float-none line-height-0">
                                                        {{ number_format($price_to_stock->discountPrice() * $product->pivot->quantity) }}
                                                        <span>تومان</span>
                                                    </div>
                                                    @if($price_to_stock->hasDiscount())
                                                        <del class="text-danger old-cart-product-price"><span class="currency-suffix"></span>{{ number_format($price_to_stock->regularPrice() * $product->pivot->quantity) }} <span class="currency-suffix">تومان</span></del>
                                                    @endif
                                                </a>

                                            </div>
                                        </div>
                                    @endforeach
                                </form>
                            </div>

                        </div>
                        <div class="col-lg-3 col-md-3 col-xs-12 pull-left sticky-sidebar">
                        @include('front::carts.partials.checkout-sidebar')
                        </div>
                    </div>

                    <div class="cart-tab-main" style="display:none;">
                        <div class="col-lg-8 col-md-8 col-xs-12 pull-right">
                            <div class="page-content-cart">
                                <div class="container">
                                    <div class="checkout-empty">
                                        <div class="checkout-empty-icon">
                                            <span class="mdi mdi-cart-remove"></span>
                                        </div>
                                        <div class="checkout-empty-title">لیست خرید بعدی شما خالی است!</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-xs-12 pull-left">
                            <div class="page-aside">
                                <div class="checkout-summary">
                                    <h1>لیست خرید بعدی چیست؟</h1>
                                    <p>
                                        شما می‌توانید محصولاتی که به سبد خرید
                                        خود افزوده اید و موقتا قصد خرید آن‌ها را ندارید، در لیست خرید بعدی خود قرار داده و
                                        هر زمان مایل بودید آن‌ها را مجدداً به سبد خرید اضافه کرده و خرید آن‌ها را تکمیل کنید.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--  cart------------------>


            @else
                <div class="col-12">
                    <div class="cart-page">
                        <div class="container">
                            <div class="checkout-empty">
                                <div class="checkout-empty-empty-cart-icon"></div>
                                <div class="checkout-empty-title">سبد خرید شما خالی است!</div>
                                <div class="col-lg-6 col-md-6!important col-xs-12 mx-auto">
                                    <div class="checkout-empty-links">

                                        <p>
                                            می‌توانید برای مشاهده محصولات بیشتر به صفحه اصلی بروید.
                                        </p>
                                        <div class="checkout-empty-link-urls">
                                            <a href="/">
                                                صفحه اصلی
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            @endif


    <!-- End main-content -->

    <!-- Start Modal location new -->
    <div class="modal fade" id="delete-modal" role="dialog"
        aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-md"
            role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalCenterTitle">
                        <i class="now-ui-icons location_pin"></i>
                        حذف محصول
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>آیا تمایل به حذف این محصول از سبدخرید دارید؟</p>

                    <div class="form-ui dt-sl p-0">
                        <form id="delete-form" class="text-center p-0" action="#" method="POST">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-danger btn-md">بله حذف شود</button>
                            <button class="btn btn-light" data-dismiss="modal">لغو</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal location new -->


@endsection

@push('scripts')
    <script src="{{theme_asset('js/theia-sticky-sidebar.min.js')}}"></script>
    <script src="{{ theme_asset('js/pages/cart.js') }}?v=3"></script>
@endpush

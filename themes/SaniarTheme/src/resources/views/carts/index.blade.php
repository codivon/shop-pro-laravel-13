@extends('front::layouts.master')

@section('content')
    <!-- Start main-content -->
    <main class="main-content">

        <!-- start breadcroumb -->
        <div class="bread-crumb pt-3">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid">
                        <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('front.index') }}"
                                        class="font-14 text-muted">خانه</a></li>
                                <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">
                                    سبد خرید</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end breadcroumb -->

        <!-- start content -->
        <div class="content">
            <div class="container-fluid">

                <div class="line-step-container d-sm-block d-none">
                    <div class="line-step">
                        <div class="line-step-boxs">
                            <div class="line-step-box complete">
                                <a href="{{ route('front.cart') }}">
                                    <p>سبد خرید</p>
                                    <div class="icon">1</div>
                                </a>
                            </div>
                            <div class="line-step-box disabled">
                                <a href="{{ route('front.checkout') }}">
                                    <p>جزییات پرداخت</p>
                                    <div class="icon">2</div>
                                </a>
                            </div>
                            <div class="line-step-box disabled">
                                <a href="javascript:void(0)">
                                    <p>تکمیل سفارش</p>
                                    <div class="icon">3</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            @if($cart && $cart->products()->count())

                <div class="container-fluid">
                    @if(!check_cart_quantity())
                        <div class="alert alert-warning rounded-3 font-14 d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill ms-2"></i>
                            <span>موجودی برخی از محصولات اضافه شده به سبد خرید به اتمام رسیده و یا کمتر از تعداد اضافه شده
                                به سبد خرید است، لطفا سبد خریدتان را ویرایش کنید.</span>
                        </div>
                    @endif

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-bag-check-fill main-color-one-color font-20 me-1"></i>
                            <h6 class="font-16 mb-0">سبد خرید</h6>
                            <span class="badge ms-2 main-color-one-bg rounded-pill">{{ $cart->products()->count() }}</span>
                        </div>
                        <button id="update-cart" data-action="{{ route('front.cart') }}"
                            data-redirect="{{ route('front.cart') }}" type="button"
                            class="btn btn-light rounded-3 font-14">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            بروزرسانی سبد خرید
                        </button>
                    </div>

                    <div class="cart-product">
                        <div class="row gy-4">
                            <div class="col-lg-9">
                                <form id="cart-update-form" action="{{ route('front.cart') }}" method="POST">
                                    @method('put')
                                    @csrf

                                    @foreach($cart->products as $product)
                                        @php
                                            $price_to_stock = $product->prices()->find($product->pivot->price_id);
                                            // فاز ۷۸ — پیش‌فروش: بدون هشدار کمبود موجودی
                                            $has_stock = $product->pre_order ? ['status' => true] : $price_to_stock->hasStock($product->pivot->quantity);
                                        @endphp
                                        <div class="cart-product-item">
                                            <div class="content-box">
                                                <div class="container-fluid">
                                                    <div class="cart-items">
                                                        <div class="item">
                                                            <div class="row gy-2">
                                                                <div class="col-2 w-100-in-400">
                                                                    <div class="image">
                                                                        <a href="{{ route('front.products.show', ['product' => $product]) }}">
                                                                            <img src="{{ $product->image ? asset($product->image) : asset('empty.svg') }}"
                                                                                alt="{{ $product->title }}" class="img-fluid">
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                                <div class="col-10 w-100-in-400">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div class="d-flex align-items-start flex-column me-2">
                                                                            <div class="title d-flex align-items-center flex-wrap">
                                                                                <h6 class="font-16">
                                                                                    <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                                                                        class="text-dark">{{ $product->title }}</a>
                                                                                    @if (!$product->isPhysical())
                                                                                        <span class="badge ms-2 bg-info rounded-pill">دانلودی</span>
                                                                                    @elseif ($product->isPreOrder())
                                                                                        {{-- فاز ۸۱ — نشان پیش‌فروش در سبد خرید --}}
                                                                                        <span class="badge ms-2 bg-info rounded-pill">{{ option('dt_pre_order_badge_text', 'پیش‌فروش') }}</span>
                                                                                    @endif
                                                                                </h6>
                                                                            </div>
                                                                            <div class="cart-item-feature d-flex align-items-center flex-wrap mt-3">
                                                                                @if ($product->isPhysical())
                                                                                    @foreach ($price_to_stock->get_attributes as $attribute)
                                                                                        <div class="item d-flex align-items-center">
                                                                                            @if ($attribute->group->type == 'color')
                                                                                                <div class="icon"><i class="bi bi-palette2"></i></div>
                                                                                                <div class="saller-name mx-2">{{ $attribute->group->name }}:</div>
                                                                                                <div class="saller-name text-muted">
                                                                                                    <div class="product-meta-color-items mt-0">
                                                                                                        <label class="btn-light mb-0 px-2 py-1">
                                                                                                            <span style="background-color: {{ $attribute->value }};"></span>
                                                                                                            {{ $attribute->name }}
                                                                                                        </label>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @else
                                                                                                <div class="icon"><i class="bi bi-bounding-box-circles"></i></div>
                                                                                                <div class="saller-name mx-2">{{ $attribute->group->name }}:</div>
                                                                                                <div class="saller-name text-muted">{{ $attribute->name }}</div>
                                                                                            @endif
                                                                                        </div>
                                                                                    @endforeach
                                                                                @else
                                                                                    <div class="item d-flex align-items-center">
                                                                                        <div class="icon"><i class="bi bi-download"></i></div>
                                                                                        <div class="saller-name text-muted">{{ $price_to_stock->file->title }}</div>
                                                                                    </div>
                                                                                @endif
                                                                                @if (!$has_stock['status'])
                                                                                    <div class="item d-flex align-items-center text-danger font-12">
                                                                                        <div class="icon"><i class="bi bi-exclamation-circle"></i></div>
                                                                                        <div class="mx-1">{{ $has_stock['message'] }}</div>
                                                                                    </div>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="remove danger-label">
                                                                            <a href="javascript:void(0)" class="remove-card"
                                                                                data-action="{{ route('front.cart.destroy', ['id' => $product->pivot->id]) }}"
                                                                                aria-label="حذف از سبد خرید">
                                                                                <i class="bi bi-trash-fill font-25"></i>
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="action d-flex flex-wrap justify-content-sm-end justify-content-center align-items-center mt-3">
                                                                        <div class="cart-item-feature d-flex align-items-center flex-wrap">
                                                                            @if($price_to_stock->hasDiscount())
                                                                                <div class="item d-flex align-items-center me-2">
                                                                                    <p class="mb-0 old-price font-16">{{ number_format($price_to_stock->regularPrice() * $product->pivot->quantity) }}</p>
                                                                                </div>
                                                                            @endif
                                                                            <div class="item d-flex align-items-center">
                                                                                <p class="mb-0 new-price font-16 main-color-one-color">{{ number_format($price_to_stock->discountPrice() * $product->pivot->quantity) }} تومان</p>
                                                                            </div>
                                                                        </div>
                                                                        @if ($product->isPhysical())
                                                                            <div class="counter">
                                                                                <input class="cart_quantity count" type="number"
                                                                                    min="{{ cart_min($price_to_stock) }}"
                                                                                    max="{{ cart_max($price_to_stock) }}"
                                                                                    name="product-{{ $product->pivot->id }}" step="1"
                                                                                    value="{{ $product->pivot->quantity }}"
                                                                                    data-minus-class="mdi mdi-minus"
                                                                                    data-remove-class="mdi mdi-delete-outline" required>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </form>
                            </div>
                            <div class="col-lg-3">
                                @include('front::carts.partials.checkout-sidebar')
                            </div>
                        </div>
                    </div>
                </div>

            @else
                {{-- سبد خرید خالی — مطابق empty-cart.html قالب سانیار --}}
                <div class="container-fluid">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="text-center py-5">
                                <img src="{{ theme_asset('image/empty-cart.svg') }}" style="max-width: 400px;"
                                    alt="سبد خرید خالی" class="mx-auto d-block img-fluid">
                                <h6 class="font-18 mt-4">سبد خرید شما خالی است!</h6>
                                <p class="text-muted font-14 mt-2 mb-4">
                                    می‌توانید برای مشاهده محصولات بیشتر به صفحه اصلی بروید.
                                </p>
                                <a href="{{ route('front.index') }}"
                                    class="btn main-color-one-bg border-0 rounded-3">صفحه اصلی</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
        <!-- end content -->
    </main>
    <!-- End main-content -->

    <!-- Start delete modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        <i class="bi bi-trash3 main-color-one-color me-1"></i>
                        حذف محصول
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>آیا تمایل به حذف این محصول از سبدخرید دارید؟</p>
                    <form id="delete-form" action="#" method="POST"
                        class="d-flex align-items-center justify-content-center gap-2 mb-0">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-danger rounded-3">بله حذف شود</button>
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">لغو</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End delete modal -->
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/carts/cart.js') }}"></script>
    <script src="{{ theme_asset('js/pages/cart.js') }}?v=3"></script>
@endpush

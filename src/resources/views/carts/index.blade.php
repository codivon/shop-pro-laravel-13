{{-- صفحه سبد خرید — قرارداد: $cart با کامپوزر تزریق می‌شود ؛ $city_id از CartController@show --}}
@extends('front::layouts.master', ['title' => 'سبد خرید'])

@push('meta')
    <meta name="robots" content="noindex, follow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/cart.css') }}">
@endpush

@section('content')
    {{-- بردکرامب --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">سبد خرید</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            @include('front::carts.partials.steps', ['step' => 1, 'done' => 0])

            @if($cart && $cart->products()->count())

                {{-- هشدار کمبود موجودی --}}
                @if(!check_cart_quantity())
                    <div class="alert alert-danger rounded-3 font-14" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        موجودی برخی کالاهای سبد شما کمتر از تعداد درخواستی است؛ لطفاً پیش از ادامه، تعداد را اصلاح کنید.
                    </div>
                @endif

                {{-- خطاهای بروزرسانی (406) --}}
                <div id="cart-errors" class="d-none">
                    <div class="alert alert-danger rounded-3 font-14" role="alert">
                        <ul class="mb-0"></ul>
                    </div>
                </div>

                <div class="cart-product">
                    <div class="row gy-4">

                        {{-- ستون اقلام --}}
                        <div class="col-lg-9">

                            <form id="cart-update-form" action="{{ route('front.cart') }}" method="POST">
                                @method('put')
                                @csrf

                                @foreach($cart->products as $product)
                                    @php
                                        $price_to_stock = $product->prices()->find($product->pivot->price_id);
                                        $has_stock = $price_to_stock ? $price_to_stock->hasStock($product->pivot->quantity) : ['status' => false, 'message' => 'این کالا موجود نیست'];
                                    @endphp

                                    <div class="cart-product-item" data-pivot-id="{{ $product->pivot->id }}">
                                        <div class="content-box">
                                            <div class="container-fluid">
                                                <div class="cart-items">
                                                    <div class="item">
                                                        <div class="row gy-2">

                                                            {{-- تصویر --}}
                                                            <div class="col-2 w-100-in-400">
                                                                <div class="image">
                                                                    <a href="{{ route('front.products.show', ['product' => $product]) }}">
                                                                        <img src="{{ $product->image ? asset($product->image) : asset('empty.svg') }}"
                                                                             class="img-fluid" alt="{{ $product->title }}" loading="lazy">
                                                                    </a>
                                                                </div>
                                                            </div>

                                                            {{-- اطلاعات --}}
                                                            <div class="col-10 w-100-in-400">
                                                                <div class="d-flex justify-content-between align-items-start">

                                                                    <div class="d-flex align-items-start flex-column me-2">
                                                                        <div class="title d-flex align-items-center flex-wrap">
                                                                            <a href="{{ route('front.products.show', ['product' => $product]) }}" class="text-dark text-decoration-none">
                                                                                <h6 class="font-16 mb-0">{{ $product->title }}</h6>
                                                                            </a>
                                                                            @if($price_to_stock && $price_to_stock->hasDiscount())
                                                                                @php
                                                                                    $cart_discount_percent = $price_to_stock->regularPrice() > 0
                                                                                        ? round((1 - $price_to_stock->discountPrice() / $price_to_stock->regularPrice()) * 100)
                                                                                        : (int) ($price_to_stock->discount ?? 0);
                                                                                @endphp
                                                                                @if($cart_discount_percent > 0)
                                                                                    <span class="badge ms-2 danger-label rounded-pill">{{ $cart_discount_percent }}%</span>
                                                                                @endif
                                                                            @endif
                                                                            @if(!$product->isPhysical())
                                                                                <span class="badge ms-2 main-color-one-bg rounded-pill text-white">دانلودی</span>
                                                                            @endif
                                                                        </div>

                                                                        {{-- واریانت‌ها و فروشنده --}}
                                                                        <div class="cart-item-feature d-flex align-items-center flex-wrap mt-3">
                                                                            @if($price_to_stock && $price_to_stock->seller)
                                                                                <div class="item d-flex align-items-center">
                                                                                    <div class="icon"><i class="bi bi-shop"></i></div>
                                                                                    <div class="mx-2 text-muted">فروشنده:</div>
                                                                                    <div class="text-muted">{{ $price_to_stock->seller->name }}</div>
                                                                                </div>
                                                                            @endif
                                                                            @foreach($price_to_stock ? $price_to_stock->get_attributes : [] as $attribute)
                                                                                <div class="item d-flex align-items-center">
                                                                                    <div class="icon"><i class="bi bi-palette2"></i></div>
                                                                                    <label class="btn-light mb-0 px-2 py-1 d-inline-flex align-items-center">
                                                                                        @if($attribute->group->type == 'color')
                                                                                            <span class="d-inline-block rounded-circle ms-1" style="width:12px;height:12px;background-color:{{ $attribute->value }};"></span>
                                                                                        @endif
                                                                                        {{ $attribute->group->name }}: {{ $attribute->name }}
                                                                                    </label>
                                                                                </div>
                                                                            @endforeach
                                                                            @if(!$product->isPhysical() && $price_to_stock && $price_to_stock->file)
                                                                                <div class="item d-flex align-items-center">
                                                                                    <div class="icon"><i class="bi bi-file-earmark-arrow-down"></i></div>
                                                                                    <div class="mx-2 text-muted">{{ $price_to_stock->file->title }}</div>
                                                                                </div>
                                                                            @endif
                                                                        </div>

                                                                        {{-- خطای موجودی --}}
                                                                        @if(!$has_stock['status'])
                                                                            <div class="mt-2 font-12 text-danger"><i class="bi bi-exclamation-circle"></i> {{ $has_stock['message'] }}</div>
                                                                        @endif
                                                                    </div>

                                                                    {{-- حذف --}}
                                                                    <div class="remove danger-label">
                                                                        <a href="#delete-modal" class="text-decoration-none remove-card"
                                                                           data-action="{{ route('front.cart.destroy', ['id' => $product->pivot->id]) }}"
                                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                                           aria-label="حذف از سبد خرید">
                                                                            <i class="bi bi-trash-fill font-25"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>

                                                                {{-- قیمت و تعداد --}}
                                                                <div class="action d-flex flex-wrap justify-content-sm-end justify-content-center align-items-center mt-3 gap-2">
                                                                    @if($price_to_stock)
                                                                        @if($price_to_stock->hasDiscount())
                                                                            <p class="mb-0 old-price font-16">{{ number_format($price_to_stock->regularPrice() * $product->pivot->quantity) }}</p>
                                                                        @endif
                                                                        <p class="mb-0 new-price font-16">{{ number_format($price_to_stock->discountPrice() * $product->pivot->quantity) }} تومان</p>
                                                                    @endif

                                                                    @if($product->isPhysical())
                                                                        <div class="counter d-flex align-items-center" dir="ltr">
                                                                            <button type="button" class="btn-counter waves-effect waves-light" data-dir="up" aria-label="افزایش تعداد">
                                                                                <i class="bi bi-plus-lg"></i>
                                                                            </button>
                                                                            <input type="text" class="counter-input" inputmode="numeric"
                                                                                   name="product-{{ $product->pivot->id }}"
                                                                                   value="{{ $product->pivot->quantity }}"
                                                                                   data-min="{{ cart_min($price_to_stock) }}"
                                                                                   data-max="{{ cart_max($price_to_stock) }}"
                                                                                   aria-label="تعداد">
                                                                            <button type="button" class="btn-counter waves-effect waves-light" data-dir="down" aria-label="کاهش تعداد">
                                                                                <i class="bi bi-dash-lg"></i>
                                                                            </button>
                                                                        </div>
                                                                    @else
                                                                        <input type="hidden" name="product-{{ $product->pivot->id }}" value="{{ $product->pivot->quantity }}">
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

                                {{-- دکمه بروزرسانی سبد --}}
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="button" id="update-cart" class="btn border-0 main-color-one-bg rounded-3 px-4 waves-effect waves-light"
                                            data-action="{{ route('front.cart') }}" data-redirect="{{ route('front.cart') }}">
                                        <i class="bi bi-arrow-repeat"></i> بروزرسانی سبد خرید
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- ستون خلاصه سفارش --}}
                        <div class="col-lg-3">
                            <div class="cart-canvases position-sticky top-0">
                                @include('front::carts.partials.checkout-sidebar')
                            </div>
                        </div>
                    </div>
                </div>

            @else
                {{-- سبد خالی --}}
                <div class="content-box">
                    <div class="container-fluid">
                        <div class="text-center py-5">
                            <img src="{{ theme_asset('image/empty-cart (1).svg') }}" class="mx-auto d-block empty-cart-image" alt="سبد خرید خالی است">
                            <h6 class="font-18 mt-4">سبد خرید شما خالی است</h6>
                            <p class="font-14 text-muted">می‌توانید از میان هزاران کالا، محصولات مورد نظر خود را انتخاب کنید.</p>
                            <div class="d-flex justify-content-center gap-2 mt-3">
                                <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg rounded-3 px-4">مشاهده همه محصولات</a>
                                <a href="{{ route('front.index') }}" class="btn border-0 btn-light rounded-3 px-4">بازگشت به صفحه اصلی</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- مودال تأیید حذف --}}
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true" aria-labelledby="delete-modal-label">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="delete-modal-label">حذف از سبد خرید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="font-14 mb-0">آیا از حذف این کالا از سبد خرید مطمئن هستید؟</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">انصراف</button>
                    <form id="delete-form" action="" method="POST" class="d-inline">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn border-0 rounded-3 text-white btn-remove-item">حذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/carts/cart.js') }}"></script>
@endpush

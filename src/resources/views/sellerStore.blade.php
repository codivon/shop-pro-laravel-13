@extends('front::layouts.master', ['title' => $seller_info->business_name])

@push('meta')
    <meta property="og:title" content="{{ $seller_info->business_name ?: $seller_info->fullname }}" />
    <meta property="og:url" content="{{ route('front.showSellerStore', ['seller' => $seller]) }}" />
    <meta name="description" content="{{ $seller_info->bio ?: $seller_info->business_name}}">
    <meta name="keywords" content="{{ $seller_info->business_name }}">
    <link rel="canonical" href="{{ route('front.showSellerStore', ['seller' => $seller]) }}" />
@endpush

@section('content')
    {{-- فروشگاه فروشنده — سانیار bread-crumb + سایدبار + گرید product-box --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a>
                            </li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">فروشگاه {{ $seller_info->business_name }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid py-3">
            <div class="row gy-4">

                {{-- سایدبار فروشنده --}}
                <div class="col-lg-3 col-md-12 col-sm-12">
                    <div class="position-sticky top-0">

                        <div class="content-box mb-3">
                            <div class="container-fluid text-center">
                                <div class="seller-avatar d-flex align-items-center justify-content-center rounded-3 shadow-box mx-auto my-3">
                                    @if($seller->seller_info->logo)
                                        <img class="rounded-3" src="{{ asset($seller->seller_info->logo) }}" alt="avatar" width="80" height="80">
                                    @else
                                        <span class="main-color-one-color font-30 fw-bold p-4"><?= mb_substr($seller->seller_info->business_name, 0, 1, 'UTF-8') ?></span>
                                    @endif
                                </div>
                                <h5 class="font-16 mt-3 mb-1">
                                    {{ $seller->business_name }}
                                    <i class="bi bi-patch-check-fill main-color-one-color"></i>
                                </h5>
                                <p class="text-muted font-12 mb-2">{{ $seller->created_at ? $seller->created_at->diffForHumans() : 'چند لحظه پیش' }}</p>
                                <p class="font-14 mb-1">آمار رضایت محصولات</p>
                                <p class="font-14 mb-3">
                                    <span class="text-muted">عملکرد: </span>
                                    <span class="fw-bold main-color-one-color">عالی</span>
                                </p>
                                <div class="row g-2 text-center pb-3">
                                    <div class="col-6">
                                        <div class="bg-body-tertiary rounded-3 p-2">
                                            <div class="fw-bold">% 100</div>
                                            <div class="font-12 text-muted">تعهد ارسال</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-body-tertiary rounded-3 p-2">
                                            <div class="fw-bold">% 100</div>
                                            <div class="font-12 text-muted">بدون مرجوعی</div>
                                        </div>
                                    </div>
                                </div>
                                @if($seller->seller_info and $seller->seller_info->bio)
                                    <div class="text-start border-top pt-3 pb-3">
                                        <p class="font-12 text-muted mb-0 lh-base text-justify">
                                            <span class="fw-bold text-dark">بیوگرافی: </span>{{ $seller->seller_info->bio }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($has_filter)
                            @include('front::products.partials.seller-filters', ['seller' => $seller, 'filterable' => $filterable, 'products_id' => $products_id, 'categorise' => $categorise])
                        @endif

                        {{-- نوار بازه قیمت — سانیار js-price-slider --}}
                        <div class="content-box mb-3">
                            <div class="container-fluid">
                                <div class="filter-item py-3">
                                    <h5 class="filter-item-title">قیمت</h5>
                                    <div class="filter-item-content">
                                        <div class="filter-price js-price-slider"
                                             data-min-selected="{{ request('min_price') ?: $min_price }}"
                                             data-max-selected="{{ request('max_price') ?: $max_price }}"
                                             data-min="{{ $min_price }}"
                                             data-max="{{ $max_price }}">
                                            <div class="filter-slider">
                                                <div class="price-slider"></div>
                                            </div>
                                            <ul class="filter-range mb-4">
                                                <li>
                                                    <input type="text" class="js-slider-range-from min-price-filter" disabled>
                                                    <span>تومان</span>
                                                </li>
                                                <li class="label fw-bold">تا</li>
                                                <li>
                                                    <input type="text" class="js-slider-range-to max-price-filter" disabled>
                                                    <span>تومان</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- لیست محصولات --}}
                <div id="category-products-div" data-action="{{ route('front.showSellerStore', ['seller' => $seller]) }}" class="col-lg-9 col-md-12 col-sm-12">

                    {{-- مرتب‌سازی — سانیار category-sort --}}
                    <div class="category-sort mb-3">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="d-flex align-items-center flex-wrap">
                                    <div>
                                        <h5 class="font-18 mb-0">مرتب سازی</h5>
                                        <p class="mb-0 text-muted font-14">براساس</p>
                                    </div>
                                    <div class="form-checks ms-md-5 ms-0 mt-md-0 mt-3">
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'latest' || !request('sort_type') ? 'active' : '' }}">
                                            <input class="form-check-input" type="radio" name="sort_type" id="sortLatest" value="latest" {{ request('sort_type') == 'latest' || !request('sort_type') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sortLatest">جدید ترین</label>
                                        </div>
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'view' ? 'active' : '' }}">
                                            <input class="form-check-input" type="radio" name="sort_type" id="sortView" value="view" {{ request('sort_type') == 'view' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sortView">پربازدید ترین</label>
                                        </div>
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'sale' ? 'active' : '' }}">
                                            <input class="form-check-input" type="radio" name="sort_type" id="sortSale" value="sale" {{ request('sort_type') == 'sale' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sortSale">پرفروش ترین</label>
                                        </div>
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'cheapest' ? 'active' : '' }}">
                                            <input class="form-check-input" type="radio" name="sort_type" id="sortCheapest" value="cheapest" {{ request('sort_type') == 'cheapest' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sortCheapest">ارزان ترین</label>
                                        </div>
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'expensivest' ? 'active' : '' }}">
                                            <input class="form-check-input" type="radio" name="sort_type" id="sortExpensive" value="expensivest" {{ request('sort_type') == 'expensivest' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sortExpensive">گران ترین</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($products->count())
                        <div class="category-items">
                            <div class="row gy-3">
                                @foreach($products as $product)
                                    <div class="col-lg-3 col-md-4 col-sm-6 col-12 category-product-div">
                                        @include('front::partials.product-card', ['product' => $product, 'withTimer' => false, 'hoverBtns' => true])
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="my-paginate my-4">
                            {{ $products->appends(request()->all())->links('front::components.paginate') }}
                        </div>
                    @else
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="text-center py-5">
                                    <i class="bi bi-basket2 font-30 main-color-one-color"></i>
                                    <p class="mt-3 mb-0">چیزی برای نمایش وجود ندارد</p>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/vendor/nouislider.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/products/category.js') }}?v=5"></script>
@endpush

{{-- صفحه عمومی فروشگاه فروشنده سانیار (فاز G) — SEO ایندکس‌شده
    قرارداد MainController@showSellerStore :
    $seller, $seller_info, $products (paginator), $min_price, $max_price, $has_filter, $filterable, $products_id, $categorise --}}
@extends('front::layouts.master', ['title' => ($seller_info->business_name ?? 'فروشگاه') . ' | فروشندگان'])

@push('meta')
    <meta name="description" content="خرید محصولات {{ $seller_info->business_name ?? $seller->email }} با بهترین قیمت از {{ option('info_site_title', config('front.demo.name')) }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ route('front.showSellerStore', ['seller' => $seller]) }}">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => $seller_info->business_name ?? $seller->email,
            'url' => route('front.showSellerStore', ['seller' => $seller]),
            'description' => 'فروشگاه ' . ($seller_info->business_name ?? $seller->email) . ' در ' . option('info_site_title', config('front.demo.name')),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('plugin/nouislider/nouislider.min.css') }}">
@endpush

@push('styles')
    <style>
        .price-slider .noUi-connect { background: var(--main-color-one, #ef394e); }
        .price-slider .noUi-handle { border-color: var(--main-color-one, #ef394e); }
        .noUi-txt-dir-rtl.lower { right: 0; }
    </style>
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=1">
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
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">{{ $seller_info->business_name ?? 'فروشگاه' }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-20">
        <div class="container-fluid">

            {{-- ===== هدر فروشگاه ===== --}}
            <div class="seller-store-head mb-3">
                <div class="seller-store-banner"></div>
                <div class="px-3 px-lg-4 pb-3">
                    <div class="d-flex flex-wrap align-items-end gap-3" style="margin-top: -44px;">
                        @if($seller_info && $seller_info->logo)
                            <img src="{{ asset($seller_info->logo) }}" alt="{{ $seller_info->business_name }}"
                                 class="seller-store-avatar">
                        @else
                            <span class="seller-store-avatar-fallback">
                                {{ mb_substr($seller_info->business_name ?? 'ف', 0, 1, 'UTF-8') }}
                            </span>
                        @endif
                        <div class="mt-3">
                            <h1 class="h5 fw-bold mb-1">
                                {{ $seller_info->business_name ?? $seller->email }}
                                <i class="bi bi-patch-check-fill main-color-one-color font-16" aria-label="فروشنده تایید‌شده"></i>
                            </h1>
                            <p class="font-13 text-muted mb-0">
                                <i class="bi bi-geo-alt me-1"></i>
                                {{ optional($seller_info->city)->name ?? optional($seller_info->state)->name ?? '' }}
                                — عضو {{ option('info_site_title', config('front.demo.name')) }} از
                                {{ \Morilog\Jalali\Jalalian::fromDateTime($seller->created_at)->format('F Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap mt-4">
                        <div class="seller-store-stat flex-grow-1">
                            <span class="stat-num">{{ number_format($products->total()) }}</span>
                            <span class="font-13 text-muted">کالا</span>
                        </div>
                        <div class="seller-store-stat flex-grow-1">
                            <span class="stat-num">{{ number_format(\App\Models\Viewer::getSellerStoreVisitsCount($seller->slug)) }}</span>
                            <span class="font-13 text-muted">بازدید فروشگاه</span>
                        </div>
                        <div class="seller-store-stat flex-grow-1">
                            <span class="stat-num">{{ number_format(\App\Models\SellerVariant::where('seller_id', $seller->id)->count()) }}</span>
                            <span class="font-13 text-muted">تنوع کالا</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- دکمه شناور فیلتر — فقط موبایل --}}
            <div class="custom-filter d-lg-none d-block">
                <button class="btn btn-filter-float border-0 main-color-two-bg shadow-box px-3 rounded-3 position-fixed"
                        style="z-index: 999; bottom: 80px;" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#saniarFilters" aria-controls="saniarFilters">
                    <i class="bi bi-funnel font-20 fw-bold text-white"></i>
                    <span class="d-block font-14 text-white">فیلتر</span>
                </button>
            </div>

            <div class="row gy-3">
                {{-- ستون فیلتر --}}
                @include('front::sellers.partials.store-filters')

                {{-- گرید محصولات + مرتب‌سازی (سازوکار مشترک category.js) --}}
                <div id="category-products-div"
                     data-action="{{ route('front.showSellerStore', ['seller' => $seller]) }}"
                     data-base-url="{{ route('front.showSellerStore', ['seller' => $seller]) }}"
                     class="col-lg-9">
                    <div class="category-sort mb-3">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="d-flex align-items-center flex-wrap">
                                    <div>
                                        <h5 class="font-18 mb-0">مرتب سازی</h5>
                                        <p class="mb-0 text-muted font-16">براساس</p>
                                    </div>
                                    <div class="form-checks ms-md-5 ms-0 mt-md-0 mt-3" role="radiogroup" aria-label="نوع مرتب‌سازی">
                                        <div class="form-check form-check-inline {{ !request('sort_type') || request('sort_type') == 'latest' ? 'active' : '' }}">
                                            <input class="form-check-input js-sort-radio" type="radio" name="inlineRadioOptions"
                                                   id="sortDefault" value="latest" @checked(!request('sort_type') || request('sort_type') == 'latest')>
                                            <label class="form-check-label" for="sortDefault">جدیدترین</label>
                                        </div>
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'view' ? 'active' : '' }}">
                                            <input class="form-check-input js-sort-radio" type="radio" name="inlineRadioOptions"
                                                   id="sortView" value="view" @checked(request('sort_type') == 'view')>
                                            <label class="form-check-label" for="sortView">پربازدیدترین</label>
                                        </div>
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'sale' ? 'active' : '' }}">
                                            <input class="form-check-input js-sort-radio" type="radio" name="inlineRadioOptions"
                                                   id="sortSale" value="sale" @checked(request('sort_type') == 'sale')>
                                            <label class="form-check-label" for="sortSale">پرفروش‌ترین</label>
                                        </div>
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'cheapest' ? 'active' : '' }}">
                                            <input class="form-check-input js-sort-radio" type="radio" name="inlineRadioOptions"
                                                   id="sortCheapest" value="cheapest" @checked(request('sort_type') == 'cheapest')>
                                            <label class="form-check-label" for="sortCheapest">ارزان‌ترین</label>
                                        </div>
                                        <div class="form-check form-check-inline {{ request('sort_type') == 'expensivest' ? 'active' : '' }}">
                                            <input class="form-check-input js-sort-radio" type="radio" name="inlineRadioOptions"
                                                   id="sortExpensivest" value="expensivest" @checked(request('sort_type') == 'expensivest')>
                                            <label class="form-check-label" for="sortExpensivest">گران‌ترین</label>
                                        </div>
                                    </div>
                                    <div class="ms-auto">
                                        <h2 class="font-16 mb-0">کالاهای {{ $seller_info->business_name ?? 'این فروشگاه' }}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($products->count())
                        <div class="category-items">
                            <div class="row g-3">
                                @foreach($products as $product)
                                    <div class="col-lg-4 col-md-6 col-6">
                                        @include('front::products.partials.product-card', ['product' => $product])
                                    </div>
                                @endforeach
                            </div>

                            {{ $products->appends(request()->all())->links('front::components.paginate') }}
                        </div>
                    @else
                        @include('front::partials.empty', ['message' => 'محصولی در این فروشگاه یافت نشد'])
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('plugin/nouislider/nouislider.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/products/category.js') }}"></script>
@endpush

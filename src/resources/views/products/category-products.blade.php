@extends('front::layouts.master', ['title' => $category->title])

@push('meta')
    <meta property="og:title" content="{{ $category->meta_title ?: $category->title }}">
    <meta property="og:url" content="{{ route('front.products.category-products', ['category' => $category]) }}">
    <meta property="og:image" content="{{ $category->image ? asset($category->image) : option('info_logo', theme_asset('image/logo.png')) }}">
    <meta name="description" content="{{ $category->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($category->description ?: 'خرید محصولات ' . $category->title . ' با بهترین قیمت از ' . option('info_site_title', config('front.demo.name'))), 160) }}">
    <meta name="keywords" content="{{ $category->getTags }}">
    <link rel="canonical" href="{{ route('front.products.category-products', ['category' => $category]) }}">
    {{-- JSON-LD دسته‌بندی محصولات --}}
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@@type' => 'BreadcrumbList',
            'itemListElement' => array_merge(
                [['@@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => route('front.index')]],
                [['@@type' => 'ListItem', 'position' => 2, 'name' => 'فروشگاه', 'item' => route('front.products.index')]],
                $category->parents()->map(fn ($p, $i) => ['@@type' => 'ListItem', 'position' => $i + 3, 'name' => $p->title, 'item' => route('front.products.category', ['category' => $p])])->values()->all(),
                [['@@type' => 'ListItem', 'position' => $category->parents()->count() + 3, 'name' => $category->title, 'item' => route('front.products.category-products', ['category' => $category])]]
            ),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('plugin/nouislider/nouislider.min.css') }}">
@endpush

@push('styles')
    <style>
        /* اسلایدر قیمت سانیار — رنگ‌بندی تم */
        .price-slider .noUi-connect { background: var(--main-color-one, #ef394e); }
        .price-slider .noUi-handle { border-color: var(--main-color-one, #ef394e); }
        .noUi-txt-dir-rtl.lower { right: 0; }
    </style>
@endpush

@section('content')
    {{-- بردکرامب سانیار --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('front.products.index') }}" class="font-14 text-muted">فروشگاه</a></li>
                            @foreach ($category->parents() as $parent)
                                <li class="breadcrumb-item">
                                    <a href="{{ route('front.products.category', ['category' => $parent]) }}" class="font-14 text-muted">{{ $parent->title }}</a>
                                </li>
                            @endforeach
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">{{ $category->title }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- نوار برندهای این دسته (داینامیک + کش) --}}
    @php $category_brands = saniar_category_brands($category); @endphp
    @if ($category_brands->count())
        <div class="category-brand">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid">
                        <div class="swiper free-mode">
                            <div class="swiper-wrapper">
                                @foreach ($category_brands as $cbrand)
                                    <div class="swiper-slide">
                                        <div class="item text-center">
                                            <a href="{{ route('front.brands.show', ['brand' => $cbrand]) }}" title="{{ $cbrand->name }}">
                                                @if ($cbrand->image)
                                                    <img src="{{ asset($cbrand->image) }}" alt="{{ $cbrand->name }}" loading="lazy" class="img-fluid" height="48">
                                                @else
                                                    <span class="d-inline-block px-3 py-2 font-14 fw-bold text-muted">{{ $cbrand->name }}</span>
                                                @endif
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- محتوای اصلی --}}
    <div class="content py-20">
        <div class="container-fluid">

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
                {{-- ستون فیلتر (در موبایل offcanvas) --}}
                @include('front::products.partials.category-filters')

                <div id="category-products-div"
                     data-action="{{ route('front.products.category-products', ['category' => $category]) }}"
                     data-base-url="{{ route('front.products.category-products', ['category' => $category]) }}"
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
                                        <h1 class="font-16 mb-0">{{ $category->title }}</h1>
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
                        @include('front::partials.empty', ['message' => 'محصولی مطابق فیلترهای شما یافت نشد'])
                    @endif
                </div>
            </div>

            {{-- توضیحات دسته در انتهای صفحه (SEO) --}}
            @if ($category->description)
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="content-box">
                            <div class="container-fluid p-4">
                                <h2 class="font-16 mb-3">درباره {{ $category->title }}</h2>
                                <div class="text-muted font-14 line-height-2">{!! $category->description !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('plugin/nouislider/nouislider.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/products/category.js') }}"></script>
@endpush

@extends('front::layouts.master', ['title' => $category->title])

@push('meta')
    <meta property="og:title" content="{{ $category->meta_title ?: $category->title }}" />
    <meta property="og:url" content="{{ route('front.products.category-products', ['category' => $category]) }}" />
    <meta name="description" content="{{ $category->meta_description ?: $category->description }}">
    <meta name="keywords" content="{{ $category->getTags }}">
    <link rel="canonical" href="{{ route('front.products.category-products', ['category' => $category]) }}" />
@endpush

@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('plugin/nouislider/nouislider.min.css') }}">
@endpush

@php
    $has_filter = $category->getFilter();
@endphp

@section('content')
    <!-- Start main-content -->
    <main class="main-content">
        <div class="container-fluid">

            <!-- start breadcroumb -->
            <div class="bread-crumb pt-3">
                <div class="container-fluid">
                    <div class="content-box">
                        <div class="container-fluid">
                            <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}" class="font-14 text-muted">خانه</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('front.products.index') }}" class="font-14 text-muted">محصولات</a></li>
                                    @foreach ($category->parents() as $parent)
                                        <li class="breadcrumb-item"><a href="{{ route('front.products.category', ['category' => $parent]) }}" class="font-14 text-muted">{{ $parent->title }}</a></li>
                                    @endforeach
                                    <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">{{ $category->title }}</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end breadcroumb -->

            <!-- start content -->
            <div class="content py-20">
                <div class="container-fluid">
                    <div class="content-box">
                        <div class="container-fluid">

                            <div class="row gy-3">

                                @if ($has_filter)
                                    @include('front::products.partials.category-filters')
                                @endif

                                <div id="category-products-div"
                                     data-action="{{ route('front.products.category-products', ['category' => $category]) }}"
                                     class="{{ $has_filter ? 'col-lg-9' : 'col-lg-12' }} col-md-12 col-sm-12">

                                    {{-- نوار مرتب‌سازی سانیار --}}
                                    <div class="category-sort mb-3">
                                        <div class="content-box">
                                            <div class="container-fluid">
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <div>
                                                        <h5 class="font-18">مرتب سازی</h5>
                                                        <p class="mb-0 text-muted font-16">براساس</p>
                                                    </div>
                                                    <div class="form-checks ms-md-5 ms-0 mt-md-0 mt-3">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="sort_type" id="sort-latest" value="latest" {{ request('sort_type') == 'latest' || !request('sort_type') ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="sort-latest">جدید ترین</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="sort_type" id="sort-view" value="view" {{ request('sort_type') == 'view' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="sort-view">پربازدید ترین</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="sort_type" id="sort-sale" value="sale" {{ request('sort_type') == 'sale' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="sort-sale">پرفروش ترین</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="sort_type" id="sort-cheapest" value="cheapest" {{ request('sort_type') == 'cheapest' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="sort-cheapest">ارزان ترین</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="sort_type" id="sort-expensivest" value="expensivest" {{ request('sort_type') == 'expensivest' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="sort-expensivest">گران ترین</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($products->count())
                                        <div class="category-items">
                                            <div class="row g-3">
                                                @foreach($products as $product)
                                                    <div class="col-lg-4 col-md-6 col-sm-6 col-12 category-product-div">
                                                        @include('front::products.partials.product-card', ['product' => $product, 'withTimer' => false, 'hoverBtns' => true])
                                                    </div>
                                                @endforeach
                                            </div>

                                            {{ $products->appends(request()->all())->links('front::components.paginate') }}
                                        </div>
                                    @else
                                        @include('front::partials.empty')
                                    @endif
                                </div>
                            </div>

                            @if ($category->description)
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="category-description shadow-box rounded-3 p-4 category-background"
                                             style="background-image: url({{ asset($category->background_image) }});">
                                            {!! $category->description !!}
                                        </div>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
            <!-- end content -->

        </div>
    </main>
    <!-- End main-content -->
@endsection

@push('scripts')
    <script src="{{ theme_asset('plugin/nouislider/nouislider.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/products/category.js') }}?v=2"></script>
@endpush

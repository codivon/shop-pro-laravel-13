@extends('front::layouts.master', ['title' => 'جستجو برای ' . request('q')])

@section('content')
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
                                    <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">جستجو برای «{{ $q }}»</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end breadcroumb -->

            <div class="content py-20">
                <div class="container-fluid">
                    <div class="content-box">
                        <div class="container-fluid">

                            {{-- عنوان و آمار نتایج --}}
                            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                                <div class="title d-flex align-items-center">
                                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                                    <h5 class="font-16 ms-3">نتایج <span class="main-color-one-color d-inline-block">جستجو</span></h5>
                                </div>
                                <div class="search-stats d-flex align-items-center gap-2 flex-wrap">
                                    @if($stats['products_count'])
                                        <span class="badge rounded-pill main-color-one-bg font-12">{{ $stats['products_count'] }} محصول</span>
                                    @endif
                                    @if($stats['categories_count'])
                                        <span class="badge rounded-pill main-color-two-bg font-12">{{ $stats['categories_count'] }} دسته</span>
                                    @endif
                                    @if($stats['brands_count'])
                                        <span class="badge rounded-pill bg-success font-12">{{ $stats['brands_count'] }} برند</span>
                                    @endif
                                    @if($stats['posts_count'])
                                        <span class="badge rounded-pill bg-warning text-dark font-12">{{ $stats['posts_count'] }} مقاله</span>
                                    @endif
                                </div>
                            </div>

                            {{-- ========== تب‌های جستجو (بوت‌استرپ ۵) ========== --}}
                            <ul class="nav nav-pills search-tabs mb-4" id="searchTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="true">
                                        <i class="bi bi-basket me-1"></i> محصولات
                                        @if($stats['products_count'] > 0)
                                            <span class="badge rounded-pill bg-light text-dark">{{ $stats['products_count'] }}</span>
                                        @endif
                                    </button>
                                </li>
                                @if($categories->count() > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab" aria-controls="categories" aria-selected="false">
                                            <i class="bi bi-tag me-1"></i> دسته‌بندی‌ها
                                            <span class="badge rounded-pill bg-light text-dark">{{ $categories->count() }}</span>
                                        </button>
                                    </li>
                                @endif
                                @if($brands->count() > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="brands-tab" data-bs-toggle="tab" data-bs-target="#brands" type="button" role="tab" aria-controls="brands" aria-selected="false">
                                            <i class="bi bi-award me-1"></i> برندها
                                            <span class="badge rounded-pill bg-light text-dark">{{ $brands->count() }}</span>
                                        </button>
                                    </li>
                                @endif
                                @if($posts->count() > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab" aria-controls="posts" aria-selected="false">
                                            <i class="bi bi-newspaper me-1"></i> مقالات
                                            <span class="badge rounded-pill bg-light text-dark">{{ $posts->count() }}</span>
                                        </button>
                                    </li>
                                @endif
                            </ul>

                            {{-- ========== محتوای تب‌ها ========== --}}
                            <div class="tab-content" id="searchTabContent">

                                {{-- تب محصولات --}}
                                <div class="tab-pane fade show active" id="products" role="tabpanel" aria-labelledby="products-tab">
                                    @if($products->count() > 0)
                                        <div class="row g-3">
                                            @foreach($products as $product)
                                                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                                                    @include('front::products.partials.product-card', ['product' => $product, 'withTimer' => false, 'hoverBtns' => true])
                                                </div>
                                            @endforeach
                                        </div>
                                        {{ $products->appends(request()->all())->links('front::components.paginate') }}
                                    @else
                                        @include('front::partials.empty', ['message' => 'محصولی برای "'.$q.'" یافت نشد'])
                                    @endif
                                </div>

                                {{-- تب دسته‌بندی‌ها --}}
                                @if($categories->count() > 0)
                                    <div class="tab-pane fade" id="categories" role="tabpanel" aria-labelledby="categories-tab">
                                        <div class="row g-3">
                                            @foreach($categories as $category)
                                                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                                                    <div class="subcategory-card shadow-box rounded-3 p-3 text-center h-100">
                                                        <a href="{{ route('front.products.category-products', $category->slug) }}">
                                                            <div class="category-image mb-3">
                                                                <img src="{{ $category->image ? asset($category->image) : theme_asset('images/category-default.png') }}" alt="{{ $category->title }}" class="img-fluid">
                                                            </div>
                                                            <h5 class="font-16 mb-0">{{ $category->title }}</h5>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- تب برندها --}}
                                @if($brands->count() > 0)
                                    <div class="tab-pane fade" id="brands" role="tabpanel" aria-labelledby="brands-tab">
                                        <div class="row g-3">
                                            @foreach($brands as $brand)
                                                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                                                    <div class="brand-card shadow-box rounded-3 p-3 text-center h-100">
                                                        <a href="{{ route('front.brands.show', $brand->slug ?? $brand->id) }}">
                                                            <div class="brand-logo mb-2">
                                                                <img src="{{ $brand->image ? asset($brand->image) : theme_asset('images/brand-default.png') }}" alt="{{ $brand->name }}" class="img-fluid">
                                                            </div>
                                                            <h6 class="font-14 mb-0">{{ $brand->name }}</h6>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- تب مقالات — articles-list خودش گرید سانیار را رندر می‌کند (بدون حلقه تکراری) --}}
                                @if($posts->count() > 0)
                                    <div class="tab-pane fade" id="posts" role="tabpanel" aria-labelledby="posts-tab">
                                        @include('front::articles.partials.articles-list', ['posts' => $posts])
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/products/search.js') }}"></script>
@endpush

@extends('front::layouts.master', ['title' => 'جستجو برای ' . request('q')])

@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('css/search.css') }}">
@endpush

@push('styles')
    <style>
        /* استایل تب‌های جستجو */

    </style>
@endpush

@section('content')
    <main class="main-content dt-sl mb-3">
        <div class="container main-container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 d-flex flex-column search-card-res ">

                    {{-- عنوان و مسیر --}}
                    <div class="title-breadcrumb-special dt-sl mb-3 d-flex flex-column">
                        <div class="breadcrumb dt-sl">
                            <nav>
                                <a href="/">خانه</a>
                                <a href="{{ route('front.products.index') }}">محصولات</a>
                                <span>جستجو برای "{{ $q }}"</span>
                            </nav>
                        </div>
                        <div class="search-stats">
                            @if($stats['products_count'])<span class="badge badge-primary">{{ $stats['products_count'] }} محصول</span> @endif
                            @if($stats['categories_count'])<span class="badge badge-success">{{ $stats['categories_count'] }} دسته</span> @endif
                            @if($stats['brands_count'])<span class="badge badge-info">{{ $stats['brands_count'] }} برند</span> @endif
                            @if($stats['posts_count']) <span class="badge badge-warning">{{ $stats['posts_count'] }} مقاله</span> @endif
                        </div>
                    </div>

                    {{-- ========== تب‌های جستجو ========== --}}
                    <ul class="nav nav-tabs search-tabs mb-4" id="searchTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="products-tab" data-toggle="tab" href="#products" role="tab">
                                <i class="mdi mdi-cart"></i> محصولات
                                @if($stats['products_count'] > 0)
                                    <span class="badge badge-light">{{ $stats['products_count'] }}</span>
                                @endif
                            </a>
                        </li>
                        @if($categories->count() > 0)
                            <li class="nav-item">
                                <a class="nav-link" id="categories-tab" data-toggle="tab" href="#categories" role="tab">
                                    <i class="mdi mdi-tag"></i> دسته‌بندی‌ها
                                    <span class="badge badge-light">{{ $categories->count() }}</span>
                                </a>
                            </li>
                        @endif
                        @if($brands->count() > 0)
                            <li class="nav-item">
                                <a class="nav-link" id="brands-tab" data-toggle="tab" href="#brands" role="tab">
                                    <i class="mdi mdi-trademark"></i> برندها
                                    <span class="badge badge-light">{{ $brands->count() }}</span>
                                </a>
                            </li>
                        @endif
                        @if($posts->count() > 0)
                            <li class="nav-item">
                                <a class="nav-link" id="posts-tab" data-toggle="tab" href="#posts" role="tab">
                                    <i class="mdi mdi-newspaper"></i> مقالات
                                    <span class="badge badge-light">{{ $posts->count() }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>

                    {{-- ========== محتوای تب‌ها ========== --}}
                    <div class="tab-content">

                        {{-- تب محصولات --}}
                        <div class="tab-pane fade show active" id="products" role="tabpanel">
                            @if($products->count() > 0)
                                <div class="row">
                                    @foreach($products as $product)
                                        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                                            @include('front::products.partials.product-card', ['product' => $product])
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
                            <div class="tab-pane fade" id="categories" role="tabpanel">
                                <div class="row">
                                    @foreach($categories as $category)
                                        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                                            <div class="category-card text-center">
                                                <a href="{{ route('front.products.category-products', $category->slug) }}">
                                                    <div class="category-image">
                                                        <img src="{{ $category->image ? asset($category->image) : theme_asset('images/category-default.png') }}" alt="{{ $category->title }}">
                                                    </div>
                                                    <h4 class="category-title">{{ $category->title }}</h4>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- تب برندها --}}
                        @if($brands->count() > 0)
                            <div class="tab-pane fade" id="brands" role="tabpanel">
                                <div class="row">
                                    @foreach($brands as $brand)
                                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                                            <div class="brand-card text-center">
                                                <a href="{{ route('front.brands.show', $brand->slug ?? $brand->id) }}">
                                                    <div class="brand-logo">
                                                        <img src="{{ $brand->image ? asset($brand->image) : theme_asset('images/brand-default.png') }}" alt="{{ $brand->name }}">
                                                    </div>
                                                    <h5 class="brand-name">{{ $brand->name }}</h5>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- تب مقالات --}}
                        @if($posts->count() > 0)
                            <div class="tab-pane fade" id="posts" role="tabpanel">
                                <div class="row">
                                    @foreach($posts as $post)
                                        @include('front::articles.partials.articles-list', ['posts' => $posts])
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // حفظ تب فعال بعد از رفرش
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                localStorage.setItem('activeSearchTab', $(e.target).attr('href'));
            });

            var activeTab = localStorage.getItem('activeSearchTab');
            if (activeTab) {
                $('#searchTab a[href="' + activeTab + '"]').tab('show');
            }
        });
    </script>
@endpush

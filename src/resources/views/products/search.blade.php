@extends('front::layouts.master', ['title' => 'جستجو برای ' . $q])

@push('meta')
    <meta name="robots" content="{{ $products->currentPage() > 1 ? 'noindex, follow' : 'index, follow' }}">
    <link rel="canonical" href="{{ route('front.products.index', ['q' => $q]) }}">
    <meta name="description" content="نتایج جستجو برای {{ $q }} در {{ option('info_site_title', config('front.demo.name')) }}">
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
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">
                                جستجو برای «{{ $q }}»
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-20">
        <div class="container-fluid">

            {{-- هدر نتایج با آمار داینامیک --}}
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <span class="main-color-one-bg text-white rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-search font-20"></i>
                    </span>
                    <h1 class="font-16 ms-3 mb-0">نتایج جستجوی <span class="main-color-one-color d-inline-block ms-1">{{ $q }}</span></h1>
                </div>
                <div class="search-stats-saniar mt-2 mt-lg-0">
                    @if($stats['products_count'])<span class="badge bg-success-subtle text-success border border-success-subtle">{{ $stats['products_count'] }} محصول</span>@endif
                    @if($stats['categories_count'])<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $stats['categories_count'] }} دسته</span>@endif
                    @if($stats['brands_count'])<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">{{ $stats['brands_count'] }} برند</span>@endif
                    @if($stats['posts_count'])<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">{{ $stats['posts_count'] }} مقاله</span>@endif
                </div>
            </div>

            {{-- تب‌های نتایج (فقط تب‌های دارای نتیجه) --}}
            <ul class="nav nav-tabs saniar-tabs mb-4" id="saniarSearchTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-products" data-bs-toggle="tab" data-bs-target="#pane-products" type="button" role="tab" aria-selected="true">
                        <i class="bi bi-box-seam me-1"></i>محصولات
                    </button>
                </li>
                @if($categories->count() > 0)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-categories" data-bs-toggle="tab" data-bs-target="#pane-categories" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-diagram-3 me-1"></i>دسته‌بندی‌ها
                        </button>
                    </li>
                @endif
                @if($brands->count() > 0)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-brands" data-bs-toggle="tab" data-bs-target="#pane-brands" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-award me-1"></i>برندها
                        </button>
                    </li>
                @endif
                @if($posts->count() > 0)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-posts" data-bs-toggle="tab" data-bs-target="#pane-posts" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-journal-text me-1"></i>مقالات
                        </button>
                    </li>
                @endif
            </ul>

            <div class="tab-content">

                {{-- تب محصولات --}}
                <div class="tab-pane fade show active" id="pane-products" role="tabpanel" aria-labelledby="tab-products">
                    @if($products->count() > 0)
                        <div class="row g-3">
                            @foreach($products as $product)
                                <div class="col-lg-3 col-md-4 col-6">
                                    @include('front::products.partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                        {{ $products->appends(request()->all())->links('front::components.paginate') }}
                    @else
                        @include('front::partials.empty', ['message' => 'محصولی برای «' . $q . '» یافت نشد'])
                    @endif
                </div>

                {{-- تب دسته‌بندی‌ها --}}
                @if($categories->count() > 0)
                    <div class="tab-pane fade" id="pane-categories" role="tabpanel" aria-labelledby="tab-categories">
                        <div class="row g-3">
                            @foreach($categories as $category)
                                <div class="col-lg-3 col-md-4 col-6">
                                    <a href="{{ route('front.products.category-products', ['category' => $category->slug]) }}" class="saniar-result-card">
                                        <div class="src-thumb">
                                            <img src="{{ $category->image ? asset($category->image) : asset('no-image-product.svg') }}" loading="lazy" alt="{{ $category->title }}">
                                        </div>
                                        <div class="src-title">{{ $category->title }}</div>
                                        <div class="src-sub">دسته‌بندی محصول</div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- تب برندها --}}
                @if($brands->count() > 0)
                    <div class="tab-pane fade" id="pane-brands" role="tabpanel" aria-labelledby="tab-brands">
                        <div class="row g-3">
                            @foreach($brands as $brand)
                                <div class="col-lg-3 col-md-4 col-6">
                                    <a href="{{ route('front.brands.show', ['brand' => $brand->slug]) }}" class="saniar-result-card">
                                        <div class="src-thumb">
                                            <img src="{{ $brand->image ? asset($brand->image) : asset('no-image-product.svg') }}" loading="lazy" alt="{{ $brand->name }}">
                                        </div>
                                        <div class="src-title">{{ $brand->name }}</div>
                                        <div class="src-sub">مشاهده محصولات برند</div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- تب مقالات --}}
                @if($posts->count() > 0)
                    <div class="tab-pane fade" id="pane-posts" role="tabpanel" aria-labelledby="tab-posts">
                        <div class="row g-3">
                            @foreach($posts as $post)
                                <div class="col-lg-3 col-md-4 col-6">
                                    <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}" class="saniar-result-card">
                                        <div class="src-thumb">
                                            <img src="{{ $post->image ? asset($post->image) : asset('no-image-product.svg') }}" loading="lazy" alt="{{ $post->title }}">
                                        </div>
                                        <div class="src-title">{{ \Illuminate\Support\Str::limit($post->title, 40) }}</div>
                                        <div class="src-sub">{{ optional($post->publish_date)->translatedFormat('d F Y') }}</div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

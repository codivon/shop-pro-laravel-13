@extends('front::layouts.master', ['title' => $category->title])

@push('meta')
    <meta property="og:title" content="{{ $category->meta_title ?: $category->title }}" />
    <meta property="og:url" content="{{ route('front.products.category', ['category' => $category]) }}" />
    <meta name="description" content="{{ $category->meta_description ?: $category->description }}">
    <meta name="keywords" content="{{ $category->getTags }}">
    <link rel="canonical" href="{{ route('front.products.category', ['category' => $category]) }}" />
@endpush

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

                            @if ($category->description)
                                <div class="category-description shadow-box rounded-3 mb-4 p-4 category-background"
                                     style="background-image: url({{ asset($category->background_image) }});">
                                    {!! $category->description !!}
                                </div>
                            @endif

                            @if($category->getCategoriesCount())
                                <div class="category-items">
                                    <div class="row g-3">

                                        <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                                            <div class="subcategory-card shadow-box rounded-3 p-3 text-center h-100">
                                                <a href="{{ route('front.products.category-products', ['category' => $category]) }}">
                                                    <div class="subcategory-image mb-3">
                                                        <img src="{{ $category->image ?: asset('/no-image-product.svg') }}"
                                                             loading="lazy" alt="{{ $category->title }}" class="img-fluid">
                                                    </div>
                                                    <h5 class="font-16 mb-2">{{ $category->title }}</h5>
                                                </a>
                                                <a href="{{ route('front.products.category-products', ['category' => $category]) }}"
                                                   class="btn border-0 main-color-one-bg rounded-pill font-14">
                                                    <span class="text-white">همه محصولات</span>
                                                    <i class="bi bi-chevron-double-left text-white"></i>
                                                </a>
                                            </div>
                                        </div>

                                        @foreach(front_category_children($category) as $cat)
                                            <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                                                <div class="subcategory-card shadow-box rounded-3 p-3 text-center h-100">
                                                    <a href="{{ route('front.products.category', ['category' => $cat]) }}">
                                                        <div class="subcategory-image mb-3">
                                                            <img src="{{ $cat->image ?: asset('/no-image-product.svg') }}"
                                                                 loading="lazy" alt="{{ $cat->title }}" class="img-fluid">
                                                        </div>
                                                        <h5 class="font-16 mb-2">{{ $cat->title }}</h5>
                                                    </a>
                                                    <a href="{{ route('front.products.category', ['category' => $cat]) }}"
                                                       class="btn border-0 main-color-one-bg rounded-pill font-14">
                                                        <span class="text-white">مشاهده محصولات</span>
                                                        <i class="bi bi-chevron-double-left text-white"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach

                                    </div>
                                </div>
                            @else
                                @include('front::partials.empty')
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

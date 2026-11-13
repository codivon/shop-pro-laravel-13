@extends('front::layouts.master', ['title' => 'محصولات'])

@push('meta')
    <meta name="description" content="{{ option('info_short_description') }}">
    <meta name="keywords" content="{{ option('info_tags') }}">
    <link rel="canonical" href="{{ route('front.products.index') }}" />
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
                                    <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">محصولات</li>
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

                            @if($categories->count())
                                <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="title d-flex align-items-center">
                                        <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                                        <h5 class="font-16 ms-3">دسته <span class="main-color-one-color d-inline-block">بندی ها</span></h5>
                                    </div>
                                </div>

                                <div class="category-items">
                                    <div class="row g-3">

                                        @foreach($categories as $category)
                                            <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                                                <div class="subcategory-card shadow-box rounded-3 p-3 text-center h-100">
                                                    <a href="{{ route('front.products.category', ['category' => $category]) }}">
                                                        <div class="subcategory-image mb-3">
                                                            <img src="{{ $category->image ?: asset('/no-image-product.svg') }}"
                                                                 loading="lazy" alt="{{ $category->title }}" class="img-fluid">
                                                        </div>
                                                        <h5 class="font-16 mb-2">{{ $category->title }}</h5>
                                                    </a>
                                                    <a href="{{ route('front.products.category', ['category' => $category]) }}"
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

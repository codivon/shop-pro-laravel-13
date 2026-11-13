@extends('front::layouts.master', ['title' => 'محصولات'])

@push('meta')
    <meta name="description" content="{{ option('info_short_description') }}">
    <meta name="keywords" content="{{ option('info_tags') }}">
    <link rel="canonical" href="{{ route('front.products.index') }}" />
@endpush
@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('css/search.css') }}">
@endpush


@section('content')
    <!-- Start main-content -->
    <main class="main-content dt-sl mt-6 mb-3">
        <div class="container main-container">

            <div class="row">

                <div class="col-lg-12 col-md-12 col-sm-12 search-card-res">
                    <!-- Start Content -->
                    <div class="title-breadcrumb-special dt-sl mb-3">
                        <div class="breadcrumb dt-sl">
                            <nav>
                                <a href="/">خانه</a>
                                <span>محصولات</span>
                            </nav>
                        </div>
                    </div>
                    @if($categories->count())
                        <div class="dt-sl dt-sn px-0 search-amazing-tab">
                            <div class="row mb-3 mx-0 px-res-0">

                                @foreach($categories as $category)
                                    <div class="col-lg-3 col-md-4 col-sm-6 col-12 px-10 mb-1 px-res-0">
                                        <div class="product-card mb-2 mx-res-0 category-index">
                                            <div class="product-card-body">
                                                <h5 class="product-title">
                                                    <a href="{{ route('front.products.category', ['category' => $category]) }}">{{ $category->title }}</a>
                                                </h5>
                                            </div>
                                            <a class="product-thumb" href="{{ route('front.products.category', ['category' => $category]) }}">
                                                <img data-src="{{ $category->image }}" alt="{{ $category->title }}">
                                            </a>

                                            <a href="{{ route('front.products.category', ['category' => $category]) }}" class="more-cat">مشاهده محصولات</a>
                                        </div>
                                    </div>
                                @endforeach

                            </div>

                        </div>
                    @else
                        @include('front::partials.empty')
                    @endif
                </div>
                <!-- End Content -->
            </div>

        </div>
    </main>
    <!-- End main-content -->

@endsection

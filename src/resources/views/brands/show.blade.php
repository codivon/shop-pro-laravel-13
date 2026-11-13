@php
    $brandName = $brand->name_fa ? $brand->name_fa : $brand->name;
@endphp
@extends('front::layouts.master', ['title' => $brandName])

@push('meta')
    <meta property="og:title" content="{{ $brand->name }}" />
    <link rel="canonical" href="{{ route('front.brands.show', ['brand' => $brand]) }}" />
@endpush

@section('content')
    {{-- صفحه برند — سانیار bread-crumb + گرید product-box --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a>
                            </li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">برند: {{ $brandName }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid py-3">

            <div id="category-products-div" data-action="">
                <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                    <div class="title d-flex align-items-center">
                        <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                        <h1 class="font-16 ms-3 mb-0">همه محصولات برند <span class="main-color-one-color d-inline-block">{{ $brandName }}</span></h1>
                    </div>
                </div>

                @if($products->count())
                    <div class="category-items">
                        <div class="row gy-3">
                            @foreach($products as $product)
                                <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                                    @include('front::partials.product-card', ['product' => $product, 'withTimer' => false, 'hoverBtns' => true])
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="my-paginate my-4">
                        {{ $products->appends(request()->all())->links('front::components.paginate') }}
                    </div>
                @else
                    @include('front::partials.empty')
                @endif
            </div>

        </div>
    </div>
@endsection

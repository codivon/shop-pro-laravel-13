@php
    $brandName = $brand->name_fa ? $brand->name_fa : $brand->name;
@endphp
@extends('front::layouts.master', ['title' => 'برند ' . $brandName])

@push('meta')
    <meta property="og:title" content="برند {{ $brandName }}">
    <meta property="og:url" content="{{ route('front.brands.show', ['brand' => $brand]) }}">
    @if($brand->image)
        <meta property="og:image" content="{{ asset($brand->image) }}">
    @endif
    <meta name="description" content="همه محصولات برند {{ $brandName }} با بهترین قیمت از {{ option('info_site_title', config('front.demo.name')) }}">
    <link rel="canonical" href="{{ route('front.brands.show', ['brand' => $brand]) }}">
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
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">برند {{ $brandName }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-20">
        <div class="container-fluid">
            {{-- هدر برند با لوگو (داینامیک) --}}
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    @if($brand->image)
                        <img src="{{ asset($brand->image) }}" alt="{{ $brandName }}" loading="lazy" class="img-fluid rounded-3" style="max-height:56px;max-width:140px;">
                    @else
                        <span class="main-color-one-bg text-white rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <i class="bi bi-award font-20"></i>
                        </span>
                    @endif
                    <h1 class="font-16 ms-3 mb-0">محصولات برند <span class="main-color-one-color d-inline-block ms-1">{{ $brandName }}</span></h1>
                </div>
            </div>

            @if($products->count())
                <div class="row g-3">
                    @foreach($products as $product)
                        <div class="col-lg-3 col-md-4 col-6">
                            @include('front::products.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
                {{ $products->appends(request()->all())->links('front::components.paginate') }}
            @else
                @include('front::partials.empty', ['message' => 'محصولی برای برند «' . $brandName . '» یافت نشد'])
            @endif
        </div>
    </div>
@endsection

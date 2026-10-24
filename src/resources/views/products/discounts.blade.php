@extends('front::layouts.master', ['title' => 'محصولات تخفیف‌دار'])

@push('meta')
    <meta name="robots" content="{{ $products->currentPage() > 1 ? 'noindex, follow' : 'index, follow' }}">
    <link rel="canonical" href="{{ route('front.products.discount') }}">
    <meta name="description" content="همه محصولات تخفیف‌دار {{ option('info_site_title', config('front.demo.name')) }} — خرید با کمترین قیمت">
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
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">محصولات تخفیف‌دار</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <span class="main-color-one-bg text-white rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-percent font-20"></i>
                    </span>
                    <h1 class="font-16 ms-3 mb-0">همه <span class="main-color-one-color d-inline-block ms-1">تخفیف‌دارها</span></h1>
                </div>
                <div class="link">
                    <a href="{{ route('front.products.specials') }}" class="border-animate fromCenter pb-1 fw-bold">
                        پیشنهادهای ویژه
                        <i class="bi bi-chevron-double-left main-color-one-color"></i>
                    </a>
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
                @include('front::partials.empty', ['message' => 'در حال حاضر محصول تخفیف‌داری وجود ندارد'])
            @endif
        </div>
    </div>
@endsection

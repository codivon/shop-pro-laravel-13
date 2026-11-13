@extends('front::layouts.master', ['title' => 'محصولات ویژه'])

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
                                    <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">محصولات ویژه</li>
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

                            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                                <div class="title d-flex align-items-center">
                                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                                    <h5 class="font-16 ms-3">همه <span class="main-color-one-color d-inline-block">شگفت‌انگیزها</span></h5>
                                </div>
                            </div>

                            @if($products->count())
                                <div class="category-items">
                                    <div class="row g-3">
                                        @foreach($products as $product)
                                            <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                                                @include('front::products.partials.product-card', ['product' => $product, 'withTimer' => true, 'hoverBtns' => true])
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
                </div>
            </div>

        </div>
    </main>
@endsection

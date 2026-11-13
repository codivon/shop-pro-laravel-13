@extends('front::layouts.master', ['title' => 'محصولات ویژه'])
@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('css/search.css') }}">
@endpush
@section('content')
    <main class="main-content dt-sl mb-3 mt-6" style="transform: none;">
        <div class="header-cover">
            <div class="promotion-header-img"></div>
        </div>
        <div class="container main-container" style="transform: none;">


            <div class="row" style="transform: none;">

                <div id="category-products-div" data-action="" class=" col-md-12 col-sm-12">
                    <div class="col-12">
                        <!-- Start Content -->
                        <div class="title-breadcrumb-special dt-sl mb-3">
                            <div class="breadcrumb dt-sl">
                                <nav>
                                    <a class="color-white" href="/">خانه</a>
                                    <a class="color-white" href="{{ route('front.products.index') }}">محصولات</a>
                                    <span class="color-white">محصولات ویژه</span>
                                </nav>
                            </div>
                        </div>
                    </div>

                    @if($products->count())
                        <div class="dt-sl dt-sn px-0 search-amazing-tab">
                         {{--   <div class="col-12">
                                <h1 class="pr-3 font-size-20 pt-2">همه شگفت‌انگیز‌ها</h1>
                            </div>--}}
                            <div class="row mb-3 mx-0 px-res-0">
                                @foreach($products as $product)

                                    <div class="col-lg-3 col-md-4 col-sm-6 col-12 px-10 mb-1 px-res-0">
                                        @include('front::products.partials.product-card', ['product' => $product])
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
    </main>


@endsection

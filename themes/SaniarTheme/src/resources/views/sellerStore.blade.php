@extends('front::layouts.master', ['title' => $seller_info->business_name])

@push('meta')
    <meta property="og:title" content="{{ $seller_info->business_name ?: $seller_info->fullname }}" />
    <meta property="og:url" content="{{ route('front.showSellerStore', ['seller' => $seller]) }}" />
    <meta name="description" content="{{ $seller_info->bio ?: $seller_info->business_name}}">
    <meta name="keywords" content="{{ $seller_info->business_name }}">
    <link rel="canonical" href="{{ route('front.showSellerStore', ['seller' => $seller]) }}" />
@endpush

@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('css/vendor/nouislider.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/search.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/jquery.horizontalmenu.css') }}">
@endpush

@push('styles')

     <style>
         .widget-seller {
             display: flex;
             flex-direction: column;
             align-items: center
         }

         .widget-seller .seller-avatar {
             display: flex;
             align-items: center;
             justify-content: center;
             width: 80px;
             height: 80px;
             border: 1px solid #ececec;
             border-radius: 20px;
             margin: 15px auto
         }

         .widget-seller .seller-avatar img {
             width: 99%;
             border-radius: 20px
         }

         .widget-seller .seller-username {
             font-size: 17px;
             font-weight: 700;
             margin-bottom: 15px;
             display: flex;
             align-items: center
         }

         .widget-seller .seller-username i {
             color: #2c96ea;
             font-size: 16px;
             margin-right: 4px;
             font-weight: 400
         }

         .widget-seller .registrations-date {
             color: #979797;
             font-size: 13px;
             font-weight: 400
         }

         .widget-seller .seller-rating a {
             text-decoration: none;
             border: none
         }

         .widget-seller .seller-statistics-container {
             display: flex;
             margin-top: 15px;
             margin-bottom: 15px
         }

         .widget-seller .seller-statistics-container .seller-statistics {
             display: flex;
             flex-direction: column;
             align-items: center;
             margin-left: 10px
         }

         .widget-seller .seller-statistics-container .seller-statistics:last-child {
             margin-left: 0
         }

         .widget-seller .seller-statistics-container .seller-statistics .value {
             background-color: #f5f5f5;
             border-radius: 10px;
             padding: 6px 25px;
             margin-bottom: 5px
         }

         .widget-seller .seller-statistics-container .seller-statistics .label {
             font-size: 12px;
             font-weight: 400
         }
         .widget-seller .seller-biography{
             font-size: 13px;
             text-align: right;
         }
     </style>
@endpush
@section('content')

    <!-- Start main-content -->
    <main class="main-content dt-sl mb-3 mt-6">
        <div class="container main-container">

            <div class="row">
                <div class="col-lg-3 col-md-12 col-sm-12 sticky-sidebar">

                    <div class="dt-sn mb-3">
                        <div class="widget-seller card-body">
                            <div class="seller-avatar shadow-1">
                                @if($seller->seller_info->logo)
                                    <img class="round" src="{{ $seller->seller_info->logo ? asset($seller->seller_info->logo) : null  }}" alt="avatar" height="40" width="40">
                                @else
                                    <span class="c-profile-nav__avatar"><?= mb_substr($seller->seller_info->business_name,0,1,'UTF-8') ?></span>
                                @endif
                            </div>
                            <div class="seller-username">{{$seller->business_name}} <i class="ri-verified-badge-fill"></i>
                            </div>
                            <div class="registrations-date mb-2">{{ $seller->created_at ? $seller->created_at->diffForHumans() : 'چند لحظه پیش' }}</div>
                            <div class="seller-rating d-flex align-items-center mb-2">
                                <span class="link fs-8">آمار رضایت محصولات</span>
                            </div>
                            <div class="registrations-date"><span>عملکرد: </span>
                                <span class="text-success-dark fw-bold">عالی</span>

                            </div>
                            <div class="seller-statistics-container fa-num">
                                <div class="seller-statistics">
                                    <div class="value">% 100</div>
                                    <div class="label">تعهد ارسال</div>
                                </div>
                                <div class="seller-statistics">
                                    <div class="value">% 100</div>
                                    <div class="label">بدون مرجوعی
                                    </div>
                                </div>
                            </div>
                            @if($seller->seller_info and $seller->seller_info->bio)
                                <div class="pt-0 pb-0">
                                    <p class="seller-biography text-gray fw-light fs-8 lh-25 text-justify lts-05 mb-0">
                                        <span class="text-black fw-normal fs-8">بیوگرافی:</span>{{$seller->seller_info->bio}}</p></div>
                            @endif

                        </div>
                    </div>

                @if ($has_filter)
                    @include('front::products.partials.seller-filters',['seller'=>$seller,'filterable'=>$filterable,'products_id'=>$products_id,'categorise'=>$categorise])
                @endif
                </div>

                <div id="category-products-div" data-action="{{ route('front.showSellerStore', ['seller' => $seller]) }}" class="{{--{{ $filterable ? 'col-lg-9' : 'col-lg-12' }}--}}col-lg-9 col-md-12 col-sm-12">
                    <div class="col-12">
                        <!-- Start Content -->
                        <div class="title-breadcrumb-special dt-sl mb-3">
                            <div class="breadcrumb dt-sl">
                                <nav>
                                    <a href="/">خانه</a>
                                    <span>فروشگاه {{ $seller_info->business_name }}</span>

                                 {{--   @foreach ($category->parents() as $parent)
                                        <a href="{{ route('front.products.category', ['category' => $parent]) }}">{{ $parent->title }}</a>
                                    @endforeach
                                    <span>{{ $category->title }}</span>--}}
                                </nav>
                            </div>
                        </div>
                    </div>
                    @if($products->count())
                        <div class="dt-sl dt-sn px-0 search-amazing-tab">

                            <div class="row">
                                <div class="products-list-sort-type ah-tab-wrapper dt-sl">
                                    <div class="ah-tab dt-sl">
                                        <a class="ah-tab-item" data-sort="latest" {{ request('sort_type') == 'latest' || !request('sort_type') ? 'data-ah-tab-active=true' : '' }} >جدید ترین</a>
                                        <a class="ah-tab-item" data-sort="view" {{ request('sort_type') == 'view' ? 'data-ah-tab-active=true' : '' }}>پربازدید ترین</a>
                                        <a class="ah-tab-item" data-sort="sale" {{ request('sort_type') == 'sale' ? 'data-ah-tab-active=true' : '' }} >پرفروش ترین</a>
                                        <a class="ah-tab-item" data-sort="cheapest" {{ request('sort_type') == 'cheapest' ? 'data-ah-tab-active=true' : '' }} >ارزان ترین</a>
                                        <a class="ah-tab-item" data-sort="expensivest" {{ request('sort_type') == 'expensivest' ? 'data-ah-tab-active=true' : '' }} >گران ترین</a>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3 mx-0 px-res-0">
                                @foreach($products as $product)

                                    <div class="col-lg-3 col-md-4 col-sm-6 col-12 px-10 mb-1 px-res-0 category-product-div">
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
    <!-- End main-content -->
@endsection

@push('scripts')

    <script>
        var selected_min_price = {{ request('min_price') ?: $min_price }};
        var selected_max_price = {{ request('max_price') ?: $max_price }};
        var products_min_price = {{ $min_price }};
        var products_max_price = {{ $max_price }};
    </script>
    <script src="{{ theme_asset('js/vendor/theia-sticky-sidebar.min.js') }}"></script>
    <script src="{{ theme_asset('js/vendor/jquery.horizontalmenu.js') }}"></script>
    <script src="{{ theme_asset('js/pages/products/category.js') }}?v=5"></script>
    <script src="{{ theme_asset('js/vendor/nouislider.min.js') }}"></script>
    <script src="{{ theme_asset('js/vendor/wNumb.js') }}"></script>
    <script src="{{ theme_asset('js/vendor/ResizeSensor.min.js') }}"></script>

@endpush

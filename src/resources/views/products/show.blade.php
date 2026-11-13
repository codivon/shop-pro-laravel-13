@extends('front::layouts.master', ['title' => $product->meta_title ?: $product->title])

@push('meta')
    @include('front::products.partials.product-meta')
@endpush

@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('css/vendor/fancybox.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/products/show.css') }}?v=16">
@endpush

@section('content')

    @if($product->isPhysical())

        <!--single-product------------------------->
        <div class="product-page py-20">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid">

                        <!-- start breadcroumb -->
                        <div class="bread-crumb pb-3">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}" class="font-14 text-muted">خانه</a></li>
                                    @if ($product->category)
                                        @foreach ($product->category->parents() as $parent)
                                            <li class="breadcrumb-item"><a
                                                    href="{{ route('front.products.category', ['category' => $parent]) }}"
                                                    class="font-14 text-muted">{{ $parent->title }}</a>
                                            </li>
                                        @endforeach
                                        <li class="breadcrumb-item"><a
                                                href="{{ route('front.products.category', ['category' => $product->category]) }}"
                                                class="font-14 text-muted">{{ $product->category->title }}</a>
                                        </li>
                                    @endif
                                    <li class="breadcrumb-item active main-color-one-color font-14 text-truncate" aria-current="page">{{ $product->title }}</li>
                                </ol>
                            </nav>
                        </div>
                        <!-- end breadcroumb -->

                        <article class="js-product" data-multi-vendor="{{ option('multi_vendor_system_status', 'false') }}">

                            <!-- start product meta -->
                            <div class="row gy-3">

                                {{-- ستون گالری --}}
                                <div class="col-lg-4 col-md-12 col-12">

                                    @if(!$product->addableToCart())
                                        <div class="product-timeout position-relative pt-4 mb-3 text-center">
                                            <div class="promotion-badge not-available">
                                                نا موجود
                                            </div>
                                        </div>
                                    @elseif($product->isPreOrder())
                                        <div class="product-timeout position-relative pt-4 mb-3 text-center">
                                            <div class="promotion-badge">
                                                {{ option('dt_pre_order_badge_text', 'پیش‌فروش') }}
                                            </div>
                                        </div>
                                    @elseif($product->isSpecial())
                                        <div class="product-timeout position-relative pt-4 mb-3 text-center">
                                            <div class="promotion-badge">
                                                فروش ویژه
                                            </div>
                                            @if ($product->special_end_date)
                                                <div id="product-special-end-date" class="countdown-timer mt-3" countdown data-date="{{ $product->special_end_date->format('D M d Y H:i:s O') }}">
                                                    <span data-days="">0</span>:
                                                    <span data-hours="">0</span>:
                                                    <span data-minutes="">0</span>:
                                                    <span data-seconds="">0</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="pro_gallery">
                                        <ul class="gallery-options icon-product-box">
                                            <li class="icon-product-box-item">
                                                <button id="add-to-favorites" type="button" class="btn-option btn-option-wishes bg-transparent border-0 p-0 @if($favorite)btn-option-favorites @endif" data-product="{{$product->id}}" data-action="{{route('front.favorites.store')}}" aria-label="افزودن به علاقه‌مندی">
                                                    <i class="bi bi-heart"></i>
                                                    <span class="tooltip-short">افزودن به علاقه‌مندی</span>
                                                </button>
                                            </li>

                                            @if ($similar_products_count)
                                                <li class="icon-product-box-item">
                                                    <a href="{{ route('front.products.compare', ['product1' => $product->id]) }}" class="btn-option btn-option-comparison" aria-label="مقایسه محصول">
                                                        <i class="bi bi-arrow-left-right"></i>
                                                        <span class="tooltip-short">مقایسه محصول</span>
                                                    </a>
                                                </li>
                                            @endif

                                            @if ($show_prices_chart)
                                                <li class="icon-product-box-item">
                                                    <button type="button" class="btn-option btn-option-alarm bg-transparent border-0 p-0" data-bs-toggle="modal" data-bs-target="#price-changes-modal" aria-label="نمودار قیمت">
                                                        <i class="bi bi-bar-chart"></i>
                                                        <span class="tooltip-short">نمودار قیمت</span>
                                                    </button>
                                                </li>
                                            @endif

                                            @if (option('show_product_share_links', 1) == 1)
                                                <li class="icon-product-box-item">
                                                    <button type="button" class="btn-option btn-option-alarm bg-transparent border-0 p-0" data-bs-toggle="modal" data-bs-target="#shareproduct" aria-label="اشتراک گذاری">
                                                        <i class="bi bi-share"></i>
                                                        <span class="tooltip-short">اشتراک گذاری</span>
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>

                                        {{-- فاز ۱۵۵ — زوم تصویر حذف شد (ez-plus دیگر لود/فراخوانی نمی‌شود)؛
                                             تصویر اصلی با کلاس product-main-image سایز می‌گیرد --}}
                                        <img class="product-main-image mt-3"
                                             src="{{$product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                                             alt="{{ $product->title }}"/>
                                        @if($product->gallery()->count())
                                            {{-- فاز ۱۵۵ — بندانگشتی‌ها بدون ez-plus؛ تعویض تصویر با theme-product.js --}}
                                            <div id="gallery_01f">
                                                <ul class="gallery-items owl-carousel owl-theme" id="gallery-slider">
                                                    @foreach ($product->gallery()->orderBy('ordering')->get() as $item)
                                                        <li class="item">
                                                            <a href="javascript:void(0)" class="js-gallery-thumb active"
                                                               data-image="{{$item->image ? asset($item->image) : asset('/no-image-product.svg')}}">
                                                                <img src="{{$item->image ? asset($item->image) : asset('/no-image-product.svg')}}"
                                                                     alt="{{ $product->title }}"/></a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- ستون اطلاعات و خرید (پارشال product-info) --}}
                                @include('front::products.partials.product-info')

                            </div>
                            <!-- end product meta -->

                            <!-- Modal social-->
                            <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog"
                                 aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalCenterTitle">اشتراک گذاری در شبکه های اجتماعی
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-share-row">
                                                <div class="form-share-col">
                                                    <ul class="btn-group-share">
                                                        <li>
                                                            <a class="btn-share btn-share-twitter" target="_blank" href="https://twitter.com/intent/tweet?url={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                                                <i class="bi bi-twitter"></i>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="btn-share btn-share-facebook" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                                                <i class="bi bi-facebook"></i>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="btn-share btn-share-whatsapp" target="_blank" href="https://api.whatsapp.com/send?text={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                                                <i class="bi bi-whatsapp"></i>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal social-->

                        </article>

                        @if(option('multi_vendor_system_status','false')=="true")
                            <div class="box-suppliers sellers-box shadow-box rounded-3 p-3 mt-4">
                                <div class="box-suppliers-headline-container">
                                    <div class="headline-delivery">
                                        <span>لیست فروشندگان این کالا</span>
                                    </div>
                                    <a href="{{route('seller.index')}}" target="_blank" class="link-border">کالای خود را در {{ option('info_site_title', 'او پی شاپ') }} بفروشید</a>
                                </div>

                                <div class="table-suppliers">
                                    <div class="table-suppliers-body">
                                        <div class="table-suppliers-store">

                                        </div>

                                        <span id="get-stores" class="d-none" data-action="{{route('front.products.get_stores')}}"></span>

                                        @if(count($seller_variants)>2)
                                            <div class="table-suppliers-more">
                                                <a class="link-border">مشاهده
                                                    <span class="show-more more-suppliers-count">( فروشنــــده / گارانتی بیشتـــــر)
                                                    </span>
                                                    <span class="show-less">کمتر</span>
                                                </a>
                                            </div>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-4">
                            @if(count($related_products))
                                <div class="col-12">
                                    <div class="product-slider related-products-slider">
                                        <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                                            <div class="title d-flex align-items-center">
                                                <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                                                <h5 class="font-16 ms-3">محصولات <span class="main-color-one-color d-inline-block">مرتبط</span></h5>
                                            </div>
                                        </div>
                                        <div class="swiper product-slider-swiper">
                                            <div class="swiper-wrapper">
                                                @foreach ($related_products as $related_product)
                                                    <div class="swiper-slide">
                                                        @include('front::partials.product-card', ['product' => $related_product, 'withTimer' => false, 'hoverBtns' => true])
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="swiper-button-next d-lg-flex d-none"></div>
                                            <div class="swiper-button-prev d-lg-flex d-none"></div>
                                            <div class="swiper-pagination"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="p-tabs p-0 col-12">
                                <div class="box-tabs-main">
                                    <ul class="box-tabs">
                                        @if ($product->isDownload())
                                            <li class="box-tabs-tab">
                                                <a> فایل های محصول</a>
                                            </li>
                                        @endif
                                        @if ($product->description)
                                            <li class="box-tabs-tab  active-tabs">
                                                <a> مشخصات کلی</a>
                                            </li>
                                        @endif
                                        @if ($product->specificationGroups()->count())
                                            <li class="box-tabs-tab @if (!$product->description)active-tabs @endif">
                                                <a>مشخصات فنی</a>
                                            </li>
                                        @endif
                                        <li class="box-tabs-tab @if (!$product->specificationGroups()->count() and !$product->description) active-tabs @endif">
                                            <a>نظرات</a>
                                        </li>
                                        <li class="box-tabs-tab">
                                            <a>پرسش و پاسخ</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tabs-content">
                                    <div class="tab-active-content">
                                        @if ($product->description)
                                            <div class="tab content-expert" style="display:block;">
                                                <article class="shadow-box rounded-3 p-4">
                                                    <h2 class="params-headline font-18">
                                                        نقد و بررسی اجمالی
                                                        <span>{{$product->title}}</span>
                                                    </h2>
                                                    <section class="content-expert-summary">
                                                        <div class="is-masked">
                                                            <div class="mask-text-product-summary">
                                                                <p>{!! $product->description !!}</p>
                                                            </div>
                                                            <a class="mask-handler">
                                                                <span class="show-more">ادامه مطلب</span>
                                                                <span class="show-less">بستن</span>
                                                            </a>
                                                            <div class="shadow-box"></div>
                                                        </div>
                                                    </section>
                                                </article>
                                            </div>
                                        @endif
                                        @if ($product->specificationGroups()->count())
                                            <div class="tab params"
                                                 style="@if (!$product->description)display:block; @else display:none;  @endif">
                                                <article class="shadow-box rounded-3 p-4">
                                                    <h2 class="params-headline font-18">
                                                        مشخصات فنی
                                                        <span>{{$product->title}}</span>
                                                    </h2>
                                                    @php
                                                        // FIX مقیاس: مشخصات یک‌بار لود می‌شوند (قبلاً به‌ازای هر گروه یک کوئری = N+1)
                                                        $product_specifications = $product->specifications;
                                                    @endphp
                                                    @foreach($product->specificationGroups->unique() as $group)
                                                        <section>
                                                            <h3 class="params-title font-16">{{ $group->name }}</h3>
                                                            <ul class="params-list">
                                                                @foreach($product_specifications->where('pivot.specification_group_id', $group->id) as $specification)
                                                                    <li class="row g-2">
                                                                        <div class="col-lg-3 col-md-3 col-12">
                                                                            <div class="params-list-key">
                                                                                <span class="block fw-bold">{{ $specification->name }}</span>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-lg-9 col-md-9 col-12">
                                                                            <div class="params-list-value">
                                                                                <span class="block">{!! nl2br(htmlentities($specification->pivot->value)) !!}</span>
                                                                            </div>
                                                                        </div>

                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </section>
                                                    @endforeach
                                                </article>
                                            </div>
                                        @endif
                                        <div class="tab comments" id="CommentsTab"
                                             style="@if (!$product->specificationGroups()->count() and !$product->description)display:block; @else display:none;  @endif">
                                            <h2 class="comments-headline font-18">امتیاز کاربران به:
                                                <span>
                                        <span>{{$product->title}}</span>
                                    </span>
                                            </h2>
                                            <div class="comments-summary row gy-3">
                                                <div class="col-lg-4 col-md-4 col-12">
                                                    <div class="comments-summary-box shadow-box rounded-3 p-3 h-100">
                                                        <div class="comments-side-rating-container">
                                                            <div class="comments-side-rating text-center">
                                                                @if ($reviews->count())
                                                                    <div
                                                                        class="comments-side-rating-main font-24 fw-bold main-color-one-color">{{ $product->rating }}</div>
                                                                    <div class="comments-side-rating-desc text-muted">از ۵</div>
                                                                @else
                                                                    <div class="comments-side-rating-desc text-muted">هنوز امتیازی ثبت
                                                                        نشده است
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="comments-side-rating-bottom text-center">
                                                                @if ($reviews->count())
                                                                    <div class="product-star mb-3">
                                                                        @for($i=1;$i<=5;$i++)
                                                                            <i class="bi bi-star-fill @if($i<=$product->rating)active @endif"></i>
                                                                        @endfor
                                                                    </div>
                                                                    <div class="comments-side-rating-all text-muted font-14">
                                                                        از مجموع {{ $product->reviews_count }} امتیاز
                                                                    </div>
                                                                @else
                                                                    <div class="product-star mb-3">
                                                                        @for($i=1;$i<=5;$i++)
                                                                            <i class="bi bi-star-fill"></i>
                                                                        @endfor
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if(count($ProductAbilityScores) and $reviews->count())
                                                            <ul class="comments-item-rating">
                                                                @foreach($ProductAbilityScores as $ProductAbilityScore)
                                                                    <li>
                                                                        <div
                                                                            class="cell">{{$ProductAbilityScore->name}}</div>
                                                                        <div class="cell-2">
                                                                            <div class="rating rating-general js-rating"
                                                                                 style="width: 85%">
                                                                                <div class="rating-rate"
                                                                                     style="width: {{(100/5)*$ProductAbilityScore->value}}%"></div>
                                                                            </div>
                                                                            <span
                                                                                class="rating-overall-word">{{$ProductAbilityScore->value}}</span>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>

                                                    <div class="comments-summary-note shadow-box rounded-3 p-3 mt-3">
                                                        <span>شما هم درباره این کالا دیدگاه ثبت کنید</span>
                                                        @if (auth('web')->check())
                                                            <button type="button" data-bs-toggle="modal"
                                                                    data-bs-target="#add-product-review-modal"
                                                                    class="btn border-0 main-color-one-bg rounded-pill mt-2">
                                                                <i class="bi bi-chat-square-text me-1"></i>
                                                                افزودن نظر جدید
                                                            </button>
                                                        @else
                                                            <a href="{{ route('login', ['redirect', route('front.products.show', ['product' => $product])]) }}"
                                                               class="btn border-0 main-color-one-bg rounded-pill mt-2">
                                                                افزودن نظر جدید
                                                                <i class="bi bi-chat-square-text me-1"></i>
                                                            </a>

                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-lg-8 col-md-8 col-12">

                                                    @if ($reviews->count())
                                                        <div class="comments-filter shadow-box rounded-3 p-3 mb-3">
                                                            <div class="filter-item-main">
                                                                <ul class="filter-items nav nav-tabs" id="myTab"
                                                                    role="tablist">
                                                                    <li>
                                                        <span class="sort-row-text font-14"><i class="bi bi-sort-down"></i>
                                                            مرتب‌سازی
                                                            دیدگاه‌ها بر اساس:</span>
                                                                    </li>
                                                                    <li class="nav-item filter-items-active" data-id="new">
                                                                        <a class="nav-link active" id="Newscomments-tab"
                                                                           data-toggle="tab"
                                                                           href="#Newscomments" role="tab"
                                                                           aria-controls="Newscomments"
                                                                           aria-selected="true">جدیدترین نظرات</a>

                                                                    <li class=" nav-item" data-id="Buyerscomments">
                                                                        <a class="nav-link " id="Buyerscomments-tab"
                                                                           data-toggle="tab" role="tab"
                                                                           aria-controls="Buyerscomments"
                                                                           aria-selected="true">نظر
                                                                            خریداران</a>
                                                                    </li>
                                                                    <li class="nav-item" data-id="moreLike">
                                                                        <a class="nav-link" id="Usefulcomments-tab"
                                                                           data-toggle="tab"
                                                                           role="tab"
                                                                           aria-controls="Usefulcomments"
                                                                           aria-selected="true">مفیدترین
                                                                            نظرات</a>
                                                                    </li>

                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div id="product-comment-list">
                                                            <div class="tab-content" id="myTabContent"
                                                                 style="display: contents;">
                                                                <input name="UrlGetComment" type="hidden"
                                                                       value="{{route('front.products.getComments')}}">
                                                                <input name="poduct_id" type="hidden"
                                                                       value="{{route('front.products.getComments')}}">
                                                                <div class="tab-pane fade show active" id="Newscomments"
                                                                     role="tabpanel" aria-labelledby="Newscomments-tab">
                                                                    @include('front::products.partials.reviews-comments')
                                                                </div>
                                                                <input type="hidden" name="sortComment" value="new">
                                                            </div>
                                                        </div>
                                                    @else
                                                        <p>
                                                            <b>شما هم می‌توانید در مورد این کالا نظر دهید.</b><br>

                                                            اگر این محصول را قبلا
                                                            از {{ option('info_site_title', 'او پی شاپ') }} خریده باشید،
                                                            دیدگاه شما به عنوان خریدار ثبت خواهد شد.

                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab js-faq-container" id="" style="display:none;">
                                            <input name="UrlGetQuestions" type="hidden"
                                                   value="{{route('front.products.getQuestions')}}">
                                            <input type="hidden" name="sortQuestions" value="new">
                                            @include('front::components.comments', ['model' => $product, 'route_link' => route('front.product.comments', ['product' => $product]), 'message' => 'هیچ دیدگاهی برای این محصول ثبت نشده است.' ])


                                        </div>
                                    </div>
                                    <div class="footer-product-id" data-id="{{$product->id}}">
                                        <span>شناسه کالا :</span>
                                        <span>DKP - {{$product->id}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('front::products.partials.sizes-modal')
            <!--single-product------------------------->
            @if (auth('web')->check())
                @include('front::products.partials.add-review')
            @endif
        </div>

        @if ($show_prices_chart)
            <!-- Modal -->
            <div class="modal fade" id="price-changes-modal" tabindex="-1" aria-labelledby="price-changes-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="price-changes-modal-label">نمودار قیمت فروش</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                        </div>
                        <div class="modal-body chart-area">
                            <strong class="text-muted">{{ $product->title }}</strong>
                            <p class="mt-1 text-muted" id="selected-chart-price-title"></p>
                            <div>
                                <div id="empty-chart" class="empty-chart" style="display: none">
                                    <p>در سی روز گذشته تغییر قیمتی برای این کالا ثبت نشده است.</p>
                                </div>
                                <div id="chart" class="ltr"></div>
                            </div>
                            <ul class="chart-prices-label">
                                @foreach ($product->prices()->orderBy('stock', 'desc')->get() as $chart_price)
                                    @php
                                        $label = $chart_price->getAttributesName();
                                    @endphp
                                    <li>
                                        <label data-action="{{ route('front.products.priceChart', ['price' => $chart_price]) }}" data-title="{{ $chart_price->getAttributesName() }}" title="{{ $chart_price->getAttributesName() }}">
                                            <span>{{ $label != '' ? $label : $product->title }}</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    @elseif($product->isDownload())
        @include('front::products.partials.download-product',$product)
    @endif


    @if (option('show_product_share_links', 1) == 1)
        <!-- Modal -->
        <div class="modal fade" id="shareproduct" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">اشتراک گذاری</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">

                        <div><p>با استفاده از روش‌های زیر می‌توانید این صفحه را با دوستان خود به اشتراک بگذارید.</p></div>
                        <ul class="share-product">

                            <a target="_blank" class="telegram" href="https://t.me/share/url?url={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                <li  class="bi bi-telegram"></li>
                            </a>

                            <a target="_blank" class="whatsapp" href="https://api.whatsapp.com/send?text={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                <li  class="bi bi-whatsapp"></li>
                            </a>
                            <a target="_blank" class="twiiter" href="https://twitter.com/intent/tweet?url={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                <li  class="bi bi-twitter"></li>
                            </a>
                            <a target="_blank" class="linkedin" href="https://www.linkedin.com/sharing/share-offsite/?url= {{route('front.products.shortLink', ['id' => $product->id]) }}">
                                <li  class="bi bi-linkedin"></li>
                            </a>
                        </ul>
                        <hr>
                        <div class="filed-link dir-ltr copy-text">

                            <input id="shareLink" type="text" disabled value="{{ route('front.products.shortLink', ['id' => $product->id]) }}" readonly="">

                            <div class="copy-text-btn" data-bs-toggle="tooltip" data-bs-placement="right" title="کپی لینک">
                                <i class="bi bi-copy"></i>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif


    @if(!$product->addableToCart())
        <!-- Start Modal stocknotify -->
        <div class="modal fade" id="modal-stock-notify" role="dialog"
             aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-md send-info modal-dialog-centered"
                 role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalCenterTitle">
                            <i class="bi bi-geo-alt"></i>
                            {{ trans('front::messages.products.inventory-information') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-ui dt-sl">
                                    <form class="form-account" action="#">
                                        <div class="row">
                                            <div class="col-md-6 col-sm-12 mb-2">
                                                <div class="form-row-title">
                                                    <h4>
                                                        {{ trans('front::messages.products.fname-and-lname') }} </h4>
                                                </div>
                                                <div class="form-row">
                                                    <input class="form-control text-right"
                                                           type="text"
                                                           name="name"
                                                           id="stock-name"
                                                           placeholder=" {{ trans('front::messages.products.enter-your-name') }} " required>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-sm-12 mb-2">
                                                <div class="form-row-title">
                                                    <h4>
                                                        {{ trans('front::messages.products.phone-number') }}
                                                    </h4>
                                                </div>
                                                <div class="form-row">
                                                    <input
                                                        class="form-control dir-ltr text-left"
                                                        type="text"
                                                        name="mobile"
                                                        id="stock-mobile"
                                                        placeholder="09xxxxxxxxx" required>
                                                </div>
                                            </div>

                                            <div class="col-12 text-center">
                                                <button id="sendStockNotifyBtn" type="button" class="btn border-0 main-color-one-bg rounded-pill" data-bs-dismiss="modal">{{ trans('front::messages.products.let-me-know') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Modal stocknotify -->
    @endif

    {{-- فاز ۸۳ — مودال مدرن هشدار کاهش قیمت (برای مهمان و کاربر عضو) --}}
    {{-- استایل و اسکریپت این قابلیت به فایل‌های asset منتقل شده است:
         css/pages/products/price-alert.css و js/pages/products/price-alert.js --}}
    @if(option('dt_price_alert_status', '1') == '1')
        @php
            $paModalTitle = option('dt_price_alert_modal_title', 'هشدار کاهش قیمت') ?: 'هشدار کاهش قیمت';
            $paModalDesc  = option('dt_price_alert_modal_desc', '') ?: 'به‌محض اینکه قیمت این محصول کمتر شد، همان لحظه به شما خبر می‌دهیم؛ دیگر لازم نیست صفحه را دنبال کنید.';

            $paChannelRaw = trim((string) option('dt_price_alert_channels', 'sms,profile'));
            $paChannelList = collect(explode(',', $paChannelRaw))
                ->map(fn ($c) => trim(strtolower($c)))
                ->filter(fn ($c) => in_array($c, ['sms', 'profile'], true))
                ->unique()->values()->all();
            $paChannelList = $paChannelList ?: ['sms', 'profile'];

            $paIsUser = auth('web')->check();
            $paUserMobile = $paIsUser ? (auth('web')->user()->mobile ?: auth('web')->user()->username) : '';

            // نقشهٔ رنگ — هم‌قرارداد با مودال/دکمه
            $paColorMap = [
                'red'    => ['#ef394e', '#c8102e'],
                'orange' => ['#f97316', '#ea580c'],
                'green'  => ['#10b981', '#059669'],
                'teal'   => ['#06b6d4', '#0891b2'],
                'purple' => ['#8b5cf6', '#7c3aed'],
                'dark'   => ['#334155', '#0f172a'],
            ];
            [$paBg, $paBg2] = $paColorMap[option('dt_price_alert_button_color', 'red') ?: 'red'] ?? $paColorMap['red'];
        @endphp

        @push('styles')
            <link rel="stylesheet" href="{{ theme_asset('css/pages/products/price-alert.css') }}">
        @endpush

        <div class="dt-pa-overlay" id="dt_pa_overlay" aria-hidden="true">
            <div class="dt-pa-modal" role="dialog" aria-modal="true" aria-labelledby="dt_pa_title"
                 data-product="{{ $product->id }}"
                 data-user="{{ $paIsUser ? '1' : '0' }}"
                 data-action="{{ route('front.price-alert.store') }}"
                 style="--dt-pa-bg: {{ $paBg }}; --dt-pa-bg2: {{ $paBg2 }};">

                <button type="button" class="dt-pa-close" aria-label="بستن" title="بستن">&times;</button>

                {{-- حالت اصلی --}}
                <div class="dt-pa-body" id="dt_pa_body">
                    <div class="dt-pa-icon"><i class="bi bi-bell" aria-hidden="true"></i></div>
                    <h3 class="dt-pa-title" id="dt_pa_title">{{ $paModalTitle }}</h3>
                    <p class="dt-pa-desc">{{ $paModalDesc }}</p>

                    <div class="dt-pa-channels" role="group" aria-label="راه اطلاع‌رسانی">
                        @if(in_array('sms', $paChannelList))
                            <button type="button" class="dt-pa-channel is-on" data-channel="sms" aria-pressed="true">
                                <span class="dt-pa-channel-ico"><i class="bi bi-phone" aria-hidden="true"></i></span>
                                <span class="dt-pa-channel-txt">
                                    <strong>پیامک موبایل</strong>
                                    <small>به‌محض کاهش قیمت، پیامک اطلاع‌رسانی می‌گیرید</small>
                                </span>
                                <span class="dt-pa-check"><i class="bi bi-check" aria-hidden="true"></i></span>
                            </button>
                        @endif

                        @if($paIsUser && in_array('profile', $paChannelList))
                            <button type="button" class="dt-pa-channel is-on" data-channel="profile" aria-pressed="true">
                                <span class="dt-pa-channel-ico"><i class="bi bi-person" aria-hidden="true"></i></span>
                                <span class="dt-pa-channel-txt">
                                    <strong>اعلان در پروفایل</strong>
                                    <small>در صفحهٔ «اعلان‌ها»ی پروفایل کاربری شما ذخیره می‌شود</small>
                                </span>
                                <span class="dt-pa-check"><i class="bi bi-check" aria-hidden="true"></i></span>
                            </button>
                        @elseif(!$paIsUser && in_array('profile', $paChannelList))
                            <div class="dt-pa-channel is-locked" aria-disabled="true">
                                <span class="dt-pa-channel-ico"><i class="bi bi-lock" aria-hidden="true"></i></span>
                                <span class="dt-pa-channel-txt">
                                    <strong>اعلان در پروفایل</strong>
                                    <small>مخصوص کاربران عضو — با <a href="{{ route('login') }}">ورود به حساب</a> فعال می‌شود</small>
                                </span>
                            </div>
                        @endif
                    </div>

                    <div id="dt_pa_guest_fields">
                        @if($paIsUser)
                            <p class="dt-pa-hint">
                                <i class="bi bi-phone" aria-hidden="true"></i>
                                پیامک به شمارهٔ حساب شما (<b>{{ $paUserMobile }}</b>) ارسال می‌شود.
                            </p>
                        @else
                            <div class="dt-pa-field">
                                <label for="dt_pa_name">نام و نام خانوادگی (اختیاری)</label>
                                <input type="text" class="dt-pa-input" id="dt_pa_name" placeholder="نام شما" maxlength="100" autocomplete="name">
                            </div>
                            <div class="dt-pa-field">
                                <label for="dt_pa_mobile">شماره موبایل</label>
                                <input type="tel" class="dt-pa-input" id="dt_pa_mobile" dir="ltr" inputmode="numeric"
                                       placeholder="09xxxxxxxxx" maxlength="11" autocomplete="tel">
                            </div>
                        @endif
                    </div>

                    <p class="dt-pa-error" id="dt_pa_error" hidden></p>

                    <button type="button" class="dt-pa-submit" id="dt_pa_submit">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        فعال‌سازی هشدار
                    </button>

                    <p class="dt-pa-note">با هر افت قیمت فقط یک‌بار خبردار می‌شوید؛ بعد از آن می‌توانید دوباره هشدار بگذارید.</p>
                </div>

                {{-- حالت موفقیت --}}
                <div class="dt-pa-success" id="dt_pa_success" hidden>
                    <div class="dt-pa-success-ico"><i class="bi bi-check" aria-hidden="true"></i></div>
                    <h3>هشدار فعال شد!</h3>
                    <p>به‌محض کاهش قیمت این محصول، از طریق راه‌هایی که انتخاب کردید خبرتان می‌کنیم.</p>
                </div>
            </div>
        </div>
    @endif
    <!-- End Modal price-alert (فاز ۸۳) -->

@endsection

{{-- فاز ۱۵۵ — پوش ez-plus حذف شد: زوم تصویر محصول کاملأ برداشته شده --}}

@push('scripts')
     <script src="{{theme_asset('js/theia-sticky-sidebar.min.js')}}"></script>
     <script src="{{ theme_asset('js/vendor/jquery.fancybox.min.js') }}"></script>
     <script src="{{ theme_asset('js/plugins/apexcharts/apexcharts.js') }}"></script>
     @if(option('dt_price_alert_status', '1') == '1')
        <script src="{{ theme_asset('js/pages/products/price-alert.js') }}"></script>
     @endif
   <script src="{{ theme_asset('js/pages/products/show.js') }}?v=16"></script>
   <script src="{{ theme_asset('js/pages/comments.js') }}"></script>
   {{-- فاز ۱۵۵ — منطق لحظه‌ای قیمت + تعویض تصویر گالری (بدون اسکریپت اینلاین) --}}
   <script src="{{ theme_asset('js/theme-product.js') }}?v=1"></script>
@endpush

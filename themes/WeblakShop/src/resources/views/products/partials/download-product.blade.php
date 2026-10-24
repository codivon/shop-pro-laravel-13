@extends('front::layouts.master', ['title' => $product->meta_title ?: $product->title])

@push('meta')
    @include('front::products.partials.product-meta')
@endpush

@section('content')
<div class="col-12" style="transform: none;">
    <div class="product-page" style="transform: none;">
        <article class="js-product" style="transform: none;">
            <div class="product-nav-container">
                <nav aria-label="breadcrumb">
                    @if ($product->category)

                        <ol class="breadcrumb px-0">
                            @foreach ($product->category->parents() as $parent)
                                <li class="breadcrumb-item"><a
                                        href="{{ route('front.products.category', ['category' => $parent]) }}">{{ $parent->title }}</a>
                                </li>
                            @endforeach
                            <li class="breadcrumb-item"><a
                                    href="{{ route('front.products.category', ['category' => $product->category]) }}">{{ $product->category->title }}</a>
                            </li>
                        </ol>  @endif
                </nav>
            </div>
            <div class="col-lg-8 col-md-12 col-xs-12 pull-right">
                <div class="product-gallery">
                    <div class="main-slider" style="margin-right: 15px;width: 98%">
                        <div class="main-slider-container">
                            <div id="carouselExampleIndicators" class="carousel " data-ride="carousel">
                                @if($product->gallery()->count()>=2)
                                <ol class="carousel-indicators">
                                    @foreach ($product->gallery()->orderBy('ordering')->get() as $item)
                                    <li data-target="#carouselExampleIndicators" style='border: 1px solid #ef394e' data-slide-to="{{$loop->index}}" class="{{$loop->index==0 ? 'active' : ''}}"></li>
                                    @endforeach
                                </ol>
                                @endif
                                <div class="carousel-inner">
                                    @if($product->gallery()->count())
                                    @foreach ($product->gallery()->orderBy('ordering')->get() as $item)
                                    <div class="carousel-item {{$loop->index==0 ? 'active' : ''}}">
                                        <img class="d-block w-100" src="{{$item->image ? asset($item->image) : asset('/no-image-product.svg') }}" alt="{{$product->title.'-image-'.$item->id}}">
                                    </div>
                                    @endforeach
                                    @else
                                        <div class="carousel-item active">
                                            <img class="d-block w-100" src="{{$product->image ? asset($product->image) : asset('/no-image-product.svg') }}" alt="{{$product->title.'-image-'.$product->id}}">
                                        </div>
                                    @endif
                                </div>
                                @if($product->gallery()->count()>=2)
                                <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button"
                                   data-slide="prev">
                                    <span class="fa fa-angle-left" aria-hidden="true"></span>
                                    <span class="sr-only">Previous</span>
                                </a>
                                <a class="carousel-control-next" href="#carouselExampleIndicators" role="button"
                                   data-slide="next">
                                    <span class="fa fa-angle-right" aria-hidden="true"></span>
                                    <span class="sr-only">Next</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="gallery-item">
                        <ul class="gallery-options">
                            <li class="option-wishes">
                                <button id="add-to-favorites" class="btn-option btn-option-wishes @if($favorite)btn-option-favorites @endif" data-product="{{$product->id}}" data-action="{{route('front.favorites.store')}}">
                                    <i class="mdi mdi-heart-outline"></i>
                                    <span class="tooltip-short">افزودن به علاقه‌مندی</span>
                                </button>
                            </li>

                            {{-- <li class="option-alarm">
                                 <button class="btn-option btn-option-alarm" data-toggle="modal"
                                         data-target="#exampleModalCenteralarm">
                                     <i class="mdi mdi-bell-outline"></i>
                                     <span class="tooltip-short">اطلاع‌رسانی</span>
                                 </button>
                             </li>--}}
                            @if ($similar_products_count)
                                <li class="option-alarm">
                                    <a href="{{ route('front.products.compare', ['product1' => $product->id]) }}" class="btn-option btn-option-comparison">
                                        <i class="mdi mdi-compare"></i>
                                        <span class="tooltip-short">مقایسه محصول</span>
                                    </a>
                                </li>
                            @endif

                            @if ($show_prices_chart)
                                <li>
                                    <button class="btn-option btn-option-alarm" data-toggle="modal"
                                            data-target="#price-changes-modal">
                                        <i class="mdi mdi-chart-line"></i>
                                        <span class="tooltip-short">نمودار قیمت</span>
                                    </button>
                                </li>
                            @endif
                            @if (option('show_product_share_links', 1) == 1)
                                <li>
                                    <button class="btn-option btn-option-alarm" data-toggle="modal"
                                            data-target="#shareproduct">
                                        <i class="mdi mdi-share-variant"></i>
                                        <span class="tooltip-short">اشتراک گذاری</span>
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-12 col-xs-12 pull-left px-0" style="transform: none;">
                <section class="product-info" style="transform: none;">
                    @if ($product->category)
                        <div class="product-headline">
                            <div class="product-title-container">
                                <div class="product-directory">
                                    <ul class="mb-0">
                                        @foreach ($product->category->parents() as $parent)
                                            <li>
                                                <a class="link-border" href="{{ route('front.products.category', ['category' => $parent]) }}">{{ $parent->title }}</a>
                                            </li>
                                            <li>
                                                <span>/</span>
                                            </li>
                                        @endforeach

                                        <li>
                                            <a class="link-border" href="{{ route('front.products.category', ['category' => $product->category]) }}">{{ $product->category->title }}</a>
                                        </li>
                                    </ul>
                                    <h1 class="product-title">{{ $product->title }}</h1>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="product-attributes" style="transform: none;">
                        <div class="col-lg-12 col-md-8 col-xs-12 pull-right pr-0">
                            <div class="product-config">
                                <span class="product-title-en">{{ $product->title_en }}</span>
                                @if ($product->rating)
                                    <div class="product-engagement">
                                        <div class="product-engagement-item">
                                            <div class="product-engagement-rating">{{ $product->rating }}
                                                <span class="product-engagement-rating-num">
                                                    ({{ $product->reviews_count }})
                                                </span>
                                            </div>
                                        </div>

                                        <div class="product-engagement-item">
                                            <div class="product-engagement-set"></div>
                                            <div class="product-engagement-link" data-activate-tab="comments">
                                                {{ $product->reviews_count }}
                                                دیدگاه کاربران
                                            </div>
                                        </div>
                                        <div class="product-engagement-item">
                                            <div class="product-engagement-set"></div>
                                            <div class="product-engagement-link" data-activate-tab="questions">
                                                {{ $product->comments()->accepted()->count() }}
                                                پرسش و پاسخ
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($product->suggestionCount())
                                    <div class="col-12 d-flex">
                                        <i class="mdi mdi mdi-thumb-up-outline text-success mx-0"></i>
                                        <p class="text-muted commodity mx-2"><span>{{ $product->suggestionPercent() }}%</span>({{ $product->suggestionCount() }}
                                            ) نفر از خریداران این کالا را پیشنهاد کردن </p>
                                    </div>
                                @endif


                                <div class="product-config-wrapper d-flex">
                                    @if ($product->brand)
                                        <div class="product-params p-0">
                                            <ul class="m-0" data-title="برند">
                                                <li class="mb-0">
                                                    <span>برند: </span>
                                                    <span>
                                             <a  href="{{ route('front.brands.show', ['brand' => $product->brand]) }}" class="link--with-border-bottom link-border">{{ $product->brand->name }}</a>
                                            </span>
                                                </li>
                                            </ul>

                                        </div>
                                    @endif
                                        @if ($product->specialSpecifications()->count())
                                            <div class="product-params">
                                                <ul data-title="ویژگی‌های محصول">

                                                    <li class="title-product-features">
                                                        ویژگی‌های محصول
                                                    </li>
                                                    @foreach ($product->specialSpecifications() as $specification)
                                                        <li class='{{$loop->index>=3 ? 'product-params-more' : ''}}'>
                                                            <span>{{ $specification->name }}: </span>
                                                            <span> {{ $specification->pivot->value }} </span>
                                                        </li>
                                                    @endforeach
                                                    @if ($product->specialSpecifications()->count() > 3)
                                                        <li class="product-params-more-handler">
                                                            <a class="link-border" href="">
                                                                <span class="show-more">موارد بیشتر</span>
                                                                <span class="show-less">بستن</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>


                                                <p class="little-des pt-0 mt-0"></p>

                                                @if ($product->short_description)
                                                    <div class="product-additional-info">
                                                        <div class="product-additional-item is-masked">
                                                            <p style="max-height:150px">{!! nl2br($product->short_description) !!}</p>
                                                            <a class="mask-handler link-border" href=''>
                                                                <span class="show-more">مشاهده بیشتر</span>
                                                                <span class="show-less">مشاهده کمتر</span>
                                                            </a>
                                                            <div class="shadow-box"></div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                </div>
                            </div>
                        </div>

                    </div>

                </section>
            </div>
        </article>

        <div class="box-suppliers mt-3">
            <div class="box-suppliers-headline-container">
                <div class="headline-delivery">
                    <span>فایل های محصول</span>
                </div>
                <a href="{{route('seller.index')}}" target="_blank" class="link-border" style="float:left;">کالای خود را در {{ option('info_site_title', 'او پی شاپ') }} بفروشید</a>
            </div>
            <div class="table-suppliers">
                <div class="table-suppliers-body">
                    @foreach ($product->prices()->whereHas('file')->orderBy('prices.ordering')->get() as $price)
                        <div class="table-suppliers-row ">
                                <div class="table-suppliers-cell d-flex">
                                    <span class="mdi mdi-lock font-size-20 ml-1"></span>

                                    <p class="m-0 mr-1">{{ $price->file->title }}</p>
                                </div>

                            {{--    <div class="table-suppliers-cell table-suppliers-cell-title">
                                   <div class="seller-wrapper">
                                       <p class="table-suppliers-seller-name">
                                           @if(!$product->seller_id)
                                               <span><a href="/">{{ option('info_site_title', 'او پی شاپ') }}</a></span>
                                           @else
                                               <span><a href="/">{{ $product->seller->seller_info->business_name }}</a></span>
                                           @endif
                                       </p>
                                 {{-- <div class="table-suppliers-rating">
                                           <div class="product-seller-second-line">
                                               عملکرد:
                                               <span class="u-text-bold">۵</span>
                                               از ۵
                                               <span class="u-divider"></span>
                                               <span class="u-text-bold">۸۳٪</span>
                                               رضایت از کالا
                                           </div>
                                       </div>
                                   </div>
                               </div>--}}
                            <div class="table-suppliers-cell table-suppliers-cell-price">
                                <div class="seller-wrapper">
                                    <div class="price-secondary">
                                        <div class="price-secondary">
                                            <div class="price-secondary">
                                                <div class="product-seller-row-price mb-3">
                                                    @if ($price->discount)
                                                        <div class="product-seller-price-real text-danger">
                                                            <del class="product-seller-price-prev text-danger font-size-16">{{ number_format($price->tomanPrice()) }}</del>
                                                            تومان
                                                            <div class="discount show-discount mr-3 t-25 r--10">
                                                                <span>{{ $price->discount() }}%</span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="product-seller-price-real">
                                                        <div class="product-seller-price-prev font-size-20">{{ number_format($price->discountPrice()) }} </div>
                                                        تومان
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-suppliers-cell table-suppliers-cell-price">
                                <div class="seller-wrapper">
                                    <div class="price-secondary">
                                        <div class="price-value">
                                            {{ formatSizeUnits($price->file->size) }}
                                        </div>
                                    </div>
                                </div>
                            </div>




                                @if ($price->isDownloadable())

                                    @if (auth('web')->check())
                                    <div class="table-suppliers-cell table-suppliers-cell-action">
                                            <div class="seller-wrapper">
                                                <a  href="{{ $price->downloadLink() }}" class="js-btn-add-to-cart">دانلود</a>
                                            </div>
                                    </div>


                                    @else
                                    <div class="table-suppliers-cell table-suppliers-cell-action">
                                        <a href="{{ route('login', ['redirect' => route('front.products.show', ['product' => $product])]) }}" class="btn btn-success">
                                            ورود به حساب کاربری و دانلود
                                        </a>
                                    </div>
                                    @endif

                                @else
                                <div class="table-suppliers-cell table-suppliers-cell-action" style='padding: 15px 0;'>
                                    <button data-price_id="{{ $price->id }}" data-action="{{ route('front.cart.store', ['product' => $product]) }}" data-product="{{ $product->slug }}" type="button" class="c-wallet__header-card-btn--deposit js-trigger-wallet-modal add-to-cart color-white font-size-16 mt-2">
                                        افزودن به سبد خرید
                                    </button>
                            </div>
                                @endif

                        </div>
                    @endforeach
                        @if(count($product->prices()->whereHas('file')->orderBy('prices.ordering')->get()) > 2)
                            <div class="table-suppliers-more">
                                <a class="link-border">مشاهده
                                    <span class="show-more more-suppliers-count">( فایل های بیشتـــــر)
                                </span>
                                    <span class="show-less">کمتر</span>
                                </a>
                            </div>
                        @endif

                </div>
            </div>
        </div>

        <div class="col-lg-12 col-md-12 col-xs-12 pull-right p-0">
            <div class="row">
                @if(count($related_products))
                    <div class="col-12">
                        <div class="widget widget-product card">
                            <header class="card-header">
                                <span class="title-one">محصولات مرتبط</span>
                            </header>
                            <div class="product-carousel owl-carousel owl-theme owl-rtl owl-loaded owl-drag">
                                <div class="owl-stage-outer">
                                    <div class="owl-stage"
                                         style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                        @php $i=1; @endphp
                                        @foreach ($related_products as $related_product)
                                            @include('front::partials.product-block', ['product' => $related_product])
                                            @php $i++; @endphp
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-tabs" style="transform: none;">
            <div class="col-lg-12 col-md-12 col-xs-12 pull-right p-0 res-w" style="transform: none;">
                <div class="box-tabs-main">
                    <ul class="box-tabs">
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
                                <article>
                                    <h2 class="params-headline">
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
                                {{--  <section class="content-expert-stats row">
                                      <div class="col-8 pull-right">
                                          <div class="content-expert-stats-left">
                                              <div class="content-expert-evaluation">
                                                  <div class="col-lg-5 col-md-5 col-xs-12 pull-right" style="padding:0;">
                                                      <div class="content-expert-evaluation-positive">
                                                          <span>نقاط قوت</span>
                                                          <ul>
                                                              <li>صفحه نمایش AMOLED </li>
                                                              <li>طراحی چشم‎نواز قاب پشتی</li>
                                                              <li>عملکرد مطلوب تراشه Exynos 9610 </li>
                                                              <li>طول عمر بالای باتری</li>
                                                          </ul>
                                                      </div>
                                                  </div>
                                                  <div class="col-lg-5 col-md-5 col-xs-12 pull-right" style="padding:0;">
                                                      <div class="content-expert-evaluation-negative">
                                                          <span>نقاط ضعف</span>
                                                          <ul>
                                                              <li>مقاوم نبودن در برابر آب</li>
                                                              <li>عملکرد نه چندان خوب دوربین در شب</li>
                                                          </ul>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </section>--}}
                            </div>
                        @endif
                        @if ($product->specificationGroups()->count())
                            <div class="tab params"
                                 style="@if (!$product->description)display:block; @else display:none;  @endif">
                                <article>
                                    <h2 class="params-headline">
                                        مشخصات فنی
                                        <span>{{$product->title}}</span>
                                    </h2>
                                    @foreach($product->specificationGroups->unique() as $group)
                                        <section>
                                            <h3 class="params-title">{{ $group->name }}</h3>
                                            <ul class="params-list">
                                                @foreach($product->specifications()->where('specification_group_id', $group->id)->get() as $specification)
                                                    <li>
                                                        <div class="col-lg-3 col-md-3 col-xs-12 pull-right"
                                                             style="padding:0;">
                                                            <div class="params-list-key">
                                                                            <span
                                                                                class="block">{{ $specification->name }}</span>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-9 col-md-9 col-xs-12 pull-left"
                                                             style="padding:0;">
                                                            <div class="params-list-value">
                                                                            <span
                                                                                class="block">{!! nl2br(htmlentities($specification->pivot->value)) !!}</span>
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
                            <h2 class="comments-headline">امتیاز کاربران به:
                                <span>
                                        <span>{{$product->title}}</span>
                                    </span>
                            </h2>
                            <div class="comments-summary">
                                <div class="col-lg-4 col-md-4 col-xs-12 pull-right sticky-sidebar">
                                    <div class="comments-summary-box">
                                        <div class="comments-side-rating-container">
                                            <div class="comments-side-rating">
                                                @if ($reviews->count())
                                                    <div
                                                        class="comments-side-rating-main">{{ $product->rating }}</div>
                                                    <div class="comments-side-rating-desc">از ۵</div>
                                                @else
                                                    <div class="comments-side-rating-desc">هنوز امتیازی ثبت
                                                        نشده است
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="comments-side-rating-bottom">
                                                @if ($reviews->count())
                                                    <div class="product-star mb-3">
                                                        @for($i=1;$i<=5;$i++)
                                                            <i class="fa fa-star @if($i<=$product->rating)active @endif"></i>
                                                        @endfor
                                                    </div>
                                                    <div class="comments-side-rating-all">
                                                        از مجموع {{ $product->reviews_count }} امتیاز
                                                    </div>
                                                @else
                                                    <div class="product-star mb-3">
                                                        @for($i=1;$i<=5;$i++)
                                                            <i class="fa fa-star"></i>
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

                                    <div class="comments-summary-note">
                                        <span>شما هم درباره این کالا دیدگاه ثبت کنید</span>
                                        @if (auth('web')->check())
                                            <button data-toggle="modal"
                                                    data-target="#add-product-review-modal"
                                                    class="dk-btn dk-btn-info">
                                                <i class="fa fa-comments sign-in"></i>
                                                افزودن نظر جدید
                                            </button>
                                        @else
                                            <a href="{{ route('login', ['redirect', route('front.products.show', ['product' => $product])]) }}"
                                               class="parent-btn">
                                                <button class="dk-btn dk-btn-info">
                                                    افزودن نظر جدید
                                                    <i class="fa fa-comments sign-in"></i>
                                                </button>
                                            </a>

                                        @endif

                                        {{-- <div class="comments-dc-touch">
                                             <div class="comments-dc-touch-title">۵ امتیاز دیجی‌کلاب</div>
                                             <div class="comments-dc-touch-desc">با بیان دیدگاه برای این کالا دریافت
                                                 کنید</div>
                                         </div>--}}
                                    </div>
                                </div>
                                <div class="col-lg-8 col-md-8 col-xs-12 pull-left">

                                    @if ($reviews->count())
                                        <div class="comments-filter">
                                            <div class="filter-item-main">
                                                <ul class="filter-items nav nav-tabs" id="myTab"
                                                    role="tablist">
                                                    <li>
                                                        <span class="sort-row-text"><i class="mdi mdi-sort"></i>
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
@if ($show_prices_chart)
    <!-- Modal -->
    <div class="modal fade" id="price-changes-modal" tabindex="-1" aria-labelledby="price-changes-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="price-changes-modal-label">نمودار قیمت فروش</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
@if (auth('web')->check())
    @include('front::products.partials.add-review')
@endif
@if (option('show_product_share_links', 1) == 1)
    <!-- Modal -->
    <div class="modal fade" id="shareproduct" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">اشتراک گذاری</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">



                    <div><p>با استفاده از روش‌های زیر می‌توانید این صفحه را با دوستان خود به اشتراک بگذارید.</p></div>
                    <ul class="share-product">

                        <a target="_blank" class="telegram" href="https://t.me/share/url?url={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                            <li  class="custom-mdi mdi mdi-telegram"></li>
                        </a>

                        <a target="_blank" class="whatsapp" href="https://api.whatsapp.com/send?text={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                            <li  class="custom-mdi mdi mdi-whatsapp"></li>
                        </a>
                        <a target="_blank" class="twiiter" href="https://twitter.com/intent/tweet?url={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                            <li  class="custom-mdi mdi mdi-twitter"></li>
                        </a>
                        <a target="_blank" class="linkedin" href="https://www.linkedin.com/sharing/share-offsite/?url= {{route('front.products.shortLink', ['id' => $product->id]) }}">
                            <li  class="custom-mdi mdi mdi-linkedin"></li>
                        </a>
                    </ul>
                    <hr>
                    <div class="filed-link dir-ltr copy-text">

                        <input id="shareLink" type="text" disabled value="{{ route('front.products.shortLink', ['id' => $product->id]) }}" readonly="">

                        <div class="copy-text-btn" data-toggle="tooltip" data-placement="right" title="" data-original-title="کپی لینک">
                            <i class="mdi mdi-content-copy"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endif
@endsection

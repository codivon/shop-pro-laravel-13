@extends('front::layouts.master', ['title' => $product->meta_title ?: $product->title])

@push('meta')
    @include('front::products.partials.product-meta')
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/vendor/fancybox.min.css') }}">
    <style>
        .type-container ul.type-list {
            list-style: none
        }

        .type-container ul.type-list li {
            display: inline-flex;
            align-items: center;
            margin-bottom: 8px;
            margin-left: 8px;
            background-color: #eff3f8;
            border: 2px solid #ffffff;
            padding: 6px 10px;
            border-radius: 10px;
            letter-spacing: -.2px;
            word-spacing: -1px;
            color: #1c1c25;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none
        }

        .type-container ul.type-list li.selected {
            position: relative;
            outline: 2px solid #3ecaf6;
            padding-right: 30px
        }

        .type-container ul.type-list li.selected:after {
            content: "";
            font-family: remixicon;
            position: absolute;
            right: 5px;
            color: #3ecaf6;
            font-size: 20px
        }

        .color-container {
            padding-right: 5px
        }
        .color-container .color-container-title {
            font-weight: 700;
            font-size: 13px;
            color: #1c1c25;
            margin-bottom: 8px
        }

        .color-container ul.color-list {
            list-style: none
        }

        .color-container ul.color-list li {
            display: inline-block;
            margin-left: 10px;
            width: 30px;
            height: 30px;
            border-radius: 50px;
            cursor: pointer;
            transition: all .2s ease-in-out;
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
            vertical-align: middle;
            margin-bottom: 10px;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none
        }

        .color-container ul.color-list li.selected {
            transform: scale(1.15);
            outline: 2px solid #3ecaf6
        }

        .color-container ul.color-list li.selected:after {
            content: "";
            font-family: remixicon;
            position: absolute;
            color: #fff;
            font-size: 20px
        }

        .color-container ul.color-list li input {
            display: none
        }

        .color-container ul.color-list .is-white {
            border: 2px solid #ebf1f6
        }

        .color-container ul.color-list .is-white.selected:after {
            content: "";
            color: #8f9bad
        }

        .extra-options-container {
            padding: 0 5px
        }

        .extra-options-container ul.type-list {
            list-style: none
        }

        .extra-options-container ul.type-list li {
            display: inline-flex;
            align-items: center
        }

        .extra-options-container ul.type-list li div.option-item {
            display: inline-flex;
            align-items: center;
            margin-bottom: 8px;
            margin-left: 8px;
            background-color: #eff3f8;
            padding: 8px;
            border-radius: 10px;
            letter-spacing: -.2px;
            word-spacing: -1px;
            color: #1c1c25;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none
        }

        .extra-options-container ul.type-list li div.option-item .selectbox {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background-color: #fff;
            border-radius: 5px;
            margin-left: 8px;
            transition: ease .1s all
        }

        .extra-options-container ul.type-list li div.option-item .selectbox i {
            visibility: hidden;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            line-height: 10px
        }

        .extra-options-container ul.type-list li div.option-item.selected .selectbox {
            background-color: #1abc9c
        }

        .extra-options-container ul.type-list li div.option-item.selected .selectbox i {
            visibility: visible
        }
        .type-container ul.type-list li{
            position: relative;
        }
        .type-container .product-attribute:has(input[type=radio]:checked){
            outline: 2px solid #3ecaf6;
        }
        .product-actions .seller-container {
            width: 100%;
            padding: 17px 11px 0 0;
            background-color: var(--main-background);
            border-radius: 20px
        }

        .product-actions .seller-container .seller-avatar {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border: 1px solid #ebf1f6;
            background-color: #fff;
            border-radius: 12px
        }
        .product-actions .bg-success {
            background-color: #13deb91a !important;
            color: #13deb9 !important;
        }
        .product-actions .seller-container .seller-avatar i.shop {
            font-size: 23px;
            margin: 0;
            padding: 0
        }

        .product-actions .seller-container .seller-avatar img {
            width: 100%;
        }

        .product-actions .seller-container .seller-container-title {
            font-weight: 700;
            font-size: 15px;
            color: #1c1c25
        }

        .product-actions .seller-container ul {
            list-style: none
        }

        .product-actions .seller-container ul li {
            display: flex;
            align-items: center;
            border-bottom: dashed 1px #e5e9ec;
            padding: 12px 0
        }

        .product-actions .seller-container ul li:last-child {
            border: none;
            padding-bottom: 0
        }

        .product-actions .seller-container ul li i {
            color: #1c1c25;
            font-size: 16px;
            margin-left: 7px
        }

        .product-actions .seller-container ul li .table-name {
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            color: #1c1c25
        }

        .product-actions .seller-container ul li .table-name .verify {
            font-size: 11px;
            margin-right: 2px;
            color: #0097e6
        }

        .product-actions .seller-container ul li a.table-name {
            transition: all .2s ease-in-out
        }

        .product-actions .seller-container ul li a.table-name:hover {
            color: #3ecaf6
        }

        .product-actions .seller-container ul li span.divider {
            margin-left: 8px;
            margin-right: 8px
        }

        .product-actions .seller-container ul li span.divider:before {
            background-color: #cdd4dc;
            border-radius: 50%;
            content: "";
            display: inline-block;
            height: 4px;
            width: 4px
        }

        .product-actions .seller-container ul li span.table-flag {
            font-size: 14px;
            font-weight: 400;
            color: #b7b7bc
        }

        .product-actions .seller-container ul li span.table-flag.green {
            color: #16a085;
            font-weight: 700
        }
        .text-success-dark {
            color: #16a085 !important;
        }
        .fw-bold {
            font-weight: 700 !important;
        }
        .p-tabs{
            background-color: unset;
        }
        .p-tabs .box-tabs-main{
            border-radius: 4px 4px 0 0;
            overflow: hidden;
        }
        .p-tabs .box-tabs{
            padding: 0 5px;
        }
    </style>
@endpush

@section('content')

    @if($product->isPhysical())

        <!--single-product------------------------->
        <div class="col-12">
            <div class="product-page" >
                <article class="js-product mt-0">
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

                    <div class="col-lg-4 col-md-12 col-xs-12 pull-right">
                        @if(!$product->addableToCart())

                            <div class="product-timeout position-relative pt-5 mb-4">
                                <div class="promotion-badge not-available text-center">
                                    نا موجود
                                </div>
                            </div>
                        @elseif($product->isSpecial())
                            <div class="product-timeout position-relative pt-5 mb-4 text-center">
                                <div class="promotion-badge ">
                                    <div class="product-special">
                                        فروش ویژه
                                    </div>
                                </div>
                                @if ($product->special_end_date)
                                    <div id="product-special-end-date" class="countdown-timer mt-4 " countdown data-date="{{ $product->special_end_date->format('D M d Y H:i:s O') }}">
                                        <span data-days="">0</span>:
                                        <span data-hours="">0</span>:
                                        <span data-minutes="">0</span>:
                                        <span data-seconds="">0</span>
                                    </div>
                                @endif
                            </div>
                        @endif


                        <div class="product-gallery">
                            <img class="zoom-img" id="img-product-zoom" src="{{$product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                                 data-zoom-image="{{$product->image ? asset($product->image) : asset('/no-image-product.svg') }}" width="411"
                                 alt="img-slider"/>
                            @if($product->gallery()->count())
                                <div id="gallery_01f" style="width:420px;float:right;">
                                    <ul class="gallery-items owl-carousel owl-theme" id="gallery-slider">
                                        @foreach ($product->gallery()->orderBy('ordering')->get() as $item)
                                            <li class="item">
                                                <a class="elevatezoom-gallery active" data-update=""
                                                   data-image="{{$item->image ? asset($item->image) : asset('/no-image-product.svg')}}"
                                                   data-zoom-image="{{$item->image ? asset($item->image) : asset('/no-image-product.svg')}}">
                                                    <img src="{{$item->image ? asset($item->image) : asset('/no-image-product.svg')}}" width="100"
                                                         alt="img-slider"/></a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

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
                    @include('front::products.partials.product-info')


                    <!-- Modal social-->
                    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog"
                         aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalCenterTitle">اشتراک گذاری در شبکه های اجتماعی
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="form-share-row">
                                    <div class="form-share-col">
                                        <ul class="btn-group-share">
                                            <li>
                                                <a class="btn-share btn-share-twitter">
                                                    <i class="fa fa-twitter"></i>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="btn-share btn-share-facebook">
                                                    <i class="fa fa-facebook"></i>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="btn-share btn-share-whatsapp">
                                                    <i class="fa fa-whatsapp"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="form-share-title">ارسال به ایمیل</div>
                                <form for="#" class="send-to-email">
                                    <div class="form-share-row">
                                        <div class="form-share-col">
                                            <input name="email" class="input-send-to-email" type="email"
                                                   placeholder="آدرس ایمیل را وارد نمایید.">
                                        </div>
                                    </div>
                                    <div class="form-share-row">
                                        <div class="form-share-col">
                                            <div class="btn-send-email">ارسال</div>
                                        </div>
                                    </div>
                                </form>
                                <div class="form-share-row">
                                    <div class="form-share-col">
                                        <input class="ui-url-field" type="url"
                                               value="https://www.digikala.com/product/dkp-1672478" readonly="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal social-->
                    <!-- Modal alarm -->
                    <div class="modal fade" id="exampleModalCenteralarm" tabindex="-1" role="dialog"
                         aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalCenterTitle">
                                        به من اطلاع بده
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="#" class="form-notification">
                                        <div class="form-notification-title">اطلاع به من در زمان:</div>
                                        <div class="form-notification-row">
                                            <div class="form-notification-col">
                                                <div class="form-notification-status">
                                                    پیشنهاد شگفت‌انگیز
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-notification-title">از طریق:</div>
                                        <div class="form-notification-row">
                                            <div class="form-notification-col">
                                                <ul class="form-notification-params">
                                                    <li>
                                                        <div class="form-auth-row">
                                                            <label class="ui-checkbox">
                                                                <input type="checkbox" value="1" name="login"
                                                                       id="remember1">
                                                                <span class="ui-checkbox-check"></span>
                                                            </label>
                                                            <label for="remember1" class="remember-me">ایمیل به
                                                                <span class="js-observed-user-email">09911234567</span>
                                                            </label>
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <div class="form-auth-row">
                                                            <label class="ui-checkbox">
                                                                <input type="checkbox" value="1" name="login"
                                                                       id="remember2">
                                                                <span class="ui-checkbox-check"></span>
                                                            </label>
                                                            <label for="remember2" class="remember-me">پیامک به
                                                                <span class="js-observed-user-email">09911234567</span>
                                                            </label>
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <div class="form-auth-row">
                                                            <label class="ui-checkbox">
                                                                <input type="checkbox" value="1" name="login"
                                                                       id="remember3">
                                                                <span class="ui-checkbox-check"></span>
                                                            </label>
                                                            <label for="remember3" class="remember-me">سیستم پیام شخصی
                                                                دیجی‌کالا </label>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer d-block text-right">
                                    <button type="button" class="btn btn-primary ml-2">ثبت</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">بازگشت</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal alarm -->


                </article>

                @if(option('multi_vendor_system_status','false')=="true")
                <div class="box-suppliers sellers-box ">
                    <div class="box-suppliers-headline-container">
                        <div class="headline-delivery">
                            <span>لیست فروشندگان این کالا</span>
                        </div>
                        <a href="{{route('seller.index')}}" target="_blank" class="link-border" style="float:left;">کالای خود را در {{ option('info_site_title', 'او پی شاپ') }} بفروشید</a>
                    </div>

                    <div class="table-suppliers">
                        <div class="table-suppliers-body">
                            <div class="table-suppliers-store">

                            </div>

                            <span id="get-stores" class="d-none" data-action="{{route('front.products.get_stores')}}"

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
            </div>
                @endif

                <div class="col-lg-12 col-md-12 col-xs-12 pull-right ">
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
                        <div class="p-tabs p-0">
                            <div class="col-lg-12 col-md-12 col-xs-12 pull-right res-w">
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
                                                    @php
                                                        // FIX مقیاس: مشخصات یک‌بار لود می‌شوند (قبلاً به‌ازای هر گروه یک کوئری = N+1)
                                                        $product_specifications = $product->specifications;
                                                    @endphp
                                                    @foreach($product->specificationGroups->unique() as $group)
                                                        <section>
                                                            <h3 class="params-title">{{ $group->name }}</h3>
                                                            <ul class="params-list">
                                                                @foreach($product_specifications->where('pivot.specification_group_id', $group->id) as $specification)
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
                           {{-- @include('front::products.partials.product-info-sidebar')--}}
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


    @if(!$product->addableToCart())
        <!-- Start Modal stocknotify -->
        <div class="modal fade" id="modal-stock-notify" role="dialog"
             aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-md send-info modal-dialog-centered"
                 role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalCenterTitle">
                            <i class="now-ui-icons location_pin"></i>
                            {{ trans('front::messages.products.inventory-information') }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
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
                                                    <input class="input-ui pr-2 text-right"
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
                                                        class="input-ui pl-2 dir-ltr text-left"
                                                        type="text"
                                                        name="mobile"
                                                        id="stock-mobile"
                                                        placeholder="09xxxxxxxxx" required>
                                                </div>
                                            </div>

                                            <div class="col-12 pr-4 pl-4 text-center">
                                                <button id="sendStockNotifyBtn" type="button" class="btn btn-md btn-primary btn-submit-form" data-dismiss="modal">{{ trans('front::messages.products.let-me-know') }}</button>
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

@endsection

@push('scripts-top-js')
 <script src="{{theme_asset('js/jquery.ez-plus.js')}}"></script>
@endpush

@push('scripts')
    <script>
        var multi_vendor_system_status='{{option('multi_vendor_system_status','false')}}'
    </script>

     <script src="{{theme_asset('js/theia-sticky-sidebar.min.js')}}"></script>
     <script src="{{ theme_asset('js/vendor/jquery.fancybox.min.js') }}"></script>
     <script src="{{ theme_asset('js/plugins/apexcharts/apexcharts.js') }}"></script>
   <script src="{{ theme_asset('js/pages/products/show.js') }}?v=13"></script>
   <script src="{{ theme_asset('js/pages/comments.js') }}"></script>
@endpush

@extends('front::layouts.master', ['title' => $product->meta_title ?: $product->title])

@push('meta')
    @include('front::products.partials.product-meta', ['product' => $product])
@endpush

@push('befor-styles')
    <link rel="stylesheet" href="{{ theme_asset('css/product.css') }}">
@endpush

@section('content')
    {{-- بردکرامب سانیار (داینامیک از زنجیره دسته) --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('front.products.index') }}" class="font-14 text-muted">فروشگاه</a></li>
                            @if ($product->category)
                                @foreach ($product->category->parents() as $parent)
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('front.products.category', ['category' => $parent]) }}" class="font-14 text-muted">{{ $parent->title }}</a>
                                    </li>
                                @endforeach
                                <li class="breadcrumb-item">
                                    <a href="{{ route('front.products.category', ['category' => $product->category]) }}" class="font-14 text-muted">{{ $product->category->title }}</a>
                                </li>
                            @endif
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">
                                {{ \Illuminate\Support\Str::limit($product->title, 50) }}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== بخش اصلی محصول ===== --}}
    <div class="product-meta py-20">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="row gy-3">

                        {{-- گالری تصاویر (داینامیک: تصویر اصلی + گالری) --}}
                        <div class="col-lg-4">
                            <div class="pro_gallery">
                                {{-- آیکون‌های کنار گالری (داینامیک با تنظیمات/محصول) --}}
                                <div class="icon-product-box">
                                    @if (option('show_product_share_links', 1) == 1)
                                        <div class="icon-product-box-item" data-bs-toggle="modal" data-bs-target="#shareModal"
                                             data-bs-placement="right" title="اشتراک گذاری">
                                            <i class="bi bi-share"></i>
                                        </div>
                                    @endif
                                    <div class="icon-product-box-item" data-bs-toggle="tooltip" data-bs-placement="right"
                                         data-bs-title="افزودن به علاقه مندی" id="add-to-favorites"
                                         data-product="{{ $product->id }}"
                                         data-action="{{ route('front.favorites.store') }}"
                                         role="button" tabindex="0" aria-label="افزودن به علاقه‌مندی">
                                        <i class="bi {{ $favorite ? 'bi-heart-fill main-color-one-color' : 'bi-heart' }}"></i>
                                    </div>
                                    @if ($similar_products_count)
                                        <a href="{{ route('front.products.compare', ['product1' => $product->id]) }}"
                                           class="icon-product-box-item text-decoration-none" data-bs-toggle="tooltip"
                                           data-bs-placement="right" data-bs-title="مقایسه محصول">
                                            <i class="bi bi-arrow-left-right"></i>
                                        </a>
                                    @endif
                                    @if ($show_prices_chart && $product->prices()->count())
                                        <div class="icon-product-box-item" data-bs-toggle="modal" data-bs-target="#chartModal"
                                             data-bs-placement="right" title="نمودار تغییر قیمت">
                                            <i class="bi bi-bar-chart"></i>
                                        </div>
                                    @endif
                                </div>

                                {{-- اسلایدر اصلی گالری (سوایپر با زوم) --}}
                                <div class="swiper product-gallery">
                                    <div class="swiper-wrapper" title="برای بزرگنمایی تصویر دابل کلیک کنید">
                                        <div class="swiper-slide">
                                            <div class="swiper-zoom-container">
                                                <img alt="{{ $product->title }}" class="img-fluid js-gallery-main"
                                                     src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}" />
                                            </div>
                                        </div>
                                        @foreach ($product->gallery()->orderBy('ordering')->get() as $item)
                                            <div class="swiper-slide">
                                                <div class="swiper-zoom-container">
                                                    <img alt="{{ $product->title . ' - تصویر ' . $loop->iteration }}" class="img-fluid"
                                                         src="{{ $item->image ? asset($item->image) : asset('no-image-product.svg') }}" />
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="swiper-button-next d-none d-lg-flex"></div>
                                    <div class="swiper-button-prev d-none d-lg-flex"></div>
                                    <div class="swiper-pagination d-none d-lg-block"></div>
                                </div>

                                {{-- تصاویر بندانگشتی گالری (فقط با بیش از ۱ تصویر) --}}
                                @if ($product->gallery()->count() >= 1)
                                    <div thumbsSlider class="swiper product-gallery-thumb">
                                        <div class="swiper-wrapper">
                                            <div class="swiper-slide">
                                                <img class="img-fluid" alt="{{ $product->title }}"
                                                     src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}">
                                            </div>
                                            @foreach ($product->gallery()->orderBy('ordering')->get() as $item)
                                                <div class="swiper-slide">
                                                    <img class="img-fluid" alt="{{ $product->title . ' - تصویر ' . $loop->iteration }}"
                                                         src="{{ $item->image ? asset($item->image) : asset('no-image-product.svg') }}">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- بلاک اطلاعات محصول (AJAX-reload با تغییر واریانت) --}}
                        @include('front::products.partials.product-info', [
                            'product' => $product,
                            'selected_price' => $selected_price,
                            'attributeGroups' => $attributeGroups,
                            'get_stores' => $get_stores,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== فروشندگان این کالا (چندفروشندگی — فقط محصولات فیزیکی، داینامیک) ===== --}}
    @if (option('multi_vendor_system_status', 'false') == "true" && $product->isPhysical())
        <div class="multi-seller {{ empty($get_stores['has_stores']) ? 'd-none' : '' }}" id="saniar-stores">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid">
                        <div class="title-panel d-flex align-items-center justify-content-between flex-wrap mb-3">
                            <h6 class="font-16 mb-0">
                                <i class="bi bi-shop main-color-one-color me-1"></i>
                                لیست فروشندگان این کالا
                                (<span class="js-seller-count count_stores">{{ $initialCountStores > 1 ? $initialCountStores : 1 }}</span> فروشنده)
                            </h6>
                            <a href="{{ route('seller.index') }}" class="font-13 main-color-one-color text-decoration-none">
                                کالای خود را در {{ option('info_site_title', config('front.demo.name')) }} بفروشید
                            </a>
                        </div>
                        <div class="table-suppliers-store saniar-stores-body">
                            @if (!empty($get_stores) && !empty($get_stores['attribute_prices']))
                                @include('front::products.partials.stores-template', $get_stores)
                            @endif
                        </div>

                        {{-- قرارداد JS فروشندگان --}}
                        <span id="get-stores" class="d-none" data-action="{{ route('front.products.get_stores') }}"></span>
                        <span id="product-stores-info" class="d-none"
                              data-initial-stores="{{ (int) $initialCountStores }}"
                              data-first-color-id="{{ $firstColorId }}"
                              data-product-id="{{ $product->id }}"></span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== فایل‌های محصول دانلودی (داینامیک) ===== --}}
    @if ($product->isDownload())
        @include('front::products.partials.links-tab', ['product' => $product])
    @endif

    {{-- ===== توضیحات / مشخصات / دیدگاه‌ها / پرسش‌ها ===== --}}
    <div class="product-desc py-20">
        <div class="container-fluid">
            <div class="row gy-3">
                <div class="col-lg-9">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="product-descs" id="prodesc">
                                <div class="product-desc">
                                    {{-- نوار تب‌ها (داینامیک: تب‌های خالی حذف می‌شوند) --}}
                                    <ul class="nav" id="productTab" role="tablist">
                                        @if ($product->description || $product->short_description)
                                            <li class="nav-item" role="presentation">
                                                <button class="active waves-effect waves-light" id="productDescTab" type="button" role="tab"
                                                        data-bs-toggle="tab" data-bs-target="#productDescLess-pane" aria-selected="true">
                                                    معرفی محصول
                                                </button>
                                            </li>
                                        @endif
                                        @if ($product->specificationGroups()->count())
                                            <li class="nav-item" role="presentation">
                                                <button class="waves-effect waves-light {{ !($product->description || $product->short_description) ? 'active' : '' }}" id="productTableTab" type="button" role="tab"
                                                        data-bs-toggle="tab" data-bs-target="#productTable-pane" aria-selected="false">
                                                    مشخصات فنی
                                                </button>
                                            </li>
                                        @endif
                                        <li class="nav-item" role="presentation">
                                            <button class="waves-effect waves-light {{ !($product->description || $product->short_description) && !$product->specificationGroups()->count() ? 'active' : '' }}" id="productCommentTab" type="button" role="tab"
                                                    data-bs-toggle="tab" data-bs-target="#productComment-pane" aria-selected="false">
                                                دیدگاه‌ها ({{ $product->reviews_count }})
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="waves-effect waves-light" id="productQuestionTab" type="button" role="tab"
                                                    data-bs-toggle="tab" data-bs-target="#productQuestion-pane" aria-selected="false">
                                                پرسش و پاسخ ({{ $questions->total() }})
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="product-desc-tab-content">
                                        <div class="tab-content" id="productTabContent">

                                            {{-- تب معرفی (داینامیک) --}}
                                            @if ($product->description || $product->short_description)
                                                <div class="tab-pane fade show {{ ($product->description || $product->short_description) ? 'active' : '' }}" id="productDescLess-pane" role="tabpanel" aria-labelledby="productDescTab">
                                                    <div class="product-desc-content">
                                                        <input type="checkbox" class="read-more-state d-none" id="readMoreDesc">
                                                        <div class="read-more-wrap">
                                                            <h6 class="font-20 mb-3">{{ $product->title }}</h6>
                                                            <div class="saniar-product-description font-14 text-muted line-height-2">
                                                                {!! $product->description ?: nl2br(strip_tags($product->short_description)) !!}
                                                            </div>
                                                        </div>
                                                        <label for="readMoreDesc" class="read-more-trigger main-color-one-color font-14 fw-bold pointer"></label>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- تب مشخصات فنی (داینامیک از گروه‌های مشخصات) --}}
                                            @if ($product->specificationGroups()->count())
                                                <div class="tab-pane fade {{ !($product->description || $product->short_description) ? 'show active' : '' }}" id="productTable-pane" role="tabpanel" aria-labelledby="productTableTab">
                                                    <div class="tab-pane fade active show">
                                                        <h6 class="font-20 mb-3">مشخصات فنی {{ $product->title }}</h6>
                                                        @php
                                                            $product_specifications = $product->specifications;
                                                        @endphp
                                                        @foreach($product->specificationGroups->unique() as $group)
                                                            <div class="saniar-spec-group mb-3">
                                                                <h6 class="font-16 main-color-one-color mb-2">
                                                                    <i class="bi bi-caret-left-fill"></i>{{ $group->name }}
                                                                </h6>
                                                                <ul class="list-unstyled mb-0">
                                                                    @foreach($product_specifications->where('pivot.specification_group_id', $group->id) as $specification)
                                                                        <li class="d-flex border-bottom py-2 font-14">
                                                                            <span class="text-muted" style="flex:0 0 220px;">{{ $specification->name }}</span>
                                                                            <strong class="font-14">{!! nl2br(htmlentities($specification->pivot->value)) !!}</strong>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- تب دیدگاه‌ها (داینامیک + AJAX مرتب‌سازی) --}}
                                            <div class="tab-pane fade product-comment-content {{ !($product->description || $product->short_description) && !$product->specificationGroups()->count() ? 'show active' : '' }}"
                                                 id="productComment-pane" role="tabpanel" aria-labelledby="productCommentTab">
                                                <div class="comments-summary mb-4">
                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-lg-4 text-center">
                                                            <div class="saniar-rating-big">
                                                                <div class="fw-bold font-40 main-color-one-color">{{ $product->rating ?: '0' }}</div>
                                                                <div class="text-muted font-12 mb-1">از ۵</div>
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    @for ($i = 1; $i <= 5; $i++)
                                                                        <i class="bi {{ $i <= round($product->rating) ? 'bi-star-fill text-warning' : 'bi-star text-muted' }}"></i>
                                                                    @endfor
                                                                </div>
                                                                <div class="text-muted font-12 mt-1">{{ $product->reviews_count }} دیدگاه</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-8">
                                                            {{-- توزیع امتیازها (داینامیک) --}}
                                                            @php
                                                                $saniar_rating_dist = $product->reviews()->accepted()
                                                                    ->selectRaw('rating, count(*) as total')
                                                                    ->groupBy('rating')
                                                                    ->pluck('total', 'rating');
                                                                $saniar_rating_total = max(1, $product->reviews_count);
                                                            @endphp
                                                            @for ($star = 5; $star >= 1; $star--)
                                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                                    <span class="font-12 text-muted" style="flex:0 0 40px;">{{ $star }} ستاره</span>
                                                                    <div class="progress w-100" style="height:8px;" role="progressbar"
                                                                         aria-label="{{ $star }} ستاره"
                                                                         aria-valuenow="{{ round(($saniar_rating_dist[$star] ?? 0) / $saniar_rating_total * 100) }}"
                                                                         aria-valuemin="0" aria-valuemax="100">
                                                                        <div class="progress-bar bg-warning" style="width: {{ round(($saniar_rating_dist[$star] ?? 0) / $saniar_rating_total * 100) }}%"></div>
                                                                    </div>
                                                                    <span class="font-12 text-muted" style="flex:0 0 30px;">{{ $saniar_rating_dist[$star] ?? 0 }}</span>
                                                                </div>
                                                            @endfor

                                                            {{-- ثبت دیدگاه --}}
                                                            <div class="mt-3">
                                                                @if (auth('web')->check())
                                                                    <button type="button" class="btn main-color-one-bg text-white rounded-3 px-4"
                                                                            data-bs-toggle="modal" data-bs-target="#add-product-review-modal">
                                                                        <i class="bi bi-pencil-square me-1"></i>ثبت دیدگاه
                                                                    </button>
                                                                @else
                                                                    <a href="{{ route('login', ['redirect' => url()->current()]) }}"
                                                                       class="btn main-color-one-bg text-white rounded-3 px-4">
                                                                        <i class="bi bi-box-arrow-in-left me-1"></i>برای ثبت دیدگاه وارد شوید
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- فیلتر دیدگاه‌ها (AJAX) --}}
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-3 js-comment-filter">
                                                    <input type="hidden" name="UrlGetComment" value="{{ route('front.products.getComments') }}">
                                                    <input type="hidden" name="poduct_id" value="{{ $product->id }}">
                                                    <input type="hidden" name="sortComment" value="new">
                                                    <span class="font-14 text-muted">ترتیب نمایش:</span>
                                                    <button class="btn btn-sm btn-light rounded-3 font-12 js-comment-sort-btn active" data-sort="new">جدیدترین</button>
                                                    <button class="btn btn-sm btn-light rounded-3 font-12 js-comment-sort-btn" data-sort="moreLike">مفیدترین</button>
                                                    <button class="btn btn-sm btn-light rounded-3 font-12 js-comment-sort-btn" data-sort="Buyerscomments">خریداران</button>
                                                </div>

                                                {{-- لیست دیدگاه‌ها (اولیه سمت سرور، بعدی‌ها AJAX) --}}
                                                <div class="saniar-comments-wrapper">
                                                    @include('front::products.partials.reviews-comments', ['reviews' => $reviews, 'Buyerscomments' => $Buyerscomments])
                                                </div>
                                            </div>

                                            {{-- تب پرسش و پاسخ (داینامیک + فرم پرسش) --}}
                                            <div class="tab-pane fade" id="productQuestion-pane" role="tabpanel" aria-labelledby="productQuestionTab">
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                                                    <input type="hidden" name="UrlGetQuestions" value="{{ route('front.products.getQuestions') }}">
                                                    <input type="hidden" name="poduct_id" value="{{ $product->id }}">
                                                    <input type="hidden" name="sortQuestions" value="new">
                                                    <span class="font-14 text-muted">ترتیب نمایش:</span>
                                                    <button class="btn btn-sm btn-light rounded-3 font-12 js-question-sort-btn active" data-sort="new">جدیدترین</button>
                                                    <button class="btn btn-sm btn-light rounded-3 font-12 js-question-sort-btn" data-sort="moreLike">مفیدترین</button>
                                                </div>

                                                {{-- فرم ثبت پرسش (داینامیک با auth) --}}
                                                <div class="content-box mb-3">
                                                    <div class="container-fluid p-3">
                                                        @auth
                                                            <form id="product-question-form" action="{{ route('front.product.comments', ['product' => $product]) }}" method="POST">
                                                                @csrf
                                                                {{-- فاز ۴۴ میزبان — حفاظت ربات‌گیر + کپچای اختیاری --}}
                                                                {!! \App\Services\CommentProtection::hiddenFields() !!}
                                                                @if(\App\Services\SecuritySettings::captchaEnabled('comments'))
                                                                    <div class="mb-2"><x-captcha /></div>
                                                                @endif
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control" name="body" placeholder="پرسش خود را درباره این کالا بپرسید ..." required maxlength="500">
                                                                    <button type="submit" class="btn main-color-one-bg text-white rounded-0 rounded-start-3 px-4">
                                                                        <i class="bi bi-send me-1"></i>ثبت پرسش
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        @else
                                                            <p class="font-14 text-muted mb-0">
                                                                برای پرسیدن سوال
                                                                <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="main-color-one-color fw-bold">وارد شوید</a>
                                                            </p>
                                                        @endauth
                                                    </div>
                                                </div>

                                                <div class="saniar-questions-wrapper">
                                                    @include('front::components.question-answer-data', ['questions' => $questions])
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- مینی‌کارت چسبان محصول (دسکتاپ — داینامیک) --}}
                <div class="col-lg-3 d-lg-block d-none position-relative">
                    <div class="content-box top-0 position-sticky">
                        <div class="container-fluid">
                            <div class="product-float p-2">
                                <div class="product-float-title">
                                    <div class="row align-items-center gy-2">
                                        <div class="col-4">
                                            <div class="image">
                                                <img src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}"
                                                     alt="{{ $product->title }}" class="img-fluid">
                                            </div>
                                        </div>
                                        <div class="col-8">
                                            <div class="title">
                                                <h6 class="font-14 mb-1">{{ \Illuminate\Support\Str::limit($product->title, 60) }}</h6>
                                                @if ($selected_price)
                                                    <div class="font-14 fw-bold main-color-one-color">
                                                        {{ number_format($selected_price->salePrice()) }}
                                                        <span class="font-12 text-muted">تومان</span>
                                                    </div>
                                                @endif
                                                <a href="#saniar-buy-box" class="btn btn-sm border-0 main-color-one-bg text-white rounded-3 mt-2 js-scroll-to-buy">
                                                    <i class="bi bi-basket me-1"></i>خرید کالا
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== محصولات مرتبط (داینامیک، اسلایدر سانیار) ===== --}}
    @if (count($related_products))
        <div class="product-slider site-slider py-20">
            <div class="container-fluid">
                <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                    <div class="title d-flex align-items-center">
                        <span class="main-color-one-bg text-white rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <i class="bi bi-grid-fill font-20"></i>
                        </span>
                        <h5 class="font-16 ms-3 mb-0">محصولات <span class="main-color-one-color d-inline-block ms-1">مشابه</span></h5>
                    </div>
                    @if ($product->category)
                        <div class="link">
                            <a href="{{ route('front.products.category', ['category' => $product->category]) }}" class="border-animate fromCenter pb-1 fw-bold">
                                مشاهده همه
                                <i class="bi bi-chevron-double-left main-color-one-color"></i>
                            </a>
                        </div>
                    @endif
                </div>
                <div class="parent">
                    <div class="container-fluid">
                        <div class="swiper product-slider-swiper">
                            <div class="swiper-wrapper">
                                @foreach ($related_products as $related_product)
                                    <div class="swiper-slide">
                                        @include('front::products.partials.product-card', ['product' => $related_product])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== مودال‌ها ===== --}}
    @include('front::products.partials.sizes-modal', ['product' => $product])

    @if (auth('web')->check())
        @include('front::products.partials.add-review', ['product' => $product])
    @endif

    {{-- مودال اشتراک‌گذاری (داینامیک با لینک کوتاه) --}}
    @if (option('show_product_share_links', 1) == 1)
        <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content rounded-4">
                    <div class="modal-header">
                        <h5 class="modal-title font-16" id="shareModalLabel">
                            <i class="bi bi-share main-color-one-color me-1"></i>اشتراک گذاری
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <a target="_blank" rel="noopener" class="btn btn-light rounded-circle" aria-label="تلگرام"
                               href="https://t.me/share/url?url={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                <i class="bi bi-telegram text-info"></i>
                            </a>
                            <a target="_blank" rel="noopener" class="btn btn-light rounded-circle" aria-label="واتس‌اپ"
                               href="https://wa.me/?text={{ urlencode(route('front.products.shortLink', ['id' => $product->id])) }}">
                                <i class="bi bi-whatsapp text-success"></i>
                            </a>
                            <a target="_blank" rel="noopener" class="btn btn-light rounded-circle" aria-label="توییتر"
                               href="https://twitter.com/intent/tweet?url={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                <i class="bi bi-twitter-x"></i>
                            </a>
                            <a target="_blank" rel="noopener" class="btn btn-light rounded-circle" aria-label="لینکدین"
                               href="https://www.linkedin.com/sharing/share-offsite/?url={{ route('front.products.shortLink', ['id' => $product->id]) }}">
                                <i class="bi bi-linkedin text-primary"></i>
                            </a>
                        </div>
                        <div class="input-group">
                            <input id="shareLink" type="text" class="form-control font-12" dir="ltr"
                                   value="{{ route('front.products.shortLink', ['id' => $product->id]) }}" readonly>
                            <button class="btn main-color-one-bg text-white js-copy-link rounded-0 rounded-start-3" type="button">کپی</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- مودال نمودار قیمت (داینامیک: برای هر قیمت/واریانت یک دکمه) --}}
    @if ($show_prices_chart && $product->prices()->count())
        <div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content rounded-4">
                    <div class="modal-header">
                        <h5 class="modal-title font-16" id="chartModalLabel">
                            <i class="bi bi-bar-chart main-color-one-color me-1"></i>نمودار تغییر قیمت
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body chart-area">
                        <strong class="text-muted font-14">{{ $product->title }}</strong>
                        <p class="mt-1 text-muted font-13" id="selected-chart-price-title"></p>
                        <div>
                            <div id="empty-chart" class="text-center text-muted py-4" style="display:none">
                                <p>در سی روز گذشته تغییر قیمتی برای این کالا ثبت نشده است.</p>
                            </div>
                            <div id="chart" class="ltr" style="height:300px;" dir="ltr"></div>
                        </div>
                        <ul class="chart-prices-label list-unstyled d-flex flex-wrap gap-2 mt-3">
                            @foreach ($product->prices()->orderBy('stock', 'desc')->get() as $chart_price)
                                @php
                                    $label = $chart_price->getAttributesName();
                                @endphp
                                <li>
                                    <label class="btn btn-sm btn-light rounded-3 font-12"
                                           data-action="{{ route('front.products.priceChart', ['price' => $chart_price]) }}"
                                           data-title="{{ $chart_price->getAttributesName() }}"
                                           title="{{ $chart_price->getAttributesName() }}">
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

    {{-- لینک‌های دانلود داخل بخش محصول دانلودی (بالا) رندر شده است --}}

    {{-- شناسه‌های مخفی قرارداد JS --}}
    <span class="footer-product-id d-none" data-id="{{ $product->id }}"></span>
@endsection

@push('scripts')
    @if ($show_prices_chart && $product->prices()->count())
        <script src="{{ theme_asset('plugin/chartjs/chart.js') }}"></script>
    @endif
    <script src="{{ theme_asset('js/pages/products/show.js') }}"></script>
@endpush

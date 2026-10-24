{{-- صفحه فرود فروشندگان سانیار (فاز G) — کاملاً داینامیک از Slider/SellerHero/SellerCommission/SellerQuestion
    قرارداد SellerController@index : $sliders, $sellers_heroes, $sellers_commissions, $sellers_questions --}}
@extends('front::layouts.master', ['title' => 'فروش در ' . option('info_site_title', config('front.demo.name'))])

@push('meta')
    <meta name="description" content="فروش کالای خود را در {{ option('info_site_title', config('front.demo.name')) }} شروع کنید؛ بدون هزینه اجاره فروشگاه، با میلیون‌ها مشتری، پنل مدیریت فروشندگان و تسویه‌های منظم.">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=1">
@endpush

@section('content')
    {{-- ===== هیرو ===== --}}
    <div class="content py-20">
        <div class="container-fluid">
            @if(count($sliders))
                <div id="sellerLandingSlider" class="carousel slide seller-landing-slider" data-bs-ride="carousel">
                    @if(count($sliders) > 1)
                        <div class="carousel-indicators">
                            @foreach($sliders as $i => $slider)
                                <button type="button" data-bs-target="#sellerLandingSlider" data-bs-slide-to="{{ $i }}"
                                        {{ $loop->first ? 'class="active" aria-current="true"' : '' }} aria-label="اسلاید {{ $i + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                    <div class="carousel-inner">
                        @foreach($sliders as $slider)
                            <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                <img src="{{ asset($slider->image) }}" class="d-block w-100"
                                     alt="{{ $slider->title ?: 'فروشندگان' }}">
                                @if($slider->title or $slider->description)
                                    <div class="seller-slider-caption">
                                        @if($slider->title)<h2 class="fw-bold h4 mb-1">{{ $slider->title }}</h2>@endif
                                        @if($slider->description)<p class="mb-0 font-14">{{ $slider->description }}</p>@endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if(count($sliders) > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#sellerLandingSlider" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">قبلی</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#sellerLandingSlider" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">بعدی</span>
                        </button>
                    @endif
                </div>
            @else
                {{-- هیرو جایگزین داینامیک (وقتی اسلایدری ثبت نشده) --}}
                <div class="seller-landing-hero p-4 p-lg-5">
                    <div class="row align-items-center gy-4">
                        <div class="col-lg-8">
                            <h1 class="hero-title h2 mb-3">
                                فروش کالای خود را در {{ option('info_site_title', config('front.demo.name')) }} شروع کنید
                            </h1>
                            <p class="hero-sub mb-4">
                                بدون هزینه اجاره فروشگاه و طراحی سایت، با دسترسی به میلیون‌ها خریدار، پنل مدیریت
                                حرفه‌ای فروشندگان، تسویه‌های منظم و پشتیبانی همیشگی. فقط کالای خود را ثبت کنید و بفروشید.
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('seller.registration') }}" class="btn btn-light rounded-3 px-4 py-3 fw-bold text-nowrap">
                                    <i class="bi bi-person-plus me-1"></i>ثبت‌نام فروشنده
                                </a>
                                <a href="{{ route('seller.login') }}" class="btn btn-outline-light rounded-3 px-4 py-3 fw-bold text-nowrap">
                                    <i class="bi bi-box-arrow-in-left me-1"></i>ورود به پنل
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-4 text-center d-none d-lg-block">
                            <img src="{{ theme_asset('image/panel.svg') }}" class="seller-hero-img img-fluid"
                                 alt="پنل فروشندگان {{ option('info_site_title', config('front.demo.name')) }}">
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== چرا فروش در این فروشگاه؟ (داینامیک از SellerHero) ===== --}}
    @if(count($sellers_heroes))
        <div class="content pb-20">
            <div class="container-fluid">
                <div class="slider-title-desc-center mb-4 text-center">
                    <h2 class="h4 fw-bold">چرا {{ option('info_site_title', config('front.demo.name')) }} جای خوبی برای فروش کالاست؟</h2>
                </div>
                <div class="row g-3">
                    @foreach($sellers_heroes as $sellers_hero)
                        <div class="col-sm-6 col-lg-3">
                            <div class="seller-benefit-card">
                                <div class="seller-benefit-icon" aria-hidden="true">
                                    @if($sellers_hero->icon)
                                        {!! $sellers_hero->icon !!}
                                    @else
                                        <i class="bi bi-star-fill"></i>
                                    @endif
                                </div>
                                <h3 class="h6 fw-bold mb-2">{{ $sellers_hero->title }}</h3>
                                <p class="font-13 text-muted mb-0" style="line-height: 2;">{{ $sellers_hero->description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ===== فرآیند شروع کار ===== --}}
    <div class="content pb-20">
        <div class="container-fluid">
            <div class="slider-title-desc-center mb-4 text-center">
                <h2 class="h4 fw-bold">فرآیند شروع کار</h2>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-6 col-md-3">
                    <div class="seller-roadmap-step">
                        <span class="step-badge">۱</span>
                        <p class="font-14 fw-bold mb-1">ثبت‌نام در پنل فروشندگان</p>
                        <p class="font-12 text-muted mb-0">با ایمیل و شماره موبایل شروع کنید</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="seller-roadmap-step">
                        <span class="step-badge">۲</span>
                        <p class="font-14 fw-bold mb-1">ثبت اطلاعات و مدارک</p>
                        <p class="font-12 text-muted mb-0">اطلاعات تجاری و تصویر مدارک هویتی</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="seller-roadmap-step">
                        <span class="step-badge">۳</span>
                        <p class="font-14 fw-bold mb-1">ثبت کالاها و قیمت‌ها</p>
                        <p class="font-12 text-muted mb-0">محصولات خود را در پنل ثبت کنید</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="seller-roadmap-step">
                        <span class="step-badge">۴</span>
                        <p class="font-14 fw-bold mb-1">آغاز فروش</p>
                        <p class="font-12 text-muted mb-0">سفارش‌ها را ببینید و ارسال کنید</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== مدارک مورد نیاز ===== --}}
    <div class="content pb-20">
        <div class="container-fluid">
            <div class="slider-title-desc-center mb-4 text-center">
                <h2 class="h4 fw-bold">مدارک مورد نیاز</h2>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="seller-doc-card">
                        <i class="bi bi-person-vcard doc-icon d-block mb-2"></i>
                        <h3 class="h6 fw-bold mb-2">فرد حقیقی</h3>
                        <p class="font-13 text-muted mb-0">تصویر کارت ملی یا کارت شناسایی معتبر</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="seller-doc-card">
                        <i class="bi bi-building doc-icon d-block mb-2"></i>
                        <h3 class="h6 fw-bold mb-2">فرد حقوقی</h3>
                        <p class="font-13 text-muted mb-0">تصاویر ثبت‌نام در وب‌سایت evat.ir، روزنامه رسمی شرکت و کارت ملی صاحبین امضا</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== کمیسیون دسته‌بندی‌ها (داینامیک از SellerCommission) ===== --}}
    @if(count($sellers_commissions))
        <div class="content pb-20">
            <div class="container-fluid">
                <div class="slider-title-desc-center mb-4 text-center">
                    <h2 class="h4 fw-bold">میزان کمیسیون در هر دسته‌بندی</h2>
                    <p class="font-14 text-muted mt-2 mb-0" style="max-width: 720px; margin-inline: auto; line-height: 2;">
                        با {{ option('info_site_title', config('front.demo.name')) }} دیگر نیازی به پرداخت اجاره فروشگاه، طراحی سایت و
                        دریافت پنل ندارید؛ تنها کمیسیون متناسب با دسته‌بندی کالا می‌پردازید.
                    </p>
                </div>
                <div class="row g-3">
                    @foreach($sellers_commissions as $sellers_commission)
                        <div class="col-sm-6 col-lg-3">
                            <div class="seller-commission-item">
                                <span class="commission-icon d-block mb-2" aria-hidden="true">
                                    @if($sellers_commission->icon)
                                        {!! $sellers_commission->icon !!}
                                    @else
                                        <i class="bi bi-tags-fill"></i>
                                    @endif
                                </span>
                                <h3 class="h6 fw-bold mb-1">{{ $sellers_commission->title }}</h3>
                                <p class="font-13 text-muted mb-0">{{ $sellers_commission->description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ===== سوالات متداول (داینامیک از SellerQuestion) ===== --}}
    @if(count($sellers_questions))
        <div class="content pb-20">
            <div class="container-fluid" style="max-width: 900px;">
                <div class="slider-title-desc-center mb-4 text-center">
                    <h2 class="h4 fw-bold">سوالات شما</h2>
                </div>
                <div class="d-grid gap-2">
                    @foreach($sellers_questions as $i => $sellers_question)
                        <div class="seller-faq-item">
                            <button type="button" class="faq-btn font-14" data-bs-toggle="collapse"
                                    data-bs-target="#sellerFaq{{ $i }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                    aria-controls="sellerFaq{{ $i }}">
                                <span>{{ $sellers_question->question }}</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div id="sellerFaq{{ $i }}" class="collapse {{ $loop->first ? 'show' : '' }}">
                                <p class="faq-body font-14 mb-0">{{ $sellers_question->answer }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ===== نوار دعوت به ثبت‌نام ===== --}}
    <div class="content pb-20">
        <div class="container-fluid">
            <div class="seller-landing-cta p-4 p-lg-5 text-center">
                <h2 class="h4 fw-bold mb-2">همین امروز فروش خود را شروع کنید</h2>
                <p class="font-14 mb-4" style="opacity:.85;">
                    ثبت‌نام رایگان است؛ اطلاعات کسب‌وکار خود را تکمیل کنید و به جمع فروشندگان
                    {{ option('info_site_title', config('front.demo.name')) }} بپیوندید.
                </p>
                <a href="{{ route('seller.registration') }}" class="btn btn-light rounded-3 px-4 py-3 fw-bold">
                    <i class="bi bi-person-plus me-1"></i>ثبت‌نام فروشنده
                </a>
            </div>
        </div>
    </div>
@endsection

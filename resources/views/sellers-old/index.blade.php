@extends('sellers-old.layouts.master')


@section('content')
    <header class="header-seller">
        <div class="container-seller">
            <div class="seller-header d-flex justify-content-sm-between">
                <div class="logo">
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo_seller', theme_asset('img/logo-seller.png')) }}"
                             alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                @if(auth('seller')->check())
                <div class="d-flex ai-center">
                    <a href="{{route('seller.login')}}" class="seller-login-btn">ورود به پنل فروشندگان</a>
                </div>
                @else
                    <div class="d-flex ai-center">
                        <a href="{{route('seller.login')}}" class="seller-login-btn">ورود به پنل فروشندگان</a>
                        <a href="{{route('seller.registration')}}" class="seller-register-btn">ثبت‌نام</a>
                    </div>
                @endif
            </div>

        </div>
    </header>
    <div class="main-seller">
        <div class="search-results-list js-search-ad-banner">
            <div class="main-slider d-contents">
                <div class="main-slider-container">
                    <div id="carouselExampleIndicators2" class="carousel slide" data-ride="carousel2">
                        @if(count($sliders)>1)
                            <ol class="carousel-indicators">
                                @for($i=1;$i<= count($sliders);$i++)
                                    <li data-target="#carouselExampleIndicators2" data-slide-to="{{$i}}"
                                        class="@if($i==0)active @endif"></li>
                                @endfor
                            </ol>
                        @endif
                        @foreach($sliders as $slider)
                            <div class="carousel-inner">
                                <div class="carousel-item @if ($loop->first)active @endif">
                                    <img class="d-block w-100" src="{{asset($slider->image)}}"
                                         alt="{{$slider->title ?: $slider->image}}">
                                </div>
                                <div class="seller-slider-contact">
                                    <h2 class="color-white">{{$slider->title}}</h2>
                                    <p class="color-white">{{$slider->description}}</p>
                                </div>
                            </div>
                        @endforeach
                        @if(count($sliders)>1)
                            <a class="carousel-control-prev" href="#carouselExampleIndicators2" role="button"
                               data-slide="prev">
                                <span class="fa fa-angle-left" aria-hidden="true"></span>
                                <span class="sr-only">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselExampleIndicators2" role="button"
                               data-slide="next">
                                <span class="fa fa-angle-right" aria-hidden="true"></span>
                                <span class="sr-only">Next</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if(count($sellers_heroes))
            <div class="px-4 px-0-lg">
                <div class="container-xl-w mx-auto mx-1 mt-4 mt-10-lg px-10-lg">
                    <h1 class="break-words py-3">
                        <div class="d-flex ai-center grow-1"><p class="grow-1 text-h4 color-700">
                                چرا {{ option('info_site_title', 'او پی شاپ') }} جای خوبی برای
                                فروش کالاست؟</p></div>
                        <div
                            class="mt-2 Title_Title__line__NnTQY styles_LandingSellerIntroductionBenefits__title__fMOkh"></div>
                    </h1>
                    <div class="mt-4-lg d-grid grid-cols-2 grid-cols-2-lg">
                        @foreach($sellers_heroes as $sellers_hero)
                            <div class="p-2 h-full">
                                <div class="d-flex Landing-icon">
                                    {!! $sellers_hero->icon !!}
                                </div>
                                <h1 class="break-words py-3 p-0">
                                    <div class="d-flex ai-center grow-1"><p
                                            class="grow-1 text-h5 color-900">{{$sellers_hero->title}}</p></div>
                                    <div class="mt-2 Title_Title__line__NnTQY bg-200"></div>
                                </h1>
                                <p class="text-body-2 color-700">{{$sellers_hero->description}}</p></div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div
            class="overflow-hidden container-xl-w mx-auto mt-4 mt-10-lg styles_LandingSellerIntroductionSellerRoadmap__Section__HrmnO">
            <h1 class="break-words py-3 py-3">
                <div class="d-flex ai-center grow-1"><p class="grow-1 text-h4 color-700">فرآیند شروع کار</p></div>
                <div
                    class="Title_Title__line__NnTQY styles_LandingSellerIntroductionSellerRoadmap__title__86lZ6"></div>
            </h1>
            <div class="mt-1 mt-4-lg d-flex flex-wrap overflow-x-scroll overflow-x-hidden-lg ai-center-lg gap-2 gap-0-lg">
                <div class="d-flex flex-column ai-start ai-center-lg jc-center shrink-0 h-full styles_LandingSellerIntroductionSellerRoadmap__Box__LI662">
                    <div class="d-inline-block radius-circle p-2 z-1 styles_LandingSellerIntroductionSellerRoadmap__Icon__dkQeQ">
                        <div class="d-flex">
                            <i class="fa-solid fa-right-to-bracket"></i>
                        </div>
                        <span
                            class="radius-circle text-body2-strong d-flex ai-center jc-center color-white z-2 p-1 styles_LandingSellerIntroductionSellerRoadmap__IconIndex__51XU_">۱</span>
                    </div>
                    <p class="text-body1-strong color-700 mt-4 align-center-lg">ثبت‌نام در پنل فروشندگان</p></div>
                <div
                    class="d-flex flex-column ai-start ai-center-lg jc-center shrink-0 h-full styles_LandingSellerIntroductionSellerRoadmap__Box__LI662">
                    <div
                        class="d-inline-block radius-circle p-2 z-1 styles_LandingSellerIntroductionSellerRoadmap__Icon__dkQeQ">
                        <div class="d-flex">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <span
                            class="radius-circle text-body2-strong d-flex ai-center jc-center color-white z-2 p-1 styles_LandingSellerIntroductionSellerRoadmap__IconIndex__51XU_">۲</span>
                    </div>
                    <p class="text-body1-strong color-700 mt-4 align-center-lg">یادگیری استفاده از پنل</p></div>
                <div
                    class="d-flex flex-column ai-start ai-center-lg jc-center shrink-0 h-full styles_LandingSellerIntroductionSellerRoadmap__Box__LI662">
                    <div
                        class="d-inline-block radius-circle p-2 z-1 styles_LandingSellerIntroductionSellerRoadmap__Icon__dkQeQ">
                        <div class="d-flex">
                            <i class="fa-solid fa-file-pen"></i>
                        </div>
                        <span
                            class="radius-circle text-body2-strong d-flex ai-center jc-center color-white z-2 p-1 styles_LandingSellerIntroductionSellerRoadmap__IconIndex__51XU_">۳</span>
                    </div>
                    <p class="text-body1-strong color-700 mt-4 align-center-lg">ثبت اطلاعات و قیمت‌ کالاها</p></div>
                <div
                    class="d-flex flex-column ai-start ai-center-lg jc-center shrink-0 h-full styles_LandingSellerIntroductionSellerRoadmap__Box__LI662">
                    <div
                        class="d-inline-block radius-circle p-2 z-1 styles_LandingSellerIntroductionSellerRoadmap__Icon__dkQeQ">
                        <div class="d-flex">
                            <i class="fa-solid fa-shop"></i>
                        </div>
                        <span
                            class="radius-circle text-body2-strong d-flex ai-center jc-center color-white z-2 p-1 styles_LandingSellerIntroductionSellerRoadmap__IconIndex__51XU_">۴</span>
                    </div>
                    <p class="text-body1-strong color-700 mt-4 align-center-lg">آغاز فروش در {{option('info_site_title', 'او پی شاپ') }}</p></div>
            </div>
        </div>

        <div class="styles_LandingSellerIntroductionSellerDocuments__Container__Iin9g">
            <div class="container-xl-w mx-auto pt-7 pb-4 px-4 py-10-lg px-10-lg">
                <h1 class="break-words py-3 py-3">
                    <div class="d-flex ai-center grow-1"><p class="grow-1 text-h4 color-700">مدارک مورد نیاز</p></div>
                    <div
                        class=" Title_Title__line__NnTQY styles_LandingSellerIntroductionSellerDocuments__title__c_WH0"></div>
                </h1>
                <div
                    class="mt-4 d-flex-lg bg-000 mx-auto styles_LandingSellerIntroductionSellerDocuments__BoxContainer__K3SrZ">
                    <div class="w-50-lg d-flex-lg flex-column-lg ai-center  p-2 px-3-lg pt-7-lg pb-8-lg">
                        <div class="d-flex ai-center js-center">
                            <div class="d-flex">
                                <i class="fa-regular fa-user"></i>
                            </div>
                            <p class="text-h6 color-700 mr-1">فرد حقیقی</p></div>
                        <p class="align-center-lg text-subtitle color-700 mt-3">تصویر کارت ملی یا کارت شناسایی معتبر</p>
                    </div>
                    <div class="w-50-lg d-flex-lg flex-column-lg ai-center  p-2 px-3-lg pt-7-lg pb-8-lg">
                        <div class="d-flex ai-center js-center">
                            <div class="d-flex">
                                <i class="fa-solid fa-shop"></i>
                            </div>
                            <p class="text-h6 color-700 mr-1">فرد حقوقی</p></div>
                        <p class="align-center-lg text-subtitle color-700 mt-3">تصاویر ثبت‌نام در وب‌سایت «evat.ir»،
                            روزنامه رسمی شرکت و کارت ملی صاحبین امضا</p></div>
                </div>
            </div>
        </div>

        @if(count($sellers_commissions))
        <div class="d-flex-lg jc-between container-xl-w mx-auto pt-7 pb-4 px-4 py-10-lg px-10-lg sellers-commissions">
            <div class="shrink-0 ml-20-lg">
                <h1 class="break-words py-3">
                    <div class="d-flex ai-center grow-1"><p class="grow-1 text-h4 color-700">میزان کمیسیون در هر
                            دسته‌بندی</p></div>
                    <div
                        class="Title_Title__line__NnTQY styles_LandingSellerIntroductionCommission__title__rtaPe"></div>
                </h1>
                <p class="text-subtitle color-700 styles_LandingSellerIntroductionCommission__Desc__lzTsd">با {{ option('info_site_title', 'او پی شاپ') }}
                    دیگر نیازی به پرداخت اجاره فروشگاه، طراحی سایت، دریافت پنل و... ندارید! تنها هزینه‌ای اندک برای
                    استفاده از خدمات {{ option('info_site_title', 'او پی شاپ') }} و کمیسیون می‌پردازید. میزان کمیسیون متناسب با دسته‌بندی کالا تعیین
                    می‌شود. اینجا می‌توانید میزان کمیسیون هر دسته‌بندی را با جزئیات ببینید</p></div>
            <div class="mt-2 mt-4-lg d-grid grid-cols-2 grid-cols-2-lg gap-2 styles_LandingSellerIntroductionCommission__BoxContainer__JQp7v">
               @foreach($sellers_commissions as $sellers_commission)
                <div class="px-3 py-2 user-select-none">
                    <div class="d-flex">
                        {!! $sellers_commission->icon !!}
                    </div>
                    <p class="text-body1-strong color-700 mt-1">{{$sellers_commission->title}}</p>
                    <p class="text-body-2 color-500 mt-1">{{$sellers_commission->description}}</p></div>
                @endforeach
            </div>
        </div>
        @endif

        @if(count($sellers_questions))
        <div class="d-flex-lg jc-between container-xl-w mx-auto pt-7 pb-4 px-4 py-10-lg px-10-lg styles_LandingSellerIntroductionFaq__Section__cRJDx">
            <div class="content-info-page w-100">
                <h1 class="break-words py-3">
                    <div class="d-flex ai-center grow-1"><p class="grow-1 text-h4 color-700">سوالات شما</p></div>
                    <div
                        class="Title_Title__line__NnTQY styles_LandingSellerIntroductionCommission__title__rtaPe"></div>
                </h1>

                @foreach($sellers_questions as $sellers_question)
                <div class="toggle-box">
                    <div class="toggle-box-active">
                        <ul>
                            <li class="has-sub cursor-pointer"><a>{{$sellers_question->question}}</a>
                                <ul>
                                    <li class="has-sub"><a>{{$sellers_question->answer}}</a></li>
                                </ul>
                            </li>

                        </ul>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
@endsection

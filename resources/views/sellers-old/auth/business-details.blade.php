@extends('sellers-old.auth.layouts', ['title' => 'اطلاعات فروشنده'])

@push('styles')
    <link rel="stylesheet" href="https://cdn.map.ir/web-sdk/1.4.2/css/mapp.min.css" />
    <link rel="stylesheet" href="https://cdn.map.ir/web-sdk/1.4.2/css/fa/style.css" />
    <link rel="stylesheet" href="{{theme_asset('css/map-selected-styles.css')}}" />
@endpush

@section('content')
    <div class="new-login_seller">
        <div class='row w-100 m-0'>

            <div class='col-md-4 p-0'>
                <div class="new-login_seller-sidebar">
            <div class="new-login_seller_sidebar-content">
                <header>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('img/logo.png')) }}"
                             alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                    <h1>ثبت‌نام در مرکز فروشندگان</h1>
                </header>

                <ul class="c-reg-steps d-flex-r">
                    <li class="c-reg-steps__item">

                        <div class="c-reg-steps__icon c-reg-steps__icon--info c-reg-steps__icon--current">
                            <i class="fa-solid fa-file-pen"></i>
                        </div>
                        <h2 class="c-reg-steps__header">۱. اطلاعات فروشنده</h2>
                        <p class="c-reg-steps__description">اطلاعات شخصی فروشنده، اطلاعات تجاری، اطلاعات تماس</p>
                    </li>
                    <li class="c-reg-steps__item c-reg-steps__item--next">

                        <div class="c-reg-steps__icon c-reg-steps__icon--documents">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <h2 class="c-reg-steps__header">۲. بارگذاری مدارک</h2>
                        <p class="c-reg-steps__description">اطلاعات مربوط به مالیات بر ارزش افزوده، تصویر مدارک شخصی و تجاری</p>
                    </li>
                    <li class="c-reg-steps__item c-reg-steps__item--next">

                        <div class="c-reg-steps__icon c-reg-steps__icon--checkout">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h2 class="c-reg-steps__header">۳. اتمام ثبت نام</h2>
                        <p class="c-reg-steps__description">به جمع فروشندگان {{ option('info_site_title', 'او پی شاپ') }} خوش آمدید.</p>
                    </li>
                </ul>
            </div>
        </div>
            </div>
            <div class='col-md-8' id="main">
                <div class="new-login_seller_main registration-business-details">
            <div class="c-reg-form--full">
                <p class="c-reg-form__text">
                    <span class="c-reg-form__text-label">توجه: </span>پر کردن تمامی موارد الزامیست.
                </p>
                <form id="seller-register-business-details" action="{{route('seller.registration_business_details_store')}}" method="post">
                    @csrf
                    <div class="c-reg-form__row c-reg-form__row--gap-20 mt-4">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <h2 class="c-reg-form__header">چه نوع فروشنده ای هستید؟</h2>
                            <input type="hidden" name="seconds" id="seconds" value="1849">
                        </div>
                    </div>
                    <div class="checkout-time-table-time d-flex c-reg-form__row mb-5">

                        <div class="radio-box custom-control custom-radio form-group mb-0 pl-0 pr-3">
                            <input type="radio" class="custom-control-input form-control" name="private_business" id="private_business-private" value="private" checked="">
                            <label for="private_business-private" class="custom-control-label">
                                <span class="checkout-time-table-title-bar mt-2 d-inline-block">شخص حقیقی</span>
                            </label>
                        </div>

                        <div class="radio-box custom-control custom-radio form-group mb-0 pl-0 pr-3">
                            <input type="radio" class="custom-control-input form-control" name="private_business" id="private_business-business" value="business">
                            <label for="private_business-business" class="custom-control-label">
                                <span class="checkout-time-table-title-bar mt-2 d-inline-block">شخص حقوقی</span>
                            </label>
                        </div>

                    </div>

                    <div class="c-reg-form__row  c-reg-form__row--gap-10">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <p class="c-reg-form__text c-reg-form__text--condensed c-reg-form__text--gap">
                                <span class="c-reg-form__text--highlight">شخص حقیقی</span> فردی است که دارای خصوصیاتی مختص به خود مانند نام، نام خانوادگی، تاریخ تولد، کد ملی، شماره شناسنامه و غیره می باشد.
                            </p>
                            <p class="c-reg-form__text c-reg-form__text--condensed">
                                <span class="c-reg-form__text--highlight">شخص حقوقی</span> موسسات یا شرکت هایی هستند که پس از طی مراحل قانونی به ثبت می‌رسند و دارای مشخصاتی مانند نام شخص حقوقی، تاریخ ثبت، شماره ثبت، کد شناسایی، کد اقتصادی، موضوع فعالیت و غیره می باشند.
                            </p>
                        </div>
                    </div>


                    <div class="col-lg-12 col-xs-12 mx-auto p-0 ">
                        <div class="form-legal-col p-0 c-reg-form__row">
                            <fieldset class="form-legal-fieldset">
                                <div id="private-div" class="">
                                    <h4 class="c-reg-form__header mb-4">اطلاعات شخصی</h4>
                                    <div class="d-flex w-100">
                                        <div class="form-legal-item col-md-6 form-group">
                                            <label for="first_name">نام</label>
                                            <input type="text" id="first_name" name="first_name" class="input-name-first" placeholder="نام خود را وارد کنید">
                                        </div>

                                        <div class="form-legal-item col-md-6 form-group">
                                            <label for="last_name">نام خانوادگی</label>
                                            <input type="text" id="last_name" name="last_name" class="input-name-last" placeholder="نام خانوادگی خود را وارد کنید">
                                        </div>
                                    </div>
                                    <div class="d-flex w-100">
                                        <div class="form-legal-item col-md-12 mb-0 form-group">
                                            <label>تاریخ تولد</label>
                                            <select name="birth_day" id="birth_day">
                                                <option value="" selected="selected">روز</option>
                                                @for($i=1;$i<=31;$i++)
                                                    <option value="{{$i}}">{{$i}}</option>
                                                @endfor

                                            </select>
                                            <select name="birth_month" id="birth_month">
                                                <option value="" selected="selected">ماه</option>
                                                <option value="1">فروردین</option>
                                                <option value="2">اردیبهشت</option>
                                                <option value="3">خرداد</option>
                                                <option value="4">تیر</option>
                                                <option value="5">مرداد</option>
                                                <option value="6">شهریور</option>
                                                <option value="7">مهر</option>
                                                <option value="8">آبان</option>
                                                <option value="9">آذر</option>
                                                <option value="10">دی</option>
                                                <option value="11">بهمن</option>
                                                <option value="12">اسفند</option>
                                            </select>
                                            <select name="birth_year" id="birth_year">
                                                <option value="" selected="selected">سال</option>
                                                @for($i=1350;$i<=1390;$i++)
                                                    <option value="{{$i}}">{{$i}}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex w-100">
                                        <div class="form-legal-item col-md-12 form-group">
                                            <label for="name-first">جنسیت</label>
                                            <div class="checkout-time-table checkout-time-table-time d-flex c-reg-form__row pt-0">

                                                <div class=" custom-control custom-radio form-group mb-0 pl-0 ">
                                                    <input type="radio" class="custom-control-input form-control" name="gender" id="gender-male" value="male" checked="">
                                                    <label for="gender-male" class="custom-control-label">
                                                        <span class="checkout-time-table-title-bar mt-2 d-inline-block mr-3">مرد</span>
                                                    </label>
                                                </div>

                                                <div class=" custom-control custom-radio form-group mb-0 pl-0 pr-3">
                                                    <input type="radio" class="custom-control-input form-control" name="gender" id="gender-female" value="female">
                                                    <label for="gender-female" class="custom-control-label">
                                                        <span class="checkout-time-table-title-bar mt-2 d-inline-block mr-3">زن</span>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex w-100">
                                        <div class="form-legal-item col-md-6 form-group">
                                            <label for="identity_card_number">شماره شناسنامه</label>
                                            <input type="number" id="identity_card_number" name="identity_card_number" class="input-name-first" placeholder="۱۲۳۴۵">
                                        </div>

                                        <div class="form-legal-item col-md-6 form-group">
                                            <label for="national_identity_number">کد ملی</label>
                                            <input type="number" id="national_identity_number" name="national_identity_number" class="input-name-last" placeholder="۱۲۳۴۵۶۷۸۹۰">
                                        </div>
                                    </div>
                                </div>

                                <div id="business-div" class="d-none">
                                    <h4 class="c-reg-form__header mb-4">اطلاعات شرکتی</h4>
                                    <div class="d-flex w-100">
                                        <div class="form-legal-item col-md-12 form-group">
                                            <label for="company_name">نام شرکت</label>
                                            <input type="text" id="company_name" name="company_name" class="input-name-first" placeholder="نام شرکت را وارد کنید ...">
                                        </div>

                                    </div>
                                    <div class="d-flex w-100">
                                        <div class="form-legal-item col-md-6">
                                            <div class="form-checkout-valid-row w-100 form-group">
                                                <label for="company_type">نوع شرکت <span class="required-star" style="color:red;"></span></label>
                                                <select name="company_type" id="company_type" class="w-100 mb-0">
                                                    <option value="public" data-select2-id="11">سهامی عام</option>
                                                    <option value="joint_stock" data-select2-id="25">سهامی خاص</option>
                                                    <option value="ltd" data-select2-id="26">مسولیت محدود</option>
                                                    <option value="coop" data-select2-id="27">تعاونی</option>
                                                    <option value="solidarity" data-select2-id="28">تضامنی</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-legal-item col-md-6 form-group">
                                            <label for="company_registration_number">شماره ثبت</label>
                                            <input type="number" id="company_registration_number" name="company_registration_number" class="input-name-last" placeholder="۱۲۳۴۵">
                                        </div>
                                    </div>
                                    <div class="d-flex w-100">
                                        <div class="form-legal-item col-md-6 form-group">
                                            <label for="company_national_identity_number">شناسه ملی</label>
                                            <input type="number" name="company_national_identity_number" id="company_national_identity_number" class="input-name-first" placeholder="۱۲۳۴۵۶۷۸۹۰">
                                        </div>

                                        <div class="form-legal-item col-md-6 form-group">
                                            <label for="company_economic_number">کد اقتصادی</label>
                                            <input type="number" id="company_economic_number" name="company_economic_number" class="input-name-last" placeholder="۱۲۳۴۵۶۷۸۹۰">
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $cities = [];
                                    $city_id = null;
                                @endphp
                                <h4 class="c-reg-form__header mb-4">اطلاعات تماس</h4>
                                <div class="d-flex w-100 col-md-12 form-legal-item mb-0">
                                    <div class="form-checkout-valid-row form-group">
                                        <label for="province">استان <span class="required-star" style="color:red;"></span></label>
                                        <select name="state_id" id="province" class="w-100 mb-0 province">
                                            <option value="" selected="selected">شهر مورد نظر خود را انتخاب کنید</option>
                                            @foreach($provinces as $item)
                                                <option value="{{ $item->id }}" >{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-checkout-valid-row form-group">
                                        <label for="city">شهر
                                           </label>
                                        <select name="city_id" id="city"  class="w-100 mb-0">
                                            <option value="" selected="selected">شهر مورد نظر خود را انتخاب کنید</option>
                                            @foreach($cities as $item)
                                                <option value="{{ $item->id }}" >{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex w-100">
                                    <div class="form-legal-item col-md-12 form-group">
                                        <label for="address">آدرس</label>
                                        <input type="text" id="address" name="address" class="input-name-first" placeholder="آدرس خود را به صورت کامل (محله - خیابان اصلی - کوچه - پلاک – واحد ) وارد نمایید">
                                    </div>

                                </div>
                                <div class="d-flex w-100">
                                    <div class="form-legal-item col-md-6 form-group">
                                        <label for="post_code">کد پستی</label>
                                        <input type="number" id="post_code" name="post_code" class="input-name-first" placeholder="۱۲۳۴۵ - ۶۷۸۹۰">
                                    </div>

                                    <div class="form-legal-item col-md-6 form-group">
                                        <label for="lat_and_long">موقعیت مکانی</label>
                                        <input type="hidden" name="lat_and_long" value="" class="js-coordinates-input">
                                        <input  type="text" id="lat_and_long" readonly class="input-name-last cursor-pointer"  data-toggle="modal" data-target="#business-details-map-modal" placeholder="موقعیت را ثبت کنید">
                                        <div class="c-ui-input__icon c-ui-input__icon--map-placeholder js-coordinates-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex w-100">
                                    <div class="form-legal-item col-md-6 form-group">
                                        <label for="phone">تلفن ثابت(به همراه کد شهر)</label>
                                        <input type="number" id="phone" name="phone" class="input-name-first" placeholder="021-56000000">
                                    </div>

                                    <div class="form-legal-item col-md-6 form-group">
                                        <label for="mobile_phone">تلفن همراه</label>
                                        <input type="number" id="mobile_phone" name="mobile_phone" disabled class="input-name-last disabled" placeholder="09" value="{{session('show-seller-business-details')}}">
                                    </div>
                                </div>

                                <h4 class="c-reg-form__header mb-4">اطلاعات تجاری</h4>
                                <div class="d-flex w-100">
                                    <div class="form-legal-item col-md-12 form-group">
                                        <label for="business_name">نام فروشگاه</label>
                                        <input type="text" id="business_name" name="business_name" class="input-name-first" placeholder="نام فروشگاه شما در سایت {{ option('info_site_title', 'او پی شاپ') }}">
                                    </div>
                                </div>
                                <div class="d-flex w-100">
                                    <div class="form-legal-item col-md-12 form-group">
                                        <label for="shaba_number">شماره شبا (به نام شخص یا شرکت ثبت نام کننده)</label>
                                        <input type="text" id="shaba_number" name="shaba_number" class="input-name-first" placeholder="شماره شبا خود را وارد کنید">
                                    </div>
                                </div>
                                <div class="d-flex w-100">
                                    <div class="form-legal-item col-md-12 mb-0">
                                        <div class="form-checkout-valid-row w-100 form-group">
                                            <label for="main_supply_category_id">قصد فروش چه کالاهایی را دارید؟ <span class="required-star" style="color:red;"></span></label>
                                            <select name="main_supply_category_id" id="main_supply_category_id" class="w-100">
                                                @foreach($categories as $category)
                                                <option value="{{$category->id}}">{{$category->title}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex w-100">
                                    <div class="form-legal-item col-md-6 mb-0">
                                        <div class="form-checkout-valid-row w-100 form-group">
                                            <label for="number_of_products">تعداد حدودی تنوع کالای آماده فروش <span class="required-star" style="color:red;"></span></label>
                                            <select name="number_of_products" id="number_of_products" class="w-100">
                                                <option value="10" data-select2-id="20">1-10</option>
                                                <option value="50" data-select2-id="107">11-50</option>
                                                <option value="100" data-select2-id="108">51-100</option>
                                                <option value="300" data-select2-id="109">101-300</option>
                                                <option value="1000" data-select2-id="110">301-1000</option>
                                                <option value="3000" data-select2-id="111">1001-3000</option>
                                                <option value="10000" data-select2-id="112">3001-10000</option>
                                                <option value="30000" data-select2-id="113">10001-30000</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex w-100">
                                    <div class="form-legal-item col-md-12">
                                        <div class="form-auth-row form-group">
                                            <label class="ui-checkbox has-diviter">
                                                <input type="checkbox" value="1" name="econtract"  id="econtract">
                                                <span class="ui-checkbox-check"></span>
                                            </label>
                                            <label for="econtract" class="remember-me has-diviter-remember-me cursor-pointer w-auto-i">
                                                قرارداد همکاری را مطالعه کردم و موافقم
                                            </label>
                                            <span class="show-econtract remember-me cursor-pointer" data-toggle="modal" data-target="#seller-econtracts-modal">مشاهده قرارداد</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="parent-btn mt-0 w-100">
                                        <button type="submit" class="dk-btn dk-btn-info">
                                            ادامه
                                            <i class="fa fa-check sign-in"></i>
                                        </button>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>

            </div>


        </div>
            </div>
    </div>


    <div class="modal fade" id="business-details-map-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">

                            <div class="form-ui dt-sl">

                                    <div class="form-checkout-row map-container">
                                        <div class="container search-box">
                                            <div class="d-flex">
                                                <div class="container search-box__item  flex-row">
                                                    <input autocomplete="off" type="text" id="search" placeholder="جستجوی آدرس" /><span style="left: 18%" class="clear-seach">&#10006;</span>

                                                </div>
                                                <div class="c-ui-form__col c-ui-form__col--group-item c-ui-form__col--wrap-xs">
                                                    <button disabled class="c-ui-btn c-ui-btn--w-85 c-ui-btn--h-50 js-coordinates-confirm">ثبت</button>
                                                </div>
                                            </div>

                                            <div class="container search-box__item search-results">
                                                <div class="search-result-item"></div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="location">
                                        <div style="height: 400px" id="map-element"></div>
                                    </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bd-example-modal-xl" tabindex="-1" id="seller-econtracts-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">

                <div class="modal-body">
                    <div class="c-econtract">
                        <p class="c-econtract__desc">{!! $econtract->header !!}</p>
                    </div>
                    <div class="c-econtract__contract-wrapper">
                        <p class="c-econtract__desc">{!! $econtract->content !!}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input name="map_api" type="hidden" value="{{ option('map_api') }}">

@endsection


@push('scripts')

    <script src="{{ theme_asset('js/vendor/jquery.nice-select.min.js') }}"></script>
    <script src="{{ theme_asset('js/pages/sellers/register/business-details.js') }}"></script>
    <script type="text/javascript" src="{{ theme_asset('js/plugins/map.ir/mapp.env.js') }}"></script>
    <script type="text/javascript" src="{{ theme_asset('js/plugins/map.ir/mapp.min.js') }}"></script>
            <script src="{{ theme_asset("js/plugins/toastr/toastr.min.js") }}"></script>

@endpush

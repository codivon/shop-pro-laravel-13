{{-- پروفایل فروشنده — سانیار فاز J
    قرارداد ProfileSellerController@index :
    $seller، $seller_info، $categories (ریشه + children)، $econtract، $provinces (با cities)
    آپدیت: PUT seller.profile.update (AJAX FormData) — امضا: POST seller.profile.set_econtract --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'پروفایل شما', 'active' => 'profile'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller-profile.css') }}?v=1">
@endpush

@section('seller-content')
    @php
        // شهرهای استان فعلی برای پیش‌پر کردن انتخابگر شهر (کاسکید JS در تغییر استان جایگزین می‌کند)
        $sfe_province = $provinces->firstWhere('id', $seller_info->state_id);
        $sfe_cities = $sfe_province ? $sfe_province->cities : collect();
    @endphp

    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-person-gear main-color-one-color ms-1"></i>پروفایل شما</h5>
                    <p class="font-13 text-muted mb-0">ویرایش اطلاعات فروشندگی، مدارک هویتی، قرارداد همکاری و رمز عبور.</p>
                </div>
                @if(seller_info())
                    <a href="{{ route('front.showSellerStore', ['seller' => seller()]) }}" target="_blank"
                       class="btn btn-outline-primary rounded-3 font-14">
                        <i class="bi bi-shop me-1"></i>مشاهده فروشگاه من
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- یک فرم برای کل صفحه (ورودی‌های تب‌های مخفی هم Submit می‌شوند؛ display:none مشکلی ایجاد نمی‌کند) --}}
    <form id="sfe-profile-form" action="{{ route('seller.profile.update', $seller) }}" method="POST"
          enctype="multipart/form-data" novalidate
          data-redirect="{{ route('seller.profile.index') }}">
        @csrf
        @method('put')

        <div class="row g-3">
            {{-- ستون ناوبری تب‌ها + دکمه چسبان ثبت تغییرات --}}
            <div class="col-lg-3">
                <div class="sfe-side position-sticky top-0">
                    <div class="content-box">
                        <div class="container-fluid">
                            <div class="nav nav-pills saniar-form-tab-nav flex-lg-column gap-1 py-3" role="tablist" aria-label="بخش‌های پروفایل فروشنده">
                                <button class="saniar-form-tab-link active" type="button" role="tab" aria-selected="true"
                                        data-bs-toggle="pill" data-bs-target="#sfe-tab-profile">
                                    <i class="bi bi-person-vcard"></i><span>اطلاعات فروشنده</span>
                                </button>
                                <button class="saniar-form-tab-link" type="button" role="tab" aria-selected="false"
                                        data-bs-toggle="pill" data-bs-target="#sfe-tab-address">
                                    <i class="bi bi-geo-alt"></i><span>تماس و آدرس</span>
                                </button>
                                <button class="saniar-form-tab-link" type="button" role="tab" aria-selected="false"
                                        data-bs-toggle="pill" data-bs-target="#sfe-tab-bank">
                                    <i class="bi bi-bank"></i><span>حساب بانکی</span>
                                </button>
                                <button class="saniar-form-tab-link" type="button" role="tab" aria-selected="false"
                                        data-bs-toggle="pill" data-bs-target="#sfe-tab-documents">
                                    <i class="bi bi-person-badge"></i><span>مدارک هویتی</span>
                                </button>
                                <button class="saniar-form-tab-link" type="button" role="tab" aria-selected="false"
                                        data-bs-toggle="pill" data-bs-target="#sfe-tab-econtract">
                                    <i class="bi bi-file-earmark-check"></i><span>قرارداد</span>
                                </button>
                                <button class="saniar-form-tab-link" type="button" role="tab" aria-selected="false"
                                        data-bs-toggle="pill" data-bs-target="#sfe-tab-password">
                                    <i class="bi bi-key"></i><span>تغییر رمز عبور</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- کارت عملیات چسبان — همیشه دیده می‌شود (حتی روی تب قرارداد/رمز) --}}
                    <div class="content-box mt-3">
                        <div class="container-fluid py-3">
                            <button type="submit" id="sfe-submit-btn"
                                    class="btn main-color-one-bg text-white rounded-3 font-14 w-100 py-2">
                                <i class="bi bi-check2-circle ms-1"></i>ثبت تغییرات
                            </button>
                            <p class="font-12 text-muted mt-2 mb-0 text-center">
                                با ثبت تغییرات، اطلاعات شما مجدداً بررسی و تأیید می‌شود.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- محتوای تب‌ها --}}
            <div class="col-lg-9">
                <div class="tab-content">

                    {{-- ============ تب ۱: اطلاعات فروشنده ============ --}}
                    <div class="tab-pane fade show active" id="sfe-tab-profile" role="tabpanel" aria-label="اطلاعات فروشنده">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="title-panel mb-3">
                                    <h2 class="font-16 mb-0"><i class="bi bi-person-vcard main-color-one-color me-1"></i>اطلاعات فروشنده</h2>
                                </div>

                                {{-- چیپ‌های فقط‌خواندنی --}}
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <div class="sfe-info-chip">
                                            <span class="chip-label font-12">کد فروشنده</span>
                                            <span class="chip-value fw-bold font-13" dir="ltr">{{ $seller->id }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="sfe-info-chip">
                                            <span class="chip-label font-12">نوع فروشنده</span>
                                            <span class="chip-value fw-bold font-13">{{ $seller_info->private_business == 'private' ? 'حقیقی' : 'حقوقی' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="sfe-info-chip">
                                            <span class="chip-label font-12">نام تجاری</span>
                                            <span class="chip-value fw-bold font-13">{{ $seller_info->business_name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label font-13 fw-bold" for="sfe-main-category">قصد فروش چه کالاهایی را دارید؟ <span class="text-danger">*</span></label>
                                        <select class="form-select font-13" id="sfe-main-category" name="main_supply_category_id" required>
                                            <option value="">انتخاب کنید</option>
                                            @foreach($categories as $category)
                                                @php $sfe_children = $category->children; @endphp
                                                @if(count($sfe_children))
                                                    <optgroup label="{{ $category->title }}">
                                                        {{-- خود دستهٔ ریشه هم قابل انتخاب باشد (سنیار فاز J: بدون این، مقدار ذخیره‌شدهٔ ریشه در ویرایش ناپدید می‌شد) --}}
                                                        <option value="{{ $category->id }}"
                                                            {{ $seller_info->main_supply_category_id == $category->id ? 'selected' : '' }}>{{ $category->title }} — همهٔ زیرشاخه‌ها</option>
                                                        @foreach($sfe_children as $child)
                                                            <option value="{{ $child->id }}"
                                                                {{ $seller_info->main_supply_category_id == $child->id ? 'selected' : '' }}>{{ $child->title }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @else
                                                    <option value="{{ $category->id }}"
                                                        {{ $seller_info->main_supply_category_id == $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label font-13 fw-bold" for="sfe-number-products">تعداد حدودی تنوع کالای آماده فروش <span class="text-danger">*</span></label>
                                        <select class="form-select font-13" id="sfe-number-products" name="number_of_products" required>
                                            <option value="">انتخاب کنید</option>
                                            <option value="10" {{ $seller_info->number_of_products == '10' ? 'selected' : '' }}>۱ تا ۱۰</option>
                                            <option value="50" {{ $seller_info->number_of_products == '50' ? 'selected' : '' }}>۱۱ تا ۵۰</option>
                                            <option value="100" {{ $seller_info->number_of_products == '100' ? 'selected' : '' }}>۵۱ تا ۱۰۰</option>
                                            <option value="300" {{ $seller_info->number_of_products == '300' ? 'selected' : '' }}>۱۰۱ تا ۳۰۰</option>
                                            <option value="1000" {{ $seller_info->number_of_products == '1000' ? 'selected' : '' }}>۳۰۱ تا ۱۰۰۰</option>
                                            <option value="3000" {{ $seller_info->number_of_products == '3000' ? 'selected' : '' }}>۱۰۰۱ تا ۳۰۰۰</option>
                                            <option value="10000" {{ $seller_info->number_of_products == '10000' ? 'selected' : '' }}>۳۰۰۱ تا ۱۰۰۰۰</option>
                                            <option value="30000" {{ $seller_info->number_of_products == '30000' ? 'selected' : '' }}>۱۰۰۰۱ تا ۳۰۰۰۰</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label font-13 fw-bold" for="sfe-bio">درباره فروشنده</label>
                                        <textarea class="form-control font-13" id="sfe-bio" name="bio" rows="3"
                                                  placeholder="معرفی کوتاه فروشگاه شما به مشتریان">{{ $seller_info->bio }}</textarea>
                                    </div>
                                </div>

                                {{-- اطلاعات شخص حقیقی (فقط وقتی نوع فروشنده حقیقی است) --}}
                                <div id="sfe-private-fields" class="mt-4 {{ $seller_info->private_business == 'business' ? 'd-none' : '' }}">
                                    <h6 class="fw-bold mb-3 font-14"><i class="bi bi-person-lines-fill main-color-one-color ms-1"></i>اطلاعات شخصی</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label font-13 fw-bold" for="sfe-first-name">نام (فارسی) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control font-13" id="sfe-first-name" name="first_name"
                                                   value="{{ $seller_info->first_name }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label font-13 fw-bold" for="sfe-last-name">نام خانوادگی (فارسی) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control font-13" id="sfe-last-name" name="last_name"
                                                   value="{{ $seller_info->last_name }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label font-13 fw-bold" for="sfe-gender">جنسیت <span class="text-danger">*</span></label>
                                            <select class="form-select font-13" id="sfe-gender" name="gender">
                                                <option value="male" {{ $seller_info->gender == 'male' ? 'selected' : '' }}>مرد</option>
                                                <option value="female" {{ $seller_info->gender == 'female' ? 'selected' : '' }}>زن</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label font-13 fw-bold" for="sfe-birth-day">تاریخ تولد <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control font-13" id="sfe-birth-day" name="birth_day"
                                                   inputmode="numeric" placeholder="مثال: ۱۳۷۵/۰۶/۱۵" value="{{ $seller_info->birth_day }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label font-13 fw-bold" for="sfe-national-code">کد ملی (۱۰ رقم) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control font-13 ltr-direction" dir="ltr" inputmode="numeric"
                                                   id="sfe-national-code" name="national_identity_number" maxlength="10"
                                                   value="{{ $seller_info->national_identity_number }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label font-13 fw-bold" for="sfe-card-number">شماره شناسنامه <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control font-13 ltr-direction" dir="ltr" inputmode="numeric"
                                                   id="sfe-card-number" name="identity_card_number"
                                                   value="{{ $seller_info->identity_card_number }}">
                                        </div>
                                    </div>
                                </div>

                                {{-- اطلاعات شخص حقوقی (فقط وقتی نوع فروشنده حقوقی است) --}}
                                <div id="sfe-business-fields" class="mt-4 {{ $seller_info->private_business == 'private' ? 'd-none' : '' }}">
                                    <h6 class="fw-bold mb-3 font-14"><i class="bi bi-building-gear main-color-one-color ms-1"></i>اطلاعات شرکت</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label font-13 fw-bold" for="sfe-company-name">نام شرکت (فارسی) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control font-13" id="sfe-company-name" name="company_name"
                                                   value="{{ $seller_info->company_name }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label font-13 fw-bold" for="sfe-company-type">نوع شرکت <span class="text-danger">*</span></label>
                                            <select class="form-select font-13" id="sfe-company-type" name="company_type">
                                                <option value="public" {{ $seller_info->company_type == 'public' ? 'selected' : '' }}>سهامی عام</option>
                                                <option value="joint_stock" {{ $seller_info->company_type == 'joint_stock' ? 'selected' : '' }}>سهامی خاص</option>
                                                <option value="ltd" {{ $seller_info->company_type == 'ltd' ? 'selected' : '' }}>مسئولیت محدود</option>
                                                <option value="coop" {{ $seller_info->company_type == 'coop' ? 'selected' : '' }}>تعاونی</option>
                                                <option value="solidarity" {{ $seller_info->company_type == 'solidarity' ? 'selected' : '' }}>تضامنی</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label font-13 fw-bold" for="sfe-company-reg">شماره ثبت <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control font-13 ltr-direction" dir="ltr" inputmode="numeric"
                                                   id="sfe-company-reg" name="company_registration_number"
                                                   value="{{ $seller_info->company_registration_number }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label font-13 fw-bold" for="sfe-company-national">شناسه ملی شرکت (۱۱ رقم) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control font-13 ltr-direction" dir="ltr" inputmode="numeric"
                                                   id="sfe-company-national" name="company_national_identity_number" maxlength="11"
                                                   value="{{ $seller_info->company_national_identity_number }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label font-13 fw-bold" for="sfe-company-economic">کد اقتصادی شرکت (۱۲ رقم)</label>
                                            <input type="text" class="form-control font-13 ltr-direction" dir="ltr" inputmode="numeric"
                                                   id="sfe-company-economic" name="company_economic_number" maxlength="12"
                                                   value="{{ $seller_info->company_economic_number }}">
                                        </div>
                                    </div>
                                </div>

                                {{-- لوگو --}}
                                <div class="row g-3 align-items-center mt-4">
                                    <div class="col-auto">
                                        <img src="{{ $seller_info->logo ? asset($seller_info->logo) : asset('/empty.svg') }}"
                                             alt="لوگوی فروشنده" class="rounded-circle"
                                             style="width:80px;height:80px;object-fit:cover;">
                                    </div>
                                    <div class="col">
                                        <label class="form-label font-13 fw-bold" for="sfe-logo-input">تغییر لوگوی فروشنده</label>
                                        <input type="file" class="form-control font-12" id="sfe-logo-input" name="image"
                                               accept="image/*" aria-label="انتخاب تصویر لوگوی فروشنده">
                                        <div class="font-12 text-muted mt-1">بهترین اندازه ۶۰۰×۶۰۰ پیکسل است.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ تب ۲: تماس و آدرس ============ --}}
                    <div class="tab-pane fade" id="sfe-tab-address" role="tabpanel" aria-label="تماس و آدرس">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="title-panel mb-3">
                                    <h2 class="font-16 mb-0"><i class="bi bi-geo-alt main-color-one-color me-1"></i>تماس و آدرس</h2>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-email">ایمیل <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control font-13 ltr-direction" dir="ltr" id="sfe-email"
                                               name="email" value="{{ $seller->email }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-mobile">تلفن همراه (ثبت‌نام)</label>
                                        <input type="text" class="form-control font-13 bg-light ltr-direction" dir="ltr" id="sfe-mobile"
                                               value="{{ $seller_info->mobile }}" disabled aria-describedby="sfe-mobile-hint">
                                        <div class="font-12 text-muted mt-1" id="sfe-mobile-hint">شماره همراه هنگام ثبت‌نام تعیین شده و قابل تغییر نیست.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-website">وب‌سایت</label>
                                        <input type="text" class="form-control font-13 ltr-direction" dir="ltr" id="sfe-website"
                                               name="website" placeholder="https://example.com" value="{{ $seller_info->website }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-province">استان فروشگاه <span class="text-danger">*</span></label>
                                        <select class="form-select font-13" id="sfe-province" name="state_id" required
                                                data-cities-url="{{ route('provinces.get-cities') }}">
                                            <option value="">انتخاب کنید</option>
                                            @foreach($provinces as $province)
                                                <option value="{{ $province->id }}"
                                                    {{ $seller_info->state_id == $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-city">شهر فروشگاه <span class="text-danger">*</span></label>
                                        <select class="form-select font-13" id="sfe-city" name="city_id" required>
                                            @if(count($sfe_cities))
                                                @foreach($sfe_cities as $city)
                                                    <option value="{{ $city->id }}"
                                                        {{ $seller_info->city_id == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                                @endforeach
                                            @else
                                                <option value="">ابتدا استان را انتخاب کنید</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-location">موقعیت مکانی</label>
                                        <input type="text" class="form-control font-13" id="sfe-location" name="location"
                                               value="{{ $seller_info->location }}">
                                        <div class="font-12 text-muted mt-1">موقعیت مکانی فروشگاه</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label font-13 fw-bold" for="sfe-address">آدرس کامل <span class="text-danger">*</span></label>
                                        <textarea class="form-control font-13" id="sfe-address" name="address" rows="2"
                                                  placeholder="محله - خیابان - کوچه - پلاک - واحد">{{ $seller_info->address }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label font-13 fw-bold" for="sfe-post-code">کد پستی (۱۰ رقم) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control font-13 ltr-direction" dir="ltr" inputmode="numeric"
                                               id="sfe-post-code" name="post_code" maxlength="10" value="{{ $seller_info->post_code }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label font-13 fw-bold" for="sfe-phone">تلفن ثابت (۱۱ رقم) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control font-13 ltr-direction" dir="ltr" inputmode="numeric"
                                               id="sfe-phone" name="phone" maxlength="11" placeholder="۰۲۱..." value="{{ $seller_info->phone }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ تب ۳: حساب بانکی ============ --}}
                    <div class="tab-pane fade" id="sfe-tab-bank" role="tabpanel" aria-label="حساب بانکی">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="title-panel mb-3">
                                    <h2 class="font-16 mb-0"><i class="bi bi-bank main-color-one-color me-1"></i>حساب بانکی</h2>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label font-13 fw-bold" for="sfe-shaba">شماره شبا <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control font-13 ltr-direction" dir="ltr" id="sfe-shaba"
                                               name="shaba_number" maxlength="26" placeholder="IR + ۲۴ رقم"
                                               value="{{ $seller_info->shaba_number }}">
                                        <div class="font-12 text-muted mt-1">شماره شبا با حروف IR در ابتدا وارد شود.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label font-13 fw-bold" for="sfe-account-owner">صاحب حساب</label>
                                        <input type="text" class="form-control font-13 bg-light" id="sfe-account-owner"
                                               value="{{ $seller_info->full_name }}" disabled>
                                        <div class="font-12 text-muted mt-1">صاحب حساب بر اساس اطلاعات هویتی شماست و از این بخش قابل تغییر نیست.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ تب ۴: مدارک هویتی ============ --}}
                    <div class="tab-pane fade" id="sfe-tab-documents" role="tabpanel" aria-label="مدارک هویتی">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="title-panel mb-3">
                                    <h2 class="font-16 mb-0"><i class="bi bi-person-badge main-color-one-color me-1"></i>مدارک هویتی</h2>
                                </div>

                                @if($seller->status_documents == 'Reject')
                                    <div class="alert alert-danger font-14" role="alert">
                                        <i class="bi bi-x-circle ms-1"></i><strong>مدارک شما تأیید نشده است؛ برای ادامه، مدارک جدید بارگذاری کنید.</strong>
                                    </div>
                                @elseif($seller->status_documents == 'Accept')
                                    <div class="alert alert-success font-14" role="alert">
                                        <i class="bi bi-check-circle ms-1"></i><strong>مدارک شما تأیید شده است.</strong>
                                    </div>
                                @elseif($seller->status_documents == 'Waiting')
                                    <div class="alert alert-warning font-14" role="alert">
                                        <i class="bi bi-hourglass-split ms-1"></i><strong>مدارک شما در حال بررسی توسط کارشناسان است.</strong>
                                    </div>
                                @endif

                                {{-- پیش‌نمایش مدارک فعلی --}}
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="sfe-doc-preview">
                                            <img src="{{ $seller_info->card_image ? asset($seller_info->card_image) : asset('/empty.svg') }}"
                                                 alt="تصویر روی کارت ملی" loading="lazy">
                                            <span class="doc-caption font-12">کارت ملی (روبرو)</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="sfe-doc-preview">
                                            <img src="{{ $seller_info->card_image_back ? asset($seller_info->card_image_back) : asset('/empty.svg') }}"
                                                 alt="تصویر پشت کارت ملی" loading="lazy">
                                            <span class="doc-caption font-12">کارت ملی (پشت)</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="sfe-doc-preview">
                                            <img src="{{ $seller_info->vat_image ? asset($seller_info->vat_image) : asset('/empty.svg') }}"
                                                 alt="گواهی مالیات بر ارزش افزوده" loading="lazy">
                                            <span class="doc-caption font-12">گواهی معافیت/مشمولیت مالیات</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label font-13 fw-bold" for="sfe-vat-free">مشمول مالیات بر ارزش افزوده <span class="text-danger">*</span></label>
                                        <select class="form-select font-13" id="sfe-vat-free" name="vat_free">
                                            <option value="1" {{ $seller_info->vat_free == '1' ? 'selected' : '' }}>بله</option>
                                            <option value="2" {{ $seller_info->vat_free == '2' ? 'selected' : '' }}>خیر</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- بارگذاری مجدد مدارک — فقط وقتی مدارک رد شده باشد --}}
                                @if($seller->status_documents == 'Reject')
                                    <hr class="my-4">
                                    <h6 class="fw-bold mb-3 font-14"><i class="bi bi-cloud-arrow-up main-color-one-color ms-1"></i>بارگذاری مدارک جدید</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="saniar-upload-box">
                                                <label class="saniar-upload-cta" for="sfe-card-image">
                                                    <i class="bi bi-id-card"></i>
                                                    <span class="font-13 fw-bold">تصویر روی کارت ملی</span>
                                                    <span class="font-12 text-muted">jpg یا png — برای تغییر کلیک کنید</span>
                                                </label>
                                                <input type="file" class="form-control font-12 mt-2" id="sfe-card-image"
                                                       name="card_image" accept="image/*" aria-label="بارگذاری تصویر روی کارت ملی">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="saniar-upload-box">
                                                <label class="saniar-upload-cta" for="sfe-card-image-back">
                                                    <i class="bi bi-id-card"></i>
                                                    <span class="font-13 fw-bold">تصویر پشت کارت ملی</span>
                                                    <span class="font-12 text-muted">jpg یا png — برای تغییر کلیک کنید</span>
                                                </label>
                                                <input type="file" class="form-control font-12 mt-2" id="sfe-card-image-back"
                                                       name="card_image_back" accept="image/*" aria-label="بارگذاری تصویر پشت کارت ملی">
                                            </div>
                                        </div>
                                        <div class="col-md-6 {{ $seller_info->vat_free == '2' ? 'd-none' : '' }}" id="sfe-vat-upload">
                                            <div class="saniar-upload-box">
                                                <label class="saniar-upload-cta" for="sfe-vat-image">
                                                    <i class="bi bi-file-earmark-image"></i>
                                                    <span class="font-13 fw-bold">گواهی مالیات بر ارزش افزوده</span>
                                                    <span class="font-12 text-muted">jpg یا png — برای تغییر کلیک کنید</span>
                                                </label>
                                                <input type="file" class="form-control font-12 mt-2" id="sfe-vat-image"
                                                       name="vat_image" accept="image/*" aria-label="بارگذاری گواهی ارزش افزوده">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ============ تب ۵: قرارداد ============ --}}
                    <div class="tab-pane fade" id="sfe-tab-econtract" role="tabpanel" aria-label="قرارداد همکاری">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="title-panel mb-3">
                                    <h2 class="font-16 mb-0"><i class="bi bi-file-earmark-check main-color-one-color me-1"></i>قرارداد همکاری</h2>
                                </div>

                                @if($seller_info->econtract == 1)
                                    <div class="alert alert-success font-14" role="alert">
                                        <i class="bi bi-check-circle ms-1"></i><strong>قرارداد توسط شما امضا شده است.</strong>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary rounded-3 font-14"
                                            data-bs-toggle="modal" data-bs-target="#sfe-econtract-modal">
                                        <i class="bi bi-eye ms-1"></i>مشاهده متن قرارداد
                                    </button>
                                @else
                                    <div class="alert alert-warning font-14" role="alert">
                                        <i class="bi bi-exclamation-triangle ms-1"></i><strong>قرارداد هنوز امضا نشده است.</strong>
                                    </div>
                                    <button type="button" class="btn main-color-one-bg text-white rounded-3 font-14"
                                            data-bs-toggle="modal" data-bs-target="#sfe-econtract-modal">
                                        <i class="bi bi-pen ms-1"></i>مطالعه و امضای قرارداد
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ============ تب ۶: تغییر رمز عبور ============ --}}
                    <div class="tab-pane fade" id="sfe-tab-password" role="tabpanel" aria-label="تغییر رمز عبور">
                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="title-panel mb-3">
                                    <h2 class="font-16 mb-0"><i class="bi bi-key main-color-one-color me-1"></i>تغییر رمز عبور</h2>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-prev-password">رمز عبور قبلی</label>
                                        <input type="password" class="form-control font-13 ltr-direction" dir="ltr"
                                               id="sfe-prev-password" name="prev_password" autocomplete="current-password">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-password">رمز عبور جدید</label>
                                        <input type="password" class="form-control font-13 ltr-direction" dir="ltr"
                                               id="sfe-password" name="password" autocomplete="new-password">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label font-13 fw-bold" for="sfe-password-confirm">تکرار رمز عبور جدید</label>
                                        <input type="password" class="form-control font-13 ltr-direction" dir="ltr"
                                               id="sfe-password-confirm" name="password_confirmation" autocomplete="new-password">
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-info font-13 mb-0" role="alert">
                                            <i class="bi bi-info-circle ms-1"></i>اگر نمی‌خواهید رمز را تغییر دهید، این فیلدها را خالی بگذارید.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>

    {{-- مودال تأیید ثبت تغییرات --}}
    <div class="modal fade" id="sfe-confirm-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfe-confirm-title">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfe-confirm-title"><i class="bi bi-question-circle main-color-one-color ms-1"></i>تأیید ثبت تغییرات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body font-14">
                    با ثبت تغییرات، اطلاعات شما مجدداً نیازمند بررسی و تأیید کارشناسان خواهد بود. آیا ادامه می‌دهید؟
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" id="sfe-confirm-submit" class="btn main-color-one-bg text-white rounded-3 font-13">
                        <i class="bi bi-check2 ms-1"></i>بله، ثبت شود
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال متن قرارداد --}}
    <div class="modal fade" id="sfe-econtract-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfe-econtract-title">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfe-econtract-title"><i class="bi bi-file-earmark-check main-color-one-color ms-1"></i>قرارداد همکاری</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    @if($econtract)
                        {{-- متن قرارداد: HTML ادمین‌نویس از پنل مدیریت — عمداً بدون escape (!! ... !!) --}}
                        <div class="sfe-econtract-body font-14">
                            {!! $econtract->header !!}
                        </div>
                        <div class="sfe-econtract-body font-14">
                            {!! $econtract->content !!}
                        </div>
                    @else
                        <div class="alert alert-warning font-13" role="alert">
                            <i class="bi bi-exclamation-triangle ms-1"></i>متن قرارداد در حال حاضر در دسترس نیست؛ با پشتیبانی تماس بگیرید.
                        </div>
                    @endif

                    @if($seller_info->econtract != 1)
                        <hr>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="econtract" id="sfe-econtract-yes" value="1">
                            <label class="form-check-label font-14" for="sfe-econtract-yes">قرارداد را کامل خوانده‌ام و می‌پذیرم</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="econtract" id="sfe-econtract-no" value="0">
                            <label class="form-check-label font-14" for="sfe-econtract-no">میانه‌ایم و فعلاً امضا نمی‌کنم</label>
                        </div>
                    @endif
                </div>
                <div class="modal-footer flex-wrap">
                    <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">بعداً</button>
                    @if($seller_info->econtract != 1)
                        <button type="button" id="sfe-econtract-accept" class="btn main-color-one-bg text-white rounded-3 font-13"
                                data-action="{{ route('seller.profile.set_econtract') }}">
                            <i class="bi bi-pen ms-1"></i>ثبت امضا
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-profile.js') }}?v=1"></script>
@endpush

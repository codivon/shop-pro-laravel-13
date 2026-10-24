{{-- اطلاعات کسب‌وکار فروشنده — مرحله ۱ (فاز G)
    POST: seller.registration_business_details_store → JSON {status, redirect}
    قرارداد SellerController : $econtract (SellerEcontract)، $provinces، $categories (ریشه) --}}
@extends('front::sellers.auth.layouts.master', ['title' => 'اطلاعات فروشنده', 'auth_step' => 1, 'auth_card_class' => 'col-lg-7'])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-0 bg-white seller-auth-card">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">اطلاعات کسب‌وکار</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <p class="font-14 text-muted">
            <span class="fw-bold text-danger">توجه: </span>پر کردن تمامی موارد الزامیست.
        </p>

        <form action="{{ route('seller.registration_business_details_store') }}" method="post"
              id="seller-register-business-details" data-ajax novalidate>
            @csrf
            <input type="hidden" name="seconds" value="1849">

            {{-- ===== نوع فروشنده ===== --}}
            <h6 class="fw-bold mb-3 mt-2"><i class="bi bi-person-vcard main-color-one-color ms-1"></i>چه نوع فروشنده‌ای هستید؟</h6>
            <div class="row g-2 mb-4" role="radiogroup" aria-label="نوع فروشنده">
                <div class="col-md-6">
                    <label class="seller-radio-card is-selected" data-private-target="private">
                        <input type="radio" name="private_business" id="private_business-private" value="private" checked>
                        <span class="fw-bold font-14"><i class="bi bi-person me-1 main-color-one-color"></i>شخص حقیقی</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="seller-radio-card" data-private-target="business">
                        <input type="radio" name="private_business" id="private_business-business" value="business">
                        <span class="fw-bold font-14"><i class="bi bi-building me-1 main-color-one-color"></i>شخص حقوقی</span>
                    </label>
                </div>
            </div>

            {{-- ===== اطلاعات شخصی (حقیقی) ===== --}}
            <div id="private-fields">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-lines-fill main-color-one-color ms-1"></i>اطلاعات شخصی</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="comment-item">
                            <input type="text" class="form-control" id="first_name" name="first_name" placeholder=" " required>
                            <label for="first_name" class="form-label label-float">نام (فارسی)</label>
                        </div>
                        @error('first_name')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <div class="comment-item">
                            <input type="text" class="form-control" id="last_name" name="last_name" placeholder=" " required>
                            <label for="last_name" class="form-label label-float">نام خانوادگی (فارسی)</label>
                        </div>
                        @error('last_name')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="gender" class="form-label font-14 fw-bold text-muted">جنسیت</label>
                        <select name="gender" id="gender" class="form-select" required>
                            <option value="male" selected>مرد</option>
                            <option value="female">زن</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label font-14 fw-bold text-muted d-block">تاریخ تولد</label>
                        <div class="d-flex gap-2">
                            <select name="birth_day" id="birth_day" class="form-select" required aria-label="روز تولد">
                                <option value="" selected>روز</option>
                                @for($i = 1; $i <= 31; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
                            </select>
                            <select name="birth_month" id="birth_month" class="form-select" required aria-label="ماه تولد">
                                <option value="" selected>ماه</option>
                                @for($i = 1; $i <= 12; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
                            </select>
                            <select name="birth_year" id="birth_year" class="form-select" required aria-label="سال تولد">
                                <option value="" selected>سال</option>
                                @for($i = now()->year - 15; $i >= now()->year - 90; $i--)<option value="{{ $i }}">{{ $i }}</option>@endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="comment-item">
                            <input type="text" class="form-control" id="identity_card_number" name="identity_card_number" placeholder=" " required>
                            <label for="identity_card_number" class="form-label label-float">شماره کارت ملی</label>
                        </div>
                        @error('identity_card_number')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <div class="comment-item">
                            <input type="text" inputmode="numeric" class="form-control" id="national_identity_number" name="national_identity_number" placeholder=" " required maxlength="10">
                            <label for="national_identity_number" class="form-label label-float">کد ملی (۱۰ رقم)</label>
                        </div>
                        @error('national_identity_number')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                </div>
            </div>

            {{-- ===== اطلاعات شرکت (حقوقی) ===== --}}
            <div id="business-fields" class="company-fields-off">
                <h6 class="fw-bold mb-3"><i class="bi bi-building-gear main-color-one-color ms-1"></i>اطلاعات شرکت</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="comment-item">
                            <input type="text" class="form-control" id="company_name" name="company_name" placeholder=" ">
                            <label for="company_name" class="form-label label-float">نام شرکت (فارسی)</label>
                        </div>
                        @error('company_name')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <div class="comment-item">
                            <input type="text" class="form-control" id="company_registration_number" name="company_registration_number" placeholder=" ">
                            <label for="company_registration_number" class="form-label label-float">شماره ثبت شرکت</label>
                        </div>
                        @error('company_registration_number')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <div class="comment-item">
                            <input type="text" inputmode="numeric" class="form-control" id="company_national_identity_number" name="company_national_identity_number" placeholder=" " maxlength="11">
                            <label for="company_national_identity_number" class="form-label label-float">شناسه ملی شرکت (۱۱ رقم)</label>
                        </div>
                        @error('company_national_identity_number')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <div class="comment-item">
                            <input type="text" inputmode="numeric" class="form-control" id="company_economic_number" name="company_economic_number" placeholder=" " maxlength="12">
                            <label for="company_economic_number" class="form-label label-float">کد اقتصادی شرکت (۱۲ رقم)</label>
                        </div>
                        @error('company_economic_number')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="company_type" class="form-label font-14 fw-bold text-muted">نوع شرکت</label>
                        <select name="company_type" id="company_type" class="form-select">
                            <option value="سهامی خاص">سهامی خاص</option>
                            <option value="سهامی عام">سهامی عام</option>
                            <option value="مسئولیت محدود">مسئولیت محدود</option>
                            <option value="تضامنی">تضامنی</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ===== اطلاعات تماس ===== --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt main-color-one-color ms-1"></i>اطلاعات تماس</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="state_id" class="form-label font-14 fw-bold text-muted">استان</label>
                    <select name="state_id" id="state_id" class="form-select" data-cities-url="{{ route('provinces.get-cities') }}" required>
                        <option value="" selected>استان مورد نظر خود را انتخاب کنید</option>
                        @foreach($provinces as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-6">
                    <label for="city_id" class="form-label font-14 fw-bold text-muted">شهر</label>
                    <select name="city_id" id="city_id" class="form-select" required>
                        <option value="" selected>ابتدا استان را انتخاب کنید</option>
                    </select>
                    @error('city_id')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-12">
                    <div class="comment-item">
                        <input type="text" class="form-control" id="address" name="address" placeholder=" " required>
                        <label for="address" class="form-label label-float">آدرس کامل (محله - خیابان - کوچه - پلاک - واحد)</label>
                    </div>
                    @error('address')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-6">
                    <div class="comment-item">
                        <input type="text" inputmode="numeric" class="form-control" id="post_code" name="post_code" placeholder=" " required maxlength="10">
                        <label for="post_code" class="form-label label-float">کد پستی (۱۰ رقم)</label>
                    </div>
                    @error('post_code')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-6">
                    <div class="comment-item">
                        <input type="tel" inputmode="numeric" class="form-control" id="phone" name="phone" placeholder=" " required maxlength="11">
                        <label for="phone" class="form-label label-float">تلفن ثابت (۱۱ رقم، مانند ۰۲۱...)</label>
                    </div>
                    @error('phone')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-12">
                    <div class="comment-item">
                        <input type="text" class="form-control" id="lat_and_long" name="lat_and_long" placeholder=" ">
                        <label for="lat_and_long" class="form-label label-float">موقعیت جغرافیایی فروشگاه (اختیاری)</label>
                    </div>
                </div>
            </div>

            {{-- ===== اطلاعات فروشگاه ===== --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-shop main-color-one-color ms-1"></i>اطلاعات فروشگاه</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="comment-item">
                        <input type="text" class="form-control" id="business_name" name="business_name" placeholder=" " required>
                        <label for="business_name" class="form-label label-float">نام فروشگاه (نمایش به مشتریان)</label>
                    </div>
                    @error('business_name')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-6">
                    <div class="comment-item">
                        <input type="text" class="form-control" id="shaba_number" name="shaba_number" placeholder=" " required>
                        <label for="shaba_number" class="form-label label-float">شماره شبا (با IR)</label>
                    </div>
                    @error('shaba_number')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-6">
                    <label for="main_supply_category_id" class="form-label font-14 fw-bold text-muted">قصد فروش چه کالاهایی را دارید؟</label>
                    <select name="main_supply_category_id" id="main_supply_category_id" class="form-select" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                        @endforeach
                    </select>
                    @error('main_supply_category_id')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
                <div class="col-md-6">
                    <label for="number_of_products" class="form-label font-14 fw-bold text-muted">تعداد حدودی تنوع کالای آماده فروش</label>
                    <select name="number_of_products" id="number_of_products" class="form-select" required>
                        <option value="10">۱ تا ۱۰</option>
                        <option value="50">۱۱ تا ۵۰</option>
                        <option value="100">۵۱ تا ۱۰۰</option>
                        <option value="300">۱۰۱ تا ۳۰۰</option>
                        <option value="1000">۳۰۱ تا ۱۰۰۰</option>
                        <option value="3000">۱۰۰۱ تا ۳۰۰۰</option>
                        <option value="10000">۳۰۰۱ تا ۱۰۰۰۰</option>
                        <option value="30000">بیش از ۱۰۰۰۰</option>
                    </select>
                    @error('number_of_products')<span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>@enderror
                </div>
            </div>

            {{-- ===== قرارداد الکترونیک ===== --}}
            @if($econtract)
                <div class="accordion mb-3" id="econtractAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="econtractHeading">
                            <button class="accordion-button collapsed fw-bold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#econtractBody"
                                    aria-expanded="false" aria-controls="econtractBody">
                                <i class="bi bi-file-earmark-check me-2 main-color-one-color"></i>
                                قرارداد الکترونیک فروشندگی {{ option('info_site_title', config('front.demo.name')) }} — مطالعه و تایید
                            </button>
                        </h2>
                        <div id="econtractBody" class="accordion-collapse collapse"
                             aria-labelledby="econtractHeading" data-bs-parent="#econtractAccordion">
                            <div class="accordion-body font-14 text-muted" style="max-height: 260px; overflow-y: auto; line-height: 2.2;">
                                {!! $econtract->description !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="econtract" id="econtract" value="1" required>
                    <label class="form-check-label font-14" for="econtract">
                        تمامی شرایط و قوانین قرارداد الکترونیک را مطالعه نموده و آن را می‌پذیرم.
                    </label>
                </div>
                @error('econtract')<span class="invalid-feedback d-block mb-3" role="alert"><strong>{{ $message }}</strong></span>@enderror
            @endif

            <div class="auth-form-errors is-empty" role="alert"></div>

            <div class="form-group">
                <button type="submit" class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    تایید و ادامه
                    <i class="bi bi-arrow-left ms-1"></i>
                </button>
            </div>
        </form>
    </div>
@endsection

@extends('back.layouts.master')
@push('styles')
    <link rel="stylesheet" href="{{asset('back/assets/css/pages/settings/others.css')}}">
@endpush
@section('content')

    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">

            {{-- =============== Page Header / Breadcrumb =============== --}}
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb no-border">
                                    <li class="breadcrumb-item">مدیریت</li>
                                    <li class="breadcrumb-item">تنظیمات</li>
                                    <li class="breadcrumb-item active">تنظیمات دیگر</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <!-- users edit start -->
                <section class="users-edit settings-others">

                    <div class="card">
                        <div id="main-card" class="card-content">
                            <div class="card-body">
                                <div class="tab-content">
                                    <form id="others-form" action="{{ route('admin.settings.others') }}" method="POST" enctype="multipart/form-data">
                                        @csrf

                                        {{-- ====================================================== --}}
                                        {{-- 1) Multi-vendor                                            --}}
                                        {{-- ====================================================== --}}
                                        <div class="settings-section acc-vendor">
                                            <div class="section-heading">
                                                <span class="icon-wrap"><i class="fas fa-shopping-bag"></i></span>
                                                <div>
                                                    <h4>چند فروشندگی</h4>
                                                    <small>فعال یا غیرفعال کردن قابلیت چند فروشندگی در سایت</small>
                                                </div>
                                            </div>
                                            <div class="section-body">
                                                <div class="row">
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>سیستم چند فروشندگی</label>
                                                            <select name="multi_vendor_system_status" class="form-control">
                                                                <option value="true" {{ option('multi_vendor_system_status') == 'true' ? 'selected' : '' }}>فعال</option>
                                                                <option value="false" {{ option('multi_vendor_system_status') == 'false' ? 'selected' : '' }}>غیرفعال</option>
                                                            </select>
                                                            <div class="hint-text"><i class="feather icon-info"></i> در حالت فعال، فروشندگان مستقل می‌توانند محصولات خود را مدیریت کنند.</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ====================================================== --}}
                                        {{-- 2) Order invoice settings                                  --}}
                                        {{-- ====================================================== --}}
                                        <div class="settings-section acc-invoice">
                                            <div class="section-heading">
                                                <span class="icon-wrap"><i class="feather icon-file-text"></i></span>
                                                <div>
                                                    <h4>تنظیمات فاکتور سفارشات</h4>
                                                    <small>اطلاعاتی که در فاکتور چاپی سفارش‌ها نمایش داده می‌شود</small>
                                                </div>
                                            </div>
                                            <div class="section-body">
                                                <div class="row">
                                                    <div class="col-md-3 col-12">
                                                        <fieldset class="form-group">
                                                            <label>لوگو</label>
                                                            <div class="custom-file">
                                                                <input type="file" accept="image/*" name="factor_logo" class="custom-file-input">
                                                                <label class="custom-file-label">{{ option('factor_logo') ?: 'انتخاب فایل…' }}</label>
                                                            </div>
                                                        </fieldset>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>عنوان فاکتور</label>
                                                            <input type="text" name="factor_title" class="form-control" value="{{ option('factor_title', option('info_site_title')) }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>فروشنده</label>
                                                            <input type="text" name="factor_seller_name" class="form-control" value="{{ option('factor_seller_name') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>شناسه ملی</label>
                                                            <input type="text" name="factor_national_code" class="form-control ltr" value="{{ option('factor_national_code') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>شناسه ثبت</label>
                                                            <input type="text" name="factor_registeration_id" class="form-control ltr" value="{{ option('factor_registeration_id') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>شماره اقتصادی</label>
                                                            <input type="text" name="factor_economical_number" class="form-control ltr" value="{{ option('factor_economical_number') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ====================================================== --}}
                                        {{-- 3) User settings                                           --}}
                                        {{-- ====================================================== --}}
                                        <div class="settings-section acc-users">
                                            <div class="section-heading">
                                                <span class="icon-wrap"><i class="feather icon-users"></i></span>
                                                <div>
                                                    <h4>تنظیمات مربوط به کاربران</h4>
                                                    <small>اعتبار هدیه، سیستم معرفی و شرایط دریافت هدیه</small>
                                                </div>
                                            </div>
                                            <div class="section-body">
                                                <div class="sub-divider">اعتبار و معرفی</div>
                                                <div class="row">
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>اعتبار هدیه ثبت‌نام کاربر</label>
                                                            <input type="number" name="user_register_gift_credit" class="form-control" min="0" value="{{ option('user_register_gift_credit', 0) }}">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>فعال کردن امکان معرفی افراد</label>
                                                            <select id="user_referrals_enable" name="user_referrals_enable" class="form-control">
                                                                @if (option('user_referrals_enable') == 'false')
                                                                    <option value="true">بله</option>
                                                                    <option value="false" selected>خیر</option>
                                                                @elseif(option('user_referrals_enable') == 'true')
                                                                    <option value="true" selected>بله</option>
                                                                    <option value="false">خیر</option>
                                                                @else
                                                                    <option value="true">بله</option>
                                                                    <option value="false" selected>خیر</option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="referrals_enable" class="d-none">
                                                    <div class="row">

                                                        <div class="col-md-6">
                                                            <div class="sub-divider">نوع هدیه</div>
                                                            <div class="row">
                                                                <div class="col-md-6 col-12">
                                                                    <div class="form-group">
                                                                        <label>هدیه فرد معرفی‌کننده</label>
                                                                        <select name="user_referrals_gift_type" id="user_referrals_gift_type" class="form-control">
                                                                            <option value="discount_code" {{ option('user_referrals_gift_type') == "discount_code" ? 'selected' : '' }}>ارسال کد تخفیف</option>
                                                                            <option value="wallet" {{ option('user_referrals_gift_type') == "wallet" ? 'selected' : '' }}>ارسال به کیف پول</option>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 col-12">
                                                                    <div class="form-group">
                                                                        <label>نوع تخفیف</label>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                    <span class="input-group-text" id="discount_icon">
                                                                        <i class="fa fa-percent"></i>
                                                                    </span>
                                                                            </div>
                                                                            <select id="user_referrals_gift_discount_type" class="form-control" name="user_referrals_gift_discount_type">
                                                                                <option value="percent" {{ option('user_referrals_gift_discount_type') == "percent" ? 'selected' : '' }}>درصد</option>
                                                                                <option value="amount" {{ option('user_referrals_gift_discount_type') == "amount" ? 'selected' : '' }}>مبلغ</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>


                                                        <div class="col-md-6">

                                                            <div class="sub-divider">مقدار تخفیف</div>
                                                            <div class="row">
                                                                <div class="col-md-6 col-12">
                                                                    <div class="form-group">
                                                                        <label class="discount-label"
                                                                               data-percent="مقدار تخفیف معرفی‌کننده به درصد"
                                                                               data-amount-discount="مقدار تخفیف معرفی‌کننده به مبلغ ({{ currencyTitle() }})"
                                                                               data-amount-wallet="مقدار مبلغ معرفی‌کننده ({{ currencyTitle() }})">
                                                                            مقدار تخفیف معرفی‌کننده به درصد
                                                                        </label>
                                                                        <input type="number" name="owner_referrals_amount" class="form-control" min="0" value="{{option('owner_referrals_amount')}}">
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6 col-12">
                                                                    <div class="form-group">
                                                                        <label class="discount-label"
                                                                               data-percent="مقدار تخفیف معرفی‌شونده به درصد"
                                                                               data-amount-discount="مقدار تخفیف معرفی‌شونده به مبلغ ({{ currencyTitle() }})"
                                                                               data-amount-wallet="مقدار مبلغ معرفی‌شونده ({{ currencyTitle() }})">
                                                                            مقدار تخفیف معرفی‌شونده به درصد
                                                                        </label>
                                                                        <input type="number" name="user_referrals_amount" class="form-control" min="0" value="{{option('user_referrals_amount')}}">
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>

                                                    </div>


                                                    <div id="conditions_gift">
                                                        <div class="sub-divider">شرایط دریافت هدیه</div>
                                                        <div class="row">
                                                            <div class="col-md-3 col-12">
                                                                <div class="form-group">
                                                                    <label>حداقل مبلغ خرید برای دریافت هدیه ({{ currencyTitle() }})</label>
                                                                    <input type="number" name="minimum_amount_gift" class="form-control" min="0" value="{{ option('minimum_amount_gift', 0) }}">
                                                                </div>
                                                            </div>

                                                            <div class="col-md-3 col-12">
                                                                <div class="form-group">
                                                                    <label>حداقل تعداد محصول برای دریافت هدیه</label>
                                                                    <input type="number" name="minimum_product_gift" class="form-control" min="1" max="10" value="{{ option('minimum_product_gift', 1) }}">
                                                                    <div class="hint-text"><i class="feather icon-info"></i> بین ۱ تا ۱۰ محصول.</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                            </div>
                                        </div>

                                        {{-- ====================================================== --}}
                                        {{-- 4) Image settings                                          --}}
                                        {{-- ====================================================== --}}
                                        <div class="settings-section acc-images">
                                            <div class="section-heading">
                                                <span class="icon-wrap"><i class="feather icon-image"></i></span>
                                                <div>
                                                    <h4>تنظیمات تصاویر</h4>
                                                    <small>بهینه‌سازی، فرمت خروجی و واترمارک تصاویر</small>
                                                </div>
                                            </div>
                                            <div class="section-body">
                                                <div class="sub-divider">بهینه‌سازی و فرمت</div>
                                                <div class="row">
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>درصد بهینه‌سازی و کاهش حجم (۱ تا ۹۹)</label>
                                                            <input type="text" name="optimizeImage" class="form-control ltr" value="{{ option('optimizeImage', '10') }}">
                                                            <div class="hint-text"><i class="feather icon-info"></i> عدد بالاتر = حجم کمتر و کیفیت پایین‌تر.</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>تبدیل تصاویر به</label>
                                                            <select name="changePhotoFormat" class="form-control">
                                                                <option value="webp" {{ option('changePhotoFormat', 'webp') == 'webp' ? 'selected' : '' }}>webp</option>
                                                                <option value="jpg" {{ option('changePhotoFormat') == 'jpg' ? 'selected' : '' }}>jpg</option>
                                                                <option value="png" {{ option('changePhotoFormat') == 'png' ? 'selected' : '' }}>png</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="sub-divider">واترمارک</div>
                                                <div class="row">
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>وضعیت واترمارک</label>
                                                            <select name="watermarkStatus" class="form-control">
                                                                <option value="true" {{ option('watermarkStatus') == 'true' ? 'selected' : '' }}>فعال</option>
                                                                <option value="false" {{ option('watermarkStatus') == 'false' ? 'selected' : '' }}>غیرفعال</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <fieldset class="form-group">
                                                            <label>تصویر واترمارک</label>
                                                            <div class="custom-file">
                                                                <input type="file" accept="image/*" name="watermarkImage" class="custom-file-input">
                                                                <label class="custom-file-label">{{ option('watermarkImage') ?: 'انتخاب فایل…' }}</label>
                                                            </div>
                                                        </fieldset>
                                                    </div>

                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>موقعیت واترمارک</label>
                                                            <select name="watermarkImagePosition" class="form-control">
                                                                <option value="top-left"     {{ option('watermarkImagePosition') == 'top-left' ? 'selected' : '' }}>بالا چپ</option>
                                                                <option value="top"          {{ option('watermarkImagePosition') == 'top' ? 'selected' : '' }}>بالا وسط</option>
                                                                <option value="top-right"    {{ option('watermarkImagePosition') == 'top-right' ? 'selected' : '' }}>بالا راست</option>
                                                                <option value="left"         {{ option('watermarkImagePosition') == 'left' ? 'selected' : '' }}>وسط چپ</option>
                                                                <option value="center"       {{ option('watermarkImagePosition') == 'center' ? 'selected' : '' }}>وسط</option>
                                                                <option value="right"        {{ option('watermarkImagePosition') == 'right' ? 'selected' : '' }}>وسط راست</option>
                                                                <option value="bottom-left"  {{ option('watermarkImagePosition') == 'bottom-left' ? 'selected' : '' }}>پایین چپ</option>
                                                                <option value="bottom"       {{ option('watermarkImagePosition') == 'bottom' ? 'selected' : '' }}>پایین وسط</option>
                                                                <option value="bottom-right" {{ option('watermarkImagePosition') == 'bottom-right' ? 'selected' : '' }}>پایین راست</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ====================================================== --}}
                                        {{-- 5) قابلیت‌های فروشگاه (فاز ۸۲ — از تنظیمات قالب به تنظیمات سایت منتقل شد) --}}
                                        {{-- ====================================================== --}}
                                        <div class="settings-section acc-features">
                                            <div class="section-heading">
                                                <span class="icon-wrap"><i class="feather icon-trending-down"></i></span>
                                                <div>
                                                    <h4>قابلیت‌های فروشگاه</h4>
                                                    <small>هشدار کاهش قیمت و پیش‌فروش (شخصی‌سازی متن و آیکون)</small>
                                                </div>
                                            </div>
                                            <div class="section-body">
                                                <div class="sub-divider">هشدار کاهش قیمت</div>
                                                <div class="row">
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>قابلیت هشدار کاهش قیمت</label>
                                                            <select name="dt_price_alert_status" class="form-control">
                                                                <option value="1" {{ option('dt_price_alert_status', '1') == '1' ? 'selected' : '' }}>فعال</option>
                                                                <option value="0" {{ option('dt_price_alert_status', '1') != '1' ? 'selected' : '' }}>غیرفعال</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>متن دکمه هشدار کاهش قیمت</label>
                                                            <input type="text" name="dt_price_alert_button_text" class="form-control" value="{{ option('dt_price_alert_button_text', 'هشدار کاهش قیمت') }}" placeholder="هشدار کاهش قیمت">
                                                            <div class="hint-text"><i class="feather icon-info"></i> خالی بماند = پیش‌فرض «هشدار کاهش قیمت».</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5 col-12">
                                                        <div class="form-group">
                                                            <label>آیکون دکمه هشدار کاهش قیمت (کلاس MDI)</label>
                                                            <input type="text" name="dt_price_alert_button_icon" class="form-control ltr" value="{{ option('dt_price_alert_button_icon', 'mdi mdi-bell-ring-outline') }}" placeholder="mdi mdi-bell-ring-outline">
                                                            <div class="hint-text"><i class="feather icon-info"></i> خالی بماند = پیش‌فرض «mdi mdi-bell-ring-outline».</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- فاز ۸۳ — شخصی‌سازی کامل (هم‌قرارداد با پنل جدید) --}}
                                                <div class="row">
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>رنگ دکمه</label>
                                                            <select name="dt_price_alert_button_color" class="form-control">
                                                                @foreach(['red' => 'قرمز', 'orange' => 'نارنجی', 'green' => 'سبز', 'teal' => 'فیروزه‌ای', 'purple' => 'بنفش', 'dark' => 'سرمه‌ای'] as $cKey => $cLabel)
                                                                    <option value="{{ $cKey }}" {{ option('dt_price_alert_button_color', 'red') === $cKey ? 'selected' : '' }}>{{ $cLabel }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>اندازه دکمه</label>
                                                            <select name="dt_price_alert_button_size" class="form-control">
                                                                <option value="sm" {{ option('dt_price_alert_button_size', 'md') === 'sm' ? 'selected' : '' }}>کوچک</option>
                                                                <option value="md" {{ option('dt_price_alert_button_size', 'md') === 'md' ? 'selected' : '' }}>متوسط</option>
                                                                <option value="lg" {{ option('dt_price_alert_button_size', 'md') === 'lg' ? 'selected' : '' }}>بزرگ</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>گردی گوشه‌ها (۰ تا ۳۰ پیکسل)</label>
                                                            <input type="number" name="dt_price_alert_button_radius" min="0" max="30" class="form-control ltr fa-digits" value="{{ option('dt_price_alert_button_radius', '10') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>موقعیت دکمه</label>
                                                            {{-- فاز ۸۴ — دو گزینهٔ جدید: شناور در گوشهٔ صفحه + تگ دلخواه --}}
                                                            <select name="dt_price_alert_button_placement" class="form-control">
                                                                <option value="below-cart" {{ option('dt_price_alert_button_placement', 'below-cart') === 'below-cart' ? 'selected' : '' }}>زیر دکمهٔ خرید</option>
                                                                <option value="above-cart" {{ option('dt_price_alert_button_placement', 'below-cart') === 'above-cart' ? 'selected' : '' }}>بالای دکمهٔ خرید</option>
                                                                <option value="under-price" {{ option('dt_price_alert_button_placement', 'below-cart') === 'under-price' ? 'selected' : '' }}>چسبیده به قیمت</option>
                                                                <option value="float" {{ option('dt_price_alert_button_placement', 'below-cart') === 'float' ? 'selected' : '' }}>شناور در گوشهٔ صفحه</option>
                                                                <option value="custom-selector" {{ option('dt_price_alert_button_placement', 'below-cart') === 'custom-selector' ? 'selected' : '' }}>در تگ دلخواه (سلکتور)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>موقعیت دکمهٔ شناور (برای حالت «شناور در گوشهٔ صفحه»)</label>
                                                            <select name="dt_price_alert_float_pos" class="form-control">
                                                                @foreach(['top-left' => 'بالا-چپ', 'top-center' => 'بالا-وسط', 'top-right' => 'بالا-راست', 'middle-left' => 'وسط-چپ', 'middle-center' => 'وسط صفحه', 'middle-right' => 'وسط-راست', 'bottom-left' => 'پایین-چپ', 'bottom-center' => 'پایین-وسط', 'bottom-right' => 'پایین-راست'] as $fpKey => $fpLabel)
                                                                    <option value="{{ $fpKey }}" {{ option('dt_price_alert_float_pos', 'bottom-right') === $fpKey ? 'selected' : '' }}>{{ $fpLabel }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div class="form-group">
                                                            <label>سلکتورهای تزریق (برای حالت «در تگ دلخواه» — هر خط یک سلکتور CSS)</label>
                                                            @php($paSelectorsRaw = json_decode((string) option('dt_price_alert_selectors', '[]'), true))
                                                            <textarea name="dt_price_alert_selectors" rows="3" class="form-control ltr" placeholder=".product-seller-row>.product-seller-row-price">{{ is_array($paSelectorsRaw) ? implode("\n", $paSelectorsRaw) : '' }}</textarea>
                                                            <div class="hint-text"><i class="feather icon-info"></i> هر خط یک کلاس/آیدی؛ از هر سلکتور فقط اولین عنصرِ منطبق دکمه می‌سازد؛ اگر هیچ‌کدام پیدا نشود، دکمه زیر دکمهٔ خرید نمایش داده می‌شود.</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div class="form-group">
                                                            <label>عنوان مودال</label>
                                                            <input type="text" name="dt_price_alert_modal_title" class="form-control" value="{{ option('dt_price_alert_modal_title') }}" placeholder="هشدار کاهش قیمت">
                                                            <div class="hint-text"><i class="feather icon-info"></i> خالی بماند = پیش‌فرض «هشدار کاهش قیمت».</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-12">
                                                        <div class="form-group">
                                                            <label>راه‌های اطلاع‌رسانی مجاز</label>
                                                            <select name="dt_price_alert_channels" class="form-control">
                                                                <option value="sms,profile" {{ option('dt_price_alert_channels', 'sms,profile') === 'sms,profile' ? 'selected' : '' }}>پیامک + اعلان پروفایل</option>
                                                                <option value="sms" {{ option('dt_price_alert_channels', 'sms,profile') === 'sms' ? 'selected' : '' }}>فقط پیامک</option>
                                                                <option value="profile" {{ option('dt_price_alert_channels', 'sms,profile') === 'profile' ? 'selected' : '' }}>فقط اعلان پروفایل</option>
                                                            </select>
                                                            <div class="hint-text"><i class="feather icon-info"></i> مشتری در مودال انتخاب می‌کند؛ مهمان فقط پیامک می‌گیرد.</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="form-group">
                                                            <label>متن توضیح مودال</label>
                                                            <textarea name="dt_price_alert_modal_desc" rows="2" class="form-control" placeholder="به‌محض اینکه قیمت این محصول کمتر شد، همان لحظه به شما خبر می‌دهیم.">{{ option('dt_price_alert_modal_desc') }}</textarea>
                                                            <div class="hint-text"><i class="feather icon-info"></i> خالی بماند = متن پیش‌فرض توضیح قابلیت.</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="sub-divider">پیش‌فروش</div>
                                                <div class="row">
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>قابلیت پیش‌فروش محصولات</label>
                                                            <select name="dt_pre_order_status" class="form-control">
                                                                <option value="1" {{ option('dt_pre_order_status', '1') == '1' ? 'selected' : '' }}>فعال</option>
                                                                <option value="0" {{ option('dt_pre_order_status', '1') != '1' ? 'selected' : '' }}>غیرفعال</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>متن دکمه پیش‌فروش</label>
                                                            <input type="text" name="dt_pre_order_button_text" class="form-control" value="{{ option('dt_pre_order_button_text', 'ثبت پیش‌فروش') }}" placeholder="ثبت پیش‌فروش">
                                                            <div class="hint-text"><i class="feather icon-info"></i> خالی بماند = پیش‌فرض «ثبت پیش‌فروش».</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5 col-12">
                                                        <div class="form-group">
                                                            <label>متن نشان پیش‌فروش (کارت‌ها، صفحه محصول، سبد و سفارش‌ها)</label>
                                                            <input type="text" name="dt_pre_order_badge_text" class="form-control" value="{{ option('dt_pre_order_badge_text', 'پیش‌فروش') }}" placeholder="پیش‌فروش">
                                                            <div class="hint-text"><i class="feather icon-info"></i> خالی بماند = پیش‌فرض «پیش‌فروش».</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ====================================================== --}}
                                        {{-- 5) Pusher settings                                         --}}
                                        {{-- ====================================================== --}}
                                        <div class="settings-section acc-pusher">
                                            <div class="section-heading">
                                                <span class="icon-wrap"><i class="feather icon-radio"></i></span>
                                                <div>
                                                    <h4>تنظیمات Pusher</h4>
                                                    <small>پیکربندی سرویس ارسال اعلان‌های آنی</small>
                                                </div>
                                            </div>
                                            <div class="section-body">
                                                <div class="row">
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>PUSHER_APP_ID</label>
                                                            <input type="text" name="PUSHER_APP_ID" class="form-control ltr" value="{{ config('broadcasting.connections.pusher.app_id') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>PUSHER_APP_KEY</label>
                                                            <input type="text" name="PUSHER_APP_KEY" class="form-control ltr" value="{{ config('broadcasting.connections.pusher.key') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>PUSHER_APP_SECRET</label>
                                                            <input type="password" name="PUSHER_APP_SECRET" class="form-control ltr" value="{{ config('broadcasting.connections.pusher.secret') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-12">
                                                        <div class="form-group">
                                                            <label>PUSHER_APP_CLUSTER</label>
                                                            <input type="text" name="PUSHER_APP_CLUSTER" class="form-control ltr" value="{{ config('broadcasting.connections.pusher.options.cluster') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ====================================================== --}}
                                        {{-- 6) Mail / SMTP settings                                    --}}
                                        {{-- ====================================================== --}}
                                        <div class="settings-section acc-mail">
                                            <div class="section-heading">
                                                <span class="icon-wrap"><i class="feather icon-mail"></i></span>
                                                <div>
                                                    <h4>تنظیمات Mail</h4>
                                                    <small>اطلاعات سرور ارسال ایمیل (SMTP)</small>
                                                </div>
                                            </div>
                                            <div class="section-body">
                                                <div class="row">
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>Transport</label>
                                                            <input type="text" name="transport" class="form-control ltr" value="{{ config('mail.mailers.smtp.transport') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>MAIL_HOST</label>
                                                            <input type="text" name="MAIL_HOST" class="form-control ltr" value="{{ config('mail.mailers.smtp.host') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>MAIL_PORT</label>
                                                            <input type="text" name="MAIL_PORT" class="form-control ltr" value="{{ config('mail.mailers.smtp.port') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>MAIL_USERNAME</label>
                                                            <input type="text" name="MAIL_USERNAME" class="form-control ltr" value="{{ config('mail.mailers.smtp.username') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>MAIL_PASSWORD</label>
                                                            <input type="password" name="MAIL_PASSWORD" class="form-control ltr" value="{{ config('mail.mailers.smtp.password') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-12">
                                                        <div class="form-group">
                                                            <label>MAIL_ENCRYPTION</label>
                                                            <input type="text" name="MAIL_ENCRYPTION" class="form-control ltr" value="{{ config('mail.mailers.smtp.encryption') }}">
                                                            <div class="hint-text"><i class="feather icon-info"></i> معمولاً tls یا ssl.</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ====================================================== --}}
                                        {{--  Save bar                                                  --}}
                                        {{-- ====================================================== --}}
                                        <div class="save-bar">
                                            <div class="save-note">
                                                <i class="feather icon-shield"></i>
                                                تغییرات پس از ذخیره‌سازی روی کل سایت اعمال می‌شوند.
                                            </div>
                                            <button type="submit" class="btn btn-primary glow btn-save">
                                                <i class="feather icon-save mr-50 align-middle"></i>
                                                ذخیره تغییرات
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- users edit ends -->

            </div>
        </div>
    </div>

@endsection

@include('back.partials.plugins', ['plugins' => ['jquery.validate']])

@push('scripts')
    <script src="{{ asset('back/assets/js/pages/settings/others.js') }}"></script>
@endpush

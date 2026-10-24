{{-- بارگذاری مدارک — مرحله ۲ (فاز G)
    آپلود فوری: POST seller.registration_documents_store?imageFor=... → حذف: DELETE ..._delete
    ثبت نهایی: POST seller.registration_documents_check → JSON {status, redirect} --}}
@extends('front::sellers.auth.layouts.master', ['title' => 'بارگذاری مدارک', 'auth_step' => 2, 'auth_card_class' => 'col-lg-6'])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-0 bg-white seller-auth-card">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">بارگذاری مدارک</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <form id="seller-register-documents"
              action="{{ route('seller.registration_documents_store') }}"
              data-delete-url="{{ route('seller.registration_documents_delete') }}"
              data-check-url="{{ route('seller.registration_documents_check') }}"
              method="post" novalidate>
            @csrf

            {{-- ===== مالیات بر ارزش افزوده ===== --}}
            <h6 class="fw-bold mb-2"><i class="bi bi-percent main-color-one-color ms-1"></i>مالیات بر ارزش افزوده</h6>
            <p class="font-13 text-muted mb-3">
                آیا مشمول مالیات بر ارزش افزوده می‌باشید؟ توجه نمایید برگه ارزش افزوده حقیقی باید به نام شخص ثبت‌نام‌کننده باشد.
            </p>
            <div class="row g-2 mb-4" role="radiogroup" aria-label="مشمول مالیات بر ارزش افزوده">
                <div class="col-6">
                    <label class="seller-radio-card" data-vat="yes">
                        <input type="radio" name="vat_free" id="vat_free_1" value="1">
                        <span class="fw-bold font-14">بلی</span>
                    </label>
                </div>
                <div class="col-6">
                    <label class="seller-radio-card is-selected" data-vat="no">
                        <input type="radio" name="vat_free" id="vat_free_2" value="2" checked>
                        <span class="fw-bold font-14">خیر</span>
                    </label>
                </div>
            </div>

            {{-- گواهی ارزش افزوده (فقط بلی) --}}
            <div id="vat_free_yes" class="d-none mb-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-cloud-arrow-up main-color-one-color ms-1"></i>تصویر گواهی ارزش افزوده</h6>
                <div class="seller-upload-box" data-image-for="vat_image">
                    <i class="bi bi-file-earmark-image upload-icon"></i>
                    <p class="upload-hint mb-1 mt-2">برای بارگذاری کلیک کنید (jpg/png)</p>
                    <span class="upload-filename d-none"></span>
                    <img src="" alt="" class="upload-preview d-none">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-2 d-none seller-upload-remove" aria-label="حذف تصویر"></button>
                    <input type="file" name="vat_image" accept="image/*" aria-label="تصویر گواهی ارزش افزوده">
                </div>
                <p class="font-13 text-muted mt-2 mb-0">
                    لطفا پس از وارد کردن شماره ملی در بخش «بررسی ثبت‌نام مؤدیان مالیات» در
                    <a href="https://www.e-vat.ir/frmNewvalidationofregistration.aspx" target="_blank" rel="noopener" class="main-color-one-color">سامانه الکترونیک مالیات بر ارزش افزوده</a>،
                    از آن صفحه تصویر (اسکرین‌شات) گرفته و اینجا بارگذاری کنید.
                </p>
            </div>

            {{-- ===== تصویر کارت ملی ===== --}}
            <div id="vat_free_no">
                <h6 class="fw-bold mb-2"><i class="bi bi-person-badge main-color-one-color ms-1"></i>تصویر کارت ملی</h6>
                <ul class="font-13 text-muted mb-3 pe-3">
                    <li>تصاویر باید صاف و کاملاً واضح باشد.</li>
                    <li>تصویر رو و پشت کارت ملی باید به‌طور جداگانه بارگذاری شود.</li>
                    <li>برای شخص حقوقی، تصویر کارت ملی همه صاحبان حق امضا در قالب یک عکس بارگذاری شود.</li>
                </ul>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="font-14 fw-bold text-muted d-block mb-1">تصویر روی کارت ملی</label>
                    <div class="seller-upload-box" data-image-for="card_image">
                        <i class="bi bi-id-card upload-icon"></i>
                        <p class="upload-hint mb-1 mt-2">برای بارگذاری کلیک کنید</p>
                        <span class="upload-filename d-none"></span>
                        <img src="" alt="" class="upload-preview d-none">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-2 d-none seller-upload-remove" aria-label="حذف تصویر"></button>
                        <input type="file" name="card_image" accept="image/*" aria-label="تصویر روی کارت ملی">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="font-14 fw-bold text-muted d-block mb-1">تصویر پشت کارت ملی</label>
                    <div class="seller-upload-box" data-image-for="card_image_back">
                        <i class="bi bi-id-card upload-icon"></i>
                        <p class="upload-hint mb-1 mt-2">برای بارگذاری کلیک کنید</p>
                        <span class="upload-filename d-none"></span>
                        <img src="" alt="" class="upload-preview d-none">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-2 d-none seller-upload-remove" aria-label="حذف تصویر"></button>
                        <input type="file" name="card_image_back" accept="image/*" aria-label="تصویر پشت کارت ملی">
                    </div>
                </div>
            </div>

            <div class="auth-form-errors is-empty" role="alert"></div>

            <div class="form-group">
                <button type="submit" class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    ثبت نهایی و ادامه
                    <i class="bi bi-check2 ms-1"></i>
                </button>
            </div>
        </form>
    </div>
@endsection

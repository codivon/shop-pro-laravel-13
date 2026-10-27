@extends('sellers-old.auth.layouts', ['title' => 'اطلاعات فروشنده'])

@push('styles')
    <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">
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

                        <div class="c-reg-steps__icon c-reg-steps__icon--documents c-reg-steps__icon--current">
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
                <div class="new-login_seller_main">
            <div class=" c-reg-form--full">
                <div class="uk-hidden js-userid-data" data-userid="1089545"></div>
                <form method="post" id="seller-register-documents" action="{{route('seller.registration_documents_store')}}" deta-delete="{{route('seller.registration_documents_delete')}}" data-check="{{route('seller.registration_documents_check')}}" novalidate="novalidate">
                    @csrf
                    <div class="c-reg-form__row">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <h2 class="c-reg-form__header">مالیات بر ارزش افزوده</h2>
                        </div>
                    </div>

                    <div class="c-reg-form__row c-reg-form__row--gap-20">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <label class="c-reg-form__text">آیا مشمول مالیات بر ارزش افزوده می باشید؟<br>توجه نمایید برگه ارزش افزوده حقیقی باید به نام شخص ثبت نام کننده باشد</label>
                        </div>
                        <div class="c-reg-form__col--12">
                            <div class=" checkout-time-table-time d-flex c-reg-form__row">

                                <div class="radio-box custom-control custom-radio form-group mb-0 pl-0 pr-3 ">
                                    <input type="radio" class="custom-control-input form-control" name="vat_free" id="vat_free_1" value="1">
                                    <label for="vat_free_1" class="custom-control-label">
                                        <span class="checkout-time-table-title-bar mt-2 d-inline-block">بلی</span>
                                    </label>
                                </div>

                                <div class="radio-box custom-control custom-radio form-group mb-0 pl-0 pr-3">
                                    <input type="radio" class="custom-control-input form-control" name="vat_free" id="vat_free_2" value="2" checked>
                                    <label for="vat_free_2" class="custom-control-label">
                                        <span class="checkout-time-table-title-bar mt-2 d-inline-block">خیر</span>
                                    </label>
                                </div>

                            </div>
                    </div>

                    <div id="vat_free_yes" class="c-reg-form__row c-reg-form__row--gap-50 vat-free-fields d-none">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <h2 class="c-reg-form__header">بارگذاری مدارک</h2>
                        </div>
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <p class="c-reg-form__text has-vat uk-hidden">تصویر گواهی ارزش افزوده</p>
                        </div>
                        <div class="c-reg-form__col c-reg-form__col--12">

                            <div class="c-ui-upload  form-group" id="card-vat-image" >
                                <label class="c-ui-upload__load upload__origin_vat_image">
                                    <input name="vat_image" type="file">
                                </label>
                            </div>

                        </div>

                        <p class="c-reg-form__text">
                            فروشنده عزیز، لطفا پس از وارد کردن شماره ملی در بخش «بررسی ثبت نام مؤدیان مالیات» در <a href=" https://www.e-vat.ir/frmNewvalidationofregistration.aspx" target="_blank"> سامانه الکترونیک مالیات بر ارزش افزوده</a> ، از آن صفحه اسکرین شات گرفته و در محل گواهی ارزش افزوده، تصویر آن صفحه را بارگذاری کنید.
                        </p>
                    </div>


                    <div id="vat_free_no" class="c-reg-form__col--12 mt-5">
                        <div class="c-reg-form__col c-reg-form__col--12">
                            <p class="c-reg-form__text">تصویر کارت ملی</p>
                            <ul class="c-reg-form__list">
                                <li class="c-reg-form__list-item">- تصاویر باید صاف و کاملاً واضح باشد.</li>
                                <li class="c-reg-form__list-item">- تصویر رو و پشت کارت ملی باید به طور جداگانه بارگذاری شود.</li>
                                <li class="c-reg-form__list-item">- تصویر کارت ملی همه صاحبان حق امضا در قالب یک عکس در کنار هم قرار گرفته و بارگذاری شود.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="c-reg-form__col--12  mt-4">
                        <div class="c-reg-form__col c-reg-form__col--12 w-65">


                            <div class="c-ui-upload form-group" id="card-image-file" >
                                <label class="c-ui-upload__load upload__origin_card_image ">
                                        <input name="card_image" type="file">
                                </label>
                            </div>

                        </div>
                    </div>

                    <div class="c-reg-form__col--12 mt-4">
                        <div class="c-reg-form__col c-reg-form__col--12 w-65">


                            <div class="c-ui-upload form-group" id="card-image-back-file">
                                <label class="c-ui-upload__load upload__origin_card_image_back ">
                                    <input name="card_image_back" type="file">
                                </label>
                            </div>

                        </div>
                    </div>

                    </div>
                    <div class="col-md-4 float-left">
                        <div class="parent-btn mt-0 w-100">
                            <button class="btn-store-documents dk-btn dk-btn-info">
                                ثبت نهایی
                                <i class="fa fa-check sign-in"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>


        </div>
            </div>
        </div>
    </div>



@endsection
@push('scripts')
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-resize/dist/filepond-plugin-image-resize.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-transform/dist/filepond-plugin-image-transform.js"></script>

    <script src="{{asset('back/app-assets/plugins/filepond/filepond-plugin-image-preview.js')}}"></script>
    <script src="{{asset('back/app-assets/plugins/filepond/filepond.js')}}"></script>
    <script src="{{ theme_asset('js/pages/sellers/register/documents.js') }}"></script>

@endpush

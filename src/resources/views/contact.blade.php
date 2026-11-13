@extends('front::layouts.master', ['title' => 'تماس با ما'])

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('mapp/css/mapp.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('mapp/css/fa/style.css') }}">
@endpush

@push('meta')
    <link rel="canonical" href="{{ route('front.contact.index') }}" />
@endpush

@section('content')
    {{-- متغیرهای نقشه برای js/pages/blog/contact-page-vars.js (بدون اسکریپت اینلاین) --}}
    <div id="contact-page-vars" class="d-none"
         data-map-type="{{ option('info_map_type', 'google') }}"
         data-latitude="{{ option('info_latitude', '38.07709880960678') }}"
         data-longitude="{{ option('info_Longitude', '46.28582686185837') }}"
         data-site-title="{{ option('info_site_title', 'او پی شاپ') }}"
         data-mapir-key="{{ option('map_api') }}"></div>

    {{-- تماس با ما — سانیار content-box --}}
    <div class="content">
        <div class="container-fluid py-3">
            <div class="content-box">
                <div class="container-fluid">

                    <div class="mb-5 title-panel">
                        <h2>تماس با ما</h2>
                    </div>

                    <div class="mb-4">
                        {!! option('dt_show_contact_top_description') !!}
                    </div>

                    <div class="row gy-3">
                        @if (option('dt_show_form_in_contact') == 'yes')
                            <div class="{{ option('dt_show_map_in_contact') != 'no' ? 'col-lg-6' : 'col-lg-12' }}">
                                <div class="comment-form">
                                    <div class="title-panel mb-3">
                                        <h6 class="font-18">پیام خود را با ما در میان بگذارید</h6>
                                    </div>
                                    <form id="contact-form" class="contact-us-form" action="{{ route('front.contact.store') }}" method="POST">
                                        <div class="contact-us-form-body">
                                            <div class="form-group mb-2">
                                                <label for="name" class="form-label">
                                                    نام و نام‌خانوادگی
                                                    <span class="text-danger ms-1">*</span>
                                                </label>
                                                <input type="text" id="name" class="ui-input-field form-control" name="name">
                                            </div>
                                            <div class="form-group mb-2">
                                                <label for="email" class="form-label">
                                                    ایمیل
                                                    <span class="text-danger ms-1">*</span>
                                                </label>
                                                <input type="text" id="email" name="email" class="ui-input-field form-control text-end">
                                            </div>
                                            <div class="form-group mb-2">
                                                <label for="subject" class="form-label">
                                                    موضوع
                                                    <span class="text-danger ms-1">*</span>
                                                </label>
                                                <input type="text" id="subject" name="subject" class="ui-input-field form-control">
                                            </div>

                                            <div class="form-group mb-2">
                                                <label for="message" class="form-label">
                                                    متن پیام
                                                    <span class="text-danger ms-1">*</span>
                                                </label>
                                                <textarea name="message" id="message" cols="30" rows="7"
                                                          class="ui-textarea-field form-control"></textarea>
                                            </div>

                                            <x-captcha />

                                            <div class="mt-4">
                                                <button type="submit" class="contact-us-form-submit btn main-color-three-bg border-0 px-5 rounded-3">ثبت و ارسال</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        @if (option('dt_show_map_in_contact') == 'yes')
                            <div class="{{ option('dt_show_form_in_contact') == 'yes' ? 'col-lg-6' : 'col-lg-12' }}">
                                <div class="rounded-3 overflow-hidden shadow-box">
                                    <div id="map"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if (option('dt_show_contact_bottom_description') == 'yes')
                        <hr class="info-page-separator">
                        <div class="mt-3">
                            {!! option('dt_contact_bottom_description') !!}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    @if(option('info_map_type')=='mapir')
        <script type="text/javascript" src="{{ theme_asset('mapp/js/mapp.env.js') }}"></script>
        <script type="text/javascript" src="{{ theme_asset('mapp/js/mapp.min.js?v=1') }}"></script>
    @elseif(option('info_map_type')=='google')
        {{-- فاز ۱۳۹ — defer: اسکریپت گوگل‌مپ روی IP ایران چندثانیه پارس را قفل می‌کند؛ contact.js گارد typeof google دارد --}}
        <script src="https://maps.googleapis.com/maps/api/js?key={{ option('map_api') }}" defer></script>
    @endif

    <script src="{{ theme_asset('js/pages/blog/contact-page-vars.js') }}"></script>
    <script src="{{ theme_asset('js/pages/contact.js?v=1') }}"></script>
@endpush

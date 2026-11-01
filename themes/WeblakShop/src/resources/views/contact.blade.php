@extends('front::layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('mapp/css/mapp.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('mapp/css/fa/style.css') }}">
@endpush

@push('meta')
    <link rel="canonical" href="{{ route('front.contact.index') }}" />
@endpush

@section('content')
    <!--    about------------------------->
    <div class="col-12">
        <section class="contact-us">
            <div class="page-content-contact-us">
                <h1 class="page-content-contact-us-title">تماس باما</h1>
                <div class="page-content-contact-us-row">

                    <div class="page-content-contact-us-col-big">
                        <p>
                            {!! option('dt_show_contact_top_description') !!}
                        </p>


                        <div class="row">
                            @if (option('dt_show_form_in_contact') == 'yes')
                                <div class="{{option('dt_show_map_in_contact')!="no" ? 'col-lg-6' : 'col-lg-12'}}">
                                    <div class="page-content-contact-us-row-col">
                                        <form id="contact-form" class="contact-us-form" action="{{ route('front.contact.store') }}" method="POST">
                                            <div class="contact-us-form-body">
                                                <div class="form-legal-item form-group">
                                                    <label for="name" class="form-legal-label">
                                                        نام و نام‌خانوادگی
                                                        <span class="required-star" style="color:red;">*</span>
                                                    </label>
                                                    <input type="text" id="name" class="ui-input-field form-control" name="name">
                                                </div>
                                                <div class="form-legal-item form-group">
                                                    <label for="email" class="form-legal-label">
                                                        ایمیل
                                                        <span class="required-star" style="color:red;">*</span>
                                                    </label>
                                                    <input type="text" id="email" name="email" class="ui-input-field form-control">
                                                </div>
                                                <div class="form-group">
                                                    <label for="subject" class="form-legal-label">
                                                        موضوع
                                                        <span class="required-star" style="color:red;">*</span>
                                                    </label>
                                                    <input type="text" id="subject" name="subject" class="ui-input-field form-control">
                                                </div>


                                                <div class="form-legal-item legal-item-textarea form-group">
                                                    <label for="message" class="form-legal-label">
                                                        متن پیام
                                                        <span class="required-star" style="color:red;">*</span>
                                                    </label>
                                                    <textarea name="message" id="message" cols="30" rows="10"
                                                              class="ui-textarea-field form-control"></textarea>
                                                </div>

                                                <x-captcha />


                                                <div class="upload-drag-uploaded-and-submit mt-4">
                                                    <button class="contact-us-form-submit">ثبت و ارسال</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            @if (option('dt_show_map_in_contact') == 'yes')
                                <div class="col-lg-6">
                                    <div id="map"></div>
                                </div>
                            @endif
                        </div>

                    </div>


                    @if (option('dt_show_contact_bottom_description') == 'yes')
                        <hr class="info-page-separator">
                        {!! option('dt_contact_bottom_description') !!}
                    @endif
                </div>
            </div>
        </section>
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

    <script>
        var info_map_type = "{{ option('info_map_type', 'google') }}"
        var info_latitude = "{{ option('info_latitude', '38.07709880960678') }}";
        var info_Longitude = "{{ option('info_Longitude', '46.28582686185837') }}";
        var info_site_title = "{{ option('info_site_title', 'او پی شاپ') }}";

        var mapIrApiKey = '{{ option('map_api') }}';


    </script>

    <script src="{{ theme_asset('js/pages/contact.js?v=1') }}"></script>
@endpush

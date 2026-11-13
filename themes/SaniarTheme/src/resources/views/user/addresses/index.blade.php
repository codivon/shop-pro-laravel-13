@extends('front::user.layouts.master')
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/mapp.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/fa/style.css') }}">
    <link rel="stylesheet" href="{{theme_asset('css/map-selected-styles.css')}}" />
@endpush
@section('user-content')
    <div class="d-block">
        <div class="profile-content">
            <div class="headline-profile">
                <span>آدرس های من</span>

                    <span id="add-address-modal" class="add-address-link float-left cursor-pointer openMap" data-UpdateUrl="{{route('front.addresses.store')}}" data-toggle="modal" data-target="#add-edit-address-modal">افزودن آدرس جدید</span>


            </div>
            <div class="row">
                @if($addresses->count())
                    @foreach($addresses as $address)
                        <div class=" col-md-6 col-sm-12 col-xs-12">
                            <div class="profile-stats @if($address->active)active @endif">
                                <div class="profile-address">
                                    <div class="box-header">
                                        <span class="box-title">{{@$address->fullname}}</span>
                                    </div>
                                    <div class="profile-address-item cursor-pointer">
                                        <div class="profile-address-item-top">
                                            <div class="ui-more">
                                                <button class="btn-remove-address btn btn-danger favorite-remove-btn"  data-action="{{ route('front.addresses.destroy', ['address' => $address]) }}"  data-toggle="modal" data-target="#addresses-delete-modal">حذف</button>
                                            </div>
                                        </div>

                                        <div class="profile-address-content" >
                                            <ul class="profile-address-info m-0" data-action="{{route('front.addresses.active',$address)}}">
                                                <li>
                                                    <div class="profile-address-info-item location min-height-50">
                                                        <i class="mdi mdi-map-outline"></i>
                                                        {{$address->address}}
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="profile-address-info-item location">
                                                        <i class="mdi mdi-email-outline"></i>
                                                        {{$address->postal_code}}
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="profile-address-info-item location">
                                                        <i class="mdi mdi-phone"></i>
                                                        {{$address->mobile}}
                                                    </div>
                                                </li>
                                                <li class="d-flex">
                                                    <div class="profile-address-info-item location">
                                                        استان:
                                                        {{$address->province_name}}
                                                    </div>
                                                    <div class="profile-address-info-item location" style="margin-right: 20px;">
                                                        شهر:
                                                        {{$address->city_name}}
                                                    </div>
                                                </li>

                                            </ul>
                                            <ul>
                                                <li class="location-link">
                                                    <span class="edit-address-link cursor-pointer openMap" data-UpdateUrl="{{route('front.addresses.update',$address->id)}}" data-url="{{route('front.addresses.show',$address->id)}}" data-toggle="modal" data-target="#add-edit-address-modal">ویرایش
                                                        نشانی</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @endforeach
                @else


                <div class="profile-stats">
                <div class="row">
                    <div class="col-12">
                        <div class="page dt-sl dt-sn pt-3">
                            <p class="text-center">چیزی برای نمایش وجود ندارد!</p>
                        </div>
                    </div>
                </div>
                </div>
            @endif
        </div>
        </div>
    </div>

    <div class="pager pager-back-none">
        {{$addresses->links("pagination::bootstrap-4")}}
    </div>

    <input name="map_api" type="hidden" value="{{ option('map_api') }}">
@endsection

@push('scripts')

    <div class="modal fade" id="add-edit-address-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div id="showMap" class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        آدرس جدید
                        <div class="div-bottom-modal-title">موقعیت مکانی آدرس را مشخص کنید.</div>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">

                            <div class="form-ui dt-sl">
                                <form action="#" class="form-checkout">
                                    <div class="form-checkout-row ">
                                        @include('front::user.partials.map')
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-sm-between position-relative">
                    <p class="pt-4">مرسوله‌های شما به این موقعیت ارسال خواهد شد.</p>
                    <div class="form-checkout-valid-row">
                        <div class="parent-btn">
                            <button id="next-add-address-btn" class="dk-btn dk-btn-info disabled" disabled>
                                <i class="fa fa-check sign-in"></i>
                                تایید و ادامه

                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="more-information" class="modal-content d-none">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <a id="back-to-map" class="profile-navbar-btn-back">بازگشت</a>
                       <p> جزییات آدرس</p>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                </div>
                <div class="modal-body ">
                    <div class="row">
                        <div class="col-12 ">

                            <div class="form-ui dt-sl middle-container">
                                <form id="add-update-address-form" action="{{ route('front.addresses.store') }}" class="form-checkout setting_form" method="POST">
                                    @csrf

                                    <div class="more-information">

                                        <div class="form-checkout-row ">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label for="address">نشانی پستی
                                                        <span class="required-star" style="color:red;">*</span></label>
                                                    <textarea type="text" id="address" name="address" class="input-name-checkout mb-2"
                                                              placeholder="آدرس خود را وارد نمایید" style="height:80px;"></textarea>
                                                    <input type="hidden" id="lat" name="lat">
                                                    <input type="hidden" id="lng" name="lng">
                                                    <p class="add-address-bottom-text">آدرس بالا بر اساس موقعیت انتخابی شما وارد شده است.</p>
                                                </div>
                                            </div>
                                            <hr>
                                            @php
                                                $cities = [];
                                                $city_id = null;
                                            @endphp
                                            <div class="row">
                                                <div class="col-md-6 col-sm-12 col-xs-12">
                                                    <div class="form-checkout-valid-row">
                                                        <label for="province">استان <span class="required-star"
                                                                                          style="color:red;">*</span></label>
                                                        <select class="right" name="province_id" id="province">
                                                            <option value="date-desc" selected="selected">شهر مورد نظر خود را انتخاب کنید
                                                            </option>
                                                            @foreach($provinces as $item)
                                                                <option value="{{ $item->id }}" >{{ $item->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6 col-sm-12 col-xs-12">
                                                    <div class="form-checkout-valid-row w-100">
                                                        <label for="city">شهر
                                                            <span class="required-star" style="color:red;">*</span></label>
                                                        <select class="right" name="city_id" id="city">
                                                            <option value="date-desc" selected="selected">شهر مورد نظر خود را انتخاب کنید
                                                            </option>
                                                            @foreach($cities as $item)
                                                                <option value="{{ $item->id }}" >{{ $item->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 col-sm-12 col-xs-12">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <label for="buildingNumber">پلاک<span class="required-star"
                                                                                                  style="color:red;">*</span></label>
                                                            <input type="number" name="buildingNumber" id="buildingNumber" class="input-name-checkout"
                                                                   placeholder="پلاک">
                                                        </div>
                                                        <div class="col-6">
                                                            <label for="unit">واحد</label>
                                                            <input type="text" name="unit" id="unit" class="input-name-checkout"
                                                                   placeholder="واحد">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 col-sm-12 col-xs-12">
                                                    <label for="postalCode">کد پستی<span class="required-star"
                                                                                         style="color:red;">*</span></label>
                                                    <input type="text" name="postal_code" id="postalCode" class="input-name-checkout placeholder-right"
                                                           placeholder="کد‌پستی باید ۱۰ رقم و بدون خط تیره باشد.">
                                                </div>
                                            </div>

                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6 col-sm-12 col-xs-12">
                                                    <label for="name">نام و نام خانوادگی تحویل گیرنده <span class="required-star"
                                                                                                            style="color:red;">*</span></label>
                                                    <input type="text" id="name" name="fullname" class="input-name-checkout"
                                                           placeholder="نام تحویل گیرنده را وارد نمایید">
                                                </div>
                                                <div class="col-md-6 col-sm-12 col-xs-12">
                                                    <label for="phone-number">شماره موبایل <span class="required-star"
                                                                                                 style="color:red;">*</span></label>
                                                    <input type="text" id="phone-number" name="mobile" class="input-name-checkout" placeholder="09xxxxxxxxx"
                                                           style="text-align:left;direction: ltr">
                                                </div>
                                            </div>



                                            <div class="form-checkout-valid-row">
                                                <div class="parent-btn">
                                                    <button class="dk-btn dk-btn-info">
                                                        ثبت آدرس
                                                        <i class="fa fa-check sign-in"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <a class="cancel-edit-address cursor-pointer" data-dismiss="modal">انصراف و بازگشت</a>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="addresses-delete-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="now-ui-icons location_pin"></i>
                        حذف از لیست آدرس ها
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p>آیا تمایل به حذف این آدرس از لیست  آدرس ها دارید؟</p>

                            <div class="form-ui dt-sl">
                                <form id="favorite-remove-form" action="#" method="POST">
                                    <div class="modal-body text-center">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-danger btn-md">بله حذف شود</button>
                                        <button class="btn btn-light" data-dismiss="modal">لغو</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End favorite delete -->




@endpush
@push('scripts')


    <script src="{{ theme_asset('js/vendor/jquery.nice-select.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>

    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ theme_asset('js/pages/addresses/index.js') }}"></script>
    <script src="{{ theme_asset('js/pages/addresses/add-edit-address.js?v=2') }}"></script>

@endpush

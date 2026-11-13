@extends('front::user.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/mapp.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/mapir/css/fa/style.css') }}">
    <link rel="stylesheet" href="{{theme_asset('css/map-selected-styles.css')}}" />
@endpush

@section('user-content')

    {{-- آدرس های من — ریسکین‌شده با کامپوننت آدرس‌های قالب سانیار (address.html) --}}
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">آدرس ها</h4>

                        <span id="add-address-modal"
                              class="btn main-color-one-bg btn-sm waves-effect waves-light openMap cursor-pointer"
                              data-UpdateUrl="{{route('front.addresses.store')}}"
                              data-bs-toggle="modal" data-bs-target="#add-edit-address-modal">ثبت آدرس جدید <i class="bi bi-plus-circle text-white"></i></span>
                    </div>
                </div>
                <div class="ui-box-item-desc">
                    <div class="row">
                        @if($addresses->count())
                            @foreach($addresses as $address)
                                <div class="col-md-6 col-12">
                                    <div class="bg-white card addresses-item mb-4 shadow-sm @if($address->active) profile-stats active border-2 @else profile-stats @endif">
                                        <div class="p-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">{{@$address->fullname}}</h6>
                                                <button class="btn btn-danger btn-sm btn-remove-address favorite-remove-btn"
                                                        data-action="{{ route('front.addresses.destroy', ['address' => $address]) }}"
                                                        data-bs-toggle="modal" data-bs-target="#addresses-delete-modal">حذف</button>
                                            </div>

                                            <div class="profile-address-content">
                                                <ul class="profile-address-info list-unstyled m-0" data-action="{{route('front.addresses.active',$address)}}">
                                                    <li>
                                                        <div class="profile-address-info-item location d-flex align-items-start py-2">
                                                            <i class="bi bi-geo-alt main-color-one-color me-2"></i>
                                                            <span>{{$address->address}}</span>
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <div class="profile-address-info-item location d-flex align-items-center py-2">
                                                            <i class="bi bi-envelope main-color-one-color me-2"></i>
                                                            <span>{{$address->postal_code}}</span>
                                                        </div>
                                                    </li>
                                                    <li>
                                                        <div class="profile-address-info-item location d-flex align-items-center py-2">
                                                            <i class="bi bi-telephone main-color-one-color me-2"></i>
                                                            <span>{{$address->mobile}}</span>
                                                        </div>
                                                    </li>
                                                    <li class="d-flex">
                                                        <div class="profile-address-info-item location py-2">
                                                            استان:
                                                            {{$address->province_name}}
                                                        </div>
                                                        <div class="profile-address-info-item location py-2 ms-4">
                                                            شهر:
                                                            {{$address->city_name}}
                                                        </div>
                                                    </li>

                                                </ul>
                                                <ul class="list-unstyled mb-0 mt-2">
                                                    <li class="location-link">
                                                        <span class="edit-address-link cursor-pointer openMap btn btn-sm btn-outline-secondary"
                                                              data-UpdateUrl="{{route('front.addresses.update',$address->id)}}"
                                                              data-url="{{route('front.addresses.show',$address->id)}}"
                                                              data-bs-toggle="modal" data-bs-target="#add-edit-address-modal">ویرایش
                                                            نشانی</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @endforeach
                        @else
                            <div class="col-12">
                                <p class="text-center text-muted py-4">چیزی برای نمایش وجود ندارد!</p>
                            </div>
                        @endif
                    </div>

                    <div class="my-paginate mt-2 pb-2">
                        <nav aria-label="Page navigation">
                            {{$addresses->links("pagination::bootstrap-4")}}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input name="map_api" type="hidden" value="{{ option('map_api') }}">

@endsection

@push('scripts')

    {{-- مودال ثبت / ویرایش آدرس (نقشه + اطلاعات) — شناسه‌ها و نام فیلدها دست‌نخورده --}}
    <div class="modal fade" id="add-edit-address-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div id="showMap" class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        آدرس جدید
                        <div class="div-bottom-modal-title">موقعیت مکانی آدرس را مشخص کنید.</div>
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">

                            <form action="#" class="form-checkout">
                                <div class="form-checkout-row ">
                                    @include('front::user.partials.map')
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-sm-between position-relative">
                    <p class="pt-4">مرسوله‌های شما به این موقعیت ارسال خواهد شد.</p>
                    <div class="form-checkout-valid-row">
                        <div class="parent-btn">
                            <button id="next-add-address-btn" class="btn border-0 main-color-one-bg disabled" disabled>
                                <i class="bi bi-check-lg text-white"></i>
                                تایید و ادامه

                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="more-information" class="modal-content d-none">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <a id="back-to-map" class="profile-navbar-btn-back cursor-pointer">بازگشت</a>
                        <p> جزییات آدرس</p>
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                </div>
                <div class="modal-body ">
                    <div class="row">
                        <div class="col-12 ">

                            <div class="form-ui middle-container">
                                <form id="add-update-address-form" action="{{ route('front.addresses.store') }}" class="form-checkout setting_form" method="POST">
                                    @csrf

                                    <div class="more-information">

                                        <div class="form-checkout-row ">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-floating mb-3">
                                                        <textarea type="text" id="address" name="address" class="form-control float-input"
                                                                  placeholder="آدرس خود را وارد نمایید" style="height:80px;"></textarea>
                                                        <label for="address">نشانی پستی <span class="text-danger">*</span></label>
                                                    </div>
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
                                                <div class="col-md-6 col-12">
                                                    <div class="comment-item mb-3">
                                                        <label class="form-label label-float fw-bold" for="province">استان <span class="text-danger">*</span></label>
                                                        <select class="form-select" name="province_id" id="province">
                                                            <option value="date-desc" selected="selected">شهر مورد نظر خود را انتخاب کنید
                                                            </option>
                                                            @foreach($provinces as $item)
                                                                <option value="{{ $item->id }}" >{{ $item->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6 col-12">
                                                    <div class="comment-item mb-3">
                                                        <label class="form-label label-float fw-bold" for="city">شهر
                                                            <span class="text-danger">*</span></label>
                                                        <select class="form-select" name="city_id" id="city">
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
                                                <div class="col-md-6 col-12">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-floating mb-3">
                                                                <input type="number" name="buildingNumber" id="buildingNumber" class="form-control float-input"
                                                                       placeholder="پلاک">
                                                                <label for="buildingNumber">پلاک<span class="text-danger">*</span></label>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="form-floating mb-3">
                                                                <input type="text" name="unit" id="unit" class="form-control float-input"
                                                                       placeholder="واحد">
                                                                <label for="unit">واحد</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" name="postal_code" id="postalCode" class="form-control float-input"
                                                               placeholder="کد‌پستی باید ۱۰ رقم و بدون خط تیره باشد.">
                                                        <label for="postalCode">کد پستی<span class="text-danger">*</span></label>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" id="name" name="fullname" class="form-control float-input"
                                                               placeholder="نام تحویل گیرنده را وارد نمایید">
                                                        <label for="name">نام و نام خانوادگی تحویل گیرنده <span class="text-danger">*</span></label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 col-12">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" id="phone-number" name="mobile" class="form-control float-input" placeholder="09xxxxxxxxx"
                                                               style="text-align:left;direction: ltr">
                                                        <label for="phone-number">شماره موبایل <span class="text-danger">*</span></label>
                                                    </div>
                                                </div>
                                            </div>



                                            <div class="form-checkout-valid-row">
                                                <div class="parent-btn">
                                                    <button class="btn border-0 main-color-one-bg waves-effect waves-light">
                                                        ثبت آدرس
                                                        <i class="bi bi-check-lg text-white"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <a class="cancel-edit-address cursor-pointer" data-bs-dismiss="modal">انصراف و بازگشت</a>
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


    {{-- مودال حذف آدرس --}}
    <div class="modal fade" id="addresses-delete-modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-geo-alt"></i>
                        حذف از لیست آدرس ها
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p>آیا تمایل به حذف این آدرس از لیست  آدرس ها دارید؟</p>

                            <form id="favorite-remove-form" action="#" method="POST">
                                <div class="text-center">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-danger btn-md">بله حذف شود</button>
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">لغو</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/vendor/jquery.nice-select.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>

    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ theme_asset('js/pages/addresses/index.js') }}"></script>
    <script src="{{ theme_asset('js/pages/addresses/add-edit-address.js?v=2') }}"></script>

@endpush

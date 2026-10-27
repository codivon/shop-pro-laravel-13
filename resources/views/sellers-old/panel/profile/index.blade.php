@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">پروفایل </span>
                <span class="c-content-page__header-desc">برای ویرایش پروفایل یا تغییر رمز ورود خود از این قسمت استفاده نمایید.</span>
            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        <div class="row dashboard-profile ">
            <div class="col-12 dashboard-profile-item ">
                <div class="c-card">
                    <div class="c-card__header">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class=" nav-link menu-title active" data-toggle="tab" href="#profile">اطلاعات فروشنده</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link menu-title" data-toggle="tab" href="#address">تماس و آدرس</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link menu-title" data-toggle="tab" href="#bank">حساب بانکی</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link menu-title" data-toggle="tab" href="#documents"> مدارک</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link menu-title show-pass-tab" data-toggle="tab" href="#econtract">اطلاعات قرارداد</a>
                            </li>
                            <li class="nav-item left-0">
                                <a class="nav-link menu-title" data-toggle="tab" href="#login">  تغییر رمز ورود</a>
                            </li>

                        </ul>

                        <!-- Tab panes -->


                    </div>
                    <div class="c-card__body uk-height-1-1 uk-flex-middle">
                        <form id="seller-edit-form" action="{{ route('seller.profile.update', ['seller' => $seller]) }}" method="post" data-redirect="{{ route('seller.profile.index') }}">
                            @csrf
                            @method('put')
                          <div class="tab-content">
                            <div class="tab-pane container active" id="profile">
                                <div class="form-body col-md-12">

                                    <div class=" w-100 row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>کد فروشنده</label>
                                                <input type="text" class="form-control valid" value="{{$seller->id}}" disabled aria-invalid="false">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>نوع فروشنده</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->private_business=="private" ? 'حقیقی' : 'حقوقی'}}" disabled aria-invalid="false">

                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>نام تجاری</label>
                                                <input type="text" class="form-control valid"  value="{{$seller->seller_info->business_name}}" disabled aria-invalid="false">
                                            </div>
                                        </div>
                                    </div>


                                    <div id="private-div" class="@if($seller->seller_info->private_business=="business")d-none @endif w-100 row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>نام</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->first_name}}" name="first_name" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>نام خانوادگی</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->last_name}}" name="last_name" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>جنسیت</label>
                                                <select name="gender" class="form-control">
                                                    <option value="male" @if($seller->seller_info->gender=="male")selected @endif>مرد</option>
                                                    <option value="female" @if($seller->seller_info->gender=="female")selected @endif>زن</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>تاریخ تولد</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->birth_day}}" name="birth_day" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>کد ملی</label>
                                                <input type="number" class="form-control valid" value="{{$seller->seller_info->national_identity_number}}" name="national_identity_number" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>شماره شناسنامه</label>
                                                <input type="number" class="form-control valid" value="{{$seller->seller_info->identity_card_number}}" name="identity_card_number" aria-invalid="false">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="business-div" class="@if($seller->seller_info->private_business=="private")d-none @endif w-100 row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>نام شرکت</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->company_name}}" name="company_name" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>نوع شرکت</label>
                                                <select name="company_type" class="form-control">
                                                    <option value="public" @if($seller->seller_info->company_type=="public")selected @endif>سهامی عام</option>
                                                    <option value="joint_stock" @if($seller->seller_info->company_type=="joint_stock")selected @endif>سهامی خاص</option>
                                                    <option value="ltd" @if($seller->seller_info->company_type=="ltd")selected @endif>مسولیت محدود</option>
                                                    <option value="coop" @if($seller->seller_info->company_type=="coop")selected @endif>تعاونی</option>
                                                    <option value="solidarity" @if($seller->seller_info->company_type=="solidarity")selected @endif>تضامنی</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>شماره ثبت</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->company_registration_number}}" name="company_registration_number" aria-invalid="false">
                                            </div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>شناسه ملی</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->company_national_identity_number}}" name="company_national_identity_number" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>کد اقتصادی</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->company_economic_number}}" name="company_economic_number" aria-invalid="false">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row w-100">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>قصد فروش چه کالاهایی را دارید؟</label>
                                                <select class="form-control product-category" name="main_supply_category_id">
                                                    <option value="">انتخاب کنید</option>
                                                    @foreach ($categories as $category)
                                                        <option  class="l{{ $category->parents()->count() + 1 }} {{ $category->categories()->count() ? 'non-leaf' : '' }}"
                                                                 data-pup="{{ $category->category_id }}" value="{{ $category->id }}"
                                                            {{ ($seller->seller_info && $seller->seller_info->main_supply_category_id == $category->id) ? 'selected' : '' }}>{{ $category->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>تعداد حدودی تنوع کالای آماده فروش</label>
                                                <select name="number_of_products" id="number_of_products" class="w-100 valid form-control" aria-invalid="false">
                                                    <option value="">انتخاب کنید</option>
                                                    <option value="10" {{ ($seller->seller_info && $seller->seller_info->number_of_products == "10") ? 'selected' : '' }}>1-10</option>
                                                    <option value="50" {{ ($seller->seller_info && $seller->seller_info->number_of_products == "50") ? 'selected' : '' }} data-select2-id="107">11-50</option>
                                                    <option value="100" {{ ($seller->seller_info && $seller->seller_info->number_of_products == "100") ? 'selected' : '' }} data-select2-id="108">51-100</option>
                                                    <option value="300" {{ ($seller->seller_info && $seller->seller_info->number_of_products == "300") ? 'selected' : '' }} data-select2-id="109">101-300</option>
                                                    <option value="1000" {{ ($seller->seller_info && $seller->seller_info->number_of_products == "1000") ? 'selected' : '' }} data-select2-id="110">301-1000</option>
                                                    <option value="3000" {{ ($seller->seller_info && $seller->seller_info->number_of_products == "3000") ? 'selected' : '' }} data-select2-id="111">1001-3000</option>
                                                    <option value="10000" {{ ($seller->seller_info && $seller->seller_info->number_of_products == "10000") ? 'selected' : '' }} data-select2-id="112">3001-10000</option>
                                                    <option value="30000" {{ ($seller->seller_info && $seller->seller_info->number_of_products == "30000") ? 'selected' : '' }} data-select2-id="113">10001-30000</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row w-100">
                                        <div class="col-md-2">
                                            <label>لوگوی فروشنده</label>
                                            <div class="users-view-image">
                                                <img src="{{ $seller->seller_info->logo ? asset($seller->seller_info->logo) : asset('/empty.svg') }}" class="users-avatar-shadow w-100 rounded mb-2 ml-1" alt="avatar">
                                            </div>


                                        </div>

                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label>درباره فروشنده</label>
                                                <textarea name="bio" class="form-control valid" rows="3">{{$seller->seller_info->bio}}</textarea>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <fieldset class="form-group">
                                                <label>تغییر لوگو</label>
                                                <div class="custom-file">
                                                    <input type="file" accept="image/*" name="image" class="custom-file-input valid" aria-invalid="false">
                                                    <label class="custom-file-label" for="image"></label>
                                                    <p><small>بهترین اندازه <span class="text-danger">600 * 600</span> پیکسل میباشد.</small></p>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>


                                </div>
                            </div>

                            <div class="tab-pane container fade" id="address">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>ایمیل</label>
                                                <input type="email" class="form-control valid" value="{{$seller->email}}" name="email" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label> تلفن همراه <span class="required-star" style="color:red;">(ثبت نام)</span></label>
                                                <input type="number" class="form-control valid" value="{{$seller->seller_info->mobile}}"  disabled aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>وب سایت</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->website}}" name="website" aria-invalid="false">
                                            </div>
                                        </div>

                                        @php
                                            $cities = [];
                                            $city_id = null;
                                        @endphp

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>استان فروشگاه</label>
                                                <select id="province"  data-action="{{ route('provinces.get-cities') }}"  name="state_id" class="form-control">
                                                    <option value="">انتخاب کنید</option>

                                                    @foreach ($provinces as $province)
                                                        <option value="{{ $province->id }}" {{ $seller->seller_info->state_id == $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>شهر فروشگاه</label>
                                                <select id="city" name="city_id" class="form-control">
                                                    @foreach ($seller->seller_info->province->cities as $city)
                                                        <option value="{{ $city->id }}" {{ $seller->seller_info->city_id == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>موقعیت مکانی</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->location}}" name="location" aria-invalid="false">
                                            </div>
                                        </div>


                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>آدرس</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->address}}" name="address" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>کد پستی</label>
                                                <input type="number" class="form-control valid" value="{{$seller->seller_info->post_code}}" name="post_code" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>تلفن ثابت</label>
                                                <input type="number" class="form-control valid" value="{{$seller->seller_info->phone}}" name="phone" aria-invalid="false">
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane container fade" id="bank">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>شماره شبا</label>
                                                <input type="number" class="form-control valid" value="{{$seller->seller_info->shaba_number}}" name="shaba_number" aria-invalid="false">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>صاحب حساب</label>
                                                <input type="text" class="form-control valid" value="{{$seller->seller_info->full_name}}" disabled aria-invalid="false">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane container fade" id="econtract">
                                <div class="col-md-12">
                                    <div class="form-body">
                                        @if($seller_info->econtract=="1")
                                            <div class="alert alert-success mt-1 alert-validation-msg" role="alert">
                                                <i class="feather icon-info ml-1 align-middle"></i>
                                                <span>قرارداد توسط شما امضا شده است.</span>
                                            </div>
                                            <div class="c-mega-campaigns__btns-green-plus uk-margin-remove float-left" data-toggle="modal" data-target="#seller-econtracts-modal">
                                                مشاهده قرارداد
                                            </div>
                                        @else
                                            <div class="alert alert-danger mt-1 alert-validation-msg" role="alert">
                                                <i class="feather icon-info ml-1 align-middle"></i>
                                                <span>قرارداد توسط شما امضا نشده است.</span>
                                            </div>

                                            <div class="c-mega-campaigns__btns-green-plus uk-margin-remove float-left" data-toggle="modal" data-target="#seller-econtracts-modal">
                                                از اینجا امضا کنید
                                            </div>
                                        @endif

                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane container fade" id="documents">
                                <div class="col-md-12">
                                    <div class="form-body">
                                        @if($seller->status_documents=="Reject")
                                            <div class="alert alert-danger mt-1 alert-validation-msg" role="alert">
                                                <i class="feather icon-info ml-1 align-middle"></i>
                                                <span>مدارک شما توسط کارشناسان ما برسی و مورد تایید قرار نگرفت، لطفا مجددا مدارک خود را بارگذاری کنید.</span>
                                            </div>
                                        @elseif($seller->status_documents=="Accept")
                                            <div class="alert alert-success mt-1 alert-validation-msg" role="alert">
                                                <i class="feather icon-info ml-1 align-middle"></i>
                                                <span>مدارک شما تایید شده است.</span>
                                            </div>
                                        @elseif($seller->status_documents=="Waiting")
                                            <div class="alert alert-warning mt-1 alert-validation-msg" role="alert">
                                                <i class="feather icon-info ml-1 align-middle"></i>
                                                <span>مدارک شما در حال برسی می باشد.</span>
                                            </div>
                                        @endif
                                        <div class="row w-100">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>تصویر کارت ملی</label>
                                                    <img style="max-height: 200px;" src="{{ $seller->seller_info->card_image ? asset($seller->seller_info->card_image) : asset('/empty.svg') }}" class="users-avatar-shadow w-100 rounded mb-2 " alt="card_image">
                                                    @if($seller->status_documents=="Reject")
                                                        <div class="custom-file">
                                                            <input id="card_image" type="file" accept="image/*" name="card_image" class="custom-file-input">
                                                            <label class="custom-file-label" for="card_image"></label>
                                                        </div>
                                                    @endif

                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>تصویر پشت کارت ملی</label>
                                                    <img style="max-height: 200px;" src="{{ $seller->seller_info->card_image_back ? asset($seller->seller_info->card_image_back) : asset('/empty.svg') }}" class="users-avatar-shadow w-100 rounded mb-2 " alt="card_image_back">
                                                    @if($seller->status_documents=="Reject")
                                                    <div class="custom-file">
                                                        <input id="card_image_back" style="max-height: 220px;" type="file" accept="image/*" name="card_image_back" class="custom-file-input">
                                                        <label class="custom-file-label" for="card_image_back"></label>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>مشمولیت مالیات بر ارزش افزوده</label>
                                                    <select name="vat_free" class="form-control">
                                                        <option value="1" @if($seller->seller_info->vat_free=="1")selected @endif>بله</option>
                                                        <option value="2" @if($seller->seller_info->vat_free=="2")selected @endif>خیر</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>



                                        <div class="row w-100">
                                            <div id="vat_free_div" class="col-md-6 @if($seller->seller_info->vat_free=="2")d-none @endif ">
                                                <div class="form-group">
                                                    <label>تصویر گواهی ارزش افزوده</label>
                                                    <img style="max-height: 200px;" src="{{ $seller->seller_info->vat_image ? asset($seller->seller_info->vat_image) : asset('/empty.svg') }}" class="users-avatar-shadow w-100 rounded mb-2" alt="card_image_back">
                                                    @if($seller->status_documents=="Reject")
                                                    <div class="custom-file">
                                                        <input id="vat_image" style="max-height: 220px;" type="file" accept="image/*" name="vat_image" class="custom-file-input">
                                                        <label class="custom-file-label" for="vat_image"></label>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>

                                        </div>

                                    </div>
                                </div>
                            </div>

                              <div class="tab-pane container fade" id="login">
                                  <div class="card-body ">
                                      <div class="row">
                                          <div class="col-md-6 col-sm-12 m-auto">
                                              <div class="col-12">
                                                  <div class="form-group">
                                                      <label for="old-password">گذرواژه قبلی</label>
                                                      <input type="password" id="old-password" class="form-control ltr" name="prev_password">
                                                  </div>
                                              </div>
                                              <div class=" col-12">
                                                  <div class="form-group">
                                                      <label>گذرواژه جدید</label>
                                                      <input type="password" id="password" class="form-control ltr" name="password">
                                                  </div>
                                              </div>
                                              <div class="col-12">
                                                  <div class="form-group">
                                                      <label>تکرار گذرواژه جدید</label>
                                                      <input type="password" class="form-control ltr" name="password_confirmation">
                                                  </div>
                                              </div>
                                          </div>

                                          <div class="col-12">
                                              <div class="alert alert-info mt-1 alert-validation-msg" role="alert">
                                                  <i class="feather icon-info ml-1 align-middle"></i>
                                                  <span>در صورتی که نمیخواهید گذرواژه  را عوض کنید، فیلدهای گذرواژه را خالی بگذارید.</span>
                                              </div>
                                          </div>
                                      </div>

                                  </div>
                              </div>


                        </div>

                        </form>

                    </div>

                </div>
            </div>

            <div class="col-12 dashboard-profile-item-2">
                <div class="card mt-2">
                    <div class="c-card__body uk-height-1-1 uk-flex-middle">
                        <div class="c-mega-campaigns__btns-green-plus uk-margin-remove m-auto submit-dashboard-profile">
                            ثبت تغییرات
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade text-left" id="error-edit-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel19" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel19">آیا مطمئن هستید؟</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    با ایجاد تغییرات در پنل،اطلاعات شما باید توسط کارشناسان برسی و تایید شود، این کار ممکن است زمان بر باشد.
                </div>
                <div class="modal-footer">
                        <button type="button" class="btn btn-success waves-effect waves-light" data-dismiss="modal">خیر</button>
                        <button type="submit" class="btn btn-danger waves-effect waves-light edit-modal-success">بله ویرایش شود</button>
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

                    @if($seller_info->econtract=="0")
                        <div class="row">
                            <div class="col-6 pl-4">
                                <div class="row">
                                    <label class="ui-checkbox has-diviter">
                                        <input type="radio" value="1" name="econtract" id="econtract-1">
                                        <span class="ui-checkbox-check"></span>
                                    </label>
                                    <label for="econtract-1" class="remember-me has-diviter-remember-me cursor-pointer w-auto-i">
                                        قرارداد را کامل خوانده‌ام و موافقم
                                    </label>
                                </div>
                                <div class="row">
                                    <label class="ui-checkbox has-diviter">
                                        <input type="radio" value="0" name="econtract" id="econtract-0">
                                        <span class="ui-checkbox-check"></span>
                                    </label>
                                    <label for="econtract-0" class="remember-me has-diviter-remember-me cursor-pointer w-auto-i">
                                        مخالفم و از ثبت‌نام انصراف می‌دهم
                                    </label>
                                </div>

                            </div>

                            <div class="col-6 pt-2">
                                <div class=" c-mega-campaigns__btns-green-plus uk-margin-remove float-left mr-2 ml-3 set-econtract" data-action="{{route('seller.profile.set_econtract')}}">
                                    ثبت امضا
                                </div>
                                <span class="close-econtract-modal float-left cursor-pointer close" data-dismiss="modal">بعدا انجام می‌دهم</span>
                            </div>
                        </div>
                    @else
                        <div class="row display-inline">
                            <div class=" c-mega-campaigns__btns-green-plus uk-margin-remove float-left mr-2 ml-3 close flout-left" data-dismiss="modal">
                                بستن
                            </div>
                        </div>

                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@include('back.partials.plugins', ['plugins' => [ 'jquery-tagsinput', 'jquery-ui', 'jquery.validate']])
@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/profile/index.js') }}"></script>

@endpush

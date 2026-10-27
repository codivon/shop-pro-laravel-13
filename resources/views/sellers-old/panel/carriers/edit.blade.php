
@extends('sellers-old.panel.layouts.master')
@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('back/assets/css/pages/carriers/all.css') }}">
@endpush
@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action"> حمل و نقل</span>
                <span class="c-content-page__header-desc">برای تعیین هزینه ارسال، فعال یا غیرفعال کردن سرویس‌های پستی و تنظیم محدوده ارسال از این قسمت استفاده نمایید.</span>

            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')
    <div class="app-content content">
        <div class="">

        <div class="content-header row">
            <div class="content-body">
                <section class="card">
                    <div class="card-header">
                        <h4 class="card-title">ویرایش روش ارسال</h4>
                    </div>

                    <div id="main-card" class="card-content">
                        <div class="card-body">
                            <div class="col-12 col-md-12">
                                <form class="form" id="carrier-edit-form" action="{{ route('seller.carriers.update', ['carrier' => $carrier]) }}" data-redirect="{{ route('seller.carriers.index') }}" method="post">
                                    @csrf
                                    @method('put')

                                    <div class="form-body">
                                        <div class="nav-vertical">
                                            <ul class="nav nav-tabs nav-left flex-column" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" id="baseVerticalLeft-tab1" data-toggle="tab" aria-controls="tabVerticalLeft1" href="#tabVerticalLeft1" role="tab" aria-selected="true">اطلاعات کلی</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="baseVerticalLeft-tab2" data-toggle="tab" aria-controls="tabVerticalLeft2" href="#tabVerticalLeft2" role="tab" aria-selected="false">مناطق و هزینه ها</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="baseVerticalLeft-tab3" data-toggle="tab" aria-controls="tabVerticalLeft3" href="#tabVerticalLeft3" role="tab" aria-selected="false">بازه زمانی ارسال</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content">
                                                <div class="tab-pane active" id="tabVerticalLeft1" role="tabpanel" aria-labelledby="baseVerticalLeft-tab1">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>عنوان</label>
                                                                    <input type="text" class="form-control" name="title" value="{{ $carrier->title }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <fieldset class="form-group">
                                                                    <label>تصویر</label>
                                                                    <div class="custom-file">
                                                                        <input id="image" type="file" accept="image/*" name="image" class="custom-file-input">
                                                                        <label class="custom-file-label" for="image">{{ $carrier->image }}</label>
                                                                    </div>
                                                                </fieldset>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>وضعیت</label>
                                                                    <select name="is_active" class="form-control">
                                                                        <option value="1" {{ $carrier->is_active ? 'selected' : '' }}>فعال</option>
                                                                        <option value="0" {{ $carrier->is_active ? '' : 'selected' }}>غیر فعال</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>زمان انتظار</label>
                                                                    <input type="text" class="form-control" name="waiting_time" value="{{ $carrier->waiting_time }}">
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>حداکثر وزن بسته (گرم)</label>
                                                                    <input type="number" class="form-control" name="max_package_weight" value="{{ $carrier->max_package_weight }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>حداقل وزن بسته (گرم)</label>
                                                                    <input type="number" class="form-control" name="min_package_weight" value="{{ $carrier->min_package_weight }}">
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>توضیحات</label>
                                                                    <textarea class="form-control" name="description" rows="3">{{ $carrier->description }}</textarea>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane" id="tabVerticalLeft2" role="tabpanel" aria-labelledby="baseVerticalLeft-tab2">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>استان فروشگاه</label>
                                                                    <select id="province" data-action="{{ route('provinces.get-cities') }}"  name="province_id" class="form-control">
                                                                        <option value="">انتخاب کنید</option>

                                                                        @foreach ($provinces as $province)
                                                                            <option value="{{ $province->id }}" {{ $carrier->province->id == $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>شهر فروشگاه</label>
                                                                    <select id="city" name="city_id" class="form-control">
                                                                        @foreach ($carrier->province->cities as $city)
                                                                            <option value="{{ $city->id }}" {{ $carrier->city->id == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <fieldset class="checkbox">
                                                                    <label for="">پس کرایه</label>
                                                                    <div class="vs-checkbox-con vs-checkbox-primary">
                                                                        <input type="checkbox" name="carrige_forward" {{ $carrier->carrige_forward ? 'checked' : '' }}>
                                                                        <span class="vs-checkbox">
                                                                        <span class="vs-checkbox--check">
                                                                            <i class="vs-icon feather icon-check"></i>
                                                                        </span>
                                                                    </span>
                                                                        <span>آیا کرایه پس از ارسال مشخص میشود؟</span>
                                                                    </div>
                                                                </fieldset>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>ارسال رایگان برای وزن های بزرگتر از (گرم)</label>
                                                                    <input type="number" data-unit="گرم" class="form-control amount-input" name="free_shipping_weight" value="{{ $carrier->free_shipping_weight }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>ارسال رایگان برای مبالغ بزرگتر از</label>
                                                                    <input type="number" class="form-control amount-input" name="free_shipping_price" value="{{ $carrier->free_shipping_price }}">
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>هزینه اضافی برای بارهای سنگین تر (به ازای هر کیلوگرم)</label>
                                                                    <input type="number" class="form-control amount-input" name="extra_cost" value="{{ $carrier->extra_cost }}">
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>نوع شهرهای تحت پوشش</label>
                                                                    <select name="covered_cities" class="form-control">
                                                                        <option value="all" {{ $carrier->covered_cities == 'all' ? 'selected' : '' }}>همه</option>
                                                                        <option value="select_city" {{ $carrier->covered_cities == 'select_city' ? 'selected' : '' }}>انتخاب شهر</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div id="included-cities-container" class="col-md-4">
                                                                <label>شهرهای تحت پوشش</label>
                                                                <input type="text" class="form-control" id="included-cities" placeholder="انتخاب کنید..." autocomplete="off"/>
                                                            </div>

                                                        </div>

                                                    </div>
                                                </div>
                                                {{-- تب بازه زمانی ارسال --}}
                                                <div class="tab-pane" id="tabVerticalLeft3" role="tabpanel" aria-labelledby="baseVerticalLeft-tab3">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            {{-- نوع بازه زمانی ارسال --}}
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>نوع بازه زمانی ارسال</label>
                                                                    <select name="delivery_time_type" id="delivery_time_type" class="form-control">
                                                                        <option value="default" {{$carrier->delivery_time_type=="default" ? 'selected' : ''}}>متن پیشفرض</option>
                                                                        <option value="user_select" {{$carrier->delivery_time_type=="user_select" ? 'selected' : ''}}>انتخاب توسط کاربر</option>
                                                                    </select>
                                                                    <small class="text-muted">انتخاب کنید که بازه زمانی ثابت باشد یا کاربر انتخاب کند</small>
                                                                </div>
                                                            </div>

                                                            {{-- شروع بازه از چند روز بعد --}}
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>شروع بازه زمانی از (روز بعد از سفارش)</label>
                                                                    <select name="start_days_after_order" id="start_days_after_order" class="form-control">
                                                                        @for($days=1;$days<=11;$days++)
                                                                            <option value="{{$days}}" {{$carrier->start_days_after_order==$days ? 'selected' : ''}}>از {{$days}} روز بعد</option>
                                                                        @endfor
                                                                    </select>
                                                                    <small class="text-muted">تعیین کنید که بازه انتخابی از چند روز بعد از سفارش شروع شود</small>
                                                                </div>
                                                            </div>

                                                            {{-- بازه پیشفرض (پنهان در ابتدا) --}}
                                                            <div class="col-md-12" id="default_range_container">
                                                                <div class="form-group">
                                                                    <label>بازه زمانی ارسال (پیشفرض)</label>
                                                                    <input type="text" class="form-control" name="default_delivery_range"
                                                                           placeholder="مثال: ارسال در 3 الی 6 روز کاری"
                                                                           value="ارسال در 3 الی 6 روز کاری">
                                                                    <small class="text-muted">متنی که به کاربر نمایش داده می‌شود</small>
                                                                </div>
                                                            </div>

                                                            {{-- بازه‌های قابل انتخاب توسط کاربر --}}
                                                            <div class="col-md-12" id="user_select_ranges_container" style="display: none;">
                                                                {{-- گزینه‌های بازه به صورت رادیو --}}
                                                                <div class="card bg-light mb-2">
                                                                    <div class="card-header">
                                                                        <h5 class="card-title">انتخاب بازه زمانی ارسال</h5>


                                                                    </div>
                                                                    <div class="card-body">
                                                                        <div class="row">
                                                                            <div class="col-md-3">
                                                                                <div class="form-group">
                                                                                    <label class="d-block range-radio-label">
                                                                                        <input type="radio" name="user_select_ranges" value="7"  class="range-radio" {{$carrier->user_select_ranges=='7' ? 'checked' : ''}}>
                                                                                        <span class="range-radio-text">۷ روزه</span>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <div class="form-group">
                                                                                    <label class="d-block range-radio-label">
                                                                                        <input type="radio" name="user_select_ranges" value="10" class="range-radio" {{$carrier->user_select_ranges=='10' ? 'checked' : ''}}>
                                                                                        <span class="range-radio-text">۱۰ روزه</span>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <div class="form-group">
                                                                                    <label class="d-block range-radio-label">
                                                                                        <input type="radio" name="user_select_ranges" value="15" class="range-radio" {{$carrier->user_select_ranges=='15' ? 'checked' : ''}}>
                                                                                        <span class="range-radio-text">۱۵ روزه</span>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <div class="form-group">
                                                                                    <label class="d-block range-radio-label">
                                                                                        <input type="radio" name="user_select_ranges" value="20" class="range-radio" {{$carrier->user_select_ranges=='20' ? 'checked' : ''}}>
                                                                                        <span class="range-radio-text">۲۰ روزه</span>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="vs-checkbox-con vs-checkbox-primary">
                                                                            <input type="checkbox" name="disable_fridays" value="1" id="disable_fridays"  {{$carrier->disable_fridays ? 'checked' : ''}}>
                                                                            <span class="vs-checkbox">
                                                                        <span class="vs-checkbox--check">
                                                                            <i class="vs-icon feather icon-check"></i>
                                                                        </span>
                                                                    </span>
                                                                            <span>  غیرفعال کردن جمعه‌ها</span>
                                                                        </div>
                                                                        <small class="text-muted">در صورت فعال بودن، روزهای جمعه غیرقابل انتخاب می‌شوند</small>


                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <div class="vs-checkbox-con vs-checkbox-primary">
                                                                            <input type="checkbox" name="disable_holidays" value="1" id="disable_holidays"  {{$carrier->disable_holidays ? 'checked' : ''}}>
                                                                            <span class="vs-checkbox">
                                                                        <span class="vs-checkbox--check">
                                                                            <i class="vs-icon feather icon-check"></i>
                                                                        </span>
                                                                    </span>
                                                                            <span>   غیرفعال کردن تعطیلات رسمی</span>
                                                                        </div>
                                                                        <small class="text-muted">در صورت فعال بودن، روزهای تعطیل رسمی غیرقابل انتخاب می‌شوند</small>


                                                                    </div>
                                                                </div>


                                                                {{-- نمایش روزهای بازه انتخاب شده --}}
                                                                <div class="card" id="dates_preview_container" style="display: none;">
                                                                    <div class="card-header">
                                                                        <h5 class="card-title">پیش‌نمایش روزهای ارسال</h5>
                                                                        <small class="text-muted">روزهای تعطیل کمرنگ نمایش داده می‌شوند</small>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <div class="row" id="dates_preview">
                                                                            {{-- روزها به صورت داینامیک اضافه می‌شوند --}}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                            </div>
                                        </div>

                                        <div class="col-12 text-right">
                                            <button type="submit" class="btn btn-primary mb-1 waves-effect waves-light">ویرایش روش ارسال</button>
                                        </div>

                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>

@endsection

@include('back.partials.plugins', ['plugins' => ['jquery.validate', 'combo-tree']])

@push('scripts')
    <script>
        var provinces = {!! json_encode($provinces) !!};
        var selected_cities = {!! json_encode($carrier->cities->pluck('id')) !!};
        var convertToJalaliUrl='{{ route("admin.holidays.convert-to-jalali") }}';
        var calculateAndDisplayDatesUrl='{{ route("admin.holidays.get-start-dates") }}';

    </script>
            <script src="{{ theme_asset('js/seller-panel/pages/carriers/all.js') }}"></script>
            <script src="{{ theme_asset('js/seller-panel/pages/carriers/edit.js') }}"></script>
@endpush

{{-- ویرایش روش ارسال — سانیار فاز J
    قرارداد SellerCarrierController@edit :
    $provinces (با cities: id, province_id, name) + $carrier
    به‌روزرسانی: UpdateCarrierRequest → پاسخ متنی «success» + توست سشن --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'ویرایش روش ارسال', 'active' => 'carriers'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller-carriers.css') }}?v=1">
@endpush

@section('seller-content')
    @php
        $is_edit = true;
        $included_ids = $carrier->cities->pluck('id');
        $range_options = [
            7 => 'ارسال ۷ روزه',
            10 => 'ارسال ۱۰ روزه',
            15 => 'ارسال ۱۵ روزه',
            20 => 'ارسال ۲۰ روزه',
        ];
    @endphp

    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-pencil-square main-color-one-color ms-1"></i>ویرایش روش ارسال</h5>
                    <p class="font-13 text-muted mb-0">
                        «{{ $carrier->title }}» — اطلاعات کلی، مناطق تحت پوشش و بازه زمانی ارسال را ویرایش کنید.
                    </p>
                </div>
                <a href="{{ route('seller.carriers.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                    <i class="bi bi-arrow-right ms-1"></i>بازگشت به حامل‌ها
                </a>
            </div>
        </div>
    </div>

    <form class="form" id="sfa-carrier-form" action="{{ route('seller.carriers.update', ['carrier' => $carrier]) }}" method="post"
          enctype="multipart/form-data" data-redirect="{{ route('seller.carriers.index') }}">
        @csrf
        @method('put')

        {{-- ============ بخش ۱: اطلاعات کلی ============ --}}
        <div class="content-box mb-3">
            <div class="container-fluid">
                <div class="title-panel mb-3">
                    <h2 class="font-16 mb-0"><i class="bi bi-info-circle main-color-one-color me-1"></i>اطلاعات کلی</h2>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label font-13 fw-bold" for="sfa-title">عنوان روش ارسال <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-13" id="sfa-title" name="title" maxlength="191"
                               value="{{ $carrier->title }}" required>
                        <div class="font-12 text-muted mt-1">این عنوان به خریداران نمایش داده می‌شود.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-13 fw-bold" for="sfa-image">تصویر حامل</label>
                        <input type="file" class="form-control font-13" id="sfa-image" name="image" accept="image/*">
                        @if($carrier->image)
                            <div class="d-flex align-items-center gap-1 mt-2">
                                <img src="{{ asset($carrier->image) }}" class="sfa-img-thumb" alt="تصویر فعلی روش ارسال {{ $carrier->title }}">
                                <span class="font-12 text-muted">تصویر فعلی — در صورت انتخاب فایل جدید جایگزین می‌شود.</span>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-13 fw-bold" for="sfa-is-active">وضعیت</label>
                        <select class="form-select font-13" id="sfa-is-active" name="is_active">
                            <option value="1" @selected($carrier->is_active)>فعال</option>
                            <option value="0" @selected(!$carrier->is_active)>غیرفعال</option>
                        </select>
                        <div class="font-12 text-muted mt-1">حامل‌های غیرفعال در فروشگاه دیده نمی‌شوند.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfa-waiting-time">زمان انتظار تا آماده‌سازی</label>
                        <input type="text" class="form-control font-13" id="sfa-waiting-time" name="waiting_time"
                               value="{{ $carrier->waiting_time }}" placeholder="مثال: ۲۴ ساعت">
                        <div class="font-12 text-muted mt-1">متن کوتاهی که در صفحه محصول نمایش داده می‌شود.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfa-min-weight">حداقل وزن بسته (گرم)</label>
                        <input type="number" class="form-control font-13" id="sfa-min-weight" name="min_package_weight"
                               min="0" inputmode="numeric" value="{{ $carrier->min_package_weight }}" placeholder="مثال: 100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfa-max-weight">حداکثر وزن بسته (گرم)</label>
                        <input type="number" class="form-control font-13" id="sfa-max-weight" name="max_package_weight"
                               min="0" inputmode="numeric" value="{{ $carrier->max_package_weight }}" placeholder="مثال: 20000">
                    </div>
                    <div class="col-12">
                        <label class="form-label font-13 fw-bold" for="sfa-description">توضیحات</label>
                        <textarea class="form-control font-13" id="sfa-description" name="description" rows="3"
                                  placeholder="توضیحات مربوط به این روش ارسال (اختیاری)">{{ $carrier->description }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ بخش ۲: مناطق و هزینه‌ها ============ --}}
        <div class="content-box mb-3">
            <div class="container-fluid">
                <div class="title-panel mb-3">
                    <h2 class="font-16 mb-0"><i class="bi bi-geo main-color-one-color me-1"></i>مناطق و هزینه‌ها</h2>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label font-13 fw-bold" for="sfa-province">استان مرکز فروشگاه <span class="text-danger">*</span></label>
                        <select class="form-select font-13" id="sfa-province" name="province_id"
                                data-cities-url="{{ route('provinces.get-cities') }}" required>
                            <option value="">انتخاب استان…</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}" @selected($carrier->province?->id == $province->id)>{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-13 fw-bold" for="sfa-city">شهر مرکز فروشگاه <span class="text-danger">*</span></label>
                        <select class="form-select font-13" id="sfa-city" name="city_id" required>
                            @foreach(($carrier->province?->cities ?? []) as $city)
                                <option value="{{ $city->id }}" @selected($carrier->city?->id == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                        <div class="font-12 text-muted mt-1">مرکز ارسال مرسوله‌های شما این شهر است.</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sfa-carrige-forward" name="carrige_forward" value="1"
                                   @checked($carrier->carrige_forward)>
                            <label class="form-check-label font-13 fw-bold" for="sfa-carrige-forward">
                                کرایه پس از ارسال (پس‌کرایه) — هزینه ارسال هنگام تحویل دریافت می‌شود
                            </label>
                        </div>
                        <div class="font-12 text-muted mt-1">برای حامل‌های پس‌کرایه، تعرفه هزینه ارسال ثبت نمی‌شود.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfa-free-weight">ارسال رایگان برای وزن بیشتر از (گرم)</label>
                        <input type="number" class="form-control font-13" id="sfa-free-weight" name="free_shipping_weight"
                               min="0" inputmode="numeric" value="{{ $carrier->free_shipping_weight }}" placeholder="مثال: 5000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfa-free-price">ارسال رایگان برای مبلغ بیشتر از (تومان)</label>
                        <input type="number" class="form-control font-13" id="sfa-free-price" name="free_shipping_price"
                               min="0" inputmode="numeric" value="{{ $carrier->free_shipping_price }}" placeholder="مثال: 5000000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfa-extra-cost">هزینه اضافه به ازای هر کیلوگرم (تومان)</label>
                        <input type="number" class="form-control font-13" id="sfa-extra-cost" name="extra_cost"
                               min="0" inputmode="numeric" value="{{ $carrier->extra_cost }}" placeholder="مثال: 10000">
                        <div class="font-12 text-muted mt-1">فقط برای وزن‌های خارج از بازه تعرفه‌ها اعمال می‌شود.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfa-covered-cities">نوع شهرهای تحت پوشش <span class="text-danger">*</span></label>
                        <select class="form-select font-13" id="sfa-covered-cities" name="covered_cities">
                            <option value="all" @selected($carrier->covered_cities == 'all')>همه شهرها</option>
                            <option value="select_city" @selected($carrier->covered_cities == 'select_city')>انتخاب شهرها</option>
                        </select>
                        <div class="font-12 text-muted mt-1">در حالت «همه شهرها» به سراسر کشور ارسال می‌کنید.</div>
                    </div>

                    {{-- درخت چک‌باکس شهرها (جایگزین پلاگین comboTree) — وضعیت فعلی از $carrier --}}
                    <div class="col-12 {{ $carrier->covered_cities == 'select_city' ? '' : 'd-none' }}" id="sfa-included-cities-container">
                        <label class="form-label font-13 fw-bold" for="sfa-cities-tree">شهرهای تحت پوشش <span class="text-danger">*</span></label>
                        <div class="sfa-cities-tree" id="sfa-cities-tree" role="group" aria-label="انتخاب شهرهای تحت پوشش">
                            @foreach($provinces as $province)
                                <div class="sfa-province-group">
                                    <div class="form-check sfa-province-head">
                                        <input class="form-check-input sfa-province-toggle" type="checkbox" value="{{ $province->id }}"
                                               id="sfa-ptoggle-{{ $province->id }}" data-target="sfa-province-{{ $province->id }}"
                                               aria-label="انتخاب همه شهرهای استان {{ $province->name }}">
                                        <label class="form-check-label fw-bold font-13" for="sfa-ptoggle-{{ $province->id }}">{{ $province->name }}</label>
                                    </div>
                                    <div class="sfa-province-cities" id="sfa-province-{{ $province->id }}">
                                        @foreach($province->cities as $city)
                                            <div class="form-check">
                                                <input class="form-check-input sfa-city-check" type="checkbox" name="included_cities[]"
                                                       value="{{ $city->id }}" id="sfa-city-{{ $city->id }}"
                                                       data-province="sfa-province-{{ $province->id }}"
                                                       @checked($included_ids->contains($city->id))>
                                                <label class="form-check-label font-13" for="sfa-city-{{ $city->id }}">{{ $city->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="font-12 text-muted mt-1">برای انتخاب یا حذف گروهی، تیک کنار نام هر استان را بزنید.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ بخش ۳: بازه زمانی ارسال ============ --}}
        <div class="content-box mb-3">
            <div class="container-fluid">
                <div class="title-panel mb-3">
                    <h2 class="font-16 mb-0"><i class="bi bi-calendar-week main-color-one-color me-1"></i>بازه زمانی ارسال</h2>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label font-13 fw-bold" for="sfa-delivery-type">نوع بازه زمانی <span class="text-danger">*</span></label>
                        <select class="form-select font-13" id="sfa-delivery-type" name="delivery_time_type">
                            <option value="default" @selected($carrier->delivery_time_type == 'default')>متن پیش‌فرض</option>
                            <option value="user_select" @selected($carrier->delivery_time_type == 'user_select')>انتخاب توسط خریدار</option>
                        </select>
                        <div class="font-12 text-muted mt-1">انتخاب کنید که بازه زمانی ثابت باشد یا خریدار هنگام ثبت سفارش انتخاب کند.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-13 fw-bold" for="sfa-start-days">شروع بازه از <span class="text-danger">*</span></label>
                        <select class="form-select font-13" id="sfa-start-days" name="start_days_after_order">
                            @for($days = 1; $days <= 11; $days++)
                                <option value="{{ $days }}" @selected($carrier->start_days_after_order == $days)>از {{ $days }} روز بعدِ ثبت سفارش</option>
                            @endfor
                        </select>
                        <div class="font-12 text-muted mt-1">زمان آماده‌سازی سفارش تا شروع بازه ارسال.</div>
                    </div>

                    {{-- حالت متن پیش‌فرض — سانیار: مقدار فعلی حامل بایند می‌شود (رفع باگ مقدار هاردکد WeblakShop) --}}
                    <div class="col-12 {{ $carrier->delivery_time_type == 'user_select' ? 'd-none' : '' }}" id="sfa-default-range-container">
                        <label class="form-label font-13 fw-bold" for="sfa-default-range">بازه زمانی ارسال (متن نمایش به خریدار) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-13" id="sfa-default-range" name="default_delivery_range"
                               placeholder="مثال: ارسال در ۳ الی ۶ روز کاری"
                               value="{{ $carrier->default_delivery_range }}">
                        <div class="font-12 text-muted mt-1">این متن در صفحه محصول و مرحله پرداخت نمایش داده می‌شود.</div>
                    </div>

                    {{-- حالت انتخاب توسط خریدار --}}
                    <div class="col-12 {{ $carrier->delivery_time_type == 'default' ? 'd-none' : '' }}" id="sfa-user-ranges-container">
                        <div class="border rounded-3 p-3 bg-light mb-3">
                            <span class="font-13 fw-bold d-block mb-2">بازه‌های قابل انتخاب توسط خریدار <span class="text-danger">*</span></span>
                            <div class="row g-2">
                                @foreach($range_options as $range_value => $range_label)
                                    <div class="col-6 col-md-3">
                                        <label class="sfa-range-option d-block border rounded-3 p-2 text-center font-13"
                                               for="sfa-range-{{ $range_value }}">
                                            <input class="form-check-input mt-0" type="radio" name="user_select_ranges"
                                                   value="{{ $range_value }}" id="sfa-range-{{ $range_value }}"
                                                   @checked($carrier->user_select_ranges == $range_value)>
                                            {{ $range_label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="disable_fridays" value="1" id="sfa-disable-fridays"
                                               @checked($carrier->disable_fridays)>
                                        <label class="form-check-label font-13" for="sfa-disable-fridays">غیرفعال کردن جمعه‌ها</label>
                                    </div>
                                    <div class="font-12 text-muted">در صورت فعال بودن، روزهای جمعه برای تحویل انتخاب نمی‌شوند.</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="disable_holidays" value="1" id="sfa-disable-holidays"
                                               @checked($carrier->disable_holidays)>
                                        <label class="form-check-label font-13" for="sfa-disable-holidays">غیرفعال کردن تعطیلات رسمی</label>
                                    </div>
                                    <div class="font-12 text-muted">در صورت فعال بودن، روزهای تعطیل رسمی برای تحویل انتخاب نمی‌شوند.</div>
                                </div>
                            </div>
                        </div>

                        {{-- پیش‌نمایش روزهای ارسال (با AJAX از سرویس تعطیلات) --}}
                        <div class="d-none" id="sfa-dates-preview-container">
                            <div class="border rounded-3 p-3">
                                <span class="font-13 fw-bold d-block mb-2">
                                    <i class="bi bi-calendar-day main-color-one-color ms-1"></i>پیش‌نمایش روزهای ارسال
                                </span>
                                <div class="row g-2" id="sfa-dates-preview"></div>
                                <div class="font-12 text-muted mt-2">روزهای تعطیل با نشان قرمز و کمرنگ‌تر نمایش داده می‌شوند.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- دکمه‌های ثبت --}}
        <div class="content-box">
            <div class="container-fluid">
                <div class="d-flex flex-wrap justify-content-end gap-2 py-3">
                    <a href="{{ route('seller.carriers.index') }}" class="btn btn-outline-secondary rounded-3 font-14">
                        <i class="bi bi-arrow-right ms-1"></i>بازگشت به حامل‌ها
                    </a>
                    <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-14 px-4" id="sfa-submit-btn">
                        <span class="saniar-btn-spinner spinner-border spinner-border-sm d-none ms-1" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check2-circle ms-1"></i>ویرایش روش ارسال
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-carriers.js') }}?v=1"
            data-saniar-carriers="edit"
            data-dates-url="{{ route('seller.holidays.get-start-dates') }}"></script>
@endpush

{{-- آدرس‌های من — قرارداد AddressesController@index : $addresses, $provinces, $active --}}
@extends('front::user.layouts.master', ['title' => 'آدرس‌های من'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="fw-bold font-16 mb-0">
                            <i class="bi bi-pin-map main-color-one-color me-1"></i>
                            آدرس های <span class="main-color-one-color">ثبت شده</span>
                        </h2>
                        <button type="button" class="js-add-address btn main-color-one-bg text-white btn-sm waves-effect waves-light rounded-3 px-3"
                                data-bs-toggle="modal" data-bs-target="#add-edit-address-modal"
                                data-update-url="{{ route('front.addresses.store') }}">
                            ثبت آدرس جدید <i class="bi bi-plus-circle text-white ms-1"></i>
                        </button>
                    </div>
                </div>
                <div class="ui-box-item-desc">

                    @if($addresses->count())
                        <div class="row g-3">
                            @foreach($addresses as $address)
                                <div class="col-md-6 col-xl-4">
                                    <div class="bg-white card addresses-item mb-0 shadow-sm h-100 {{ $address->active ? 'active border-primary border-2' : '' }}">
                                        <div class="gold-members p-4 h-100 d-flex flex-column">
                                            <div class="media">
                                                <div class="media-body d-flex flex-column h-100">
                                                    <h3 class="mb-1 font-15 fw-bold">
                                                        {{ $address->fullname }}
                                                        @if($address->active)
                                                            <span class="badge fw-normal font-10 float-end main-color-three-bg text-white rounded-pill">آدرس پیشفرض</span>
                                                        @endif
                                                    </h3>
                                                    <p class="text-overflow-2 address-line text-muted font-13 flex-grow-1">
                                                        {{ $address->province_name }}، {{ $address->city_name }}، {{ $address->address }}
                                                        @if($address->buildingNumber) — پلاک {{ $address->buildingNumber }} @endif
                                                        @if($address->unit) — واحد {{ $address->unit }} @endif
                                                    </p>
                                                    <p class="mb-1 font-13 text-muted">
                                                        <i class="bi bi-envelope me-1"></i> کد پستی:
                                                        <span class="ltr-inline">{{ $address->postal_code }}</span>
                                                    </p>
                                                    <p class="mb-2 font-13 text-muted">
                                                        <i class="bi bi-phone me-1"></i>
                                                        <span class="ltr-inline">{{ $address->mobile }}</span>
                                                    </p>
                                                    <p class="mb-0 btn-group-sm font-weight-bold mt-auto d-flex flex-wrap gap-1">
                                                        <button type="button" class="btn btn-primary btn-sm rounded-3 js-address-edit"
                                                                data-bs-toggle="modal" data-bs-target="#add-edit-address-modal"
                                                                data-update-url="{{ route('front.addresses.update', $address) }}"
                                                                data-info-url="{{ route('front.addresses.show', $address->id) }}"
                                                                aria-label="ویرایش آدرس" title="ویرایش آدرس">
                                                            <i class="bi bi-pencil text-white"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm rounded-3 js-address-delete"
                                                                data-action="{{ route('front.addresses.destroy', $address) }}"
                                                                data-bs-toggle="modal" data-bs-target="#addressDeleteModal"
                                                                aria-label="حذف آدرس" title="حذف آدرس">
                                                            <i class="bi bi-trash text-white"></i>
                                                        </button>
                                                        @unless($address->active)
                                                            <button type="button" class="btn btn-success btn-sm rounded-3 js-address-active"
                                                                    data-action="{{ route('front.addresses.active', $address) }}"
                                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="ثبت به عنوان آدرس پیشفرض"
                                                                    aria-label="ثبت به عنوان آدرس پیشفرض">
                                                                <i class="bi bi-check-lg text-white"></i>
                                                            </button>
                                                        @endunless
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-geo-alt text-muted" style="font-size:3rem;"></i>
                            <h3 class="font-16 mt-3">هنوز آدرسی ثبت نکرده‌اید</h3>
                            <p class="text-muted font-14">برای دریافت سریع‌تر مرسوله‌ها آدرس خود را اضافه کنید.</p>
                            <button type="button" class="js-add-address btn border-0 main-color-one-bg text-white rounded-3 px-4"
                                    data-bs-toggle="modal" data-bs-target="#add-edit-address-modal"
                                    data-update-url="{{ route('front.addresses.store') }}">
                                <i class="bi bi-plus-circle me-1"></i> ثبت آدرس جدید
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    @if($addresses->hasPages())
        <div class="my-paginate mt-4">
            {{ $addresses->links('front::components.paginate') }}
        </div>
    @endif

    {{-- مودال افزودن/ویرایش آدرس (بدون نقشه — هم‌الگوی چک‌اوت فاز D) --}}
    <div class="modal fade" id="add-edit-address-modal" tabindex="-1" aria-hidden="true" aria-labelledby="add-edit-address-modal-label">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="add-edit-address-modal-label">ثبت آدرس جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <form id="add-update-address-form" method="POST" action="{{ route('front.addresses.store') }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="comment-item">
                                    <input type="text" class="form-control rounded-3" id="address-fullname" name="fullname"
                                           placeholder="نام و نام خانوادگی گیرنده" required>
                                    <label class="label-float fw-bold font-14" for="address-fullname">نام گیرنده <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="comment-item">
                                    <input type="text" class="form-control rounded-3" id="address-mobile" name="mobile"
                                           placeholder="09xxxxxxxxx" inputmode="numeric" required>
                                    <label class="label-float fw-bold font-14" for="address-mobile">شماره همراه <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-14 fw-bold" for="province">استان <span class="text-danger">*</span></label>
                                <select class="form-select rounded-3" id="province" name="province_id" required>
                                    <option value="">انتخاب استان...</option>
                                    @foreach($provinces as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-14 fw-bold" for="city">شهر <span class="text-danger">*</span></label>
                                <select class="form-select rounded-3" id="city" name="city_id" required>
                                    <option value="">ابتدا استان را انتخاب کنید...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="comment-item">
                                    <input type="text" class="form-control rounded-3" id="address-postal" name="postal_code"
                                           placeholder="کد پستی ۱۰ رقمی" inputmode="numeric" required>
                                    <label class="label-float fw-bold font-14" for="address-postal">کد پستی <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="comment-item">
                                    <input type="text" class="form-control rounded-3" id="address-building" name="buildingNumber"
                                           placeholder="پلاک" inputmode="numeric" required>
                                    <label class="label-float fw-bold font-14" for="address-building">پلاک <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="comment-item">
                                    <input type="text" class="form-control rounded-3" id="address-unit" name="unit" placeholder="واحد (اختیاری)">
                                    <label class="label-float fw-bold font-14" for="address-unit">واحد</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="comment-item">
                                    <textarea class="form-control rounded-3 py-3" id="address" name="address" rows="3"
                                              placeholder="آدرس کامل: خیابان، کوچه، ..." required></textarea>
                                    <label class="label-float fw-bold font-14" for="address">آدرس <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <input type="hidden" name="lat" value="">
                            <input type="hidden" name="lng" value="">
                        </div>
                        <div id="address-form-error" class="font-12 text-danger mt-2 d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn border-0 main-color-one-bg rounded-3 px-4" id="address-submit-btn">
                            ثبت آدرس
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال حذف آدرس --}}
    <div class="modal fade" id="addressDeleteModal" tabindex="-1" aria-labelledby="addressDeleteLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="addressDeleteLabel">
                        <i class="bi bi-trash text-danger me-1"></i> حذف آدرس
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <p class="font-14 mb-3">آیا از حذف این آدرس از لیست آدرس‌ها مطمئن هستید؟</p>
                    <form id="address-remove-form" action="#" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="submit" class="btn btn-danger rounded-3 px-4">بله، حذف شود</button>
                            <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">لغو</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush

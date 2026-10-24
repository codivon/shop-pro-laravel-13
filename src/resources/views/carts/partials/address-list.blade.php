{{-- لیست آدرس‌های تحویل — $addresses (حداکثر ۳) ؛ id و data ها قرارداد JS هستند --}}
<div id="address-section" data-action="{{ route('front.checkout.prices') }}">

    <div class="content-box mb-4">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pb-3 mb-3">
                <h5 class="font-16 mb-0"><i class="bi bi-geo-alt-fill main-color-one-color ms-1"></i>آدرس تحویل سفارش</h5>
                <a href="#add-edit-address-modal" class="btn btn-sm border-0 main-color-one-bg rounded-3 waves-effect waves-light openMap"
                   data-UpdateUrl="{{ route('front.addresses.store') }}" data-bs-toggle="modal" data-bs-target="#add-edit-address-modal">
                    <i class="bi bi-plus-circle"></i> افزودن آدرس جدید
                </a>
            </div>

            @if(isset($addresses) && count($addresses))
                <div class="row gy-3">
                    @foreach($addresses as $address)
                        <div class="col-md-4">
                            <div class="bg-white card addresses-item mb-0 shadow-sm h-100 {{ $address->active ? 'active-address active border-primary border-2' : '' }}"
                                 data-city="{{ $address->city_id }}" data-address-id="{{ $address->id }}">
                                <div class="gold-members p-3">
                                    <div class="media">
                                        <div class="media-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="mb-1">
                                                    {{ $address->fullname }}
                                                    @if($address->active)
                                                        <span class="badge fw-normal font-10 main-color-three-bg rounded-pill">آدرس پیشفرض</span>
                                                    @endif
                                                </h6>
                                                <input type="radio" class="d-none address-radio" id="select-address-{{ $address->id }}"
                                                       name="address" value="{{ $address->id }}" {{ $address->active ? 'checked' : '' }}>
                                            </div>
                                            <p class="text-overflow-2 address-line font-14 text-muted mb-2">
                                                {{ $address->province_name }}، {{ $address->city_name }}، {{ $address->address }}
                                                @if($address->buildingNumber) — پلاک {{ $address->buildingNumber }} @endif
                                                @if($address->unit) — واحد {{ $address->unit }} @endif
                                            </p>
                                            <p class="font-12 text-muted mb-2">
                                                <i class="bi bi-phone"></i> {{ $address->mobile }}
                                                @if($address->postal_code)
                                                    <span class="ms-2"><i class="bi bi-mailbox"></i> {{ $address->postal_code }}</span>
                                                @endif
                                            </p>
                                            <div class="btn-group-sm font-weight-bold d-flex gap-1">
                                                <a class="btn btn-primary btn-sm openMap edit-address-link" role="button"
                                                   data-UpdateUrl="{{ route('front.addresses.update', $address->id) }}"
                                                   data-url="{{ route('front.addresses.show', $address->id) }}"
                                                   data-bs-toggle="modal" data-bs-target="#add-edit-address-modal"
                                                   aria-label="ویرایش آدرس" data-bs-toggle-tooltip="tooltip" data-bs-title="ویرایش آدرس">
                                                    <i class="bi bi-pencil text-white"></i>
                                                </a>
                                                @unless($address->active)
                                                    <a class="btn btn-success btn-sm change-active-button sett-address" role="button"
                                                       data-action="{{ route('front.addresses.active', $address) }}"
                                                       aria-label="ثبت به عنوان آدرس پیشفرض" data-bs-title="ثبت به عنوان آدرس پیشفرض">
                                                        <i class="bi bi-check-lg text-white"></i>
                                                    </a>
                                                @endunless
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-geo-alt font-50 text-muted"></i>
                    <p class="font-16 mt-2">هنوز آدرسی ثبت نکرده‌اید؛ اولین آدرس خود را ثبت کنید.</p>
                    <a href="#add-edit-address-modal" class="btn border-0 main-color-one-bg rounded-3 openMap"
                       data-UpdateUrl="{{ route('front.addresses.store') }}" data-bs-toggle="modal" data-bs-target="#add-edit-address-modal">
                        <i class="bi bi-plus-circle"></i> ثبت آدرس
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card shadow-1 cart address-container mb-4"  id="address-section" data-action="{{ route('front.checkout.prices') }}">
    <div class="card-body">
        <div class="send-address-title">آدرس تحویل سفارش را انتخاب نمایید:</div>
        <div class="row">
            @foreach($addresses as $address)
                <div class="col-xl-3 col-lg-4 col-md-6 user-address-item  @if($address->active)active-address @endif" data-city="{{$address->city_id}}">
                    <div class="custom-radio-box">
                        <label class=" custom-radio-box-label" for="select-address-{{$address->id}}" data-placeholder="{{$address->active ? 'انتخاب شده' : 'انتخاب این آدرس'}}">
                            <input type="radio" class="d-none" id="select-address-{{$address->id}}" name="address" value="{{$address->id}}" {{$address->active ? 'checked' : ''}}>
                            <span class="icon-address">
                                                        @if($address->active)
                                    <i class="  fas fa-check"></i>
                                @else
                                    <i class=" fas fa-circle-dot"></i>
                                @endif

                                                    </span>
                            <span class="d-block user-address-recipient mb-2">{{$address->province_name .','. $address->city_name}}</span><span
                                class="d-block user-contact-items mb-3">
                                                        <span class="user-contact-item">
                                                            <i class="icon fas fa-location-dot"></i>
                                                            <span class="value full-address lts-05">{{$address->address}} </span>
                                                        </span>
                                                        <span class="user-contact-item">
                                                            <i class="icon fas fa-phone"></i>
                                                            <span class="value">{{$address->mobile}}</span>
                                                        </span>
                                                        <span class="user-contact-item">
                                                            <i class="icon fas fa-user"></i>
                                                            <span class="value">{{$address->fullname}} </span>
                                                        </span>
                                                    </span>

                            <span class="d-flex align-items-center justify-content-end">
                                                        <a href="javascript:void(0)" class="link border-bottom-0 fs-7 fw-bold edit-address-link openMap"  data-UpdateUrl="{{route('front.addresses.update',$address->id)}}" data-url="{{route('front.addresses.show',$address->id)}}" data-toggle="modal" data-target="#add-edit-address-modal">ویرایش</a>
                                                    </span>
                            <button type="button" class="change-active-button sett-address" data-action="{{route('front.addresses.active',$address)}}"></button>
                        </label>
                    </div>
                </div>
            @endforeach

            <div class="col-xl-3 col-lg-4 col-md-6 user-address-item user-add-address-container">
                <div class="user-add-address--box openMap"  data-UpdateUrl="{{route('front.addresses.store')}}" data-toggle="modal"
                     data-target="#add-edit-address-modal">
                    <i class="ri-add-line"></i>
                    <span class="lts-05">افزودن آدرس جدید</span>
                </div>
            </div>

        </div>
    </div>
</div>

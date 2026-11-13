@php
    $carrierKey = $group['is_store'] ? 'carrier_id_store' : 'carrier_id_seller_' . ($group['seller_id'] ?? '');
    $selectedCarrierId = $group['selected_carrier_id'] ?? null;

    // پیدا کردن روش ارسال انتخابی و بررسی type آن
    $selectedCarrierType = null;
    foreach($group['carriers'] as $carrier) {
        if($carrier['id'] == $selectedCarrierId) {
            $selectedCarrierType = $carrier['delivery_type'];
            break;
        }
    }

    // نمایش تاریخ‌ها فقط اگر روش انتخابی از نوع user_select باشد و دیتا وجود داشته باشد
    $showDeliveryDates = ($selectedCarrierType == 'user_select' && isset($AllDeliveryDates[$carrierKey]) && count($AllDeliveryDates[$carrierKey]) > 0);
@endphp
@if($showDeliveryDates)
    {{-- بخش انتخاب روز ارسال (برای روش‌های user_select) --}}
    <div class="col-12 send-period-container mb-2" id="send-period-container-{{ $groupId }}" style="display: block;">
        <div class="send-address-title mb-3 fs-8 mt-0">تاریخ تحویل:</div>
        <div id="delivery-dates-wrapper-{{ $groupId }}">
            <div class="days-widget">
                <ul>
                    @foreach($AllDeliveryDates[$carrierKey] as $DeliveryDate)
                        <li>
                            <button type="button"
                                    class="delivery-date-btn {{ !$DeliveryDate['is_selectable'] ? 'holiday disabled' : '' }}"
                                    data-date="{{ $DeliveryDate['date'] }}"
                                    data-jalali="{{ $DeliveryDate['jalali'] }}"
                                    data-group-id="{{ $groupId }}"
                                {{ !$DeliveryDate['is_selectable'] ? 'disabled' : '' }}>
                                <span class="day">{{ $DeliveryDate['day_name'] }}</span>
                                <span class="full mb-2">{{ $DeliveryDate['displayDate'] }}</span>
                                <i class="ri-circle-line fw-bold"></i>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif


@if($deliveryDateForOne and count($deliveryDateForOne['deliveryDateForOne']))
    @php
        $groupId=$deliveryDateForOne['groupId'];
        $carrier_id=$deliveryDateForOne['carrier_id'];
    @endphp
    {{-- بخش انتخاب روز ارسال (برای روش‌های user_select) --}}
    <div class="col-12 send-period-container mb-2" style="display: block;">
        <div class="days-widget">
            <div class="send-address-title mb-3 fs-8 mt-0">تاریخ تحویل:</div>
            <div class="days-widget p-0">
                <ul>
                    @foreach($deliveryDateForOne['deliveryDateForOne'] as $key=> $DeliveryDate)
                        <li>
                            <input type="radio" name="carrier_date_{{$groupId}}" value="{{$DeliveryDate['date']}}"
                                   class="delivery-Date"
                                   data-date="{{ $DeliveryDate['date'] }}" id="date-{{$groupId.'-'.$key}}"
                                   data-jalali="{{ $DeliveryDate['jalali'] }}"
                                   data-group-id="{{ $groupId }}" {{ !$DeliveryDate['is_selectable'] ? 'disabled' : '' }}    >
                            <label for="date-{{$groupId.'-'.$key}}" class="delivery-date-btn {{ !$DeliveryDate['is_selectable'] ? 'holiday disabled' : '' }}">
                                <span class="day">{{ $DeliveryDate['day_name'] }}</span>
                                <span class="full mb-2">{{ $DeliveryDate['displayDate'] }}</span>
                                <i class="fas fa-circle-dot"></i>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>


    </div>
@endif

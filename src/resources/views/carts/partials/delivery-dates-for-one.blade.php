{{-- تاریخ‌های تحویل — قرارداد getPrices: با کلید deliveryDateForOne رندر و داخل #send-period-container-{groupId} تزریق می‌شود
    $deliveryDateForOne = ['deliveryDateForOne' => [...dates], 'groupId' => 'store|seller_{id}', 'carrier_id' => id]
--}}
@if($deliveryDateForOne && count($deliveryDateForOne['deliveryDateForOne']))
    @php
        $groupId = $deliveryDateForOne['groupId'];
        $firstSelectable = null;
        foreach ($deliveryDateForOne['deliveryDateForOne'] as $d) {
            if ($d['is_selectable']) { $firstSelectable = $d['date']; break; }
        }
    @endphp
    <div class="send-period-container mt-3">
        <h6 class="font-14 mb-2"><i class="bi bi-calendar2-check-fill main-color-one-color ms-1"></i>زمان ارسال را انتخاب کنید:</h6>
        <div class="row gy-2">
            @foreach($deliveryDateForOne['deliveryDateForOne'] as $key => $DeliveryDate)
                <div class="col-4">
                    <label class="send-item d-block {{ $DeliveryDate['is_selectable'] ? '' : 'disabled' }} {{ $DeliveryDate['date'] == $firstSelectable ? 'active' : '' }}"
                           {{ $DeliveryDate['is_selectable'] ? '' : 'aria-disabled="true"' }}>
                        <h6 class="font-14 text-center mb-0 {{ $DeliveryDate['is_selectable'] ? '' : 'text-muted' }}">{{ $DeliveryDate['day_name'] }}</h6>
                        <p class="font-12 text-center mt-1 mb-0 text-muted">{{ $DeliveryDate['displayDate'] }}</p>
                        <input type="radio" class="d-none delivery-Date" name="carrier_date_{{ $groupId }}"
                               value="{{ $DeliveryDate['date'] }}" id="date-{{ $groupId }}-{{ $key }}"
                               {{ $DeliveryDate['date'] == $firstSelectable ? 'checked' : '' }}
                               {{ $DeliveryDate['is_selectable'] ? '' : 'disabled' }}>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- شهرهای تحت پوشش حامل — فرگمنت AJAX — سانیار فاز J
    قرارداد SellerCarrierController@cities : $carrier (eager: cities.province)
    بدون اسکریپت — ساده و سازگار با RTL --}}
@php
    $grouped_cities = $carrier->cities->groupBy(function ($city) {
        return $city->province?->name ?? 'نامشخص';
    });
@endphp
@if($grouped_cities->count())
    @foreach($grouped_cities as $province_name => $cities)
        <h6 class="fw-bold font-14 mb-2">
            <i class="bi bi-geo-alt main-color-one-color ms-1"></i>{{ $province_name }}
        </h6>
        <div class="d-flex flex-wrap gap-1 mb-3">
            @foreach($cities as $city)
                <span class="badge rounded-pill bg-light text-dark font-12 border">{{ $city->name }}</span>
            @endforeach
        </div>
        @if(!$loop->last)
            <hr class="my-2">
        @endif
    @endforeach
@else
    <div class="text-center text-muted font-14 py-3">
        <i class="bi bi-geo main-color-one-color font-24 d-block mb-2"></i>
        برای این روش ارسال شهری ثبت نشده است.
    </div>
@endif

{{-- برچسب مرسوله تمام‌عرض — سانیار فاز I | قرارداد: $order --}}
<div class="ship-label mb-2" dir="rtl">
    <div class="row g-0">
        {{-- مشخصات گیرنده --}}
        <div class="col-6 p-2">
            <ul class="list-unstyled font-13 mb-1">
                <li><b>گیرنده:</b> <span>{{ $order->name }}</span></li>
                <li><b>نشانی:</b> <span>{{ $order->province->name ?? '' }} - {{ $order->city->name ?? '' }} - {{ $order->address }}</span></li>
                <li><b>کدپستی:</b> <span>{{ $order->postal_code }}</span></li>
                <li><b>تلفن:</b> <span>{{ $order->mobile }}</span></li>
                <li><b>تاریخ سفارش:</b> <span>{{ jdate($order->created_at)->format('Y/m/d') }}</span></li>
            </ul>
            <div class="label-barcode">
                <img src="{{ barcode($order->id) }}" alt="بارکد مرسوله {{ $order->id }}">
            </div>
        </div>

        {{-- مشخصات فرستنده --}}
        <div class="col-6 p-2 print-border">
            <h4 class="text-center font-16 py-2 m-0 border-bottom">
                @if(option('factor_logo'))
                    <img src="{{ asset(option('factor_logo')) }}" alt="لوگوی فروشگاه"
                         style="max-height:48px;object-fit:contain;" class="ms-1">
                @endif
                {{ option('info_site_title', config('front.demo.name')) }}
            </h4>
            <ul class="list-unstyled font-13 mt-2 mb-1">
                <li><b>فرستنده:</b> <span>{{ seller_info()->business_name ?? option('info_site_title', config('front.demo.name')) }}</span></li>
                <li><b>نشانی:</b> <span>{{ option('info_address') }}</span></li>
                <li><b>کدپستی:</b> <span>{{ option('info_postal_code') }}</span></li>
                <li><b>تلفن:</b> <span>{{ option('info_tel') }}</span></li>
                <li><b>وب‌سایت:</b> <span>{{ config('app.url') }}</span></li>
            </ul>
        </div>
    </div>
    <div class="print-border-top">
        <ul class="d-flex justify-content-around list-unstyled font-13 m-0 p-2">
            <li><b>شناسه سفارش:</b> <span>{{ $order->id }}</span></li>
            <li><b>روش حمل‌ونقل:</b> <span>{{ $order->carrier->title ?? 'پیک' }}</span></li>
            <li><b>روش پرداخت:</b> <span>{{ $order->carrier && $order->carrier->carrige_forward ? 'پس‌کرایه' : 'پرداخت آنلاین' }}</span></li>
        </ul>
    </div>
</div>

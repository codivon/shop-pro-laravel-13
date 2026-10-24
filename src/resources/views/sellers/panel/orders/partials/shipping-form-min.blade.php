{{-- لیبل پستی کوچک ۱۰×۷ — سانیار فاز I | قرارداد: $order --}}
<div class="ship-label-min mb-1 rounded-1" dir="rtl">
    <div class="row g-0 h-100">
        {{-- ستون کناری: لوگو / دامنه / تاریخ / حامل --}}
        <div class="col-3 min-col-side p-1 text-center">
            @if(option('info_logo'))
                <div class="pb-1 border-bottom">
                    <img src="{{ asset(option('info_logo')) }}" alt="لوگو"
                         style="width:90%;height:64px;object-fit:contain;">
                </div>
            @endif
            <div class="py-1 border-bottom font-10">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url') }}</div>
            <div class="py-1 border-bottom font-10">{{ jdate($order->created_at)->format('Y/m/d') }}</div>
            <div class="pt-1">
                @if($order->carrier && $order->carrier->image)
                    <img src="{{ asset($order->carrier->image) }}" alt="{{ $order->carrier->title }}"
                         style="width:100%;height:44px;object-fit:contain;">
                @else
                    <p class="font-10 mb-0">{{ $order->carrier->title ?? 'پیک' }}</p>
                @endif
            </div>
        </div>

        {{-- ستون اصلی --}}
        <div class="col-9 p-2">
            <p class="font-10"><b>فرستنده:</b> {{ seller_info()->business_name ?? option('info_site_title', config('front.demo.name')) }}</p>
            <div class="border-bottom pb-1">
                <p class="font-10">{{ option('info_address') }} | کدپستی: {{ option('info_postal_code') }} | تلفن: {{ option('info_tel') }}</p>
            </div>
            <div class="mt-1">
                <p class="font-10"><b>نشانی گیرنده:</b> {{ $order->province->name ?? '' }} - {{ $order->city->name ?? '' }} - {{ $order->address }}</p>
            </div>
            <div class="row g-0 min-row mt-2">
                <div class="col-6 print-border p-1">
                    <p class="font-10"><b>گیرنده:</b> {{ $order->name }}</p>
                </div>
                <div class="col-6 p-1">
                    <p class="font-10"><b>تلفن:</b> {{ $order->mobile }}</p>
                </div>
                <div class="col-6 print-border p-1">
                    <p class="font-10"><b>کدپستی:</b> {{ $order->postal_code }}</p>
                </div>
                <div class="col-6 p-1">
                    <p class="font-10"><b>شناسه سفارش:</b> {{ $order->id }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

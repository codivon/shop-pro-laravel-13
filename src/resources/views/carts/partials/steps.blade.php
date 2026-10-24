{{-- نوار مراحل خرید (۳ مرحله) — $step : شماره مرحله فعال جاری (1=سبد، 2=چک‌اوت، 3=نتیجه) ؛ $done : تعداد مراحل کامل‌شده قبل از این --}}
@php
    $step = $step ?? 1;
    $done = $done ?? ($step - 1);
    $steps = [
        1 => ['label' => 'سبد خرید', 'url' => route('front.cart')],
        2 => ['label' => 'جزییات پرداخت', 'url' => $step > 2 ? route('front.checkout') : '#'],
        3 => ['label' => 'تکمیل سفارش', 'url' => '#'],
    ];
@endphp
<div class="line-step-container d-sm-block d-none mb-4">
    <div class="line-step">
        <div class="line-step-boxs">
            @foreach($steps as $num => $item)
                <div class="line-step-box {{ $num <= $done ? 'complete' : ($num == $step ? 'complete current' : 'disabled') }}">
                    <a href="{{ $item['url'] }}">
                        <p>{{ $item['label'] }}</p>
                        <div class="icon">{{ $num }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

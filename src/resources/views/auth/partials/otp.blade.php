{{-- پارشال OTP سانیار (فاز E) — پنج خانه کد تایید + نوار شمارش معکوس ارسال مجدد
    ورودی‌ها: $resend_time (unix ts)، $resend_action (مسیر ارسال مجدد) --}}

@php
    $otp_length = 5;
    $resend_action = $resend_action ?? route('front.index');
@endphp

<div id="otp-input" data-length="{{ $otp_length }}">
    @for ($i = 0; $i < $otp_length; $i++)
        <input type="tel" inputmode="numeric" maxlength="2" autocomplete="one-time-code"
               placeholder="." aria-label="رقم شماره {{ $i + 1 }} کد تایید"
               @if ($i === 0) autofocus @endif />
    @endfor
    <input type="hidden" name="verify_code" id="otp-value" />
</div>

<div class="countDownContainer">
    <div class="countdown-bar" id="countdownB"
         data-resend="{{ $resend_time }}"
         data-total="120"
         data-action="{{ $resend_action }}">
        <div></div>
        <div></div>
    </div>
</div>

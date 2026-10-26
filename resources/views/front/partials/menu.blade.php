{{--
    منوی فرانت ماژول CustomerClub (باشگاه مشتریان)
    این فایل در سایدبار پنل کاربری @include می‌شود (FrontModuleMenuRegistry).
    --------------------------------------------------------------------------
    • درس فاز ۸۹: لینک «راهنما و آموزش» در منوی فرانت نیست — راهنما به‌صورت
      بنر درون‌صفحه‌ای داخل خود باشگاه ارائه می‌شود.
    • الزام کاربر: با club_enabled خاموش شود، آیتم پنهان است.
    • با loyalty_enabled خاموش شود، نشان امتیاز هم پنهان می‌شود.
--}}
@php
    use Modules\CustomerClub\Models\ClubSetting;
    use Modules\CustomerClub\Services\LoyaltyService;

    $ccClubOn = ClubSetting::enabled('club_enabled');
    $ccLoyOn  = ClubSetting::enabled('loyalty_enabled');
    $ccBalance = ($ccClubOn && $ccLoyOn && auth()->check()) ? LoyaltyService::balance(auth()->id()) : 0;
@endphp

@if ($ccClubOn)
<li class="nav-item">
    <a href="{{ route('front.club.index') }}" class="profile-menu-url {{ request()->routeIs('front.club.*') ? 'active' : '' }}">
        <span class="fas fa-award"></span>
        باشگاه مشتریان
        @if ($ccLoyOn && $ccBalance > 0)
            <span class="badge badge-primary ml-1" style="font-size: 12px;" title="امتیاز فعلی">{{ number_format($ccBalance) }} امتیاز</span>
        @endif
    </a>
</li>
@endif

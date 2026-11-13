{{-- منوی کشویی حساب کاربری — مارکاپ سانیار --}}
@if(auth('web')->check())
    <ul class="dropdown-menu">
        @if (auth('web')->user()->level == 'admin' || auth('web')->user()->level == 'creator')
            <li><a href="{{ route('np.dashboard') }}" class="dropdown-item"><i class="bi bi-speedometer2 me-2"></i>پنل مدیریت</a></li>
        @endif
        <li><a href="{{ route('front.user.profile') }}" class="dropdown-item"><i class="bi bi-house-door me-2"></i>پروفایل</a></li>
        <li><a href="{{ route('front.orders.index') }}" class="dropdown-item"><i class="bi bi-cart-check me-2"></i>سفارش های من</a></li>
        <li><a href="{{ route('front.wallet.index') }}" class="dropdown-item"><i class="bi bi-wallet2 me-2"></i>کیف پول</a></li>
        <li><a href="{{ route('front.addresses.index') }}" class="dropdown-item"><i class="bi bi-pin-map me-2"></i>آدرس های من</a></li>
        <li><a href="{{ route('front.notifications.index') }}" class="dropdown-item"><i class="bi bi-bell me-2"></i>پیام ها و اطلاعیه ها</a></li>
        <li><a href="{{ route('front.user.comments') }}" class="dropdown-item"><i class="bi bi-chat-dots me-2"></i>نظرات من</a></li>
        <li><a href="{{ route('front.tickets.index') }}" class="dropdown-item"><i class="bi bi-question-circle me-2"></i>درخواست پشتیبانی</a></li>
        <li><a href="{{ route('front.favorites.index') }}" class="dropdown-item"><i class="bi bi-heart me-2"></i>محصولات مورد علاقه</a></li>
        <li><a href="{{ route('front.user.user-history') }}" class="dropdown-item"><i class="bi bi-clock-history me-2"></i>بازدید های اخیر</a></li>
        <li><a href="{{ route('logout') }}" class="dropdown-item mct-hover"><i class="bi bi-arrow-right-square me-2"></i>خروج از حساب کاربری</a></li>
    </ul>
@else
    <ul class="dropdown-menu">
        <li><a href="{{ route('login') }}" class="dropdown-item"><i class="bi bi-box-arrow-in-left me-2"></i>ورود به سایت</a></li>
        <li><a href="{{ route('register') }}" class="dropdown-item"><i class="bi bi-person-plus me-2"></i>ثبت نام</a></li>
    </ul>
@endif

<!--start .author-author__info-->
@if(auth('web')->check())

    <ul class="nav float-left">
    <li class="nav-item account dropdown">
        <a class="nav-link" href="#" data-toggle="dropdown" aria-haspopup="true"
           aria-expanded="false">
            <span class="label-dropdown">حساب کاربری</span>
            <i class="mdi mdi-account-circle-outline"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-sm dropdown-menu-left">

            @if (auth('web')->user()->level == 'admin' || auth('web')->user()->level == 'creator')
                <a class="dropdown-item" href="{{ route('np.dashboard') }}">
                    پنل مدیریت
                </a>
            @endif
            <a class="dropdown-item" href="{{ route('front.user.profile') }}">
                <i class="mdi mdi-account-card-details-outline"></i>پروفایل
            </a>
            <a class="dropdown-item" href="{{ route('front.orders.index') }}">
                <i class="mdi mdi-account-edit-outline"></i>سفارشات من
            </a>
            <div class="dropdown-divider" role="presentation"></div>
            <a class="dropdown-item" href="{{ route('logout') }}">
                <i class="mdi mdi-logout-variant"></i>خروج
            </a>
        </div>
    </li>
</ul>
@else
    <ul class="nav float-left">
        <li class="nav-item account dropdown">
            <a class="nav-link" href="#" data-toggle="dropdown" aria-haspopup="true"
               aria-expanded="false">
                <span class="label-dropdown">حساب کاربری</span>
                <i class="mdi mdi-account-circle-outline"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-sm dropdown-menu-left">
                <a class="dropdown-item" href="{{ route('login') }}">
                    <i class="mdi mdi-account-card-details-outline"></i>ورود به سایت
                </a>
                <a class="dropdown-item" href="{{ route('register') }}">
                    <i class="mdi mdi-account-edit-outline"></i>ثبت نام
                </a>
            </div>
        </li>
    </ul>
@endif


<!--end /.author-author__info-->

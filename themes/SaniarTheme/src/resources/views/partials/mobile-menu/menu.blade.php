{{-- منوی موبایل سانیار (offcanvas) --}}
<ul class="rm-item-menu navbar-nav">
    <li class="nav-item bg-ul-f7"><a href="{{ route('front.index') }}" class="nav-link">صفحه
            اصلی</a>
    </li>
    @foreach($menus as $menu)
        @include('front::partials.mobile-menu.child-menu')
    @endforeach
</ul>

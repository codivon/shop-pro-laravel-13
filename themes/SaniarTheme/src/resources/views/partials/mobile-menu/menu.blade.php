
<ul class="nav-categories ul-base">
    @foreach($menus as $menu)
        @include('front::partials.mobile-menu.child-menu')
    @endforeach
</ul>

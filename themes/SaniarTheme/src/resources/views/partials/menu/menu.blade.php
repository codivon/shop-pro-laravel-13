<nav class="main-menu dt-sl">
    <ul class="list float-right hidden-md new-list-menu">
        @foreach($menus as $menu)
            @include('front::partials.menu.child-menu')
        @endforeach

    </ul>
</nav>

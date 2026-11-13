{{-- آیتم منوی موبایل سانیار --}}
@if($menu->menus->isEmpty())

    @switch($menu->type)
        @case('static')
            @include('front::partials.mobile-menu.static-menus', ['menu' => $menu])
            @break

        @case('category')
            @include('front::partials.mobile-menu.child-category', ['category' => $menu->category])
            @break

        @default
            <li class="nav-item bg-ul-f7">
                <a href="{{ $menu->link ?: '#' }}" class="nav-link">{!! menu_icon($menu->icon) !!}{{ $menu->title }}</a>
            </li>
    @endswitch
@else
    <li class="nav-item bg-ul-f7">
        <a href="{{ $menu->type === 'category' ? $menu->category->link : ($menu->link ?: '#') }}" class="nav-link">{!! menu_icon($menu->icon) !!}{{ $menu->type === 'category' ? $menu->category->title : $menu->title }}</a>
        <span class="showSubMenu"><i class="bi bi-chevron-left"></i></span>
        <ul class="navbar-nav h-0">
            @if($menu->type === 'category' && $menu->category->getCategoriesCount())
                <li class="nav-item">
                    <a class="nav-link" href="{{ $menu->category->link }}">همه دسته بندی های {{ $menu->category->title }}</a>
                </li>
                @foreach (front_category_children($menu->category) as $childCategory)
                    @include('front::partials.mobile-menu.child-category', ['category' => $childCategory])
                @endforeach
            @endif

            @foreach ($menu->childrenmenus as $childMenu)
                @include('front::partials.mobile-menu.child-menu', ['menu' => $childMenu])
            @endforeach
        </ul>
    </li>
@endif

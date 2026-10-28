@if($menu->menus->isEmpty())

    @switch($menu->type)
        @case('static')
        @include('front::partials.mobile-menu.static-menus', ['menu' => $menu])
        @break

        @case('category')

        @include('front::partials.mobile-menu.child-category', ['category' => $menu->category])
        @break

        @default

        <li>
            <a href="{{ $menu->link ?: '#' }}">{!! menu_icon($menu->icon) !!}{{ $menu->title }}</a>
        </li>

    @endswitch
@else

    @switch($menu->type)
        @case('category')

        <li class="has-sub">
            <a href="{{ $menu->category->link }}">{!! menu_icon($menu->icon) !!}{{ $menu->category->title }}</a>
            <ul>

                @if($menu->category->getCategoriesCount())
                    @foreach (front_category_children($menu->category) as $childCategory)
                        @include('front::partials.mobile-menu.child-category', ['category' => $childCategory])
                    @endforeach
                @endif

                @foreach ($menu->childrenmenus as $childMenu)
                    @include('front::partials.mobile-menu.child-menu', ['menu' => $childMenu])
                @endforeach

            </ul>
        </li>

        @break

        @default
        <li class="has-sub">
            <a href="{{ $menu->link ?: '#' }}">{!! menu_icon($menu->icon) !!}{{ $menu->title }}</a>
            <ul>
                @foreach ($menu->childrenmenus as $childMenu)
                    @include('front::partials.mobile-menu.child-menu', ['menu' => $childMenu])
                @endforeach
            </ul>
        </li>


    @endswitch
@endif

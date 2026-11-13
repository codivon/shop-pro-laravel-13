{{-- آیتم منوی سطح اول — مارکاپ سانیار --}}

@if($menu->menus->isEmpty())

    @switch($menu->type)
        @case('static')
            @include('front::partials.menu.static-menus', ['menu' => $menu])
            @break

        @case('category')

            @include('front::partials.menu.child-menucategory', ['menu' => $menu])
            @break

        @case('normal')
        @case('megamenu')

            <li class="nav-item">
                <a class="nav-link border-animate fromCenter" href="{{ $menu->link ?: '#' }}">{!! menu_icon($menu->icon) !!}{{ $menu->title }}</a>
            </li>

    @endswitch

@else

    @switch($menu->type)
        @case('category')

            {{-- منوی دسته‌بندی — مگای دوم سانیار (sub-menu mega-container) --}}
            <li class="nav-item">
                <a href="{{ $menu->category->link }}" class="nav-link border-animate fromCenter">{!! menu_icon($menu->icon) !!}{{ $menu->category->title }}</a>
                <ul class="list-unstyled shadow-lg back-menu sub-menu mega-container">
                    @if(!$menu->category->getCategoriesCount() || !$menu->children)
                        <li><a href="{{ $menu->category->link }}">{{ $menu->title ?: $menu->category->title }}</a></li>
                    @else
                        <li><a class="title" href="{{ $menu->category->link }}">همه دسته بندی های {{ $menu->category->title }}</a></li>
                        @foreach (front_category_children($menu->category) as $childCategory)
                            @include('front::partials.menu.child-category', ['category' => $childCategory])
                        @endforeach
                    @endif

                    @foreach ($menu->childrenmenus as $childMenu)
                        @include('front::partials.menu.child-menu', ['menu' => $childMenu])
                    @endforeach
                </ul>
            </li>
            @break

        @case('megamenu')
            @include('front::partials.menu.megamenu', ['menu' => $menu])

            @break

        @default

            {{-- منوی ساده با زیرمنو — level-one / level-two سانیار --}}
            <li class="nav-item">
                <a class="nav-link border-animate fromCenter" href="{{ $menu->link ?: '#' }}">{!! menu_icon($menu->icon) !!}{{ $menu->title }}</a>
                <ul class="level-one">
                    @foreach ($menu->childrenmenus as $childMenu)
                        @include('front::partials.menu.child-menu-level', ['menu' => $childMenu])
                    @endforeach
                </ul>
            </li>
    @endswitch

@endif

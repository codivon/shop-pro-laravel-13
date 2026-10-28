@if(isset($menu->category))
    @if(!$menu->category->getCategoriesCount() || !$menu->children)
        <li class="item-menu-2">
            <a href="{{ $menu->category->link }}" class="list-category-menu-2"><i
                    class="{{ icon_class($menu->icon) }}" @if(icon_style($menu->icon)) style="{{ icon_style($menu->icon) }}" @endif></i>{{ $menu->category->title }}</a>

        </li>
    @else
        <li class="item-menu-2">
            <a href="{{ $menu->category->link }}" class="list-category-menu-2"><i
                    class="{{ icon_class($menu->icon) }}" @if(icon_style($menu->icon)) style="{{ icon_style($menu->icon) }}" @endif></i>{{ $menu->category->title }}</a>

            <ul class="megamenu-level-3 row">
                <li class="list-category">
                    <a href="{{ $menu->category->link }}" class="list-category-megamenu">همه دسته بندی
                        های {{ $menu->category->title }}</a>
                </li>
                @foreach (front_category_children($menu->category) as $childCategory)

                    @include('front::partials.megamenu.child-category', ['category' => $childCategory])
                @endforeach
            </ul>
        </li>

    @endif
@endif

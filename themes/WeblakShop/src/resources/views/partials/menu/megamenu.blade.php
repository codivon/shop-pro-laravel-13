<li class="item-list-menu megamenu-1 category nav-overlay">
    <a href="{{ $menu->link }}" class="list-category first after" style="font-weight: bold;"><i class="{{ icon_class($menu->icon) }}" @if(icon_style($menu->icon)) style="{{ icon_style($menu->icon) }}" @endif></i>
        {{ $menu->title }}</a>
    @if(count($menu->childrenmenus))
        <ul class="list-menu-level-2">
            @foreach($menu->childrenmenus as $childMenu)
                @switch($childMenu->type)
                    @case('category')
                        @include('front::partials.megamenu.category', ['menu' => $childMenu])
                    @break

                    @case('normal')

                        @break
                    @default

               @endswitch

            @endforeach
        </ul>
    @endif


</li>


<li class="item-list-menu megamenu-1 category nav-overlay">
    {{-- فاز ۱۱۷ — menu_icon: هم فونت‌آیکون، هم SVG/Lucide --}}
    <a href="{{ $menu->link ?: '#' }}" class="list-category first after" style="font-weight: bold;">{!! menu_icon($menu->icon) !!}
        {{ $menu->title }}</a>
    @if(count($menu->childrenmenus))
        <ul class="list-menu-level-2">
            @foreach($menu->childrenmenus as $childMenu)
                @switch($childMenu->type)
                    @case('category')
                        @include('front::partials.megamenu.category', ['menu' => $childMenu])
                    @break

                    {{-- فاز ۱۱۷ — فرزندان «منوی معمولی» داخل مگامنو قبلاً بی‌صدا دور انداخته
                         می‌شدند (case خالی) و ادمین فکر می‌کرد منو را نمی‌آورد --}}
                    @case('normal')
                        <li class="item-menu-2">
                            <a href="{{ $childMenu->link ?: '#' }}" class="list-category-menu-2">{!! menu_icon($childMenu->icon) !!}{{ $childMenu->title }}</a>
                        </li>
                    @break
                    @default

               @endswitch

            @endforeach
        </ul>
    @endif


</li>

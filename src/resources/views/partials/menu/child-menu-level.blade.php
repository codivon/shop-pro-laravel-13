{{-- آیتم‌های داخل منوی ساده level-one سانیار --}}
@if($menu->menus->isEmpty())
    <li><a href="{{ $menu->link ?: '#' }}">{!! menu_icon($menu->icon) !!}{{ $menu->title }}</a></li>
@else
    <li class="position-relative">
        <a href="{{ $menu->link ?: '#' }}">{!! menu_icon($menu->icon) !!}{{ $menu->title }} <i class="bi bi-chevron-left"></i></a>
        <ul class="level-two">
            @foreach ($menu->childrenmenus as $childMenu)
                @include('front::partials.menu.child-menu-level', ['menu' => $childMenu])
            @endforeach
        </ul>
    </li>
@endif

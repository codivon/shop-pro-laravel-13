
{{-- فاز ۱۱۷ — menu_icon برای آیکون (هم فونت‌آیکون، هم SVG/Lucide)؛ قبلاً آیکونِ منوی دسته‌بندی اصلاً رندر نمی‌شد --}}
@if(!$menu->category->getCategoriesCount() || !$menu->children)
    <li class="list-item">
        <a class="nav-link" href="{{ $menu->category->link }}">{!! menu_icon($menu->icon) !!}{{ $menu->title ?: $menu->category->title }}</a>
    </li>
@else
    <li class="list-item list-item-has-children menu-col-1">
        <a class="nav-link" href="{{ $menu->category->link }}">{!! menu_icon($menu->icon) !!}{{ $menu->category->title }}</a>
        <ul class="sub-menu nav">
            @foreach (front_category_children($menu->category) as $childCategory)
                @include('front::partials.menu.child-category', ['category' => $childCategory])
            @endforeach
        </ul>
    </li>
@endif

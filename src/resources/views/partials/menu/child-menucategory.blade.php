{{-- منوی نوع category سطح اول — سانیار --}}
@if(!$menu->category->getCategoriesCount() || !$menu->children)
    <li class="nav-item">
        <a class="nav-link border-animate fromCenter" href="{{ $menu->category->link }}">{!! menu_icon($menu->icon) !!}{{ $menu->title ?: $menu->category->title }}</a>
    </li>
@else
    <li class="nav-item">
        <a href="{{ $menu->category->link }}" class="nav-link border-animate fromCenter">{!! menu_icon($menu->icon) !!}{{ $menu->category->title }}</a>
        <ul class="list-unstyled shadow-lg back-menu sub-menu mega-container">
            <li><a class="title" href="{{ $menu->category->link }}">همه دسته بندی های {{ $menu->category->title }}</a></li>
            @foreach (front_category_children($menu->category) as $childCategory)
                @include('front::partials.menu.child-category', ['category' => $childCategory])
            @endforeach
        </ul>
    </li>
@endif

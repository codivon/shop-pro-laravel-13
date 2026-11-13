{{-- منوی static در موبایل سانیار --}}
@switch($menu->static_type)
    @case('products')
        @if($productcats->count())
            <li class="nav-item bg-ul-f7">
                <a href="{{ route('front.products.index') }}" class="nav-link">{{ $menu->title }}</a>
                <span class="showSubMenu"><i class="bi bi-chevron-left"></i></span>
                <ul class="navbar-nav h-0">
                    @foreach($productcats as $category)
                        @include('front::partials.mobile-menu.child-category', ['category' => $category])
                    @endforeach
                </ul>
            </li>
        @endif
        @break

    @case('posts')
        @if($postcats->count())
            <li class="nav-item bg-ul-f7">
                <a href="{{ route('front.blogs.index') }}" class="nav-link">{{ $menu->title }}</a>
                <span class="showSubMenu"><i class="bi bi-chevron-left"></i></span>
                <ul class="navbar-nav h-0">
                    @foreach($postcats as $category)
                        @include('front::partials.mobile-menu.child-category', ['category' => $category])
                    @endforeach
                </ul>
            </li>
        @endif
        @break
@endswitch

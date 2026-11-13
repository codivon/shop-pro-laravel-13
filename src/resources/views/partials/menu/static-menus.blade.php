{{-- منوی نوع static — سانیار --}}
@switch($menu->static_type)
    @case('products')
        @if($productcats->count())
            @include('front::partials.staticmenu.products', ['menu' => $menu,'productcats'=>$productcats])
        @endif
        @break

    @case('posts')
        @if($postcats->count())
            <li class="nav-item">
                <a href="{{ route('front.blogs.index') }}" class="nav-link border-animate fromCenter">{!! menu_icon($menu->icon) !!}{{ $menu->title }}</a>
                <ul class="list-unstyled shadow-lg back-menu sub-menu mega-container">
                    <li><a class="title" href="{{ route('front.blogs.index') }}">{{ $menu->title }}</a></li>
                    @foreach($postcats as $category)
                        @include('front::partials.menu.child-category', ['category' => $category])
                    @endforeach
                </ul>
            </li>
        @endif
        @break
@endswitch

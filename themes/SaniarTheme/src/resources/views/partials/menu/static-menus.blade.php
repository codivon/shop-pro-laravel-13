@switch($menu->static_type)
    @case('products')
        @if($productcats->count())
            @include('front::partials.staticmenu.products', ['menu' => $menu,'productcats'=>$productcats])


        @endif

        @break

    @case('posts')
        @if($postcats->count())

            <!-- mega menu 5 column -->
            <li class="list-item list-item-has-children menu-col-1">
                <a class="nav-link" href="{{ route('front.blogs.index') }}">{{ $menu->title }}</a>
                <ul class="sub-menu nav">
                    @foreach($postcats as $category)
                        @include('front::partials.menu.child-category', ['category' => $category])
                    @endforeach
                </ul>
            </li>
        @endif

        @break
@endswitch


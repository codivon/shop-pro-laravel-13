<div class="offcanvas offcanvas-start" tabindex="-1" id="responsiveMenu" aria-labelledby="responsiveMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="responsiveMenuLabel">{{ option('info_site_title', config('front.demo.name')) }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <a href="{{ route('front.index') }}" class="text-center d-block mb-3">
            <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" alt="{{ option('info_site_title', config('front.demo.name')) }}" class="img-fluid" width="200">
        </a>
        <div class="header-bottom-form mb-4 w-100">
            <div class="search-form">
                <form action="{{ route('front.products.index') }}" method="GET">
                    <div class="search-filed">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="جستجوی محصولات ..." class="form-control search-input">
                        <button type="submit" class="btn search-btn main-color-one-bg rounded-pill"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <ul class="rm-item-menu navbar-nav">
            <li class="nav-item bg-ul-f7">
                <a href="{{ route('front.index') }}" class="nav-link">صفحه اصلی</a>
            </li>

            {{-- دسته‌بندی محصولات (درختی) --}}
            @if($productcats->count())
                @foreach($productcats as $category)
                    <li class="nav-item bg-ul-f7">
                        <a href="{{ $category->link }}" class="nav-link">{{ $category->title }}</a>
                        @if(front_category_children($category)->count())
                            <span class="showSubMenu"><i class="bi bi-chevron-left"></i></span>
                            <ul class="navbar-nav h-0">
                                @foreach(front_category_children($category) as $childCategory)
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ $childCategory->link }}">{{ $childCategory->title }}</a>
                                        @if(front_category_children($childCategory)->count())
                                            <span class="showSubMenu"><i class="bi bi-chevron-left"></i></span>
                                            <ul class="navbar-nav h-0 bg-ul-f7">
                                                @foreach(front_category_children($childCategory) as $grandChild)
                                                    <li class="nav-item"><a href="{{ $grandChild->link }}" class="nav-link">{{ $grandChild->title }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            @endif

            {{-- منوهای پنل --}}
            @foreach($menus as $menu)
                @if($menu->menus->isEmpty())
                    @switch($menu->type)
                        @case('static')
                            @if($menu->static_type == 'products')
                                <li class="nav-item bg-ul-f7"><a href="{{ route('front.products.index') }}" class="nav-link">{{ $menu->title }}</a></li>
                            @elseif($menu->static_type == 'posts')
                                <li class="nav-item bg-ul-f7"><a href="{{ route('front.blog.index') }}" class="nav-link">{{ $menu->title }}</a></li>
                            @endif
                            @break
                        @case('category')
                            <li class="nav-item bg-ul-f7"><a href="{{ $menu->category->link }}" class="nav-link">{{ $menu->category->title }}</a></li>
                            @break
                        @default
                            <li class="nav-item bg-ul-f7"><a href="{{ $menu->link }}" class="nav-link">{{ $menu->title }}</a></li>
                    @endswitch
                @else
                    <li class="nav-item bg-ul-f7">
                        <a href="{{ $menu->link }}" class="nav-link">{{ $menu->title }}</a>
                        <span class="showSubMenu"><i class="bi bi-chevron-left"></i></span>
                        <ul class="navbar-nav h-0">
                            @foreach($menu->childrenmenus as $childMenu)
                                <li class="nav-item"><a href="{{ $childMenu->link }}" class="nav-link">{{ $childMenu->title }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach

            <li class="nav-item bg-ul-f7">
                <a href="{{ route('front.products.specials') }}" class="nav-link">تخفیف ها و پیشنهاد ها</a>
            </li>
            <li class="nav-item bg-ul-f7">
                <a href="{{ route('front.blog.index') }}" class="nav-link">وبلاگ</a>
            </li>
            @if(option('multi_vendor_system_status'))
                <li class="nav-item bg-ul-f7">
                    <a href="{{ route('seller.index') }}" class="nav-link">در فروشگاه بفروشید</a>
                </li>
            @endif
        </ul>
    </div>
</div>

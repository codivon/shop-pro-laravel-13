<nav class="mega-menu d-lg-block d-none" aria-label="منوی اصلی">
    <div class="container-fluid">
        <div class="top-menu-parent">
            <div class="row align-items-center">
                <div class="col-lg-9">
                    <div class="top-menu-menu">
                        <ul class="navbar-nav align-items-center">
                            <li class="position-relative m-0"></li>

                            {{-- تب اصلی: دسته‌بندی محصولات (مگا تب) --}}
                            @if($productcats->count())
                                <li class="nav-item main-menu-head">
                                    <a href="{{ route('front.products.index') }}"
                                       class="nav-link border-animate main-color-one-bg fromCenter btn nav-active fw-bold">
                                        <i class="bi bi-list"></i>
                                        دسته‌بندی محصولات
                                    </a>
                                    <ul class="main-menu mega-container">
                                        @foreach($productcats as $category)
                                            <li @class(['main-menu-sub-active-li' => $loop->first])>
                                                <a href="{{ $category->link }}">
                                                    <i class="{{ $category->icon ?: 'bi bi-grid' }}"></i>
                                                    {{ $category->title }}
                                                </a>
                                                <ul class="main-menu-sub back-menu">
                                                    @foreach(front_category_children($category) as $childCategory)
                                                        @include('front::partials.menu.child-category', ['category' => $childCategory])
                                                    @endforeach
                                                </ul>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif

                            {{-- منوهای ساخته‌شده از پنل (Menu) --}}
                            @foreach($menus as $menu)
                                @if($menu->menus->isEmpty())
                                    @switch($menu->type)
                                        @case('static')
                                            @if($menu->static_type == 'products' && $productcats->count())
                                                <li class="nav-item">
                                                    <a href="{{ route('front.products.index') }}" class="nav-link border-animate fromCenter">
                                                        <i class="bi bi-grid"></i> {{ $menu->title }}
                                                    </a>
                                                </li>
                                            @elseif($menu->static_type == 'posts' && $postcats->count())
                                                <li class="nav-item">
                                                    <a href="{{ route('front.blog.index') }}" class="nav-link border-animate fromCenter">
                                                        <i class="bi bi-journal-text"></i> {{ $menu->title }}
                                                    </a>
                                                    <ul class="level-one">
                                                        @foreach($postcats as $postcat)
                                                            <li><a href="{{ route('front.articles.index', ['cat' => $postcat->slug]) }}">{{ $postcat->title }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endif
                                            @break

                                        @case('category')
                                            <li class="nav-item">
                                                <a href="{{ $menu->category->link }}" class="nav-link border-animate fromCenter">
                                                    <i class="bi bi-tags"></i> {{ $menu->category->title }}
                                                </a>
                                                <ul class="level-one">
                                                    @foreach($menu->category->categories as $childCategory)
                                                        <li><a href="{{ $childCategory->link }}">{{ $childCategory->title }}</a></li>
                                                    @endforeach
                                                    @foreach($menu->childrenmenus as $childMenu)
                                                        <li><a href="{{ $childMenu->link }}">{{ $childMenu->title }}</a></li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                            @break

                                        @default
                                            <li class="nav-item">
                                                <a href="{{ $menu->link }}" class="nav-link border-animate fromCenter">
                                                    <i class="{{ $menu->icon ?: 'bi bi-link-45deg' }}"></i> {{ $menu->title }}
                                                </a>
                                                @if($menu->childrenmenus->count())
                                                    <ul class="level-one">
                                                        @foreach($menu->childrenmenus as $childMenu)
                                                            <li><a href="{{ $childMenu->link }}">{{ $childMenu->title }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </li>
                                    @endswitch
                                @else
                                    <li class="nav-item">
                                        <a href="{{ $menu->link }}" class="nav-link border-animate fromCenter">
                                            <i class="{{ $menu->icon ?: 'bi bi-list-ul' }}"></i> {{ $menu->title }}
                                        </a>
                                        <ul class="list-unstyled shadow-lg back-menu sub-menu mega-container">
                                            @foreach($menu->childrenmenus as $childMenu)
                                                @if($childMenu->childrenmenus->count())
                                                    <li class="position-relative">
                                                        <a href="{{ $childMenu->link }}">{{ $childMenu->title }} <i class="bi bi-chevron-left"></i></a>
                                                        <ul class="level-two">
                                                            @foreach($childMenu->childrenmenus as $grandChild)
                                                                <li><a href="{{ $grandChild->link }}">{{ $grandChild->title }}</a></li>
                                                            @endforeach
                                                        </ul>
                                                    </li>
                                                @else
                                                    <li><a href="{{ $childMenu->link }}">{{ $childMenu->title }}</a></li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </li>
                                @endif
                            @endforeach

                            {{-- لینک‌های ثابت فروشگاه --}}
                            <li class="nav-item">
                                <a href="{{ route('front.products.specials') }}" class="nav-link border-animate fromCenter">
                                    <i class="bi bi-tag"></i> تخفیف ها و پیشنهاد ها
                                </a>
                            </li>
                            @if(option('multi_vendor_system_status'))
                                <li class="nav-item">
                                    <a href="{{ route('seller.index') }}" class="nav-link border-animate fromCenter">
                                        <i class="bi bi-shop"></i> در فروشگاه بفروشید
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="d-flex align-items-center justify-content-end">
                        @php $tel = option('info_tel', ''); @endphp
                        @if(!empty(option('social_whatsapp')))
                            <a href="{{ option('social_whatsapp') }}" class="main-color-three-bg btn border-0 d-flex align-items-center rounded-pill">
                                {{ $tel }}<i class="bi bi-whatsapp text-white ms-2"></i>
                            </a>
                        @endif
                        @if(!empty($tel))
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $tel) }}" class="main-color-two-bg btn border-0 d-flex align-items-center ms-3 rounded-pill">
                                {{ $tel }}<i class="bi bi-telephone-fill ms-2 text-white"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

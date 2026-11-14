{{-- مگامنوی اصلی سانیار (main-menu-head + mega-container) --}}
{{-- فاز ۱۷۲ — بازطراحی مطابق نمونهٔ کاربر:
     ستون راست: ردیف دسته‌ها با آیکون (آیکون بعد از عنوان، مثل نمونه)
     ستون چپ: پنل ثابت ۳ ستونه؛ گروه‌های منویی با تیتر پررنگ و فرزندان زیر خودشان --}}
<li class="nav-item main-menu-head">
    <a href="{{ $menu->link ?: '#' }}"
       class="nav-link border-animate main-color-one-bg fromCenter btn nav-active fw-bold">
        <i class="bi bi-list"></i>
        {{ $menu->title }}
    </a>
    <ul class="main-menu mega-container">
        @foreach($menu->childrenmenus as $childMenu)
            @switch($childMenu->type)
                @case('category')
                    {{-- فاز ۱۷۰ — data-cat-image: تصویر پس‌زمینهٔ دسته برای نمایش پایین-چپ پنل فرعی --}}
                    <li class="main-menu-sub-active-li" @if(!empty($childMenu->category->background_image) || !empty($childMenu->category->image)) data-cat-image="{{ asset($childMenu->category->background_image ?: $childMenu->category->image) }}" @endif>
                        <a href="{{ $childMenu->category->link }}">{{ $childMenu->title ?: $childMenu->category->title }}{!! menu_icon($childMenu->category->icon) !!}</a>
                        <ul class="main-menu-sub back-menu">
                            @if(!$childMenu->category->getCategoriesCount() || !$childMenu->children)
                                <li><a href="{{ $childMenu->category->link }}">{{ $childMenu->category->title }}</a></li>
                            @else
                                <li><a class="title" href="{{ $childMenu->category->link }}">همه دسته بندی های {{ $childMenu->category->title }}</a></li>
                                @foreach (front_category_children($childMenu->category) as $childCategory)
                                    <li><a href="{{ $childCategory->link }}">{{ $childCategory->title }}</a></li>
                                @endforeach
                            @endif

                            @foreach ($childMenu->childrenmenus as $childMenu2)
                                @if($childMenu2->type === 'normal')
                                    @if($childMenu2->menus->isEmpty())
                                        <li><a href="{{ $childMenu2->link ?: '#' }}">{{ $childMenu2->title }}</a></li>
                                    @else
                                        {{-- فاز ۱۷۲ — گروه با تیتر پررنگ؛ فرزندان داخل همان بلوک می‌مانند --}}
                                        <li class="snr-mega-group">
                                            <a class="title" href="{{ $childMenu2->link ?: '#' }}">{{ $childMenu2->title }}</a>
                                            <ul class="snr-mega-group-list">
                                                @foreach ($childMenu2->childrenmenus as $childMenu3)
                                                    <li><a href="{{ $childMenu3->type === 'category' ? $childMenu3->category->link : ($childMenu3->link ?: '#') }}">{{ $childMenu3->type === 'category' ? ($childMenu3->title ?: $childMenu3->category->title) : $childMenu3->title }}</a></li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @endif
                                @endif
                            @endforeach
                        </ul>
                    </li>
                    @break

                @case('normal')
                    @if($childMenu->menus->isEmpty())
                        <li>
                            <a href="{{ $childMenu->link ?: '#' }}">{{ $childMenu->title }}{!! menu_icon($childMenu->icon) !!}</a>
                        </li>
                    @else
                        <li class="main-menu-sub-active-li">
                            <a href="{{ $childMenu->link ?: '#' }}">{{ $childMenu->title }}{!! menu_icon($childMenu->icon) !!}</a>
                            <ul class="main-menu-sub back-menu">
                                @foreach ($childMenu->childrenmenus as $childMenu2)
                                    <li><a href="{{ $childMenu2->type === 'category' ? $childMenu2->category->link : ($childMenu2->link ?: '#') }}">{{ $childMenu2->type === 'category' ? ($childMenu2->title ?: $childMenu2->category->title) : $childMenu2->title }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                    @break

                @default
            @endswitch
        @endforeach
    </ul>
</li>

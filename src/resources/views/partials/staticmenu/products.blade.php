{{-- منوی محصولات (static_type=products) — مگامنوی اصلی سانیار --}}
<li class="nav-item main-menu-head">
    <a href="{{ route('front.products.index') }}"
       class="nav-link border-animate main-color-one-bg fromCenter btn nav-active fw-bold">
        <i class="{{ icon_class($menu->icon) }}" @if(icon_style($menu->icon)) style="{{ icon_style($menu->icon) }}" @endif></i>
        {{ $menu->title }}
    </a>
    @if(count($productcats))
        <ul class="main-menu mega-container">
            @foreach ($productcats as $category)
                {{-- فاز ۱۷۰ — تصویر پس‌زمینهٔ دسته برای نمایش پایین-چپ پنل فرعی مگامنو --}}
                <li class="main-menu-sub-active-li" @if(!empty($category->background_image) || !empty($category->image)) data-cat-image="{{ asset($category->background_image ?: $category->image) }}" @endif>
                    <a href="{{ $category->link }}"><i class="{{ icon_class($category->icon) }}" @if(icon_style($category->icon)) style="{{ icon_style($category->icon) }}" @endif></i>{{ $category->title }}</a>
                    @if ($category->getCategoriesCount())
                        <ul class="main-menu-sub back-menu">
                            <li><a class="title my-flex-baseline" href="{{ $category->link }}">همه دسته بندی های {{ $category->title }}</a></li>
                            {{-- FIX مقیاس: از relation ازقبل-لود شدهٔ getWithChilds استفاده می‌شود
                            (قبلاً به‌ازای هر دستهٔ ریشه یک کوئری اجرا می‌شد = N+1) --}}
                            @foreach ($category->categories->where('published', true)->values() as $childCategory)
                                <li><a href="{{ $childCategory->link }}">{{ $childCategory->title }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</li>

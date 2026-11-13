{{-- آیتم دسته‌بندی در منوی موبایل سانیار --}}
@if($category)
    @if(!$category->getCategoriesCount())
        <li class="nav-item bg-ul-f7">
            <a href="{{ $category->link }}" class="nav-link">{{ $category->title }}</a>
        </li>
    @else
        <li class="nav-item bg-ul-f7">
            <a href="{{ $category->link }}" class="nav-link">{{ $category->title }}</a>
            <span class="showSubMenu"><i class="bi bi-chevron-left"></i></span>
            <ul class="navbar-nav h-0 bg-ul-f7">
                <li class="nav-item">
                    <a class="nav-link" href="{{ $category->link }}">همه موارد این دسته</a>
                </li>
                @foreach (front_category_children($category) as $childCategory)
                    @include('front::partials.mobile-menu.child-category', ['category' => $childCategory])
                @endforeach
            </ul>
        </li>
    @endif
@endif

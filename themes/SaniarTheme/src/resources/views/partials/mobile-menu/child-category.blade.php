@if($category)
    @if(!$category->getCategoriesCount())
        <li>
            <a class="category-level-4" href="{{ $category->link }}">{{ $category->title }}</a>
        </li>
    @else

        <li class="has-sub">
            <a class="category-level-2" href="{{ $category->link }}">{{ $category->title }}</a>
            <ul>
                <li>
                    <a class="category-level-3" href="{{ $category->link }}"><i class="mdi mdi-chevron-left"></i>همه
                        موارد این دسته</a>
                </li>

                @foreach (front_category_children($category) as $childCategory)
                    @include('front::partials.mobile-menu.child-category', ['category' => $childCategory])
                @endforeach
            </ul>
        </li>
    @endif
@endif

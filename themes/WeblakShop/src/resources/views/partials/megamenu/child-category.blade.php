@if($category)
    @if(!$category->getCategoriesCount())
        <li class="item-megamenu-title">
            <a href="{{ $category->link }}" class="list-category-megamenu-3"><span>{{ $category->title }}<i
                        class="fa fa-angle-left"></i></span></a>
        </li>

    @else

        <li class="item-megamenu-title"><a href="{{ $category->link }}" class="list-category-megamenu-3"><span>{{ $category->title }}<i
                        class="fa fa-angle-left"></i></span></a></li>
        {{-- FIX مقیاس: relation ازقبل-لود شده به‌جای کوئری به‌ازای هر زیر‌دسته --}}
        @foreach ($category->categories->where('published', true)->values() as $childCategory)
            <li class="item-megamenu-item"><a href="{{ $childCategory->link }}"
                                              class="list-category-megamenu-3">{{ $childCategory->title }}</a></li>
        @endforeach

    @endif
@endif

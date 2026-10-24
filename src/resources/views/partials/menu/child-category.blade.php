{{--
    آیتم دسته‌بندی داخل ستون مگامنو سانیار (یک سطح — مطابق طراحی قالب)
    $category : Category
--}}
@if($category)
    <li><a href="{{ $category->link }}">{{ $category->title }}</a></li>
@endif

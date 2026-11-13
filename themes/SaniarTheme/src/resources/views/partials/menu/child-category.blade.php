{{-- آیتم دسته‌بندی داخل لیست‌های منو (sub-menu mega-container) --}}
@if(!$category->getCategoriesCount())
    <li><a href="{{ $category->link }}">{{ $category->title }}</a></li>
@else
    <li><a class="title" href="{{ $category->link }}">{{ $category->title }}</a></li>
    @foreach (front_category_children($category) as $childCategory)
        @include('front::partials.menu.child-category', ['category' => $childCategory])
    @endforeach
@endif

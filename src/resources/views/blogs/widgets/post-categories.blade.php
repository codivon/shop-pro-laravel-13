@php
    // سانیار فاز K — نوار دسته‌بندی مقالات (navbar-blog مرجع) کاملاً از DB
    // در حالت ویجت: متغیرها از get_widget؛ در حالت پیش‌فرض: از $categories
    if ($widget ?? null) {
        $variables = get_widget($widget);
        $categories = $variables['categories'] ?? collect();
    }
    $fallbackCats = $categories ?? collect();
@endphp
@if($fallbackCats->isNotEmpty())
    <div class="blog-categories-bar py-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="navbar-blog">
                        <nav class="navbar p-0" aria-label="دسته‌بندی مقالات">
                            <ul class="navbar-nav flex-row gap-1">
                                <li class="nav-item">
                                    <a href="{{ route('front.articles.index') }}" wire:navigate
                                       class="nav-link {{ ! request('cat') ? 'active' : '' }}">
                                        <i class="bi bi-newspaper font-20 me-2"></i>همه مطالب
                                    </a>
                                </li>
                                @foreach($fallbackCats as $category)
                                    <li class="nav-item">
                                        <a href="{{ route('front.articles.index', ['cat' => $category->slug]) }}" wire:navigate
                                           class="nav-link {{ request('cat') == $category->slug ? 'active' : '' }}">
                                            {{ $category->title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

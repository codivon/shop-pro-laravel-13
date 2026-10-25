<div class="main-menu menu-fixed menu-accordion menu-shadow {{ user_option('theme_color') == 'light' ? 'menu-light' : 'menu-dark' }}"
     data-scroll-to-active="true">
    <div class="navbar-header">
        <div class="form-group">
            <div class="row">
                <input type="hidden" name="type" value="postcat">
                <div class="pl-0-5">
                    <div class=' position-relative'>
                        <input id="sidebar-search-input" type="text" class="form-control" name="sidebar-search-input"
                               placeholder="بخشی از عنوان مثل : افزودن مح">
                    </div>
                </div>
                <div class="">
                    <button type="submit" class="btn personal-success-btn waves-effect waves-light w-100 pr-0-5 pl-0-5">
                        جستجو
                    </button>
                </div>
            </div>


            <div class="sidebar--account-section  mt-1 dropdown dropdown-user nav-item">
                <div class="inner nav-link dropdown-user-link" data-toggle="dropdown">
                    <div class="user-avatar"><img class="shadow-1" width="60" height="60"
                                                  src="{{auth()->user()->imageUrl}}"
                                                  alt="{{auth('adminPanel')->user()->full_name}}"></div>
                    <div class="user-details ml-1">
                        <span class="name mb-1">{{auth('adminPanel')->user()->full_name}}</span>
                        <span class="type">مدیریت</span>
                    </div>
                    <div class="user-settings"><i class=" fas fa-angle-down"></i></div>
                </div>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="{{ route('admin.admin.profile.show') }}"><i
                            class="feather icon-user"></i> ویرایش پروفایل</a>
                    <div class="dropdown-divider"></div>
                    @if(Auth::user('adminPanel')->id=='1')
                        <a class="dropdown-item" href="{{ route('admin.cache-clear') }}"><i class="fa fa-trash"></i> پاک
                            کردن حافظه کش </a>

                    @endif
                    @can('notifications.panel')
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('admin.notifications.index') }}"><i
                                class="fa-solid fa-bell"></i> اعلان ها</a>
                    @endcan
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('admin.logout') }}"><i class="feather icon-power"></i> خروج
                        از حساب</a>
                </div>
            </div>

        </div>

    </div>
    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            <li class="title">نمای کلی</li>
            <li class="{{ active_class('admin.dashboard') }} nav-item"><a href="{{ route('admin.dashboard') }}">
                    <i class="fa-solid fa-landmark"></i>
                    <span class="menu-title">داشبورد</span>
                </a>
            </li>
            <li class="{{ active_class('admin.sessions.index') }} nav-item">
                <a class="d-flex align-items-center" href="{{ route('admin.sessions.index') }}">
                    <i class="feather icon-monitor"></i>
                    <span class="menu-title text-truncate">نشست‌های فعال</span>
                </a>
            </li>
            @can('sessions.blocked')
            <li class="nav-item {{ active_class('admin.sessions.blocked-list') }}">
                <a class="d-flex align-items-center" href="{{ route('admin.sessions.blocked-list') }}">
                    <i class="feather icon-shield-off"></i>
                    <span class="menu-title text-truncate">دستگاه‌های بلاک شده</span>
                </a>
            </li>
            @endcan


            @can('pulse.monitor')
                <li class="nav-item {{ request()->routeIs('admin.pulse.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.pulse.index') }}"
                       class="d-flex align-items-center {{ request()->routeIs('admin.pulse.*') ? 'active' : '' }}">

                        {{-- آیکون با نقطه لایو --}}
                        <span style="position:relative; display:inline-flex; align-items:center; margin-left: 10px;">
            <i class="feather icon-activity"
               style="{{ request()->routeIs('admin.pulse.*') ? 'color:#a78bfa;' : '' }}"></i>
            {{-- نقطه لایو سبز ----}}
            <span style="
                position:absolute; top:-2px; right:-4px;
                width:6px; height:6px; border-radius:50%;
                background:#00d4aa;
                box-shadow:0 0 0 0 rgba(0,212,170,.5);
                animation:navLivePulse 1.8s infinite;
            "></span>
        </span>

                        <span class="menu-title">مانیتور سیستم</span>
                    </a>
                </li>
            @endcan

            @can('activity-log.index')
                <li class="nav-item {{ active_class('admin.activity-log.index') }}">
                    <a class="d-flex align-items-center" href="{{ route('admin.activity-log.index') }}">
                        <i class=" fab fa-slideshare"></i>
                        <span class="menu-title text-truncate">فعالیت های اخیر</span>
                    </a>
                </li>
            @endcan

            @can('packages')
                <li class="nav-item has-sub {{ open_class(['admin.packages.*']) }}">
                    <a>
                        <i class=" fab fa-buffer"></i><span class="menu-title"> پکیج ها</span></a>
                    <ul class="menu-content">
                        @can('packages.index')
                            <li class="{{ active_class('admin.packages.index') }}">
                                <a href="{{ route('admin.packages.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">پکیج‌ها و قالب‌ها</span></a>
                            </li>
                        @endcan

                        @can('packages.installed')
                            <li class="{{ active_class('admin.packages.installed') }}">
                                <a href="{{ route('admin.packages.installed') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">نصب شده</span></a>
                            </li>
                        @endcan


                    </ul>
                </li>

            @endcan


            <li class="title">مدیریت محتوا </li>
            @can('posts')

                <li class="nav-item has-sub {{ open_class(['admin.posts.*']) }}">
                    <a>
                        <i class="fas fa-sticky-note"></i><span class="menu-title"> وبلاگ</span></a>
                    <ul class="menu-content">
                        @can('posts.index')
                            <li class="{{ active_class('admin.posts.index') }}">
                                <a href="{{ route('admin.posts.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست نوشته ها</span></a>
                            </li>
                        @endcan

                        @can('posts.create')
                            <li class="{{ active_class('admin.posts.create') }}">
                                <a href="{{ route('admin.posts.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد نوشته</span></a>
                            </li>
                        @endcan

                        @can('posts.category')
                            <li class="{{ active_class('admin.posts.categories.index') }}">
                                <a href="{{ route('admin.posts.categories.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">دسته بندی ها</span></a>
                            </li>
                        @endcan


                    </ul>
                </li>

                @can('posts.comments')
                    <li class=" nav-item">
                        <a href="{{ route('admin.comments.posts') }}">
                            <i class=" far fa-comment-dots"></i>
                            <span class="menu-title"> دیدگاه ها </span>
                        </a>
                    </li>
                @endcan
            @endcan

            @can('tags')
            <li class="{{ active_class('admin.tags.index') }}">
                <a href="{{ route('admin.tags.index') }}"><i
                        class="fa-solid fa-tag"></i><span class="menu-item"> برچسب ها</span></a>
            </li>
            @endcan

            @can('searches')
                <li class="{{ active_class('admin.searches.index') }} nav-item">
                    <a href="{{ route('admin.searches.index') }}">
                        <i class="fa-solid fa-search"></i>
                        <span class="menu-title"> جستجوها</span>
                    </a>
                </li>
            @endcan

            @can('pages')
                <li class="nav-item has-sub {{ open_class(['admin.pages.*']) }}"><a><i
                            class="fa-solid fa-file"></i><span class="menu-title"> صفحات</span></a>
                    <ul class="menu-content">
                        @can('pages.index')
                            <li class="{{ active_class('admin.pages.index') }}">
                                <a href="{{ route('admin.pages.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست صفحات</span></a>
                            </li>
                        @endcan

                        @can('pages.create')
                            <li class="{{ active_class('admin.pages.create') }}">
                                <a href="{{ route('admin.pages.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد صفحه</span></a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan



            <li class="title"> امور ظاهری</li>
            @can('themes')
                <li class="nav-item has-sub {{ open_class(['admin.themes.*', 'admin.widgets.*']) }}"><a><i
                            class="fa-solid fa-boxes-packing"></i><span class="menu-title">قالب </span></a>
                    <ul class="menu-content">
                        @can('themes.index')
                            <li class="{{ active_class('admin.themes.index') }}">
                                <a href="{{ route('admin.themes.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست قالب ها</span></a>
                            </li>
                        @endcan

                        @can('themes.create')
                            <li class="{{ active_class('admin.themes.create') }}">
                                <a href="{{ route('admin.themes.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">افزودن قالب جدید</span></a>
                            </li>
                        @endcan

                        @can('themes.settings')
                            @if(config('front.settings.fields'))
                                <li class="{{ active_class('admin.themes.settings') }}">
                                    <a href="{{ route('admin.themes.settings') }}"><i
                                            class="fa-solid fa-circle"></i><span
                                            class="menu-item">تنظیمات قالب</span></a>
                                </li>
                            @endcan
                        @endcan

                        @can('themes.settings')
                            @if (config('front.home-widgets'))
                                <li class="{{ active_class('admin.widgets.index').' '.active_class('admin.widgets.create') }}">
                                    <a href="{{ route('admin.widgets.index') }}"><i class="fa-solid fa-circle"></i><span
                                            class="menu-item">مدیریت صفحه اصلی</span></a>
                                </li>
                            @endif
                            @if (config('front.posts-widgets'))
                                <li class="{{ active_class('admin.posts-widgets.index').' '.active_class('admin.posts-widgets.create') }}">
                                    <a href="{{ route('admin.posts-widgets.index') }}"><i class="fa-solid fa-circle"></i><span
                                            class="menu-item">مدیریت صفحه اصلی مقالات</span></a>
                                </li>
                            @endif
                        @endcan

                    </ul>
                </li>
            @endcan
            @can('sliders')
                <li class="nav-item has-sub {{ open_class(['admin.sliders.*']) }}"><a><i
                            class="fa-solid fa-panorama"></i><span class="menu-title"> اسلایدرها</span></a>
                    <ul class="menu-content">
                        @can('sliders.index')
                            <li class="{{ active_class('admin.sliders.index') }}">
                                <a href="{{ route('admin.sliders.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست اسلایدرها</span></a>
                            </li>
                        @endcan

                        @can('sliders.create')
                            <li class="{{ active_class('admin.sliders.create') }}">
                                <a href="{{ route('admin.sliders.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد اسلایدر</span></a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endcan

            @can('banners')
                <li class="nav-item has-sub {{ open_class(['admin.banners.*']) }}"><a><i
                            class="fa-solid fa-road-barrier"></i><span class="menu-title"> بنرها</span></a>
                    <ul class="menu-content">
                        @can('banners.index')
                            <li class="{{ active_class('admin.banners.index') }}">
                                <a href="{{ route('admin.banners.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست بنرها</span></a>
                            </li>
                        @endcan

                        @can('banners.create')
                            <li class="{{ active_class('admin.banners.create') }}">
                                <a href="{{ route('admin.banners.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد بنر</span></a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @can('links')
                <li class="nav-item has-sub {{ open_class(['admin.links.*']) }}">
                    <a><i
                            class="fa-solid fa-link"></i><span class="menu-title"> لینک های فوتر</span></a>
                    <ul class="menu-content">
                        @can('links.index')
                            <li class="{{ active_class('admin.links.index') }}">
                                <a href="{{ route('admin.links.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست لینک ها</span></a>
                            </li>
                        @endcan

                        @can('links.create')
                            <li class="{{ active_class('admin.links.create') }}">
                                <a href="{{ route('admin.links.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد لینک </span></a>
                            </li>
                        @endcan

                        @can('links.groups')
                            <li class="{{ active_class('admin.links.groups.index') }}">
                                <a href="{{ route('admin.links.groups.index') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">لیست گروه ها </span></a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan


            @can('menus')
                <li class="{{ active_class('admin.menus.index') }} nav-item">
                    <a href="{{ route('admin.menus.index') }}">
                        <i class="fa-solid fa-bars"></i>
                        <span class="menu-title"> منوها</span>
                    </a>
                </li>
            @endcan



            <li class="title">عملیات محصولات</li>

            @can('warehouses')
                <li class="nav-item has-sub {{ open_class(['admin.warehouses.*']) }}">
                    <a><i
                            class=" fas fa-warehouse"></i><span class="menu-title"> انبارداری</span></a>
                    <ul class="menu-content">
                        @can('warehouses.index')
                            <li class="{{ active_class('admin.warehouses.index') }} nav-item">
                                <a href="{{ route('admin.warehouses.index') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-title"> لیست انبارها</span>
                                </a>
                            </li>
                        @endcan

                        @can('warehouses.create')
                            <li class="{{ active_class('admin.warehouses.create') }} nav-item">
                                <a href="{{ route('admin.warehouses.create') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-title"> ایحاد انبار جدید </span>
                                </a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endcan

            @can('products')
                <li class="nav-item has-sub {{ open_class(['admin.products.*', 'admin.brands.*', 'admin.sizetypes.*']) }}">
                    <a><i class="fa-solid fa-cart-shopping"></i><span class="menu-title"> محصولات</span></a>
                    <ul class="menu-content">
                        @can('products.index')
                            <li class="{{ active_class('admin.products.index') }}">
                                <a href="{{ route('admin.products.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست محصولات</span></a>
                            </li>
                        @endcan

                        @can('products.create')
                            <li class="{{ active_class('admin.products.create') }}">
                                <a href="{{ route('admin.products.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد محصول</span></a>
                            </li>
                        @endcan


                        @can('products.category')
                            <li class="{{ active_class('admin.products.categories.index') }}">
                                <a href="{{ route('admin.products.categories.index') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">دسته بندی ها</span></a>
                            </li>
                        @endcan

                        @can('products.sizetypes')
                            <li class="{{ active_class('admin.sizetypes.index') }}">
                                <a href="{{ route('admin.sizetypes.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">راهنمای سایز</span></a>
                            </li>
                        @endcan

                        @can('products.spectypes')
                            <li class="{{ active_class('admin.spectypes.index') }}">
                                <a href="{{ route('admin.spectypes.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">نوع مشخصات</span></a>
                            </li>
                        @endcan

                        @can('products.stock-notify')
                            <li class="{{ active_class('admin.stock-notifies.index') }}">
                                <a href="{{ route('admin.stock-notifies.index') }}"><i
                                        class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست اطلاع از موجودی</span></a>
                            </li>
                        @endcan

                        @can('products.price-alert')
                            <li class="{{ active_class('admin.price-alerts.index') }}">
                                <a href="{{ route('admin.price-alerts.index') }}"><i
                                        class="fa-solid fa-circle"></i><span
                                        class="menu-item">هشدار کاهش قیمت</span></a>
                            </li>
                        @endcan

                        @can('products.prices')
                            <li class="{{ active_class('admin.product.prices.index') }}">
                                <a href="{{ route('admin.product.prices.index') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">قیمت ها</span></a>
                            </li>
                        @endcan

                        @can('products.pricesGroup')
                            <li class="{{ active_class('admin.product.pricesGroup.index') }}">
                                <a href="{{ route('admin.product.pricesGroup.index') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item"> تغییر گروهی قیمت </span></a>
                            </li>
                        @endcan

                        @can('products.brands')
                            <li class="{{ open_class(['admin.brands.*']) }}">
                                <a><i class="fa-solid fa-circle"></i><span class="menu-item"> برندها</span></a>
                                <ul class="menu-content">
                                    <li class="{{ active_class('admin.brands.index') }}">
                                        <a class="{{ active_class('admin.brands.index') }}"
                                           href="{{ route('admin.brands.index') }}"><i
                                                class="fa-solid fa-circle"></i><span
                                                class="menu-item">لیست برندها</span></a>
                                    </li>
                                    <li class="{{ active_class('admin.brands.create') }}">
                                        <a class="{{ active_class('admin.brands.create') }}"
                                           href="{{ route('admin.brands.create') }}"><i
                                                class="fa-solid fa-circle"></i><span class="menu-item">ایجاد برند</span></a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        @can('attributes')
                            <li class="{{ open_class(['admin.attributeGroups.*']) }}">
                                <a><i class="fa-solid fa-circle"></i><span class="menu-item"> ویژگی ها</span></a>
                                <ul class="menu-content">
                                    @can('attributes.groups.index')
                                        <li class="{{ active_class('admin.attributeGroups.index') }}">
                                            <a class="{{ active_class('admin.attributeGroups.index') }}"
                                               href="{{ route('admin.attributeGroups.index') }}"><i
                                                    class="fa-solid fa-circle"></i><span class="menu-item">لیست گروه ویژگی ها</span></a>
                                        </li>
                                    @endcan

                                    @can('attributes.groups.create')
                                        <li class="{{ active_class('admin.attributeGroups.create') }}">
                                            <a class="{{ active_class('admin.attributeGroups.create') }}"
                                               href="{{ route('admin.attributeGroups.create') }}"><i
                                                    class="fa-solid fa-circle"></i><span class="menu-item">ایجاد گروه ویژگی</span></a>
                                        </li>
                                    @endcan

                                    @can('attributes.create')
                                        <li class="{{ active_class('admin.attributes.create') }}">
                                            <a class="{{ active_class('admin.attributes.create') }}"
                                               href="{{ route('admin.attributes.create') }}"><i
                                                    class="fa-solid fa-circle"></i><span
                                                    class="menu-item">ایجاد ویژگی</span></a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcan

                        @can('filters')
                            <li class="{{ open_class(['admin.filters.*']) }}">
                                <a><i class="fa-solid fa-circle"></i><span class="menu-item"> فیلترها</span></a>
                                <ul class="menu-content">
                                    @can('filters.index')
                                        <li class="{{ active_class('admin.filters.index') }}">
                                            <a class="{{ active_class('admin.filters.index') }}"
                                               href="{{ route('admin.filters.index') }}"><i
                                                    class="fa-solid fa-circle"></i><span
                                                    class="menu-item">لیست فیلتر ها</span></a>
                                        </li>
                                    @endcan

                                    @can('filters.create')
                                        <li class="{{ active_class('admin.filters.create') }}">
                                            <a class="{{ active_class('admin.filters.create') }}"
                                               href="{{ route('admin.filters.create') }}"><i
                                                    class="fa-solid fa-circle"></i><span
                                                    class="menu-item">ایجاد فیلتر</span></a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endcan

            @can('products.comments')
            <li class="nav-item has-sub {{ open_class(['admin.comments.*']) }}">
                <a><i
                        class="fa-solid fa-comment"></i><span class="menu-title"> دیدگاه ها و پرسش و پاسخ</span></a>
                <ul class="menu-content">
                    @can('products.comments')
                        <li class="{{ active_class('admin.comments.products') }} nav-item">
                            <a href="{{ route('admin.comments.products') }}">
                                <i class="fa-solid fa-circle"></i>
                                <span class="menu-title"> پرسش و پاسخ محصولات</span>
                            </a>
                        </li>
                    @endcan

                    @can('products.reviews')
                        <li class="{{ active_class('admin.reviews.index') }} nav-item">
                            <a href="{{ route('admin.reviews.index') }}">
                                <i class="fa-solid fa-circle"></i>
                                <span class="menu-title"> دیدگاه ها </span>
                            </a>
                        </li>
                    @endcan

                </ul>
            </li>
            @endcan

            @can('discounts')
                <li class="nav-item has-sub {{ open_class(['admin.discounts.*']) }}"><a><i
                            class="fa-solid fa-tag"></i><span class="menu-title"> تخفیف ها</span></a>
                    <ul class="menu-content">
                        <li class="{{ active_class('admin.discounts.index') }}">
                            <a class="{{ active_class('admin.discounts.index') }}"
                               href="{{ route('admin.discounts.index') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item">لیست تخفیف ها</span></a>
                        </li>

                        <li class="{{ active_class('admin.discounts.create') }}">
                            <a class="{{ active_class('admin.discounts.create') }}"
                               href="{{ route('admin.discounts.create') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item">ایجاد تخفیف</span></a>
                        </li>
                    </ul>
                </li>
            @endcan
            @can('carriers')
                <li class="nav-item has-sub {{ open_class(['admin.provinces.*', 'admin.carriers.*', 'admin.tariffs.*']) }}">
                    <a><i class="fa-solid fa-truck-fast"></i><span class="menu-title"> حمل و نقل</span></a>
                    <ul class="menu-content">
                        <li class="{{ active_class('admin.carriers.index') }}">
                            <a class="{{ active_class('admin.carriers.index') }}"
                               href="{{ route('admin.carriers.index') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item">روش های ارسال</span></a>
                        </li>

                        @can('carriers.provinces.index')
                            <li class="{{ active_class('admin.provinces.index') }}">
                                <a class="{{ active_class('admin.provinces.index') }}"
                                   href="{{ route('admin.provinces.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست استان ها</span></a>
                            </li>
                        @endcan

                        @can('carriers.provinces.create')
                            <li class="{{ active_class('admin.provinces.create') }}">
                                <a class="{{ active_class('admin.provinces.create') }}"
                                   href="{{ route('admin.provinces.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد استان</span></a>
                            </li>
                        @endcan

                        @can('carriers.cities.create')
                            <li class="{{ active_class('admin.cities.create') }}">
                                <a class="{{ active_class('admin.cities.create') }}"
                                   href="{{ route('admin.cities.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد شهر</span></a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan


            <li class="title">امور فروشندگان</li>
            @if(option('multi_vendor_system_status','false')=="true")

                @can('sellers')
                    <li class="nav-item has-sub {{ open_class(['admin.sellers.*']) }}"><a><i
                                class="fa-sharp fa-solid fa-shop"></i><span class="menu-title"> فروشندگان</span></a>
                        <ul class="menu-content">
                            @can('sellers.index')
                                <li class="{{ active_class('admin.sellers.index') }}">
                                    <a href="{{ route('admin.sellers.index') }}"><i class="fa-solid fa-circle"></i><span
                                            class="menu-item">لیست فروشندگان</span></a>
                                </li>
                            @endcan
                            @can('sellers.products')
                                <li class="{{ active_class('admin.sellers.products') }}">
                                    <a href="{{ route('admin.sellers.products') }}"><i
                                            class="fa-solid fa-circle"></i><span
                                            class="menu-item">لیست محصولات</span></a>
                                </li>
                            @endcan

                            @can('sellers.orders')
                                <li class="{{ active_class('admin.sellers.orders') }}">
                                    <a href="{{ route('admin.sellers.orders') }}"><i
                                            class="fa-solid fa-circle"></i><span
                                            class="menu-item">همه سفارشات</span></a>
                                </li>
                            @endcan

                        </ul>
                    </li>

                    <li class="{{ open_class(['admin.settings.*']) }}">
                        <a><i class="fa-solid fa-gear"></i><span class="menu-item"> تنظیمات فروشندگان</span></a>
                        <ul class="menu-content">
                            <li class="{{ active_class('admin.settings.seller-hero') }}">
                                <a class="{{ active_class('admin.settings.seller-hero') }}"
                                   href="{{ route('admin.settings.seller-hero') }}"><i
                                        class="fa-solid fa-circle"></i><span
                                        class="menu-item">چرا {{ option('info_site_title', 'webTpro') }}</span></a>
                            </li>
                            <li class="{{ active_class('admin.settings.seller-commission') }}">
                                <a class="{{ active_class('admin.settings.seller-commission') }}"
                                   href="{{ route('admin.settings.seller-commission') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">نمایش میزان کمیسیون</span></a>
                            </li>
                            <li class="{{ active_class('admin.settings.seller-question') }}">
                                <a class="{{ active_class('admin.settings.seller-question') }}"
                                   href="{{ route('admin.settings.seller-question') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">سوالات پر تکرار</span></a>
                            </li>
                            <li class="{{ active_class('admin.settings.seller-econtract') }}">
                                <a class="{{ active_class('admin.settings.seller-econtract') }}"
                                   href="{{ route('admin.settings.seller-econtract') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">متن قرارداد فروشنده</span></a>
                            </li>
                        </ul>
                    </li>
                @endcan


            @endif


            <li class="title">امور مالی و سفارشات</li>

            @can('orders')
                <li class="nav-item has-sub {{ open_class(['admin.orders.*','admin.order.*']) }}"><a><i
                            class="fa-solid fa-gifts"></i><span class="menu-title"> سفارشات</span></a>
                    <ul class="menu-content">
                        @can('orders.index')
                            <li class="{{ active_class('admin.orders.index')}} {{active_class('admin.orders.show-item') }}">
                                <a href="{{ route('admin.orders.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">همه سفارشات</span></a>
                            </li>
                        @endcan

                        @can('orders.index')
                            <li class="">
                                <a href="{{ route('admin.orders.index') }}?status=paid&shipping_status=pending"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">سفارشات جدید</span></a>
                            </li>
                        @endcan

                        @can('orders.index')
                            <li class="">
                                <a href="{{ route('admin.orders.index') }}?status=paid"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">سفارشات پرداخت شده</span></a>
                            </li>
                        @endcan

                        @can('orders.index')
                            <li class="{{ active_class('admin.orders.notCompleted') }}">
                                <a href="{{ route('admin.orders.notCompleted') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item"> سفارشات کنسل شده</span></a>
                            </li>
                        @endcan

                        @can('orders.create')
                            <li class="{{ active_class('admin.orders.create') }}">
                                <a href="{{ route('admin.orders.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item"> افزودن سفارش</span></a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endcan

            @can('returns')
                <li class="nav-item has-sub {{ open_class(['admin.returns.*','admin.return.*']) }}">
                    <a>
                        <i class="fa-solid fa-rotate-left"></i>
                        <span class="menu-title">مرجوعی</span>
                    </a>
                    <ul class="menu-content">
                        @can('returns.index')
                            <li class="{{ active_class('admin.returns.index') }}">
                                <a href="{{ route('admin.returns.index') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-item">لیست مرجوعی‌ها</span>
                                </a>
                            </li>
                        @endcan

                        @can('returns.reasons')
                            <li class="{{ active_class('admin.returns.reasons') }}">
                                <a href="{{ route('admin.returns.reasons') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-item">دلایل مرجوعی</span>
                                </a>
                            </li>
                        @endcan

                        {{-- فیلترهای سریع برای وضعیت‌های مختلف --}}
                        @can('returns.index')
                            <li class="">
                                <a href="{{ route('admin.returns.index') }}?status=pending">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-item">در انتظار بررسی</span>
                                </a>
                            </li>
                        @endcan

                        @can('returns.index')
                            <li class="">
                                <a href="{{ route('admin.returns.index') }}?status=approved">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-item">تأیید شده</span>
                                </a>
                            </li>
                        @endcan

                        @can('returns.index')
                            <li class="">
                                <a href="{{ route('admin.returns.index') }}?status=completed">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-item">تکمیل شده</span>
                                </a>
                            </li>
                        @endcan

                        @can('returns.index')
                            <li class="">
                                <a href="{{ route('admin.returns.index') }}?status=rejected">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-item">رد شده</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @can('statistics')
                <li class="nav-item has-sub"><a><i class="fa-solid fa-chart-pie"></i><span
                            class="menu-title">گزارشات</span></a>
                    <ul class="menu-content">

                        @can('statistics.orders')
                            <li class="{{ active_class('admin.statistics.orders') }}">
                                <a href="{{ route('admin.statistics.orders') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">سفارشات</span></a>
                            </li>
                        @endcan

                        @can('statistics.users')
                            <li class="{{ active_class('admin.statistics.users') }}">
                                <a href="{{ route('admin.statistics.users') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">کاربران</span></a>
                            </li>
                        @endcan

                        @can('statistics.views')
                            <li class="{{ active_class('admin.statistics.views') }}">
                                <a href="{{ route('admin.statistics.views') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">آمار بازدیدها</span></a>
                            </li>
                        @endcan
                        @can('statistics.product')
                            <li class="{{ active_class('admin.statistics.orders.products') }}">
                                <a href="{{ route('admin.statistics.orders.products') }}"><i
                                        class="fa-solid fa-circle"></i><span
                                        class="menu-item">آمارگیری فروش هر محصول</span></a>
                            </li>
                        @endcan

                        @can('statistics.viewsList')
                            <li class="{{ active_class('admin.statistics.viewsList') }}">
                                <a href="{{ route('admin.statistics.viewsList') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">لیست بازدیدها</span></a>
                            </li>
                        @endcan

                        @can('statistics.viewers')
                            <li class="{{ active_class('admin.statistics.viewers') }}">
                                <a href="{{ route('admin.statistics.viewers') }}"><i
                                        class="fa-solid fa-circle"></i><span
                                        class="menu-item"> بازدید کنندگان امروز</span></a>
                            </li>
                        @endcan

                        @can('statistics.sms')
                            <li class="{{ active_class('admin.statistics.smsLog') }}">
                                <a href="{{ route('admin.statistics.smsLog') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item"> لاگ پیامک های ارسالی</span></a>
                            </li>
                        @endcan


                    </ul>
                </li>
            @endcan

            @can(['payments'])
                <li class="nav-item has-sub {{ open_class(['admin.transactions.*', 'admin.currencies.*']) }}"><a><i
                            class="fa-solid fa-money-bill-wave"></i><span class="menu-title"> پرداخت</span></a>
                    <ul class="menu-content">
                        @can('payments.transactions.index')
                            <li class="{{ active_class('admin.transactions.index') }} nav-item">
                                <a href="{{ route('admin.transactions.index') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-title"> لیست تراکنش ها</span>
                                </a>
                            </li>
                        @endcan

                        @can('payments.wallet-histories.index')
                            <li class="{{ active_class('admin.wallet-histories.index') }} nav-item">
                                <a href="{{ route('admin.wallet-histories.index') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-title"> تاریخچه کیف پول</span>
                                </a>
                            </li>
                        @endcan

                        @can('payments.transactions.seller_deposit')
                            <li class="{{ active_class('admin.transactions.seller_deposits') }} nav-item">
                                <a href="{{ route('admin.transactions.seller_deposits') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-title"> لیست کمیسیون فروشندگان</span>
                                </a>
                            </li>
                        @endcan


                        @can('payments.currencies')
                            <li class="{{ active_class('admin.currencies.index') }} nav-item">
                                <a href="{{ route('admin.currencies.index') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-title"> لیست ارز ها</span>
                                </a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endcan

            @can('request_deposit')
                <li class="{{ active_class('admin.request-deposit.index') }} nav-item"><a
                        href="{{ route('admin.request-deposit.index') }}">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                        <span class="menu-title">صف پرداخت سهم ها</span>
                    </a>
                </li>
            @endcan


            <li class="title">امور پشتیبانی</li>
            @can('tickets')
                <li class="nav-item has-sub {{ open_class(['admin.tickets.*']) }}"><a><i
                            class="fa-solid fa-envelope-open-text"></i><span class="menu-title"> تیکت ها</span></a>
                    <ul class="menu-content">
                        @can('tickets.index')
                            <li class="{{ active_class('admin.tickets.index') }}">
                                <a href="{{ route('admin.tickets.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست تیکت ها</span></a>
                            </li>
                        @endcan

                        @can('tickets.create')
                            <li class="{{ active_class('admin.tickets.create') }}">
                                <a href="{{ route('admin.tickets.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد تیکت</span></a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan
            @can('contacts')
                <li class="{{ active_class('admin.contacts.index') }} nav-item">
                    <a href="{{ route('admin.contacts.index') }}">
                        <i class="fa-solid fa-message"></i>
                        <span class="menu-title">لیست تماس با ما</span>
                    </a>
                </li>
            @endcan
            @can('forms')
                <li class="nav-item has-sub {{ open_class(['admin.forms.*']) }}">
                    <a>
                        <i class=" fab fa-wpforms"></i>
                        <span class="menu-title">فرم‌ها</span>
                    </a>
                    <ul class="menu-content">
                        @can('forms.index')
                            <li class="{{ active_class('admin.forms.index') }}">
                                <a href="{{ route('admin.forms.index') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-item">لیست فرم‌ها</span>
                                </a>
                            </li>
                        @endcan

                        @can('forms.create')
                            <li class="{{ active_class('admin.forms.create') }}">
                                <a href="{{ route('admin.forms.create') }}">
                                    <i class="fa-solid fa-circle"></i>
                                    <span class="menu-item">ایجاد فرم جدید</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @can('newsletters')
                <li class="{{ active_class('admin.newsletters.index') }} nav-item">
                    <a href="{{ route('admin.newsletters.index') }}">
                        <i class=" fas fa-bullhorn"></i>
                        <span class="menu-title"> خبرنامه</span>
                    </a>
                </li>
            @endcan
            @can('faqs')
                <li class="{{ active_class('admin.faqs.index') }} nav-item">
                    <a href="{{ route('admin.faqs.index') }}">
                        <i class=" far fa-circle-question"></i>
                        <span class="menu-title"> سوالات متداول</span>
                    </a>
                </li>
            @endcan


            <li class="title">پکیج ها </li>
            @foreach (\App\Services\ModuleMenuRegistry::all() as $menuView)
                @if (view()->exists($menuView))
                    {{-- apPanel: پارشال منوی هر پکیج با همین کانتکست، مارک‌آپ مخصوص این سایدبار را رندر می‌کند --}}
                    @include($menuView, ['apPanel' => 'old'])
                @endif
            @endforeach


            <li class="title">امور کاربران و مدیران </li>
            @can('users')
                <li class="nav-item has-sub {{ open_class(['admin.users.*']) }}"><a><i
                            class="fa-solid fa-user-group"></i><span class="menu-title"> کاربران</span></a>
                    <ul class="menu-content">
                        @can('users.index')
                            <li class="{{ active_class('admin.users.index') }}">
                                <a href="{{ route('admin.users.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست کاربران</span></a>
                            </li>
                        @endcan

                        @can('users.create')
                            <li class="{{ active_class('admin.users.create') }}">
                                <a href="{{ route('admin.users.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد کاربر</span></a>
                            </li>
                        @endcan


                    </ul>
                </li>
            @endcan

            @can('admins')
                <li class="nav-item has-sub {{ open_class(['admin.admins.*']) }}"><a><i
                            class="fa-solid fa-user-tie"></i><span class="menu-title"> مدیران</span></a>
                    <ul class="menu-content">
                        @can('admins.index')
                            <li class="{{ active_class('admin.admins.index') }}">
                                <a href="{{ route('admin.admins.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">لیست مدیران</span></a>
                            </li>
                        @endcan

                        @can('admins.create')
                            <li class="{{ active_class('admin.admins.create') }}">
                                <a href="{{ route('admin.admins.create') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">ایجاد مدیر</span></a>
                            </li>
                        @endcan

                        @can('roles')
                            <li class="{{ open_class(['admin.roles.*']) }}">
                                <a><i class="fa-solid fa-user-lock" style='font-size: 15px'></i><span class="menu-item"> مقام ها</span></a>
                                <ul class="menu-content">
                                    @can('roles.index')
                                        <li class="{{ active_class('admin.roles.index') }}">
                                            <a href="{{ route('admin.roles.index') }}"><i
                                                    class="fa-solid fa-circle"></i><span
                                                    class="menu-item">لیست مقام ها</span></a>
                                        </li>
                                    @endcan

                                    @can('roles.create')
                                        <li class="{{ active_class('admin.roles.create') }}">
                                            <a href="{{ route('admin.roles.create') }}"><i
                                                    class="fa-solid fa-circle"></i><span
                                                    class="menu-item">ایجاد مقام</span></a>
                                        </li>
                                    @endcan

                                </ul>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan
            @if(Auth::user('adminPanel')->can('notifications.panel') or Auth::user('adminPanel')->can('notifications'))
                <li class="nav-item has-sub {{ open_class(['admin.notifications.*']) }}">
                    <a> <i class="fa-solid fa-bell"></i><span class="menu-title">  اعلان ها</span>
                        @if($notifications->count())
                            <span class="badge badge badge-primary "> {{ $notifications->count() }}</span>
                        @endif
                    </a>
                    <ul class="menu-content">
                        @can('notifications.panel')
                            <li class="{{ active_class('admin.notifications') }}">
                                <a href="{{ route('admin.notifications') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">اعلان های پنل</span></a>
                            </li>
                        @endcan
                        @can('notifications')
                            <li class="{{ active_class('admin.notifications.index') }}">
                                <a href="{{ route('admin.notifications.index') }}"><i
                                        class="fa-solid fa-circle"></i><span
                                        class="menu-item">مدیریت اعلان ها</span></a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif

            @can('settings.sms')
                <li class="{{ active_class('admin.messages.index') }} nav-item">
                    <a href="{{ route('admin.messages.index') }}">
                        <i class=" feather icon-mail"></i>
                        <span class="menu-title">ارسال پیام</span>
                    </a>
                </li>

            @endcan


            <li class="title">امور مدیریتی</li>
            @can('imports')
                <li class="nav-item has-sub {{ open_class(['admin.import.*']) }}"><a><i
                            class="fa-solid fa-file-invoice"></i><span class="menu-title"> درون ریزی</span></a>
                    <ul class="menu-content">
                        @can('imports.posts')
                            <li class="{{ active_class('admin.import.posts') }}">
                                <a href="{{ route('admin.import.posts') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">درون ریزی مقالات</span></a>
                            </li>
                        @endcan
                        @can('imports.products')
                            <li class="{{ active_class('admin.import.products') }}">
                                <a href="{{ route('admin.import.products') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">درون ریزی محصولات</span></a>
                            </li>
                        @endcan
                        @can('imports.users')
                            <li class="{{ active_class('admin.import.users') }}">
                                <a href="{{ route('admin.import.users') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">درون ریزی کاربران</span></a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @can('settings')
                <li class="nav-item has-sub">
                    <a>
                        <i class="fa-solid fa-gear"></i>
                        <span class="menu-title">تنظیمات</span>
                    </a>
                    <ul class="menu-content">
                        @can('settings.information')
                            <li class="{{ active_class('admin.settings.information') }}">
                                <a href="{{ route('admin.settings.information') }}"><i
                                        class="fa-solid fa-circle"></i><span class="menu-item">اطلاعات کلی</span></a>
                            </li>
                        @endcan



                        @can('settings.socials')
                            @if (config('front.socials'))
                                <li class="{{ active_class('admin.settings.socials') }}">
                                    <a href="{{ route('admin.settings.socials') }}"><i
                                            class="fa-solid fa-circle"></i><span
                                            class="menu-item">شبکه های اجتماعی</span></a>
                                </li>
                            @endif
                        @endcan

                        @can('settings.gateway')
                            <li class="{{ active_class('admin.settings.gateways') }}">
                                <a href="{{ route('admin.settings.gateways') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">درگاه های پرداخت</span></a>
                            </li>
                        @endcan

                        @can('settings.others')
                            <li class="{{ active_class('admin.settings.others') }}">
                                <a href="{{ route('admin.settings.others') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">تنظیمات دیگر</span></a>
                            </li>
                        @endcan

                        @can('settings.sms')
                            <li class="{{ active_class('admin.settings.sms') }}">
                                <a href="{{ route('admin.settings.sms') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">تنظیمات پیامک</span></a>
                            </li>
                        @endcan
                        @can('settings.floating-widget')
                            <li class="{{ active_class('admin.settings.floating-widget.*') }}">
                                <a href="{{ route('admin.settings.floating-widget.index') }}"><i class="fa-solid fa-circle"></i><span
                                        class="menu-item">تنظیمات ویجت شناور</span></a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endcan

            @if(Auth::user('adminPanel')->can('file-manager') or Auth::user('adminPanel')->can('backups.index') or Auth::user('adminPanel')->can('apikeys.index'))
                <li class="nav-item has-sub {{ open_class(['admin.file-manager', 'admin.backups.*', 'admin.apikeys.*']) }}">
                    <a>
                        <i class="fas fa-ellipsis-h"></i>
                        <span class="menu-title">بیشتر</span>
                    </a>
                    <ul class="menu-content">
                        @can('filds.index')
                            <li class="{{ active_class('admin.filds.*') }} nav-item">
                                <a href="{{ route('admin.filds.index') }}">
                                    <i class="fa-solid fa-arrow-right-to-city" style='font-size: 15px'></i>
                                    <span class="menu-title"> فیلدهای اختصاصی</span>
                                </a>
                            </li>
                        @endcan
                        @can('redirects.index')
                            <li class="{{ active_class('admin.redirects.*') }} nav-item">
                                <a href="{{ route('admin.redirects.index') }}">
                                    <i class="fa-solid fa-retweet" style='font-size: 15px'></i>
                                    <span class="menu-title">ریدایرکت</span>
                                </a>
                            </li>
                        @endcan
                        @can('file-manager')
                            <li class="{{ active_class('admin.file-manager') }} nav-item">
                                <a href="{{ route('admin.file-manager') }}">
                                    <i class="fas fa-folder-open" style='font-size: 15px'></i>
                                    <span class="menu-title"> فایل ها</span>
                                </a>
                            </li>
                        @endcan

                        @can('backups.index')
                            <li class="{{ active_class('admin.backups.index') }} nav-item">
                                <a href="{{ route('admin.backups.index') }}">
                                    <i class="fas fa-cloud-upload-alt" style='font-size: 15px'></i>
                                    <i class="feather icon-upload-"></i>
                                    <span class="menu-title">لیست بکاپ ها</span>
                                </a>
                            </li>
                        @endcan

                        @can('apikeys.index')
                            <li class="{{ active_class('admin.apikeys.index') }} nav-item">
                                <a href="{{ route('admin.apikeys.index') }}">
                                    <i class="fa fa-key" style='font-size: 15px'></i>
                                    <span class="menu-title">کلیدهای وب سرویس</span>
                                </a>
                            </li>
                        @endcan




                    </ul>
                </li>

            @endif

            @if(auth('adminPanel')->user()->isCreator())
                <li class="nav-item has-sub">
                    <a>
                        <i style='color: #EA5455' class="fas fa-radiation-alt"></i>
                        <span style='color: #EA5455' class="menu-title">برنامه نویس</span>

                    </a>
                    <ul class="menu-content">

                        <li class="{{ active_class('admin.permissions.index') }}">
                            <a href="{{ route('admin.permissions.index') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item">دسترسی ها</span></a>
                        </li>

                        <li class="{{ active_class('admin.developer.settings') }}">
                            <a href="{{ route('admin.developer.settings') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item">تنظیمات توسعه دهنده</span></a>
                        </li>

                        <li class="{{ active_class('admin.logs.index') }}">
                            <a target="_blank" href="{{ route('admin.logs.index') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item">لاگ ها</span></a>
                        </li>

                        <li class="{{ active_class('admin.robots.index') }}">
                            <a href="{{ route('admin.robots.index') }}">
                                <i class="fas fa-robot"></i>
                                <span class="menu-item">robots.txt</span>
                            </a>
                        </li>

                        <li class="{{ active_class('admin.developer.showUpdater') }}">
                            <a href="{{ route('admin.developer.showUpdater') }}"><i class="fa-solid fa-circle"></i><span
                                    class="menu-item">بروزرسانی</span></a>
                        </li>

                    </ul>
                </li>
            @endif

        </ul>
    </div>
</div>

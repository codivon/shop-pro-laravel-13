<?php

/*
|--------------------------------------------------------------------------
| نقشه منوی پنل مدیریت جدید
|--------------------------------------------------------------------------
| ساختار: گروه‌ها -> آیتم‌ها (تا ۳ سطح)
| هر آیتم:
|   label      عنوان فارسی
|   route      نام روت (np.* برای صفحات جدید، admin.* برای لینک موقت به پنل قدیمی)
|   permission کلید دسترسی طبق config('general.permissions') و جدول permissions
|   icon       نام آیکون lucide
|   params     پارامترهای query string
|   external   باز شدن در تب جدید
|
| نکته: تا پایان فازها، آیتم‌هایی که هنوز نسخه Livewire ندارند با route
| قدیمی (admin.*) به پنل قدیمی (admin-old) لینک می‌شوند تا هیچ بخشی حذف نشود.
*/

return [

    'groups' => [

        /*
        |------------------
        | نمای کلی
        |------------------
        */
        [
            'key' => 'overview',
            'title' => 'نمای کلی',
            'items' => [
                [
                    'label' => 'داشبورد',
                    'route' => 'np.dashboard',
                    'permission' => null,
                    'icon' => 'layout-dashboard',
                ],
                [
                    'label' => 'نشست‌های فعال',
                    'route' => 'np.sessions.index',
                    'permission' => 'sessions.index',
                    'icon' => 'monitor',
                ],
                [
                    'label' => 'دستگاه‌های بلاک شده',
                    'route' => 'np.sessions.blocked',
                    'permission' => 'sessions.blocked',
                    'icon' => 'shield-off',
                ],
                [
                    'label' => 'مانیتور سیستم',
                    'route' => 'np.pulse.index',
                    'permission' => 'pulse.monitor',
                    'icon' => 'activity',
                    'live' => true,
                ],
                [
                    'label' => 'فعالیت‌های اخیر',
                    'route' => 'np.activity-log.index',
                    'permission' => 'activity-log.index',
                    'icon' => 'history',
                ],
                [
                    'label' => 'پکیج‌ها',
                    'icon' => 'package',
                    'children' => [
                        [
                            'label' => 'لیست پکیج‌ها',
                            'route' => 'np.packages.index',
                            'permission' => 'packages.index',
                            'icon' => 'package',
                        ],
                        [
                            'label' => 'پکیج‌های نصب‌شده',
                            'route' => 'np.packages.installed',
                            'permission' => 'packages.installed',
                            'icon' => 'package-check',
                        ],
                    ],
                ],
            ],
        ],

        /*
        |------------------
        | مدیریت محتوا
        |------------------
        */
        [
            'key' => 'content',
            'title' => 'مدیریت محتوا',
            'items' => [
                [
                    'label' => 'وبلاگ',
                    'icon' => 'notebook-pen',
                    'children' => [
                        [
                            'label' => 'لیست مطالب',
                            'route' => 'np.posts.index',
                            'permission' => 'posts.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'نوشته جدید',
                            'route' => 'np.posts.create',
                            'permission' => 'posts.create',
                            'icon' => 'plus',
                        ],
                        [
                            'label' => 'دسته‌بندی‌ها',
                            'route' => 'np.categories.post',
                            'permission' => 'posts.category',
                            'icon' => 'folder-tree',
                        ],
                    ],
                ],
                [
                    'label' => 'دیدگاه‌ها',
                    'route' => 'np.comments.posts',
                    'permission' => 'posts.comments',
                    'icon' => 'message-square',
                ],
                [
                    'label' => 'تگ‌ها',
                    'route' => 'np.tags.index',
                    'permission' => 'tags',
                    'icon' => 'tags',
                ],
                [
                    'label' => 'جستجوها',
                    'route' => 'np.searches.index',
                    'permission' => 'searches',
                    'icon' => 'search',
                ],
                [
                    'label' => 'صفحات',
                    'icon' => 'file-text',
                    'children' => [
                        [
                            'label' => 'لیست صفحات',
                            'route' => 'np.pages.index',
                            'permission' => 'pages.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'صفحه جدید',
                            'route' => 'np.pages.create',
                            'permission' => 'pages.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
            ],
        ],

        /*
        |------------------
        | امور ظاهری
        |------------------
        */
        [
            'key' => 'appearance',
            'title' => 'امور ظاهری',
            'items' => [
                [
                    'label' => 'قالب',
                    'icon' => 'layout-template',
                    'children' => [
                        [
                            'label' => 'لیست قالب‌ها',
                            'route' => 'np.themes.index',
                            'permission' => 'themes.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'آپلود قالب جدید',
                            'route' => 'np.themes.create',
                            'permission' => 'themes.create',
                            'icon' => 'upload',
                        ],
                        [
                            'label' => 'تنظیمات قالب',
                            'route' => 'np.themes.settings',
                            'permission' => 'themes.settings',
                            'icon' => 'settings-2',
                        ],
                        [
                            'label' => 'ویجت‌ساز صفحه اصلی',
                            'route' => 'np.widgets.builder',
                            'permission' => 'themes.settings',
                            'icon' => 'layout-panel-top',
                        ],
                        [
                            'label' => 'ویجت‌های وبلاگ',
                            'route' => 'np.posts-widgets.index',
                            'permission' => 'themes.settings',
                            'icon' => 'layout-panel-left',
                        ],
                    ],
                ],
                [
                    'label' => 'اسلایدرها',
                    'icon' => 'image-play',
                    'children' => [
                        [
                            'label' => 'لیست اسلایدرها',
                            'route' => 'np.sliders.index',
                            'permission' => 'sliders.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'اسلایدر جدید',
                            'route' => 'np.sliders.create',
                            'permission' => 'sliders.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
                [
                    'label' => 'بنرها',
                    'icon' => 'panel-top',
                    'children' => [
                        [
                            'label' => 'لیست بنرها',
                            'route' => 'np.banners.index',
                            'permission' => 'banners.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'بنر جدید',
                            'route' => 'np.banners.create',
                            'permission' => 'banners.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
                [
                    'label' => 'لینک‌های فوتر',
                    'icon' => 'link-2',
                    'children' => [
                        [
                            'label' => 'لیست لینک‌ها',
                            'route' => 'np.links.index',
                            'permission' => 'links.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'لینک جدید',
                            'route' => 'np.links.create',
                            'permission' => 'links.create',
                            'icon' => 'plus',
                        ],
                        [
                            'label' => 'گروه‌های لینک',
                            'route' => 'np.links.groups',
                            'permission' => 'links.groups',
                            'icon' => 'folder',
                        ],
                    ],
                ],
                [
                    'label' => 'منوها',
                    'route' => 'np.menus.index',
                    'permission' => 'menus',
                    'icon' => 'menu',
                ],
            ],
        ],

        /*
        |------------------
        | عملیات محصولات
        |------------------
        */
        [
            'key' => 'products',
            'title' => 'عملیات محصولات',
            'items' => [
                [
                    'label' => 'انبارداری',
                    'icon' => 'warehouse',
                    'children' => [
                        [
                            'label' => 'لیست انبارها',
                            'route' => 'np.warehouses.index',
                            'permission' => 'warehouses.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'ایجاد انبار جدید',
                            'route' => 'np.warehouses.index',
                            'permission' => 'warehouses.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
                [
                    'label' => 'محصولات',
                    'icon' => 'shopping-bag',
                    'children' => [
                        [
                            'label' => 'لیست محصولات',
                            'route' => 'np.products.index',
                            'permission' => 'products.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'محصول جدید',
                            'route' => 'np.products.create',
                            'permission' => 'products.create',
                            'icon' => 'plus',
                        ],
                        [
                            'label' => 'دسته‌بندی محصولات',
                            'route' => 'np.categories.product',
                            'permission' => 'products.category',
                            'icon' => 'folder-tree',
                        ],
                        [
                            'label' => 'سایزبندی محصولات',
                            'route' => 'np.sizetypes.index',
                            'permission' => 'products.sizetypes',
                            'icon' => 'ruler',
                        ],
                        [
                            'label' => 'انواع مشخصات',
                            'route' => 'np.spectypes.index',
                            'permission' => 'products.spectypes',
                            'icon' => 'clipboard-list',
                        ],
                        [
                            'label' => 'اطلاع‌رسانی موجودی',
                            'route' => 'np.stock-notifies.index',
                            'permission' => 'products.stock-notify',
                            'icon' => 'bell-ring',
                        ],
                        [
                            'label' => 'قیمت‌ها',
                            'route' => 'np.products.prices',
                            'permission' => 'products.prices',
                            'icon' => 'tags',
                        ],
                        [
                            'label' => 'گروه‌های قیمت',
                            'route' => 'np.products.prices-group',
                            'permission' => 'products.pricesGroup',
                            'icon' => 'tags',
                        ],
                        [
                            'label' => 'برندها',
                            'icon' => 'award',
                            'children' => [
                                [
                                    'label' => 'لیست برندها',
                                    'route' => 'np.brands.index',
                                    'permission' => 'products.brands',
                                    'icon' => 'list',
                                ],
                                [
                                    'label' => 'برند جدید',
                                    'route' => 'np.brands.index',
                                    'permission' => 'products.brands',
                                    'icon' => 'plus',
                                ],
                            ],
                        ],
                        [
                            'label' => 'ویژگی‌ها',
                            'icon' => 'sliders-horizontal',
                            'children' => [
                                [
                                    'label' => 'گروه‌های ویژگی',
                                    'route' => 'np.attribute-groups.index',
                                    'permission' => 'attributes.groups.index',
                                    'icon' => 'list',
                                ],
                                [
                                    'label' => 'گروه ویژگی جدید',
                                    'route' => 'np.attribute-groups.index',
                                    'permission' => 'attributes.groups.create',
                                    'icon' => 'plus',
                                ],
                                [
                                    'label' => 'ویژگی جدید',
                                    'route' => 'np.attributes.create',
                                    'permission' => 'attributes.create',
                                    'icon' => 'plus',
                                ],
                            ],
                        ],
                        [
                            'label' => 'فیلترها',
                            'icon' => 'filter',
                            'children' => [
                                [
                                    'label' => 'لیست فیلترها',
                                    'route' => 'np.filters.index',
                                    'permission' => 'filters.index',
                                    'icon' => 'list',
                                ],
                                [
                                    'label' => 'فیلتر جدید',
                                    'route' => 'np.filters.create',
                                    'permission' => 'filters.create',
                                    'icon' => 'plus',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'دیدگاه‌ها و پرسش و پاسخ',
                    'icon' => 'messages-square',
                    'children' => [
                        [
                            'label' => 'پرسش و پاسخ محصولات',
                            'route' => 'np.comments.products',
                            'permission' => 'products.comments',
                            'icon' => 'help-circle',
                        ],
                        [
                            'label' => 'دیدگاه‌ها',
                            'route' => 'np.reviews.index',
                            'permission' => 'products.reviews',
                            'icon' => 'star',
                        ],
                    ],
                ],
                [
                    'label' => 'تخفیف‌ها',
                    'icon' => 'badge-percent',
                    'children' => [
                        [
                            'label' => 'لیست تخفیف‌ها',
                            'route' => 'np.discounts.index',
                            'permission' => 'discounts.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'تخفیف جدید',
                            'route' => 'np.discounts.create',
                            'permission' => 'discounts.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
                [
                    'label' => 'حمل و نقل',
                    'icon' => 'truck',
                    'permission' => 'carriers',
                    'children' => [
                        [
                            'label' => 'لیست حمل و نقل‌ها',
                            'route' => 'np.carriers.index',
                            'permission' => null,
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'لیست استان‌ها',
                            'route' => 'np.provinces.index',
                            'permission' => 'carriers.provinces.index',
                            'icon' => 'map',
                        ],
                        [
                            'label' => 'استان جدید',
                            'route' => 'np.provinces.index',
                            'permission' => 'carriers.provinces.create',
                            'icon' => 'plus',
                        ],
                        [
                            'label' => 'شهر جدید',
                            'route' => 'np.cities.index',
                            'permission' => 'carriers.cities.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
            ],
        ],

        /*
        |------------------
        | امور فروشندگان
        |------------------
        */
        [
            'key' => 'sellers',
            'title' => 'امور فروشندگان',
            'items' => [
                [
                    'label' => 'فروشندگان',
                    'icon' => 'store',
                    'children' => [
                        [
                            'label' => 'لیست فروشندگان',
                            'route' => 'np.sellers.index',
                            'permission' => 'sellers.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'محصولات فروشندگان',
                            'route' => 'np.sellers.products',
                            'permission' => 'sellers.products',
                            'icon' => 'package-2',
                        ],
                        [
                            'label' => 'سفارشات فروشندگان',
                            'route' => 'np.sellers.orders',
                            'permission' => 'sellers.orders',
                            'icon' => 'receipt',
                        ],
                    ],
                ],
                [
                    'label' => 'تنظیمات فروشندگان',
                    'icon' => 'settings',
                    'permission' => 'sellers',
                    'children' => [
                        [
                            'label' => 'چرا ما',
                            'route' => 'np.settings.seller-hero',
                            'permission' => null,
                            'icon' => 'sparkles',
                        ],
                        [
                            'label' => 'نمایش میزان کمیسیون',
                            'route' => 'np.settings.seller-commission',
                            'permission' => null,
                            'icon' => 'percent',
                        ],
                        [
                            'label' => 'سوالات پر تکرار',
                            'route' => 'np.settings.seller-question',
                            'permission' => null,
                            'icon' => 'message-circle-question',
                        ],
                        [
                            'label' => 'قرارداد الکترونیک',
                            'route' => 'np.settings.seller-econtract',
                            'permission' => null,
                            'icon' => 'file-signature',
                        ],
                    ],
                ],
            ],
        ],

        /*
        |------------------
        | امور مالی و سفارشات
        |------------------
        */
        [
            'key' => 'finance',
            'title' => 'امور مالی و سفارشات',
            'items' => [
                [
                    'label' => 'سفارشات',
                    'icon' => 'shopping-cart',
                    'children' => [
                        [
                            'label' => 'همه سفارشات',
                            'route' => 'np.orders.index',
                            'permission' => 'orders.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'سفارشات جدید',
                            'route' => 'np.orders.index',
                            'permission' => 'orders.index',
                            'icon' => 'circle-dot',
                            'params' => ['status' => 'paid', 'shipping_status' => 'pending'],
                        ],
                        [
                            'label' => 'سفارشات پرداخت شده',
                            'route' => 'np.orders.index',
                            'permission' => 'orders.index',
                            'icon' => 'circle-check',
                            'params' => ['status' => 'paid'],
                        ],
                        [
                            'label' => 'سفارشات کنسل شده',
                            'route' => 'np.orders.not-completed',
                            'permission' => 'orders.index',
                            'icon' => 'circle-x',
                        ],
                        [
                            'label' => 'ثبت سفارش جدید',
                            'route' => 'np.orders.create',
                            'permission' => 'orders.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
                [
                    'label' => 'مرجوعی',
                    'icon' => 'undo-2',
                    'children' => [
                        [
                            'label' => 'همه مرجوعی‌ها',
                            'route' => 'np.returns.index',
                            'permission' => 'returns.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'دلایل مرجوعی',
                            'route' => 'np.returns.reasons',
                            'permission' => 'returns.reasons',
                            'icon' => 'clipboard-list',
                        ],
                        [
                            'label' => 'در انتظار بررسی',
                            'route' => 'np.returns.index',
                            'permission' => 'returns.index',
                            'icon' => 'clock',
                            'params' => ['status' => 'pending'],
                        ],
                        [
                            'label' => 'تایید شده',
                            'route' => 'np.returns.index',
                            'permission' => 'returns.index',
                            'icon' => 'check',
                            'params' => ['status' => 'approved'],
                        ],
                        [
                            'label' => 'تکمیل شده',
                            'route' => 'np.returns.index',
                            'permission' => 'returns.index',
                            'icon' => 'check-check',
                            'params' => ['status' => 'completed'],
                        ],
                        [
                            'label' => 'رد شده',
                            'route' => 'np.returns.index',
                            'permission' => 'returns.index',
                            'icon' => 'x',
                            'params' => ['status' => 'rejected'],
                        ],
                    ],
                ],
                [
                    'label' => 'گزارشات',
                    'icon' => 'bar-chart-3',
                    'children' => [
                        [
                            'label' => 'آمار سفارشات',
                            'route' => 'np.statistics.orders',
                            'permission' => 'statistics.orders',
                            'icon' => 'chart-column',
                        ],
                        [
                            'label' => 'آمار کاربران',
                            'route' => 'np.statistics.users',
                            'permission' => 'statistics.users',
                            'icon' => 'chart-line',
                        ],
                        [
                            'label' => 'آمار بازدید',
                            'route' => 'np.statistics.views',
                            'permission' => 'statistics.views',
                            'icon' => 'eye',
                        ],
                        [
                            'label' => 'آمارگیری فروش هر محصول',
                            'route' => 'np.statistics.orders-products',
                            'permission' => 'statistics.product',
                            'icon' => 'chart-pie',
                        ],
                        [
                            'label' => 'لیست بازدیدها',
                            'route' => 'np.statistics.views-list',
                            'permission' => 'statistics.viewsList',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'بازدیدکنندگان امروز',
                            'route' => 'np.statistics.viewers',
                            'permission' => 'statistics.viewers',
                            'icon' => 'users',
                        ],
                        [
                            'label' => 'لاگ پیامک‌های ارسالی',
                            'route' => 'np.statistics.sms-log',
                            'permission' => 'statistics.sms',
                            'icon' => 'smartphone',
                        ],
                    ],
                ],
                [
                    'label' => 'پرداخت',
                    'icon' => 'wallet',
                    'children' => [
                        [
                            'label' => 'لیست تراکنش‌ها',
                            'route' => 'np.transactions.index',
                            'permission' => 'payments.transactions.index',
                            'icon' => 'credit-card',
                        ],
                        [
                            'label' => 'تاریخچه کیف پول',
                            'route' => 'np.wallet-histories.index',
                            'permission' => 'payments.wallet-histories.index',
                            'icon' => 'history',
                        ],
                        [
                            'label' => 'لیست کمیسیون فروشندگان',
                            'route' => 'np.transactions.seller-deposits',
                            'permission' => 'payments.transactions.seller_deposit',
                            'icon' => 'coins',
                        ],
                        [
                            'label' => 'لیست ارزها',
                            'route' => 'np.currencies.index',
                            'permission' => 'payments.currencies',
                            'icon' => 'banknote',
                        ],
                    ],
                ],
                [
                    'label' => 'صف پرداخت سهم‌ها',
                    'route' => 'np.request-deposit.index',
                    'permission' => 'request_deposit',
                    'icon' => 'hourglass',
                ],
            ],
        ],

        /*
        |------------------
        | امور پشتیبانی
        |------------------
        */
        [
            'key' => 'support',
            'title' => 'امور پشتیبانی',
            'items' => [
                [
                    'label' => 'تیکت‌ها',
                    'icon' => 'mail-warning',
                    'children' => [
                        [
                            'label' => 'لیست تیکت‌ها',
                            'route' => 'np.tickets.index',
                            'permission' => 'tickets.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'تیکت جدید',
                            'route' => 'np.tickets.create',
                            'permission' => 'tickets.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
                [
                    'label' => 'لیست تماس با ما',
                    'route' => 'np.contacts.index',
                    'permission' => 'contacts',
                    'icon' => 'phone',
                ],
                [
                    'label' => 'فرم‌ها',
                    'icon' => 'clipboard-pen',
                    'children' => [
                        [
                            'label' => 'لیست فرم‌ها',
                            'route' => 'np.forms.index',
                            'permission' => 'forms.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'فرم جدید',
                            'route' => 'np.forms.create',
                            'permission' => 'forms.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
                [
                    'label' => 'خبرنامه',
                    'route' => 'np.newsletters.index',
                    'permission' => 'newsletters',
                    'icon' => 'send',
                ],
                [
                    'label' => 'سوالات متداول',
                    'route' => 'np.faqs.index',
                    'permission' => 'faqs',
                    'icon' => 'circle-help',
                ],
            ],
        ],

        /*
        |------------------
        | امور کاربران و مدیران
        |------------------
        */
        [
            'key' => 'people',
            'title' => 'امور کاربران و مدیران',
            'items' => [
                [
                    'label' => 'کاربران',
                    'icon' => 'users',
                    'children' => [
                        [
                            'label' => 'لیست کاربران',
                            'route' => 'np.users.index',
                            'permission' => 'users.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'کاربر جدید',
                            'route' => 'np.users.create',
                            'permission' => 'users.create',
                            'icon' => 'plus',
                        ],
                    ],
                ],
                [
                    'label' => 'مدیران',
                    'icon' => 'user-cog',
                    'children' => [
                        [
                            'label' => 'لیست مدیران',
                            'route' => 'np.admins.index',
                            'permission' => 'admins.index',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'مدیر جدید',
                            'route' => 'np.admins.index',
                            'permission' => 'admins.create',
                            'icon' => 'plus',
                        ],
                        [
                            'label' => 'نقش‌ها',
                            'icon' => 'key-round',
                            'children' => [
                                [
                                    'label' => 'لیست نقش‌ها',
                                    'route' => 'np.roles.index',
                                    'permission' => 'roles.index',
                                    'icon' => 'list',
                                ],
                                [
                                    'label' => 'نقش جدید',
                                    'route' => 'np.roles.index',
                                    'permission' => 'roles.create',
                                    'icon' => 'plus',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'اعلان‌ها',
                    'icon' => 'bell',
                    'children' => [
                        [
                            'label' => 'اعلان‌های پنل',
                            'route' => 'np.notifications.panel',
                            'permission' => 'notifications.panel',
                            'icon' => 'bell-ring',
                        ],
                        [
                            'label' => 'مدیریت اعلان‌ها',
                            'route' => 'np.notifications.index',
                            'permission' => 'notifications',
                            'icon' => 'settings-2',
                        ],
                    ],
                ],
                [
                    'label' => 'ارسال پیام',
                    'route' => 'np.messages.index',
                    'permission' => 'settings.sms',
                    'icon' => 'send',
                ],
            ],
        ],

        /*
        |------------------
        | امور مدیریتی
        |------------------
        */
        [
            'key' => 'management',
            'title' => 'امور مدیریتی',
            'items' => [
                [
                    'label' => 'درون‌ریزی',
                    'icon' => 'file-spreadsheet',
                    'children' => [
                        [
                            'label' => 'درون‌ریزی مطالب',
                            'route' => 'np.imports.posts',
                            'permission' => 'imports.posts',
                            'icon' => 'download',
                        ],
                        [
                            'label' => 'درون‌ریزی محصولات',
                            'route' => 'np.imports.products',
                            'permission' => 'imports.products',
                            'icon' => 'download',
                        ],
                        [
                            'label' => 'درون‌ریزی کاربران',
                            'route' => 'np.imports.users',
                            'permission' => 'imports.users',
                            'icon' => 'download',
                        ],
                    ],
                ],
                [
                    'label' => 'تنظیمات',
                    'icon' => 'settings',
                    'children' => [
                        [
                            'label' => 'اطلاعات فروشگاه',
                            'route' => 'np.settings.information',
                            'permission' => 'settings.information',
                            'icon' => 'store',
                        ],
                        [
                            'label' => 'شبکه‌های اجتماعی',
                            'route' => 'np.settings.socials',
                            'permission' => 'settings.socials',
                            'icon' => 'share-2',
                        ],
                        [
                            'label' => 'درگاه‌های پرداخت',
                            'route' => 'np.settings.gateways',
                            'permission' => 'settings.gateway',
                            'icon' => 'credit-card',
                        ],
                        [
                            'label' => 'سایر تنظیمات',
                            'route' => 'np.settings.others',
                            'permission' => 'settings.others',
                            'icon' => 'sliders-horizontal',
                        ],
                        [
                            'label' => 'تنظیمات پیامک',
                            'route' => 'np.settings.sms',
                            'permission' => 'settings.sms',
                            'icon' => 'message-circle',
                        ],
                        [
                            'label' => 'ویجت شناور',
                            'route' => 'np.settings.floating-widget',
                            'permission' => 'settings.floating-widget',
                            'icon' => 'panel-bottom',
                        ],
                    ],
                ],
                [
                    'label' => 'بیشتر',
                    'icon' => 'ellipsis',
                    'children' => [
                        [
                            'label' => 'فیلدهای اختصاصی',
                            'route' => 'np.filds.index',
                            'permission' => 'filds.index',
                            'icon' => 'database',
                        ],
                        [
                            'label' => 'ریدایرکت‌ها',
                            'route' => 'np.redirects.index',
                            'permission' => 'redirects.index',
                            'icon' => 'arrow-right-left',
                        ],
                        [
                            'label' => 'مدیریت فایل‌ها',
                            'route' => 'np.file-manager.index',
                            'permission' => 'file-manager',
                            'icon' => 'folder-open',
                        ],
                        [
                            'label' => 'لیست بکاپ‌ها',
                            'route' => 'np.backups.index',
                            'permission' => 'backups.index',
                            'icon' => 'archive',
                        ],
                        [
                            'label' => 'کلیدهای وب سرویس',
                            'route' => 'np.apikeys.index',
                            'permission' => 'apikeys.index',
                            'icon' => 'key-square',
                        ],
                    ],
                ],
                [
                    'label' => 'برنامه‌نویس',
                    'icon' => 'code-2',
                    'danger' => true,
                    'creator_only' => true,
                    'children' => [
                        [
                            'label' => 'دسترسی‌ها',
                            'route' => 'np.permissions.index',
                            'permission' => null,
                            'icon' => 'shield',
                        ],
                        [
                            'label' => 'تنظیمات برنامه‌نویس',
                            'route' => 'np.developer.index',
                            'permission' => null,
                            'icon' => 'settings',
                        ],
                        [
                            'label' => 'لاگ سیستم',
                            'route' => 'np.logs.index',
                            'permission' => null,
                            'icon' => 'scroll-text',
                            'external' => true,
                        ],
                        [
                            'label' => 'تنظیمات Robots',
                            'route' => 'np.robots.index',
                            'permission' => null,
                            'icon' => 'bot',
                        ],
                        [
                            'label' => 'بروزرسانی سیستم',
                            'route' => 'np.developer.index',
                            'permission' => null,
                            'icon' => 'refresh-cw',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

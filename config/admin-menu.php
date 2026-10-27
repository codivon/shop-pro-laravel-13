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
                    'label' => 'فعالیت‌های اخیر',
                    'route' => 'np.activity-log.index',
                    'permission' => 'activity-log.index',
                    'icon' => 'history',
                ],
                // فاز ۱۷: «نشست‌های فعال» و «مانیتور سیستم» طبق درخواست کاربر به بالای منو
                // (نمای کلی) برگشتند. «دستگاه‌های بلاک شده» حذف شد (فاز ۱۳ به‌درخواست کاربر)
                // — لیست بلاک‌شده‌ها به‌صورت تب داخل np.sessions.index در دسترس است.
                [
                    'label' => 'نشست‌های فعال',
                    'route' => 'np.sessions.index',
                    'permission' => 'sessions.index',
                    'icon' => 'monitor',
                ],
                [
                    'label' => 'مانیتور سیستم',
                    'route' => 'np.pulse.index',
                    'permission' => 'pulse.monitor',
                    'icon' => 'activity',
                    'live' => true,
                ],
                [
                    'label' => 'بازار',
                    'icon' => 'package',
                    'children' => [
                        [
                            'label' => 'پکیج‌ها و قالب‌ها',
                            'route' => 'np.packages.index',
                            'permission' => 'packages.index',
                            'icon' => 'package',
                            // فاز ۹۹ — گیت تنظیماتی (الگوی فاز ۸۳): خاموشی کلید اصلی بازارچه ⇒ آیتم منو مخفی
                            'enabled' => 'packages_market_enabled',
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
        | عملیات محصولات
        |------------------
        */
        [
            'key' => 'products',
            'title' => 'عملیات محصولات',
            'items' => [
                [
                    // فاز ۱۶: «محصولات» اولین آیتم گروه (پرکاربردترین بخش فروشگاه)
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
                        // فاز ۸۴ — به درخواست کاربر: منوی «هشدار کاهش قیمت» در «محصولات»،
                        // دقیقاً زیر «اطلاع‌رسانی موجودی» (قبلاً در اعلان‌ها بود)؛
                        // فقط وقتی قابلیت از تنظیمات (dt_price_alert_status) فعال باشد
                        [
                            'label' => 'هشدار کاهش قیمت',
                            'route' => 'np.price-alerts.index',
                            'permission' => 'products.price-alert',
                            'icon' => 'trending-down',
                            'enabled' => 'dt_price_alert_status',
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
                            'route' => 'np.brands.index',
                            'permission' => 'products.brands',
                            'icon' => 'award',
                        ],
                        [
                            'label' => 'گروه‌های ویژگی',
                            'route' => 'np.attribute-groups.index',
                            'permission' => 'attributes.groups.index',
                            'icon' => 'sliders-horizontal',
                        ],
                        [
                            'label' => 'ویژگی جدید',
                            'route' => 'np.attributes.create',
                            'permission' => 'attributes.create',
                            'icon' => 'plus',
                        ],
                        [
                            'label' => 'فیلترها',
                            'route' => 'np.filters.index',
                            'permission' => 'filters.index',
                            'icon' => 'filter',
                        ],
                        [
                            'label' => 'فیلتر جدید',
                            'route' => 'np.filters.create',
                            'permission' => 'filters.create',
                            'icon' => 'plus',
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
                            'permission' => 'carriers',
                            'icon' => 'list',
                        ],
                        [
                            'label' => 'لیست استان‌ها',
                            'route' => 'np.provinces.index',
                            'permission' => 'carriers.provinces.index',
                            'icon' => 'map',
                        ],
                    ],
                ],
                [
                    // فاز ۱۶: «انبارداری» به انتهای گروه منتقل شد (کاربرد تخصصی‌تر)
                    'label' => 'انبارداری',
                    'icon' => 'warehouse',
                    'children' => [
                        [
                            'label' => 'لیست انبارها',
                            'route' => 'np.warehouses.index',
                            'permission' => 'warehouses.index',
                            'icon' => 'list',
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
                            'vendor' => true,
                        ],
                        [
                            // فاز ۱۰۳ — پنل کمیسیون فروشندگان (گیت صفحه: کلید اصلی چندفروشندگی + همین پرمیشن)
                            'label' => 'کمیسیون فروشندگان',
                            'route' => 'np.vendors.commission',
                            'permission' => 'payments.transactions.seller_deposit',
                            'icon' => 'percent',
                            'vendor' => true,
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
                    'vendor' => true,
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
            ],
        ],
        /*
        |------------------
        | امور فروشندگان
        |------------------
        */
        [
            // فاز ۱۲: فقط وقتی سیستم چند فروشندگی فعال است نمایش داده می‌شود
            'vendor' => true,
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
                            // فاز ۱۰۶-۷ — گیت سوییچ multi_vendor_leaderboard (الگوی فاز ۸۳)
                            'label' => 'مقایسهٔ فروشندگان',
                            'route' => 'np.sellers.leaderboard',
                            'permission' => 'sellers.index',
                            'icon' => 'trophy',
                            'enabled' => 'multi_vendor_leaderboard',
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
                            'permission' => 'sellers',
                            'icon' => 'sparkles',
                        ],
                        [
                            // فاز ۱۰۹ — محتوای لندینگ تک‌صفحه‌ای /seller
                            'label' => 'صفحهٔ فرود فروشندگان',
                            'route' => 'np.settings.seller-landing',
                            'permission' => 'sellers',
                            'icon' => 'layout-template',
                        ],
                        [
                            'label' => 'نمایش میزان کمیسیون',
                            'route' => 'np.settings.seller-commission',
                            'permission' => 'sellers',
                            'icon' => 'percent',
                        ],
                        [
                            'label' => 'سوالات پر تکرار',
                            'route' => 'np.settings.seller-question',
                            'permission' => 'sellers',
                            'icon' => 'message-circle-question',
                        ],
                        [
                            'label' => 'قرارداد الکترونیک',
                            'route' => 'np.settings.seller-econtract',
                            'permission' => 'sellers',
                            'icon' => 'file-signature',
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
                        // فاز ۱۹: «دیدگاه‌ها» (دیدگاه‌های مطالب) به زیرمنوی وبلاگ منتقل شد
                        [
                            'label' => 'دیدگاه‌ها',
                            'route' => 'np.comments.posts',
                            'permission' => 'posts.comments',
                            'icon' => 'message-square',
                        ],
                    ],
                ],
                // فاز ۱۶: «صفحات» بلافاصله بعد از وبلاگ (محتوای اصلی سایت)
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
                // فاز ۱۹: «دیدگاه‌ها» به زیرمنوی وبلاگ منتقل شد (np.comments.posts)
                [
                    'label' => 'تگ‌ها',
                    'route' => 'np.tags.index',
                    'permission' => 'tags',
                    'icon' => 'tags',
                ],
                // فاز ۱۶: «جستجوها» ابزار تحلیلی است — به انتهای گروه منتقل شد
                [
                    'label' => 'جستجوها',
                    'route' => 'np.searches.index',
                    'permission' => 'searches',
                    'icon' => 'search',
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
                        // فاز ۱۹: «آپلود قالب جدید» حذف شد — نصب قالب از داخل صفحه‌ی
                        // «لیست قالب‌ها» (کارت آپلود) انجام می‌شود.
                        [
                            'label' => 'تنظیمات قالب',
                            'route' => 'np.themes.settings',
                            'permission' => 'themes.settings',
                            'icon' => 'settings-2',
                        ],
                        [
                            // فاز ۱۱: ورود اول به لیست ابزارک‌ها؛ ویجت‌ساز تعاملی از همان صفحه در دسترس است
                            'label' => 'ابزارک‌های صفحه اصلی',
                            'route' => 'np.widgets.index',
                            'permission' => 'themes.settings',
                            'icon' => 'layout-panel-top',
                            'active_routes' => ['np.widgets.index', 'np.widgets.create', 'np.widgets.edit', 'np.widgets.builder'],
                        ],
                        [
                            'label' => 'ابزارک‌های مقالات',
                            'route' => 'np.posts-widgets.index',
                            'permission' => 'themes.settings',
                            'active_routes' => ['np.posts-widgets.index', 'np.posts-widgets.create', 'np.posts-widgets.edit', 'np.widgets.posts-builder'],
                            'icon' => 'layout-panel-left',
                        ],
                    ],
                ],
                [
                    // فاز ۱۶: «منوها» بلافاصله بعد از قالب (ساختار ناوبری سایت)
                    'label' => 'منوها',
                    'route' => 'np.menus.index',
                    'permission' => 'menus',
                    'icon' => 'menu',
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
                            'label' => 'گروه‌های لینک',
                            'route' => 'np.links.groups',
                            'permission' => 'links.groups',
                            'icon' => 'folder',
                        ],
                    ],
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
                            'label' => 'نقش‌ها',
                            'route' => 'np.roles.index',
                            'permission' => 'roles.index',
                            'icon' => 'key-round',
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
                        // فاز ۸۴ — آیتم «هشدار کاهش قیمت» به «محصولات» (زیر اطلاع‌رسانی موجودی) منتقل شد
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
                // فاز ۱۷: «نشست‌های فعال» و «مانیتور سیستم» به نمای کلی برگشتند
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
                            'label' => 'پشتیبان‌گیری',
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
                            'creator_only' => true,
                            'icon' => 'shield',
                        ],
                        [
                            'label' => 'تنظیمات برنامه‌نویس',
                            'route' => 'np.developer.index',
                            'permission' => null,
                            'creator_only' => true,
                            'icon' => 'settings',
                        ],
                        [
                            'label' => 'لاگ های سیستم',
                            'route' => 'np.logs.index',
                            'permission' => null,
                            'creator_only' => true,
                            'icon' => 'scroll-text',
                            // فاز ۲۰: دیگر تب جدید باز نمی‌شود — صفحه‌ی داخلی با دانلود/پاک‌سازی لاگ
                        ],
                        [
                            'label' => 'تنظیمات Robots',
                            'route' => 'np.robots.index',
                            'permission' => null,
                            'creator_only' => true,
                            'icon' => 'bot',
                        ],
                        // فاز ۱۷: «بروزرسانی سیستم» فعلاً از منو حذف شد (در جای دیگری در
                        // دسترس است) — روت np.developer.index همچنان کار می‌کند.
                    ],
                ],
            ],
        ],
    ],
];

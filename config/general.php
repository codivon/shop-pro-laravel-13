<?php

return [

    'api_url' => 'http://webtpro.ir/api/v1',

    'admin_route_prefix' => env('ADMIN_ROUTE_PREFIX'),

    'current_theme' => env('CURRENT_THEME', 'WeblakShop'),

    'permissions' => [

        'admins' => [
            'title' => 'مدیریت مدیران',
            'values' => [
                'index' => 'لیست مدیران',
                'view' => 'مشاهده مدیر',
                'create' => 'ایجاد مدیر',
                'update' => 'ویرایش مدیر',
                'delete' => 'حذف مدیر',
                'export' => 'خروجی گرفتن',
            ]
        ],
        'sessions' => [
            'title' => 'مدیریت نشست های فعال',
            'values' => [
                'index' => 'لیست نشست های فعال',
                'exit' => 'خروج دادن',
                'blocked' => 'مسدود سازی',
            ]
        ],
        'pulse' => [
            'title' => 'سنجه‌های سیستمی',
            'values' => [
                'monitor' => 'نمایش وضعیت لحظه‌ای سیستم',
            ]
        ],
        'activity-log' => [
            'title' => 'فعالیت های اخیر',
            'values' => [
                'index' => 'لیست فعالیت ها',
            ]
        ],
        'users' => [
            'title' => 'مدیریت کاربران',
            'values' => [
                'index' => 'لیست کاربران',
                'view' => 'مشاهده کاربر',
                'create' => 'ایجاد کاربر',
                'update' => 'ویرایش کاربر',
                'delete' => 'حذف کاربر',
                'export' => 'خروجی گرفتن',
                'wallet' => 'مدیریت کیف پول',
            ]
        ],

        'posts' => [
            'title' => 'مدیریت نوشته ها',
            'values' => [
                'index' => 'لیست نوشته ها',
                'details' => 'جزئیات نوشته',
                'create' => 'ایجاد نوشته',
                'createAi' => 'ایجاد نوشته توسط هوش مصنویی',
                'update' => 'ویرایش نوشته',
                'delete' => 'حذف نوشته',
                'category' => 'مدیریت دسته بندی ها',
                'comments' => 'مدیریت دیدگاه ها',
            ]
        ],
        'packages' => [
            'title' => 'بازار (پکیج و قالب)',
            'values' => [
                'index' => 'پکیج‌ها و قالب‌ها',
                'installed' => 'پکیج های نصب شده',
            ]
        ],

        'products' => [
            'title' => 'مدیریت محصولات',
            'values' => [
                'index' => 'لیست محصولات',
                'create' => 'ایجاد محصول',
                'update' => 'ویرایش محصول',
                'details' => 'جزئیات محصول',
                'delete' => 'حذف محصول',
                'export' => 'خروجی گرفتن',
                'category' => 'مدیریت دسته بندی ها',
                'spectypes' => 'مدیریت نوع مشخصات',
                'sizetypes' => 'مدیریت سایزبندی ها',
                'stock-notify' => 'مدیریت لیست اطلاع از موجودی',
                'price-alert' => 'مدیریت هشدار کاهش قیمت',
                'brands' => 'مدیریت برندها',
                'prices' => 'قیمت ها',
                'pricesGroup' => 'تغییر گروهی قیمت',
                'reviews' => 'مدیریت دیدگاه ها',
                'comments' => 'مدیریت پرسش و پاسخ ها',
            ]
        ],
        'warehouses' => [
            'title' => 'مدیریت انبارها',
            'values' => [
                'index' => 'لیست انبارها',
                'create' => 'ایجاد انبار',
                'update' => 'ویرایش انبار',
                'show' => 'نمایش انبار',
                'delete' => 'حذف انبار',
                'export' => 'خروجی گرفتن از انبار',
                'movements' => 'تاریخچه انبار',
                'movements_export' => 'خروجی گرفتن از تاریخچه انبار',
            ]
        ],
        'sellers' => [
            'title' => 'مدیریت فروشندگان',
            'values' => [
                'index' => 'لیست فروشندگان',
                'view' => 'مشاهده فروشنده',
                'update' => 'ویرایش فروشنده',
                'delete' => 'حذف فروشنده',
                'export' => 'خروجی گرفتن',
                'wallet' => 'مدیریت کیف پول',
                'products' => 'لیست محصولات',
                'orders' => 'لیست سفارشات',
                'orders.export' => 'خروجی گرفتن سفارشات',
            ]
        ],

        'request_deposit' => 'درخواستی واریز وجه',

        'payments.transactions.seller_deposit' => 'لیست واریزی فروشندگان',

        'discounts' => [
            'title' => 'مدیریت تخفیف ها',
            'values' => [
                'index' => 'لیست تخفیف ها',
                'create' => 'ایجاد تخفیف',
                'update' => 'ویرایش تخفیف',
                'delete' => 'حذف تخفیف',
            ]
        ],

        'attributes' => [
            'title' => 'مدیریت ویژگی ها',
            'values' => [
                'groups.index' => 'لیست گروه ویژگی ها',
                'groups.show' => 'مشاهده گروه ویژگی',
                'groups.create' => 'ایجاد گروه ویژگی',
                'groups.update' => 'ویرایش گروه ویژگی',
                'groups.delete' => 'حذف گروه ویژگی',

                'index' => 'لیست ویژگی ها',
                'create' => 'ایجاد ویژگی',
                'update' => 'ویرایش ویژگی',
                'delete' => 'حذف ویژگی',
            ]
        ],

        'filters' => [
            'title' => 'مدیریت فیلترها',
            'values' => [
                'index' => 'لیست فیلترها',
                'create' => 'ایجاد فیلتر',
                'update' => 'ویرایش فیلتر',
                'delete' => 'حذف فیلتر',
            ]
        ],

        'orders' => [
            'title' => 'مدیریت سفارشات',
            'values' => [
                'index' => 'لیست سفارشات',
                'create' => 'افزودن سفارش جدید',
                'view' => 'مشاهده سفارش',
                'update' => 'ویرایش سفارش',
                'delete' => 'حذف سفارش',
                'export' => 'خروجی گرفتن',
            ]
        ],

        'returns' => [
            'title' => 'مدیریت مرجوعی',
            'values' => [
                'index' => 'لیست مرجوعی‌ها',
                'show' => 'مشاهده مرجوعی',
                'approve' => 'تأیید مرجوعی',
                'received' => 'ثبت دریافت کالا',
                'complete' => 'تکمیل مرجوعی',
                'reject' => 'رد مرجوعی',
                'reasons' => 'مدیریت دلایل مرجوعی',
                'reasons.store' => 'افزودن دلیل مرجوعی',
                'reasons.destroy' => 'حذف دلیل مرجوعی',
                'reasons.toggle' => 'تغییر وضعیت دلیل مرجوعی',
            ]
        ],

        'carriers' => [
            'title' => 'مدیریت حمل و نقل',
            'values' => [
                'provinces.index' => 'لیست استان ها',
                'provinces.update' => 'ویرایش استان',
                'provinces.delete' => 'حذف استان',
                'provinces.create' => 'ایجاد استان',
                'provinces.show' => 'مشاهده استان',
                'cities.update' => 'ویرایش شهر',
                'cities.delete' => 'حذف شهر',
                'cities.create' => 'ایجاد شهر',
            ]
        ],

        'tags' => [
            'title' => 'مدیریت تگ ها',
            'values' => [
                'index' => 'لیست تگ ها',
                'create' => 'ایجاد تگ',
                'update' => 'ویرایش',
                'show' => 'جزئیات',
                'delete' => 'حذف تگ',
            ]
        ],

        'sliders' => [
            'title' => 'مدیریت اسلایدرها',
            'values' => [
                'index' => 'لیست اسلایدرها',
                'create' => 'ایجاد اسلایدر',
                'update' => 'ویرایش اسلایدر',
                'delete' => 'حذف اسلایدر',
            ]
        ],

        'banners' => [
            'title' => 'مدیریت بنرها',
            'values' => [
                'index' => 'لیست بنرها',
                'create' => 'ایجاد بنر',
                'update' => 'ویرایش بنر',
                'delete' => 'حذف بنر',
            ]
        ],

        'links' => [
            'title' => 'مدیریت لینک های فوتر',
            'values' => [
                'index' => 'لیست لینک ها',
                'create' => 'ایجاد لینک',
                'update' => 'ویرایش لینک',
                'delete' => 'حذف لینک',
                'groups' => 'مدیریت گروه ها'
            ]
        ],

        'backups' => [
            'title' => 'مدیریت بکاپ ها',
            'values' => [
                'index' => 'لیست بکاپ ها',
                'create' => 'ایجاد بکاپ',
                'download' => 'دانلود بکاپ',
                'delete' => 'حذف بکاپ',
            ]
        ],

        'apikeys' => [
            'title' => 'مدیریت کلیدهای وب سرویس',
            'values' => [
                'index' => 'لیست کلیدهای وب سرویس',
                'create' => 'ایجاد کلید وب سرویس',
                'update' => 'ویرایش کلید وب سرویس',
                'delete' => 'حذف کلید وب سرویس',
            ]
        ],

        'pages' => [
            'title' => 'مدیریت صفحات',
            'values' => [
                'index' => 'لیست صفحات',
                'details' => 'جزئیات صفحه',
                'create' => 'ایجاد صفحه',
                'update' => 'ویرایش صفحه',
                'delete' => 'حذف صفحه',
            ]
        ],
        'forms' => [
            'title' => 'مدیریت فرم ها',
            'values' => [
                'index' => 'لیست صفحات',
                'create' => 'ایجاد صفحه',
                'update' => 'ویرایش صفحه',
                'submissions' => 'مشاهده پاسخ ها',
                'preview' => 'پیشنمایش',
                'delete' => 'حذف صفحه',
            ]
        ],

        'roles' => [
            'title' => 'مدیریت مقام ها',
            'values' => [
                'index' => 'لیست مقام ها',
                'create' => 'ایجاد مقام',
                'update' => 'ویرایش مقام',
                'delete' => 'حذف مقام',
            ]
        ],

        'statistics' => [
            'title' => 'گزارشات',
            'values' => [
                'orders' => 'سفارشات',
                'users' => 'کاربران',
                'views' => 'بازدیدها',
                'viewsList' => 'لیست بازدیدها',
                'product' => 'آمارگیری فروش هر محصول',
                'viewers' => 'بازدید کنندگان امروز',
                'sms' => 'لاگ پیامک های ارسالی',
            ]
        ],

        'themes' => [
            'title' => 'مدیریت قالب ها',
            'values' => [
                'index' => 'لیست قالب ها',
                'create' => 'افزودن قالب',
                'update' => 'تغییر قالب',
                'delete' => 'حذف قالب',
                'settings' => 'تنظیمات قالب',
                'widgets' => 'مدیریت صفحه اصلی'
            ]
        ],

        'file-manager' => 'مدیریت فایل ها',



        'filds' => [
            'title' => 'فیلدهای اختصاصی',
            'values' => [
                'index' => 'لیست فیلد ها',
                'create' => 'ایجاد فیلد',
                'update' => 'ویرایش فیلد',
                'delete' => 'حذف فیلد',
            ]
        ],

        'redirects' => [
            'title' => ' ریدایرکت',
            'values' => [
                'index' => 'لیست ریدایرکت ها',
                'create' => 'ایجاد ریدایرکت',
                'update' => 'ویرایش ریدایرکت',
                'delete' => 'حذف ریدایرکت',
            ]
        ],

        'tickets' => [
            'title' => 'مدیریت تیکت ها',
            'values' => [
                'index' => 'لیست تیکت ها',
                'show' => 'مشاهده تیکت',
                'create' => 'ایجاد تیکت',
                'update' => 'ویرایش تیکت',
                'delete' => 'حذف تیکت',
            ]
        ],

        'menus' => [
            'title' => 'مدیریت منو ها',
            'values' => [
                'index' => 'لیست منو ها',
                'create' => 'ایجاد منو',
                'update' => 'ویرایش منو',
                'delete' => 'حذف منو',
            ]
        ],

        'payments' => [
            'title' => 'مدیریت پرداخت',
            'values' => [
                'transactions.index' => 'لیست تراکنش ها',
                'transactions.view' => 'مشاهده تراکنش',
                'transactions.delete' => 'حذف تراکنش',
                'currencies' => 'مدیریت ارزها',
                'wallet-histories.index' => 'تاریخچه کیف پول'
            ]
        ],

        'contacts' => [
            'title' => 'مدیریت تماس با ما',
            'values' => [
                'index' => 'لیست تماس با ما',
                'view' => 'مشاهده تماس با ما',
                'delete' => 'حذف تماس با ما',
            ]
        ],

        'notifications' => [
            'title' => 'مدیریت اعلان ها',
            'values' => [
                'index' => 'لیست اعلان ها',
                'create' => 'ایجاد اعلان',
                'show' => 'مشاهده اعلان',
                'update' => 'ویرایش اعلان',
                'delete' => 'حذف اعلان',
                'panel' => 'مشاهده اعلانات پنل',
            ]
        ],
        'searches' => [
            'title' => 'مدیریت جستجوها',
            'values' => [
                'index' => 'لیست جستجوها',
                'delete' => 'حذف جستجوها',
            ]
        ],
        'newsletters' => [
            'title' => 'مدیریت خبرنامه ها',
            'values' => [
                'index' => 'لیست خبرنامه',
                'show' => 'جزئیات خبرنامه',
                'delete' => 'حذف خبرنامه',
            ]
        ],
        'faqs' => [
            'title' => 'مدیریت سوالات متداول',
            'values' => [
                'index' => 'لیست سوالات متداول',
                'create' => 'ایجاد  سوالات متداول',
                'update' => 'ویرایش  سوالات متداول',
                'delete' => 'حذف سوالات متداول',
            ]
        ],

        'imports' => [
            'title' => 'مدیریت درون ریزی ها',
            'values' => [
                'posts' => 'درون ریزی مقالات',
                'products' => 'درون ریزی محصولات',
                'users' => 'درون ریزی کاربران',
            ]
        ],

        'settings' => [
            'title' => 'تنظیمات',
            'values' => [
                'information' => 'اطلاعات سایت',
                'socials' => 'شبکه های اجتماعی',
                'gateway' => 'درگاه های پرداخت',
                'others' => 'تنظیمات دیگر',
                'sms' => 'تنظیمات پیامک',
                'floating-widget' => 'تنظیمات ویجت شناور',
            ]
        ],


    ],

    'static_menus' => [
        'posts' => [
            'title' => 'وبلاگ'
        ],
        'products' => [
            'title' => 'محصولات',
        ]
    ],

    'supported_gateways' => [
        'behpardakht' => 'به پرداخت ملت',
        'payir' => 'pay.ir',
        'zarinpal' => 'زرین پال',
        'toman' => 'تومن',
        'payping' => 'پی پینگ',
        'irankish' => 'ایران کیش',
        'saman' => 'سامان',
        'sepehr' => 'بانک صادرات',
        'idpay' => 'idpay',
        'sadad' => 'بانک ملی',
        'zibal' => 'زیبال',
    ],


    'non_language_options' => [
        'multi_language_enabled',
        'debugbar_enabled',
        'user_register_gift_credit',

        // فاز ۴۴ — تنظیمات امنیتی (تب «امنیت» تنظیمات برنامه‌نویس) — مستقل از زبان
        'security_captcha_enabled',
        'security_captcha_admin_login',
        'security_captcha_register',
        'security_captcha_customer_login',
        'security_captcha_comments',
        'security_time_trap_seconds',
        'security_honeypot_enabled',
        'security_lockout_enabled',
        'security_lockout_admin_max',
        'security_lockout_admin_minutes',
        'security_lockout_customer_max',
        'security_lockout_customer_minutes',
        'security_lockout_comments_max',
        'security_lockout_comments_minutes',
        'security_headers_enabled',
        'security_hsts_enabled',
        'security_bot_trap_enabled', // فاز ۷۱ — کلید اصلی تلهٔ ربات
        'admin_2fa_enabled',
        'admin_2fa_channel',
    ]
];

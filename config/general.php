<?php
$default_images_path = 'themes/SaniarTheme/src/resources/assets/image/';
return [
    'theme_name' => 'SaniarTheme',

    'sliderGroups' => [
        'home' => [
            [
                'group' => 'main_sliders',
                'name' => 'اسلایدر اصلی',
                'width' => 1780,
                'height' => 890,
                'count' => 5,
                'size' => '890 * 1780',
                'images' => [
                    $default_images_path . 'banner-1.jpg',
                    $default_images_path . 'banner-2.jpg',
                    $default_images_path . 'banner-3.jpg',
                    $default_images_path . 'banner-11.jpg',
                    $default_images_path . 'banner-12.jpg',
                ],
            ],
            [
                'group' => 'coworker_sliders',
                'name' => 'اسلایدر لوگو همکاران',
                'width' => 100,
                'height' => 100,
                'count' => 6,
                'size' => '100 * 100',
                'images' => [
                    $default_images_path . 'brand1-1.png',
                    $default_images_path . 'brand1-2.png',
                    $default_images_path . 'brand1-3.png',
                    $default_images_path . 'brand1-4.png',
                    $default_images_path . 'brand1-5.png',
                    $default_images_path . 'brand1-6.png',
                ],
            ],
            [
                'group' => 'sevices_sliders',
                'name' => 'اسلایدر خدمات',
                'width' => 60,
                'height' => 60,
                'count' => 5,
                'size' => '60 * 60',
                'images' => [
                    $default_images_path . 'about-us-2.jpg',
                    $default_images_path . 'badge.png',
                ],
            ],
        ],
        'posts' => [
            [
                'group' => 'main_sliders',
                'name' => 'اسلایدر اصلی',
                'width' => 1780,
                'height' => 890,
                'count' => 5,
                'size' => '890 * 1780',
                'images' => [
                    $default_images_path . 'banner-1.jpg',
                    $default_images_path . 'banner-2.jpg',
                    $default_images_path . 'banner-3.jpg',
                ],
            ],
        ],
        'sellers' => [
            [
                'group' => 'fullscreen_slider',
                'name' => 'اسلایدر تمام صفحه',
                'width' => 1600,
                'height' => 600,
                'count' => 2,
                'size' => '600 * 1800',
                'images' => [
                    $default_images_path . 'banner-section-blue.webp',
                    $default_images_path . 'banner-13.jpg',
                ],
            ],
        ],
    ],

    'bannerGroups' => [
        'home' => [
            [
                'group' => 'index_middle_banners',
                'name' => 'بنر تکی',
                'width' => 1300,
                'height' => 300,
                'count' => 1,
                'size' => '300 * 1300',
                'images' => [
                    $default_images_path . 'banner-4.webp',
                ],
            ],
            [
                'group' => 'index_middle_2_banners',
                'name' => 'بنر دوتایی',
                'width' => 650,
                'height' => 300,
                'count' => 2,
                'size' => '300 * 650',
                'images' => [
                    $default_images_path . 'banner-1.webp',
                    $default_images_path . 'banner-2.webp',
                ],
            ],
            [
                'group' => 'index_middle_4_banners',
                'name' => 'بنر چهارتایی',
                'width' => 320,
                'height' => 240,
                'count' => 4,
                'size' => '240 * 320',
                'images' => [
                    $default_images_path . 'banner-four-1.jpg',
                    $default_images_path . 'banner-four-2.jpg',
                    $default_images_path . 'banner-four-3.jpg',
                    $default_images_path . 'banner-four-4.jpg',
                ],
            ],
            [
                'group' => 'index_highest_banner',
                'name' => 'بنر تکی بالای هدر',
                'width' => 1300,
                'height' => 60,
                'count' => 1,
                'size' => '1352 * 60',
                'images' => [
                    $default_images_path . 'banner-3.webp',
                ],
            ],
        ],
        'posts' => [
            [
                'group' => 'index_middle_4_banners',
                'name' => 'بنر چهارتایی',
                'width' => 320,
                'height' => 240,
                'count' => 4,
                'size' => '240 * 320',
                'images' => [
                    $default_images_path . 'banner-four-1.jpg',
                    $default_images_path . 'banner-four-2.jpg',
                    $default_images_path . 'banner-four-3.jpg',
                    $default_images_path . 'banner-four-4.jpg',
                ],
            ],
        ],
        'sellers'=>[]
    ],

    'bannerGroupsPlace' => [
        [
            'group' => 'index_banners_place1',
            'name' => 'گروه اول',
            'width' => 650,
            'height' => 300,
            'count' => 2,
            'size' => '300 * 650',
            'images' => [
                $default_images_path . 'banner-1.webp',
            ],
        ],
        [
            'group' => 'index_banners_place2',
            'name' => 'گروه دوم',
            'width' => 320,
            'height' => 240,
            'count' => 4,
            'size' => '240 * 320'
        ],
        [
            'group' => 'index_banners_place3',
            'name' => 'گروه سوم',
            'width' => 650,
            'height' => 300,
            'count' => 2,
            'size' => '300 * 650'
        ],
        [
            'group' => 'index_banners_place4',
            'name' => 'گروه چهارم',
            'width' => 320,
            'height' => 240,
            'count' => 4,
            'size' => '240 * 320'
        ],
    ],

    'slider_sections' => [
        [
            'name' => 'صفحه اصلی',
            'key' => 'home',
        ],
        [
            'name' => 'صفحه اصلی مقالات',
            'key' => 'posts',
        ],
        [
            'name' => 'صفحه اصلی فروشندگان',
            'key' => 'sellers',
        ],
    ],
    'banner_sections' => [
        [
            'name' => 'صفحه اصلی',
            'key' => 'home',
        ],
        [
            'name' => 'صفحه اصلی مقالات',
            'key' => 'posts',
        ],
    ],


    'imageSizes' => [
        'productCategoryImage' => '500 * 500',
        'CategoryImage' => '500 * 500',
        'postImage' => '300 * 400',
        'productGalleryImage' => '720 * 1280',
        'productImage' => '600 * 600',
        'logo' => '36 * 128',
        'icon' => '32 * 32',
    ],

    'linkGroups' => [
        [
            'name' => 'گروه اول',
            'key' => 1,
        ],
        [
            'name' => 'گروه دوم',
            'key' => 2,
        ],
        [
            'name' => 'گروه سوم',
            'key' => 3,
        ],
    ],

    'errors' => [
        '404' => 'front::errors.404'
    ],

    'pages' => [
        'login' => 'front::auth.login',
        'register' => 'front::auth.register',
        'forgot-password' => 'front::auth.forgot-password',
        'login-with-code' => 'front::auth.login-with-code',
        'one-time-login' => 'front::auth.one-time-login',
    ],

    'routes' => [
        'verify' => 'front.verify.showVerify',
        'change-password' => 'front.user.force-change-password',
        'change-password-routes' => ['front.user.force-change-password', 'front.user.force-update-password'],
    ],

    'exceptVerifyCsrfToken' => [
        'orders/payment/callback/*',
        'wallet/payment/callback/*',
    ],

    'asset_path' => 'themes/SaniarTheme/',
    'mainfest_path' => 'themes/SaniarTheme',
    'demo' => [
        'image' => 'image/logo.png',
        'name' => 'سانیار',
        'description' => 'قالب فروشگاه سانیار'
    ],

    'socials' => [
        [
            'name' => 'تلگرام',
            'key' => 'social_telegram',
            'icon' => 'bi bi-telegram',
        ],
        [
            'name' => 'اینستاگرام',
            'key' => 'social_instagram',
            'icon' => 'bi bi-instagram',
        ],
        [
            'name' => 'واتساپ',
            'key' => 'social_whatsapp',
            'icon' => 'bi bi-whatsapp',
        ],
    ],

    'settings' => [
        'fields' => [
            [
                'title' => 'نمایش نمودار قیمت محصول',
                'key' => 'dt_show_price_change_chart',
                'input-type' => 'select',
                'class' => 'col-md-3 col-6',
                'options' => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
                'attributes' => 'required'
            ],
            [
                'title' => 'نمایش اشتراک گذاری محصول',
                'key' => 'show_product_share_links',
                'input-type' => 'select',
                'class' => 'col-md-3 col-6',
                'options' => [
                    [
                        'value' => '1',
                        'title' => 'بله'
                    ],
                    [
                        'value' => '0',
                        'title' => 'خیر'
                    ]
                ],
                'attributes' => 'required'
            ],
            [
                'title' => 'متن توضیحات نظرات محصول',
                'key' => 'dt_product_reviews_description',
                'input-type' => 'inline-editor',
                'class' => 'col-md-7',
            ],
            [
                'input-type' => 'linebreak',
                'html' => '<hr>'
            ],
            [
                'title' => 'نوع پاپ آپ صفحه اصلی',
                'key' => 'dt_index_popup_type',
                'input-type' => 'select',
                'class' => 'col-md-3 col-6',
                'options' => [
                    [
                        'value' => 'none',
                        'title' => 'هیچکدام'
                    ],
                    [
                        'value' => 'image',
                        'title' => 'تصویر'
                    ],
                    [
                        'value' => 'text',
                        'title' => 'متن'
                    ]
                ],
                'attributes' => 'required'
            ],
            [
                'title' => 'تصویر پاپ آپ',
                'key' => 'dt_index_popup_image',
                'input-type' => 'file',
                'class' => 'col-md-3 col-6',
            ],
            [
                'title' => 'تصویر پاپ آپ مخصوص موبایل',
                'key' => 'dt_index_popup_image_mobile',
                'input-type' => 'file',
                'class' => 'col-md-3 col-6',
            ],
            [
                'title' => 'لینک تصویر',
                'key' => 'dt_index_popup_link',
                'input-type' => 'input',
                'type' => 'text',
                'class' => 'col-md-3 col-6',
            ],
            [
                'title' => 'متن پاپ آپ',
                'key' => 'dt_index_popup_text',
                'input-type' => 'inline-editor',
                'class' => 'col-md-7',
            ],
            [
                'input-type' => 'linebreak',
                'html' => '<hr>'
            ],
            [
                'title' => 'نمایش فرم تماس در صفحه تماس باما',
                'key' => 'dt_show_form_in_contact',
                'input-type' => 'select',
                'class' => 'col-md-3 col-6',
                'options' => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
                'attributes' => 'required'
            ],
            [
                'title' => 'نمایش نقشه در صفحه تماس باما',
                'key' => 'dt_show_map_in_contact',
                'input-type' => 'select',
                'class' => 'col-md-3 col-6',
                'options' => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
                'attributes' => 'required'
            ],
            [
                'title' => 'متن توضیحات صفحه تماس باما بالای صفحه',
                'key' => 'dt_show_contact_top_description',
                'input-type' => 'inline-editor',
                'class' => 'col-md-12',
            ],
            [
                'title' => 'نمایش متن توضیحات صفحه تماس باما قسمت پایین',
                'key' => 'dt_show_contact_bottom_description',
                'input-type' => 'select',
                'class' => 'col-md-3 col-6',
                'options' => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
                'attributes' => 'required'
            ],
            [
                'title' => 'متن توضیحات صفحه تماس باما قسمت پایین',
                'key' => 'dt_contact_bottom_description',
                'input-type' => 'inline-editor',
                'class' => 'col-md-12',
            ],
        ],
        'rules' => [
            'dt_show_price_change_chart' => 'required|in:yes,no'
        ]
    ],

    'links' => [
        public_path('themes/SaniarTheme') => base_path('themes/SaniarTheme/src/resources/assets'),
    ],

    'home-widgets' => require __DIR__ . '/../config/HomeWidgets.php',
    'posts-widgets' => require __DIR__ . '/../config/PostsWidgets.php',

    'cache-forget' => [
        'categories' => [
            'front.productcats',
            'front.index.categories',
            'front.category.map'
        ],
        'products' => [
            'front.user_layout.random_products'
        ],
        'posts' => [
            'front.blog.latest_posts'
        ],
        'sliders' => [],
        'banners' => []
    ]
];

<?php

return [


    'fullscreen-slider' => [
        'title' => 'اسلایدر تمام صفحه',
        'image' => 'widgets/fullscreen-slider.jpg',
        'options' => [
            [
                'title' => 'تعداد اسلایدر',
                'key' => 'number',
                'input-type' => 'input',
                'type' => 'number',
                'default' => '5',
                'class' => 'col-md-4 col-6',
                'attributes' => 'required'
            ],
            [
                'title' => 'ترتیب نمایش',
                'key' => 'ordering',
                'input-type' => 'select',
                'class' => 'col-md-4',
                'options' => [
                    [
                        'value' => 'asc',
                        'title' => 'صعودی'
                    ],
                    [
                        'value' => 'desc',
                        'title' => 'نزولی'
                    ]
                ],
            ],
        ],
        'rules' => [
            'number' => 'required',
        ]
    ],

    'main-slider' => [
        'title' => 'اسلایدر اصلی و بنر کناری',
        'image' => 'widgets/slider.jpg',
        'options' => [
            [
                'title'      => 'تعداد اسلایدر',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'default'    => '5',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'required'
            ],
            [
                'title'      => 'جایگاه بنر',
                'key'        => 'banner_position',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'left',
                        'title' => 'سمت چپ'
                    ],
                    [
                        'value' => 'right',
                        'title' => 'سمت راست'
                    ]
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'ترتیب نمایش',
                'key'        => 'ordering',
                'input-type' => 'select',
                'class'      => 'col-md-4',
                'options'    => [
                    [
                        'value' => 'asc',
                        'title' => 'صعودی'
                    ],
                    [
                        'value' => 'desc',
                        'title' => 'نزولی'
                    ]
                ],
            ],
        ],
        'rules' => [
            'number' => 'required',
            'banner_position' => 'required|in:right,left'
        ]
    ],

    'post-categories' => [
        'title' => 'دسته بندی مقالات',
        'image' => 'widgets/categories.png',
        'options' => [

            [
                'title'      => 'عنوان',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'لینک',
                'key'        => 'link',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'عنوان لینک',
                'key'        => 'link_title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'انتخاب دسته بندی ها',
                'key'        => 'post_categories',
                'input-type' => 'post_categories',
                'class'      => 'col-md-12',
            ],

        ],
        'rules' => [
            'post_categories'      => 'required|array',
            'post_categories.*'    => 'exists:categories,id',
        ]
    ],

    'posts-default-block' => [
        'title' => 'کادر مقالات ساده',
        'image' => 'widgets/products-default.jpg',
        'options' => [
            [
                'title'      => 'عنوان',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'نوع مقاله',
                'key'        => 'posts_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'is_editor_pick',
                        'title' => 'انتخاب سردبیر'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'نوع مرتب سازی',
                'key'        => 'sort_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'latest',
                        'title' => 'جدیدترین'
                    ],
                    [
                        'value' => 'oldest',
                        'title' => 'قدیمی ترین'
                    ],
                    [
                        'value' => 'view',
                        'title' => 'پربازدید ترین'
                    ],
                    [
                        'value' => 'random',
                        'title' => 'تصادفی'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'لینک',
                'key'        => 'link',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'عنوان لینک',
                'key'        => 'link_title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'تعداد',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'class'      => 'col-md-4 col-6',
                'default'    => '10',
                'attributes' => 'required'
            ],
            [
                'title'      => 'انتخاب دسته بندی ها (اختیاری)',
                'key'        => 'post_categories',
                'input-type' => 'post_categories',
                'class'      => 'col-md-9',
            ],
            [
                'title'      => 'شامل مقالات زیر دسته ها',
                'key'        => 'sub_category_post',
                'input-type' => 'select',
                'class'      => 'col-md-3',
                'options'    => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
            ],
        ],
        'rules' => [
            'posts_type'    => 'required|in:all,is_editor_pick',
            'sort_type'        => 'required|in:latest,oldest,view,random',
            'link'             => 'nullable|string',
            'link_title'       => 'nullable|string',
            'number'           => 'required',
            'post_categories'       => 'nullable|array',
            'post_categories.*'     => 'exists:categories,id',
        ]
    ],
    'posts-three-box-block' => [
        'title' => 'کادر مقالات 3 باکس',
        'image' => 'widgets/products-default.jpg',
        'options' => [
            [
                'title'      => 'عنوان',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'نوع مقاله',
                'key'        => 'posts_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'is_editor_pick',
                        'title' => 'انتخاب سردبیر'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'نوع مرتب سازی',
                'key'        => 'sort_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'latest',
                        'title' => 'جدیدترین'
                    ],
                    [
                        'value' => 'oldest',
                        'title' => 'قدیمی ترین'
                    ],
                    [
                        'value' => 'view',
                        'title' => 'پربازدید ترین'
                    ],
                    [
                        'value' => 'random',
                        'title' => 'تصادفی'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'لینک',
                'key'        => 'link',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'عنوان لینک',
                'key'        => 'link_title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'انتخاب دسته بندی ها (اختیاری)',
                'key'        => 'post_categories',
                'input-type' => 'post_categories',
                'class'      => 'col-md-9',
            ],
            [
                'title'      => 'شامل مقالات زیر دسته ها',
                'key'        => 'sub_category_post',
                'input-type' => 'select',
                'class'      => 'col-md-3',
                'options'    => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
            ],
        ],
        'rules' => [
            'posts_type'    => 'required|in:all,is_editor_pick',
            'sort_type'        => 'required|in:latest,oldest,view,random',
            'link'             => 'nullable|string',
            'link_title'       => 'nullable|string',
            'post_categories'       => 'nullable|array',
            'post_categories.*'     => 'exists:categories,id',
        ]
    ],
    'posts-big-box-block' => [
        'title' => 'کادر مقالات باکس اول بزرگ',
        'image' => 'widgets/products-default.jpg',
        'options' => [
            [
                'title'      => 'عنوان',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'نوع مقاله',
                'key'        => 'posts_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'is_editor_pick',
                        'title' => 'انتخاب سردبیر'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'نوع مرتب سازی',
                'key'        => 'sort_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'latest',
                        'title' => 'جدیدترین'
                    ],
                    [
                        'value' => 'oldest',
                        'title' => 'قدیمی ترین'
                    ],
                    [
                        'value' => 'view',
                        'title' => 'پربازدید ترین'
                    ],
                    [
                        'value' => 'random',
                        'title' => 'تصادفی'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'لینک',
                'key'        => 'link',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'عنوان لینک',
                'key'        => 'link_title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'انتخاب دسته بندی ها (اختیاری)',
                'key'        => 'post_categories',
                'input-type' => 'post_categories',
                'class'      => 'col-md-9',
            ],
            [
                'title'      => 'شامل مقالات زیر دسته ها',
                'key'        => 'sub_category_post',
                'input-type' => 'select',
                'class'      => 'col-md-3',
                'options'    => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
            ],
        ],
        'rules' => [
            'posts_type'    => 'required|in:all,is_editor_pick',
            'sort_type'        => 'required|in:latest,oldest,view,random',
            'link'             => 'nullable|string',
            'link_title'       => 'nullable|string',
            'post_categories'       => 'nullable|array',
            'post_categories.*'     => 'exists:categories,id',
        ]
    ],

    'posts-tags' => [
        'title' => 'کلمات کلیدی',
        'image' => '',
        'options' => [
            [
                'title'      => 'عنوان',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'تعداد قابل نمایش',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'default'    => '1',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'required'
            ],
            [
                'title'      => 'نوع مرتب سازی',
                'key'        => 'sort_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'most_used',
                        'title' => 'پراستفاده ترین'
                    ],
                    [
                        'value' => 'latest',
                        'title' => 'جدیدترین'
                    ],
                    [
                        'value' => 'oldest',
                        'title' => 'قدیمی ترین'
                    ],
                    [
                        'value' => 'view',
                        'title' => 'پربازدید ترین'
                    ],
                    [
                        'value' => 'random',
                        'title' => 'تصادفی'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'ترتیب نمایش',
                'key'        => 'ordering',
                'input-type' => 'select',
                'class'      => 'col-md-4',
                'options'    => [
                    [
                        'value' => 'asc',
                        'title' => 'صعودی'
                    ],
                    [
                        'value' => 'desc',
                        'title' => 'نزولی'
                    ]
                ],
            ],
        ],
        'rules' => [
            'number' => 'required',
        ]
    ],

    'middle-banners' => [
        'title' => 'بنر تکی',
        'image' => 'widgets/banner.png',
        'options' => [
            [
                'title'      => 'تعداد قابل نمایش',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'default'    => '1',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'required'
            ],
            [
                'title'      => 'ترتیب نمایش',
                'key'        => 'ordering',
                'input-type' => 'select',
                'class'      => 'col-md-4',
                'options'    => [
                    [
                        'value' => 'asc',
                        'title' => 'صعودی'
                    ],
                    [
                        'value' => 'desc',
                        'title' => 'نزولی'
                    ]
                ],
            ],
            [
                'title'      => 'گروه',
                'key'        => 'place',
                'input-type' => 'select',
                'class'      => 'col-md-4',
                'options'    => [
                    [
                        'value' => 'index_banners_place1',
                        'title' => 'گروه اول'
                    ],
                    [
                        'value' => 'index_banners_place2',
                        'title' => 'گروه دوم'
                    ],
                    [
                        'value' => 'index_banners_place3',
                        'title' => 'گروه سوم'
                    ]
                ],

            ],
        ],
        'rules' => [
            'number' => 'required',
        ]
    ],

    'middle-banners-2' => [
        'title' => 'بنر دوتایی',
        'image' => 'widgets/banner-2.jpg',
        'options' => [
            [
                'title'      => 'تعداد قابل نمایش',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'default'    => '2',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'required'
            ],
            [
                'title'      => 'ترتیب نمایش',
                'key'        => 'ordering',
                'input-type' => 'select',
                'class'      => 'col-md-4',
                'options'    => [
                    [
                        'value' => 'asc',
                        'title' => 'صعودی'
                    ],
                    [
                        'value' => 'desc',
                        'title' => 'نزولی'
                    ]
                ],
            ],
            [
                'title'      => 'گروه',
                'key'        => 'place',
                'input-type' => 'select',
                'class'      => 'col-md-4',
                'options'    => [
                    [
                        'value' => 'index_banners_place1',
                        'title' => 'گروه اول'
                    ],
                    [
                        'value' => 'index_banners_place2',
                        'title' => 'گروه دوم'
                    ],
                    [
                        'value' => 'index_banners_place3',
                        'title' => 'گروه سوم'
                    ]
                ],

            ],
        ],
        'rules' => [
            'number' => 'required',
        ]
    ],

    'middle-banners-4' => [
        'title' => 'بنر چهارتایی',
        'image' => 'widgets/banner-4.png',
        'options' => [
            [
                'title'      => 'تعداد قابل نمایش',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'default'    => '4',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'required'
            ],
            [
                'title'      => 'ترتیب نمایش',
                'key'        => 'ordering',
                'input-type' => 'select',
                'class'      => 'col-md-4',
                'options'    => [
                    [
                        'value' => 'asc',
                        'title' => 'صعودی'
                    ],
                    [
                        'value' => 'desc',
                        'title' => 'نزولی'
                    ]
                ],
            ],
            [
                'title'      => 'گروه',
                'key'        => 'place',
                'input-type' => 'select',
                'class'      => 'col-md-4',
                'options'    => [
                    [
                        'value' => 'index_banners_place1',
                        'title' => 'گروه اول'
                    ],
                    [
                        'value' => 'index_banners_place2',
                        'title' => 'گروه دوم'
                    ],
                    [
                        'value' => 'index_banners_place3',
                        'title' => 'گروه سوم'
                    ]
                ],

            ],

        ],
        'rules' => [
            'number' => 'required',
        ]
    ],

    'products-moment-block' => [
        'title' => 'کادر محصولات با پیشنهاد لحظه ای',
        'image' => 'widgets/products-moment.png',
        'options' => [
            [
                'title'      => 'عنوان',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'نوع محصولات',
                'key'        => 'products_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'discount',
                        'title' => 'تخفیف خورده'
                    ],
                    [
                        'value' => 'special',
                        'title' => 'پیشنهاد ویژه'
                    ],
                    [
                        'value' => 'moment',
                        'title' => 'پیشنهاد لحظه ای'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'وضعیت موجودی',
                'key'        => 'inventory_status',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'available',
                        'title' => 'موجود'
                    ],
                    [
                        'value' => 'unavailable',
                        'title' => 'نا موجود'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'نوع مرتب سازی',
                'key'        => 'sort_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'latest',
                        'title' => 'جدیدترین'
                    ],
                    [
                        'value' => 'sell',
                        'title' => 'پرفروش ترین'
                    ],
                    [
                        'value' => 'view',
                        'title' => 'پربازدید ترین'
                    ],
                    [
                        'value' => 'cheapest',
                        'title' => 'ارزانترین'
                    ],
                    [
                        'value' => 'expensivest',
                        'title' => 'گرانترین'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'لینک',
                'key'        => 'link',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'عنوان لینک',
                'key'        => 'link_title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'نمایش محصولات موجود در اول',
                'key'        => 'order_by_stock',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
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
                'title'      => 'تعداد',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'class'      => 'col-md-4 col-6',
                'default'    => '10',
                'attributes' => 'required'
            ],
            [
                'title'      => 'انتخاب دسته بندی ها (اختیاری)',
                'key'        => 'categories',
                'input-type' => 'product_categories',
                'class'      => 'col-md-9',
            ],
            [
                'title'      => 'شامل محصولات زیر دسته ها',
                'key'        => 'sub_category_products',
                'input-type' => 'select',
                'class'      => 'col-md-3',
                'options'    => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
            ],
        ],
        'rules' => [
            'products_type'    => 'required|in:all,discount,special,moment',
            'inventory_status' => 'required|in:all,available,unavailable',
            'sort_type'        => 'required|in:latest,sell,view,cheapest,expensivest',
            'order_by_stock'   => 'required|in:yes,no',
            'link'             => 'nullable|string',
            'link_title'       => 'nullable|string',
            'number'           => 'required',
            'categories'       => 'nullable|array',
            'categories.*'     => 'exists:categories,id',
        ]
    ],

    'products-default-block' => [
        'title' => 'کادر محصولات ساده',
        'image' => 'widgets/products-default.jpg',
        'options' => [
            [
                'title'      => 'عنوان',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'نوع محصولات',
                'key'        => 'products_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'discount',
                        'title' => 'تخفیف خورده'
                    ],
                    [
                        'value' => 'special',
                        'title' => 'پیشنهاد ویژه'
                    ],
                    [
                        'value' => 'moment',
                        'title' => 'پیشنهاد لحظه ای'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'وضعیت موجودی',
                'key'        => 'inventory_status',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'available',
                        'title' => 'موجود'
                    ],
                    [
                        'value' => 'unavailable',
                        'title' => 'نا موجود'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'نوع مرتب سازی',
                'key'        => 'sort_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'latest',
                        'title' => 'جدیدترین'
                    ],
                    [
                        'value' => 'sell',
                        'title' => 'پرفروش ترین'
                    ],
                    [
                        'value' => 'view',
                        'title' => 'پربازدید ترین'
                    ],
                    [
                        'value' => 'cheapest',
                        'title' => 'ارزانترین'
                    ],
                    [
                        'value' => 'expensivest',
                        'title' => 'گرانترین'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'لینک',
                'key'        => 'link',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'عنوان لینک',
                'key'        => 'link_title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'نمایش محصولات موجود در اول',
                'key'        => 'order_by_stock',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
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
                'title'      => 'تعداد',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'class'      => 'col-md-4 col-6',
                'default'    => '10',
                'attributes' => 'required'
            ],
            [
                'title'      => 'انتخاب دسته بندی ها (اختیاری)',
                'key'        => 'categories',
                'input-type' => 'product_categories',
                'class'      => 'col-md-9',
            ],
            [
                'title'      => 'شامل محصولات زیر دسته ها',
                'key'        => 'sub_category_products',
                'input-type' => 'select',
                'class'      => 'col-md-3',
                'options'    => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
            ],
        ],
        'rules' => [
            'products_type'    => 'required|in:all,discount,special,moment',
            'inventory_status' => 'required|in:all,available,unavailable',
            'sort_type'        => 'required|in:latest,sell,view,cheapest,expensivest',
            'order_by_stock'   => 'required|in:yes,no',
            'link'             => 'nullable|string',
            'link_title'       => 'nullable|string',
            'number'           => 'required',
            'categories'       => 'nullable|array',
            'categories.*'     => 'exists:categories,id',
        ]
    ],

    'products-colorful-block' => [
        'title' => 'کادر محصولات با پس زمینه',
        'image' => 'widgets/special.jpg',
        'options' => [
            [
                'title'      => 'نوع محصولات',
                'key'        => 'products_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'discount',
                        'title' => 'تخفیف خورده'
                    ],
                    [
                        'value' => 'special',
                        'title' => 'پیشنهاد ویژه'
                    ],
                    [
                        'value' => 'moment',
                        'title' => 'پیشنهاد لحظه ای'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'وضعیت موجودی',
                'key'        => 'inventory_status',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'all',
                        'title' => 'همه'
                    ],
                    [
                        'value' => 'available',
                        'title' => 'موجود'
                    ],
                    [
                        'value' => 'unavailable',
                        'title' => 'نا موجود'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'نوع مرتب سازی',
                'key'        => 'sort_type',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
                    [
                        'value' => 'latest',
                        'title' => 'جدیدترین'
                    ],
                    [
                        'value' => 'sell',
                        'title' => 'پرفروش ترین'
                    ],
                    [
                        'value' => 'view',
                        'title' => 'پربازدید ترین'
                    ],
                    [
                        'value' => 'cheapest',
                        'title' => 'ارزانترین'
                    ],
                    [
                        'value' => 'expensivest',
                        'title' => 'گرانترین'
                    ],
                ],
                'attributes' => 'required'
            ],
            [
                'title'      => 'نمایش محصولات موجود در اول',
                'key'        => 'order_by_stock',
                'input-type' => 'select',
                'class'      => 'col-md-4 col-6',
                'options'    => [
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
                'title'      => 'لینک',
                'key'        => 'link',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'عنوان لینک',
                'key'        => 'link_title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'رنگ پس زمینه کادر',
                'key'        => 'block_color',
                'input-type' => 'input',
                'default'    => '#ef394e',
                'type'       => 'color',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'تصویر',
                'key'        => 'image',
                'input-type' => 'file',
                'type'       => 'file',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'accept="image/*"',
                'help'       => 'بهترین اندازه 850 * 500'
            ],
            [
                'title'      => 'تعداد',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'class'      => 'col-md-4 col-6',
                'default'    => '10',
                'attributes' => 'required'
            ],
            [
                'title'      => 'انتخاب دسته بندی ها (اختیاری)',
                'key'        => 'categories',
                'input-type' => 'product_categories',
                'class'      => 'col-md-9',
            ],
            [
                'title'      => 'شامل محصولات زیر دسته ها',
                'key'        => 'sub_category_products',
                'input-type' => 'select',
                'class'      => 'col-md-3',
                'options'    => [
                    [
                        'value' => 'yes',
                        'title' => 'بله'
                    ],
                    [
                        'value' => 'no',
                        'title' => 'خیر'
                    ]
                ],
            ],
        ],
        'rules' => [
            'products_type'    => 'required|in:all,discount,special,moment',
            'inventory_status' => 'required|in:all,available,unavailable',
            'sort_type'        => 'required|in:latest,sell,view,cheapest,expensivest',
            'order_by_stock'   => 'required|in:yes,no',
            'link'             => 'nullable|string',
            'link_title'       => 'nullable|string',
            'block_color'      => 'nullable|string',
            'number'           => 'required',
            'image'            => 'nullable|image',
        ]
    ],


    'coworker-sliders' => [
        'title' => 'اسلایدر لوگو همکاران',
        'image' => 'widgets/customer.jpg',
        'options' => [
            [
                'title'      => 'عنوان ',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'تعداد قابل نمایش',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'default'    => '10',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'required'
            ] ,


        ],
        'rules' => [
            'number' => 'required',
        ]
    ],

    'sevices-sliders' => [
        'title' => 'اسلایدر خدمات',
        'image' => 'widgets/support.jpg',
        'options' => [
            [
                'title'      => 'تعداد قابل نمایش',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'default'    => '4',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'required'
            ]

        ],
        'rules' => [
            'number' => 'required',
        ]
    ],
    'faqs' => [
        'title' => 'سوالات متداول',
        'image' => 'widgets/faqs.png',
        'options' => [
            [
                'title'      => 'عنوان ',
                'key'        => 'title',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'توضیحات',
                'key'        => 'text',
                'input-type' => 'input',
                'type'       => 'text',
                'class'      => 'col-md-8 col-6',
            ],
            [
                'title'      => 'تعداد قابل نمایش',
                'key'        => 'number',
                'input-type' => 'input',
                'type'       => 'number',
                'default'    => '10',
                'class'      => 'col-md-4 col-6',
                'attributes' => 'required'
            ],
            [
                'title'      => 'رنگ پس زمینه کادر',
                'key'        => 'block_color',
                'input-type' => 'input',
                'default'    => '#4f46e5',
                'type'       => 'color',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'رنگ متن زمینه کادر',
                'key'        => 'text_color',
                'input-type' => 'input',
                'default'    => '#ffffff',
                'type'       => 'color',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'عرض ویجت',
                'key'        => 'width',
                'input-type' => 'select',
                'type'       => 'select',
                'options'    => [
                    [
                        'value' => '50%',
                        'title' => '۵۰ درصد'
                    ],
                    [
                        'value' => '70%',
                        'title' => '۷۰ درصد'
                    ],
                    [
                        'value' => '80%',
                        'title' => '۸۰ درصد'
                    ],
                    [
                        'value' => '90%',
                        'title' => '۹۰ درصد'
                    ],
                    [
                        'value' => '100%',
                        'title' => '۱۰۰ درصد'
                    ],

                ],
                'default'    => '90%',
                'class'      => 'col-md-4 col-6',
            ],
            [
                'title'      => 'نوع چیدمان',
                'key'        => 'layout',
                'input-type' => 'select',
                'type'       => 'select',
                'options'    => [
                    [
                        'value' => 'top',
                        'title' => 'هدر در بالا (عمودی)'
                    ],
                    [
                        'value' => 'side',
                        'title' => 'هدر در راست (افقی)'
                    ],
                ],
                'default'    => 'top',
                'class'      => 'col-md-4 col-6',
            ],
        ],
        'rules' => [
            'number' => 'required',
        ]
    ],

    /*'categories' => [
        'title' => 'دسته بندی محصولات',
        'image' => 'widgets/categories.png',
        'options' => [
            [
                'title'      => 'انتخاب دسته بندی ها',
                'key'        => 'categories',
                'input-type' => 'categories',
                'class'      => 'col-md-12',
            ],

        ],
        'rules' => [
            'categories'      => 'required|array',
            'categories.*'    => 'exists:categories,id',
        ]
    ],*/

];

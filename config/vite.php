<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Vite Configuration — پنل مدیریت جدید
|--------------------------------------------------------------------------
| خروجی build در public/admin-panel قرار می‌گیرد تا با خروجی Mix پنل قدیمی
| تداخلی نداشته باشد. فایل vite.admin.config.mjs مسئول build است.
*/

return [

    'build_path' => 'admin-panel',

    'implicit_tailwind_id_attribute' => false,

    'script_loading_strategy' => 'module',

    'preloading' => [
        'preload_tag' => 'link',
        'resolve_dependencies' => true,
    ],

    'dev_server' => [
        'url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),
        'ping_timeout' => 500,
        'key' => env('VITE_DEV_SERVER_KEY'),
        'cert' => env('VITE_DEV_SERVER_CERT'),
    ],
];

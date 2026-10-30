<?php

/*
|--------------------------------------------------------------------------
| رلهٔ سراسری هوش مصنوعی (AI Relay) — فاز ۱۲۴
|--------------------------------------------------------------------------
|
| یک فایل relay.php چند-سرویسی که روی سرور «خارج از ایران» آپلود می‌شود تا
| ترافیک هاست‌های داخل ایران (ارور 401/403 مسدودی جغرافیایی) عبور کند.
| مدیریت: پنل ادمین ← تنظیمات توسعه‌دهنده ← تب «رلهٔ هوش مصنوعی».
|
| مقادیر ذخیره‌شده در جدول options:
|   ai_relay_access_key  — کلید دسترسی رله (RELAY_ACCESS_KEY)
|   ai_relay_domain      — دامنهٔ نصب رله (برای نمایش آدرس‌های آماده)
|   ai_relay_providers   — JSON سرویس‌ها [{key,base_url,api_key,headers,enabled}]
|   ai_relay_allowed_ips — IPهای مجاز
|   ai_relay_headers     — هدرهای عمومی (هر خط Name: value)
|
*/

return [

    'providers' => [
        // key => [title فارسی, base پیش‌فرض, نوع احراز]
        'openai' => [
            'title' => 'OpenAI',
            'base'  => 'https://api.openai.com/v1',
            'auth'  => 'bearer',
        ],
        'openrouter' => [
            'title' => 'OpenRouter',
            'base'  => 'https://openrouter.ai/api/v1',
            'auth'  => 'bearer',
        ],
        'deepseek' => [
            'title' => 'DeepSeek',
            'base'  => 'https://api.deepseek.com/v1',
            'auth'  => 'bearer',
        ],
        'anthropic' => [
            'title' => 'Claude (Anthropic)',
            'base'  => 'https://api.anthropic.com/v1',
            'auth'  => 'x-api-key',
        ],
        'gemini' => [
            'title' => 'Gemini (Google)',
            'base'  => 'https://generativelanguage.googleapis.com/v1beta',
            'auth'  => 'query',
        ],
        'mistral' => [
            'title' => 'Mistral',
            'base'  => 'https://api.mistral.ai/v1',
            'auth'  => 'bearer',
        ],
        'groq' => [
            'title' => 'Groq',
            'base'  => 'https://api.groq.com/openai/v1',
            'auth'  => 'bearer',
        ],
        'cohere' => [
            'title' => 'Cohere',
            'base'  => 'https://api.cohere.com/compatibility/v1',
            'auth'  => 'bearer',
        ],
        'ollama' => [
            'title' => 'Ollama (سرور خودتان)',
            'base'  => 'http://127.0.0.1:11434/v1',
            'auth'  => 'bearer',
        ],
        'custom' => [
            'title' => 'سرویس سفارشی (هر گیت‌وی سازگار OpenAI)',
            'base'  => '',
            'auth'  => 'bearer',
        ],
    ],

    /*
    | الگوهای آدرس استفاده در فروشگاه — {domain} دامنهٔ رله است.
    | نسخهٔ مسیر (v1/v1beta) صرفاً خواناست؛ رله فقط پسوند نهایی را فوروارد می‌کند.
    */
    'shop_base_paths' => [
        'default'  => '/relay.php/{provider}/v1',
        'gemini'   => '/relay.php/{provider}/v1beta',
    ],
];

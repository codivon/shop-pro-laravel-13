<?php

/**
 * ─────────────────────────────────────────────────────────────────────────────
 *  رلهٔ سراسری هوش مصنوعی (AI Relay) — تک‌فایل، چند-سرویسی، بدون وابستگی
 *  نسخه ۲.۱ — همهٔ سرویس‌ها با یک فایل (فاز ۱۲۴) + تولید تصویر (فاز ۱۲۷)
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *  چرا؟  OpenAI، Anthropic و Google درخواست‌های IPهای ایران را مسدود می‌کنند
 *        (حتی با کلید معتبر ۴۰۱/۴۰۳ می‌دهند). این فایل را روی یک سرور «خارج از
 *        ایران» بگذارید؛ چندین سرویس AI (OpenAI، Claude، Gemini، DeepSeek و ...)
 *        با «همین یک فایل» رله می‌شوند — یک بار تنظیم، استفادهٔ همهٔ پکیج‌ها.
 *
 *  مسیریابی بر اساس سرویس در آدرس:
 *        https://دامنه-رله.ir/relay.php/{provider}/v1/chat/completions
 *        {provider} = کلید سرویس در RELAY_PROVIDERS (مثل openai، anthropic، ...)
 *
 *  نقطه‌های پایانی مجاز (allowlist):
 *        …/chat/completions                     (OpenAI-سازگار)
 *        …/completions    …/embeddings    …/responses
 *        …/models        (GET لیست / GET تک مدل)
 *        …/messages                             (Claude / Anthropic)
 *        …/images/generations                   (تولید تصویر — dall-e-3 / gpt-image-1)
 *        …/images/edits                         (ویرایش تصویر)
 *        …/models/{model}:generateContent       (Gemini)
 *        …/models/{model}:streamGenerateContent (Gemini استریم)
 *
 *  احراز کلاینت (فروشگاه) — یکی از این چهار حالت کافی است:
 *        Authorization: Bearer {RELAY_ACCESS_KEY}     (درایور OpenAI-سازگار)
 *        x-api-key: {RELAY_ACCESS_KEY}                (درایور Claude)
 *        ?key={RELAY_ACCESS_KEY}                      (درایور Gemini)
 *        X-Relay-Key: {RELAY_ACCESS_KEY}              (هدر عمومی)
 *  سپس رله، احراز را با کلید واقعی سرویس مقصد جایگزین می‌کند — کلید واقعی
 *  فقط روی رله می‌ماند و از فروشگاه پنهان است.
 *
 *  نصب:  ۱) این فایل را در پوشهٔ public (یا www) هاست خارجی آپلود کنید.
 *        ۲) اگر پنل فروشگاه فایل را ساخته، همهٔ مقادیر از قبل پر شده‌اند.
 *        ۳) در فروشگاه، آدرس هر سرویس را به شکل زیر بدهید:
 *             Base URL:  https://دامنه-رله.ir/relay.php/{provider}/v1
 *             API Key:   همان RELAY_ACCESS_KEY
 *
 *  امنیت: HTTPS اجباری (به‌جز localhost) · allowlist مسیرها · IPهای مجاز
 *         · هیچ لاگی از بدنهٔ پیام‌ها نوشته نمی‌شود · خطاهای PHP هرگز به‌صورت
 *         HTML/مسیر سرور نمایش داده نمی‌شوند (پاسخ همیشه JSON است) · کلیدهای
 *         واقعی سرویس‌ها به‌صورت رمزگذاری‌شده (enc) داخل فایل ذخیره می‌شوند تا
 *         در صورت نشست احتمالی سورس، کلید قابل استفاده نباشد.
 *  سازگاری: PHP 7.4+ — بدون composer، بدون افزونهٔ خاص (cURL استاندارد).
 * ─────────────────────────────────────────────────────────────────────────────
 */

/* ═══════════════════════════  پیکربندی  ═══════════════════════════ */

// سالت رمزگشایی کلیدها — تصادفی و مخصوص همین فایل (از پنل پر می‌شود)
define('RELAY_SALT', getenv('RELAY_SALT') ?: 'CHANGE-ME-RANDOM-SALT');

// کلید دسترسی رله — حتماً به یک رشتهٔ بلند و تصادفی تغییر دهید!
define('RELAY_ACCESS_KEY', getenv('RELAY_ACCESS_KEY') ?: 'CHANGE-ME-TO-A-LONG-RANDOM-STRING');

// سرویس‌های مقصد — کلید هر سرویس در آدرس رله استفاده می‌شود.
//   auth: 'bearer' (OpenAI-سازگار) | 'x-api-key' (Claude) | 'query' (Gemini ?key=)
//   key خالی = احراز کلاینت عیناً به مقصد فوروارد می‌شود (پاس‌ترو).
// مثال:
// $RELAY_PROVIDERS = [
//     'openai'  => ['base' => 'https://api.openai.com/v1', 'key' => 'sk-...', 'auth' => 'bearer', 'headers' => []],
//     'anthropic' => ['base' => 'https://api.anthropic.com/v1', 'key' => 'sk-ant-...', 'auth' => 'x-api-key', 'headers' => []],
//     'gemini'  => ['base' => 'https://generativelanguage.googleapis.com/v1beta', 'key' => 'AIza...', 'auth' => 'query', 'headers' => []],
// ];
$RELAY_PROVIDERS = [];

// (اختیاری) فقط این IPها حق استفاده از رله را دارند — مثلاً IP هاست فروشگاه.
define('ALLOWED_CLIENT_IPS', []);

// (اختیاری) هدرهای اضافی برای همهٔ سرویس‌ها — مثلاً برای OpenRouter:
// define('RELAY_GLOBAL_HEADERS', ['HTTP-Referer' => 'https://your-shop.ir']);
define('RELAY_GLOBAL_HEADERS', []);

// تایم‌اوت‌ها و سقف اندازهٔ بدنهٔ درخواست
define('RELAY_CONNECT_TIMEOUT', 20);
define('RELAY_TIMEOUT', 300);
define('RELAY_MAX_BODY_BYTES', 8 * 1024 * 1024); // 8MB

/* ═══════════════════════════  اجرا  ═══════════════════════════ */

// ── گاردهای نمایش خطا: هیچ‌گاه HTML/مسیر سرور به بیرون درز نمی‌کند ──
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED);

if (! headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
}

// notice/warning/deprecated → کاملاً خاموش؛ خطاهای واقعی → پاسخ JSON بدون جزئیات
set_error_handler(static function (int $no, string $str, string $file = '', int $line = 0): bool {
    $ignorable = [E_WARNING, E_CORE_WARNING, E_USER_WARNING, E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED];

    if (in_array($no, $ignorable, true)) {
        return true; // هیچی چاپ نشود
    }

    relay_fail(500, 'خطای داخلی رله — فایل را از پنل فروشگاه دوباره بسازید.');

    return true;
});

set_exception_handler(static function (Throwable $e): void {
    relay_fail(500, 'خطای داخلی رله — فایل را از پنل فروشگاه دوباره بسازید.');
});

// خطای مهلک (fatal) → JSON تمیز، بدون HTML و بدون مسیر فایل
register_shutdown_function(static function (): void {
    $last = error_get_last();

    if (is_array($last) && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (! headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            header('X-AI-Relay-Version: 2.1');
        }

        echo json_encode([
            'error' => [
                'message' => 'خطای مهلک رله — فایل را از پنل فروشگاه دوباره بسازید.',
                'type'    => 'relay_fatal',
                'code'    => 500,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }
});

relay_main();

/**
 * رمزگشایی مقادیر «enc:…» (XOR + Base64 روی RELAY_SALT) — کلیدهای واقعی
 * هرگز به‌صورت خوانا در سورس نمی‌مانند؛ اگر سالت دست‌کاری شود خالی برمی‌گردد.
 */
function relay_decode(string $value): string
{
    if ($value === '' || strncmp($value, 'enc:', 4) !== 0) {
        return $value;
    }

    $raw = base64_decode(substr($value, 4), true);

    if ($raw === false || $raw === '') {
        return '';
    }

    $salt = (string) RELAY_SALT;

    if ($salt === '' || $salt === 'CHANGE-ME-RANDOM-SALT') {
        return '';
    }

    $out = '';
    $len = strlen($raw);
    $sln = strlen($salt);

    for ($i = 0; $i < $len; $i++) {
        $out .= chr(ord($raw[$i]) ^ ord($salt[$i % $sln]));
    }

    return $out;
}

function relay_fail(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-AI-Relay-Version: 2.1');
    echo json_encode([
        'error' => [
            'message' => $message,
            'type'    => 'relay_error',
            'code'    => $status,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/** مقصد مجاز را از انتهای مسیر استخراج می‌کند (allowlist) */
function relay_forward_path(string $rest): ?string
{
    $patterns = [
        '#/chat/completions$#',
        '#/completions$#',
        '#/embeddings$#',
        '#/responses$#',
        '#/messages$#',
        '#/images/generations$#',   // تولید تصویر (OpenAI Images API — dall-e-3 / gpt-image-1)
        '#/images/edits$#',         // ویرایش تصویر
        '#/models/[^/]+:streamGenerateContent$#',
        '#/models/[^/]+:generateContent$#',
        '#/models/[^/:]+$#',
        '#/models$#',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $rest) === 1) {
            preg_match($pattern, $rest, $m);

            return $m[0];
        }
    }

    return null;
}

function relay_main(): void
{
    global $RELAY_PROVIDERS;

    // دفاعی — اگر از بیرون دست‌کاری شده باشد، همیشه آرایهٔ معتبر است
    $RELAY_PROVIDERS = is_array($RELAY_PROVIDERS) ? $RELAY_PROVIDERS : [];

    // ── ۱) HTTPS (به‌جز localhost برای تست لوکال) ──
    $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if (! $isLocal && empty($_SERVER['HTTPS']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https') {
        relay_fail(426, 'رله فقط با HTTPS قابل استفاده است (Upgrade Required).');
    }

    $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = parse_url($uri, PHP_URL_PATH) ?: '';
    $path = '/'.trim($path, '/');

    // ── ۲) مسیر سلامت — بدون احراز (برای تست نصب فایل) ──
    if ($path === '/health' || $path === '/relay.php/health') {
        header('Content-Type: application/json; charset=utf-8');
        header('X-AI-Relay-Version: 2.1');
        echo json_encode([
            'ok'        => true,
            'relay'     => 'ai-relay',
            'version'   => '2.1',
            'providers' => array_keys($RELAY_PROVIDERS),
            'secured'   => RELAY_ACCESS_KEY !== 'CHANGE-ME-TO-A-LONG-RANDOM-STRING',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── ۳) گارد IP سفارشی ──
    if (ALLOWED_CLIENT_IPS !== []) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (! in_array($ip, ALLOWED_CLIENT_IPS, true)) {
            relay_fail(403, 'IP شما اجازهٔ استفاده از این رله را ندارد.');
        }
    }

    // ── ۴) تشخیص سرویس مقصد از مسیر (segment اول) ──
    $segments = array_values(array_filter(explode('/', trim($path, '/')), fn (string $s) => $s !== ''));

    // نام اسکریپت از ابتدای مسیر حذف می‌شود — /relay.php/{provider}/… یا /{provider}/…
    if (isset($segments[0]) && preg_match('/\.php$/i', $segments[0]) === 1) {
        array_shift($segments);
    }

    $providerKey = (string) ($segments[0] ?? '');
    $rest = '/'.implode('/', array_slice($segments, 1));

    if ($providerKey === '' || ! isset($RELAY_PROVIDERS[$providerKey])) {
        // سازگاری با فایل‌های تک-سرویس قدیمی: اگر فقط یک سرویس تعریف شده باشد
        if (count($RELAY_PROVIDERS) === 1) {
            $providerKey = (string) array_key_first($RELAY_PROVIDERS);
            $rest = '/'.implode('/', $segments);
        } else {
            relay_fail(404, 'سرویس مقصد در مسیر مشخص نیست — آدرس باید به شکل /relay.php/{provider}/... باشد. سرویس‌های فعال رله: '
                .($RELAY_PROVIDERS === [] ? 'هیچ' : implode(', ', array_keys($RELAY_PROVIDERS))));
        }
    }

    $provider = (array) $RELAY_PROVIDERS[$providerKey];
    $upstreamBase = rtrim((string) ($provider['base'] ?? ''), '/');
    $upstreamKey  = (string) ($provider['key'] ?? '');
    $authMode     = (string) ($provider['auth'] ?? 'bearer');

    if ($upstreamBase === '') {
        relay_fail(500, "سرویس «{$providerKey}» روی رله پیکربندی نشده است (آدرس پایهٔ مقصد خالی است).");
    }

    // ── ۵) مسیر مجاز (allowlist) ──
    $forwardPath = relay_forward_path($rest);

    if ($forwardPath === null) {
        relay_fail(404, 'مسیر رله پشتیبانی نمی‌شود — مجاز: chat/completions، completions، embeddings، responses، messages، images/generations، models و models/{model}:generateContent.');
    }

    // ── ۶) احراز کلاینت (فروشگاه) — چهار حالت پذیرفته می‌شود ──
    $clientAuth = [
        'header'    => null, // X-Relay-Key
        'bearer'    => null, // مقدار بعد از «Bearer »
        'x-api-key' => null,
        'query'     => null, // ?key=
    ];

    $authHeader = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));

    if (stripos($authHeader, 'bearer ') === 0) {
        $clientAuth['bearer'] = trim(substr($authHeader, 7));
    }

    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $clientAuth['x-api-key'] = (string) $_SERVER['HTTP_X_API_KEY'];
    }

    if (isset($_GET['key'])) {
        $clientAuth['query'] = (string) $_GET['key'];
    }

    if (isset($_SERVER['HTTP_X_RELAY_KEY'])) {
        $clientAuth['header'] = (string) $_SERVER['HTTP_X_RELAY_KEY'];
    }

    $givenKey = $clientAuth['header']
        ?? $clientAuth['bearer']
        ?? $clientAuth['x-api-key']
        ?? $clientAuth['query']
        ?? '';

    if (! hash_equals(RELAY_ACCESS_KEY, (string) $givenKey)) {
        header('WWW-Authenticate: Bearer realm="ai-relay"');
        relay_fail(401, 'کلید رله نامعتبر است — در فیلد «کلید API» سرویس فروشگاه، همان RELAY_ACCESS_KEY رله را وارد کنید.');
    }

    // ── ۷) بدنهٔ درخواست ──
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $body   = null;

    if ($method === 'POST') {
        $raw = (string) file_get_contents('php://input');

        if (strlen($raw) > RELAY_MAX_BODY_BYTES) {
            relay_fail(413, 'بدنهٔ درخواست بیش از حد بزرگ است.');
        }

        if ($raw === '') {
            relay_fail(400, 'بدنهٔ درخواست خالی است.');
        }

        $body = $raw;
    } elseif ($method !== 'GET') {
        relay_fail(405, 'فقط GET و POST مجاز است.');
    }

    // ── ۸) آدرس مقصد + احراز سرویس واقعی ──
    $url = $upstreamBase.$forwardPath;

    if ($authMode === 'query') {
        // Gemini — کلید در querystring (کلاینت ?key= خودش حذف و کلید مقصد تزریق می‌شود)
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        $params['key'] = $upstreamKey !== '' ? $upstreamKey : (string) ($clientAuth['query'] ?? '');
        $url = $upstreamBase.$forwardPath.'?'.http_build_query($params);
    }

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    if ($upstreamKey !== '') {
        // کلید واقعی فقط همین‌جا تزریق می‌شود
        if ($authMode === 'x-api-key') {
            $headers[] = 'x-api-key: '.$upstreamKey;
        } elseif ($authMode !== 'query') {
            $headers[] = 'Authorization: Bearer '.$upstreamKey;
        }
    } else {
        // پاس‌ترو — احراز کلاینت عیناً به مقصد می‌رود
        if ($clientAuth['bearer'] !== null) {
            $headers[] = 'Authorization: Bearer '.$clientAuth['bearer'];
        }

        if ($clientAuth['x-api-key'] !== null) {
            $headers[] = 'x-api-key: '.$clientAuth['x-api-key'];
        }
    }

    // هدرهای عمومی + مخصوص سرویس (هر خط «Name: value»)
    foreach (array_merge(RELAY_GLOBAL_HEADERS, (array) ($provider['headers'] ?? [])) as $name => $value) {
        if ((string) $name !== '' && (string) $value !== '') {
            $headers[] = $name.': '.(string) $value;
        }
    }

    // نسخهٔ Anthropic — اگر کلاینت نفرستاده باشد، ست می‌شود
    if ($providerKey === 'anthropic' && preg_grep('/^anthropic-version:/i', $headers) === []) {
        $headers[] = 'anthropic-version: 2023-06-01';
    }

    // ── ۹) فوروارد با cURL ──
    $isStream = false;

    if ($body !== null) {
        $decoded = json_decode($body, true);
        $isStream = is_array($decoded) && ! empty($decoded['stream']);
    }

    if (! $isStream && isset($_GET['alt']) && $_GET['alt'] === 'sse') {
        $isStream = true; // Gemini streamGenerateContent?alt=sse
    }

    $ch = curl_init($url);

    if ($isStream) {
        // پاسخ استریمی (SSE) — چانک‌به‌چانک به کلاینت هم‌زمان با دریافت برمی‌گردد
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no'); // خاموش‌کردن بافر nginx
        header('X-AI-Relay-Provider: '.$providerKey);

        curl_setopt_array($ch, [
            CURLOPT_POST           => $method === 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => RELAY_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => RELAY_TIMEOUT,
            CURLOPT_HEADERFUNCTION => function ($ch, string $line): int {
                $trimmed = trim($line);
                // status-line → کد HTTP؛ هدرهای خاص هم پاس داده می‌شوند
                if (stripos($trimmed, 'HTTP/') === 0) {
                    if (preg_match('#HTTP/\S+\s+(\d{3})#', $trimmed, $m)) {
                        http_response_code((int) $m[1]);
                    }

                    return strlen($line);
                }
                if (preg_match('#^(content-type|x-request-id|request-id|openai-[^:]+|anthropic-[^:]+):\s*(.+)$#i', $trimmed, $m)) {
                    header($m[1].': '.$m[2]);
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION  => function ($ch, string $chunk): int {
                echo $chunk;
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();

                return strlen($chunk);
            },
        ]);

        if ($method !== 'POST') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        curl_exec($ch);

        if (curl_errno($ch) !== 0) {
            // بعد از شروع استریم نمی‌توان status عوض کرد؛ فقط در لاگ می‌رود
            error_log('[ai-relay] stream curl error: '.curl_error($ch));
        }

        curl_close($ch);
        exit;
    }

    // پاسخ JSON ساده — عیناً با همان کد وضعیت برمی‌گردد
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CONNECTTIMEOUT => RELAY_CONNECT_TIMEOUT,
        CURLOPT_TIMEOUT        => RELAY_TIMEOUT,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
    ]);

    if ($method === 'POST') {
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $body,
        ]);
    }

    $response = curl_exec($ch);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        error_log('[ai-relay] curl error: '.$err);
        relay_fail(502, 'اتصال رله به سرویس مقصد برقرار نشد: '.$err);
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $responseBody = $headerSize > 0 ? substr((string) $response, $headerSize) : (string) $response;

    http_response_code($status !== 0 ? $status : 502);
    header('Content-Type: application/json; charset=utf-8');
    header('X-AI-Relay-Provider: '.$providerKey);
    header('X-AI-Relay-Version: 2.1');
    echo $responseBody;
    exit;
}

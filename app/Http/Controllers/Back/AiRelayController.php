<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * رلهٔ سراسری هوش مصنوعی — فاز ۱۲۴.
 *
 * یک بار در «تنظیمات توسعه‌دهنده ← تب رلهٔ هوش مصنوعی» تنظیم می‌شود و فایل
 * relay.php چند-سرویسی ساخته/دانلود می‌شود؛ همهٔ پکیج‌ها (هوش مصنوعی و
 * پکیج‌های آینده) از همین رله استفاده می‌کنند — بدون تکرار در هیچ پکیجی.
 *
 * ذخیره‌گاه: جدول options (توسط Livewire Developer::saveAiRelay).
 * این کنترلر فقط «ساخت و دانلود فایل» را انجام می‌دهد (GET — idempotent).
 */
class AiRelayController extends Controller
{
    /** کلیدهای option */
    public const OPT_ACCESS_KEY = 'ai_relay_access_key';

    public const OPT_DOMAIN = 'ai_relay_domain';

    public const OPT_PROVIDERS = 'ai_relay_providers';

    public const OPT_ALLOWED_IPS = 'ai_relay_allowed_ips';

    public const OPT_HEADERS = 'ai_relay_headers';

    /** فهرست سرویس‌های ذخیره‌شده — [{key,base_url,api_key,headers,enabled}] */
    public static function savedProviders(): array
    {
        $raw = (string) option(self::OPT_PROVIDERS, '[]');
        $list = json_decode($raw, true);

        return is_array($list) ? array_values($list) : [];
    }

    /** فقط سرویس‌های فعال‌شده با آدرس مقصد */
    public static function activeProviders(): array
    {
        return array_values(array_filter(self::savedProviders(), fn (array $p) => (
            ! empty($p['enabled']) && trim((string) ($p['base_url'] ?? '')) !== ''
        )));
    }

    /** آیا رله به‌اندازهٔ کافی پیکربندی شده است؟ (کلید + حداقل یک سرویس فعال) */
    public static function configured(): bool
    {
        return trim((string) option(self::OPT_ACCESS_KEY, '')) !== ''
            && self::activeProviders() !== [];
    }

    /** اطلاعات رله برای نمایش در سایر صفحات (مثل تنظیمات پکیج AI) */
    public static function summary(): array
    {
        $providers = self::savedProviders();

        return [
            'configured' => self::configured(),
            'domain' => trim((string) option(self::OPT_DOMAIN, '')),
            'accessKey' => (string) option(self::OPT_ACCESS_KEY, ''),
            'enabledKeys' => array_values(array_map(
                fn (array $p) => (string) $p['key'],
                array_filter($providers, fn (array $p) => ! empty($p['enabled']))
            )),
            'providerCount' => count($providers),
        ];
    }

    /** آدرس پایهٔ استفاده در فروشگاه برای یک سرویس — https://رله/relay.php/{key}/v1 */
    public static function shopBaseUrl(string $providerKey): string
    {
        $domain = rtrim(trim((string) option(self::OPT_DOMAIN, '')), '/');

        if ($domain === '') {
            return '';
        }

        $pattern = (string) (config("ai-relay.shop_base_paths.{$providerKey}")
            ?? config('ai-relay.shop_base_paths.default'));

        return $domain.str_replace('{provider}', $providerKey, $pattern);
    }

    public function download(Request $request): Response
    {
        abort_unless(config('admin_panel.enabled', true), 404);

        $accessKey = trim((string) option(self::OPT_ACCESS_KEY, ''));

        if ($accessKey === '') {
            return back()->with('toast-error', 'ابتدا «کلید دسترسی رله» را ذخیره کنید.');
        }

        $active = self::activeProviders();

        if ($active === []) {
            return back()->with('toast-error', 'حداقل یک سرویس را در رله فعال کنید (آدرس مقصد لازم است).');
        }

        $templatePath = resource_path('relay/relay-template.php');

        if (! is_file($templatePath)) {
            return back()->with('toast-error', 'قالب relay.php یافت نشد — فایل resources/relay/relay-template.php را بررسی کنید.');
        }

        $code = (string) file_get_contents($templatePath);

        $phpStr = fn (string $v): string => "'".addcslashes($v, "'\\")."'";

        // ── ۱) سرویس‌ها ──
        $providerLines = [];

        foreach ($active as $p) {
            $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim((string) $p['key'])));

            if ($key === '' || $key === 'health') {
                continue;
            }

            $base = rtrim(trim((string) $p['base_url']), '/');
            $apiKey = trim((string) ($p['api_key'] ?? ''));
            $auth = (string) (config("ai-relay.providers.{$key}.auth") ?? 'bearer');

            $providerLines[] = str_repeat(' ', 4).$phpStr($key).' => ['."\n"
                .str_repeat(' ', 8)."'base' => ".$phpStr($base).",\n"
                .str_repeat(' ', 8)."'key' => ".$phpStr($apiKey).",\n"
                .str_repeat(' ', 8)."'auth' => ".$phpStr($auth).",\n"
                .str_repeat(' ', 8)."'headers' => [],\n"
                .str_repeat(' ', 4).'],';
        }

        $providersCode = "\n".implode("\n", $providerLines)."\n";
        $code = str_replace('$RELAY_PROVIDERS = [];', '$RELAY_PROVIDERS = ['.$providersCode.'];', $code);

        // ── ۲) کلید دسترسی ──
        $code = str_replace(
            "define('RELAY_ACCESS_KEY', getenv('RELAY_ACCESS_KEY') ?: 'CHANGE-ME-TO-A-LONG-RANDOM-STRING');",
            "define('RELAY_ACCESS_KEY', getenv('RELAY_ACCESS_KEY') ?: ".$phpStr($accessKey).");",
            $code
        );

        // ── ۳) IPهای مجاز ──
        $ips = array_values(array_filter(array_map(
            'trim',
            preg_split('/[\s,،؛;]+/u', (string) option(self::OPT_ALLOWED_IPS, '')) ?: []
        ), fn (string $ip) => $ip !== '' && preg_match('/^[0-9a-fA-F.:]+$/', $ip) === 1));

        $ipsCode = $ips === []
            ? '[]'
            : '['.implode(', ', array_map($phpStr, $ips)).']';

        $code = str_replace("define('ALLOWED_CLIENT_IPS', []);", "define('ALLOWED_CLIENT_IPS', {$ipsCode});", $code);

        // ── ۴) هدرهای عمومی — هر خط «Name: value» ──
        $headerLines = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) option(self::OPT_HEADERS, '')) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name !== '' && $value !== '' && preg_match('/^[A-Za-z0-9-]+$/', $name) === 1) {
                $headerLines[] = str_repeat(' ', 4).$phpStr($name).' => '.$phpStr($value).',';
            }
        }

        $headersCode = $headerLines === []
            ? '[]'
            : "[\n".implode("\n", $headerLines)."\n]";

        $code = str_replace("define('RELAY_GLOBAL_HEADERS', []);", "define('RELAY_GLOBAL_HEADERS', {$headersCode});", $code);

        // ── ۵) نشانِ تولید از پنل ──
        $providerKeys = implode('، ', array_map(fn (array $p) => (string) $p['key'], $active));
        $banner = "<?php\n\n/* ✅ این فایل از پنل فروشگاه ساخته شده است (تنظیمات توسعه‌دهنده ← رلهٔ هوش مصنوعی)\n"
            ."   تاریخ: ".now()->format('Y-m-d H:i')." — سرویس‌ها: {$providerKeys}\n"
            ."   نسخهٔ رله: 2.0 (چند-سرویسی)\n*/\n";
        $code = (string) Str::replaceFirst('<?php', rtrim($banner, "\n"), $code);

        Log::info('AiRelay: فایل رلهٔ چند-سرویسی relay.php ساخته شد', [
            'providers' => $providerKeys,
            'ips' => count($ips),
        ]);

        return response($code, 200, [
            'Content-Type' => 'application/x-php; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="relay.php"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

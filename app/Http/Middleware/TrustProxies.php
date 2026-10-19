<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/*
 * پراکسی‌های معتمد — در لاراول ۱۳ از کلاس داخلی فریم‌ورک استفاده می‌شود
 * (پکیج fideloper/proxy دیگر وجود ندارد)
 *
 * از .env کنترل می‌شود:
 *  TRUST_PROXIES=*            → اعتماد به همه (پشت Cloudflare/Nginx داخلی)
 *  TRUST_PROXIES=10.0.0.5,... → لیست مشخص
 *  بدون مقدار                 → بدون اعتماد (پیش‌فرض امن)
 */
class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct()
    {
        $proxies = trim((string) env('TRUST_PROXIES', ''));

        if ($proxies !== '') {
            $this->proxies = $proxies === '*'
                ? '*'
                : array_filter(array_map('trim', explode(',', $proxies)));
        } else {
            $this->proxies = '';
        }
    }
}

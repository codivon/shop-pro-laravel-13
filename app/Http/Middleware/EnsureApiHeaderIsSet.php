<?php

namespace App\Http\Middleware;

use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Apikey;
use Closure;
use Illuminate\Http\Request;

class EnsureApiHeaderIsSet
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $api_key = $request->header('x-api-key');

        $currentPath = $request->path();
        $exceptPaths = ['api/v1/torob/products', 'api/v1/emalls/products']; // مسیرهای کامل

        // فاز ۹۵ — تأیید پرداخت بدون نشست: این روت عیناً callbackUrl درگاه است و
        // درگاه‌ها هدر سفارشی x-api-key نمی‌فرستند؛ امنیت از طریق توکن یکتای
        // تراکنش (64 نویسهٔ تصادفی) تضمین می‌شود.
        $exceptPaths[] = 'api/v1/payments/verify/*';

        foreach ($exceptPaths as $exceptPath) {
            if ($exceptPath === $currentPath || fnmatch($exceptPath, $currentPath)) {
                return $next($request);
            }
        }

        if ($api_key) {
            if (Apikey::where('key', $api_key)->where('is_active', true)->first()) {
                return $next($request);
            }
        }
        return $this->respondForbidden('please set x-api-key header correctly');
    }
}

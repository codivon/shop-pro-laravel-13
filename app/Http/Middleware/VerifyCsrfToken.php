<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    public function handle($request, Closure $next)
    {
        // امنیتی: هیچ مسیری به‌صورت پویا از CSRF مستثنی نمی‌شود
        return parent::handle($request, $next);
    }
}

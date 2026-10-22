<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $whitelist = array('127.0.0.1', "::1");

        // $request->ip() به‌جای $_SERVER['REMOTE_ADDR'] — در CLI/تست‌ها (و برخی
        // کانفیگ‌های FPM پشت پروکسی) REMOTE_ADDR در $_SERVER وجود ندارد و هشدار
        // undefined در لاراول به استثنا تبدیل و درخواست 500 می‌شود
        if(in_array($request->ip(), $whitelist)){
            return $next($request);
        }
        if (!$request->secure()) {
            return redirect()->secure($request->getRequestUri());
        }
        return $next($request);

    }
}

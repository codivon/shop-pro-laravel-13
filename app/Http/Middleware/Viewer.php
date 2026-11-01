<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Viewer as ViewerModel;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Agent;

class Viewer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!application_installed()) {
            return $next($request);
        }

        /*
         * فاز ۱۳۷ — پنل‌های ادمین/فروشنده ثبت‌نمی‌شوند:
         * میدلور روی «هر GET» یک INSERT به جدول viewers می‌زد (حتی برای
         * پیمایش مدیر در پنل و هر بارگذاری اولیهٔ Livewire). آمار بازدیدِ
         * «بازدیدکننده» به ورودِ پرسنل نیازی ندارد؛ حذفش از مسیر پنل یعنی
         * یک INSERT کمتر روی MySQL هاست اشتراکی در هر پیمایش پنل.
         */
        if (auth('adminPanel')->check() || auth('seller')->check()) {
            return $next($request);
        }

        if ($request->method() == 'GET') {
            $options = [];
            $agent   = new Agent();

            $options['device']    = $agent->device();
            $options['platform']  = $agent->platform();
            $options['browser']   = $agent->browser();
            $options['robot']     = $agent->robot();
            $options['method']    = request()->method();
            $options['referer']   = request()->headers->get('referer');

            $page_path=explode('/',request()->getRequestUri());

            // فاز ۱۳۷ — آمار هرگز نباید رندر صفحه را بشکند
            try {
                ViewerModel::create([
                    'ip'      => request()->ip(),
                    'page_path'    => @$page_path[1],
                    // FIX PHP 8.4 deprecation: urldecode(null) — وقتی segment وجود ندارد
                    'product_path' => isset($page_path[2]) ? urldecode((string) $page_path[2]) : null,
                    'path'    => request()->getRequestUri(),
                    'auth'    => auth()->check(),
                    'admin_id' => auth('adminPanel')->check() ? auth('adminPanel')->user()->id : null,
                    'user_id' => auth('web')->check() ? auth('web')->user()->id : null,
                    'seller_id' => auth('seller')->check() ? auth('seller')->user()->id : null,
                    'options' => json_encode($options)
                ]);

                Cache::increment('admin.views-count-' . now()->format('Y-m-d'));
            } catch (\Throwable $e) {
                // آمار مهم‌تر از صفحه است؛ خطای جدول/دیسک فقط report می‌شود
                report($e);
            }
        }

        return $next($request);
    }
}

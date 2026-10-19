<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class InstallController extends Controller
{
    public function showRegisterForm(Request $request)
    {
        return view('back.auth.register');
    }

    public function register(Request $request)
    {
        // امنیتی: قفل اتمی نصب — جلوگیری از رقابت همزمان برای ساخت اولین مدیر
        try {
            $lock = cache()->lock('install:creator-lock', 30);

            if (!$lock->get()) {
                return response()->json(['message' => 'فرآیند نصب در حال انجام است؛ لطفاً چند لحظه بعد تلاش کنید.'], 429);
            }

            try {
                return $this->performRegistration($request);
            } finally {
                optional($lock)->release();
            }
        } catch (\Throwable $e) {
            // اگر درایور کش از lock پشتیبانی نمی‌کند، بدون قفل ادامه بده
            report($e);

            return $this->performRegistration($request);
        }
    }

    private function performRegistration(Request $request)
    {
        $this->validate($request, [
            'first_name'            => 'required|string|max:191',
            'last_name'             => 'required|string|max:191',
            'username'              => 'required|string|max:191|unique:admins',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
            'admin_route_prefix'    => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,100}$/'],
        ]);

        $admin = Admin::create([
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'username'       => $request->username,
            'level'          => 'creator',
            'password'       => Hash::make($request->password),
            'remember_token' => Str::random(10),
            'verified_at'    => Carbon::now(),
        ]);

        DB::table('admin_role')->insert([
            'role_id' => '1',
            'admin_id' => $admin->id
        ]);
        change_env('ADMIN_ROUTE_PREFIX', $request->admin_route_prefix);


        $this->configApplication();

        // امنیتی: ثبت نشان نصب‌شده — مسیرهای /install و /update عمومی برای همیشه بسته می‌شوند
        try { touch(storage_path('installed')); } catch (\Throwable $e) { report($e); }

        Auth::guard('adminPanel')->login($admin);

        return response()->json(['admin_prefix' => $request->admin_route_prefix, 'redirect' => url('/admin/' . $request->admin_route_prefix)]);
    }

    private function configApplication()
    {
        AppServiceProvider::loadTheme();

        Artisan::call('shop:link');
        Artisan::call('theme:config');

        change_env('SESSION_DRIVER', 'database');
        change_env('QUEUE_CONNECTION', 'database');
        option_update('app_debug_mode', 'on');

        Artisan::call('optimize');

        try {
            Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json'
            ])->get(config('general.api_url') . '/shop/installed', [
                'host'         => url('/') ?: config('app.url'),
                'time'         => now(),
                'script'       => 'shop',
                'server_ip'    => request()->server('SERVER_ADDR'),
                'installer_ip' => request()->ip(),
            ]);
        } catch (Exception $e) {
            // just continue
        }
    }
}

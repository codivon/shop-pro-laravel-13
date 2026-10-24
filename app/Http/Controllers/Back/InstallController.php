<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        // فاز ۲۵ — کارهای پس از نصب در سرویس مشترک (پنل جدید هم همین را صدا می‌زند)
        app(\App\Services\SetupFinalizer::class)->finalize($request->admin_route_prefix);

        Auth::guard('adminPanel')->login($admin);

        return response()->json(['admin_prefix' => $request->admin_route_prefix, 'redirect' => url('/admin/' . $request->admin_route_prefix)]);
    }
}

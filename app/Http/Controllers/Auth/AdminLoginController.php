<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class AdminLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:adminPanel')->except('logout');
    }

    public function showLoginForm()
    {
        if (count(Admin::all())){
            if (Auth::guard('adminPanel')->user()){
                return redirect('/admin-old/'.admin_route_prefix());
            }else{
                return view('back.auth.login');
            }
        }else{
            return redirect()->route('admin.register');
        }


    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'username'   => 'required',
            'password' => 'required|min:6'
        ],[
            'username.required'=>'فیلد ایمیل الزامی می باشد.',
            'password.required'=>'فیلد رمز ورود الزامی می باشد.',
            'password.min'=>'فیلد رمز ورود 6 کاراکتر الزامی می باشد.',
        ]);
        // Attempt to log the user in
        if (Auth::guard('adminPanel')->attempt(['username' => $request->username, 'password' => $request->password], $request->remember)) {
            // if successful, then redirect to their intended location
            if (admin()->status=="ACTIVE"){
                Admin::where('id', admin()->id)->update(['updated_at' => Carbon::now()->format('Y-m-d H:m:s')]);
                $request->session()->regenerate();
                $request->session()->put('auth.password_confirmed_at', time());
                return redirect('/admin-old/'.admin_route_prefix());
            }else{
                Auth::guard('adminPanel')->logout();
                session()->put('admin_login_error','حساب کاربری شما غیر فعال می باشد.');
                return redirect('/admin-old/'.admin_route_prefix().'/login');
            }

        }

        session()->put('admin_login_error','مشخصات وارد شده با اطلاعات ما سازگار نیست.');
        // if unsuccessful, then redirect back to the login with the form data
        return redirect()->back()->withInput($request->only('username', 'remember'));
    }
    public function logout()
    {
        Auth::guard('adminPanel')->logout();
        return redirect('/admin-old/'.admin_route_prefix().'/login');
    }
}

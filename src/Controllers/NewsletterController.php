<?php

namespace Themes\SaniarTheme\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        // اعتبارسنجی - یک فیلد که هم ایمیل باشه هم شماره
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string|max:255|unique:newsletters,contact',
        ], [
            'contact.required' => 'لطفا ایمیل یا شماره موبایل خود را وارد کنید',
            'contact.unique' => 'این ایمیل یا شماره قبلاً ثبت شده است',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // بررسی کنید که ورودی ایمیل است یا شماره موبایل
        $contact = $request->contact;
        $isValid = false;

        // چک کردن ایمیل
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $isValid = true;
        }
        // چک کردن شماره موبایل
        elseif (preg_match('/^09[0-9]{9}$/', $contact)) {
            $isValid = true;
        }

        if (!$isValid) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فرمت ایمیل یا شماره موبایل صحیح نیست'
                ]);
            }
            return back()->with('error', 'فرمت ایمیل یا شماره موبایل صحیح نیست');
        }

        // دریافت اطلاعات کاربر
        $userAgent = $request->userAgent();

        // ثبت در دیتابیس
        $newsletter = Newsletter::create([
            'contact' => $contact,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => Newsletter::getDeviceType($userAgent),
            'browser' => Newsletter::getBrowser($userAgent),
            'os' => Newsletter::getOS($userAgent),
            'referrer' => $request->server('HTTP_REFERER'),
            'landing_page' => url()->previous(),
            'is_active' => true,
        ]);

        $message = 'عضویت شما در خبرنامه با موفقیت ثبت شد';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return back()->with('success', $message);
    }

    public function unsubscribe($id)
    {
        $subscriber = Newsletter::findOrFail($id);

        // امنیت: هر کاربر فقط می‌تواند اشتراک خودش را لغو کند (رفع IDOR)
        $user = auth()->user();
        $isOwner = $user && (
            ($user->email && $subscriber->contact === $user->email)
            || ($user->mobile && $subscriber->contact === $user->mobile)
        );

        abort_unless($isOwner, 404);

        $subscriber->update(['is_active' => false]);

        return redirect()->back()->with('success', 'شما با موفقیت از خبرنامه لغو عضویت شدید');
    }
}

<?php

namespace Themes\SaniarTheme\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\NewSeller;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerCommission;
use App\Models\SellerEcontract;
use App\Models\SellerHero;
use App\Models\SellerInfo;
use App\Models\SellerQuestion;
use App\Models\Slider;
use App\Models\Wallet;
use App\Notifications\Seller\SellerCreated;
use App\Notifications\Seller\SellerRegistered;
use App\Notifications\Sms\NewSellerCodeSent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Themes\SaniarTheme\src\Requests\Store_registration_business_details;

class SellerController extends Controller
{
    // Registration status constants
    protected const REGISTER_STATUS_COMPLETE = 'complete';
    protected const REGISTER_STATUS_DOCUMENTS = 'documents';
    protected const REGISTER_STATUS_BUSINESS_DETAILS = 'business-details';
    protected const REGISTER_STATUS_CHECKOUT = 'checkout';

    // Session keys
    protected const SESSION_KEY_BUSINESS_DETAILS = 'show-seller-business-details';
    protected const SESSION_KEY_VERIFICATION = 'show-seller-verification-form';
    protected const SESSION_KEY_DOCUMENTS = 'show-seller-documents';
    protected const SESSION_KEY_CHECKOUT = 'show-seller-checkout';

    public function __construct()
    {
        if (option('multi_vendor_system_status', 'false') === 'false') {
            abort(404);
        }
    }

    // ---------------------- HELPER METHODS ----------------------

    private function forgetAllRegistrationSessions(): void
    {
        session()->forget([
            self::SESSION_KEY_BUSINESS_DETAILS,
            self::SESSION_KEY_VERIFICATION,
            self::SESSION_KEY_DOCUMENTS,
            self::SESSION_KEY_CHECKOUT,
        ]);
    }

    private function getSellerFromSession(string $sessionKey): ?Seller
    {
        $mobile = session($sessionKey);
        return Seller::where('mobile', $mobile)->first();
    }

    private function getNewSellerFromSession(string $sessionKey): ?NewSeller
    {
        $mobile = session($sessionKey);
        return NewSeller::where('mobile', $mobile)->first();
    }

    private function generateUniqueSlug(string $businessName): string
    {
        // فاز ۲۵ — اسلاگ فارسی (Str::slug حروف فارسی را حذف می‌کرد)
        $slug = sluggable_helper_function($businessName) ?: trim($businessName);
        if (Seller::where('slug', $slug)->exists()) {
            $lastId = Seller::latest()->first()->id ?? 0;
            return $slug . ($lastId + 1);
        }
        return $slug;
    }

    private function getLoginField(string $login): string
    {
        return is_numeric($login) ? 'mobile' : 'email';
    }

    // ---------------------- PAGE DISPLAY METHODS ----------------------

    public function index()
    {
        $this->forgetAllRegistrationSessions();

        $sliders = Slider::whereJsonContains('pages', 'sellers')
            ->where('published', true)
            ->orderBy('ordering')
            ->get();

        return view('front::sellers.index', [
            'sliders' => $sliders,
            'sellers_heroes' => SellerHero::all(),
            'sellers_commissions' => SellerCommission::all(),
            'sellers_questions' => SellerQuestion::all(),
        ]);
    }

    public function login()
    {
        $this->forgetAllRegistrationSessions();

        if (Auth::guard('seller')->check()) {
            return redirect()->route('seller.dashboard');
        }

        return view('front::sellers.auth.login');
    }

    public function registration()
    {
        $this->forgetAllRegistrationSessions();

        if (Auth::guard('seller')->check()) {
            return redirect()->route('seller.dashboard');
        }

        return view('front::sellers.auth.register');
    }

    public function registration_mobile()
    {
        $seller = NewSeller::where(['mobile' => session(self::SESSION_KEY_VERIFICATION)])->first();

        if (!$seller) {
            return redirect()->route('seller.registration_new_seller');
        }

        $resend_time = $seller->updated_at->addSeconds(120)->timestamp;

        return view('front::sellers.auth.register-mobile', compact('seller', 'resend_time'));
    }

    public function registration_business_details()
    {
        if (session(self::SESSION_KEY_DOCUMENTS)) {
            return redirect()->route('seller.registration_documents');
        }

        $seller = NewSeller::where(['mobile' => session(self::SESSION_KEY_BUSINESS_DETAILS)])->first();
        if (!$seller) {
            return redirect()->route('seller.registration_new_seller');
        }

        $econtract = SellerEcontract::find(1);
        $provinces = Province::all();
        $categories = Category::where('category_id', null)->get();

        return view('front::sellers.auth.business-details', compact('econtract', 'provinces', 'categories'));
    }

    public function registration_documents()
    {
        $seller = Seller::where(['mobile' => session(self::SESSION_KEY_DOCUMENTS)])->first();
        if (!$seller) {
            return redirect()->route('seller.registration_new_seller');
        }

        return view('front::sellers.auth.documents');
    }

    public function registration_checkout()
    {
        $seller = Seller::where(['mobile' => session(self::SESSION_KEY_CHECKOUT)])->first();

        if (!$seller) {
            return redirect()->route('seller.registration_new_seller');
        }

        $categories = Category::where('commission', '!=', null)->get();

        return view('front::sellers.auth.checkout', compact('categories'));
    }

    // ---------------------- AUTH & LOGIN ----------------------

    public function login_check(Request $request)
    {
        // سانیار فاز I: اعتبارسنجی پیش از getLoginField — نسخهٔ اصلی با درخواستِ
        // بدون فیلد username، TypeError (۵۰۰) می‌داد (باگ لاتنت WeblakShop)
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], ['username.required' => 'لطفا این قسمت را خالی نگذارید']);

        $loginField = $this->getLoginField($request->username);

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        $messages = ['username.required' => 'لطفا این قسمت را خالی نگذارید'];

        if ($loginField === 'mobile') {
            $rules['username'] = 'required|digits:11|regex:/(09)[0-9]{9}/';
            $messages['username.regex'] = 'شماره موبایل نادرست است';
            $messages['username.digits'] = 'شماره موبایل نادرست است';
            $userTitle = 'شماره موبایل';
        } else {
            $rules['username'] = 'required|string|email';
            $messages['username.email'] = 'ایمیل را بطور صحیح وارد کنید';
            $messages['username.exists'] = 'حساب کاربری با مشخصات وارد شده وجود ندارد. لطفا از شماره تلفن همراه برای ساخت حساب کاربری استفاده نمایید.';
            $userTitle = 'ایمیل';
        }

        $this->validate($request, $rules, $messages);

        $seller = Seller::where($loginField, $request->username)->first();

        if (!$seller) {
            return response([
                'status' => 'error',
                'message' => $userTitle . ' وارد شده با اطلاعات ما سازگار نیست.',
            ]);
        }

        if (!Auth::guard('seller')->attempt(
            [$loginField => $request->username, 'password' => $request->password],
            $request->remember
        )) {
            return response([
                'status' => 'error',
                'message' => 'رمز عبور وارد شده صحیح نمی باشد.',
            ]);
        }

        // Handle login based on registration status
        if ($seller->status_register == self::REGISTER_STATUS_COMPLETE) {
            if ($seller->status != 'ACTIVE') {
                Auth::guard('seller')->logout();
                return response([
                    'status' => 'error',
                    'message' => 'حساب کاربری شما مسدود شده است',
                ]);
            }

            Seller::where('id', $seller->id)->update(['updated_at' => Carbon::now()->format('Y-m-d H:i:s')]);

            // راه‌حل ۲: Regenerate و لاگین مجدد
            $request->session()->regenerate();
            Auth::guard('seller')->login($seller, $request->remember);

            return response([
                'status' => 'success',
                'message' => 'با موفقیت وارد شدید',
                'redirect' => route('seller.dashboard'),
            ]);
        } elseif ($seller->status_register == self::REGISTER_STATUS_BUSINESS_DETAILS) {
            session()->put(self::SESSION_KEY_DOCUMENTS, $seller->mobile);
            return response([
                'status' => 'success',
                'message' => 'با موفقیت وارد شدید',
                'redirect' => route('seller.registration_documents'),
            ]);
        } elseif ($seller->status_register == self::REGISTER_STATUS_DOCUMENTS) {
            session()->put(self::SESSION_KEY_CHECKOUT, $seller->mobile);
            return response([
                'status' => 'success',
                'message' => 'با موفقیت وارد شدید',
                'redirect' => route('seller.registration_checkout'),
            ]);
        }

        Auth::guard('seller')->logout();
        return response([
            'status' => 'error',
            'message' => 'وضعیت ثبت‌نام نامعتبر است.',
        ]);
    }

    public function logout()
    {
        Auth::guard('seller')->logout();
        return redirect()->route('seller.dashboard');
    }

    // ---------------------- REGISTRATION FLOW ----------------------

    public function registration_new_seller(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:sellers',
            'mobile' => 'required|regex:/(09)[0-9]{9}/|digits:11|unique:sellers',
            'password' => 'required',
        ]);

        $code_mobile = random_int(10000, 99999);

        $seller = NewSeller::where(['mobile' => $request->mobile])->first();

        if ($seller) {
            $seller->code_mobile_verification = $code_mobile;
            $seller->email = $request->email;
            $seller->password = $request->password;
            $seller->save();
        } else {
            $seller = new NewSeller();
            $seller->email = $request->email;
            $seller->mobile = $request->mobile;
            $seller->password = $request->password;
            $seller->code_mobile_verification = $code_mobile;
            $seller->save();
        }

        Notification::send($seller, new NewSellerCodeSent($seller));

        session()->put(self::SESSION_KEY_VERIFICATION, $request->mobile);

        return response([
            'redirect' => route('seller.registration_mobile'),
        ]);
    }

    public function registration_mobile_check(Request $request)
    {
        $user = NewSeller::where('mobile', $request->mobile)->first();
        $time = Carbon::now()->subMinutes(15);

        $this->validate($request, [
            'mobile' => 'required|regex:/(09)[0-9]{9}/|digits:11|unique:sellers',
            'verify_code' => [
                'required',
                Rule::exists('new_sellers', 'code_mobile_verification')->where(function ($query) use ($user, $time) {
                    $query->where('mobile', $user->mobile)->where('updated_at', '>=', $time);
                }),
            ],
        ], [
            'verify_code.exists' => 'کد وارد شده اشتباه است',
        ]);

        session()->put(self::SESSION_KEY_BUSINESS_DETAILS, $request->mobile);
        session()->forget(self::SESSION_KEY_VERIFICATION);

        return response([
            'redirect' => route('seller.registration_business_details'),
        ]);
    }

    public function registration_business_details_store(Store_registration_business_details $request)
    {
        $newSeller = NewSeller::where(['mobile' => session(self::SESSION_KEY_BUSINESS_DETAILS)])->first();

        if (!$newSeller) {
            return redirect()->route('seller.login');
        }

        $seller = Seller::where(['mobile' => session(self::SESSION_KEY_BUSINESS_DETAILS), 'email' => $newSeller->email])->first();

        if ($seller) {
            session()->forget(self::SESSION_KEY_BUSINESS_DETAILS);
            return response([
                'status' => 'error',
                'message' => 'ایمیل و شماره از قبل وجود دارد.',
                'redirect' => route('seller.login'),
            ]);
        }

        $slug = $this->generateUniqueSlug($request->business_name);

        $seller = new Seller();
        $seller->slug = $slug;
        $seller->email = $newSeller->email;
        $seller->mobile = $newSeller->mobile;
        $seller->password = Hash::make($newSeller->password);
        $seller->mobile_verification = 'YES';
        $seller->remember_token = Str::random(60);
        $seller->save();

        $seller_info = new SellerInfo();
        $seller_info->seller_id = $seller->id;
        $seller_info->first_name = $request->first_name;
        $seller_info->last_name = $request->last_name;
        $seller_info->birth_day = $request->birth_year . '/' . $request->birth_month . '/' . $request->birth_day;
        $seller_info->gender = $request->gender;
        $seller_info->identity_card_number = $request->identity_card_number;
        $seller_info->national_identity_number = $request->national_identity_number;

        $seller_info->company_name = $request->company_name;
        $seller_info->company_type = $request->company_type;
        $seller_info->company_registration_number = $request->company_registration_number;
        $seller_info->company_national_identity_number = $request->company_national_identity_number;
        $seller_info->company_economic_number = $request->company_economic_number;

        $seller_info->state_id = $request->state_id;
        $seller_info->city_id = $request->city_id;
        $seller_info->address = $request->address;
        $seller_info->phone = $request->phone;
        $seller_info->post_code = $request->post_code;
        $seller_info->location = $request->lat_and_long;
        $seller_info->mobile = $newSeller->mobile;

        $seller_info->business_name = $request->business_name;
        $seller_info->shaba_number = $request->shaba_number;
        $seller_info->main_supply_category_id = $request->main_supply_category_id;
        $seller_info->number_of_products = $request->number_of_products;
        $seller_info->econtract = $request->econtract;
        $seller_info->save();

        $wallet = new Wallet();
        $wallet->seller_id = $seller->id;
        $wallet->save();

        $newSeller->delete();

        if ($seller_info) {
            session()->forget(self::SESSION_KEY_BUSINESS_DETAILS);
            session()->put(self::SESSION_KEY_DOCUMENTS, $seller_info->mobile);
            return response([
                'status' => 'success',
                'redirect' => route('seller.registration_documents'),
            ]);
        }
    }

    public function registration_documents_store(Request $request)
    {
        $seller = Seller::where(['mobile' => session(self::SESSION_KEY_DOCUMENTS)])->first();

        if (!$seller) {
            return abort(404);
        }

        $sellerInfo = SellerInfo::where('seller_id', $seller->id)->first();

        if ($request->hasFile($request->imageFor)) {
            $directory = "uploads/sellers/documents/{$seller->id}";

            if (!file_exists(public_path($directory))) {
                Storage::disk('public')->makeDirectory($directory);
            }

            if ($sellerInfo[$request->imageFor] && Storage::disk('public')->exists($sellerInfo[$request->imageFor])) {
                Storage::disk('public')->delete($sellerInfo[$request->imageFor]);
            }

            $file = $request[$request->imageFor];
            $name = uploadOptimizedImage($file, 'sellers/documents', $seller->id);

            $sellerInfo[$request->imageFor] = $name;
            $sellerInfo->save();
        }
    }

    public function registration_documents_delete(Request $request)
    {
        $seller = Seller::where(['mobile' => session(self::SESSION_KEY_DOCUMENTS)])->first();

        if (!$seller) {
            return abort(404);
        }

        $sellerInfo = SellerInfo::where('seller_id', $seller->id)->first();

        Storage::disk('public')->delete($sellerInfo[$request->imageFor]);
        $sellerInfo[$request->imageFor] = null;
        $sellerInfo->save();
    }

    public function registration_documents_check(Request $request)
    {
        $seller = Seller::where(['mobile' => session(self::SESSION_KEY_DOCUMENTS)])->first();

        if (!$seller) {
            return abort(404);
        }

        $sellerInfo = SellerInfo::where('seller_id', $seller->id)->first();

        if ($request->vat_free == 1) {
            if ($sellerInfo->card_image == "" or $sellerInfo->card_image_back == "" or $sellerInfo->vat_image == "") {
                return response([
                    'status' => 'error',
                    'message' => 'انتخاب تصویر گواهی ارزش افزوده و تصویر کارت ملی اجباری است',
                ]);
            }
        } elseif ($request->vat_free == 2) {
            if ($sellerInfo->card_image == "" or $sellerInfo->card_image_back == "") {
                return response([
                    'status' => 'error',
                    'message' => 'انتخاب تصویر کارت ملی اجباری است',
                ]);
            }
        }

        $sellerInfo->vat_free = $request->vat_free;
        $sellerInfo->save();

        $seller->status_register = self::REGISTER_STATUS_DOCUMENTS;
        $seller->save();

        session()->forget(self::SESSION_KEY_DOCUMENTS);
        session()->put(self::SESSION_KEY_CHECKOUT, $sellerInfo->mobile);

        return response([
            'status' => 'success',
            'redirect' => route('seller.registration_checkout'),
        ]);
    }

    public function registration_checkout_store(Request $request)
    {
        $seller = Seller::where(['mobile' => session(self::SESSION_KEY_CHECKOUT)])->first();

        if (!$seller) {
            return response([
                'status' => 'success',
                'redirect' => route('seller.registration_new_seller'),
            ]);
        }

        $seller->status_register = self::REGISTER_STATUS_COMPLETE;
        $seller->save();

        session()->forget(self::SESSION_KEY_CHECKOUT);

        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new SellerRegistered($seller));

        if (option('sms_on_seller_register', 'off') == 'on') {
            Notification::send($seller, new SellerCreated($seller));
        }

        Auth::guard('seller')->login($seller);

        return response([
            'status' => 'success',
            'redirect' => route('seller.dashboard'),
        ]);
    }

    public function get_new_code(Request $request)
    {
        $newSeller = NewSeller::where('mobile', $request->mobile)->first();

        if ($newSeller) {
            $now = Carbon::now();
            $time = $newSeller->updated_at;

            if ($time->diffInSeconds($now) < 1) {
                return response([
                    'status' => 'errorTime',
                ]);
            }

            if (option('sms_to_verify_user', 'on') == 'on') {
                Notification::send($newSeller, new NewSellerCodeSent($newSeller));
            }

            return response([
                'status' => 'success',
            ]);
        }

        return response([
            'status' => 'error',
            'redirect' => route('seller.registration'),
        ]);
    }
}

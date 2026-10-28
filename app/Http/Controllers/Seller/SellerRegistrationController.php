<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\NewSeller;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Models\SellerRegistration;
use App\Models\Wallet;
use App\Notifications\Sms\SellerPhoneOtp;
use App\Support\Finnotech;
use App\Support\SellerRegistrationFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/*
 * کنترلر ثبت‌نام مرحله‌ای فروشندگان (فاز ۱۱۰ — پنل مدرن)
 *
 * جریان جدید: موبایل → کد ۶ رقمی → وضعیت (حقیقی/حقوقی) → اطلاعات تکمیلی
 * (کد ملی + احراز هویت اختیاری فینوتک) → اطلاعات کسب‌وکار (ایمیل/رمز +
 * آدرس + فروشگاه) → مدارک → پایان.
 *
 * اندپوینت‌های قدیمی (registration_new_seller و…) برای پنل قدیمی و
 * تست‌های فازهای قبل دست‌نخورده‌اند؛ این کنترلر فقط جریان جدید است.
 *
 * ذخیرهٔ هر مرحله در جدول seller_registrations (پیش‌نویس) انجام می‌شود تا
 * رفرش/قطع اینترنت پیشرفت را نپراند و گیتِ ترتیب مراحل سمت سرور باشد.
 */
class SellerRegistrationController extends Controller
{
    public function __construct()
    {
        if (option('multi_vendor_system_status', 'false') === 'false') {
            abort(404);
        }
    }

    /* ================= گام ۱ — ارسال کد موبایل ================= */

    /**
     * دریافت فقط شمارهٔ موبایل؛ ساخت/به‌روزرسانی پیش‌نویس و ارسال کد ۶ رقمی.
     * (لندینگ «ثبت‌نام سریع» و صفحهٔ گام اول هر دو همین اندپوینت را صدا می‌زنند.)
     */
    public function sendPhoneOtp(Request $request): JsonResponse
    {
        $this->validate($request, [
            'mobile' => 'required|digits:11|regex:/(09)[0-9]{9}/',
        ], [
            'mobile.required' => 'شمارهٔ موبایل را وارد کنید',
            'mobile.digits' => 'شمارهٔ موبایل نادرست است',
            'mobile.regex' => 'شمارهٔ موبایل نادرست است',
        ]);

        $mobile = (string) $request->input('mobile');

        // فاز ۱۱۱ — بررسی شمارهٔ تکراری با تفکیک وضعیت:
        //  ۱) ثبت‌نام تکمیل + فروشندهٔ تعلیق‌شده ⇒ پیام تعلیق (بدون اجازهٔ ادامه)
        //  ۲) ثبت‌نام تکمیل + فروشندهٔ فعال ⇒ status=exists؛ فرانت مودال دو‌دکمه‌ای
        //     «رفتن به صفحهٔ ورود / تغییر شماره» را باز می‌کند
        //  ۳) ثبت‌نام نیمه‌کاره (حساب ساخته شده ولی مدارک/پایان باقی است) ⇒ همیشه کد
        //     تأیید تازه لازم است؛ پس از تأیید، دقیقاً به همان مرحله برمی‌گردد
        //  ۴) بدون حساب ولی با پیش‌نویس پیش‌رفته ⇒ ادامهٔ مستقیم از همان مرحله
        //     (همان رفتار صفحهٔ ثبت‌نام؛ پیامک بیهوده نمی‌شود)
        $seller = Seller::where('mobile', $mobile)->first();

        if ($seller && $seller->status_register === 'complete') {
            if ($seller->status !== 'ACTIVE') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'حساب فروشنده با این شماره موبایل تعلیق شده است؛ برای پیگیری با پشتیبانی تماس بگیرید.',
                ]);
            }

            return response()->json([
                'status' => 'exists',
                'message' => 'فروشنده با این شماره از قبل ثبت‌نام کرده است.',
                'login_url' => route('seller.login'),
            ]);
        }

        $draft = SellerRegistration::firstOrNew(['mobile' => $mobile]);
        $draft->token = $draft->token ?: SellerRegistration::newToken();

        // ۳) حساب نیمه‌کاره — تأیید موبایل اجباری، بعد ادامه از مرحلهٔ دقیق
        if ($seller) {
            $draft->step = 'otp';
            $draft->save();

            $this->issueOtp($mobile);

            session([
                SellerRegistrationFlow::SESSION_VERIFICATION => $mobile,
                SellerRegistrationFlow::SESSION_DRAFT => $draft->id,
            ]);
            $this->queueDraftCookie($draft);

            return response()->json([
                'status' => 'success',
                'redirect' => route('seller.registration_mobile'),
            ]);
        }

        // ۴) پیش‌نویس پیش‌رفتهٔ بدون حساب — تأیید موبایل تازه لازم است (فرانت آن را
        //     درخواست کرده) ولی مرحلهٔ پیش‌نویس دست‌نخورده می‌ماند تا دقیقاً از همان‌جا ادامه شود
        if ($draft->exists && $draft->step !== 'otp') {
            $this->issueOtp($mobile);

            session([
                SellerRegistrationFlow::SESSION_VERIFICATION => $mobile,
                SellerRegistrationFlow::SESSION_DRAFT => $draft->id,
            ]);
            $this->queueDraftCookie($draft);

            return response()->json([
                'status' => 'success',
                'redirect' => route('seller.registration_mobile'),
            ]);
        }

        // جریان تازه — ارسال کد و رفتن به صفحهٔ کد تأیید
        $this->issueOtp($mobile);

        $draft->step = 'otp';
        $draft->save();

        session([
            SellerRegistrationFlow::SESSION_VERIFICATION => $mobile,
            SellerRegistrationFlow::SESSION_DRAFT => $draft->id,
        ]);
        $this->queueDraftCookie($draft);

        return response()->json([
            'status' => 'success',
            'redirect' => route('seller.registration_mobile'),
        ]);
    }

    /** ساخت ردیف new_sellers برای موبایل و ارسال کد ۶ رقمی (فاز ۱۱۱ — مشترک بین مسیرها) */
    private function issueOtp(string $mobile): void
    {
        $code = random_int(100000, 999999);

        $newSeller = NewSeller::firstOrNew(['mobile' => $mobile]);
        $newSeller->code_mobile_verification = $code;
        $newSeller->save();

        Notification::send($newSeller, new SellerPhoneOtp($code));
    }

    /* ================= گام ۲ — تأیید کد ۶ رقمی ================= */

    public function verifyPhoneOtp(Request $request): JsonResponse
    {
        $this->validate($request, [
            'mobile' => 'required|digits:11|regex:/(09)[0-9]{9}/',
            'verify_code' => 'required|digits:6',
        ], [
            'verify_code.required' => 'کد تأیید ۶ رقمی را وارد کنید',
            'verify_code.digits' => 'کد تأیید ۶ رقمی است',
        ]);

        $mobile = (string) $request->input('mobile');

        $newSeller = NewSeller::where('mobile', $mobile)->first();

        if (! $newSeller) {
            return response()->json([
                'status' => 'error',
                'message' => 'نشست تأیید منقضی شده است؛ دوباره تلاش کنید.',
                'redirect' => route('seller.registration'),
            ]);
        }

        $expired = $newSeller->updated_at->lt(now()->subMinutes(SellerRegistrationFlow::CODE_TTL_MINUTES));

        if ($expired || (string) $newSeller->code_mobile_verification !== (string) $request->input('verify_code')) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد وارد شده اشتباه است یا منقضی شده است',
            ]);
        }

        $draft = SellerRegistration::firstOrNew(['mobile' => $mobile]);
        $draft->token = $draft->token ?: SellerRegistration::newToken();

        // فاز ۱۱۱ — رزومهٔ دقیق: اگر حساب فروشنده از قبل ساخته شده (ثبت‌نام نیمه‌کاره)،
        // مرحلهٔ پیش‌نویس باید با وضعیت واقعی حساب هم‌تراز شود تا کاربر دقیقاً به
        // همان مرحله برود؛ برای پیش‌نویس‌های تازه، از گام وضعیت (حقیقی/حقوقی) ادامه می‌دهیم.
        // پیش‌نویسی که خودش جلوتر رفته (identity/business) دست‌نخورده می‌ماند.
        $seller = Seller::where('mobile', $mobile)->first();

        if ($seller) {
            if ($seller->status_register === 'complete') {
                // تکمیل‌شده بین ارسال و تأیید کد (یا نشست قدیمی) — ورود، نه ادامهٔ ثبت‌نام
                session()->forget(SellerRegistrationFlow::SESSION_VERIFICATION);

                return response()->json([
                    'status' => 'exists',
                    'message' => 'فروشنده با این شماره از قبل ثبت‌نام کرده است.',
                    'login_url' => route('seller.login'),
                ]);
            }

            $draft->step = $seller->status_register === 'documents' ? 'checkout' : 'documents';
        } elseif (! $draft->reached('password')) {
            // فاز ۱۱۲ — جریان تازه بعد از تأیید کد، اول رمز عبور را تنظیم می‌کند؛
            // پیش‌نویس‌هایی که قبلاً رمز را ست کرده‌اند (یا جلوترند) مرحله‌شان
            // دست‌نخورده می‌ماند تا رمز دوباره از آنها پرسیده نشود
            $draft->step = 'password';
        }

        $draft->save();

        session([SellerRegistrationFlow::SESSION_DRAFT => $draft->id]);
        session()->forget(SellerRegistrationFlow::SESSION_VERIFICATION);
        $this->queueDraftCookie($draft);

        // فاز ۱۱۱ — مقصد دقیقاً مرحلهٔ فعلی پیش‌نویس است (برای فروشندهٔ نیمه‌کارهٔ
        // با حساب ساخته‌شده، همین‌جا به مدارک/تسویه هدایت می‌شود)
        return response()->json([
            'status' => 'success',
            'redirect' => SellerRegistrationFlow::resumeUrl($draft),
        ]);
    }

    /* ================= ارسال مجدد کد (گیت ۲ دقیقه‌ای سروری) ================= */

    public function resendPhoneOtp(Request $request): JsonResponse
    {
        $mobile = (string) $request->input('mobile');

        $newSeller = NewSeller::where('mobile', $mobile)->first();

        if (! $newSeller) {
            return response()->json([
                'status' => 'error',
                'redirect' => route('seller.registration'),
            ]);
        }

        // تایمر ۲ دقیقه‌ای از updated_at دیتابیس — با رفرش صفحه از دست نمی‌رود
        if ($newSeller->updated_at->diffInSeconds(now()) < SellerRegistrationFlow::RESEND_SECONDS - 1) {
            return response()->json([
                'status' => 'errorTime',
                'message' => 'برای ارسال مجدد کد کمی صبر کنید',
            ]);
        }

        if (option('sms_to_verify_user', 'on') == 'on') {
            $code = random_int(100000, 999999);
            $newSeller->code_mobile_verification = $code;
            $newSeller->save();

            Notification::send($newSeller, new SellerPhoneOtp($code));
        }

        return response()->json([
            'status' => 'success',
        ]);
    }

    /* ================= گام ۳ — تنظیم رمز عبور (فاز ۱۱۲) ================= */

    /**
     * بعد از تأیید کد موبایل، فقط رمز عبور و تکرارش جمع می‌شود و هش‌شده در
     * پیش‌نویس ذخیره می‌شود؛ در ساخت حساب مستقیم استفاده می‌شود. فروشنده‌ای که
     * ثبت‌نام را نیمه‌کاره رها کند و بعداً با کد برگردد، این گام را دوباره نمی‌بیند
     * (پیش‌نویس جلوتر از password رفته) و دقیقاً به مرحلهٔ خودش می‌رود.
     */
    public function savePassword(Request $request): JsonResponse|RedirectResponse
    {
        $draft = $this->requireDraft();

        if (! $draft instanceof SellerRegistration) {
            return $draft;
        }

        if (($blocked = $this->guardStep($draft, 'password')) !== null) {
            return $blocked;
        }

        $this->validate($request, [
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required' => 'رمز عبور را وارد کنید',
            'password.min' => 'رمز عبور حداقل ۸ کاراکتر باشد',
            'password.confirmed' => 'تکرار رمز عبور با رمز عبور یکسان نیست',
        ]);

        $draft->update([
            'password' => Hash::make((string) $request->input('password')),
            'step' => 'type',
        ]);

        return response()->json([
            'status' => 'success',
            'redirect' => route('seller.registration_type'),
        ]);
    }

    /* ================= گام ۴ — وضعیت فروشنده (حقیقی/حقوقی) ================= */

    public function saveType(Request $request): JsonResponse|RedirectResponse
    {
        $draft = $this->requireDraft();

        if (! $draft instanceof SellerRegistration) {
            return $draft;
        }

        if (($blocked = $this->guardStep($draft, 'type')) !== null) {
            return $blocked;
        }

        $this->validate($request, [
            'type' => 'required|in:'.SellerRegistration::TYPE_REAL.','.SellerRegistration::TYPE_LEGAL,
        ], [
            'type.required' => 'وضعیت فروشنده را انتخاب کنید',
            'type.in' => 'وضعیت انتخاب‌شده معتبر نیست',
        ]);

        $draft->update([
            'type' => $request->input('type'),
            'step' => 'identity',
        ]);

        return response()->json([
            'status' => 'success',
            'redirect' => route('seller.registration_identity'),
        ]);
    }

    /* ================= گام ۴ — اطلاعات تکمیلی (کد ملی و…) ================= */

    public function saveIdentity(Request $request): JsonResponse|RedirectResponse
    {
        $draft = $this->requireDraft();

        if (! $draft instanceof SellerRegistration) {
            return $draft;
        }

        if (($blocked = $this->guardStep($draft, 'identity')) !== null) {
            return $blocked;
        }

        $isLegal = $draft->type === SellerRegistration::TYPE_LEGAL;

        // فاز ۱۱۷ — کد ملی ایرانی با الگوریتم رسمی کنترل رقم (iran_national_code؛
        // ارقام یکسان هم رد می‌شود) و شناسهٔ ملی حقوقی (ir_company_id) اعتبارسنجی
        // می‌شوند؛ ارقام فارسی خودکار به انگلیسی تبدیل می‌شوند
        $rules = [
            'national_code' => ['required', 'iran_national_code'],
            'first_name' => 'required|persian_alpha',
            'last_name' => 'required|persian_alpha',
            'ownership_confirm' => 'accepted',
            'info_correct' => 'accepted',
        ];

        if (! $isLegal) {
            $rules['national_code'][] = 'unique:sellers_info,national_identity_number';
            $rules['gender'] = 'required|in:male,female';
            $rules['birth_year'] = 'required';
            $rules['birth_month'] = 'required';
            $rules['birth_day'] = 'required';
        } else {
            $rules['company_name'] = 'required|persian_alpha';
            $rules['company_type'] = 'required';
            $rules['company_registration_number'] = 'required';
            $rules['company_national_identity_number'] = 'required|ir_company_id';
            $rules['company_economic_number'] = 'nullable|digits:12';
        }

        $this->validate($request, $rules, [
            'national_code.required' => 'کد ملی را وارد کنید',
            'national_code.iran_national_code' => 'کد ملی وارد شده معتبر نیست؛ کد ملی ۱۰ رقمی را مطابق کارت ملی بررسی کنید',
            'national_code.unique' => 'کدملی قبلا ثبت شده',
            'first_name.required' => 'نام را وارد کنید',
            'last_name.required' => 'نام خانوادگی را وارد کنید',
            'gender.required' => 'جنسیت را انتخاب کنید',
            'company_name.required' => 'نام شرکت را وارد کنید',
            'company_national_identity_number.required' => 'شناسهٔ ملی شرکت را وارد کنید',
            'company_national_identity_number.ir_company_id' => 'شناسهٔ ملی شرکت معتبر نیست؛ شناسهٔ ملی ۱۱ رقمی حقوقی را بررسی کنید',
            'company_economic_number.digits' => 'شمارهٔ اقتصادی ۱۲ رقم است',
            'ownership_confirm.accepted' => 'برای ادامه باید تأیید کنید شمارهٔ موبایل و کد ملی متعلق به یک نفر است',
            'info_correct.accepted' => 'برای ادامه باید صحت اطلاعات را تأیید کنید',
        ]);

        $nationalCode = en_digits(trim((string) $request->input('national_code')));
        $warning = null;

        // احراز هویت فینوتک (شاهکار): فقط وقتی از تنظیمات فعال، کلیدها پر و
        // سرویس در دسترس است؛ جواب صریح «مخالف» مسدودکننده است.
        $kycStatus = 'skip';

        if (Finnotech::ready()) {
            $result = Finnotech::mobileOwnershipMatches($draft->mobile, $nationalCode);

            if ($result === Finnotech::MISMATCH) {
                return response()->json([
                    'status' => 'error',
                    'message' => Finnotech::mismatchMessage(),
                ]);
            }

            if ($result === Finnotech::MATCH) {
                $kycStatus = 'verified';
            } else {
                $kycStatus = 'unavailable';
                $warning = 'سرویس احراز هویت در دسترس نبود؛ ثبت‌نام ادامه یافت. اطلاعات شما در بررسی مدارک کنترل می‌شود.';
            }
        }

        $draft->update([
            'national_code' => $nationalCode,
            'first_name' => (string) $request->input('first_name'),
            'last_name' => (string) $request->input('last_name'),
            'gender' => $isLegal ? $draft->gender : (string) $request->input('gender'),
            'birth_date' => $isLegal
                ? $draft->birth_date
                : $request->input('birth_year').'/'.$request->input('birth_month').'/'.$request->input('birth_day'),
            'company_name' => $isLegal ? (string) $request->input('company_name') : $draft->company_name,
            'company_type' => $isLegal ? (string) $request->input('company_type') : $draft->company_type,
            'company_registration_number' => $isLegal ? (string) $request->input('company_registration_number') : $draft->company_registration_number,
            'company_national_identity_number' => $isLegal ? (string) $request->input('company_national_identity_number') : $draft->company_national_identity_number,
            'company_economic_number' => $isLegal ? (string) $request->input('company_economic_number') : $draft->company_economic_number,
            'kyc_status' => $kycStatus,
            'kyc_checked_at' => $kycStatus === 'verified' ? now() : $draft->kyc_checked_at,
            'step' => 'business',
        ]);

        return response()->json([
            'status' => 'success',
            'warning' => $warning,
            'redirect' => route('seller.registration_business_details'),
        ]);
    }

    /* ================= گام ۵ — اطلاعات کسب‌وکار (ساخت حساب) ================= */

    public function saveBusiness(Request $request): JsonResponse|RedirectResponse
    {
        $draft = $this->requireDraft();

        if (! $draft instanceof SellerRegistration) {
            return $draft;
        }

        if (($blocked = $this->guardStep($draft, 'business')) !== null) {
            return $blocked;
        }

        // اگر حساب قبلاً ساخته شده (رفتن/برگشتن به این گام) → ادامهٔ جریان
        $existing = Seller::where('mobile', $draft->mobile)->first();

        if ($existing) {
            return response()->json([
                'status' => 'success',
                'redirect' => $existing->status_register === 'documents'
                    ? route('seller.registration_checkout')
                    : route('seller.registration_documents'),
            ]);
        }

        $isLegal = $draft->type === SellerRegistration::TYPE_LEGAL;

        $rules = [
            'email' => 'required|email|unique:sellers',
            'state_id' => 'required',
            'city_id' => 'required',
            'address' => 'required|'.($isLegal ? 'persian_alpha_num' : 'persian_alpha_eng_num'),
            'post_code' => $isLegal ? 'required' : 'required|digits:10',
            'phone' => $isLegal ? 'required' : 'required|digits:11',
            'business_name' => 'required|unique:sellers_info|'.($isLegal ? 'persian_alpha_num' : 'persian_alpha_eng_num'),
            'shaba_number' => 'required',
            'main_supply_category_id' => 'required',
            'number_of_products' => 'required',
            'econtract' => 'required',
        ];

        // فاز ۱۱۳ — فیلد رمز عبور از گام کسب‌وکار حذف شد؛ رمز حتماً باید در گام
        // «تنظیم رمز عبور» ست شده باشد. برای پیش‌نویس‌های خیلی قدیمیِ قبل از فاز ۱۱۲
        // (بدون رمز ولی رسیده به این گام) رمز تصادفی ساخته می‌شود — فروشنده می‌تواند
        // با روش دوم ورود (موبایل + کد تأیید، فاز ۱۱۲) وارد شود.
        if (! $draft->password) {
            $draft->password = Hash::make(Str::password(12));
            $draft->save();
        }

        $this->validate($request, $rules, [
            'email.required' => 'ایمیل را وارد کنید',
            'email.unique' => 'این ایمیل قبلاً در سیستم ثبت شده است',
            'state_id.required' => 'استان را انتخاب کنید',
            'city_id.required' => 'شهر را انتخاب کنید',
            'address.required' => 'آدرس را وارد کنید',
            'post_code.required' => 'کد پستی را وارد کنید',
            'post_code.digits' => 'کد پستی ۱۰ رقم است',
            'phone.required' => 'تلفن ثابت را وارد کنید',
            'phone.digits' => 'تلفن ثابت باید ۱۱ رقم با کد شهر باشد',
            'business_name.required' => 'نام فروشگاه را وارد کنید',
            'business_name.unique' => 'نام فروشگاه قبلا انتخاب شده',
            'shaba_number.required' => 'شمارهٔ شبا را وارد کنید',
            'main_supply_category_id.required' => 'دستهٔ اصلی تأمین کالا را انتخاب کنید',
            'number_of_products.required' => 'تعداد محصولات قابل ارائه را انتخاب کنید',
            'econtract.required' => 'پذیرش قرارداد الکترونیکی الزامی است',
        ]);

        $newSeller = NewSeller::where('mobile', $draft->mobile)->first();

        if (! $newSeller) {
            return response()->json([
                'status' => 'error',
                'message' => 'نشست ثبت‌نام منقضی شده است؛ دوباره وارد شوید.',
                'redirect' => route('seller.registration'),
            ]);
        }

        // مثل جریان قدیمی: ایمیل روی new_sellers می‌نشیند؛ رمز از گام «تنظیم رمز
        // عبور» (هش‌شده در پیش‌نویس) می‌آید
        $newSeller->email = (string) $request->input('email');
        $newSeller->save();

        $slug = $this->generateUniqueSlug((string) $request->input('business_name'));

        $seller = new Seller;
        $seller->slug = $slug;
        $seller->email = $newSeller->email;
        $seller->mobile = $draft->mobile;
        // فاز ۱۱۲/۱۱۳ — رمزِ ست‌شدهٔ هش‌شده از گام رمز عبور (برای پیش‌نویس قدیمیِ
        // بدون رمز، بالا تصادفی ساخته شد)
        $seller->password = $draft->password;
        $seller->mobile_verification = 'YES';
        $seller->remember_token = Str::random(60);
        $seller->save();

        $sellerInfo = new SellerInfo;
        $sellerInfo->seller_id = $seller->id;
        $sellerInfo->private_business = $isLegal ? 'business' : 'private';
        $sellerInfo->first_name = $draft->first_name;
        $sellerInfo->last_name = $draft->last_name;
        $sellerInfo->birth_day = $draft->birth_date;
        $sellerInfo->gender = $draft->gender;
        $sellerInfo->identity_card_number = null; // ستون int است و کد ملی ۱۰ رقمی جا نمی‌شود
        $sellerInfo->national_identity_number = $draft->national_code;

        $sellerInfo->company_name = $draft->company_name;
        $sellerInfo->company_type = $draft->company_type;
        $sellerInfo->company_registration_number = $draft->company_registration_number;
        $sellerInfo->company_national_identity_number = $draft->company_national_identity_number;
        $sellerInfo->company_economic_number = $draft->company_economic_number;

        $sellerInfo->state_id = $request->input('state_id');
        $sellerInfo->city_id = $request->input('city_id');
        $sellerInfo->address = $request->input('address');
        $sellerInfo->phone = $request->input('phone');
        $sellerInfo->post_code = $request->input('post_code');
        $sellerInfo->location = (string) $request->input('lat_and_long', '');
        $sellerInfo->mobile = $draft->mobile;

        $sellerInfo->business_name = $request->input('business_name');
        $sellerInfo->shaba_number = $request->input('shaba_number');
        $sellerInfo->main_supply_category_id = $request->input('main_supply_category_id');
        $sellerInfo->number_of_products = $request->input('number_of_products');
        $sellerInfo->econtract = $request->input('econtract');
        $sellerInfo->save();

        $wallet = new Wallet;
        $wallet->seller_id = $seller->id;
        $wallet->save();

        $newSeller->delete();

        $draft->update([
            'email' => $request->input('email'),
            'step' => 'documents',
        ]);

        // سشن پیش‌نویس دست‌نخورده می‌ماند (رفرش/برگشت ← ادامه از مدارک)؛
        // کوکی توکن هم پشتیبان گم‌شدن سشن است.
        session()->put('show-seller-documents', $draft->mobile);
        // سازگاری با جریان قدیمی/لاگین مجدد
        session()->put('show-seller-business-details', $draft->mobile);

        return response()->json([
            'status' => 'success',
            'redirect' => route('seller.registration_documents'),
        ]);
    }

    /* ================= هلپرهای مشترک ================= */

    /**
     * پیش‌نویس فعال را برمی‌گرداند؛ نبودش ریدایرکت به گام اول (برای JSON هم
     * ریدایرکت معنا ندارد → خطا با مقصد).
     */
    private function requireDraft(): SellerRegistration|JsonResponse
    {
        $draft = SellerRegistrationFlow::currentDraft();

        if (! $draft || $draft->step === 'complete') {
            return response()->json([
                'status' => 'error',
                'message' => 'نشست ثبت‌نام یافت نشد؛ از ابتدا شروع کنید.',
                'redirect' => route('seller.registration'),
            ]);
        }

        return $draft;
    }

    /**
     * گیت ترتیب مراحل: اگر پیش‌نویس هنوز به این گام نرسیده، به مرحلهٔ فعلی
     * خودش برمی‌گردد (نمی‌شود با آدرسِ مرورگر جلوتر پرید).
     */
    private function guardStep(SellerRegistration $draft, string $minStep): ?JsonResponse
    {
        if (! $draft->reached($minStep)) {
            return response()->json([
                'status' => 'error',
                'message' => 'ابتدا مراحل قبلی را تکمیل کنید',
                'redirect' => SellerRegistrationFlow::resumeUrl($draft),
            ]);
        }

        return null;
    }

    /** اسلاگ یکتا برای نام فروشگاه (همان هلپر جریان قدیمی) */
    private function generateUniqueSlug(string $businessName): string
    {
        $slug = sluggable_helper_function($businessName) ?: trim($businessName);

        if (Seller::where('slug', $slug)->exists()) {
            $lastId = Seller::latest()->first()->id ?? 0;

            return $slug.($lastId + 1);
        }

        return $slug;
    }

    /** کوکی توکن بازیابی پیش‌نویس (۷ روز — سرورخوانده، رمزنگاری استاندارد) */
    private function queueDraftCookie(SellerRegistration $draft): void
    {
        cookie()->queue(
            SellerRegistrationFlow::COOKIE_TOKEN,
            (string) $draft->token,
            60 * 24 * 7
        );
    }
}

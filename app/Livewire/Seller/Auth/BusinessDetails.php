<?php

namespace App\Livewire\Seller\Auth;

use App\Models\Category;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerEcontract;
use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * گام ۶ ثبت‌نام — فاز ۱۱۰: اطلاعات کسب‌وکار و ورود (ایمیل + آدرس + فروشگاه).
 * اطلاعات هویتی از گام قبلی (پیش‌نویس) می‌آید و اینجا حساب
 * Seller ساخته می‌شود (اندپوینت جدید seller.registration_business_store).
 *
 * فاز ۱۱۳: فیلد رمز عبور از این گام حذف شد (رمز در گام «تنظیم رمز عبور» بعد
 * از کد تأیید گرفته می‌شود)؛ دستهٔ اصلی تأمین کالا اصلاح شد — کوئری قبلی
 * where('category_id', null) در SQL هیچ‌وقت true نمی‌شود و لیست همیشه خالی
 * بود؛ اکنون whereNull + فقط منتشرشدهٔ type=productcat با ستون title.
 */
#[Title('اطلاعات کسب‌وکار')]
#[Layout('sellers.layouts.guest')]
class BusinessDetails extends Component
{
    public ?array $seller = null;

    /** ایمیل ذخیره‌شده در پیش‌نویس (برای برگشت به این گام) */
    public ?string $draftEmail = null;

    /** وضعیت انتخابی (حقیقی/حقوقی) از پیش‌نویس */
    public ?string $draftType = null;

    /** قرارداد الکترونیکی (رکورد ۱ — مثل قدیمی) */
    public ?object $econtract = null;

    public $provinces;

    public $categories;

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $draft = SellerRegistrationFlow::currentDraft();

        if (! $draft || ! $draft->reached('business')) {
            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        // حساب قبلاً ساخته شده؟ → ادامهٔ جریان (مدارک/پایان)
        $existing = Seller::where('mobile', $draft->mobile)->first();

        if ($existing) {
            session()->put('show-seller-documents', $draft->mobile);
            $this->redirect(
                $existing->status_register === 'documents'
                    ? route('seller.registration_checkout')
                    : route('seller.registration_documents'),
                navigate: true
            );

            return;
        }

        $this->seller = ['mobile' => $draft->mobile];
        $this->draftEmail = $draft->email;
        $this->draftType = $draft->type;
        $this->econtract = SellerEcontract::find(1);
        $this->provinces = Province::all();

        // فاز ۱۱۳ — اصلاح لیست خالی دسته‌بندی‌ها: category_id ستون nullable است و
        // مقایسهٔ = NULL در SQL همیشه false است؛ والد‌ها (دستهٔ ریشه) با whereNull
        // گرفته می‌شوند و عنوان دسته‌بندی هم ستون title است (نه name).
        $this->categories = Category::query()
            ->whereNull('category_id')
            ->where('published', 1)
            ->where('type', 'productcat')
            ->orderBy('ordering')
            ->get(['id', 'title']);
    }

    public function render()
    {
        return view('sellers.auth.business-details');
    }
}

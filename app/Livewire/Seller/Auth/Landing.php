<?php

namespace App\Livewire\Seller\Auth;

use App\Models\SellerCommission;
use App\Models\SellerHero;
use App\Models\SellerQuestion;
use App\Support\MultiVendorSettings;
use App\Support\SellerLandingContent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * لندینگ «فروشنده شوید» (/seller).
 *
 * فاز ۱۰۸: پورت صفحهٔ قدیمی به پنل مدرن.
 * فاز ۱۰۹: صفحهٔ تک‌صفحه‌ای کامل (hero + پلن‌ها + آمار + چرا ما + مراحل +
 * تجربهٔ فروشندگان + CTA + سوالات متداول) با محتوای قابل شخصی‌سازی از
 * پنل مدیریت (فروشندگان ← تنظیمات فروشندگان ← صفحهٔ فرود فروشندگان).
 *
 * سوییچ seller_landing_page (تب چندفروشندگی برنامه‌نویس — قانون طلایی):
 * خاموش ⇒ نسخهٔ سادهٔ فاز ۱۰۸ (landing-simple) سرو می‌شود.
 */
#[Title('فروشنده شوید')]
#[Layout('sellers.layouts.guest')]
class Landing extends Component
{
    public $sellersHeroes;

    public $sellersCommissions;

    public $sellersQuestions;

    /** محتوای ادیت‌شدهٔ ادمین (ادغام‌شده با پیش‌فرض‌ها) */
    public array $landingContent = [];

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        // مثل قدیمی: ورود به لندینگ سشن‌های ثبت‌نام نیمه‌کاره را پاک می‌کند
        session()->forget([
            'show-seller-business-details',
            'show-seller-verification-form',
            'show-seller-documents',
            'show-seller-checkout',
        ]);

        // لندینگ تمام‌عرض می‌شود (بدون ستون برند) و عریض‌تر از صفحات فرم است
        view()->share('sellerGuestHideAside', true);
        view()->share('sellerGuestMaxWidth', 'max-w-6xl');

        $this->sellersHeroes = SellerHero::all();
        $this->sellersCommissions = SellerCommission::all();
        $this->sellersQuestions = SellerQuestion::all();
        $this->landingContent = SellerLandingContent::get();
    }

    public function render()
    {
        if (! MultiVendorSettings::landingPageEnabled()) {
            // سوییچ خاموش ⇒ نسخهٔ سادهٔ فاز ۱۰۸ (قانون طلایی)
            return view('sellers.auth.landing-simple');
        }

        return view('sellers.auth.landing', [
            'stats' => SellerLandingContent::resolvedStats($this->landingContent),
        ]);
    }
}

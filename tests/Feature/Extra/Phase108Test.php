<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۱۰۸ — پنل مدرن فروشندگان + جداسازی پنل فروشنده از تم.
 * --------------------------------------------------------------------------
 *  • کل مسیرهای پنل فروشنده (ثبت‌نام/ورود/پنل) از تم به هسته منتقل شد:
 *    routes/seller.php + App\Http\Controllers\Seller + resources/views/sellers-old.
 *    تعویض قالب دیگر هیچ تأثیری روی پنل فروشنده ندارد.
 *  • سوییچ seller_panel_modern (تب چندفروشندگی برنامه‌نویس — قانون طلایی):
 *    روشن ⇒ صفحات GET از App\Livewire\Seller (لایوایر ۴ — SPA با wire:navigate)
 *    سرو می‌شوند؛ خاموش ⇒ پنل قدیمی (sellers-old) سرجایش است.
 *    اندپوینت‌های POST/داده‌ای در هر دو حالت همان کنترلرهای منتقل‌شدهٔ app هستند.
 *  • سوییچ «سیستم چند فروشندگی» از تنظیمات دیگر حذف شد (مالکیت واحد: برنامه‌نویس).
 */
class Phase108Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();

        // پیش‌فرض فاز ۱۰۸: پنل مدرن روشن، چندفروشندگی خاموش (تست‌ها دستی فعال می‌کنند)
        option_update(MultiVendorSettings::KEY_MODERN_PANEL, '1');
        option_update(MultiVendorSettings::KEY_ENABLED, 'false');
        MultiVendorSettings::flushCache();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        option_update(MultiVendorSettings::KEY_MODERN_PANEL, '1');
        option_update(MultiVendorSettings::KEY_ENABLED, 'false');
        MultiVendorSettings::flushCache();
        Cache::flush();

        parent::tearDown();
    }

    private function enableVendor(): void
    {
        MultiVendorSettings::setEnabled(true);
        MultiVendorSettings::flushCache();
        Cache::flush();
    }

    private function newSeller(): Seller
    {
        $suffix = Str::random(8);

        $seller = Seller::create([
            'slug' => 'seller-'.$suffix,
            'email' => 'seller-'.$suffix.'@test.ir',
            'mobile' => '0913'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'password' => Hash::make('secret1234'),
            'status' => 'ACTIVE',
            'status_register' => 'complete',
            'status_documents' => 'Accept',
            'status_work' => 'ACTIVE',
        ]);

        SellerInfo::create([
            'seller_id' => $seller->id,
            'business_name' => 'کسب‌وکار '.$suffix,
            'first_name' => 'نام',
            'last_name' => $suffix,
            'state_id' => 1,
            'city_id' => 1,
            'address' => 'آدرس تست',
            'post_code' => '1234567890',
            'phone' => '02112345678',
            'mobile' => $seller->mobile,
            'shaba_number' => 'IR'.str_pad((string) random_int(1000000, 9999999), 24, '0', STR_PAD_LEFT),
            'main_supply_category_id' => 1,
            'number_of_products' => 0,
        ]);

        return $seller;
    }

    /* ================= ۱) جداسازی از تم ================= */

    #[Test]
    #[TestDox('پنل فروشنده مستقل از تم است: کنترلرها در app، ویوهای قدیمی در sellers-old و روت‌ها در routes/seller.php')]
    public function seller_panel_is_decoupled_from_theme(): void
    {
        // کنترلرهای پنل فروشنده در اپ‌لایه هستند (نه تم)
        $this->assertFileExists(app_path('Http/Controllers/Seller/SellerController.php'));
        $this->assertFileExists(app_path('Http/Controllers/Seller/WalletSellerController.php'));
        $this->assertFileExists(app_path('Http/Requests/SellerPanel/products/StoreProductRequest.php'));

        // ویوهای پنل قدیمی از تم خارج شده‌اند
        $this->assertFileExists(resource_path('views/sellers-old/panel/layouts/master.blade.php'));
        $this->assertFileExists(resource_path('views/sellers-old/auth/login.blade.php'));

        // روت‌های فروشنده مستقل از تم لود می‌شوند
        $this->assertFileExists(base_path('routes/seller.php'));

        // هیچ ارجاع front::sellers باقی نمانده باشد (ویوها/کنترلرها)
        $sellersOldViews = glob(resource_path('views/sellers-old').'/*/*.blade.php');
        $this->assertNotEmpty($sellersOldViews);
        foreach ($sellersOldViews as $view) {
            $content = (string) file_get_contents($view);
            $this->assertStringNotContainsString('front::sellers.', $content, basename($view).' هنوز به namespace تم اشاره می‌کند');
        }
    }

    #[Test]
    #[TestDox('مثل قدیمی: چندفروشندگی خاموش ⇒ صفحات فروشنده ۴۰۴ هستند')]
    public function vendor_disabled_pages_are_404(): void
    {
        // چندفروشندگی خاموش (پیش‌فرض)
        $this->get(route('seller.index'))->assertNotFound();
        $this->get(route('seller.login'))->assertNotFound();
    }

    /* ================= ۲) پنل مدرن (سوییچ روشن — پیش‌فرض) ================= */

    #[Test]
    #[TestDox('پنل مدرن: لندینگ و ورود فروشنده رندر می‌شوند')]
    public function modern_auth_pages_render(): void
    {
        $this->enableVendor();

        $landing = $this->get(route('seller.index'));
        $landing->assertOk();
        $landing->assertSee('فروشنده شوید', false);

        $login = $this->get(route('seller.login'));
        $login->assertOk();
        // فرم مدرن با fetch به همان اندپوینت قدیمی کار می‌کند
        $login->assertSee(route('seller.login_check'), false);
        $login->assertSee('ورود به پنل فروشندگان', false);
    }

    #[Test]
    #[TestDox('پنل مدرن: داشبورد لایوایر با آمار تسویه (کلاس vset-card) و لینک‌های منو رندر می‌شود')]
    public function modern_dashboard_renders(): void
    {
        $this->enableVendor();
        $seller = $this->newSeller();
        $this->actingAs($seller, 'seller');

        $response = $this->get(route('seller.dashboard'));
        $response->assertOk();

        // رشته‌های تست‌شدهٔ فاز ۱۰۳ — عیناً در پنل مدرن هم هست
        $response->assertSee('کمیسیون طبق جدول دسته‌بندی‌ها', false);
        $response->assertSee(route('seller.commission'), false);
        $response->assertSee('vset-card', false);

        // لایوت مدرن: سایدبار با لینک‌های ماژول‌ها (SPA با wire:navigate)
        $response->assertSee(route('seller.products.index'), false);
        $response->assertSee(route('seller.orders.index'), false);
        $response->assertSee(route('seller.wallet.index'), false);
        $response->assertSee(route('seller.statistics.index'), false);
    }

    #[Test]
    #[TestDox('پنل مدرن: صفحهٔ ورود فروشندهٔ لاگین‌شده به داشبورد ریدایرکت می‌شود')]
    public function modern_login_redirects_authenticated_seller(): void
    {
        $this->enableVendor();
        $seller = $this->newSeller();
        $this->actingAs($seller, 'seller');

        $this->get(route('seller.login'))->assertRedirect(route('seller.dashboard'));
    }

    /* ================= ۳) سوییچ پنل مدرن (قانون طلایی) ================= */

    #[Test]
    #[TestDox('تب چندفروشندگی برنامه‌نویس سوییچ پنل مدرن را ذخیره می‌کند')]
    public function developer_tab_saves_modern_panel_toggle(): void
    {
        Gate::after(function ($user = null) {
            return $user instanceof Admin && $user->level == 'creator';
        });

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۰۸',
            'username' => 'feat108-'.Str::random(6),
            'email' => 'feat108-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);
        $this->actingAs($creator, 'adminPanel');

        Livewire::test(Developer::class)
            ->set('vendorModernPanel', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option(MultiVendorSettings::KEY_MODERN_PANEL, '1'));

        Livewire::test(Developer::class)
            ->assertSet('vendorModernPanel', false)
            ->set('vendorModernPanel', true)
            ->call('saveMultiVendor');

        $this->assertSame('1', option(MultiVendorSettings::KEY_MODERN_PANEL, '1'));
    }

    #[Test]
    #[TestDox('سوییچ خاموش: پنل قدیمی (sellers-old) دوباره سرو می‌شود — بازگشت فوری روی تولید')]
    public function legacy_panel_serves_when_toggle_off(): void
    {
        $this->enableVendor();
        option_update(MultiVendorSettings::KEY_MODERN_PANEL, '0');
        MultiVendorSettings::flushCache();
        Cache::flush();

        $seller = $this->newSeller();
        $this->actingAs($seller, 'seller');

        // داشبورد قدیمی — همان ویو sellers-old با کلاس‌های قدیمی
        $response = $this->get(route('seller.dashboard'));
        $response->assertOk();
        $response->assertSee('کمیسیون طبق جدول دسته‌بندی‌ها', false);
        $response->assertSee(route('seller.commission'), false);
        $response->assertSee('vset-card', false);

        // لندینگ قدیمی
        $this->get(route('seller.index'))->assertOk();

        // اندپوینت POST مشترک — در هر دو حالت کنترلر منتقل‌شدهٔ app
        Auth::guard('seller')->logout();
        $this->get(route('seller.login'))->assertOk();
    }

    /* ================= ۴) حذف تب چندفروشندگی از تنظیمات دیگر ================= */

    #[Test]
    #[TestDox('تنظیمات دیگر (پنل جدید) دیگر سوییچ سیستم چندفروشندگی ندارد — مالکیت: برنامه‌نویس')]
    public function others_settings_page_has_no_vendor_switch(): void
    {
        $view = (string) file_get_contents(resource_path('views/admin-panel/livewire/admin/settings/others.blade.php'));

        $this->assertStringNotContainsString('multi_vendor_system_status', $view);
        $this->assertStringNotContainsString("tab === 'vendor'", $view);

        // کامپوننت هم دیگر این کلید را ذخیره نمی‌کند (مالکیت واحد تب چندفروشندگی)
        $component = (string) file_get_contents(app_path('Livewire/Admin/Settings/Others.php'));
        $this->assertStringNotContainsString('public bool $multi_vendor_system_status', $component);
        $this->assertStringNotContainsString('optionKeys [', $component);
    }

    #[Test]
    #[TestDox('کامپوننت تنظیمات دیگر پس از ذخیره، کلید multi_vendor_system_status را دست نمی‌زند')]
    public function others_component_keeps_vendor_key_untouched(): void
    {
        // بدنهٔ save: هیچ option_update('multi_vendor_system_status') نباید باشد
        $component = (string) file_get_contents(app_path('Livewire/Admin/Settings/Others.php'));

        $this->assertStringNotContainsString("option_update('multi_vendor_system_status'", $component);

        // خواندن mount هم حذف شده (دیگر مالکیت ندارد)
        $this->assertStringNotContainsString('$this->multi_vendor_system_status', $component);
    }
}

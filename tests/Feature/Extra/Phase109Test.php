<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\Sellers\Settings\SellerLanding;
use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\Seller;
use App\Support\MultiVendorSettings;
use App\Support\SellerLandingContent;
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
 * فاز ۱۰۹ — لندینگ تک‌صفحه‌ای فروشندگان + شخصی‌سازی ادمین + فیکس ارور Alpine.
 * --------------------------------------------------------------------------
 *  • صفحهٔ /seller لندینگ کامل شد (hero + پلن‌ها + آمار + چرا ما + مزایا +
 *    مراحل + تجربهٔ فروشندگان + CTA + سوالات متداول) — الهام از لندینگ مرجع.
 *  • محتوا از پنل مدیریت (فروشندگان ← تنظیمات فروشندگان ← صفحهٔ فرود) با کلید
 *    seller_landing_content (JSON در options) شخصی‌سازی می‌شود؛ آمارِ بدون عدد
 *    از دیتابیس واقعی محاسبه می‌شود.
 *  • سوییچ seller_landing_page (تب چندفروشندگی برنامه‌نویس — قانون طلایی):
 *    خاموش ⇒ نسخهٔ سادهٔ فاز ۱۰۸ (landing-simple) سرو می‌شود.
 *  • فیکس ارور JS ثبت‌نام/ورود: هندلرهای Alpine چندخطی به متد منتقل شدند
 *    (عبارت inline که با var شروع شود در evaluator جدید می‌شکند) — گارد ایستا.
 *  • پیش‌پرکردن موبایل ثبت‌نام از فرم هیروی لندینگ (?mobile=).
 */
class Phase109Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();

        option_update(MultiVendorSettings::KEY_MODERN_PANEL, '1');
        option_update(MultiVendorSettings::KEY_LANDING_PAGE, '1');
        option_update(MultiVendorSettings::KEY_ENABLED, 'false');
        option_update(SellerLandingContent::KEY, '');
        MultiVendorSettings::flushCache();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        option_update(MultiVendorSettings::KEY_MODERN_PANEL, '1');
        option_update(MultiVendorSettings::KEY_LANDING_PAGE, '1');
        option_update(MultiVendorSettings::KEY_ENABLED, 'false');
        option_update(SellerLandingContent::KEY, '');
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

    /** ادمین creator + گیت سراسری (الگوی Phase108) */
    private function actingCreator(): Admin
    {
        Gate::after(function ($user = null) {
            return $user instanceof Admin;
        });

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۰۹',
            'username' => 'feat109-'.Str::random(6),
            'email' => 'feat109-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);
        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /* ================= ۱) لندینگ کامل ================= */

    #[Test]
    #[TestDox('لندینگ کامل با بخش‌های پیش‌فرض رندر می‌شود (پلن‌ها/مراحل/تجربهٔ فروشندگان/سوالات)')]
    public function full_landing_renders_with_default_sections(): void
    {
        $this->enableVendor();

        $landing = $this->get(route('seller.index'));
        $landing->assertOk();

        // رشتهٔ سازگار با هر دو نسخه (Phase108 هم آن را گارد می‌کند)
        $landing->assertSee('فروشنده شوید', false);
        // بخش‌های مخصوص لندینگ کامل
        $landing->assertSee('مسیر شروع فروش', false);
        $landing->assertSee('فروشندهٔ حرفه‌ای', false);
        $landing->assertSee('تجربهٔ فروشندگان', false);
        $landing->assertSee('ثبت‌نام سریع', false);
        // فاز ۱۱۰ — ثبت‌نام سریع فقط موبایل است و مستقیم به اندپوینت ارسال کد وصل است
        $landing->assertSee(route('seller.registration_phone_send'), false);
    }

    #[Test]
    #[TestDox('سوییچ seller_landing_page خاموش ⇒ نسخهٔ سادهٔ فاز ۱۰۸ سرو می‌شود')]
    public function simple_landing_serves_when_toggle_off(): void
    {
        $this->enableVendor();
        $this->actingCreator(); // سوییچ در تب چندفروشندگی برنامه‌نویس است (گیت creator)

        Livewire::test(Developer::class)
            ->set('vendorLandingPage', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option(MultiVendorSettings::KEY_LANDING_PAGE, '1'));

        $landing = $this->get(route('seller.index'));
        $landing->assertOk();
        // مخصوص نسخهٔ ساده (همیشه رندر می‌شود — مستقل از دیتای SellerCommission)
        $landing->assertSee('فروشگاه اختصاصی خود را بسازید', false);
        $landing->assertDontSee('مسیر شروع فروش', false); // مخصوص لندینگ کامل
    }

    #[Test]
    #[TestDox('آمار بدون عدد از دیتابیس واقعی محاسبه می‌شود و ردیف بی‌عدد رندر نمی‌شود')]
    public function stats_fall_back_to_real_database_numbers(): void
    {
        $this->enableVendor();

        // محیط قطعی: بدون فروشنده، ردیف «فروشندهٔ فعال» اصلاً رندر نمی‌شود (صادقانه)
        Seller::query()->delete();

        $landing = $this->get(route('seller.index'));
        $landing->assertOk();
        $landing->assertDontSee('فروشندهٔ فعال', false);

        // با یک فروشندهٔ کامل، عدد واقعی با + نمایش داده می‌شود (fa-digits سمت کلاینت است)
        $this->newSellerForStats();
        $landing = $this->get(route('seller.index'));
        $landing->assertSee('فروشندهٔ فعال', false);
        $landing->assertSee('+1', false);
    }

    /* ================= ۲) شخصی‌سازی ادمین ================= */

    #[Test]
    #[TestDox('ادیتور ادمین محتوا را ذخیره می‌کند و لندینگ همان را نشان می‌دهد + بازگشت به پیش‌فرض')]
    public function admin_editor_saves_content_and_landing_reflects_it(): void
    {
        $this->enableVendor();
        $this->actingCreator();

        Livewire::test(SellerLanding::class)
            ->assertSet('heroTitle', SellerLandingContent::get()['hero']['title'])
            ->set('heroTitle', 'لندینگ اختصاصی تست فاز ۱۰۹')
            ->set('heroButton', 'بزن بریم')
            ->call('save')
            ->assertDispatched('toast');

        $stored = json_decode((string) option(SellerLandingContent::KEY, ''), true);
        $this->assertSame('لندینگ اختصاصی تست فاز ۱۰۹', $stored['hero']['title']);
        $this->assertSame('بزن بریم', $stored['hero']['button']);

        // لندینگ محتوای سفارشی را نشان می‌دهد
        $landing = $this->get(route('seller.index'));
        $landing->assertOk();
        $landing->assertSee('لندینگ اختصاصی تست فاز ۱۰۹', false);

        // بازگشت به پیش‌فرض
        Livewire::test(SellerLanding::class)
            ->call('resetDefaults')
            ->assertDispatched('toast');

        $this->assertSame('', (string) option(SellerLandingContent::KEY, ''));

        $landing = $this->get(route('seller.index'));
        $landing->assertSee(SellerLandingContent::get()['hero']['title'], false);
    }

    #[Test]
    #[TestDox('ادیتور ادمین فقط با چندفروشندگی فعال در دسترس است (خاموش ⇒ ۴۰۴) و در منو هست')]
    public function admin_editor_is_gated_by_vendor_system(): void
    {
        $this->actingCreator();

        // چندفروشندگی خاموش (پیش‌فرض) ⇒ ۴۰۴
        $this->get(route('np.settings.seller-landing'))->assertNotFound();

        $this->enableVendor();
        $this->get(route('np.settings.seller-landing'))->assertOk();

        // آیتم منو با گیت چندفروشندگی ثبت شده باشد
        $menu = (string) file_get_contents(config_path('admin-menu.php'));
        $this->assertStringContainsString('np.settings.seller-landing', $menu);
    }

    #[Test]
    #[TestDox('اعتبارسنجی ادیتور: عنوان خالی ذخیره نمی‌شود')]
    public function admin_editor_validates_required_fields(): void
    {
        $this->enableVendor();
        $this->actingCreator();

        Livewire::test(SellerLanding::class)
            ->set('heroTitle', '')
            ->call('save')
            ->assertHasErrors(['heroTitle' => 'required']);

        $this->assertSame('', (string) option(SellerLandingContent::KEY, ''));
    }

    /* ================= ۳) ثبت‌نام ================= */

    #[Test]
    #[TestDox('صفحهٔ ثبت‌نام موبایل ارسالی از لندینگ را پیش‌پر می‌کند')]
    public function registration_page_prefills_mobile(): void
    {
        $this->enableVendor();

        $page = $this->get(route('seller.registration', ['mobile' => '09123456789']));
        $page->assertOk();
        // فاز ۱۱۰ — پیش‌پر کردن با x-model انجام می‌شود (فرم تک‌فیلدی موبایل)
        $page->assertSee("mobile: '09123456789'", false);
    }

    /* ================= ۴) گارد ایستا: ارور Alpine ================= */

    #[Test]
    #[TestDox('هیچ عبارت Alpine در ویوهای sellers با var شروع نمی‌شود (ریشهٔ ارور JS فاز ۱۰۹)')]
    public function no_alpine_expression_starts_with_var_in_seller_views(): void
    {
        $files = glob(resource_path('views/sellers').'/**/*.blade.php') ?: [];
        $files = array_merge($files, glob(resource_path('views/sellers').'/*.blade.php') ?: []);
        $this->assertNotEmpty($files);

        // عبارت‌هایی که بعد از x-on/x-init/x-data مستقیماً با var شروع شوند در
        // evaluator جدید (‎__self.result = …‎) SyntaxError می‌دهند — مثل فاز ۱۰۸
        // که دکمهٔ ارسال مجدد کد و toggle تم را می‌شکست.
        foreach ($files as $file) {
            $content = (string) file_get_contents($file);

            // عبارت‌هایی که بلافاصله بعد از اتریبیوت با var شروع شوند در
            // evaluator جدید (‎__self.result = …‎) SyntaxError می‌دهند.
            // (var داخل بدنهٔ متدِ x-data مجاز است — فقط «شروع عبارت» گارد می‌شود)
            $this->assertSame(
                0,
                preg_match('/(?:x-on:[\w:.-]+|x-init|x-data|x-effect|x-show|x-model|@click|@submit(?:\.prevent)?)="\s*var\s/', $content),
                basename($file).' عبارت Alpine با var شروع می‌شود'
            );
        }
    }

    /* ================= پشتیبانی ================= */

    private function newSellerForStats(): void
    {
        Seller::create([
            'slug' => 'seller-stats-'.Str::random(6),
            'email' => 'stats-'.Str::random(6).'@test.ir',
            'mobile' => '0914'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'password' => Hash::make('secret1234'),
            'status' => 'ACTIVE',
            'status_register' => 'complete',
            'status_documents' => 'Accept',
            'status_work' => 'ACTIVE',
        ]);
    }
}

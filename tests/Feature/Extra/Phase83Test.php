<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\Settings\Others;
use App\Models\Admin;
use App\Models\NotificationManage;
use App\Models\NotificationManageUser;
use App\Models\PriceAlert;
use App\Services\ThemeAssetLinker;
use App\Services\ThemeAssetSync;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۸۳ — بازطراحی هشدار کاهش قیمت طبق بازخورد کاربر:
 *
 *  ۱) «دکمه هنوز در فرانت کار نمی‌کند» → ریشه: کپی assetهای تم روی هاستِ
 *     بدون-symlink بعد از git pull کهنه می‌ماند → ThemeAssetSync خودکار نوسازی می‌کند
 *  ۲) «مارجین راست دکمه را حذف کن» → کلاس مستقل dt-pa-btn با margin صریح (بدون کلاس‌های قدیمی)
 *  ۳) «شخصی‌سازی خیلی بیشتر مثل نمونهٔ CheckoutTurbo» → رنگ/اندازه/گردی/موقعیت/مودال/کانال‌ها
 *     در تب «قابلیت‌های فروشگاه» + پیش‌نمایش زنده
 *  ۴) «مودال مدرن با توضیح و انتخاب پیامک/اعلان پروفایل» → کنترلر کانال‌ها را می‌پذیرد
 *  ۵) «لیست مشتری‌های هشدار قیمت زیرمنوی اعلان‌ها وقتی فعال است» → منو با گیت enabled
 */
class Phase83Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();

        // کلیدهای قابلیت به حالت پیش‌فرض برمی‌گردند (تست مستقل از رکوردهای قبلی)
        foreach ([
            'dt_price_alert_status'            => '1',
            'dt_price_alert_button_text'       => '',
            'dt_price_alert_button_icon'       => '',
            'dt_price_alert_button_color'      => '',
            'dt_price_alert_button_size'       => '',
            'dt_price_alert_button_radius'     => '',
            'dt_price_alert_button_placement'  => '',
            'dt_price_alert_modal_title'       => '',
            'dt_price_alert_modal_desc'        => '',
            'dt_price_alert_channels'          => '',
        ] as $key => $value) {
            option_update($key, $value);
        }
        Cache::flush();
    }

    protected function tearDown(): void
    {
        option_update('dt_price_alert_status', '1');
        Cache::flush();

        parent::tearDown();
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'شخصی‌سازی هشدار',
            'username'   => 'feat83-'.Str::random(6),
            'email'      => 'feat83-'.Str::random(6).'@test.ir',
            'password'   => Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    /* ================= ۱) همگام‌سازی خودکار دارایی‌های تم ================= */

    #[Test]
    #[TestDox('کپی کهنهٔ assetهای تم بعد از تغییر منبع، خودکار نوسازی می‌شود (ریشهٔ «دکمه کار نمی‌کند»)')]
    public function stale_theme_asset_copy_is_refreshed_automatically(): void
    {
        $base = storage_path('app/testing/phase83-assets');
        $src  = $base.'/src';
        $link = $base.'/public-copy';

        File::deleteDirectory($base);
        File::ensureDirectoryExists($src.'/js');
        File::put($src.'/js/show.js', 'v1');

        // کپی واقعی با مارکر (شبیه‌سازی ThemeAssetLinker روی هاست بدون symlink)
        $mode = ThemeAssetLinker::linkOrCopy($src, $link, allowSymlink: false, allowExec: false);
        $this->assertSame('copy', $mode);
        $this->assertFileExists($link.'/js/show.js');
        $this->assertFileExists($link.'/.shoplink-copy');

        // تا وقتی منبع عوض نشده → fresh
        $this->assertSame('fresh', ThemeAssetSync::syncIfStale('X', srcOverride: $src, linkOverride: $link)['status']);

        // شبیه‌سازی git pull: فایل منبع جدیدتر می‌شود
        sleep(1);
        touch($src.'/js/show.js', time() + 5);
        File::put($src.'/js/show.js', 'v2');
        Cache::flush(); // مثل optimize:clear

        $result = ThemeAssetSync::syncIfStale('X', srcOverride: $src, linkOverride: $link);
        $this->assertSame('refreshed', $result['status']);
        $this->assertStringEqualsFile($link.'/js/show.js', 'v2', 'کپی باید با منبع همگام شده باشد');

        // مارکر فقط وقتی است که نوسازی دوباره «کپی» باشد؛ اگر هاست symlink بپذیرد
        // (مثل سندباکس) لینکر کپی را به symlink ارتقا می‌دهد — هر دو درست است
        $this->assertTrue(
            is_link($link) || is_file($link.'/.shoplink-copy'),
            'بعد از نوسازی باید یا مارکر کپی یا symlink سالم باشد'
        );

        // بعد از نوسازی → اگر کپی مانده: fresh؛ اگر به symlink ارتقا یافته: not-our-copy
        $status = ThemeAssetSync::syncIfStale('X', srcOverride: $src, linkOverride: $link)['status'];
        $this->assertContains($status, ['fresh', 'not-our-copy']);

        // symlink سالم یا پوشهٔ دست‌ساز ادمین (بدون مارکر) → دست نمی‌خورد
        if (is_link($link)) {
            @unlink($link); // فقط خودِ لینک — نه محتوای منبع
        } else {
            File::deleteDirectory($link);
        }

        File::ensureDirectoryExists($link.'/js');
        File::put($link.'/js/show.js', 'دست‌ساز ادمین');
        $this->assertSame('not-our-copy', ThemeAssetSync::syncIfStale('X', srcOverride: $src, linkOverride: $link)['status']);
        $this->assertStringEqualsFile($link.'/js/show.js', 'دست‌ساز ادمین');

        File::deleteDirectory($base);
    }

    #[Test]
    #[TestDox('هوک ThemeServiceProvider برای نوسازی خودکار ثبت شده است')]
    public function theme_service_provider_wires_the_asset_sync_hook(): void
    {
        $provider = File::get(base_path('themes/WeblakShop/src/ThemeServiceProvider.php'));

        $this->assertStringContainsString('ThemeAssetSync::syncIfStale', $provider,
            'بوت تم باید ThemeAssetSync را صدا بزند تا کپی کهنه خودکار تازه شود');
    }

    /* ================= ۲) دکمهٔ فرانت: مارجین و کلاس‌های مستقل ================= */

    #[Test]
    #[TestDox('دکمهٔ جدید با کلاس مستقل dt-pa-btn (بدون مارجین ارثی) در پارشال سایدبار رندر می‌شود')]
    public function sidebar_button_uses_standalone_class_without_inherited_margins(): void
    {
        $partial = File::get(base_path('themes/WeblakShop/src/resources/views/products/partials/product-info-sidebar.blade.php'));

        $this->assertStringContainsString('dt-pa-btn', $partial);
        $this->assertStringContainsString('dt_price_alert_btn', $partial, 'آی‌دی جدید پس از فاز ۸۳');
        $this->assertStringNotContainsString('cart-not-available w-100 mt-2', $partial,
            'کلاس‌های قدیمی با مارجین ارثی باید حذف شده باشند');

        $style = File::get(base_path('themes/WeblakShop/src/resources/views/products/show.blade.php'));
        $this->assertStringContainsString('margin:10px 0 0', $style, 'مارجین صریح (بدون راست/چپ) در CSS دکمه');
    }

    #[Test]
    #[TestDox('با قابلیت غیرفعال، دکمه در هیچ موقعیتی رندر نمی‌شود')]
    public function button_is_not_rendered_when_feature_disabled(): void
    {
        $partial = File::get(base_path('themes/WeblakShop/src/resources/views/products/partials/product-info-sidebar.blade.php'));

        $this->assertStringContainsString("\$paEnabled && \$paPlacement === 'above-cart'", $partial);
        $this->assertStringContainsString("\$paEnabled && \$paPlacement !== 'above-cart'", $partial);
    }

    /* ================= ۳) شخصی‌سازی گسترده در تنظیمات ================= */

    #[Test]
    #[TestDox('تنظیمات دیگر: فیلدهای جدید شخصی‌سازی + ذخیره با وایت‌لیست و کانال‌ها')]
    public function others_component_saves_all_customization_fields(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');
        Gate::define('settings.others', fn ($user = null) => true);

        Livewire::test(Others::class)
            ->assertSet('dt_price_alert_status', true)
            ->assertSet('dt_price_alert_button_text', 'هشدار کاهش قیمت')
            ->assertSet('dt_price_alert_button_color', 'red')
            ->assertSet('dt_price_alert_button_size', 'md')
            ->assertSet('dt_price_alert_button_radius', '10')
            ->assertSet('dt_price_alert_button_placement', 'below-cart')
            // ذخیره با مقادیر سفارشی
            ->set('dt_price_alert_button_color', 'green')
            ->set('dt_price_alert_button_size', 'lg')
            ->set('dt_price_alert_button_radius', '18')
            ->set('dt_price_alert_button_placement', 'above-cart')
            ->set('dt_price_alert_modal_title', 'خبر خوب برای شما')
            ->set('dt_price_alert_modal_desc', 'به‌محض کاهش قیمت خبرتان می‌کنیم.')
            ->set('dt_price_alert_channel_profile', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('green', option('dt_price_alert_button_color'));
        $this->assertSame('lg', option('dt_price_alert_button_size'));
        $this->assertSame('18', option('dt_price_alert_button_radius'));
        $this->assertSame('above-cart', option('dt_price_alert_button_placement'));
        $this->assertSame('خبر خوب برای شما', option('dt_price_alert_modal_title'));
        $this->assertSame('به‌محض کاهش قیمت خبرتان می‌کنیم.', option('dt_price_alert_modal_desc'));
        $this->assertSame('sms', option('dt_price_alert_channels'), 'پروفایل غیرفعال → فقط پیامک');

        // گردی خارج از بازه → کلمپ ۰ تا ۳۰
        Livewire::test(Others::class)
            ->set('dt_price_alert_button_radius', '99')
            ->call('save');
        $this->assertSame('30', option('dt_price_alert_button_radius'));

        // رنگ نامعتبر → پیش‌فرض قرمز
        option_update('dt_price_alert_button_color', 'hacker');
        Cache::flush();
        Livewire::test(Others::class)
            ->assertSet('dt_price_alert_button_color', 'red');
    }

    #[Test]
    #[TestDox('کانال‌های اطلاع‌رسانی: هیچ‌کدام تیک نخورد → هر دو (پیش‌فرض) ذخیره می‌شود')]
    public function channels_fallback_to_both_when_none_selected(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');
        Gate::define('settings.others', fn ($user = null) => true);

        Livewire::test(Others::class)
            ->set('dt_price_alert_channel_sms', false)
            ->set('dt_price_alert_channel_profile', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('sms,profile', option('dt_price_alert_channels'));
    }

    /* ================= ۴) مودال مدرن و کانال‌ها در کنترلر ================= */

    #[Test]
    #[TestDox('مودال مدرن با توضیح و انتخاب کانال در صفحه محصول رندر می‌شود')]
    public function product_page_contains_modern_modal_with_channel_choice(): void
    {
        $show = File::get(base_path('themes/WeblakShop/src/resources/views/products/show.blade.php'));

        // توضیح قابلیت + کارت‌های کانال + حالت موفقیت
        $this->assertStringContainsString('dt-pa-channel', $show);
        $this->assertStringContainsString('dt_pa_success', $show);
        $this->assertStringContainsString('dt_price_alert_modal_desc', $show, 'توضیح قابل شخصی‌سازی');
        $this->assertStringContainsString('route(\'front.price-alert.store\')', $show);
        $this->assertStringNotContainsString('modal-price-alert', $show, 'مودال قدیمی bootstrap حذف شده است');

        $js = File::get(base_path('themes/WeblakShop/src/resources/assets/js/pages/products/show.js'));
        $this->assertStringNotContainsString('#price_alert_btn', $js, 'بایندهای قدیمی JS حذف شده‌اند (منطق در blade اینلاین است)');
    }

    #[Test]
    #[TestDox('کاربر عضو: هر دو کانال ذخیره می‌شود؛ کانال خارج از وایت‌لیست فیلتر می‌شود')]
    public function logged_in_user_subscribes_with_selected_channels(): void
    {
        $product = $this->newProduct([], [
            'price'          => 100000,
            'discount_price' => 100000,
            'regular_price'  => 100000,
            'stock'          => 10,
        ]);

        $user = $this->newUser(['mobile' => '09121112233']);

        // هر دو کانال
        $this->actingAs($user, 'web')
            ->post(route('front.price-alert.store'), [
                'product_id' => $product->id,
                'channels'   => ['sms', 'profile'],
            ])->assertOk()
            ->assertJson(['status' => 'ok']);

        $alert = PriceAlert::where('product_id', $product->id)->firstOrFail();
        $this->assertTrue($alert->notify_sms);
        $this->assertTrue($alert->notify_profile);
        $this->assertSame($user->id, $alert->user_id);

        // فقط پروفایل → پیامک خاموش
        $this->actingAs($user, 'web')
            ->post(route('front.price-alert.store'), [
                'product_id' => $product->id,
                'channels'   => ['profile'],
            ])->assertOk();

        $alert = $alert->fresh();
        $this->assertFalse($alert->notify_sms);
        $this->assertTrue($alert->notify_profile);

        // کانال نامعتبر (بدون هیچ کانال معتبر) → 422 با پیام راهنما
        $this->actingAs($user, 'web')
            ->post(route('front.price-alert.store'), [
                'product_id' => $product->id,
                'channels'   => ['telegram'],
            ])->assertStatus(422)
            ->assertJsonPath('status', 'error');

        // بدون فیلد کانال (کلاینت قدیمی/فاز ۷۸) → پیش‌فرض «هر دو مجاز» — سازگاری رو به عقب
        $this->actingAs($user, 'web')
            ->post(route('front.price-alert.store'), [
                'product_id' => $product->id,
            ])->assertOk();

        $alert = $alert->fresh();
        $this->assertTrue($alert->notify_sms, 'بدون فیلد کانال → هر دو مجاز اعمال می‌شود');
        $this->assertTrue($alert->notify_profile);
    }

    #[Test]
    #[TestDox('مهمان: همیشه فقط پیامک؛ حتی اگر اعلان پروفایل بخواهد')]
    public function guest_is_forced_to_sms_channel_only(): void
    {
        $product = $this->newProduct([], [
            'price'          => 100000,
            'discount_price' => 100000,
            'regular_price'  => 100000,
            'stock'          => 10,
        ]);

        $this->post(route('front.price-alert.store'), [
            'product_id' => $product->id,
            'mobile'     => '09351114455',
            'name'       => 'مهمان قیمتی',
            'channels'   => ['sms', 'profile'],
        ])->assertOk();

        $alert = PriceAlert::where('product_id', $product->id)->firstOrFail();
        $this->assertTrue($alert->notify_sms);
        $this->assertFalse($alert->notify_profile, 'مهمان نمی‌تواند اعلان پروفایل بگیرد');
        $this->assertNull($alert->user_id);
    }

    #[Test]
    #[TestDox('وقتی فقط اعلان پروفایل مجاز است، مهمان با خطای راهنما رد می‌شود')]
    public function guest_is_rejected_when_only_profile_channel_allowed(): void
    {
        $product = $this->newProduct([], [
            'price'          => 100000,
            'discount_price' => 100000,
            'regular_price'  => 100000,
            'stock'          => 10,
        ]);

        option_update('dt_price_alert_channels', 'profile');
        Cache::flush();

        $this->post(route('front.price-alert.store'), [
            'product_id' => $product->id,
            'mobile'     => '09351114455',
        ])->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertSame(0, PriceAlert::count());
    }

    /* ================= ۵) پردازش هشدار با کانال اعلان پروفایل ================= */

    #[Test]
    #[TestDox('پردازش: مشتریِ اعلان-پروفایل در پروفایلش اعلان می‌گیرد؛ مهمان فقط پیامکی')]
    public function processing_sends_profile_notification_and_keeps_sms_only_for_guests(): void
    {
        $product = $this->newProduct([], [
            'price'          => 100000,
            'discount_price' => 100000,
            'regular_price'  => 100000,
            'stock'          => 10,
        ]);

        $user = $this->newUser(['mobile' => '09122223344']);

        // کاربر عضو — فقط اعلان پروفایل
        $userAlert = PriceAlert::create([
            'product_id'     => $product->id,
            'user_id'        => $user->id,
            'mobile'         => '09122223344',
            'name'           => 'کاربر پروفایلی',
            'target_price'   => 100000,
            'notify_sms'     => false,
            'notify_profile' => true,
            'active'         => true,
        ]);

        // مهمان — فقط پیامک (بدون الگوی پیامک → علامت می‌خورد ولی ارسال نمی‌شود)
        $guestAlert = PriceAlert::create([
            'product_id'     => $product->id,
            'mobile'         => '09351115566',
            'name'           => 'مهمان',
            'target_price'   => 100000,
            'notify_sms'     => true,
            'notify_profile' => false,
            'active'         => true,
        ]);

        // افت قیمت → پردازش
        $product->prices()->first()->update([
            'price'          => 70000,
            'discount_price' => 70000,
            'regular_price'  => 70000,
        ]);

        // اعلان پروفایل: NotificationManage + پیوت به کاربر
        $this->assertDatabaseHas('notification_manages', [
            'private' => 'user',
        ]);

        $notification = NotificationManage::where('title', 'like', 'کاهش قیمت%')->latest('id')->first();
        $this->assertNotNull($notification, 'اعلان پروفایل باید ساخته شده باشد');
        $this->assertDatabaseHas('notification_manage_users', [
            'notification_manage_id' => $notification->id,
            'user_id'                => $user->id,
        ]);

        // هر دو هشدار بسته شده‌اند (هر کدام حداقل یک کانال قابل‌ارسال داشتند)
        $this->assertFalse($userAlert->fresh()->active);
        $this->assertFalse($guestAlert->fresh()->active);
        $this->assertNotNull($userAlert->fresh()->notified_at);
    }

    #[Test]
    #[TestDox('هشدارِ فقط-پیامکیِ بدون الگو بسته می‌شود ولی پیامکی ارسال نمی‌شود (رفتار فاز ۷۸ حفظ شد)')]
    public function sms_only_alert_is_closed_without_pattern(): void
    {
        $product = $this->newProduct([], [
            'price'          => 100000,
            'discount_price' => 100000,
            'regular_price'  => 100000,
            'stock'          => 10,
        ]);

        $alert = PriceAlert::create([
            'product_id'     => $product->id,
            'mobile'         => '09351117788',
            'target_price'   => 100000,
            'notify_sms'     => true,
            'notify_profile' => false,
            'active'         => true,
        ]);

        option_update('price_alert_pattern_code', '');
        Cache::flush();

        $product->prices()->first()->update([
            'price'          => 70000,
            'discount_price' => 70000,
            'regular_price'  => 70000,
        ]);

        // رفتار فاز ۷۸: بسته می‌شود تا حلقهٔ پردازش تکرار نیفتد (لاگ هشدار ثبت شده)
        $this->assertFalse($alert->fresh()->active);
        $this->assertNotNull($alert->fresh()->notified_at);
    }

    /* ================= ۶) منوی هشدار قیمت با گیت فعال‌بودن ================= */

    #[Test]
    #[TestDox('«هشدار کاهش قیمت» در منوی «محصولات» (فاز ۸۴: برگشت از اعلان‌ها به محصولات) با گیت enabled')]
    public function price_alert_menu_lives_under_notifications_with_enable_gate(): void
    {
        // فاز ۸۴ — درخواست کاربر: منو باید در «محصولات»، دقیقاً زیر «اطلاع‌رسانی موجودی» باشد
        // (تغییر تصمیم نسبت به فاز ۸۳ که موقتاً زیر «اعلان‌ها» بود)؛ تست فاز ۸۴ جایگاه دقیق را می‌سنجد
        $menu = config('admin-menu.groups');

        $found = null;
        foreach ($menu as $group) {
            foreach ($group['items'] as $item) {
                foreach ($item['children'] ?? [] as $child) {
                    if (($child['route'] ?? '') === 'np.price-alerts.index') {
                        $found = $child;
                    }
                }
            }
        }

        $this->assertNotNull($found, 'آیتم منوی هشدار کاهش قیمت باید در منو باشد');
        $this->assertSame('products.price-alert', $found['permission'] ?? null);
        $this->assertSame('dt_price_alert_status', $found['enabled'] ?? null,
            'آیتم باید با کلید تنظیمات dt_price_alert_status گیت شده باشد');
    }

    #[Test]
    #[TestDox('سایدبار پنل جدید به کلید enabled احترام می‌گذارد (فعال/غیرفعال از تنظیمات)')]
    public function sidebar_honors_the_enabled_option_gate(): void
    {
        $sidebar = File::get(base_path('resources/views/admin-panel/components/sidebar.blade.php'));
        $this->assertStringContainsString("option(\$item['enabled'], '1') != '1'", $sidebar);

        $oldSidebar = File::get(base_path('resources/views/back/partials/sidebar.blade.php'));
        $this->assertStringContainsString("option('dt_price_alert_status', '1') == '1'", $oldSidebar,
            'پنل قدیم هم آیتم را وقتی قابلیت غیرفعال است نمی‌رود');
        $this->assertStringContainsString('اعلان ها', $oldSidebar);
    }

    /* ================= ۷) صفحهٔ لیست ادمین ================= */

    #[Test]
    #[TestDox('صفحهٔ لیست: تب‌های فیلتر + ستون مشتری/کانال‌ها (مستند در ویو و کامپوننت)')]
    public function admin_list_page_has_filter_tabs_and_channel_columns(): void
    {
        $view = File::get(base_path('resources/views/admin-panel/livewire/admin/price-alert/price-alerts.blade.php'));

        $this->assertStringContainsString("setFilter('active')", $view);
        $this->assertStringContainsString("setFilter('notified')", $view);
        $this->assertStringContainsString('channelsLabel', $view);
        $this->assertStringContainsString('مهمان', $view, 'نوع مشتری (عضو/مهمان) نمایش داده می‌شود');

        $oldView = File::get(base_path('resources/views/back/price-alerts/index.blade.php'));
        $this->assertStringContainsString('راه اطلاع‌رسانی', $oldView);
        $this->assertStringContainsString('کاربر عضو', $oldView);
    }
}

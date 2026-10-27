<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۱۰۵ — تعمیم الگوی تب‌بندی مدرن (صفحهٔ تنظیمات AI) به صفحات تنظیمات بزرگ.
 * -------------------------------------------------------------------------------------------------
 * کار کیفیت‌محور، بدون قابلیت جدید: کیت مشترک تب‌بندی (settings-tabs.css/js) ساخته شد و
 * صفحات تنظیمات بزرگِ بدون تب، به آن مجهز شدند:
 *   • پکیج‌ها (نمای واحدِ هر دو پنل): حسابداری، باشگاه مشتریان، لجستیک
 *   • هسته — پنل قدیم (back/): developer/settings، settings/others، settings/sms
 * (پنل جدیدِ هسته از فاز ۳۰/۸۴ خودش تب Alpine دارد؛ information تب دارد؛ gateways آکاردئون است)
 *
 * قرارداد کیت:  nav.stab-tabs > button.stab-tab[data-stab]  +  section.stab-pane[data-stab-pane]
 * همهٔ فیلدها/نام‌ها/مسیرهای ذخیره باید دست‌نخورده باقی بمانند — این تست هر دو را همزمان گارد می‌کند.
 */
class Phase105Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    /* ================= کمکی‌ها ================= */

    private function newCreator(): Admin
    {
        foreach (Permission::where('active', true)->with('roles')->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۰۵',
            'username' => 'feat105-'.Str::random(6),
            'email' => 'feat105-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /**
     * قرارداد مشترک همهٔ صفحات: تب‌لِیست + تب‌های خواسته‌شده + پنل‌ها + اسکریپت کیت
     * + فیلدهای کلیدیِ صفحه (گاردِ دست‌نخوردگی فیلدها هنگام بازچینش).
     *
     * @param  array<int, string>  $tabKeys
     * @param  array<int, string>  $fieldNames
     */
    private function assertTabbedPage(string $html, array $tabKeys, array $fieldNames, string $label = ''): void
    {
        // نوار تب‌ها با نقش ARIA
        self::assertStringContainsString('role="tablist"', $html, $label);
        foreach ($tabKeys as $key) {
            self::assertStringContainsString('data-stab="'.$key.'"', $html, $label.': تب '.$key);
            self::assertStringContainsString('data-stab-pane="'.$key.'"', $html, $label.': پنل '.$key);
        }
        // کیت مشترک باید لود شود
        self::assertStringContainsString('settings-tabs.js', $html, $label);

        // هیچ فیلدی نباید در بازچینش گم شده باشد
        foreach ($fieldNames as $field) {
            self::assertStringContainsString('name="'.$field.'"', $html, $label.': فیلد '.$field);
        }
    }

    /* ================= پکیج‌ها (نمای واحدِ هر دو پنل) ================= */

    #[Test]
    #[TestDox('تنظیمات حسابداری — کیت تب مشترک (۲ تب) + همهٔ فیلدها سر جایشان')]
    public function accounting_settings_is_tabbed_without_losing_fields(): void
    {
        if (! is_dir(base_path('Modules/Accounting/app'))) {
            $this->markTestSkipped('ماژول Accounting روی این میزبان نصب نیست.');
        }

        $this->newCreator();

        $res = $this->get(route('admin.accounting.settings.edit'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['general', 'connections'],
            [
                'accounting_enabled', 'auto_invoice_enabled', 'inventory_sync_enabled',
                'include_shipping_enabled', 'provider', 'inventory_interval_minutes',
                'invoice_title_prefix', 'hesabfa_enabled', 'sepidar_enabled', 'holoo_enabled',
                'credentials[hesabfa][apiKey]', 'credentials[hesabfa][loginToken]',
                'credentials[sepidar][username]', 'credentials[holoo][databaseCode]',
            ],
            'حسابداری'
        );
    }

    #[Test]
    #[TestDox('تنظیمات باشگاه مشتریان — کیت تب مشترک (۳ تب) + همهٔ فیلدها سر جایشان')]
    public function customerclub_settings_is_tabbed_without_losing_fields(): void
    {
        if (! is_dir(base_path('Modules/CustomerClub/app'))) {
            $this->markTestSkipped('ماژول CustomerClub روی این میزبان نصب نیست.');
        }

        $this->newCreator();

        $res = $this->get(route('admin.customerclub.settings.edit'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['toggles', 'referral', 'values'],
            [
                'club_enabled', 'loyalty_enabled', 'giftcards_enabled',
                'referral_program_enabled', 'referral_bonus_enabled',
                'referral_gift_type', 'referral_gift_discount_type',
                'referral_owner_amount', 'referral_invitee_amount',
                'referral_min_order_amount', 'referral_discount_days',
                'redeem_rate_toman', 'redeem_min_points', 'points_expiry_days',
            ],
            'باشگاه مشتریان'
        );

        // دکمهٔ ذخیره از کارت سوم به بیرونِ تب‌ها منتقل شده — باید همان‌جا باشد
        $res->assertSee('ذخیرهٔ تنظیمات');
    }

    #[Test]
    #[TestDox('تنظیمات لجستیک — کیت تب مشترک (۲ تب) + همهٔ فیلدها سر جایشان')]
    public function logistics_settings_is_tabbed_without_losing_fields(): void
    {
        if (! is_dir(base_path('Modules/Logistics/app'))) {
            $this->markTestSkipped('ماژول Logistics روی این میزبان نصب نیست.');
        }

        $this->newCreator();

        $res = $this->get(route('admin.logistics.settings.edit'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['toggles', 'carriers'],
            [
                'logistics_enabled', 'auto_shipment_enabled', 'auto_tracking_enabled',
                'sync_tracking_code_enabled', 'sync_order_status_enabled',
                'sms_on_status_enabled', 'customer_panel_enabled', 'track_interval_minutes',
                'tipax_enabled', 'mahex_enabled', 'post_enabled',
                'credentials[tipax][api_key]', 'credentials[mahex][api_key]', 'credentials[post][api_key]',
            ],
            'لجستیک'
        );
    }

    /* ================= هسته — پنل قدیم (back/) ================= */

    #[Test]
    #[TestDox('تنظیمات برنامه‌نویس (پنل قدیم) — کیت تب مشترک (۴ تب) + همهٔ فیلدها')]
    public function developer_settings_is_tabbed_without_losing_fields(): void
    {
        $this->newCreator();

        $res = $this->get(route('admin.developer.settings'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['cron', 'update', 'webpush', 'general'],
            [
                'shared_hosting_cron_enabled',
                'secret',
                'info_maintenance_mode_title', 'info_maintenance_mode_description',
                'SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN',
                'app_debug_mode', 'debugbar_enabled', 'enable_old_updates',
            ],
            'برنامه‌نویس'
        );
    }

    #[Test]
    #[TestDox('تنظیمات دیگر (پنل قدیم) — کیت تب مشترک (۷ تب ۱:۱) + همهٔ فیلدها')]
    public function others_settings_is_tabbed_without_losing_fields(): void
    {
        $this->newCreator();

        $res = $this->get(url('/admin-old/'.admin_route_prefix().'/settings/others'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['vendor', 'invoice', 'users', 'images', 'features', 'pusher', 'mail'],
            [
                'multi_vendor_system_status',
                'factor_logo', 'factor_title', 'factor_seller_name',
                'factor_national_code', 'factor_registeration_id', 'factor_economical_number',
                'minimum_product_gift',
                'changePhotoFormat',
                'dt_pre_order_status', 'dt_pre_order_button_text',
                'PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER',
                'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_ENCRYPTION',
            ],
            'تنظیمات دیگر'
        );
    }

    #[Test]
    #[TestDox('تنظیمات پیامک (پنل قدیم) — کیت تب مشترک (۲ تب: پنل/رویدادها) + همهٔ فیلدها')]
    public function sms_settings_is_tabbed_without_losing_fields(): void
    {
        $this->newCreator();

        $res = $this->get(url('/admin-old/'.admin_route_prefix().'/settings/sms'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['panel', 'events'],
            [
                'sms_panel_provider', 'admin_mobile_number',
                'sms_on_seller_register', 'sms_on_user_register', 'sms_to_verify_user',
                'forgot_password_link', 'login_with_code',
                'sms_on_order_paid', 'sms_on_order_cancelled',
                'seller_sms_on_order_paid', 'user_sms_on_order_paid',
                'wallet_increase_sms', 'wallet_decrease_sms', 'wallet_refund_sms',
                'happy_birthday_sms',
            ],
            'پیامک'
        );

        // پیکربندی ارائه‌دهنده‌ها فقط یک‌بار (هم‌تبِ پنل) — بدون تکرار
        self::assertSame(
            1,
            substr_count($res->getContent(), 'name="SMS_PANEL_USERNAME"'),
            'پیکربندی ارائه‌دهندهٔ پیامک نباید تکرار شده باشد'
        );
    }

    /* ================= کیت مشترک ================= */

    #[Test]
    #[TestDox('کیت تب‌بندی — فایل‌های CSS/JS موجود و قرارداد کلاس‌ها/ARIA/دیپ‌لینک')]
    public function shared_tab_kit_assets_exist_and_follow_contract(): void
    {
        $cssPath = public_path('back/assets/css/settings-tabs.css');
        $jsPath = public_path('back/assets/js/settings-tabs.js');

        self::assertFileExists($cssPath);
        self::assertFileExists($jsPath);

        $css = (string) file_get_contents($cssPath);
        // قرارداد کلاس‌ها + رنگ تأکیدِ قابل‌شخصی‌سازی per-page
        foreach (['.stab-tabs', '.stab-tab', '.stab-pane', '--stab-accent', 'prefers-reduced-motion'] as $needle) {
            // prefers-reduced-motion اختیاری است؛ فقط چهار مورد اول الزامی
            if ($needle !== 'prefers-reduced-motion') {
                self::assertStringContainsString($needle, $css, 'CSS باید '.$needle.' داشته باشد');
            }
        }

        $js = (string) file_get_contents($jsPath);
        // کلیک → aria-selected + دیپ‌لینک هش + بازیابی از هش
        self::assertStringContainsString('aria-selected', $js);
        self::assertStringContainsString('#tab=', $js);
        self::assertStringContainsString('data-stab-pane', $js);
    }
}

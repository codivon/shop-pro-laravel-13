<?php

namespace Tests\Feature\Packages;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Modules\MarketingMessaging\Console\Commands\ProcessAbandonedCartsCommand;
use Modules\MarketingMessaging\Models\AbandonedCart;
use Modules\MarketingMessaging\Models\Campaign;
use Modules\MarketingMessaging\Models\CampaignTarget;
use Modules\MarketingMessaging\Models\MarketingSetting;
use Modules\MarketingMessaging\Models\Segment;
use Modules\MarketingMessaging\Notifications\CartRecoverySms;
use Modules\MarketingMessaging\Services\AbandonedCartFinder;
use Modules\MarketingMessaging\Services\CampaignDispatcher;
use Modules\MarketingMessaging\Services\MessageTemplate;
use Modules\MarketingMessaging\Services\SegmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۸۰ — تست پکیج «مارکتینگ و پیام‌رسانی» (MarketingMessaging v1)
 *
 * فلسفه (الگوی MoneyPackagesTest): روی میزبانی که پکیج نصب است، ساختار و
 * مسیرهای داده واقعاً راستی‌آزمایی می‌شود؛ روی میزبان بدون پکیج، با پیام
 * روشن Skip می‌شود.
 *
 * پوشش:
 *  - ساختار: مانیفست v1، قرارداد InstallPermissionsCommand، گارد hasTable
 *    مایگریشن‌ها، ثبت منو + ۴ کامند + دو لیستنر OrderPaid در سورس
 *  - سبد رهاشده: معیار MAX(cart_product.updated_at)، مستثنیات (سبد مهمان،
 *    رضایت، رزرو، سفارشِ پس از فعالیت)، دنبالهٔ دومرحله‌ای، ضدتکرار،
 *    بازیابی با OrderPaid، انقضای سبدِ خالی‌شده
 *  - کمپین: سگمنت‌سازی، ارسال، گزارش تبدیل با پنجرهٔ ۳۰ روزه
 */
class MarketingMessagingTest extends TestCase
{
    use ShopTestSetup;

    // ==================================================================
    //  کمکی‌ها
    // ==================================================================

    private function moduleDir(): ?string
    {
        $path = base_path('Modules/MarketingMessaging');

        return is_dir($path . '/app') ? $path : null;
    }

    private function requireModule(): string
    {
        $path = $this->moduleDir();

        if ($path === null) {
            $this->markTestSkipped('ماژول MarketingMessaging روی این میزبان نصب نیست — تست پکیج رد شد.');
        }

        return $path;
    }

    private function requireTables(): void
    {
        if (!Schema::hasTable('marketing_abandoned_carts') || !Schema::hasTable('marketing_campaigns')) {
            $this->markTestSkipped('جداول ماژول مارکتینگ مایگریت نشده‌اند.');
        }
    }

    /** کاربرِ پیامک‌پذیر (موبایل + رضایت) */
    private function newSmsableUser(array $attributes = []): User
    {
        return $this->newUser(array_merge(['notification' => 1], $attributes));
    }

    /**
     * سفارش سبک بدون وابستگی به seed استان/شهر (محدودیت محیطی shop_test).
     * فقط ستون‌های الزامی + کلیدهای گزارش.
     */
    private function newSimpleOrder(User $user, int $price, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'user_id'       => $user->id,
            'name'          => 'خریدار مارکتینگ',
            'mobile'        => $user->mobile,
            'price'         => $price,
            'status'        => 'paid',
            'shipping_cost' => 0,
        ], $attributes));
    }

    /** سبد کاربردار با یک قلم و آخرینِ فعالیتِ قدیمی */
    private function newAbandonedCart(User $user, string $lastActivity = '-6 hours', bool $reserve = false): Cart
    {
        $product = $this->newProduct([], ['stock' => 10]);
        $price   = $product->prices()->first();

        $cart = Cart::create(['user_id' => $user->id, 'reserve' => $reserve ? 1 : 0]);
        $cart->products()->attach($product->id, ['quantity' => 1, 'price_id' => $price->id]);

        // معیار رهاشدگی = updated_at پارچ cart_product (نه carts.updated_at)
        \Illuminate\Support\Facades\DB::table('cart_product')
            ->where('cart_id', $cart->id)
            ->update(['updated_at' => now()->modify($lastActivity)]);

        return $cart->refresh();
    }

    // ==================================================================
    //  ساختار پکیج
    // ==================================================================

    #[Test]
    public function manifest_is_valid_v1(): void
    {
        $dir = $this->requireModule();

        $manifest = json_decode((string) file_get_contents($dir . '/module.json'), true);

        $this->assertIsArray($manifest, 'module.json باید JSON معتبر باشد.');
        $this->assertSame('MarketingMessaging', $manifest['name']);
        $this->assertSame('marketing-messaging', $manifest['alias']);
        $this->assertGreaterThanOrEqual(1.0, (float) $manifest['version']);
        $this->assertContains(
            'Modules\\MarketingMessaging\\Providers\\MarketingMessagingServiceProvider',
            $manifest['providers']
        );
    }

    #[Test]
    public function install_permissions_command_follows_host_installer_contract(): void
    {
        $dir = $this->requireModule();

        $path = $dir . '/app/Console/Commands/InstallPermissionsCommand.php';
        $this->assertFileExists($path, 'قرارداد PackageInstallerService: کلاس در مسیر ثابت باید باشد.');

        $source = (string) file_get_contents($path);
        $this->assertStringContainsString("marketing:install-permissions", $source);
        $this->assertStringContainsString('{--remove}', $source);
    }

    #[Test]
    public function migrations_have_has_table_guards(): void
    {
        $dir = $this->requireModule();

        foreach (glob($dir . '/database/migrations/*.php') as $migration) {
            $this->assertStringContainsString(
                'hasTable',
                (string) file_get_contents($migration),
                "مایگریشن {$migration} باید گارد hasTable داشته باشد (درس باگ CreditPay)."
            );
        }
    }

    #[Test]
    public function provider_registers_commands_menu_and_listeners(): void
    {
        $dir = $this->requireModule();

        $provider = (string) file_get_contents($dir . '/app/Providers/MarketingMessagingServiceProvider.php');
        $this->assertStringContainsString("ModuleMenuRegistry::add('marketing-messaging::back.partials.menu'", $provider);
        $this->assertStringContainsString('marketing:process-abandoned-carts', $provider);
        $this->assertStringContainsString('marketing:send-campaign', $provider);
        $this->assertStringContainsString('marketing:calculate-segments', $provider);

        $events = (string) file_get_contents($dir . '/app/Providers/EventServiceProvider.php');
        $this->assertStringContainsString(\App\Events\OrderPaid::class, $events);
        $this->assertStringContainsString('MarkCartRecovered', $events);
        $this->assertStringContainsString('TrackCampaignConversion', $events);
    }

    #[Test]
    public function menu_partial_is_two_panel(): void
    {
        $dir = $this->requireModule();

        $menu = (string) file_get_contents($dir . '/resources/views/back/partials/menu.blade.php');
        $this->assertStringContainsString('ap-menu-link', $menu, 'شاخهٔ پنل جدید (Livewire) باید باشد.');
        $this->assertStringContainsString("@can('marketing')", $menu, 'شاخهٔ پنل قدیم باید باشد.');
    }

    // ==================================================================
    //  سگمنت‌سازی
    // ==================================================================

    #[Test]
    public function segment_builder_requires_mobile_and_consent(): void
    {
        $this->requireModule();
        $this->requireTables();

        $withConsent = $this->newSmsableUser();
        User::query()->where('id', '!=', $withConsent->id)->update(['notification' => 0]);

        $ids = SegmentBuilder::baseQuery()->pluck('users.id');

        $this->assertContains($withConsent->id, $ids);
        $this->assertEquals(1, $ids->count(), 'فقط کاربر رضایت‌دار با موبایل باید واجد شرایط باشد.');
    }

    #[Test]
    public function segment_builder_filters_by_purchase_history(): void
    {
        $this->requireModule();
        $this->requireTables();

        $buyer  = $this->newSmsableUser();
        $lurker = $this->newSmsableUser();

        $this->newSimpleOrder($buyer, 500000);

        $purchased = SegmentBuilder::query(['has_purchased' => true])->pluck('users.id');
        $this->assertContains($buyer->id, $purchased);
        $this->assertNotContains($lurker->id, $purchased);

        $notPurchased = SegmentBuilder::query(['has_purchased' => false])->pluck('users.id');
        $this->assertContains($lurker->id, $notPurchased);

        $spenders = SegmentBuilder::query(['spent_min' => 400000])->pluck('users.id');
        $this->assertContains($buyer->id, $spenders);
        $this->assertNotContains($lurker->id, $spenders);
    }

    // ==================================================================
    //  سبد رهاشده
    // ==================================================================

    #[Test]
    public function abandoned_finder_uses_pivot_activity_and_respects_exclusions(): void
    {
        $this->requireModule();
        $this->requireTables();

        $eligible     = $this->newSmsableUser();
        $noConsent    = $this->newUser(['notification' => 0]);
        $reservedUser = $this->newSmsableUser(); // هر کاربر فقط یک سبد (unique carts.user_id)

        $cartOk       = $this->newAbandonedCart($eligible);                   // واجد شرایط
        $cartSilent   = $this->newAbandonedCart($noConsent);                  // بدون رضایت
        $cartGuest    = Cart::create(['user_id' => null]);                    // سبد مهمان
        $cartReserved = $this->newAbandonedCart($reservedUser, '-6 hours', true); // در حال تسویه

        $ids = AbandonedCartFinder::eligibleQuery(240)->pluck('carts.id');

        $this->assertContains($cartOk->id, $ids);
        $this->assertNotContains($cartSilent->id, $ids);
        $this->assertNotContains($cartGuest->id, $ids);
        $this->assertNotContains($cartReserved->id, $ids);
    }

    #[Test]
    public function user_with_order_after_cart_activity_is_excluded(): void
    {
        $this->requireModule();
        $this->requireTables();

        $user = $this->newSmsableUser();
        $cart = $this->newAbandonedCart($user, '-6 hours');

        // سفارشی که پس از آخرین فعالیت سبد (۶ ساعت پیش) ثبت شده — یک ساعت پیش
        $this->newSimpleOrder($user, 100000, [
            'created_at' => now()->subHour(),
            'paid_at'    => now()->subHour(),
        ]);

        $this->assertNotContains(
            $cart->id,
            AbandonedCartFinder::eligibleQuery(240)->pluck('carts.id'),
            'کاربری که پس از آخرین فعالیت سبد سفارش ثبت کرده، نباید یادآور بگیرد.'
        );
    }

    #[Test]
    public function disabled_engine_is_a_no_op(): void
    {
        $this->requireModule();
        $this->requireTables();

        MarketingSetting::set('abandoned_enabled', '0');

        $user = $this->newSmsableUser();
        $this->newAbandonedCart($user);

        Notification::fake();

        $this->artisan('marketing:process-abandoned-carts')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertSame(0, AbandonedCart::count());
    }

    #[Test]
    public function stage1_reminder_is_sent_once_and_is_deduped(): void
    {
        $this->requireModule();
        $this->requireTables();

        option_update('cart_recovery_pattern_code', 'TEST-CART-PATTERN');
        MarketingSetting::set('abandoned_enabled', '1');

        $user = $this->newSmsableUser();
        $this->newAbandonedCart($user);

        Notification::fake();

        // اجرای اول — یادآور می‌رود
        $this->artisan('marketing:process-abandoned-carts')->assertSuccessful();

        Notification::assertSentTo($user, CartRecoverySms::class, function ($n) {
            $data = $n->toSms($n->user ?? null);

            return ($data['data']['name'] ?? '') !== '' && isset($data['data']['total']);
        });

        $record = AbandonedCart::where('stage', 1)->first();
        $this->assertNotNull($record, 'رکورد مرحلهٔ ۱ باید ثبت شود.');
        $this->assertSame(AbandonedCart::STATUS_REMENDED, $record->status);
        $this->assertGreaterThan(0, $record->items_count);
        $this->assertGreaterThan(0, $record->cart_total);

        // اجرای دوم — ضدتکرار: ارسال دوباره ممنوع
        Notification::fake();
        $this->artisan('marketing:process-abandoned-carts')->assertSuccessful();
        Notification::assertNothingSent();
        $this->assertSame(1, AbandonedCart::where('stage', 1)->count());
    }

    #[Test]
    public function empty_cart_records_expire_without_stage2(): void
    {
        $this->requireModule();
        $this->requireTables();

        option_update('cart_recovery_pattern_code', 'TEST-CART-PATTERN');
        MarketingSetting::set('abandoned_enabled', '1');

        $user = $this->newSmsableUser();
        $cart = $this->newAbandonedCart($user);

        // یادآور اول ارسال شد
        $this->artisan('marketing:process-abandoned-carts')->assertSuccessful();

        // کاربر سبدش را خالی کرد
        \Illuminate\Support\Facades\DB::table('cart_product')->where('cart_id', $cart->id)->delete();

        // گذشت تأخیر مرحلهٔ دوم — رکورد باید expired شود نه یادآور دوم
        AbandonedCart::where('stage', 1)->update(['reminded_at' => now()->subHours(30)]);

        Notification::fake();
        $this->artisan('marketing:process-abandoned-carts')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertSame(
            AbandonedCart::STATUS_EXPIRED,
            AbandonedCart::where('stage', 1)->first()->status
        );
        $this->assertSame(0, AbandonedCart::where('stage', 2)->count());
    }

    #[Test]
    public function order_paid_marks_latest_reminder_recovered(): void
    {
        $this->requireModule();
        $this->requireTables();

        $user = $this->newSmsableUser();
        $cart = $this->newAbandonedCart($user);

        $record = AbandonedCart::create([
            'cart_id'     => $cart->id,
            'user_id'     => $user->id,
            'stage'       => 1,
            'status'      => AbandonedCart::STATUS_REMENDED,
            'items_count' => 1,
            'cart_total'  => 1000,
            'reminded_at' => now()->subHours(5),
        ]);

        $order = $this->newSimpleOrder($user, 250000);

        // فراخوانی مستقیم لیستنر (بدون صف/برودکست)
        (new \Modules\MarketingMessaging\Listeners\MarkCartRecovered)
            ->handle(new \App\Events\OrderPaid($order));

        $record->refresh();

        $this->assertSame(AbandonedCart::STATUS_RECOVERED, $record->status);
        $this->assertEquals($order->id, $record->order_id);
        $this->assertNotNull($record->recovered_at);
    }

    // ==================================================================
    //  کمپین و گزارش
    // ==================================================================

    #[Test]
    public function campaign_dispatch_targets_segment_and_tracks_conversion(): void
    {
        $this->requireModule();
        $this->requireTables();

        option_update('marketing_campaign_pattern_code', 'TEST-CAMPAIGN-PATTERN');

        $buyer  = $this->newSmsableUser();
        $lurker = $this->newSmsableUser();

        $this->newSimpleOrder($buyer, 300000);

        $segment = Segment::create([
            'name'     => 'خریداران',
            'criteria' => ['has_purchased' => true],
        ]);

        $campaign = Campaign::create([
            'name'       => 'کمپین تست',
            'segment_id' => $segment->id,
            'message'    => '{{name}} عزیز، {{store}} پیشنهاد ویژه دارد!',
            'status'     => Campaign::STATUS_SCHEDULED,
        ]);

        Notification::fake();

        $result = CampaignDispatcher::dispatch($campaign);

        $this->assertSame(1, $result['targets'], 'فقط خریدار باید گیرنده باشد.');

        $target = CampaignTarget::where('campaign_id', $campaign->id)->first();
        $this->assertNotNull($target);
        $this->assertEquals($buyer->id, $target->user_id);
        $this->assertSame(CampaignTarget::STATUS_SENT, $target->status);

        Notification::assertSentTo($buyer, \Modules\MarketingMessaging\Notifications\CampaignSms::class);
        Notification::assertNotSentTo($lurker, \Modules\MarketingMessaging\Notifications\CampaignSms::class);

        $campaign->refresh();
        $this->assertSame(Campaign::STATUS_SENT, $campaign->status);
        $this->assertNotNull($campaign->sent_at);

        // ===== تبدیل: سفارشِ پس از کمپین =====
        $order = $this->newSimpleOrder($buyer, 800000);

        (new \Modules\MarketingMessaging\Listeners\TrackCampaignConversion)
            ->handle(new \App\Events\OrderPaid($order));

        $target->refresh();
        $campaign->refresh();

        $this->assertNotNull($target->converted_at, 'سفارش در پنجرهٔ ۳۰ روزه باید تبدیل شمرده شود.');
        $this->assertEquals($order->id, $target->order_id);
        $this->assertSame(1, $campaign->converted_count);
        $this->assertSame(800000, $campaign->converted_amount);
    }

    #[Test]
    public function campaign_without_pattern_fails_safe(): void
    {
        $this->requireModule();
        $this->requireTables();

        option_update('marketing_campaign_pattern_code', ''); // پترن تنظیم نشده

        $user    = $this->newSmsableUser();
        $segment = Segment::create(['name' => 'همه', 'criteria' => []]);

        $campaign = Campaign::create([
            'name'       => 'کمپین بدون پترن',
            'segment_id' => $segment->id,
            'status'     => Campaign::STATUS_DRAFT,
        ]);

        Notification::fake();

        $result = CampaignDispatcher::dispatch($campaign);

        $this->assertSame(0, $result['targets']);
        Notification::assertNothingSent();

        $campaign->refresh();
        $this->assertSame(Campaign::STATUS_FAILED, $campaign->status);
        $this->assertSame(0, CampaignTarget::count());
    }

    // ==================================================================
    //  متغیرهای قالب
    // ==================================================================

    #[Test]
    public function message_template_variables_follow_core_driver_contract(): void
    {
        $this->requireModule();

        $user           = $this->newSmsableUser();
        $user->first_name = 'علی رضا';
        $user->last_name  = 'محمدی';

        $cartVars = MessageTemplate::cartVariables($user, 'کفش ورزشی', 1234500);
        $this->assertSame(['name', 'items', 'total'], array_keys($cartVars), 'قرارداد درایورها: name/items/total.');
        $this->assertSame('علی', $cartVars['name']);
        $this->assertSame('1,234,500', $cartVars['total']);

        $campaignVars = MessageTemplate::campaignVariables($user);
        $this->assertSame(['name', 'store'], array_keys($campaignVars), 'قرارداد درایورها: name/store.');

        $preview = MessageTemplate::preview('{{name}} عزیز، {{store}}', $campaignVars);
        $this->assertStringContainsString('علی', $preview);
    }
}

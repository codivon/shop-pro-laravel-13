<?php

namespace Tests\Feature\Extra;

use App\Events\OrderPaid;
use App\Models\Admin;
use App\Models\Permission;
use App\Models\Review;
use App\Models\WalletHistory;
use App\Services\FrontModuleMenuRegistry;
use App\Services\ModuleMenuRegistry;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Models\GiftCard;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Models\LoyaltyRule;
use Modules\CustomerClub\Models\ReferralTier;
use Modules\CustomerClub\Services\GiftCardService;
use Modules\CustomerClub\Services\LoyaltyService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۰ — پکیج «باشگاه مشتریان» (CustomerClub v1.0.0)
 * --------------------------------------------------------------------------
 * امتیاز وفاداری (لات/FIFO + تبدیل به کیف پول هسته) + کارت هدیه (شارژ مستقیم
 * کیف پول) + توسعهٔ معرفی دوستان (صفحهٔ فرود + سطوح پاداش).
 *
 * قرارداد فاز ۸۶+: هویت پکیج، license_skip، منو بدون گیت لایسنس، راهنمای
 * درون‌صفحه‌ای (نه در منوی فرانت — درس فاز ۸۹)، InstallPermissionsCommand
 * + seedDefaults، رندر واقعی صفحات دو پنل.
 */
class Phase90Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    /* ================= کمکی‌ها ================= */

    private function ccDir(): ?string
    {
        $path = base_path('Modules/CustomerClub');

        return is_dir($path.'/app') ? $path : null;
    }

    private function requireCustomerClub(): string
    {
        $path = $this->ccDir();

        if ($path === null) {
            $this->markTestSkipped('ماژول CustomerClub (باشگاه مشتریان) روی این میزبان نصب نیست — تست فاز ۹۰ رد شد.');
        }

        return $path;
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۹۰',
            'username' => 'feat90-'.Str::random(6),
            'email' => 'feat90-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);
    }

    private function read(string $relative): string
    {
        $path = base_path($relative);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /** کامپایل blade + lint؛ خروجی exitCode */
    private function compileAndLint(string $viewName): int
    {
        $path = view($viewName)->getPath();
        $compiler = app('blade.compiler');
        $compiled = $compiler->compileString((string) file_get_contents($path));

        $tmp = tempnam(sys_get_temp_dir(), 'cc90-').'.php';
        file_put_contents($tmp, $compiled);

        exec(
            sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($tmp)),
            $output,
            $exitCode
        );

        @unlink($tmp);

        return $exitCode;
    }

    private function grantCreatorPermissions(): Admin
    {
        // گیت‌های پرمیژن هسته در CLI ثبت نمی‌شوند (AuthServiceProvider فقط
        // خارج از console) — عین کلوزر پروداکشن اینجا تعریف می‌کنیم.
        foreach (Permission::where('active', true)->with('roles')->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        $creator = $this->newCreator();
        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /* ================= ۱) هویت پکیج ================= */

    #[Test]
    #[TestDox('بند ۱ — هویت پکیج: module.json (title فارسی + v1.0.0) + composer.json + قرارداد license_skip در LicenseGuard و config')]
    public function package_identity_and_license_skip_contract(): void
    {
        $dir = $this->requireCustomerClub();

        $module = json_decode((string) file_get_contents($dir.'/module.json'), true);
        $this->assertSame('CustomerClub', $module['name']);
        $this->assertSame('customer-club', $module['alias']);
        $this->assertSame('باشگاه مشتریان', $module['title']);
        $this->assertSame('1.1.1', $module['version']); // فاز ۹۲ موتور پاداش معرفی + فاز ۱۰۵ تب‌بندی تنظیمات (bump مخزن مستقل)
        $this->assertStringContainsString('CustomerClubServiceProvider', $module['providers'][0]);

        $composer = json_decode((string) file_get_contents($dir.'/composer.json'), true);
        $this->assertSame('modules/customer-club', $composer['name']);

        // قرارداد license_skip — الگوی PayYar/MarketingMessaging/CreditPay
        $guard = $this->read('Modules/CustomerClub/app/Services/LicenseGuard.php');
        $this->assertStringContainsString("ClubSetting::get('license_skip'", $guard);
        $this->assertStringContainsString("config('customer-club.license_skip'", $guard);

        $config = $this->read('Modules/CustomerClub/config/config.php');
        $this->assertStringContainsString("'license_skip'", $config);
    }

    #[Test]
    #[TestDox('بند ۲ — InstallPermissionsCommand با قرارداد نام + seedDefaults (۴ قانون امتیاز + ۳ سطح معرفی + تنظیمات) idempotent')]
    public function install_permissions_command_contract_and_seeded_defaults(): void
    {
        $this->requireCustomerClub();

        // قرارداد نام کلاس/امضا برای نصب‌کنندهٔ هاست
        $this->assertFileExists(base_path('Modules/CustomerClub/app/Console/Commands/InstallPermissionsCommand.php'));
        $cmd = $this->read('Modules/CustomerClub/app/Console/Commands/InstallPermissionsCommand.php');
        $this->assertStringContainsString('customerclub:install-permissions {--remove}', $cmd);
        $this->assertStringContainsString('seedDefaults', $cmd);

        // seedDefaults: قوانین و سطوح موجودند (کامند واقعاً در سندباکس اجرا شده)
        foreach (LoyaltyRule::EVENTS as $event) {
            $this->assertNotNull(LoyaltyRule::for($event), "قانون امتیاز {$event} seed نشده است.");
        }

        $this->assertSame(3, ReferralTier::query()->count(), '۳ سطح معرفی باید seed شده باشد.');
        $this->assertSame('1', (string) ClubSetting::get('club_enabled'));
        $this->assertGreaterThan(0, (int) ClubSetting::get('redeem_rate_toman'));

        // idempotency: اجرای دوباره مقدارها را بازنویسی نمی‌کند
        ClubSetting::set('redeem_rate_toman', '777');
        Artisan::call('customerclub:install-permissions');
        $this->assertSame('777', (string) ClubSetting::get('redeem_rate_toman'), 'اجرای مجدد نباید مقدار ادمین را بازنویسی کند.');
        ClubSetting::set('redeem_rate_toman', '1000');
    }

    /* ================= ۲) موتور امتیاز ================= */

    #[Test]
    #[TestDox('بند ۳ — موتور امتیاز (لات): award idempotent + موجودی = SUM(remaining) + کسر FIFO (نزدیک‌ترین انقضا اول)')]
    public function loyalty_engine_lot_model_with_fifo_deduction(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();

        ClubSetting::set('points_expiry_days', '0'); // انقضای سراسری خاموش برای کنترل دستی

        // لات ۱: 100 امتیاز بدون انقضا
        LoyaltyService::award($user, 100, 'manual', 'لات بدون انقضا', null, null, null, false);
        // لات ۲: 50 امتیاز که فردا منقضی می‌شود (باید FIFO اول مصرف شود)
        LoyaltyService::award($user, 50, 'manual', 'لات نزدیک انقضا', null, null, now()->addDay(), false);

        $this->assertSame(150, LoyaltyService::balance($user->id));

        // award idempotent با یکتای منبع — اجرای دوباره اضافه نمی‌کند
        $again = LoyaltyService::award($user, 100, 'manual', 'تکراری', 'order', 12345);
        $this->assertNotNull($again);
        $this->assertNull(LoyaltyService::award($user, 100, 'manual', 'تکراری', 'order', 12345));
        $this->assertSame(250, LoyaltyService::balance($user->id));

        // کسر ۱۲۰ → لاتِ نزدیک انقضا (50) کامل + 70 از لات بدون انقضا
        $this->assertTrue(LoyaltyService::deduct($user->id, 120, 'redeem', 'redeem', 'تست کسر FIFO'));

        $nearLot = LoyaltyPoint::query()->where('user_id', $user->id)->where('remaining', '>', 0)->orderBy('id')->first();
        $this->assertNotNull($nearLot);
        $this->assertSame(100, $nearLot->points, 'لاتِ نزدیک انقضا باید کامل مصرف شده باشد.');
        $this->assertSame(30, $nearLot->remaining, 'باقی‌مانده از لات بدون انقضا باید 30 باشد (100 - 70).');

        $this->assertSame(130, LoyaltyService::balance($user->id));

        // کسر بیش از موجودی رد می‌شود و سطر audit ثبت نمی‌کند
        $this->assertFalse(LoyaltyService::deduct($user->id, 999, 'redeem', 'redeem', 'بیش از موجودی'));

        // فقط یک سطر audit منفی (کسر موفقِ ۱۲۰) ثبت شده است
        $this->assertSame(1, LoyaltyPoint::query()->where('user_id', $user->id)->where('points', '<', 0)->count());
    }

    #[Test]
    #[TestDox('بند ۴ — تبدیل امتیاز به کیف پول هسته: نرخ/حداقل + WalletHistory + افزایش موجودی + سطر ledger منفی')]
    public function redeem_points_to_core_wallet(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();

        ClubSetting::set('redeem_rate_toman', '1000');
        ClubSetting::set('redeem_min_points', '100');

        LoyaltyService::award($user, 500, 'manual', 'امتیاز تست', null, null, null, false);

        // زیر حداقل رد می‌شود
        [$ok, $msg] = LoyaltyService::redeemToWallet($user, 50);
        $this->assertFalse($ok);
        $this->assertSame(500, LoyaltyService::balance($user->id));

        // تبدیل موفق 500 → 500,000 تومان
        [$ok, $msg, $history] = LoyaltyService::redeemToWallet($user, 500);
        $this->assertTrue($ok, $msg);
        $this->assertInstanceOf(WalletHistory::class, $history);
        $this->assertSame(500000, (int) $history->amount);
        $this->assertSame('deposit', $history->type);

        $this->assertSame(0, LoyaltyService::balance($user->id));

        $wallet = $user->getWallet();
        $this->assertSame(500000, (int) $wallet->balance);
    }

    /* ================= ۳) رویدادهای کسب امتیاز ================= */

    #[Test]
    #[TestDox('بند ۵ — امتیاز خرید: OrderPaid → قانون purchase (هر ۱۰هزار = ۱ امتیاز) + idempotent با dispatch دوباره')]
    public function purchase_points_on_order_paid_event(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product, ['price' => 60000]);

        // قانون purchase سر جای خودش است (seed): هر 10000 → 1 امتیاز
        $rule = LoyaltyRule::for(LoyaltyRule::EVENT_PURCHASE);
        $this->assertSame(10000, (int) $rule->amount_step);

        OrderPaid::dispatch($order);

        $this->assertSame(6, LoyaltyService::balance($user->id), '۶۰٬۰۰۰ تومان = ۶ امتیاز.');

        // dispatch دوباره — idempotent
        OrderPaid::dispatch($order);
        $this->assertSame(6, LoyaltyService::balance($user->id), 'دیسپچ دوباره نباید امتیاز دوباره بدهد.');

        // امتیاز صفر زیر حداقل قانون
        [$order2] = $this->newPaidOrder($user, $product, ['price' => 5000]);
        OrderPaid::dispatch($order2);
        $this->assertSame(6, LoyaltyService::balance($user->id));
    }

    #[Test]
    #[TestDox('بند ۶ — امتیاز ثبت‌نام: Registered → امتیاز خوش‌آمدگویی')]
    public function registration_points_on_registered_event(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();

        $points = LoyaltyService::fixedPointsFor(LoyaltyRule::EVENT_REGISTER);
        $this->assertGreaterThan(0, $points, 'قانون ثبت‌نام باید فعال و مثبت باشد.');

        event(new Registered($user));

        $this->assertSame($points, LoyaltyService::balance($user->id));
        $this->assertTrue(
            LoyaltyPoint::query()->where('user_id', $user->id)->where('event', 'register')->exists()
        );
    }

    #[Test]
    #[TestDox('بند ۷ — امتیاز نظر: Observer پکیجی روی تأیید نظر (pending → accepted) + idempotent')]
    public function review_points_on_acceptance(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();
        $product = $this->newProduct();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'title' => 'نظر تست',
            'body' => 'بدنهٔ نظر تستی',
            'rating' => 5,
            'status' => 'pending',
        ]);

        $points = LoyaltyService::fixedPointsFor(LoyaltyRule::EVENT_REVIEW);
        $this->assertGreaterThan(0, $points);

        // تأیید نظر → امتیاز
        $review->update(['status' => 'accepted']);
        $this->assertSame($points, LoyaltyService::balance($user->id));

        // ذخیرهٔ دوباره بدون تغییر وضعیت → امتیاز دوباره نمی‌گیرد
        $review->update(['title' => 'عنوان عوض شد']);
        $this->assertSame($points, LoyaltyService::balance($user->id), 'Observer نباید دوباره امتیاز بدهد.');
    }

    /* ================= ۴) انقضا ================= */

    #[Test]
    #[TestDox('بند ۸ — انقضای امتیاز: expireDue → لات سررسیده منقضی + سطر audit + موجودی صفر')]
    public function points_expiry_command_logic(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();

        ClubSetting::set('points_expiry_days', '0');

        LoyaltyService::award($user, 80, 'manual', 'منقضی‌شدنی', null, null, now()->subDay(), false);
        LoyaltyService::award($user, 20, 'manual', 'می‌ماند', null, null, null, false);

        $expired = LoyaltyService::expireDue();

        $this->assertSame(80, $expired);
        $this->assertSame(20, LoyaltyService::balance($user->id));

        // سطر audit انقضا ثبت شد
        $this->assertTrue(
            LoyaltyPoint::query()->where('user_id', $user->id)->where('type', 'expire')->where('points', -80)->exists()
        );

        // اجرای دوباره — چیزی باقی نمانده
        $this->assertSame(0, LoyaltyService::expireDue());
    }

    /* ================= ۵) کارت هدیه ================= */

    #[Test]
    #[TestDox('بند ۹ — کارت هدیه: صدور دسته‌ای + فعال‌سازی → شارژ کیف پول + یک‌بار مصرفی (تکرار/غیرفعال/منقضی رد می‌شود)')]
    public function gift_card_issue_and_redeem(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();

        [$cards, $batch] = GiftCardService::issue(3, 50000, now()->addMonth(), 'تست فاز ۹۰');

        $this->assertCount(3, $cards);
        $this->assertSame(3, GiftCard::query()->where('batch_id', $batch)->count());

        foreach ($cards as $card) {
            $this->assertMatchesRegularExpression('/^CC-[A-F0-9]{4}-[A-F0-9]{4}$/', $card->code);
        }

        // فعال‌سازی موفق
        $code = $cards[0]->code;
        [$ok, $msg, $history] = GiftCardService::redeem($user, $code);
        $this->assertTrue($ok, $msg);
        $this->assertSame(50000, (int) $history->amount);

        $wallet = $user->getWallet()->refresh();
        $this->assertSame(50000, (int) $wallet->balance);

        $card = $cards[0]->refresh();
        $this->assertSame(GiftCard::STATUS_REDEEMED, $card->status);
        $this->assertSame($user->id, (int) $card->used_by);

        // فعال‌سازی دوباره همان کد → رد
        [$ok2] = GiftCardService::redeem($user, $code);
        $this->assertFalse($ok2);
        $this->assertSame(50000, (int) $user->getWallet()->refresh()->balance);

        // کارت غیرفعال‌شده
        $cards[1]->update(['status' => GiftCard::STATUS_DISABLED]);
        [$ok3] = GiftCardService::redeem($user, $cards[1]->code);
        $this->assertFalse($ok3);

        // کارت منقضی
        $cards[2]->update(['expires_at' => now()->subDay()]);
        [$ok4] = GiftCardService::redeem($user, $cards[2]->code);
        $this->assertFalse($ok4);

        // کد ناموجود
        [$ok5] = GiftCardService::redeem($user, 'CC-XXXX-YYYY');
        $this->assertFalse($ok5);
    }

    /* ================= ۶) معرفی دوستان ================= */

    #[Test]
    #[TestDox('بند ۱۰ — معرفی دوستان: صفحهٔ فرود لینک دعوت (عمومی، prefill ?ref) + ۴۰۴ کد نامعتبر')]
    public function referral_landing_page(): void
    {
        $this->requireCustomerClub();
        $owner = $this->newUser(['referral_code' => Str::upper(Str::random(5))]);

        // صفحهٔ فرود با کد معتبر → ریدایرکت به فلوی ثبت‌نام هسته (login?ref — عین هسته)
        $response = $this->get(route('front.club.referral.landing', ['code' => $owner->referral_code]));
        $response->assertRedirect(route('login').'?ref='.$owner->referral_code);
        $this->assertSame($owner->referral_code, session('cc_referral_code'));

        // کد نامعتبر → ۴۰۴
        $this->get(route('front.club.referral.landing', ['code' => 'NOPE9']))
            ->assertStatus(404);
    }

    #[Test]
    #[TestDox('بند ۱۱ — پاداش سطوح معرفی: پرداخت سفارش دعوت‌شده → پاداش سطح برنزی یک‌بار برای معرف')]
    public function referral_tier_bonus_awarded_once(): void
    {
        $this->requireCustomerClub();

        ClubSetting::set('referral_bonus_enabled', '1');

        $owner = $this->newUser();
        $referred = $this->newUser(['referral_id' => $owner->id]);
        $product = $this->newProduct();

        $tier = ReferralTier::query()->orderBy('min_referrals')->first();
        $this->assertNotNull($tier);
        $this->assertSame(1, (int) $tier->min_referrals, 'سطح اول باید از ۱ معرفی موفق شروع شود.');

        [$order] = $this->newPaidOrder($referred, $product);
        OrderPaid::dispatch($order);

        // پاداش سطح برنزی به معرف
        $this->assertSame(
            (int) $tier->bonus_points,
            LoyaltyService::balance($owner->id),
            'معرف باید پاداش سطح برنزی را بگیرد.'
        );
        $this->assertTrue(
            LoyaltyPoint::query()->where('user_id', $owner->id)->where('event', 'referral_tier')->where('source_id', $tier->id)->exists()
        );

        // dispatch دوباره → پاداش تکراری نمی‌گیرد
        OrderPaid::dispatch($order);
        $this->assertSame((int) $tier->bonus_points, LoyaltyService::balance($owner->id));
    }

    /* ================= ۷) روت‌ها و صفحات ادمین ================= */

    #[Test]
    #[TestDox('بند ۱۲ — روت‌های ادمین ثبت شده‌اند + صفحات داشبورد/قوانین/دفتر/اعضا/کارت/معرفی/تنظیمات/راهنما رندر می‌شوند')]
    public function admin_routes_and_pages_render(): void
    {
        $this->requireCustomerClub();
        $this->grantCreatorPermissions();

        foreach ([
            'admin.customerclub.dashboard' => 'admin/customerclub',
            'admin.customerclub.rules.index' => 'admin/customerclub/rules',
            'admin.customerclub.points.index' => 'admin/customerclub/points',
            'admin.customerclub.members.index' => 'admin/customerclub/members',
            'admin.customerclub.giftcards.index' => 'admin/customerclub/gift-cards',
            'admin.customerclub.referrals.index' => 'admin/customerclub/referrals',
            'admin.customerclub.settings.edit' => 'admin/customerclub/settings',
            'admin.customerclub.guide' => 'admin/customerclub/guide',
        ] as $route => $url) {
            $this->assertTrue(Route::has($route), "روت {$route} ثبت نشده است.");
        }

        $this->get(route('admin.customerclub.dashboard'))->assertOk();
        $this->get(route('admin.customerclub.rules.index'))->assertOk();
        $this->get(route('admin.customerclub.points.index'))->assertOk();
        $this->get(route('admin.customerclub.members.index'))->assertOk();
        $this->get(route('admin.customerclub.giftcards.index'))->assertOk();
        $this->get(route('admin.customerclub.referrals.index'))->assertOk();
        $this->get(route('admin.customerclub.settings.edit'))->assertOk();
        $this->get(route('admin.customerclub.guide'))->assertOk();
    }

    /* ================= ۸) پنل مشتری ================= */

    #[Test]
    #[TestDox('بند ۱۳ — پنل مشتری: صفحهٔ باشگاه با موجودی/تبدیل/کارت هدیه/لینک دعوت + راهنمای درون‌صفحه‌ای (بدون آیتم منو)')]
    public function customer_club_page_renders_with_all_sections(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();
        LoyaltyService::award($user, 250, 'manual', 'امتیاز نمایش', null, null, null, false);

        $response = $this->actingAs($user, 'web')->get(route('front.club.index'));

        $response->assertOk();
        $response->assertSee(route('front.club.redeem'), false);
        $response->assertSee(route('front.club.giftcard.redeem'), false);
        $response->assertSee(route('front.club.guide'), false);
        // بنر راهنما درون‌صفحه‌ای (درس فاز ۸۹)
        $response->assertSee('راهنما و آموزش باشگاه مشتریان', false);
        $response->assertSee(number_format(250), false);

        if ($user->referral_code) {
            $response->assertSee('معرفی دوستان', false);
        }

        // راهنمای مشتری
        $this->actingAs($user, 'web')->get(route('front.club.guide'))->assertOk();
    }

    #[Test]
    #[TestDox('بند ۱۴ — قرارداد فاز ۸۹: «راهنما و آموزش» در منوی فرانت نیست + منوی فرانت/بک در رجیستری‌ها ثبت شده')]
    public function guide_not_in_front_menu_and_menus_registered(): void
    {
        $this->requireCustomerClub();

        $menus = FrontModuleMenuRegistry::all();
        $this->assertContains('customer-club::front.partials.menu', $menus, 'منوی فرانت باشگاه در رجیستری نیست.');

        $frontMenu = $this->read('Modules/CustomerClub/resources/views/front/partials/menu.blade.php');
        $this->assertStringNotContainsString('front.club.guide', $frontMenu, 'لینک راهنما نباید در منوی فرانت باشد (درس فاز ۸۹).');

        $backMenus = ModuleMenuRegistry::all();
        $this->assertContains('customer-club::back.partials.menu', $backMenus, 'منوی بک باشگاه در رجیستری نیست.');

        // آیتم منوی بک شامل راهنماست (قرارداد فاز ۸۶+)
        $backMenu = $this->read('Modules/CustomerClub/resources/views/back/partials/menu.blade.php');
        $this->assertStringContainsString('admin.customerclub.guide', $backMenu);
    }

    /* ================= ۹) اکشن‌های پنل مشتری ================= */

    #[Test]
    #[TestDox('بند ۱۵ — اکشن تبدیل و فعال‌سازی کارت هدیه از فرم پنل مشتری (POST) کار می‌کنند')]
    public function customer_redeem_and_giftcard_post_actions(): void
    {
        $this->requireCustomerClub();
        $user = $this->newUser();

        LoyaltyService::award($user, 300, 'manual', 'تست فرم', null, null, null, false);

        // تبدیل زیر حداقل → خطا، بدون تغییر
        $this->actingAs($user, 'web')
            ->post(route('front.club.redeem'), ['points' => 10])
            ->assertRedirect()
            ->assertSessionHas('toast-error');

        // تبدیل موفق
        $this->actingAs($user, 'web')
            ->post(route('front.club.redeem'), ['points' => 300])
            ->assertRedirect()
            ->assertSessionHas('toast-success');

        $this->assertSame(0, LoyaltyService::balance($user->id));
        $this->assertSame(300000, (int) $user->getWallet()->balance);

        // کارت هدیه از فرم
        [$cards] = GiftCardService::issue(1, 75000, null, 'فرم مشتری');
        $this->actingAs($user, 'web')
            ->post(route('front.club.giftcard.redeem'), ['code' => $cards[0]->code])
            ->assertRedirect()
            ->assertSessionHas('toast-success');

        $this->assertSame(375000, (int) $user->getWallet()->refresh()->balance);
    }

    /* ================= ۱۰) تنظیم دستی ادمین ================= */

    #[Test]
    #[TestDox('بند ۱۶ — تنظیم دستی امتیاز توسط ادمین: مثبت (لات) و منفی (کسر FIFO تا سقف موجودی)')]
    public function admin_manual_adjustment(): void
    {
        $this->requireCustomerClub();
        $this->grantCreatorPermissions();
        $user = $this->newUser();

        $this->post(route('admin.customerclub.members.adjust', $user), [
            'points' => 150,
            'description' => 'جبران خسارت تست',
        ])->assertRedirect()->assertSessionHas('toast-success');

        $this->assertSame(150, LoyaltyService::balance($user->id));

        // کسر 200 از موجودی 150 → رد
        $this->post(route('admin.customerclub.members.adjust', $user), [
            'points' => -200,
            'description' => 'بیش از موجودی',
        ])->assertRedirect()->assertSessionHas('toast-error');

        $this->assertSame(150, LoyaltyService::balance($user->id));

        // کسر مجاز
        $this->post(route('admin.customerclub.members.adjust', $user), [
            'points' => -50,
            'description' => 'کسر تست',
        ])->assertRedirect()->assertSessionHas('toast-success');

        $this->assertSame(100, LoyaltyService::balance($user->id));
    }

    /* ================= ۱۱) کامپایل همهٔ ویوها ================= */

    #[Test]
    #[TestDox('بند ۱۷ — همهٔ Blade ویوهای پکیج (ادمین + فرانت + خطا) کامپایل و lint می‌شوند')]
    public function all_blade_views_compile(): void
    {
        $this->requireCustomerClub();

        $views = [
            'customer-club::back.partials.menu',
            'customer-club::back.dashboard.index',
            'customer-club::back.rules.index',
            'customer-club::back.points.index',
            'customer-club::back.members.index',
            'customer-club::back.giftcards.index',
            'customer-club::back.referrals.index',
            'customer-club::back.settings.index',
            'customer-club::back.guide.index',
            'customer-club::front.partials.menu',
            'customer-club::front.index',
            'customer-club::front.guide.index',
        ];

        foreach ($views as $view) {
            $this->assertSame(0, $this->compileAndLint($view), "ویو {$view} کامپایل نشد.");
        }
    }
}

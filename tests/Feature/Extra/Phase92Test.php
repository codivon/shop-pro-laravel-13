<?php

namespace Tests\Feature\Extra;

use App\Events\OrderPaid;
use App\Models\Admin;
use App\Models\Discount;
use App\Models\Permission;
use App\Models\Referral;
use App\Models\User;
use App\Services\FrontModuleMenuRegistry;
use App\Services\ModuleMenuRegistry;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Services\LoyaltyService;
use Modules\CustomerClub\Services\ReferralService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۲ — پکیج «باشگاه مشتریان»: موتور کامل پاداش معرفی افراد + بازطراحی فرانت
 * --------------------------------------------------------------------------
 *  • امکان «معرفی افراد» از هسته (/settings/others + Listener CreateReferralDiscounts
 *    + صفحهٔ profile/referrals + منوی تم) به پکیج منتقل و در هسته مخفی شد.
 *  • موتور پکیج: هدیهٔ دوطرفه (کد تخفیف/کیف پول) + درصد/مبلغ + زمان پاداش معرف
 *    (ثبت‌نام یا اولین خرید واجد شرایط با حداقل مبلغ/تعداد) + گاردهای ضدتکرار.
 *  • الزام شخصی‌سازی کاربر: همهٔ کلیدها از تنظیمات پکیج کنترل می‌شوند.
 */
class Phase92Test extends TestCase
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

    private function requireCustomerClub(): void
    {
        if (!is_dir(base_path('Modules/CustomerClub/app'))) {
            $this->markTestSkipped('ماژول CustomerClub روی این میزبان نصب نیست — تست فاز ۹۲ رد شد.');
        }
    }

    private function read(string $relative): string
    {
        $path = base_path($relative);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    private function referralDefaults(): void
    {
        ClubSetting::set('club_enabled', '1');
        ClubSetting::set('referral_program_enabled', '1');
        ClubSetting::set('referral_gift_type', ReferralService::GIFT_DISCOUNT_CODE);
        ClubSetting::set('referral_gift_discount_type', 'percent');
        ClubSetting::set('referral_owner_amount', '5');
        ClubSetting::set('referral_invitee_amount', '10');
        ClubSetting::set('referral_min_order_amount', '0');
        ClubSetting::set('referral_min_products', '1');
        ClubSetting::set('referral_discount_days', '90');
        ClubSetting::set('referral_owner_timing', ReferralService::TIMING_REGISTRATION);
    }

    /** جفت معرف+دعوت‌شده با لینک کامل */
    private function newPair(): array
    {
        $owner = $this->newUser(['first_name' => 'معرف', 'last_name' => 'تستی']);
        $invitee = $this->newUser(['first_name' => 'دعوت‌شده', 'last_name' => 'تستی']);

        $invitee->update(['referral_id' => $owner->id]);
        $invitee->refresh();
        $owner->refresh();

        return [$owner, $invitee];
    }

    private function newCreator(): Admin
    {
        foreach (Permission::where('active', true)->with('roles')->get() as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission->name, function ($user = null) use ($permission) {
                $admin = $user instanceof \App\Models\Admin
                    ? $user
                    : \Illuminate\Support\Facades\Auth::guard('adminPanel')->user();

                return $admin instanceof \App\Models\Admin && $admin->level == 'creator';
            });
        }

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۹۲',
            'username'   => 'feat92-'.\Illuminate\Support\Str::random(6),
            'email'      => 'feat92-'.\Illuminate\Support\Str::random(6).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    private function compileAndLint(string $viewName): int
    {
        $path     = view($viewName)->getPath();
        $compiler = app('blade.compiler');
        $compiled = $compiler->compileString((string) file_get_contents($path));

        $tmp = tempnam(sys_get_temp_dir(), 'cc92-').'.php';
        file_put_contents($tmp, $compiled);

        exec(
            sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($tmp)),
            $output,
            $exitCode
        );

        @unlink($tmp);

        return $exitCode;
    }

    /* ================= ۱) مهاجرت امکان از هسته ================= */

    #[Test]
    #[TestDox('بند ۱ — قرارداد مهاجرت: Listener هسته کامنت + بخش ریفرال تنظیمات ادمین مخفی + منوی تم مخفی + روت قدیمی ریدایرکت')]
    public function core_referral_feature_is_hidden_and_package_takes_over(): void
    {
        $this->requireCustomerClub();

        // ۱) Listener هسته غیرفعال شده (کامنت) — پکیج جایگزین آن است
        $coreEvents = $this->read('app/Providers/EventServiceProvider.php');
        $this->assertMatchesRegularExpression(
            "/\/\/\s*'App\\\\Listeners\\\\User\\\\CreateReferralDiscounts',/",
            $coreEvents,
            'Listener هسته باید به‌صورت کامنت باقی باشد.'
        );

        // ۲) پکیج دو Listener جدید دارد
        $packageEvents = $this->read('Modules/CustomerClub/app/Providers/EventServiceProvider.php');
        $this->assertStringContainsString('CreateReferralRewards::class', $packageEvents);
        $this->assertStringContainsString('AwardReferralPurchaseReward::class', $packageEvents);

        // ۳) بخش ریفرال در تنظیمات ادمین (Livewire others) مخفی است
        $others = $this->read('resources/views/admin-panel/livewire/admin/settings/others.blade.php');
        $this->assertStringContainsString('فاز ۹۲', $others, 'یادداشت مهاجرت باید در ویو ادمین باشد.');
        $this->assertStringContainsString('{{--', $others);

        // ۴) منوی تم (موبایل + دسکتاپ) مخفی است
        $themeMaster = $this->read('themes/WeblakShop/src/resources/views/user/layouts/master.blade.php');
        $this->assertSame(2, substr_count($themeMaster, '@if(false)'), 'هر دو آیتم منوی معرفی (موبایل+دسکتاپ) باید مخفی باشند.');

        // ۵) روت قدیمی /profile/referrals ریدایرکت می‌شود
        $themeRoutes = $this->read('themes/WeblakShop/src/routes/web.php');
        $this->assertStringContainsString("redirect()->to('/profile/club/referrals')", $themeRoutes);

        // ۶) سرویس موتور پکیج موجود است و هر دو تایمینگ را پشتیبانی می‌کند
        $service = $this->read('Modules/CustomerClub/app/Services/ReferralService.php');
        $this->assertStringContainsString('TIMING_REGISTRATION', $service);
        $this->assertStringContainsString('TIMING_FIRST_PURCHASE', $service);
        $this->assertStringContainsString('GIFT_WALLET', $service);
        $this->assertStringContainsString('GIFT_DISCOUNT_CODE', $service);

        // ۷) گیت ثبت‌نام هسته پکیج را هم می‌پذیرد
        $registerController = $this->read('app/Http/Controllers/Auth/RegisteredUserController.php');
        $this->assertStringContainsString("ClubSetting::enabled('referral_program_enabled')", $registerController);
    }

    #[Test]
    #[TestDox('بند ۲ — پیش‌فرض‌های موتور پاداش در config + کلیدها با seedDefaults قابل seed شدن')]
    public function referral_engine_config_defaults_and_settings_keys(): void
    {
        $this->requireCustomerClub();

        $config = $this->read('Modules/CustomerClub/config/config.php');
        foreach ([
            "'referral_gift_type'",
            "'referral_gift_discount_type'",
            "'referral_owner_amount'",
            "'referral_invitee_amount'",
            "'referral_min_order_amount'",
            "'referral_min_products'",
            "'referral_discount_days'",
            "'referral_owner_timing'",
        ] as $key) {
            $this->assertStringContainsString($key, $config, "کلید {$key} در پیش‌فرض‌های config نیست.");
        }

        // رفتار زنجیرهٔ خواندن: DB → config → پیش‌فرض سرویس
        ClubSetting::query()->where('key', 'referral_gift_type')->delete();
        ClubSetting::flushCache();
        $this->assertSame('discount_code', (string) config('customer-club.defaults.referral_gift_type'));
        $this->assertSame('discount_code', ClubSetting::get('referral_gift_type'));

        ClubSetting::set('referral_gift_type', 'wallet');
        $this->assertSame('wallet', ClubSetting::get('referral_gift_type'));
    }

    /* ================= ۲) موتور پاداش — ثبت‌نام ================= */

    #[Test]
    #[TestDox('بند ۳ — پاداش ثبت‌نام (حالت کد تخفیف درصدی): دو کد یک‌بارمصرف برای معرف و دعوت‌شده + قیدهای حداقل + ردیف Referral')]
    public function registration_rewards_in_discount_code_mode(): void
    {
        $this->requireCustomerClub();
        $this->referralDefaults();
        ClubSetting::set('referral_min_order_amount', '500000');
        ClubSetting::set('referral_min_products', '2');

        [$owner, $invitee] = $this->newPair();

        event(new Registered($invitee));

        // ردیف جفت معرفی — دقیقاً یک عدد
        $row = Referral::query()->where('owner_id', $owner->id)->where('user_id', $invitee->id)->first();
        $this->assertNotNull($row, 'ردیف Referral باید ساخته شود.');

        // کد تخفیف معرف — درصدی ۵٪ + قیدها
        $this->assertNotNull($row->owner_discount_id, 'کد تخفیف معرف باید صادر شود.');
        $ownerDiscount = Discount::find($row->owner_discount_id);
        $this->assertSame('percent', $ownerDiscount->type);
        $this->assertSame(5.0, (float) $ownerDiscount->amount);
        $this->assertSame(500000, (int) $ownerDiscount->least_price);
        $this->assertSame(2, (int) $ownerDiscount->least_products_count);
        $this->assertTrue($ownerDiscount->users()->where('users.id', $owner->id)->exists(), 'کد معرف باید به او متصل باشد.');
        $this->assertSame(90, (int) \Carbon\Carbon::parse($ownerDiscount->start_date)->diffInDays(\Carbon\Carbon::parse($ownerDiscount->end_date)));

        // کد تخفیف دعوت‌شده — درصدی ۱۰٪
        $this->assertNotNull($row->user_discount_id, 'کد تخفیف دعوت‌شده باید صادر شود.');
        $userDiscount = Discount::find($row->user_discount_id);
        $this->assertSame('percent', $userDiscount->type);
        $this->assertSame(10.0, (float) $userDiscount->amount);
        $this->assertTrue($userDiscount->users()->where('users.id', $invitee->id)->exists());

        // کیف پول دست‌نخورده (حالت کد تخفیف)
        $this->assertSame(0, (int) $owner->getWallet()->balance);

        // dispatch دوباره — ضدتکرار
        event(new Registered($invitee));
        $this->assertSame(1, Referral::query()->where('owner_id', $owner->id)->where('user_id', $invitee->id)->count(), 'جفت تکراری نباید دوباره پاداش بگیرد.');
    }

    #[Test]
    #[TestDox('بند ۴ — پاداش ثبت‌نام (حالت کیف پول): واریز مستقیم به کیف پول هستهٔ طرفین + WalletHistory + ردیف Referral')]
    public function registration_rewards_in_wallet_mode(): void
    {
        $this->requireCustomerClub();
        $this->referralDefaults();
        ClubSetting::set('referral_gift_type', ReferralService::GIFT_WALLET);
        ClubSetting::set('referral_owner_amount', '50000');
        ClubSetting::set('referral_invitee_amount', '100000');

        [$owner, $invitee] = $this->newPair();

        event(new Registered($invitee));

        $row = Referral::query()->where('owner_id', $owner->id)->where('user_id', $invitee->id)->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->owner_wallet_history_id, 'شارژ کیف پول معرف باید ثبت شود.');
        $this->assertNotNull($row->user_wallet_history_id, 'شارژ کیف پول دعوت‌شده باید ثبت شود.');

        $this->assertSame(50000, (int) $owner->getWallet()->balance);
        $this->assertSame(100000, (int) $invitee->getWallet()->balance);

        $ownerHistory = \App\Models\WalletHistory::find($row->owner_wallet_history_id);
        $this->assertSame('deposit', $ownerHistory->type);
        $this->assertSame('success', $ownerHistory->status);
        $this->assertSame(50000, (int) $ownerHistory->amount);
        $this->assertStringContainsString('باشگاه مشتریان', $ownerHistory->description);

        // بدون کد تخفیف در حالت کیف پول
        $this->assertNull($row->owner_discount_id);
        $this->assertNull($row->user_discount_id);
    }

    #[Test]
    #[TestDox('بند ۵ — گاردهای ایمنی: خاموشی امکان، معرفی به خود و نبود معرف = بدون هیچ پاداشی')]
    public function safety_guards_prevent_illegal_rewards(): void
    {
        $this->requireCustomerClub();
        $this->referralDefaults();

        // ۱) خاموشی امکان
        ClubSetting::set('referral_program_enabled', '0');
        [$owner, $invitee] = $this->newPair();
        event(new Registered($invitee));
        $this->assertSame(0, Referral::query()->where('user_id', $invitee->id)->count(), 'با خاموشی امکان نباید ردیفی ساخته شود.');
        $this->assertSame(0, Discount::query()->where('title', 'like', '%معرفی%')->count());

        // ۲) معرفی به خود
        ClubSetting::set('referral_program_enabled', '1');
        $solo = $this->newUser();
        $solo->update(['referral_id' => $solo->id]);
        event(new Registered($solo));
        $this->assertSame(0, Referral::query()->where('user_id', $solo->id)->count(), 'معرفی به خود نباید پاداش بدهد.');

        // ۳) نبود معرف
        $lonely = $this->newUser();
        event(new Registered($lonely));
        $this->assertSame(0, Referral::query()->where('user_id', $lonely->id)->count());
    }

    /* ================= ۳) تایمینگ first_purchase ================= */

    #[Test]
    #[TestDox('بند ۶ — تایمینگ first_purchase: معرف فقط با اولین خریدِ واجد شرایط (حداقل مبلغ+تعداد) پاداش می‌گیرد + idempotent')]
    public function first_purchase_timing_rewards_owner_on_qualifying_order(): void
    {
        $this->requireCustomerClub();
        $this->referralDefaults();
        ClubSetting::set('referral_owner_timing', ReferralService::TIMING_FIRST_PURCHASE);
        ClubSetting::set('referral_min_order_amount', '400000');
        ClubSetting::set('referral_min_products', '2');

        [$owner, $invitee] = $this->newPair();
        event(new Registered($invitee));

        $row = Referral::query()->where('owner_id', $owner->id)->where('user_id', $invitee->id)->first();

        // دعوت‌شده در ثبت‌نام پاداش گرفته، ولی معرف هنوز نه
        $this->assertNotNull($row->user_discount_id, 'پاداش دعوت‌شده باید در ثبت‌نام صادر شود.');
        $this->assertNull($row->owner_discount_id, 'پاداش معرف نباید در ثبت‌نام صادر شود.');
        $this->assertSame(0, (int) $owner->getWallet()->balance);

        $product = $this->newProduct();

        // ۱) سفارش کمتر از حداقل مبلغ — پاداش نمی‌دهد
        [$smallOrder] = $this->newPaidOrder($invitee, $product, ['price' => 100000], ['quantity' => 3]);
        OrderPaid::dispatch($smallOrder);
        $row->refresh();
        $this->assertNull($row->owner_discount_id, 'سفارش زیر حداقل مبلغ نباید پاداش معرف بدهد.');

        // ۲) سفارش بالای مبلغ ولی تعداد کمتر — باز هم نه
        [$fewOrder] = $this->newPaidOrder($invitee, $product, ['price' => 900000], ['quantity' => 1]);
        OrderPaid::dispatch($fewOrder);
        $row->refresh();
        $this->assertNull($row->owner_discount_id, 'سفارش با تعداد کمتر از حداقل نباید پاداش معرف بدهد.');

        // ۳) سفارش واجد شرایط — معرف پاداش می‌گیرد
        [$goodOrder] = $this->newPaidOrder($invitee, $product, ['price' => 900000], ['quantity' => 2]);
        OrderPaid::dispatch($goodOrder);
        $row->refresh();
        $this->assertNotNull($row->owner_discount_id, 'خرید واجد شرایط باید پاداش معرف را صادر کند.');
        $ownerDiscount = Discount::find($row->owner_discount_id);
        $this->assertSame(5.0, (float) $ownerDiscount->amount);
        $this->assertTrue($ownerDiscount->users()->where('users.id', $owner->id)->exists());

        // ۴) dispatch دوباره — idempotent
        OrderPaid::dispatch($goodOrder);
        $row->refresh();
        $this->assertSame($ownerDiscount->id, $row->owner_discount_id, 'dispatch دوباره نباید کد دوم بسازد.');
    }

    /* ================= ۴) بازطراحی فرانت پکیج ================= */

    #[Test]
    #[TestDox('بند ۷ — صفحهٔ باشگاه (بازطراحی مدرن): قهرمان + تبدیل با اسلایدر + کارت هدیه + خلاصهٔ معرفی + لینک صفحهٔ معرفی')]
    public function redesigned_club_page_renders_with_new_sections(): void
    {
        $this->requireCustomerClub();
        $this->referralDefaults();
        $user = $this->newUser();
        LoyaltyService::award($user, 500, 'manual', 'امتیاز نمایش', null, null, null, false);

        $response = $this->actingAs($user, 'web')->get(route('front.club.index'));

        $response->assertOk();
        // عناصر بازطراحی فاز ۹۲
        $response->assertSee('ccf-hero', false);
        $response->assertSee('ccf-redeem-slider', false);
        $response->assertSee('ccf-preview-value', false);
        $response->assertSee('تبدیل امتیاز به اعتبار کیف پول', false);
        $response->assertSee('فعال‌سازی کارت هدیه', false);
        $response->assertSee('معرفی دوستان', false);
        $response->assertSee(route('front.club.referrals'), false);
        // پیام شرط حداقل وقتی موجودی کافی است نباید حالت خالی باشد
        $response->assertDontSee('هنوز به حداقل تبدیل نرسیده‌اید', false);

        // کاربر بدون امتیاز: حالت خالی + دکمهٔ غیرفعال نیست بلکه کارت خالی است
        $poor = $this->newUser();
        $poorResponse = $this->actingAs($poor, 'web')->get(route('front.club.index'));
        $poorResponse->assertOk();
        $poorResponse->assertSee('هنوز به حداقل تبدیل نرسیده‌اید', false);
    }

    #[Test]
    #[TestDox('بند ۸ — صفحهٔ «معرفی دوستان» پکیج: کد/لینک/اشتراک‌گذاری + قوانین پاداش + جدول جوایز + دعوت‌شدگان + سطوح')]
    public function referral_dashboard_page_renders(): void
    {
        $this->requireCustomerClub();
        $this->referralDefaults();

        [$owner, $invitee] = $this->newPair();
        event(new Registered($invitee));

        $response = $this->actingAs($owner, 'web')->get(route('front.club.referrals'));

        $response->assertOk();
        $response->assertSee('ccr-hero', false);
        $response->assertSee($owner->referral_code, false);
        $response->assertSee('data-ccr-share', false);
        $response->assertSee('قوانین پاداش', false);
        $response->assertSee('جوایز و کدهای تخفیفی که دریافت کرده‌اید', false);
        $response->assertSee('افرادی که معرفی کرده‌اید', false);
        $response->assertSee($invitee->username, false);
        $response->assertSee('5٪ تخفیف', false); // برچسب پاداش معرف (درصدی)

        // گارد: خاموشی امکان → ریدایرکت به باشگاه با پیام
        ClubSetting::set('referral_program_enabled', '0');
        $this->actingAs($owner, 'web')
            ->get(route('front.club.referrals'))
            ->assertRedirect(route('front.club.index'));
    }

    #[Test]
    #[TestDox('بند ۹ — روت قدیمی /profile/referrals به صفحهٔ پکیج هدایت می‌شود + آیتم منوی فرانت مشروط به کلیدها')]
    public function legacy_referrals_route_redirects_and_menu_toggles(): void
    {
        $this->requireCustomerClub();
        $this->referralDefaults();

        $user = $this->newUser();

        $this->actingAs($user, 'web')
            ->get('/profile/referrals')
            ->assertRedirect('/profile/club/referrals');

        // منوی فرانت: آیتم معرفی فقط با referral_program_enabled
        $menuOn = view('customer-club::front.partials.menu')->render();
        $this->assertStringContainsString('معرفی دوستان', $menuOn);
        $this->assertStringContainsString('باشگاه مشتریان', $menuOn);

        ClubSetting::set('referral_program_enabled', '0');
        $menuOff = view('customer-club::front.partials.menu')->render();
        $this->assertStringNotContainsString('معرفی دوستان', $menuOff);
        $this->assertStringContainsString('باشگاه مشتریان', $menuOff);

        ClubSetting::set('club_enabled', '0');
        $menuAllOff = view('customer-club::front.partials.menu')->render();
        $this->assertStringNotContainsString('باشگاه مشتریان', $menuAllOff);
    }

    /* ================= ۵) پنل ادمین ================= */

    #[Test]
    #[TestDox('بند ۱۰ — پنل ادمین: صفحهٔ معرفی دوستان با آمار جوایز + صفحهٔ تنظیمات با فیلدهای موتور + ذخیرهٔ POST')]
    public function admin_referrals_page_and_engine_settings(): void
    {
        $this->requireCustomerClub();
        $this->referralDefaults();
        $this->newCreator();

        // صفحهٔ گزارش معرفی
        $response = $this->get(route('admin.customerclub.referrals.index'));
        $response->assertOk();
        $response->assertSee('پاداش معرفی', false);
        $response->assertSee('جوایز صادرشده', false);
        $response->assertSee('تنظیمات فعلی موتور پاداش معرفی', false);

        // صفحهٔ تنظیمات شامل فیلدهای موتور
        $settingsPage = $this->get(route('admin.customerclub.settings.edit'));
        $settingsPage->assertOk();
        foreach ([
            'referral_gift_type',
            'referral_gift_discount_type',
            'referral_owner_amount',
            'referral_invitee_amount',
            'referral_min_order_amount',
            'referral_min_products',
            'referral_discount_days',
            'referral_owner_timing',
        ] as $field) {
            $settingsPage->assertSee('name="'.$field.'"', false);
        }

        // ذخیرهٔ POST
        $this->post(route('admin.customerclub.settings.update'), [
            'club_enabled'               => '1',
            'loyalty_enabled'            => '1',
            'giftcards_enabled'          => '1',
            'referral_program_enabled'   => '1',
            'referral_bonus_enabled'     => '0',
            'referral_gift_type'         => 'wallet',
            'referral_gift_discount_type' => 'amount',
            'referral_owner_amount'      => '50000',
            'referral_invitee_amount'    => '100000',
            'referral_min_order_amount'  => '2000000',
            'referral_min_products'      => '2',
            'referral_discount_days'     => '60',
            'referral_owner_timing'      => 'first_purchase',
            'points_expiry_days'         => '365',
            'redeem_rate_toman'          => '1000',
            'redeem_min_points'          => '100',
        ])->assertRedirect(route('admin.customerclub.settings.edit'));

        $this->assertSame('wallet', ClubSetting::get('referral_gift_type'));
        $this->assertSame('50000', ClubSetting::get('referral_owner_amount'));
        $this->assertSame('first_purchase', ClubSetting::get('referral_owner_timing'));
        $this->assertSame('60', ClubSetting::get('referral_discount_days'));
    }

    /* ================= ۶) قرارداد blade + رفتار سرویس ================= */

    #[Test]
    #[TestDox('بند ۱۱ — کامپایل و لینت همهٔ bladeهای جدید/تغییریافتهٔ فرانت پکیج')]
    public function new_blade_views_compile_and_lint(): void
    {
        $this->requireCustomerClub();

        foreach ([
            'customer-club::front.index',
            'customer-club::front.referrals.index',
            'customer-club::front.guide.index',
            'customer-club::front.partials.menu',
            'customer-club::back.settings.index',
            'customer-club::back.referrals.index',
        ] as $view) {
            $this->assertSame(
                0,
                $this->compileAndLint($view),
                "بلید {$view} باید بدون خطای syntax کامپایل شود."
            );
        }
    }

    #[Test]
    #[TestDox('بند ۱۲ — برچسب فارسی هدیه (formatGiftLabel): درصدی/مبلغی/کیف پولی')]
    public function gift_label_formatting(): void
    {
        $this->requireCustomerClub();

        $this->assertSame('5٪ تخفیف', ReferralService::formatGiftLabel(5, 'percent', ReferralService::GIFT_DISCOUNT_CODE));
        $this->assertSame('2.5٪ تخفیف', ReferralService::formatGiftLabel(2.5, 'percent', ReferralService::GIFT_DISCOUNT_CODE));
        $this->assertSame('50,000 تومان تخفیف', ReferralService::formatGiftLabel(50000, 'amount', ReferralService::GIFT_DISCOUNT_CODE));
        $this->assertSame('50,000 تومان', ReferralService::formatGiftLabel(50000, 'percent', ReferralService::GIFT_WALLET));
    }
}

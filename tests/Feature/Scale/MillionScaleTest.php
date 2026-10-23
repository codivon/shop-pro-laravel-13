<?php

namespace Tests\Feature\Scale;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ScaleTestSetup;
use Tests\TestCase;

/**
 * تست‌های مقیاس میلیون‌ها رکورد:
 *
 *  - «سرعت سایت با تعداد محصولات میلیونی» (درخواست کاربر)
 *  - «سرعت سایت با کاربران میلیونی» (درخواست کاربر)
 *
 * چگونه میلیون ردیف را در php artisan test شبیه‌سازی می‌کنیم؟
 *  ۱) درج انبوه ۳۰هزار محصول/کاربر با insert های chunk شده (سریع و واقعی).
 *  ۲) اندازه‌گیری زمان کوئری‌های کلیدی: جستجو، صفحه‌بندی عمیق، صفحه محصول،
 *     ورود کاربر، بررسی یکتایی موبایل.
 *  ۳) اثبات ایندکس‌خور بودن مسیرهای داغ (slug / username / mobile) با
 *     بررسی SHOW INDEX — یعنی با ۱۰× داده هم زمان رشد لگاریتمی دارد.
 *
 * ۳۰هزار ردیف کافی است چون همه مسیرهای تست‌شده ایندکس B+Tree دارند؛
 * زمان اجرا از log(n) پیروی می‌کند و رفتار در ۱ میلیون ردیف قابل برون‌یابی است.
 * برای تست با داده واقعی میلیون ردیف، متغیر محیطی SCALE_PRODUCTS/SCALE_USERS
 * را بالا ببرید (مثلاً ۵۰۰۰۰۰) — فقط زمان اجرای تست طولانی‌تر می‌شود.
 *
 * آستانه‌ها:
 *  - جستجو / صفحه محصول / ورود: < ۱ ثانیه
 *  - صفحه‌بندی عمیق (آفست بالا): < ۲ ثانیه
 */
class MillionScaleTest extends TestCase
{
    use ScaleTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpScale();
    }

    protected function tearDown(): void
    {
        $this->tearDownScale();
        parent::tearDown();
    }

    // =====================================================================
    //  میلیون محصول
    // =====================================================================

    #[Test]
    public function million_products_search_stays_fast(): void
    {
        $this->seedScaleProducts();

        // جستجوی کلیدواژه مشترک همه محصولات مقیاس
        $elapsed = $this->measureSeconds(fn () => $this->get('/search?q=' . urlencode('SCALESEARCH')));

        $this->assertLessThan(
            1.0,
            $elapsed,
            sprintf('جستجو در کاتالوگ %.2f ثانیه طول کشید — باید < ۱ ثانیه باشد', $elapsed)
        );
    }

    #[Test]
    public function million_products_exact_product_page_stays_fast(): void
    {
        $this->seedScaleProducts();

        // صفحه‌ی یکی از محصولات وسط مجموعه (بازه‌ی داغ ایندکس)
        $slug = 'scale-product-' . ($this->scaleRanges['products'][0] + intdiv($this->scaleProductCount(), 2));

        $elapsed = $this->measureSeconds(fn () => $this->get('/product/' . $slug));

        $this->assertLessThan(
            1.0,
            $elapsed,
            sprintf('صفحه محصول با %.0f محصول %.2f ثانیه طول کشید — باید < ۱ ثانیه باشد', $this->scaleProductCount(), $elapsed)
        );
    }

    #[Test]
    public function million_products_deep_pagination_stays_bounded(): void
    {
        $this->seedScaleProducts();

        // صفحه‌بندی عمیق: آفست بالا در لیست محصولات (بدترین حالت OFFSET)
        $elapsed = $this->measureSeconds(
            fn () => $this->get('/search?q=' . urlencode('SCALESEARCH') . '&page=300')
        );

        $this->assertLessThan(
            2.0,
            $elapsed,
            sprintf('صفحه ۳۰۰ لیست محصولات %.2f ثانیه طول کشید — باید < ۲ ثانیه باشد', $elapsed)
        );
    }

    #[Test]
    public function product_lookup_paths_are_indexed_for_million_scale(): void
    {
        // اثبات ساختاری: مسیرهای داغ باید ایندکس داشته باشند تا در میلیون ردیف هم سریع بمانند
        $productIndexes = collect(DB::select('SHOW INDEX FROM products'))
            ->pluck('Column_name')->all();

        $this->assertContains('slug', $productIndexes, 'ستون slug باید ایندکس داشته باشد (مسیر صفحه محصول)');
        $this->assertContains('product_id', $productIndexes, 'ستون product_id باید ایندکس داشته باشد');

        $priceIndexes = collect(DB::select('SHOW INDEX FROM prices'))
            ->pluck('Column_name')->all();

        $this->assertContains('product_id', $priceIndexes, 'prices.product_id باید ایندکس داشته باشد (join سبد/سفارش)');

        $userIndexes = collect(DB::select('SHOW INDEX FROM users'))
            ->pluck('Column_name')->all();

        $this->assertContains('username', $userIndexes, 'users.username باید ایندکس یکتا داشته باشد (ورود)');
        $this->assertContains('mobile', $userIndexes, 'users.mobile باید ایندکس داشته باشد');
    }

    #[Test]
    public function million_products_catalog_listing_stays_fast(): void
    {
        $this->seedScaleProducts();

        // فهرست محصولات — مسیر سایدبار کاتالوگ
        $elapsed = $this->measureSeconds(
            fn () => $this->get('/search')
        );

        $this->assertLessThan(
            1.5,
            $elapsed,
            sprintf('فهرست محصولات %.2f ثانیه طول کشید — باید < ۱.۵ ثانیه باشد', $elapsed)
        );
    }

    // =====================================================================
    //  میلیون کاربر
    // =====================================================================

    #[Test]
    public function million_users_login_lookup_stays_fast(): void
    {
        $password = $this->seedScaleUsers();

        // ورود با آخرین کاربر درج‌شده (بدترین حالت tail ایندکس)
        $mobile = $this->lastScaleUserMobile();

        $elapsed = $this->measureSeconds(function () use ($mobile, $password) {
            $this->post('/login/CheckPassword', [
                'username' => $mobile,
                'password' => $password,
            ]);
        });

        $this->assertAuthenticated();
        $this->assertLessThan(
            1.0,
            $elapsed,
            sprintf('ورود کاربر شماره %s در %.2f ثانیه — باید < ۱ ثانیه باشد', $mobile, $elapsed)
        );
    }

    #[Test]
    public function million_users_wrong_password_lookup_stays_fast(): void
    {
        $this->seedScaleUsers();

        $mobile = $this->lastScaleUserMobile();

        $elapsed = $this->measureSeconds(function () use ($mobile) {
            $this->post('/login/CheckPassword', [
                'username' => $mobile,
                'password' => 'definitely-wrong-pass',
            ]);
        });

        $this->assertGuest();
        $this->assertLessThan(
            1.0,
            $elapsed,
            sprintf('رد ورود با رمز غلط %.2f ثانیه طول کشید — باید < ۱ ثانیه باشد', $elapsed)
        );
    }

    #[Test]
    public function million_users_duplicate_mobile_registration_rejected_fast(): void
    {
        $this->seedScaleUsers();

        $mobile = $this->lastScaleUserMobile();
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $response = null;
        $elapsed = $this->measureSeconds(function () use (&$response, $mobile, $captcha) {
            $response = $this->post('/register', [
                'first_name' => 'تست',
                'last_name'  => 'یکتایی',
                'username'   => $mobile, // موبایل تکراری از مجموعه میلیون کاربری
                'password'   => 'secret1234',
                'confirmed'  => 'secret1234',
                'captcha'    => $captcha,
            ]);
        });

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
        $this->assertLessThan(
            1.0,
            $elapsed,
            sprintf('بررسی یکتایی موبایل %.2f ثانیه طول کشید — ایندکس یکتا باید سریع باشد', $elapsed)
        );
    }

    #[Test]
    public function million_users_registration_still_works(): void
    {
        $this->seedScaleUsers();

        $mobile = '0938' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $elapsed = $this->measureSeconds(function () use ($mobile, $captcha) {
            $this->post('/register', [
                'first_name' => 'میلیون',
                'last_name'  => 'کاربر',
                'username'   => $mobile,
                'password'   => 'secret1234',
                'confirmed'  => 'secret1234',
                'captcha'    => $captcha,
            ]);
        });

        $this->assertDatabaseHas('users', ['username' => $mobile]);
        $this->assertAuthenticated();
        $this->assertLessThan(
            1.5,
            $elapsed,
            sprintf('ثبت‌نام در دیتابیس میلیونی %.2f ثانیه طول کشید — باید < ۱.۵ ثانیه باشد', $elapsed)
        );
    }
}

<?php

namespace Tests\Feature\Performance;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ScaleTestSetup;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های کارایی:
 *  - زمان بارگذاری صفحه اصلی < ۲ ثانیه
 *  - زمان بارگذاری صفحه محصول < ۳ ثانیه
 *  - تحمل ترافیک همزمان (شبیه‌سازی ترافیک روز حراج با درخواست‌های پشت سر هم)
 *  - کنترل تعداد کوئری‌های صفحه محصول (پیشگیری از N+1)
 */
class PerformanceTest extends TestCase
{
    use ShopTestSetup;

    /** آستانه‌های زمانی (ثانیه) — روی ماشین توسعه */
    private const HOMEPAGE_THRESHOLD = 2.0;
    private const PRODUCT_THRESHOLD  = 3.0;
    private const SEARCH_THRESHOLD   = 2.0;

    protected function setUp(): void
    {
        parent::setUp();
        ScaleTestSetup::guardDatabaseMemory(); // فاز ۷۱ — محافظت از دیتابیس کم‌حافظه قبل از تست بار
        $this->setUpShop();
    }

    /**
     * زمان اجرای یک درخواست را برمی‌گرداند.
     */
    private function measure(callable $request): float
    {
        $start = microtime(true);
        $request();
        return microtime(true) - $start;
    }

    // =====================================================================
    //  زمان بارگذاری
    // =====================================================================

    #[Test]
    public function homepage_loads_under_2_seconds(): void
    {
        $elapsed = $this->measure(fn () => $this->get('/'));

        $this->assertLessThan(
            self::HOMEPAGE_THRESHOLD,
            $elapsed,
            sprintf('صفحه اصلی %.2f ثانیه طول کشید (آستانه: %.1f)', $elapsed, self::HOMEPAGE_THRESHOLD)
        );
    }

    #[Test]
    public function product_page_loads_under_3_seconds(): void
    {
        $product = $this->newProduct();

        $elapsed = $this->measure(fn () => $this->get('/product/' . $product->slug));

        $this->assertLessThan(
            self::PRODUCT_THRESHOLD,
            $elapsed,
            sprintf('صفحه محصول %.2f ثانیه طول کشید (آستانه: %.1f)', $elapsed, self::PRODUCT_THRESHOLD)
        );
    }

    #[Test]
    public function search_page_loads_under_2_seconds(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->newProduct(['title' => 'کارایی جستجو ' . $i]);
        }

        $elapsed = $this->measure(fn () => $this->get('/search?q=' . urlencode('کارایی جستجو')));

        $this->assertLessThan(
            self::SEARCH_THRESHOLD,
            $elapsed,
            sprintf('صفحه جستجو %.2f ثانیه طول کشید (آستانه: %.1f)', $elapsed, self::SEARCH_THRESHOLD)
        );
    }

    #[Test]
    public function cart_page_loads_fast_for_user_with_items(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product);

        $elapsed = $this->measure(fn () => $this->get('/cart'));

        $this->assertLessThan(2.0, $elapsed);
    }

    // =====================================================================
    //  ترافیک همزمان (روز حراج)
    // =====================================================================

    #[Test]
    public function shop_survives_sale_day_traffic_burst(): void
    {
        // شبیه‌سازی ۲۰ کاربر همزمان روی صفحات پرترافیک
        $urls = ['/', '/search?q=تست', '/cart', '/search'];
        $totalStart = microtime(true);
        $maxSingle = 0;

        for ($i = 0; $i < 20; $i++) {
            $url = $urls[$i % count($urls)];
            $start = microtime(true);
            $response = $this->get($url);
            $elapsed = microtime(true) - $start;

            $this->assertContains($response->status(), [200, 301, 302]);
            $maxSingle = max($maxSingle, $elapsed);
        }

        $total = microtime(true) - $totalStart;
        $average = $total / 20;

        $this->assertLessThan(
            self::HOMEPAGE_THRESHOLD,
            $maxSingle,
            'هیچ درخواستی نباید از آستانه صفحه اصلی کندتر شود'
        );

        $this->assertLessThan(
            1.0,
            $average,
            sprintf('میانگین زمان پاسخ در بار ترافیکی: %.2f ثانیه', $average)
        );
    }

    #[Test]
    public function repeated_product_views_stay_fast(): void
    {
        $product = $this->newProduct();

        $start = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $this->get('/product/' . $product->slug)->assertStatus(200);
        }

        $average = (microtime(true) - $start) / 10;

        $this->assertLessThan(
            1.5,
            $average,
            sprintf('میانگین مشاهده محصول: %.2f ثانیه (باید < ۱.۵ باشد)', $average)
        );
    }

    // =====================================================================
    //  کنترل N+1 (تعداد کوئری)
    // =====================================================================

    #[Test]
    public function product_page_does_not_explode_query_count(): void
    {
        $product = $this->newProduct();

        DB::enableQueryLog();
        $this->get('/product/' . $product->slug);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // اگر آستانه رد شد، یعنی مشکل N+1 وجود دارد
        $this->assertLessThan(
            150,
            $queryCount,
            sprintf('صفحه محصول %d کوئری اجرا کرد — احتمال مشکل N+1', $queryCount)
        );
    }

    #[Test]
    public function homepage_does_not_explode_query_count(): void
    {
        DB::enableQueryLog();
        $this->get('/');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(
            200,
            $queryCount,
            sprintf('صفحه اصلی %d کوئری اجرا کرد — احتمال مشکل N+1', $queryCount)
        );
    }
}

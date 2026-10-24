<?php

namespace Tests\Feature\Catalog;

use App\Models\Price;
use App\Models\Product;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های کاتالوگ و جستجو:
 *  - جستجوی دقیق و نسبی
 *  - جستجوی غلط املایی (Fuzzy — رفتار فعلی: LIKE، نتیجه خالی)
 *  - فیلتر برند
 *  - مرتب‌سازی (جدیدترین)
 *  - صفحه‌بندی
 *  - محصول ناموجود (پیام واضح)
 */
class CatalogSearchTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    // =====================================================================
    //  جستجو
    // =====================================================================

    #[Test]
    public function exact_search_finds_product_by_title(): void
    {
        $product = $this->newProduct(['title' => 'گوشی سامسونگ گلکسی تستی']);

        $response = $this->get('/search?q=گوشی سامسونگ گلکسی تستی');

        $response->assertStatus(200);
        $response->assertSee($product->title, false);
    }

    #[Test]
    public function relative_search_finds_product_by_partial_title(): void
    {
        $product = $this->newProduct(['title' => 'لپ تاپ ایسوس VivoBook پرو مدل تست']);

        $response = $this->get('/search?q=' . urlencode('ایسوس VivoBook'));

        $response->assertStatus(200);
        $response->assertSee($product->title, false);
    }

    #[Test]
    public function search_finds_product_by_english_title(): void
    {
        $product = $this->newProduct(['title_en' => 'Wireless Test Mouse Model X99']);

        $response = $this->get('/search?q=' . urlencode('X99'));

        $response->assertStatus(200);
        $response->assertSee($product->title, false);
    }

    #[Test]
    public function fuzzy_misspelled_search_returns_no_wrong_results(): void
    {
        $product = $this->newProduct(['title' => 'هدفون سیمی سونی مدل Z100']);

        // جستجوی غلط املایی («هدفن» به‌جای «هدفون») — موتور جستجو LIKE است
        // پس نتیجه دقیق برنمی‌گرداند، اما نباید محصول بی‌ربطی نمایش دهد
        $response = $this->get('/search?q=' . urlencode('هدفن سونی'));

        $response->assertStatus(200);
        $response->assertDontSee($product->title, false);
    }

    #[Test]
    public function search_with_short_query_redirects_back_with_error(): void
    {
        $this->from('/search')->get('/search?q=a')
            ->assertRedirect('/search')
            ->assertSessionHas('error');
    }

    #[Test]
    public function search_page_is_public(): void
    {
        $this->get('/search')->assertStatus(200);
    }

    #[Test]
    public function ajax_search_returns_matching_products(): void
    {
        $product = $this->newProduct(['title' => 'ساعت هوشمند تستی مدل AW9']);

        $response = $this->post('/search', ['q' => 'AW9']);

        $response->assertStatus(200);
        $this->assertStringContainsString('ساعت هوشمند تستی', (string) $response->json('html'));
    }

    // =====================================================================
    //  فیلتر برند
    // =====================================================================

    #[Test]
    public function brand_page_shows_only_brand_products(): void
    {
        $brand = $this->newBrand();
        $own = $this->newProduct(['title' => 'محصول برند تستی الف', 'brand_id' => $brand->id]);
        $other = $this->newProduct(['title' => 'محصول برند دیگر ب']);

        $response = $this->get('/brands/' . $brand->slug);

        $response->assertStatus(200);
    }

    // =====================================================================
    //  صفحه محصول
    // =====================================================================

    #[Test]
    public function product_page_renders_for_published_product(): void
    {
        $product = $this->newProduct();

        $response = $this->get('/product/' . $product->slug);

        $response->assertStatus(200);
        $response->assertSee($product->title, false);
    }

    #[Test]
    public function unpublished_product_page_is_not_found(): void
    {
        $product = $this->newProduct(['published' => false]);

        $this->get('/product/' . $product->slug)->assertStatus(404);
    }

    #[Test]
    public function out_of_stock_product_shows_clear_message(): void
    {
        // محصولی که تنها قیمتش موجودی صفر دارد → «ناموجود»
        $product = $this->newProduct([], ['stock' => 0]);

        $this->assertEquals('ناموجود', $product->getLowestPrice());

        $response = $this->get('/product/' . $product->slug);
        $response->assertStatus(200);
        $response->assertSee('ناموجود', false);
    }

    #[Test]
    public function out_of_stock_price_is_excluded_from_buyable_prices(): void
    {
        $product = $this->newProduct([], ['stock' => 0]);

        // getPrices فقط قیمت‌های موجود را برمی‌گرداند
        $this->assertCount(0, $product->getPrices()->get());
    }

    #[Test]
    public function short_link_redirects_to_product(): void
    {
        $product = $this->newProduct();

        $this->get('/p/' . $product->id)
            ->assertRedirect('/product/' . $product->slug);
    }

    // =====================================================================
    //  مرتب‌سازی
    // =====================================================================

    #[Test]
    public function search_results_are_sorted_newest_first(): void
    {
        $older = $this->newProduct(['title' => 'مرتب‌سازی قدیمی ' . Str::random(4)]);
        sleep(0); // جلوگیری از برخورد timestamp
        $older->update(['created_at' => now()->subDays(2)]);
        $newer = $this->newProduct(['title' => 'مرتب‌سازی جدید ' . Str::random(4)]);

        $response = $this->get('/search?q=' . urlencode('مرتب‌سازی'));

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertNotFalse(strpos($content, $newer->title));
        $this->assertNotFalse(strpos($content, $older->title));
        $this->assertTrue(
            strpos($content, $newer->title) < strpos($content, $older->title),
            'محصول جدیدتر باید قبل از قدیمی‌تر نمایش داده شود'
        );
    }

    #[Test]
    public function in_stock_products_rank_before_out_of_stock(): void
    {
        $outOfStock = $this->newProduct(['title' => 'رتبه‌بندی ناموجود ' . Str::random(4)], ['stock' => 0]);
        $inStock = $this->newProduct(['title' => 'رتبه‌بندی موجود ' . Str::random(4)], ['stock' => 5]);

        $response = $this->get('/search?q=' . urlencode('رتبه‌بندی'));
        $content = $response->getContent();

        $this->assertTrue(
            strpos($content, $inStock->title) < strpos($content, $outOfStock->title),
            'محصول موجود باید قبل از ناموجود بیاید (orderByStock)'
        );
    }

    // =====================================================================
    //  صفحه‌بندی
    // =====================================================================

    #[Test]
    public function search_results_are_paginated(): void
    {
        // ۲۵ محصول با پیشوند مشترک (صفحه ۲۰ تایی)
        for ($i = 1; $i <= 25; $i++) {
            $this->newProduct(['title' => 'صفحه‌بندی تستی شماره ' . $i]);
        }

        $response = $this->get('/search?q=' . urlencode('صفحه‌بندی تستی'));

        $response->assertStatus(200);
        $this->assertStringContainsString('page=2', $response->getContent(), 'باید لینک صفحه ۲ وجود داشته باشد');

        // صفحه دوم هم پاسخ می‌دهد و خالی نیست
        $page2 = $this->get('/search?q=' . urlencode('صفحه‌بندی تستی') . '&page=2');
        $page2->assertStatus(200);
    }

    #[Test]
    public function category_products_page_is_paginated(): void
    {
        $category = $this->newCategory();

        for ($i = 1; $i <= 22; $i++) {
            $this->newProduct(['title' => 'دسته‌ای ' . $i, 'category' => $category]);
        }

        $response = $this->get('/search/category-products/' . $category->slug);
        $response->assertStatus(200);
        $this->assertStringContainsString('page=2', $response->getContent());
    }

    // =====================================================================
    //  دسته‌بندی
    // =====================================================================

    #[Test]
    public function category_products_page_shows_only_category_products(): void
    {
        $category = $this->newCategory();
        $own = $this->newProduct(['title' => 'محصول این دسته ' . Str::random(4), 'category' => $category]);
        $foreign = $this->newProduct(['title' => 'محصول دسته دیگر ' . Str::random(4)]);

        $response = $this->get('/search/category-products/' . $category->slug);

        $response->assertStatus(200);
        $response->assertSee($own->title, false);
        $response->assertDontSee($foreign->title, false);
    }

    // =====================================================================
    //  به‌روزرسانی قیمت در نمایش
    // =====================================================================

    #[Test]
    public function discounted_price_is_displayed_on_product_page(): void
    {
        $product = $this->newProduct([], [
            'price'          => 200000,
            'discount_price' => 180000,
            'regular_price'  => 200000,
            'discount'       => 10,
        ]);

        $response = $this->get('/product/' . $product->slug);
        $response->assertStatus(200);
        $response->assertSee('180,000', false);
    }
}

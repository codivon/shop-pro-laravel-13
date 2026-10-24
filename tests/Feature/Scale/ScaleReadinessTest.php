<?php

namespace Tests\Feature\Scale;

use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های «آمادگی برای مقیاس» — زیرساخت‌هایی که فروشگاه را برای
 * میلیون‌ها محصول / هزاران دسته‌بندی / میلیون‌ها کاربر آماده می‌کنند:
 *
 *  ۱) ستون‌های دینرمال‌شده (in_stock / min_price) و همگام‌سازی با PriceObserver
 *  ۲) باگ بحرانی قدیم: Observerها کش‌ها را در «هر ریکوئست» پاک می‌کردند
 *     (کانستراکتور) — حالا باید کش‌ها بعد از instantiate شدن Observer زنده بمانند
 *  ۳) وجود ایندکس‌های FULLTEXT / ترکیبی که جستجو و ویترین میلیون رکورد را ممکن می‌کنند
 *  ۴) helper های زیردرخت دسته‌ها و شمارش محصولات (جایگزین N+1)
 */
class ScaleReadinessTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    // =====================================================================
    //  ۱) ستون‌های دینرمال‌شده + PriceObserver
    // =====================================================================

    #[Test]
    public function products_have_denormalized_columns_after_migration(): void
    {
        $this->assertTrue(\Schema::hasColumn('products', 'in_stock'));
        $this->assertTrue(\Schema::hasColumn('products', 'min_price'));
    }

    #[Test]
    public function creating_price_syncs_in_stock_and_min_price(): void
    {
        $product = $this->newProduct([
            'price_type' => 'multiple-price',
        ], [
            'price'         => 250000,
            'discount_price' => 250000,
            'regular_price' => 250000,
            'stock'         => 7,
        ]);

        $product->refresh();

        $this->assertEquals(1, $product->in_stock);
        $this->assertNotNull($product->min_price);
        $this->assertEquals(250000, (int) $product->min_price);
    }

    #[Test]
    public function setting_stock_to_zero_flips_in_stock_off(): void
    {
        $product = $this->newProduct(['price_type' => 'multiple-price']);
        $price   = $product->prices()->first();

        $price->update(['stock' => 0]);

        $product->refresh();
        $this->assertEquals(0, $product->in_stock);
        $this->assertNull($product->min_price);
    }

    #[Test]
    public function deleting_last_price_clears_denormalized_columns(): void
    {
        $product = $this->newProduct(['price_type' => 'multiple-price']);
        $price   = $product->prices()->first();

        $price->delete();

        $product->refresh();
        $this->assertEquals(0, $product->in_stock);
        $this->assertNull($product->min_price);
    }

    #[Test]
    public function cheapest_in_stock_price_wins_for_min_price(): void
    {
        $product = $this->newProduct(['price_type' => 'multiple-price'], [
            'price' => 500000, 'discount_price' => 500000, 'regular_price' => 500000, 'stock' => 5,
        ]);

        $this->newPrice($product, [
            'price' => 120000, 'discount_price' => 120000, 'regular_price' => 120000, 'stock' => 3,
        ]);

        $product->refresh();
        $this->assertEquals(120000, (int) $product->min_price);
    }

    #[Test]
    public function download_products_count_as_available_via_available_scope(): void
    {
        // محصول دانلودی حتی با قیمت ناموجود باید «موجود» حساب شود (رفتار قدیم)
        $product = $this->newDownloadProduct();

        $available = Product::where('id', $product->id)->available()->exists();

        $this->assertTrue($available);
    }

    // =====================================================================
    //  ۲) باگ کش Observerها (قبلاً هر ریکوئست کش را می‌سوزاند)
    // =====================================================================

    #[Test]
    public function observer_instantiation_does_not_wipe_menu_cache(): void
    {
        // کش را گرم کن
        Cache::rememberForever('front.productcats', function () {
            return collect(['cached-tree']);
        });
        Cache::put('front.category.map', [1 => null], 600);

        // Observerها در هر ریکوئست instantiate می‌شوند — نباید کش را پاک کنند
        new ProductObserver();
        new CategoryObserver();

        $this->assertTrue(Cache::has('front.productcats'));
        $this->assertTrue(Cache::has('front.category.map'));
    }

    #[Test]
    public function category_events_do_invalidate_menu_cache(): void
    {
        Cache::rememberForever('front.productcats', function () {
            return collect(['cached-tree']);
        });

        // رویداد واقعی (ایجاد دسته) باید کش را باطل کند
        $this->newCategory();

        $this->assertFalse(Cache::has('front.productcats'));
    }

    #[Test]
    public function product_events_do_invalidate_product_count_cache(): void
    {
        Cache::put('admin.products_count', 42, 600);

        $this->newProduct();

        $this->assertFalse(Cache::has('admin.products_count'));
    }

    #[Test]
    public function menu_cache_survives_two_homepage_requests(): void
    {
        // رندر اول: کش منو ساخته می‌شود
        $this->get('/')->assertStatus(200);
        $this->assertTrue(Cache::has('front.productcats'));

        // رندر دوم: کش باید هنوز همان باشد (در باگ قدیم، boot شدن Observer
        // در هر ریکوئست کش را می‌سوزاند و دوباره ساخته می‌شد)
        $first = Cache::get('front.productcats');
        $this->get('/')->assertStatus(200);
        $this->assertTrue(Cache::has('front.productcats'));
        $this->assertSame($first, Cache::get('front.productcats'));
    }

    // =====================================================================
    //  ۳) ایندکس‌های مقیاس
    // =====================================================================

    #[Test]
    public function fulltext_index_exists_on_products(): void
    {
        $rows = \DB::table('information_schema.statistics')
            ->where('table_schema', \DB::raw('DATABASE()'))
            ->where('table_name', 'products')
            ->where('index_type', 'FULLTEXT')
            ->get();

        $this->assertTrue($rows->count() > 0, 'ایندکس FULLTEXT روی products وجود ندارد');

        // PDO نام ستون‌های information_schema را UPPERCASE برمی‌گرداند
        $columns = $rows->pluck('COLUMN_NAME')->all();
        $this->assertContains('title', $columns);
        $this->assertContains('title_en', $columns);
        $this->assertContains('description', $columns);
    }

    #[Test]
    public function fulltext_match_query_executes_against_committed_data(): void
    {
        // MATCH روی داده‌های committed (داده‌های seed) — در تراکنش باز دیده نمی‌شود
        // پس اینجا فقط با کوئری خام صحت اجرا و بازگشت نتیجه چک می‌شود
        $rows = \DB::select(
            "SELECT id FROM products WHERE MATCH (title, title_en, description) AGAINST (? IN BOOLEAN MODE) LIMIT 1",
            ['"محصول" محصول*']
        );

        $this->assertIsArray($rows);
    }

    #[Test]
    public function scale_indexes_exist(): void
    {
        $expected = [
            ['products', 'products_fulltext_index'],
            ['products', 'products_in_stock_index'],
            ['products', 'products_min_price_index'],
            ['products', 'products_published_created_at_index'],
            ['products', 'products_category_published_index'],
            ['products', 'products_brand_published_index'],
            ['prices', 'prices_product_stock_index'],
            ['orders', 'orders_user_created_at_index'],
            ['favorites', 'favorites_user_product_unique'],
        ];

        foreach ($expected as [$table, $index]) {
            $exists = \DB::table('information_schema.statistics')
                ->where('table_schema', \DB::raw('DATABASE()'))
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();

            $this->assertTrue($exists, "ایندکس {$index} روی جدول {$table} وجود ندارد");
        }
    }

    #[Test]
    public function duplicate_favorites_cannot_be_inserted(): void
    {
        $user    = $this->newUser();
        $product = $this->newProduct();

        $user->favorites()->create(['product_id' => $product->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $user->favorites()->create(['product_id' => $product->id]);
    }

    // =====================================================================
    //  ۴) helper های مقیاس (جایگزین N+1)
    // =====================================================================

    #[Test]
    public function category_subtree_ids_include_children(): void
    {
        $parent = $this->newCategory();
        $child  = $this->newCategory(['category_id' => $parent->id]);
        $grand  = $this->newCategory(['category_id' => $child->id]);
        $other  = $this->newCategory();

        Cache::forget('front.category.map');

        $ids = front_category_subtree_ids($parent);

        $this->assertContains($parent->id, $ids);
        $this->assertContains($child->id, $ids);
        $this->assertContains($grand->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    #[Test]
    public function category_published_counts_uses_one_query_not_n_plus_one(): void
    {
        $cat1 = $this->newCategory();
        $cat2 = $this->newCategory();

        $this->newProduct(['category' => $cat1]);
        $this->newProduct(['category' => $cat1]);
        $this->newProduct(['category' => $cat2]);

        Cache::forget('front.category.map');
        Cache::forget('front.categories.version');

        $queryCount = 0;
        \DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $counts = front_category_published_counts(collect([$cat1, $cat2]));

        // حداکثر: مپ دسته‌ها + یک شمارش تجمیعی (+ کوئری version) — نه ۲ کوئری به‌ازای هر دسته
        $this->assertLessThanOrEqual(5, $queryCount, 'شمارش محصولات دسته‌ها N+1 شده است');
        $this->assertEquals(2, $counts[$cat1->id]);
        $this->assertEquals(1, $counts[$cat2->id]);
    }

    #[Test]
    public function category_counts_include_subtree_products(): void
    {
        $parent = $this->newCategory();
        $child  = $this->newCategory(['category_id' => $parent->id]);

        // محصول فقط به زیر‌دسته وصل است
        $this->newProduct(['category' => $child]);

        Cache::forget('front.category.map');
        Cache::forget('front.categories.version');

        $counts = front_category_published_counts(collect([$parent]));

        $this->assertEquals(1, $counts[$parent->id]);
    }

    #[Test]
    public function homepage_categories_widget_does_not_explode_query_count(): void
    {
        // ۸ دسته ریشه — در باگ قدیم، ویجت دسته‌ها ۲ کوئری به‌ازای هر دسته می‌زد
        $categories = collect();
        for ($i = 0; $i < 8; $i++) {
            $categories->push($this->newCategory());
        }
        $this->newProduct(['category' => $categories->first()]);

        Cache::forget('front.category.map');
        Cache::forget('front.categories.version');

        $categoryQueryCount = 0;
        \DB::listen(function ($query) use (&$categoryQueryCount) {
            // فقط کوئری‌های جدول categories را بشمار
            if (str_contains($query->sql, 'categories') && !str_contains($query->sql, 'category_product')) {
                $categoryQueryCount++;
            }
        });

        $this->get('/')->assertStatus(200);

        // در باگ قدیم: ~۲ کوئری بازگشتی به‌ازای هر دسته + کوئری‌های منو = ۲۰+
        // حالا: منوی کش‌شده + یک شمارش تجمیعی
        $this->assertLessThanOrEqual(15, $categoryQueryCount, 'ویجت دسته‌بندی‌ها N+1 دارد');
    }
}

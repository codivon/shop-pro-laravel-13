<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * آماده‌سازی فروشگاه برای مقیاس میلیون‌ها محصول / میلیون‌ها کاربر:
 *
 *  ۱) ستون‌های دینرمال‌شده روی products:
 *     - in_stock  : آیا حداقل یک قیمت با موجودی > 0 دارد (نگهداری توسط PriceObserver)
 *     - min_price : ارزان‌ترین قیمت مؤثر (با احتساب تخفیف) — برای مرتب‌سازی/فیلتر قیمت
 *     این دو ستون جایگزین correlated-subquery های سنگین orderByStock / orderByPrice
 *     و whereHas('prices') در scopeAvailable می‌شوند.
 *
 *  ۲) ایندکس‌های ترکیبی پرکاربرد برای کوئری‌های ویترین:
 *     - products (published, created_at)          → مرتب‌سازی «جدیدترین» در لیستینگ‌ها
 *     - products (category_id, published)         → صفحه‌بندی بر اساس دسته
 *     - products (brand_id, published)            → صفحه‌ی برند
 *     - prices   (product_id, stock)              → lowestPrice / در-stock subquery ها
 *     - orders   (user_id, created_at)            → «سفارش‌های من» مرتب بر اساس تاریخ
 *     - favorites UNIQUE (user_id, product_id)    → جلوگیری از تکرار + جستجوی یکتا
 *
 *  ۳) ایندکس FULLTEXT روی products(title, title_en, description) برای جستجوی
 *     میلیون رکورد بدون LIKE '%…%' (که full-scan است).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- favorites: حذف تکراری‌ها و ایندکس یکتا ----------
        DB::statement('DELETE f1 FROM favorites f1 JOIN favorites f2 ON f1.user_id = f2.user_id AND f1.product_id = f2.product_id AND f1.id > f2.id');

        if (! $this->indexExists('favorites', 'favorites_user_product_unique')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->unique(['user_id', 'product_id'], 'favorites_user_product_unique');
            });
        }

        // ---------- orders: سفارش‌های من (کاربر + تاریخ) ----------
        if (! $this->indexExists('orders', 'orders_user_created_at_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'orders_user_created_at_index');
            });
        }

        // ---------- prices: subquery موجودی per-product ----------
        if (! $this->indexExists('prices', 'prices_product_stock_index')) {
            Schema::table('prices', function (Blueprint $table) {
                $table->index(['product_id', 'stock'], 'prices_product_stock_index');
            });
        }

        // ---------- products: ستون‌های دینرمال‌شده + ایندکس‌ها ----------
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'in_stock')) {
                $table->boolean('in_stock')->default(false)->index('products_in_stock_index')->after('published');
            }

            if (! Schema::hasColumn('products', 'min_price')) {
                $table->bigInteger('min_price')->nullable()->index('products_min_price_index')->after('in_stock');
            }
        });

        foreach ([
            ['products', 'products_published_created_at_index', 'ALTER TABLE `products` ADD INDEX `products_published_created_at_index` (`published`, `created_at`)'],
            ['products', 'products_category_published_index', 'ALTER TABLE `products` ADD INDEX `products_category_published_index` (`category_id`, `published`)'],
            ['products', 'products_brand_published_index', 'ALTER TABLE `products` ADD INDEX `products_brand_published_index` (`brand_id`, `published`)'],
        ] as [$table, $index, $sql]) {
            if (! $this->indexExists($table, $index)) {
                DB::statement($sql);
            }
        }

        // ---------- Backfill ستون‌های دینرمال‌شده ----------
        DB::statement('UPDATE products p SET p.in_stock = EXISTS(SELECT 1 FROM prices pr WHERE pr.product_id = p.id AND pr.stock > 0 AND pr.deleted_at IS NULL)');
        DB::statement('UPDATE products p SET p.min_price = (SELECT MIN(IF(pr.discount_expire_at IS NULL OR DATE(pr.discount_expire_at) > NOW(), pr.discount_price, pr.regular_price)) FROM prices pr WHERE pr.product_id = p.id AND pr.deleted_at IS NULL AND pr.stock > 0)');

        // ---------- FULLTEXT برای جستجوی میلیون رکورد ----------
        if (! $this->fulltextExists('products')) {
            DB::statement('ALTER TABLE `products` ADD FULLTEXT INDEX `products_fulltext_index` (`title`, `title_en`, `description`)');
        }
    }

    public function down(): void
    {
        if ($this->fulltextExists('products')) {
            DB::statement('ALTER TABLE `products` DROP INDEX `products_fulltext_index`');
        }

        foreach ([
            ['products', 'products_brand_published_index'],
            ['products', 'products_category_published_index'],
            ['products', 'products_published_created_at_index'],
            ['prices', 'prices_product_stock_index'],
            ['orders', 'orders_user_created_at_index'],
            ['favorites', 'favorites_user_product_unique'],
        ] as [$table, $index]) {
            if ($this->indexExists($table, $index)) {
                Schema::table($table, function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'min_price')) {
                $table->dropColumn('min_price');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'in_stock')) {
                $table->dropColumn('in_stock');
            }
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    protected function fulltextExists(string $table): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('index_type', 'FULLTEXT')
            ->exists();
    }
};

<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * زیرساخت تست‌های مقیاس‌پذیری (میلیون محصول / میلیون کاربر).
 *
 * نکات طراحی:
 *  - برخلاف ShopTestSetup از DatabaseTransactions استفاده «نمی‌کنیم»؛
 *    درج انبوه (bulk insert) داخل یک تراکنش بزرگ هم در InnoDB مجاز است ولی
 *    برای اندازه‌گیری واقعی زمان پاسخ بهتر است داده‌ها Commit شده باشند.
 *  - همه ردیف‌ها با «نشانگر» (SCALEP / scale-product- / 0999...) درج می‌شوند
 *    و در setUp و tearDown با همان نشانگر پاک می‌شوند تا هیچ اثری نماند.
 *  - تعداد ردیف‌ها با متغیر محیطی قابل تنظیم است تا روی هر ماشینی قابل اجرا باشد:
 *      SCALE_PRODUCTS (پیش‌فرض ۳۰۰۰۰)   SCALE_USERS (پیش‌فرض ۲۰۰۰۰)
 *      SCALE_CONCURRENT (پیش‌فرض ۱۰۰۰)
 *  - با ۳۰هزار محصول، رفتار کوئری‌ها (ایندکس‌خور بودن، آستانه زمان) همان
 *    رفتاری است که در میلیون ردیف داریم؛ این آزمون «شیب رشد» را کنترل می‌کند.
 */
trait ScaleTestSetup
{
    /** بازه شناسه‌های درج‌شده برای پاکسازی دقیق */
    private array $scaleRanges = [];

    protected function setUpScale(): void
    {
        $this->cleanupScaleData(); // پاکسازی بقایای اجرای قبلی (اگر تست وسط راه خورده باشد)
    }

    protected function tearDownScale(): void
    {
        $this->cleanupScaleData();
    }

    protected function cleanupScaleData(): void
    {
        // محصولات با نشانگر
        DB::table('products')->where('product_id', 'like', 'SCALEP%')->delete();

        // قیمت‌های محصولات نشان‌دار (LEFT JOIN حذف امن)
        DB::statement(
            'DELETE p FROM prices p LEFT JOIN products pr ON pr.id = p.product_id
             WHERE pr.id IS NULL OR pr.product_id LIKE "SCALEP%"'
        );

        // پیوت دسته‌بندی‌های یتیم‌شده از محصولات نشان‌دار
        DB::statement(
            'DELETE cp FROM category_product cp
             LEFT JOIN products pr ON pr.id = cp.product_id
             WHERE pr.id IS NULL OR pr.product_id LIKE "SCALEP%"'
        );

        // دسته‌بندی نشان‌دار
        DB::table('categories')->where('slug', 'like', 'scale-cat-%')->delete();

        // کاربران نشان‌دار
        DB::table('users')->where('email', 'like', '%@scale.test')->delete();

        $this->scaleRanges = [];
    }

    /**
     * تعداد ردیف‌های مقیاس از متغیر محیطی (قابل تنظیم روی ماشین‌های ضعیف‌تر).
     */
    protected function scaleProductCount(): int
    {
        return (int) env('SCALE_PRODUCTS', 30000);
    }

    protected function scaleUserCount(): int
    {
        return (int) env('SCALE_USERS', 20000);
    }

    protected function scaleConcurrentCount(): int
    {
        return (int) env('SCALE_CONCURRENT', 1000);
    }

    /**
     * درج انبوه محصولات + قیمت‌ها + اتصال به دسته‌بندی (chunk × 1000).
     */
    protected function seedScaleProducts(?int $count = null, array $titleOverrides = []): int
    {
        $count = $count ?? $this->scaleProductCount();
        $now   = now();

        // دسته‌بندی نشان‌دار
        $categoryId = DB::table('categories')->insertGetId([
            'title'     => 'دسته مقیاس',
            'slug'      => 'scale-cat-' . uniqid(),
            'type'      => 'productcat',
            'published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $startId = (int) (DB::table('products')->max('id') ?? 0) + 1;
        $this->scaleRanges['products'] = [$startId, $startId + $count - 1];

        foreach (array_chunk(range(0, $count - 1), 1000) as $chunk) {
            $products = $prices = $pivots = [];

            foreach ($chunk as $i) {
                $id       = $startId + $i;
                $pid      = 'SCALEP' . str_pad((string) $id, 10, '0', STR_PAD_LEFT);
                $title    = $titleOverrides[$i] ?? ('محصول مقیاس ' . $i . ' SCALESEARCH');
                $products[] = [
                    'id'          => $id,
                    'product_id'  => $pid,
                    'title'       => $title,
                    'title_en'    => 'Scale Product ' . $i,
                    'slug'        => 'scale-product-' . $id,
                    'type'        => 'physical',
                    'category_id' => $categoryId,
                    'price_type'  => 'single-price',
                    'weight'      => 500,
                    'description' => 'توضیحات محصول مقیاس ' . $i,
                    'short_description' => 'خلاصه ' . $i,
                    'special'     => 0,
                    'published'   => 1,
                    'status'      => 'Accept',
                    'sell'        => 0,
                    'view'        => 0,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];

                $prices[] = [
                    'product_id'     => $id,
                    'price'          => 100000 + $i,
                    'discount_price' => 100000 + $i,
                    'regular_price'  => 100000 + $i,
                    'discount'       => 0,
                    'stock'          => 10,
                    'published'      => 1,
                    'seller_id'      => null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

                $pivots[] = ['category_id' => $categoryId, 'product_id' => $id];
            }

            DB::table('products')->insert($products);
            DB::table('prices')->insert($prices);
            DB::table('category_product')->insert($pivots);
        }

        return $count;
    }

    /**
     * درج انبوه کاربران تأییدشده با موبایل یکتا (۰۹۹۹...).
     * همه یک هش رمز یکسان دارند (BCRYPT_ROUNDS=4 در تست) تا ورود سریع باشد.
     */
    protected function seedScaleUsers(?int $count = null, string $password = 'scale-pass-123'): string
    {
        $count = $count ?? $this->scaleUserCount();
        $now   = now();
        $hash  = Hash::make($password);

        foreach (array_chunk(range(1, $count), 1000) as $chunk) {
            $users = [];

            foreach ($chunk as $i) {
                $mobile  = '0999' . str_pad((string) $i, 7, '0', STR_PAD_LEFT); // 09990000001…
                $users[] = [
                    'username'    => $mobile,
                    'mobile'      => $mobile,
                    'email'       => 'scale' . $i . '@scale.test',
                    'first_name'  => 'کاربر',
                    'last_name'   => 'مقیاس',
                    'level'       => 'user',
                    'status'      => 1,
                    'password'    => $hash,
                    'verified_at' => $now,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            DB::table('users')->insert($users);
        }

        return $password;
    }

    /**
     * آخرین موبایل درج‌شده از مجموعه مقیاس (بدترین حالت برای ایندکس tail).
     */
    protected function lastScaleUserMobile(): string
    {
        return '0999' . str_pad((string) $this->scaleUserCount(), 7, '0', STR_PAD_LEFT);
    }

    /**
     * اندازه‌گیری زمان اجرای یک کلوژر به ثانیه.
     */
    protected function measureSeconds(callable $fn): float
    {
        $start = microtime(true);
        $fn();
        return microtime(true) - $start;
    }
}

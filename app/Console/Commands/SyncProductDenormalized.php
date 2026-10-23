<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * همگام‌سازی مجدد ستون‌های دینرمال‌شدهٔ products (in_stock / min_price) با جدول prices.
 *
 * معمولاً PriceObserver آن‌ها را همیشه به‌روز نگه می‌دارد؛ این دستور برای
 * اصلاح مواردی است که مستقیماً با Query Builder آپدیت شده‌اند (بدون رویداد مدل)
 * مثل آپدیت انبوه انبار، ایمپورت اکسل یا تعمیرات دستی دیتابیس.
 *
 * استفاده:  php artisan products:sync-denormalized
 */
class SyncProductDenormalized extends Command
{
    protected $signature = 'products:sync-denormalized {--chunk=1000 : تعداد رکورد در هر دسته}';

    protected $description = 'همگام‌سازی in_stock و min_price جدول products با prices (برای مقیاس میلیون رکورد)';

    public function handle(): int
    {
        if (! \App\Models\Product::denormalizedReady()) {
            $this->error('ستون‌های in_stock/min_price روی جدول products وجود ندارند. ابتدا مایگریشن scale_readiness_for_millions را اجرا کنید.');

            return self::FAILURE;
        }

        $chunk = max(100, (int) $this->option('chunk'));

        $this->info('به‌روزرسانی درشت‌دانه با یک کوئری (سریع، مناسب همهٔ مقیاس‌ها)...');

        // یک کوئری مستقیم — سریع‌ترین حالت برای هر مقیاسی
        DB::statement('UPDATE products p SET p.in_stock = EXISTS(SELECT 1 FROM prices pr WHERE pr.product_id = p.id AND pr.stock > 0 AND pr.deleted_at IS NULL)');
        DB::statement('UPDATE products p SET p.min_price = (SELECT MIN(IF(pr.discount_expire_at IS NULL OR DATE(pr.discount_expire_at) > NOW(), pr.discount_price, pr.regular_price)) FROM prices pr WHERE pr.product_id = p.id AND pr.deleted_at IS NULL AND pr.stock > 0)');

        $total = DB::table('products')->where('in_stock', true)->count();

        $this->info("انجام شد. محصولات در-استوک: {$total}");

        return self::SUCCESS;
    }
}

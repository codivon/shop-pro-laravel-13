<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * فاز ۲۵ — بزرگ‌کردن ستون‌های عنوان (title) و آدرس (slug)
 *
 * خطای واقعی کاربر:
 *   SQLSTATE[22001]: Data too long for column 'slug' at row 1 (1406)
 *
 * دلایل:
 *   ۱) موتور اسلاگِ پیش‌فرض (cocur/slugify) حروف فارسی را حذف می‌کرد و خروجی
 *      بی‌معنیِ خیلی طولانی می‌ساخت (رفع شد: config/sluggable.php از
 *      sluggable_helper_function استفاده می‌کند).
 *   ۲) ستون‌ها از نسخه‌های قدیمی varchar(191) بودند — کمتر از حد نیاز.
 *
 * این مایگریشن همه‌ی ستون‌های title/slug/meta_title از نوع varchar را به
 * حداقل ۵۰۰ کاراکتر می‌رساند (خواسته‌ی کاربر: «حداقل ۲۵۵»).
 *
 * نکته فنی: از ALTER TABLE ... MODIFY استفاده شده تا ایندکس‌ها (یکتای اسلاگ‌ها
 * و ایندکس‌های عنوان) دست‌نخورده باقی بمانند — MySQL ایندکس‌های ستون را هنگام
 * تغییر نوع ستون به‌صورت خودکار بازسازی و حفظ می‌کند. تعریف ستون (charset،
 * nullability، default و…) از information_schema خوانده و عیناً حفظ می‌شود.
 */
return new class extends Migration
{
    /** اندازه‌ی هدف برای ستون‌های رشته‌ای */
    protected const TARGET_LENGTH = 500;

    /**
     * جدول‌های داخل پروژه که ستون‌های title/slug/meta_title از نوع varchar دارند.
     * (ستون‌های سیستمی packages_cache و installed_modules مستثنا هستند —
     *  مقدارهایشان شناسه‌ی کنترل‌شده است و نیازی به بزرگ‌سازی ندارد)
     */
    protected function targets(): array
    {
        return [
            'products'   => ['title', 'slug'],
            'order_items'=> ['title'],
            'categories' => ['title', 'slug', 'meta_title'],
            'posts'      => ['title', 'slug'],
            'pages'      => ['title', 'slug', 'meta_title'],
            'forms'      => ['title', 'slug', 'meta_title'],
            'brands'     => ['slug'],
            'tags'       => ['slug'],
            'sellers'    => ['slug'],
            'roles'      => ['title', 'slug'],
            'banners'         => ['title'],
            'carriers'        => ['title'],
            'currencies'      => ['title'],
            'discounts'       => ['title'],
            'filds'           => ['title'],
            'files'           => ['title'],
            'filters'         => ['title'],
            'labels'          => ['title'],
            'links'           => ['title'],
            'menus'           => ['title'],
            'messages'        => ['title'],
            'notification_manages' => ['title'],
            'permissions'     => ['title'],
            'return_reasons'  => ['title'],
            'reviews'         => ['title'],
            'sizes'           => ['title'],
            'size_types'      => ['title'],
            'sliders'         => ['title'],
            'static_filters'  => ['title'],
            'widgets'         => ['title'],
        ];
    }

    public function up(): void
    {
        foreach ($this->targets() as $table => $columns) {
            if (! \Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                $this->enlargeColumn($table, $column);
            }
        }
    }

    /**
     * بزرگ‌کردن یک ستون varchar با حفظ کامل تعریف و ایندکس‌هایش
     */
    protected function enlargeColumn(string $table, string $column): void
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_SET_NAME, COLLATION_NAME, EXTRA, COLUMN_COMMENT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        if (! $row) {
            return;
        }

        // فقط ستون‌های varchar کوچک‌تر از هدف
        if (! str_starts_with((string) $row->COLUMN_TYPE, 'varchar(')) {
            return;
        }

        preg_match('/^varchar\((\d+)\)$/', (string) $row->COLUMN_TYPE, $m);

        if (! $m || (int) $m[1] >= self::TARGET_LENGTH) {
            return;
        }

        $definition = "varchar(" . self::TARGET_LENGTH . ")";

        if ($row->CHARACTER_SET_NAME) {
            $definition .= " CHARACTER SET {$row->CHARACTER_SET_NAME}";
        }

        if ($row->COLLATION_NAME) {
            $definition .= " COLLATE {$row->COLLATION_NAME}";
        }

        $definition .= $row->IS_NULLABLE === 'YES' ? ' NULL' : ' NOT NULL';

        if ($row->COLUMN_DEFAULT !== null) {
            $definition .= " DEFAULT " . DB::getPdo()->quote((string) $row->COLUMN_DEFAULT);
        } elseif ($row->IS_NULLABLE === 'YES') {
            $definition .= " DEFAULT NULL";
        }

        if ((string) $row->EXTRA !== '') {
            $definition .= " {$row->EXTRA}";
        }

        if ((string) $row->COLUMN_COMMENT !== '') {
            $definition .= " COMMENT " . DB::getPdo()->quote((string) $row->COLUMN_COMMENT);
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
    }

    public function down(): void
    {
        // برگرداندن به اندازه‌ی قبلی بی‌خطر نیست (داده‌های بلندتر truncate می‌شوند) — عمداً خالی
    }
};

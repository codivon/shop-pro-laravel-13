<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ★ فاز ۲۲ (بهینه‌سازی مانیتور سیستم):
 * کوئری‌های صفحه مانیتور (WHERE type = ? AND timestamp >= ? ORDER BY value DESC)
 * روی pulse_entries ایندکس ترکیبی (type, timestamp) نداشتند و بعد از فیلتر،
 * filesort روی همه ردیف‌های ۲۴ ساعت اخیر اجرا می‌شد — با این ایندکس فیلتر نوع
 * + بازه زمانی مستقیماً از ایندکس خوانده می‌شود.
 *
 * توجه: هاست مقصد MariaDB است؛ برای سازگاری، ایندکس‌های موجود با SHOW INDEX چک می‌شوند
 * تا اجرای مجدد مایگریشن هم امن باشد.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pulse_entries')) {
            return;
        }

        $this->addIndexIfMissing('pulse_entries', 'pulse_entries_type_timestamp_index', ['type', 'timestamp']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('pulse_entries')) {
            return;
        }

        if ($this->indexExists('pulse_entries', 'pulse_entries_type_timestamp_index')) {
            Schema::table('pulse_entries', function (Blueprint $table) {
                $table->dropIndex('pulse_entries_type_timestamp_index');
            });
        }
    }

    protected function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
            $t->index($columns, $indexName);
        });
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۱۰۳ — چندفروشندگی پیشرفته: کمیسیون اختصاصی هر فروشنده.
 *
 * ستون nullable: null یعنی «بدون override» → کمیسیونِ دسته‌بندی/پیش‌فرض
 * سراسری اعمال می‌شود (دقیقاً رفتار قبل از این فاز). ادمین از «پنل
 * کمیسیون فروشندگان» آن را تنظیم می‌کند و خواندنش با گیت کلید
 * multi_vendor_seller_commission_override کنترل می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (! Schema::hasColumn('sellers', 'commission')) {
                $table->integer('commission')->nullable()->after('status_work');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (Schema::hasColumn('sellers', 'commission')) {
                $table->dropColumn('commission');
            }
        });
    }
};

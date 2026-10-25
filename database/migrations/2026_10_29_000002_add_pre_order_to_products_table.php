<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۷۸ — ستون پیش‌فروش برای محصولات.
 *
 * وقتی فعال است، محصول حتی با موجودی صفر قابل سفارش است (ثبت سفارش
 * پیش‌فروش)؛ رزرو/کسر موجودی برای آیتم‌های پیش‌فروش اعمال نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('pre_order')->default(false)->after('special');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('pre_order');
        });
    }
};

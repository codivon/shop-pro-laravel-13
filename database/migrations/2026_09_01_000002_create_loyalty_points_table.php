<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول امتیازهای وفاداری — مدل «لات» (Lot).
 *
 * هر سطر یک لات است:
 *  - سطرهای مثبت (type=award/manual/adjust مثبت): points = امتیاز اولیهٔ لات،
 *    remaining = باقی‌ماندهٔ مصرف‌نشدهٔ همان لات (موجودی = SUM(remaining)).
 *  - سطرهای منفی audit (type=redeem/expire): points منفی، remaining = 0 —
 *    دفتر کل؛ موجودی را خراب نمی‌کنند.
 *
 * کسر مصرف FIFO است: از لات با نزدیک‌ترین expires_at (NULLها آخر) شروع.
 * یکتایی (user_id, source_type, source_id) → idempotent بودن رویدادها
 * (دیسپچ دوبارهٔ OrderPaid امتیاز دوباره نمی‌دهد). در MySQL چند NULL در
 * ایندکس یگانه مجاز است، پس سطرهای audit مشکلی ندارند.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loyalty_points')) {
            return;
        }

        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->integer('points');            // + برای لات، − برای audit
            $table->integer('remaining')->default(0); // باقی‌ماندهٔ مصرف‌نشدهٔ لات (سطرهای audit صفر)
            $table->string('type', 24)->index();  // award | manual | adjust | redeem | expire
            $table->string('event', 32)->nullable()->index(); // purchase|register|review|birthday|referral_tier
            $table->string('source_type', 32)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'source_type', 'source_id'], 'cc_unique_source');
            $table->index(['user_id', 'remaining']);
            $table->index(['expires_at', 'remaining']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
    }
};

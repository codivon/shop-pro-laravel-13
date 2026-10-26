<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۷۸ — جدول هشدار کاهش قیمت.
 *
 * هر ردیف = اشتراک یک موبایل (کاربر یا مهمان) روی یک محصول برای اطلاع از
 * افت قیمت. target_price قیمتِ مؤثرِ لحظهٔ اشتراک است؛ وقتی کمترین قیمت
 * مؤثرِ قابل‌خریدِ محصول از آن پایین‌تر آمد، یک‌بار پیامک ارسال و ردیف
 * غیرفعال می‌شود (تا کاربر در صورت تمایل دوباره اشتراک بگذارد).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('mobile', 15)->index();
            $table->string('name')->nullable();
            $table->decimal('target_price', 20, 2)->nullable();
            // فاز ۸۳ — کانال‌های اطلاع‌رسانی. این ستون‌ها باید «همان‌جا» ساخته
            // شوند نه فقط در مایگریشن add_channels: ترتیب نام فایل‌ها
            // (2026_10_25_add_channels قبل از 2026_10_29_create) روی دیتابیس
            // تازه باعث می‌شد گارد hasTable آن مایگریشن بی‌صدا skip شود و
            // جدول بدون این ستون‌ها متولد شود (create بعد از add اجرا می‌شود).
            $table->boolean('notify_sms')->default(true);
            $table->boolean('notify_profile')->default(false);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_alerts');
    }
};

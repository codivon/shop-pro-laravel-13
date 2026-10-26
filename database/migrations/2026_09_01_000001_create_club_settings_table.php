<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول تنظیمات پکیج باشگاه مشتریان (key-value).
 *
 * گارد hasTable (درس فاز ۷۹-ب): نصب‌های نیمه‌شکسته و اجرای دوبارهٔ
 * مایگریشن نباید با «table already exists» بشکنند.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('club_settings')) {
            return;
        }

        Schema::create('club_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_settings');
    }
};

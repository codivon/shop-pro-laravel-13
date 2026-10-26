<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قوانین کسب امتیاز — هر رویداد یک ردیف.
 * is_active = کلید فعال/غیرفعال همان قانون (الزام شخصی‌سازی از تنظیمات).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loyalty_rules')) {
            return;
        }

        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event', 32)->unique();  // purchase | register | review | birthday
            $table->string('title');                 // عنوان فارسی برای ادمین
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('points')->nullable();  // امتیاز ثابت (register/review/birthday)
            $table->unsignedInteger('amount_step')->nullable(); // هر X تومان خرید = ۱ امتیاز (purchase)
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rules');
    }
};

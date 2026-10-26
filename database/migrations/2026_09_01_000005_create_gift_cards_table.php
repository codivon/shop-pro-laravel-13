<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کارت‌های هدیه — صدور دسته‌ای ادمین، فعال‌سازی توسط مشتری.
 * فعال‌سازی = شارژ مستقیم کیف پول هسته با WalletHistory (audit کامل).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gift_cards')) {
            return;
        }

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();        // CC-XXXX-XXXX
            $table->string('batch_id', 40)->index();     // دستهٔ صدور
            $table->decimal('amount', 64, 0);            // تومان
            $table->string('status', 16)->default('active')->index(); // active|redeemed|disabled
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('note')->nullable();          // یادداشت ادمین
            $table->unsignedBigInteger('used_by')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};

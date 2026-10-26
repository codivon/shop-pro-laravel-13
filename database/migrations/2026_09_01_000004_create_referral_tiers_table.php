<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سطوح پاداش معرفی دوستان (برنزی/نقره‌ای/طلایی).
 * min_referrals = حداقل «معرفی موفق» (دعوت‌شدنی که خرید موفق داشته).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('referral_tiers')) {
            return;
        }

        Schema::create('referral_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('title');                     // برنزی | نقره‌ای | طلایی
            $table->unsignedInteger('min_referrals')->unique();
            $table->unsignedInteger('bonus_points');     // پاداش یک‌بارمصرف رسیدن به سطح
            $table->string('color', 16)->nullable();     // برچسب رنگی UI
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_tiers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * ربات‌گیر (Bot Trap)
 * هر ربات/اسکنری که به مسیرهای طعمه (مسیرهایی که فقط اسکنرها پیمایش می‌کنند)
 * ضربه بزند، IP آن در این جدول ثبت و از کل سایت مسدود می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_traps', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('user_agent', 500)->nullable();
            $table->string('trap_path', 191)->nullable();
            $table->unsignedBigInteger('hit_count')->default(1);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_traps');
    }
};

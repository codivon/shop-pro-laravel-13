<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * فاز ۴۴ — بازار قالب‌ها:
 *  ۱) ستون type روی package_purchases تا خریدهای قالب هم در همان جدول ردیابی شوند
 *  ۲) جدول installed_themes برای وضعیت نصب قالب‌ها در پوشه themes/
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            $table->string('type', 20)->default('package')->index()->after('id');
        });

        Schema::create('installed_themes', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();          // slug قالب در پنل مرکزی
            $table->string('name');                    // نام پوشه داخل themes/
            $table->string('version')->nullable();
            $table->string('purchase_type', 20)->default('normal'); // normal | exclusive
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('status', 20)->default('installed');     // installed | updating | failed
            $table->text('last_error')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_themes');

        Schema::table('package_purchases', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};

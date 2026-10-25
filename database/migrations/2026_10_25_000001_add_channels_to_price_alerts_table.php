<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۸۳ — کانال‌های اطلاع‌رسانی هشدار کاهش قیمت.
 *
 * مشتری در مودال هشدار انتخاب می‌کند که با افت قیمت «چطور» خبردار شود:
 *  - notify_sms     → پیامک موبایل (مسیر قبلی PriceAlertSms)
 *  - notify_profile → اعلان داخل پروفایل کاربری (NotificationManage + پیوت)
 *
 * مهمان (بدون user_id) فقط پیامک می‌گیرد؛ اعلان پروفایل مخصوص کاربر عضو است.
 * رکوردهای قبلی: notify_sms=true (رفتار قبلی)، notify_profile=false.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('price_alerts')) {
            return;
        }

        Schema::table('price_alerts', function (Blueprint $table) {
            if (! Schema::hasColumn('price_alerts', 'notify_sms')) {
                $table->boolean('notify_sms')->default(true)->after('target_price');
            }

            if (! Schema::hasColumn('price_alerts', 'notify_profile')) {
                $table->boolean('notify_profile')->default(false)->after('notify_sms');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('price_alerts')) {
            return;
        }

        Schema::table('price_alerts', function (Blueprint $table) {
            foreach (['notify_sms', 'notify_profile'] as $column) {
                if (Schema::hasColumn('price_alerts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

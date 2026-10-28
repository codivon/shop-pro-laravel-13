<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فاز ۱۱۸ — ثبت واقعی امتیاز فروشنده و رضایت کالا
 *
 * به نظرات (reviews) فروشنده و تنوع خریداری‌شده وصل می‌شود:
 *  • وقتی خریدارِ واقعی محصول نظر می‌دهد، از آخرین سفارشِ پرداخت‌شدهٔ او
 *    برای همین محصول، seller_id و price_id (تنوع) استخراج و روی نظر ثبت می‌شود.
 *  • با این اتصال، امتیاز (rating) و رأی توصیه (suggest) نظر به عملکرد (operation)
 *    و درصد رضایت (satisfaction) همان فروشنده در sellers_info تزریق می‌شود
 *    (ReviewObserver) — و در فرانت بَجِ «خریدار از فروشگاه …» نمایش داده می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('seller_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('price_id')->nullable()->after('seller_id');

            $table->foreign('seller_id')->references('id')->on('sellers')->nullOnDelete();
            $table->foreign('price_id')->references('id')->on('prices')->nullOnDelete();

            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropForeign(['price_id']);
            $table->dropIndex(['seller_id']);
            $table->dropColumn(['seller_id', 'price_id']);
        });
    }
};

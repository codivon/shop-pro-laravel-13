<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * فاز ۱۱۰ — جدول پیش‌نویسِ ثبت‌نام مرحله‌ای فروشندگان (پنل مدرن)
 *
 * جریان جدید: موبایل → کد ۶ رقمی → وضعیت (حقیقی/حقوقی) → اطلاعات تکمیلی
 * (کد ملی + احراز هویت اختیاری فینوتک) → اطلاعات کسب‌وکار → مدارک → پایان.
 *
 * هر مرحله بلافاصله در همین جدول ذخیره می‌شود تا با رفرش/قطع اینترنت،
 * کاربر دقیقاً در همان مرحله ادامه دهد (ستون step). کاربر نمی‌تواند با
 * تغییر آدرس به مرحله‌ای جلوتر برود (گیت سروری روی ترتیب step) ولی
 * بازگشت به مراحل قبل و ویرایش آزاد است.
 *
 * ستون token توکن تصادفی نگه‌داشته در کوکی است تا حتی با گم‌شدن سشن،
 * پیش‌نویس بازیابی شود.
 */
class CreateSellerRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 11)->unique();

            // مرحلهٔ فعلی: otp / type / identity / business / documents / checkout / complete
            $table->string('step', 20)->default('otp');

            // وضعیت فروشنده: real = حقیقی، legal = حقوقی
            $table->string('type', 10)->nullable();

            // اطلاعات تکمیلی (گام identity)
            $table->string('national_code', 10)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('birth_date', 20)->nullable(); // 1370/06/15

            // فیلدهای اضافهٔ شخص حقوقی
            $table->string('company_name', 191)->nullable();
            $table->string('company_type', 100)->nullable();
            $table->string('company_registration_number', 50)->nullable();
            $table->string('company_national_identity_number', 20)->nullable();
            $table->string('company_economic_number', 20)->nullable();

            // اطلاعات ورود (در گام کسب‌وکار جمع می‌شود — خام مثل new_sellers قدیمی، هش در لحظهٔ ساخت Seller)
            $table->string('email', 191)->nullable();
            $table->string('password', 191)->nullable();

            // نتیجهٔ احراز هویت فینوتک: none/skip/unavailable/verified/failed
            $table->string('kyc_status', 20)->default('none');
            $table->timestamp('kyc_checked_at')->nullable();

            // توکن بازیابی از کوکی (وقتی سشن گم شود)
            $table->string('token', 64)->nullable()->index();

            $table->timestamps();
        });

        // فاز ۱۱۰ — جریان جدید اول فقط موبایل جمع می‌کند؛ ایمیل/رمز در گام
        // کسب‌وکار می‌آیند → ستون‌های new_sellers باید nullable شوند
        Schema::table('new_sellers', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_registrations');
    }
}

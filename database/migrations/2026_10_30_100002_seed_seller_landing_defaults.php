<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/*
 * فاز ۱۱۰ — پیش‌فرض‌های محتوای لندینگ فروشندگان
 *
 * جداول sellers_heroes («چرا از ما بخرید؟»)، sellers_commissions
 * («مزایای فروشندگان») و sellers_questions («سوالات متداول») از فاز ۳
 * خالی بودند و بخش‌های مربوط در لندینگ /seller رندر نمی‌شدند. این
 * مایگریشن فقط وقتی جدول خالی است، محتوای پیش‌فرضِ هماهنگ با کسب‌وکار
 * (بازارگاه چندفروشندگی) درج می‌کند — ادمین بعداً از پنل (تنظیمات ←
 * فروشندگان) می‌تواند ویرایش/حذف کند.
 *
 * آیکون‌ها SVG درون‌خطی (Lucide) هستند تا در لندینگ مدرن بدون فونت‌آیکون
 * هم رندر شوند؛ فرمت آزاد است (نمایش با {!! !!}).
 */
class SeedSellerLandingDefaults extends Migration
{
    /** SVG درون‌خطی با استایل Lucide */
    private function icon(string $inner): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" '
            .'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            .$inner.'</svg>';
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // آیکون‌ها حالا SVG درون‌خطی هم می‌توانند باشند → varchar(255) جا نمی‌دهد
        foreach (['seller_heroes', 'seller_commissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'icon')) {
                Schema::table($table, function ($t) {
                    $t->text('icon')->nullable()->change();
                });
            }
        }

        if (Schema::hasTable('seller_heroes') && ! DB::table('seller_heroes')->exists()) {
            DB::table('seller_heroes')->insert([
                [
                    'title' => 'بازدید و مشتریان آماده',
                    'description' => 'مشتریان سراسر کشور هر روز در فروشگاه جست‌وجو می‌کنند؛ محصول شما جلوی چشم هزاران خریدار قرار می‌گیرد.',
                    'icon' => $this->icon('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'راه‌اندازی رایگان',
                    'description' => 'ثبت‌نام و فعال‌سازی فروشگاه بدون هیچ هزینه‌ای؛ تنها از فروش موفق کمیسیون کسر می‌شود.',
                    'icon' => $this->icon('<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'تسویهٔ شفاف و سریع',
                    'description' => 'سهم شما بلافاصله پس از هر فروش به کیف پول واریز و صورت‌حساب ردیف‌به‌ردیف ثبت می‌شود.',
                    'icon' => $this->icon('<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'پنل مدیریت حرفه‌ای',
                    'description' => 'محصولات، موجودی، سفارش‌ها و قیمت‌گذاری را از یک پنل ساده و سریع مدیریت کنید.',
                    'icon' => $this->icon('<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'اعتماد با احراز هویت',
                    'description' => 'خریداران با اطمینان از فروشندگان احراز‌هویت‌شده خرید می‌کنند؛ اعتماد بیشتر یعنی فروش بیشتر.',
                    'icon' => $this->icon('<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'پشتیبانی فروشندگان',
                    'description' => 'تیم پشتیبانی در تمام مراحل — از ثبت‌نام تا اولین فروش — کنار شماست.',
                    'icon' => $this->icon('<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (Schema::hasTable('seller_commissions') && ! DB::table('seller_commissions')->exists()) {
            DB::table('seller_commissions')->insert([
                [
                    'title' => 'شروع بدون هزینه',
                    'description' => 'ثبت‌نام و فعال‌سازی فروشگاه کاملاً رایگان است؛ تنها از فروش موفق کمیسیون کسر می‌شود.',
                    'icon' => $this->icon('<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'تسویهٔ شفاف و سریع',
                    'description' => 'سهم شما بلافاصله پس از هر فروش به کیف پول واریز و صورت‌حساب ردیف‌به‌ردیف ثبت می‌شود.',
                    'icon' => $this->icon('<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'پنل مدیریت حرفه‌ای',
                    'description' => 'مدیریت محصولات، موجودی، سفارش‌ها و قیمت‌گذاری از یک پنل ساده و سریع.',
                    'icon' => $this->icon('<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'آمار و گزارش فروش',
                    'description' => 'بازدید محصولات، گزارش درآمد و سیگنال‌های تقاضا برای چیدمان هوشمند موجودی.',
                    'icon' => $this->icon('<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'دسترسی به بازار بزرگ',
                    'description' => 'بازدید صفحات فروشگاه، گزارش درآمد و سیگنال‌های تقاضا برای چیدمان هوشمند موجودی.',
                    'icon' => $this->icon('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'پشتیبانی اختصاصی فروشندگان',
                    'description' => 'پاسخ‌گویی سریع به سوالات فروشندگی از طریق تیکت و پیام‌رسان پنل.',
                    'icon' => $this->icon('<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (Schema::hasTable('seller_questions') && ! DB::table('seller_questions')->exists()) {
            DB::table('seller_questions')->insert([
                [
                    'question' => 'ثبت‌نام فروشنده چقدر طول می‌کشد؟',
                    'answer' => 'کل فرآیند آنلاین کمتر از ۱۰ دقیقه است: شمارهٔ موبایل، کد تأیید ۶ رقمی، انتخاب وضعیت (حقیقی/حقوقی)، اطلاعات تکمیلی و بارگذاری مدارک.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'آیا ثبت‌نام هزینه دارد؟',
                    'answer' => 'خیر؛ ثبت‌نام و فعال‌سازی فروشگاه کاملاً رایگان است. تنها در ازای هر فروش موفق، درصدی کمیسیون کسر می‌شود.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'چه مدارکی برای فروشندگی لازم است؟',
                    'answer' => 'تصویر کارت ملی (رو و پشت) الزامی است؛ اگر گواهی ارزش افزوده دارید تصویر آن را هم بارگذاری کنید. اشخاص حقوقی به مدارک شرکت نیز نیاز دارند.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'تسویهٔ فروش چطور انجام می‌شود؟',
                    'answer' => 'بلافاصله پس از پرداخت موفق هر سفارش، سهم شما (پس از کسر کمیسیون) به کیف پول فروشندگی واریز و در صورت‌حساب تسویه ثبت می‌شود.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'فروشندهٔ حقیقی و حقوقی چه فرقی دارند؟',
                    'answer' => 'فروشندهٔ حقیقی شخص مستقل است و با اطلاعات هویتی خود ثبت‌نام می‌کند؛ فروشندهٔ حقوقی شرکت است و اطلاعات شرکت (نام، شمارهٔ ثبت، شناسهٔ ملی) به‌علاوهٔ اطلاعات نماینده ثبت می‌شود.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'چرا شمارهٔ موبایل و کد ملی باید متعلق به یک نفر باشد؟',
                    'answer' => 'برای امنیت معاملات، مالکیت شمارهٔ موبایل با کد ملی بررسی می‌شود؛ فروش با اطلاعات و حساب دیگران مجاز نیست.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // پیش‌فرض‌ها برگشت‌پذیر نیستند (شاید ادمین روی همان‌ها محتوای خودش را ساخته باشد)
    }
}

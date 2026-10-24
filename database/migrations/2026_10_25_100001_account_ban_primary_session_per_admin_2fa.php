<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * فاز ۷۲ — بن اکانت + نشست اصلی + 2FA هر مدیر + شماره موبایل جدا از نام کاربری
     *
     *  1) admins.mobile — شماره همراه مستقل از username (در فرم مدیران/پروفایل فیلد جدا)
     *  2) admins.two_factor_disabled — خروج دومرحله‌ای مدیر برای هر مدیر (صفحه پروفایل)
     *  3) admin_sessions.is_primary / primary_until — «سیستم اصلی» اولین ورود هر مدیر؛
     *     تا ۴۸ ساعت از دستگاه دیگر (آیپی/مرورگر/سیستم متفاوت) قابل بن/خروج نیست
     *  4) blocked_devices.block_type — افزودن مقدار «account» (بن اکانت، فقط سازنده)
     */
    public function up(): void
    {
        // ─── ۱ و ۲) ستون‌های جدید جدول admins ───
        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'mobile')) {
                $table->string('mobile')->nullable()->after('email');

                $table->index('mobile');
            }

            if (! Schema::hasColumn('admins', 'two_factor_disabled')) {
                $table->boolean('two_factor_disabled')->default(false)->after('mobile');
            }
        });

        // ─── ۳) ستون‌های نشست اصلی در admin_sessions ───
        Schema::table('admin_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_sessions', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('admin_sessions', 'primary_until')) {
                $table->timestamp('primary_until')->nullable()->after('is_primary');
            }
        });

        // ─── ۴) نوع بلاک «account» (بن اکانت) ───
        $types = DB::selectOne("SHOW COLUMNS FROM blocked_devices WHERE Field = 'block_type'");

        if ($types && is_string($types->Type) && str_contains($types->Type, 'enum') && ! str_contains($types->Type, "'account'")) {
            DB::statement("ALTER TABLE blocked_devices MODIFY block_type ENUM('session','ip','device','browser','all','account') NOT NULL DEFAULT 'device'");
        }
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'mobile')) {
                $table->dropIndex(['mobile']);
                $table->dropColumn('mobile');
            }

            if (Schema::hasColumn('admins', 'two_factor_disabled')) {
                $table->dropColumn('two_factor_disabled');
            }
        });

        Schema::table('admin_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('admin_sessions', 'is_primary')) {
                $table->dropColumn('is_primary');
            }

            if (Schema::hasColumn('admin_sessions', 'primary_until')) {
                $table->dropColumn('primary_until');
            }
        });

        // بازگشت enum به مقادیر قبلی (فقط اگر رکورد account وجود نداشته باشد)
        $hasAccount = DB::table('blocked_devices')->where('block_type', 'account')->exists();

        if (! $hasAccount) {
            DB::statement("ALTER TABLE blocked_devices MODIFY block_type ENUM('session','ip','device','browser','all') NOT NULL DEFAULT 'device'");
        }
    }
};

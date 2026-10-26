<?php

namespace Modules\CustomerClub\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Models\LoyaltyRule;
use Modules\CustomerClub\Models\ReferralTier;
use Modules\CustomerClub\Permissions\ModulesPermissions;

/**
 * نصب/حذف پرمیژن‌های پکیج باشگاه مشتریان.
 *
 * ⚠️ قرارداد اجباری نصب‌کنندهٔ هاست (PackageInstallerService):
 * مسیر کلاس باید دقیقاً Modules\{Name}\Console\Commands\InstallPermissionsCommand
 * و امضای آن {alias}:install-permissions {--remove} باشد — تغییر نام ممنوع.
 *
 * فاز ۹۰ — پس از نصب پرمیژن‌ها، مقادیر پیش‌فرض پکیج هم seed می‌شود
 * (تنظیمات + ۴ قانون امتیاز + ۳ سطح معرفی) تا پکیج از همان لحظهٔ نصب
 * آمادهٔ کار باشد. اجرای مجدد مقادیر تغییرکردهٔ ادمین را بازنویسی نمی‌کند.
 */
class InstallPermissionsCommand extends Command
{
    protected $signature = 'customerclub:install-permissions {--remove}';
    protected $description = 'نصب یا حذف پرمیژن‌های پکیج باشگاه مشتریان (CustomerClub)';

    public $ordering;
    public $ids = [];

    public function handle(): int
    {
        if ($this->option('remove')) {
            return $this->removePermissions();
        }

        $result = $this->installPermissions();

        $this->seedDefaults();

        return $result;
    }

    protected function installPermissions(): int
    {
        $this->info('در حال نصب پرمیژن‌های پکیج باشگاه مشتریان (CustomerClub)...');
        $lastPermission = Permission::orderby('ordering', 'desc')->first();
        $this->ordering = $lastPermission ? $lastPermission->ordering : 0;

        foreach (ModulesPermissions::all() as $name => $value) {
            $this->create($name, $value);
        }

        $this->info('✅ ' . count($this->ids) . ' پرمیژن نصب شد.');

        return self::SUCCESS;
    }

    private function create($name, $value, $permission_id = null)
    {
        if (is_array($value) && isset($value['values'])) {
            $permission = Permission::updateOrCreate(
                ['name' => $name],
                ['title' => $value['title'], 'ordering' => $this->ordering++, 'permission_id' => $permission_id]
            );
            $this->ids[] = $permission->id;
            $this->line("  ✓ {$name} - {$value['title']}");
            foreach ($value['values'] as $n => $val) {
                $this->create($name . '.' . $n, $val, $permission->id);
            }
        } else {
            $permission = Permission::updateOrCreate(
                ['name' => $name],
                ['title' => $value, 'ordering' => $this->ordering++, 'permission_id' => $permission_id]
            );
            $this->ids[] = $permission->id;
            $this->line("  ✓ {$name} - {$value}");
        }
    }

    protected function removePermissions(): int
    {
        $this->warn('در حال حذف پرمیژن‌های پکیج باشگاه مشتریان (CustomerClub)...');
        $deletedCount = 0;
        foreach (ModulesPermissions::all() as $name => $value) {
            $perms = Permission::where('name', $name)->orWhere('name', 'like', $name . '.%')->get();
            foreach ($perms as $permission) {
                $permission->delete();
                $deletedCount++;
                $this->line("  ✗ {$permission->name} حذف شد");
            }
        }
        $this->info('✅ ' . $deletedCount . ' پرمیژن حذف شد.');

        return self::SUCCESS;
    }

    /* ================= فاز ۹۰ — مقادیر پیش‌فرض پکیج ================= */

    /**
     * پیش‌فرض‌های پکیج: تنظیمات + قوانین امتیاز + سطوح معرفی.
     * idempotent — مقدار موجود (ادمین) بازنویسی نمی‌شود.
     */
    protected function seedDefaults(): void
    {
        $this->line('در حال ایجاد مقادیر پیش‌فرض پکیج باشگاه مشتریان...');

        $this->seedSettings();
        $this->seedRules();
        $this->seedTiers();

        $this->info('✅ مقادیر پیش‌فرض پکیج باشگاه مشتریان آماده است.');
    }

    /** تنظیمات پیش‌فرض (فقط اگر کلید موجود نباشد) */
    protected function seedSettings(): void
    {
        $defaults = config('customer-club.defaults', []);

        foreach ($defaults as $key => $value) {
            if (!ClubSetting::query()->where('key', $key)->exists()) {
                ClubSetting::set($key, (string) $value);
                $this->line("  ✓ تنظیمات پیش‌فرض: {$key} = {$value}");
            }
        }
    }

    /** ۴ قانون امتیاز (firstOrCreate — self-heal مثل LoyaltyRule::for) */
    protected function seedRules(): void
    {
        foreach (LoyaltyRule::EVENTS as $event) {
            $rule = LoyaltyRule::for($event);
            $this->line("  ✓ قانون امتیاز: {$rule->title}");
        }
    }

    /** ۳ سطح معرفی (اگر جدول خالی باشد) */
    protected function seedTiers(): void
    {
        ReferralTier::ensureSeeded();

        foreach (ReferralTier::query()->orderBy('min_referrals')->get() as $tier) {
            $this->line("  ✓ سطح معرفی: {$tier->title} (از {$tier->min_referrals} معرفی — {$tier->bonus_points} امتیاز)");
        }
    }
}

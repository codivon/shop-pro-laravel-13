<?php

namespace App\Console\Commands;

use App\Services\ModuleRecoveryService;
use Illuminate\Console\Command;

/**
 * بازیابی ماژول‌های نصب‌شده از پوشهٔ Modules/ — فاز ۱۲۲ (گزارش کاربر)
 *
 * سناریو: بعد از انقضای اشتراک و تمدید، جدول installed_modules خالی بود
 * (ریست/بازیابی دیتابیس هاست) ولی پوشه‌های Modules/ سر جایشان بودند —
 * نتیجه: بدون منوی پکیج‌ها، بدون لیست نصب‌شده‌ها و با بازارِ همه‌«نصب‌نشده».
 *
 * این کامند رکوردهای گمشده را از دیسک بازسازی می‌کند (نسخه از module.json،
 * لایسنس از آخرین خرید پرداخت‌شده با راستی‌آزمایی آنلاین)، فایل
 * modules_statuses.json را بازسازی و کش‌ها را پاک می‌کند.
 *
 * استفاده:
 *   php artisan packages:recover-modules --dry-run   فقط پیش‌نمایش
 *   php artisan packages:recover-modules             اعمال
 */
class RecoverModulesCommand extends Command
{
    protected $signature = 'packages:recover-modules
                            {--dry-run : فقط پیش‌نمایش — هیچ تغییری اعمال نمی‌شود}';

    protected $description = 'بازسازی رکوردهای installed_modules از پوشهٔ Modules/ (بازیابی بعد از ریست دیتابیس)';

    public function handle(ModuleRecoveryService $recovery): int
    {
        $this->info('اسکن پوشهٔ Modules/...');

        $discovered = $recovery->discover();

        if ($discovered === []) {
            $this->warn('هیچ ماژول معتبری (دارای module.json) در پوشهٔ Modules/ یافت نشد.');

            return self::SUCCESS;
        }

        $this->table(
            ['ماژول', 'عنوان', 'نسخه', 'slug حدس‌زده', 'رکورد DB', 'وضعیت لایسنس'],
            array_map(fn (array $m) => [
                $m['name'],
                $m['title'] ?? '—',
                $m['version'] ?? '—',
                $m['slug_hint'] ?? '—',
                $m['has_row'] ? 'دارد' : 'گم‌شده',
                $m['license_state'],
            ], $discovered)
        );

        $missing = count(array_filter($discovered, fn (array $m) => ! $m['has_row']));

        if ($missing === 0) {
            $this->info('همهٔ ماژول‌های روی دیسک رکورد دیتابیس دارند — کاری لازم نیست.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->confirm("بازیابی {$missing} رکورد گم‌شده از دیسک انجام شود؟", true)) {
            $this->line('لغو شد.');

            return self::SUCCESS;
        }

        $report = $recovery->recover(null, $dryRun);

        if ($dryRun) {
            $this->info('— پیش‌نمایش (dry-run) — چیزی اعمال نشد —');
        }

        foreach ($report['restored'] as $row) {
            $this->info("  ✓ {$row['name']} ({$row['slug']}) نسخه {$row['version']} — لایسنس: {$row['license_state']}");
        }

        foreach ($report['skipped'] as $name) {
            $this->line("  → {$name} رد شد (رکورد موجود)");
        }

        if ($report['statuses_rebuilt']) {
            $this->info('✓ modules_statuses.json بازسازی شد (همهٔ ماژول‌ها فعال).');
        }

        if ($report['caches_cleared']) {
            $this->info('✓ کش‌ها پاک شد — منوی پکیج‌ها بعد از رفرش پنل برمی‌گردد.');
        }

        $this->newLine();
        $this->info('تمام. '.count($report['restored']).' ماژول بازیابی شد، '.count($report['skipped']).' مورد رد شد.');

        return self::SUCCESS;
    }
}

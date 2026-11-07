<?php

namespace App\Console\Commands;

use App\Services\Backup\AutoBackupRunner;
use Illuminate\Console\Command;

/**
 * پشتیبان‌گیری خودکار دیتابیس — فاز ۱۴۵
 *
 * فقط جداول و دیتای جداول را بکاپ می‌گیرد (بدون فایل‌ها) — طبق تنظیمات
 * صفحه‌ی پشتیبان‌گیری پنل ادمین (دوره/ساعت/حذف خودکار/ایمیل).
 * خودِ زمان‌بندی: در Kernel (هر دقیقه صدا زده می‌شود و خودش تصمیم می‌گیرد موعد رسیده یا نه).
 *
 * اجرای دستی (تست/اجرا اجباری): php artisan backup:auto --force
 */
class AutoBackup extends Command
{
    protected $signature = 'backup:auto
                            {--force : اجرای اجباری بدون توجه به موعد (برای تست)}';

    protected $description = 'پشتیبان‌گیری خودکار فقط-دیتابیس (جداول + دیتا) طبق تنظیمات پنل';

    public function handle(): int
    {
        $runner = app(AutoBackupRunner::class);

        $outcome = $this->option('force')
            ? $runner->run(force: true)
            : $runner->runIfDue();

        if (! $outcome['ran']) {
            // not_due / disabled / already_running — طبیعی و بی‌خطا
            $this->info('بکاپ خودکار اجرا نشد: '.$outcome['reason']);

            return self::SUCCESS;
        }

        $result = $outcome['result'];

        $this->info('بکاپ خودکار «'.$result['filename'].'» ساخته شد — '
            .number_format($result['size'] / 1048576, 2).' مگابایت، '
            .$result['tables'].' جدول، '.number_format($result['rows']).' رکورد.');

        if (($result['deleted_old'] ?? 0) > 0) {
            $this->info($result['deleted_old'].' بکاپ خودکارِ قدیمی حذف شد.');
        }

        if ($result['emailed'] ?? false) {
            $this->info('ایمیل پشتیبان ارسال شد.');
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\BotTrap;
use Illuminate\Console\Command;

/*
 * فاز ۷۰ — خلاصی اضطراری از مسدودسازی ربات‌گیر (Forbidden سراسری)
 *
 * سناریو: IP مدیر (به‌دلیل CGNAT isp یا پرشدن تلهٔ هانی‌پات) در bot_traps ثبت می‌شود
 * و BlockBots همهٔ درخواست‌هایش را با 403 جواب می‌دهد — حتی ورود به پنل.
 * این فرمان راه فرار SSH است: حذف سریع تله‌ها + پاک‌کردن کشِ فوری.
 *
 * استفاده:
 *   php artisan security:unblock --list        نمایش IPهای مسدودشده
 *   php artisan security:unblock 1.2.3.4       رفع مسدودی یک IP مشخص
 *   php artisan security:unblock --all         رفع مسدودی همهٔ IPها
 */
class SecurityUnblock extends Command
{
    protected $signature = 'security:unblock
                            {ip? : آی‌پی که باید از تلهٔ ربات آزاد شود}
                            {--all : آزادسازی همهٔ IPهای مسدودشده}
                            {--list : فقط نمایش لیست IPهای مسدودشده}';

    protected $description = 'رفع مسدودسازی ۴۰۳ سراسری (تلهٔ ربات) برای یک IP یا همه — امن‌کنندهٔ قفل‌شدن مدیر';

    public function handle(): int
    {
        if ($this->option('list') || ($this->option('all') === false && $this->argument('ip') === null)) {
            return $this->listBlocked();
        }

        if ($this->option('all')) {
            $count = BotTrap::query()->count();
            BotTrap::query()->delete();
            BotTrap::flushCache();

            $this->info("✅ {$count} تلهٔ ربات پاک شد — همهٔ IPها آزاد شدند (کش هم پاک شد).");

            return self::SUCCESS;
        }

        $ip = trim((string) $this->argument('ip'));

        if ($ip === '') {
            $this->error('آی‌پی نامعتبر است.');

            return self::FAILURE;
        }

        $deleted = BotTrap::query()->where('ip_address', $ip)->delete();

        if ($deleted === 0) {
            $this->warn("⚠️  رکوردی برای {$ip} در bot_traps نبود (شاید از قبل آزاد بوده). کش هم پاک شد.");

            BotTrap::flushCache();

            return self::SUCCESS;
        }

        BotTrap::flushCache();

        $this->info("✅ مسدودی {$ip} برداشته شد — بلافاصله اثر می‌کند (کش پاک شد).");

        return self::SUCCESS;
    }

    private function listBlocked(): int
    {
        $rows = BotTrap::query()
            ->orderByDesc('last_hit_at')
            ->get(['ip_address', 'trap_path', 'hit_count', 'last_hit_at']);

        if ($rows->isEmpty()) {
            $this->info('هیچ IP مسدودشده‌ای وجود ندارد.');

            return self::SUCCESS;
        }

        $this->table(
            ['IP', 'تله', 'ضربه‌ها', 'آخرین ضربه'],
            $rows->map(fn ($row) => [
                $row->ip_address,
                $row->trap_path,
                $row->hit_count,
                optional($row->last_hit_at)->format('Y-m-d H:i'),
            ])->all()
        );

        return self::SUCCESS;
    }
}

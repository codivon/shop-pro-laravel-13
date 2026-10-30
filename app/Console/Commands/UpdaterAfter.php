<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdaterAfter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updater:after';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run this command after update';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate');
        $this->call('db:seed', ['class' => 'PermissionSeeder']);

        // فاز ۱۳۲ — بیمهٔ پرمیژن‌های پکیج‌ها: بعد از اجرای PermissionSeeder،
        // پرمیژن‌های همهٔ ماژول‌های نصب‌شده بازسازی می‌شوند (idempotent).
        // قبلاً اگر seeder نسخهٔ قدیمی بود یا db:seed دستی اجرا می‌شد،
        // پرمیژن‌های پکیج‌ها پاک می‌ماندند و منو/گیت‌های پکیج ناپدید می‌شدند.
        try {
            $restored = app(\App\Services\PackageInstallerService::class)
                ->reinstallPermissionsForInstalledModules();

            if ($restored > 0) {
                $this->info("✅ Package permissions ensured for {$restored} module(s).");
            }
        } catch (\Throwable $e) {
            $this->warn('⚠️ Package permission reinstall failed: '.$e->getMessage());
        }

        $this->call('app:fix');
        $this->call('shop:link');
        $this->call('queue:restart');
        $this->call('optimize');
        $this->call('theme:config');
        $this->line("updater after done.");
        return 0;
    }
}

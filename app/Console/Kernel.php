<?php

namespace App\Console;

use App\Console\Commands\GenerateSitemap;
use App\Jobs\CalculateViewers;
use App\Jobs\HappyBirthday;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\File;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // InstallCommand::class
    ];

    // define your queues here in order of priority
    protected $queues = [
        'default',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $isSharedHosting = option('shared_hosting_cron_enabled', 'off') === 'on';

        /*
         * On non-shared hosting, the queue worker is started
         * from inside Laravel scheduler.
         */
        if (!$isSharedHosting) {
            $schedule->command($this->getQueueCommand())
                ->everyMinute()
                ->withoutOverlapping();

            // Restart the queue worker periodically to prevent memory issues.
            $schedule->command('queue:restart')
                ->hourly();
        }

        /*
         * Clear extra files in tmp folder.
         *
         * On shared hosting, run directly in current PHP process
         * to avoid Symfony Process / proc_open.
         */
        if ($isSharedHosting) {
            $schedule->call(function () {
                $folderPath = public_path('uploads/tmp');

                // Ensure directory exists before reading files.
                if (!File::exists($folderPath)) {
                    return;
                }

                $oneHourAgo = now()->subHour()->timestamp;

                foreach (File::files($folderPath) as $file) {
                    // Delete files older than one hour.
                    if (File::lastModified($file) < $oneHourAgo) {
                        File::delete($file);
                    }
                }
            })->dailyAt('22:00');
        } else {
            $schedule->command('clear:tmp')
                ->dailyAt('22:00');
        }

        /*
         * Update schedule heartbeat.
         */
        $schedule->call(function () {
            option_update('schedule_run', now());
        })->everyMinute();

        /*
         * فاز ۱۴۵ — پشتیبان‌گیری خودکار فقط-دیتابیس (جداول + دیتا).
         *
         * هر دقیقه صدا زده می‌شود اما خودِ AutoBackupRunner با «موعد» (دوره + ساعت)
         * تصمیم می‌گیرد بکاپ بگیرد یا نه؛ پس سربار تیک‌های بی‌موعد فقط دو option() خواندن است.
         * موتور دامپ PHP-only است، پس در هاست اشتراکی هم بدون Symfony Process
         * مستقیماً در همین پروسه اجرا می‌شود (مثل GenerateSitemap).
         */
        if ($isSharedHosting) {
            $schedule->call(function () {
                app(\App\Services\Backup\AutoBackupRunner::class)->runIfDue();
            })->everyMinute();
        } else {
            $schedule->command('backup:auto')
                ->everyMinute()
                ->withoutOverlapping();
        }

        /*
         * Generate sitemap files.
         *
         * On shared hosting, run directly in current PHP process
         * to avoid Symfony Process / proc_open.
         */
        if ($isSharedHosting) {
            $schedule->call(function () {
                app(GenerateSitemap::class)->handle();
            })->dailyAt('00:00');
        } else {
            $schedule->command('sitemap:generate')
                ->dailyAt('00:00');
        }

        /*
         * Dispatch CalculateViewers job.
         *
         * On shared hosting, run directly in current PHP process
         * to avoid Symfony Process / proc_open.
         */
        if ($isSharedHosting) {
            $schedule->call(function () {
                (new CalculateViewers)->handle();
            })->dailyAt('00:00');
        } else {
            $schedule->job(new CalculateViewers)
                ->dailyAt('00:00');
        }

        /*
         * Dispatch HappyBirthday job.
         *
         * On shared hosting, run directly in current PHP process
         * to avoid Symfony Process / proc_open.
         */
        if ($isSharedHosting) {
            $schedule->call(function () {
                (new HappyBirthday)->handle();
            })->dailyAt('01:00');
        } else {
            $schedule->job(new HappyBirthday)
                ->dailyAt('01:00');
        }
    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected function getQueueCommand()
    {
        // build the queue command
        $params = implode(' ', [
            '--daemon',
            '--tries=3',
            '--sleep=3',
            '--queue=' . implode(',', $this->queues),
        ]);

        return sprintf('queue:work %s', $params);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;

class ShopLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'link theme assets and storage';

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
        $links  = config('filesystems.links');
        $theme_links = config('front.links');

        if (is_array($theme_links)) {
            $links = array_merge($links, $theme_links);
        }

        foreach ($links as $link => $target) {
            $this->linkOrCopy((string) $link, (string) $target);
        }

        /*
         * فاز ۲۶ — فال‌بک کپی برای لینک‌های تم:
         * اگر symlink به هر دلیلی ساخته نشد (تابع غیرفعال روی هاست اشتراکی، proc_open بسته،
         * نبود پوشه‌ی والد و...)، محتوای واقعی کپی می‌شود تا «شورت‌کات استایل فرانت در public»
         * همیشه برقرار باشد. قبلاً اگر پوشه‌ی والد وجود نداشت، makeDirectory استثنا می‌داد و
         * کل حلقه بی‌صدا می‌مرد — حالا والدِ هر مسیر تضمین‌شده ساخته می‌شود.
         */
        if (is_array($theme_links)) {
            foreach ($theme_links as $link => $target) {
                try {
                    $link = (string) $link;

                    if ($this->linkIsValid($link)) {
                        continue; // symlink سالم از قبل برقرار است
                    }

                    // اگر لینک شکسته است، اول پاک شود تا کپی روی آن ممکن باشد
                    $this->removeBrokenLink($link);

                    if (!File::exists($link)) {
                        File::ensureDirectoryExists(dirname($link));
                        File::makeDirectory($link, 0755, true, true);
                    }

                    File::copyDirectory($target, $link);
                    $this->line("copied theme assets to: {$link}\n");
                } catch (\Throwable $e) {
                    report($e);
                    $this->error("failed to publish theme assets to {$link}: " . $e->getMessage());
                }
            }
        }


        $this->line("linked successfully.\n");

        return 0;
    }

    /**
     * فاز ۲۶ — ساخت symlink با زنجیره‌ی فال‌بک کامل برای «هر» لینک به‌صورت مستقل:
     *  ۱) اطمینان از وجود پوشه‌ی والد (علت اصلی شکست بی‌صدای قبلی)
     *  ۲) تابع symlink (اگر غیرفعال باشد false/null برمی‌گرداند نه استثنا!)
     *  ۳) دستور ln -s / mklink از طریق Schedule
     *  ۴) اگر هنوز برقرار نشده بود → کپی واقعی محتوا (کاملاً بی‌خطر)
     */
    protected function linkOrCopy(string $link, string $target): void
    {
        try {
            if ($this->linkIsValid($link)) {
                return; // قبلاً درست ساخته شده
            }

            $this->removeBrokenLink($link);
            File::ensureDirectoryExists(dirname($link));

            // ۱) تابع symlink — روی هاست‌های اشتراکی ممکن است disable باشد؛
            //    در آن حالت PHP فقط warning می‌دهد و false برمی‌گرداند
            $linked = false;
            if (function_exists('symlink')) {
                $linked = @symlink($target, $link);
            }

            // ۲) فال‌بک دستور سیستم‌عامل
            if (! $linked && ! $this->linkIsValid($link)) {
                $schedule = new Schedule();

                if (! $this->isWindows()) {
                    $event = $schedule->exec('ln -s ' . escapeshellarg($target) . ' ' . escapeshellarg($link));
                    $event->run($this->laravel);
                } else {
                    $mode = is_dir($target) ? 'J' : 'H';
                    $event = $schedule->exec("mklink /{$mode} " . escapeshellarg($link) . ' ' . escapeshellarg($target));
                    $event->run($this->laravel);
                }
            }

            // ۳) فال‌بک نهایی و تضمینی: کپی محتوا
            if (! $this->linkIsValid($link)) {
                $this->removeBrokenLink($link);

                if (!File::exists($link)) {
                    File::makeDirectory($link, 0755, true, true);
                }

                File::copyDirectory($target, $link);
                $this->line("symlink unavailable — copied instead: {$link}\n");
            }
        } catch (\Throwable $e) {
            // هرگز اجازه نده یک لینکِ ناموفق، بقیه‌ی لینک‌ها را هم قربانی کند
            report($e);
            $this->error("link failed for {$link}: " . $e->getMessage());
        }
    }

    /** آیا مسیر به‌عنوان symlink سالم وجود دارد؟ */
    protected function linkIsValid(string $link): bool
    {
        if (!is_link($link)) {
            return false;
        }

        // symlink شکسته (هدف موجود نیست) = نامعتبر
        $target = @readlink($link);

        return $target !== false && file_exists($link);
    }

    /** حذف symlink شکسته (file_exists روی لینک شکسته false است و File::delete هم) */
    protected function removeBrokenLink(string $link): void
    {
        if (is_link($link)) {
            @unlink($link);
        }
    }

    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}

<?php

namespace Modules\CustomerClub\Console\Commands;

use Illuminate\Console\Command;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Services\LoyaltyService;

/**
 * انقضای لات‌های امتیاز سررسیده — زمان‌بندی روزانه ۰۳:۱۵.
 * قابل اجرای دستی: php artisan customerclub:expire-points
 */
class ExpirePointsCommand extends Command
{
    protected $signature   = 'customerclub:expire-points';
    protected $description = 'انقضای لات‌های امتیاز باشگاه مشتریان که سررسید انقضایشان گذشته است';

    public function handle(): int
    {
        if (!ClubSetting::enabled('club_enabled')) {
            $this->info('باشگاه مشتریان غیرفعال است — انقضا اجرا نشد.');

            return self::SUCCESS;
        }

        $expired = LoyaltyService::expireDue();

        if ($expired > 0) {
            $this->info("✅ ".number_format($expired)." امتیاز منقضی‌شده در دفتر ثبت شد.");
        } else {
            $this->info('لات سررسیده‌ای برای انقضا نبود.');
        }

        return self::SUCCESS;
    }
}

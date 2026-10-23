<?php

namespace App\Jobs;

use App\Models\InstalledTheme;
use App\Models\PackagePurchase;
use App\Services\PackageInstallerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * نصب غیرهمزمان قالب خریداری‌شده — فاز ۴۴ (الگوی InstallPackageJob)
 */
class InstallThemeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;       // در صورت fail، retry دستی
    public int $timeout = 600;   // 10 دقیقه

    public function __construct(
        public string $slug,
        public ?string $downloadToken = null,
        public ?string $expectedHash = null,
        public string $purchaseType = 'normal',
        public ?int $adminId = null,
        public ?int $purchaseId = null,
    ) {
        $this->onQueue(config('packages.queue.queue', 'default'));
        $this->onConnection(config('packages.queue.connection', 'database'));
    }

    public function handle(PackageInstallerService $installer): void
    {
        Log::info('InstallThemeJob started', ['slug' => $this->slug, 'purchase_type' => $this->purchaseType]);

        $installer->installTemplate(
            $this->slug,
            $this->downloadToken,
            $this->expectedHash,
            $this->purchaseType,
            $this->adminId
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('InstallThemeJob FAILED', [
            'slug'  => $this->slug,
            'error' => $e->getMessage(),
        ]);

        if ($this->purchaseId) {
            PackagePurchase::where('id', $this->purchaseId)
                ->where('status', PackagePurchase::STATUS_PENDING)
                ->update(['status' => PackagePurchase::STATUS_FAILED]);
        }

        InstalledTheme::where('slug', $this->slug)
            ->where('status', InstalledTheme::STATUS_UPDATING)
            ->update(['status' => InstalledTheme::STATUS_FAILED, 'last_error' => $e->getMessage()]);
    }
}

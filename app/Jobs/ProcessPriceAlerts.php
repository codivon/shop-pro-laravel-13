<?php

namespace App\Jobs;

use App\Models\Admin;
use App\Models\Price;
use App\Models\PriceAlert;
use App\Models\Product;
use App\Notifications\Product\PriceAlertsSent;
use App\Notifications\Sms\PriceAlertSms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * فاز ۷۸ — پردازش هشدارهای کاهش قیمت یک محصول.
 *
 * با هر ذخیرهٔ قیمتی که «افت قیمت مؤثر» را نشان دهد، از PriceObserver
 * رانده می‌شود. این Job کمترین قیمت مؤثرِ قابل‌خرید (موجود + منتشرشده)
 * محصول را حساب می‌کند و هر هشدارِ فعالی که هدفش بالاتر از این قیمت است
 * را یک‌بار پیامک می‌دهد و غیرفعال می‌کند.
 *
 * اگر الگوی پیامک (price_alert_pattern_code) تنظیم نشده باشد، هشدار بدون
 * ارسال پیامک علامت خورده و در لاگ هشدار ثبت می‌شود (تا حلقهٔ تکرار بیفتد).
 */
class ProcessPriceAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(public int $productId)
    {
    }

    public function handle(): void
    {
        // فاز ۸۱ — قابلیت از تنظیمات تم غیرفعال شده؛ هیچ هشداری پردازش نمی‌شود
        if (option('dt_price_alert_status', '1') != '1') {
            return;
        }

        $product = Product::find($this->productId);

        if (! $product) {
            return;
        }

        $minPrice = $product->prices()
            ->where('stock', '>', 0)
            ->where('published', 1)
            ->get()
            ->map(fn (Price $price) => $price->salePrice())
            ->filter(fn ($price) => $price > 0)
            ->min();

        if (! $minPrice) {
            // هیچ قیمت قابل‌خریدی نیست؛ هشدارها فعال می‌مانند تا با برگشت موجودی
            // (و هر تغییر قیمت بعدی) دوباره پردازش شوند.
            return;
        }

        $alerts = PriceAlert::query()
            ->where('product_id', $product->id)
            ->where('active', true)
            ->whereNotNull('target_price')
            ->where('target_price', '>', $minPrice)
            ->get();

        if ($alerts->isEmpty()) {
            return;
        }

        $patternConfigured = filled(option('price_alert_pattern_code'));

        if (! $patternConfigured) {
            Log::warning('الگوی پیامک «هشدار کاهش قیمت» تنظیم نشده است؛ هشدار علامت خورد ولی پیامکی ارسال نشد.', [
                'option'     => 'price_alert_pattern_code',
                'product_id' => $product->id,
                'alerts'     => $alerts->count(),
            ]);
        }

        foreach ($alerts as $alert) {
            if ($patternConfigured) {
                try {
                    $alert->notify(new PriceAlertSms($product, $minPrice));
                } catch (\Throwable $e) {
                    Log::error('خطای ارسال پیامک هشدار کاهش قیمت', [
                        'alert_id' => $alert->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

            $alert->update([
                'active'      => false,
                'notified_at' => now(),
            ]);
        }

        // فاز ۸۱ — اطلاع‌رسانی به خود فروشگاه (مدیران) از طریق کانال database پنل
        try {
            $admins = Admin::whereIn('level', ['admin', 'creator'])->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new PriceAlertsSent($product, $alerts->count()));
            }
        } catch (\Throwable $e) {
            Log::error('خطای ارسال نوتیفیکیشن فروشگاه برای هشدار کاهش قیمت', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}

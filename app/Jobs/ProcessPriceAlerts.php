<?php

namespace App\Jobs;

use App\Models\Admin;
use App\Models\NotificationManage;
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
            // فاز ۸۳ — کانال پیامک (فقط اگر مشتری پیامک را انتخاب کرده باشد)
            // فاز ۸۷ — گیت رویداد پیامکی: sms_on_price_alert (پیش‌فرض فعال؛ از تنظیمات پیامک مدیریت می‌شود)
            if ($alert->notify_sms
                && $patternConfigured
                && option('sms_on_price_alert', 'on') == 'on') {
                try {
                    $alert->notify(new PriceAlertSms($product, $minPrice));
                } catch (\Throwable $e) {
                    Log::error('خطای ارسال پیامک هشدار کاهش قیمت', [
                        'alert_id' => $alert->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

            // فاز ۸۳ — کانال اعلان پروفایل: رکورد NotificationManage + پیوت به کاربر؛
            // در صفحهٔ «اعلان‌ها»ی پروفایل کاربر (front profile/notifications) دیده می‌شود
            if ($alert->notify_profile && $alert->user_id) {
                try {
                    $this->sendProfileNotification($alert->user_id, $product, $minPrice);
                } catch (\Throwable $e) {
                    Log::error('خطای ثبت اعلان پروفایل هشدار کاهش قیمت', [
                        'alert_id' => $alert->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

            // رفتار فاز ۷۸ حفظ شد: حتی اگر پیامک/اعلان ارسال نشد (مثلاً بدون الگو)
            // هشدار بسته می‌شود تا حلقهٔ پردازش تکرار نیفتد؛ لاگ هشدار بالا مانده است.
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

    /**
     * فاز ۸۳ — اعلان پروفایل برای کاربر عضو.
     * هم‌قرارداد با پنل «اعلان‌های کاربران» (UserNotifications): رکورد
     * NotificationManage با private=user + پیوت notification_manage_users؛
     * در front profile/notifications با userRecipients خوانده می‌شود.
     */
    private function sendProfileNotification(int $userId, Product $product, $price): void
    {
        $exists = NotificationManage::query()
            ->where('title', 'like', 'کاهش قیمت%')
            ->where('private', 'user')
            ->whereHas('recipients', fn ($q) => $q->where('user_id', $userId))
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($exists) {
            return; // ضدتکرار: برای هر کاربر و هر محصول در یک روز یک اعلان کافی است
        }

        $notification = new NotificationManage();
        $notification->title    = 'کاهش قیمت «'.$product->title.'»';
        $notification->message  = 'قیمت «'.$product->title.'» به '.number_format((float) $price).' تومان کاهش یافت. همین حالا با قیمت جدید خرید کنید.';
        $notification->private  = 'user';
        $notification->priority = 'low';
        $notification->popup    = 0;
        $notification->save();

        $notification->users()->attach($userId);
    }
}

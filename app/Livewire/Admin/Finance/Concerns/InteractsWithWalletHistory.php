<?php

namespace App\Livewire\Admin\Finance\Concerns;

use App\Models\Wallet;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * منطق مشترک وضعیت پرداخت/شماره پیگیریِ سطرهای کیف پول
 * (پورت ۱:۱ WalletController::status_pay قدیمی)
 *
 * پورت‌گیری دقیق:
 * - اگر مقدار جدید status_pay == unpay-refund و مقدار فعلی != unpay-refund باشد
 *   → ایجاد تاریخچه جبرانی deposit با توضیح «بازگشت وجه» (source=seller, status=success)
 *     + افزایش موجودی کیف پول به‌اندازه مبلغ سطر.
 * - سپس status_pay سطر ذخیره می‌شود.
 * باگ قدیمی: وقتی فقط trackingId ارسال می‌شد متد هیچ پاسخی نمی‌داد (silent) —
 * نسخه Livewire همیشه توست نشان می‌دهد (انحراف آگاهانه، بنا بر spec).
 */
trait InteractsWithWalletHistory
{
    /* وضعیت پرداخت (سلکت جزئیات) — مقدار فعلی سطر */
    public string $statusPay = '';

    /* شماره پیگیری (ورودی ثبت) */
    public string $trackingId = '';

    /* وضعیت‌های مجاز status_pay (ستون enum جدول wallet_histories) */
    protected array $statusPayValues = ['waiting', 'pay', 'unpay', 'unpay-refund'];

    /**
     * اعمال وضعیت پرداخت روی یک سطر — پورت دقیق status_pay() قدیمی.
     */
    protected function applyStatusPay(WalletHistory $history, string $value): void
    {
        DB::transaction(function () use ($history, $value) {
            if ($value === 'unpay-refund' && $history->status_pay !== 'unpay-refund') {
                // تاریخچه جبرانی «بازگشت وجه» — دقیقاً مثل قدیمی
                $new_history = new WalletHistory();
                $new_history->wallet_id = $history->wallet_id;
                $new_history->type = 'deposit';
                $new_history->order_id = $history->order_id;
                $new_history->source = 'seller';
                $new_history->status = 'success';
                $new_history->amount = $history->amount;
                $new_history->description = 'بازگشت وجه';
                $new_history->save();

                // افزایش موجودی کیف پول به‌اندازه مبلغ برداشت
                $wallet = Wallet::find($history->wallet_id);
                $amount = $wallet->balance + $history->amount;
                $wallet->balance = $amount;
                $wallet->save();
            }

            $history->status_pay = $value;
            $history->save();
        });
    }

    /**
     * تغییر وضعیت پرداخت از سلکت صفحه جزئیات (پورت status_pay قدیمی).
     * گیت: users.wallet (مثل روت قدیمی admin.wallets.status_pay).
     */
    public function updateStatusPay(): void
    {
        $this->authorizeFinance('users.wallet');

        $value = $this->statusPay;

        if ($value && in_array($value, $this->statusPayValues, true)) {
            $this->applyStatusPay($this->history, $value);

            $this->history->refresh();

            $this->dispatch('toast', message: 'وضعیت پرداخت بروزرسانی شد.', type: 'success');
        }
    }

    /**
     * ثبت شماره پیگیری (پورت شاخه trackingId قدیمی).
     * باگ قدیمی: این شاخه silent بود (هیچ پاسخی) — اینجا همیشه توست داریم.
     */
    public function saveTrackingId(): void
    {
        $this->authorizeFinance('users.wallet');

        $this->validate([
            'trackingId' => [
                'required',
                Rule::unique('wallet_histories', 'trackingId')->ignore($this->history->id),
            ],
        ], [
            'trackingId.unique' => 'شماره پیگیری قبلا ثبت شده است',
        ]);

        $this->history->trackingId = $this->trackingId;
        $this->history->save();

        $this->dispatch('toast', message: 'شماره پیگیری با موفقیت ثبت شد.', type: 'success');
    }

    /* گیت دسترسی مشترک ماژول مالی */
    protected function authorizeFinance(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
    }

    protected function validationAttributes(): array
    {
        return [
            'statusPay' => 'وضعیت پرداخت',
            'trackingId' => 'شماره پیگیری',
        ];
    }
}

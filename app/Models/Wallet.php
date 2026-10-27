<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function sellerInfo()
    {
        return $this->belongsTo(SellerInfo::class, 'seller_id');
    }

    public function histories()
    {
        return $this->hasMany(WalletHistory::class, 'wallet_id');
    }

    public function balance()
    {
        return $this->histories()->where('status', 'success')->where('type', 'deposit')->sum('amount') - $this->histories()->where('status', 'success')->where('type', 'withdraw')->sum('amount');
    }

    public function refereshBalance()
    {
        $this->balance = $this->balance();
        $this->save();
    }

    /**
     * واریز به کیف پول + ثبت تاریخچهٔ موفق + تازه‌سازی موجودی (فاز ۱۰۳).
     *
     * ⚠️ پیش از این فاز، CheckoutService::depositRefund() و مسیر بازگشت وجهِ تم
     * این متد را صدا می‌زدند در حالی که وجود نداشت → Fatal. اکنون متمرکز است.
     *
     * خروجی: رکورد تاریخچهٔ ساخته‌شده (truthy) — call-siteهای موجود فقط
     * truthiness را چک می‌کنند.
     */
    public function deposit(int $amount, ?string $description = null, array $attributes = [])
    {
        $history = $this->histories()->create(array_merge([
            'type' => 'deposit',
            'amount' => $amount,
            'description' => $description ?: 'افزایش اعتبار',
            'source' => $this->seller_id ? 'seller' : 'user',
            'status' => 'success',
        ], $attributes));

        $this->refereshBalance();

        return $history;
    }

    /**
     * برداشت از کیف پول + ثبت تاریخچهٔ موفق + تازه‌سازی موجودی (فاز ۱۰۳).
     * صدا زننده مسئول کفایت موجودی است (مثل فرم برداشت قدیمی).
     */
    public function withdraw(int $amount, ?string $description = null, array $attributes = [])
    {
        $history = $this->histories()->create(array_merge([
            'type' => 'withdraw',
            'amount' => $amount,
            'description' => $description ?: 'برداشت وجه',
            'source' => $this->seller_id ? 'seller' : 'user',
            'status' => 'success',
        ], $attributes));

        $this->refereshBalance();

        return $history;
    }
}

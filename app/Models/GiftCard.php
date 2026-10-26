<?php

namespace Modules\CustomerClub\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * کارت هدیه — صدور دسته‌ای ادمین، فعال‌سازی مشتری با شارژ مستقیم کیف پول هسته.
 */
class GiftCard extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
        'amount'     => 'integer',
    ];

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_DISABLED = 'disabled';

    public const STATUSES = [
        self::STATUS_ACTIVE   => 'فعال',
        self::STATUS_REDEEMED => 'مصرف‌شده',
        self::STATUS_DISABLED => 'غیرفعال',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'used_by');
    }

    /** تولید کد یگانه CC-XXXX-XXXX (هگز بزرگ) با تلاش مجدد روی برخورد */
    public static function generateCode(): string
    {
        do {
            $code = 'CC-'.strtoupper(bin2hex(random_bytes(2))).'-'.strtoupper(bin2hex(random_bytes(2)));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    /** آیا کارت قابل فعال‌سازی است؟ (پیام خطا برمی‌گرداند) */
    public function redeemBlocker(): ?string
    {
        if ($this->status === self::STATUS_DISABLED) {
            return 'این کارت هدیه غیرفعال شده است.';
        }

        if ($this->status === self::STATUS_REDEEMED) {
            return 'این کارت هدیه قبلاً استفاده شده است.';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'این کارت هدیه منقضی شده است.';
        }

        return null;
    }
}

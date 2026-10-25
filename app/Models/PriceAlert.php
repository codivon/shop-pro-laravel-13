<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * فاز ۷۸ — هشدار کاهش قیمت.
 *
 * Notifiable فقط برای عبور نوتیفیکیشن پیامکی (SmsChannel) از همین مدل
 * استفاده می‌شود؛ موبایل گیرنده در ستون mobile خود رکورد است.
 */
class PriceAlert extends Model
{
    use Notifiable;

    protected $guarded = ['id'];

    protected $casts = [
        'active'      => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

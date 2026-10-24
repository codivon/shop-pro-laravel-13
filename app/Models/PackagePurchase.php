<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagePurchase extends Model
{
    use HasFactory;

    protected $table = 'package_purchases';

    protected $fillable = [
        'type', 'admin_id', 'package_slug', 'package_name', 'version',
        'amount', 'currency', 'gateway', 'transaction_id',
        'license_key', 'license_expires_at', 'status',
        'payment_url', 'paid_at', 'meta',
    ];

    protected $casts = [
        'amount'             => 'integer',
        'license_expires_at' => 'datetime',
        'paid_at'            => 'datetime',
        'meta'               => 'array',
    ];

    /* ---------------- Constants ---------------- */

    public const STATUS_PENDING  = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    /* ---------------- Relationships ---------------- */

    public function admin()
    {
        return $this->belongsTo(\App\Models\Admin::class);
    }

    /* ---------------- Helpers ---------------- */

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function markAsPaid(array $data = []): void
    {
        $this->update(array_merge([
            'status'  => self::STATUS_PAID,
            'paid_at' => now(),
        ], $data));
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'meta'   => array_merge($this->meta ?? [], ['fail_reason' => $reason]),
        ]);
    }
}

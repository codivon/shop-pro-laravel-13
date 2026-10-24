<?php
// app/Models/BlockedDevice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedDevice extends Model
{
    protected $table = 'blocked_devices';

    protected $fillable = [
        'admin_id',
        'session_id',
        'ip_address',
        'device_fingerprint',
        'browser_fingerprint',
        'user_agent',
        'reason',
        'block_type',
        'blocked_until',
        'is_permanent'
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_permanent' => 'boolean'
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function isActive(): bool
    {
        if ($this->is_permanent) {
            return true;
        }

        if ($this->blocked_until && now()->lt($this->blocked_until)) {
            return true;
        }

        return false;
    }

    public function getStatusTextAttribute(): string
    {
        if ($this->is_permanent) {
            return 'دائمی';
        }

        if ($this->blocked_until && now()->lt($this->blocked_until)) {
            return 'موقت تا ' . jdate($this->blocked_until)->format('d F Y H:i');
        }

        return 'منقضی شده';
    }

    public function getBlockTypeTextAttribute(): string
    {
        return match($this->block_type) {
            'session' => 'نشست',
            'ip' => 'آیپی',
            'device' => 'دستگاه',
            'browser' => 'مرورگر',
            'all' => 'همه موارد',
            'account' => 'بن اکانت',
            default => 'دستگاه'
        };
    }

    public function getBlockIconAttribute(): string
    {
        return match($this->block_type) {
            'session' => 'fa-solid fa-window-maximize',
            'ip' => 'fa-solid fa-network-wired',
            'device' => 'fa-solid fa-laptop',
            'browser' => 'fa-brands fa-chrome',
            'all' => 'fa-solid fa-shield-haltered',
            'account' => 'fa-solid fa-user-lock',
            default => 'fa-solid fa-laptop'
        };
    }

    /** آیا این بلاک از نوع «بن اکانت» است؟ (فقط سازنده می‌تواند ایجاد/رفع کند) */
    public function isAccountBan(): bool
    {
        return $this->block_type === 'account';
    }
}

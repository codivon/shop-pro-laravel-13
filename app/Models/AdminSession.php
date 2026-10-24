<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdminSession extends Model
{
    protected $fillable = [
        'admin_id',
        'session_id',
        'device_fingerprint',
        'device_name',
        'device_type',
        'browser',
        'platform',
        'ip_address',
        'user_agent',
        'last_activity',
        'is_active',
        'is_primary',
        'primary_until'
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'primary_until' => 'datetime'
    ];

    /** آیا این نشست هنوز در پنجرهٔ محافظت ۴۸ ساعتهٔ «سیستم اصلی» است؟ */
    public function isPrimaryProtected(): bool
    {
        return $this->is_primary
            && $this->primary_until !== null
            && now()->lt($this->primary_until);
    }

    /** ثانیه‌های باقی‌ماندهٔ محافظت نشست اصلی */
    public function primaryProtectionLeftInSeconds(): int
    {
        if (! $this->isPrimaryProtected()) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->primary_until, false));
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    // گرفتن نشست‌های فعال یک ادمین
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // گرفتن تاریخچه کامل لاگین‌های یک ادمین
    public function scopeHistory($query, $adminId)
    {
        return $query->where('admin_id', $adminId)->orderBy('created_at', 'desc');
    }



    // گرفتن آیکون دستگاه
    public function getDeviceIconAttribute()
    {
        return match($this->device_type) {
            'mobile' => 'fa-solid fa-mobile-alt',
            'tablet' => 'fa-solid fa-tablet-alt',
            'desktop' => 'fa-solid fa-desktop',
            default => 'fa-solid fa-question-circle'
        };
    }

    // گرفتن کلاس Badge دستگاه
    public function getDeviceBadgeClassAttribute()
    {
        return match($this->device_type) {
            'mobile' => 'badge-info',
            'tablet' => 'badge-warning',
            'desktop' => 'badge-success',
            default => 'badge-secondary'
        };
    }

    // گرفتن نام دستگاه فارسی
    public function getDeviceTypeNameAttribute()
    {
        return match($this->device_type) {
            'mobile' => 'موبایل',
            'tablet' => 'تبلت',
            'desktop' => 'کامپیوتر',
            default => 'ناشناس'
        };
    }

    // گرفتن نام مرورگر از User Agent
    public function getBrowserNameAttribute()
    {
        if ($this->browser) {
            return $this->browser;
        }

        $userAgent = $this->user_agent;

        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
            return 'Opera';
        } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
            return 'Internet Explorer';
        }

        return 'نامشخص';
    }

    // گرفتن نام سیستم عامل از User Agent
    public function getPlatformNameAttribute()
    {
        if ($this->platform) {
            return $this->platform;
        }

        $userAgent = $this->user_agent;

        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            return 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'iOS';
        }

        return 'نامشخص';
    }

    // گرفتن زمان نسبی آخرین فعالیت
    public function getLastActivityAgoAttribute()
    {
        return $this->last_activity->diffForHumans();
    }
}

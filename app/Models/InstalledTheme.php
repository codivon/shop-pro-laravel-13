<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
 * قالب‌های نصب‌شده در پوشه themes/ — فاز ۴۴
 *
 * برخلاف ماژول‌ها، قالب‌ها لایسنس ندارند؛ وضعیت نصب و نسخه فقط برای
 * نمایش «نصب‌شده/در حال نصب/آپدیت» در بازار و لیست قالب‌ها استفاده می‌شود.
 */
class InstalledTheme extends Model
{
    protected $fillable = [
        'slug', 'name', 'version', 'purchase_type', 'transaction_id',
        'admin_id', 'status', 'last_error', 'installed_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
    ];

    public const STATUS_INSTALLED = 'installed';
    public const STATUS_UPDATING  = 'updating';
    public const STATUS_FAILED    = 'failed';

    /* ---------------- Helpers ---------------- */

    /** ثبت قالب در وضعیت «در حال نصب» از لحظه‌ی شروع (تجربه مثل پکیج‌ها) */
    public static function markPending(string $slug, ?string $name, ?string $version = null, string $purchaseType = 'normal', ?int $adminId = null): self
    {
        return static::updateOrCreate(
            ['slug' => $slug],
            [
                'name'          => $name ?: $slug,
                'version'       => $version,
                'purchase_type' => $purchaseType,
                'admin_id'      => $adminId,
                'status'        => self::STATUS_UPDATING,
                'last_error'    => null,
            ]
        );
    }

    public function markAsInstalled(?string $version = null): void
    {
        $this->update([
            'status'       => self::STATUS_INSTALLED,
            'version'      => $version ?: $this->version,
            'installed_at' => now(),
            'last_error'   => null,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status'     => self::STATUS_FAILED,
            'last_error' => $error,
        ]);
    }

    /** آیا این قالب، قالب فعال فروشگاه است؟ */
    public function isActive(): bool
    {
        return (get_current_theme()['name'] ?? null) === $this->name;
    }
}

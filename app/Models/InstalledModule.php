<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstalledModule extends Model
{
    use HasFactory;

    protected $table = 'installed_modules';

    protected $fillable = [
        'slug', 'name', 'version', 'license_key',
        'license_expires_at', 'installed_at', 'updated_at',
        'is_active', 'status', 'last_error',
    ];

    protected $casts = [
        'license_expires_at' => 'datetime',
        'installed_at'       => 'datetime',
        'updated_at'         => 'datetime',
        'is_active'          => 'boolean',
    ];

    /* ---------------- Constants ---------------- */

    public const STATUS_INSTALLED = 'installed';
    public const STATUS_UPDATING  = 'updating';
    public const STATUS_FAILED    = 'failed';

    /* ---------------- Relationships ---------------- */

    public function cache()
    {
        return $this->belongsTo(PackageCache::class, 'slug', 'slug');
    }

    public function logs()
    {
        return $this->hasMany(ModuleInstallLog::class, 'module_slug', 'slug');
    }

    /* ---------------- Helpers ---------------- */

    public function isExpired(): bool
    {
        return $this->license_expires_at && $this->license_expires_at->isPast();
    }

    public function isUpdatable(): bool
    {
        return $this->cache
            && version_compare($this->cache->latest_version, $this->version, '>');
    }

    public function markAsUpdating(): void
    {
        $this->update(['status' => self::STATUS_UPDATING, 'last_error' => null]);
    }

    public function markAsInstalled(string $version): void
    {
        $this->update([
            'version'     => $version,
            'status'      => self::STATUS_INSTALLED,
            'updated_at'  => now(),
            'last_error'  => null,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status'     => self::STATUS_FAILED,
            'last_error' => $error,
        ]);
    }

    // در InstalledModule:
    /**
     * ساخت/به‌روزرسانی رکورد در حالت "در حال نصب" — در لحظه dispatch
     * version همیشه مقدار دارد (ستون NOT NULL است)
     *
     * فاز ۲۷ — ستون name هم NOT NULL است؛ اگر name پاس نشود (مثل dispatchInstall
     * پکیج رایگان) باید از خود slug ساخته شود، وگرنه در strict mode درج با
     * خطای «1364 Field 'name' doesn't have a default value» شکست می‌خورد.
     */
    public static function markPending(
        string $slug,
        ?string $name = null,
        ?string $version = null,
        ?string $licenseKey = null
    ): self {
        $module = static::firstOrNew(['slug' => $slug]);

        $module->status     = self::STATUS_UPDATING;
        $module->is_active  = false;
        $module->last_error = null;

        if ($name !== null && $name !== '') $module->name = $name;
        if ($licenseKey !== null) $module->license_key = $licenseKey;

        // ★ فاز ۲۷ — name هرگز خالی نمی‌ماند (نام واقعی ماژول بعد از استخراج ZIP
        // توسط PackageInstallerService::registerInstall جایگزین می‌شود)
        if (empty($module->name)) {
            $module->name = static::deriveNameFromSlug($slug);
        }

        // نسخه: روی رکورد موجود حفظ، روی جدید placeholder
        if ($version !== null) {
            $module->version = $version;
        } elseif (!$module->exists && empty($module->version)) {
            $module->version = '0.0.0';
        }

        // ★ ستون‌های NOT NULL که تا اجرای job مقدار نمی‌گیرند
        if (empty($module->installed_at)) {
            $module->installed_at = now();
        }
        $module->last_verified_at = $module->last_verified_at ?? now();
        $module->integrity_hash   = $module->integrity_hash
            ?? md5(config('packages.api.token', '') . config('packages.api.project_key', ''));

        $module->save();

        return $module;
    }

    /** نام احتیاطی از روی slug — rack-widget → RackWidget (هماهنگ با نام پوشه Modules/) */
    public static function deriveNameFromSlug(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
    }

    /* ---------------- نمایش (فاز ۸۶) ---------------- */

    /**
     * عنوان نمایشی ماژول برای جدول‌ها و کارت‌های پنل.
     *
     * زنجیرهٔ حل:
     *   ۱) کلید «title» داخل module.json خود پکیج (منبع رسمی داخل پکیج است —
     *      سیاست فاز ۸۵/۸۶: هویت و مستندات پکیج داخل خود پکیج؛ مثلاً پکیج
     *      ادغامی درگاه‌ها با عنوان «پرداخت‌یار» شناخته می‌شود)
     *   ۲) نام نمایشیِ کش بازار (packages_cache.name)
     *   ۳) نام فنی رکورد (name)
     */
    public function displayName(): string
    {
        return static::moduleJsonTitle($this->name)
            ?? (filled($this->cache->name ?? null) ? (string) $this->cache->name : null)
            ?? (string) $this->name;
    }

    /** مموای هر ریکوئست — هر عنوان فقط یک‌بار از دیسک خوانده می‌شود */
    protected static ?array $moduleTitleMemo = null;

    /**
     * خواندن «title» از module.json پکیج نصب‌شده (fail-safe؛ نبودِ فایل یا
     * کلید یعنی null و رفتن به مرحلهٔ بعدِ زنجیره).
     */
    public static function moduleJsonTitle(?string $moduleName): ?string
    {
        $moduleName = trim((string) $moduleName);

        if ($moduleName === '') {
            return null;
        }

        static::$moduleTitleMemo ??= [];

        if (array_key_exists($moduleName, static::$moduleTitleMemo)) {
            return static::$moduleTitleMemo[$moduleName];
        }

        $title = null;

        try {
            $path = base_path('Modules/'.$moduleName.'/module.json');

            if (is_file($path)) {
                $json = json_decode((string) @file_get_contents($path), true);

                if (is_array($json)) {
                    $candidate = $json['title'] ?? null;

                    if (is_string($candidate) && trim($candidate) !== '') {
                        $title = trim($candidate);
                    }
                }
            }
        } catch (Throwable) {
            $title = null; // دیسک در دسترس نبود — مثل نبودِ عنوان
        }

        return static::$moduleTitleMemo[$moduleName] = $title;
    }
}

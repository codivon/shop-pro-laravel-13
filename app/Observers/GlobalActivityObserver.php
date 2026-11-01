<?php

namespace App\Observers;

use App\Models\Favorite;
use App\Models\Permission;
use App\Models\Review;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GlobalActivityObserver
{
    /**
     * مدل‌هایی که نباید لاگ شوند
     */
    protected $excludedModels = [
        // مدل‌های سیستمی و لاگ
        'App\Models\Activity',
        'App\Models\ActivityLog',
        'App\Models\AdminSession',
        'App\Models\Viewer',
        'App\Models\Session',
        'App\Models\FailedJob',
        'App\Models\Job',
        'App\Models\Migration',
        'App\Models\PasswordReset',
        'App\Models\PersonalAccessToken',
        'App\Models\PulseAggregate',
        'App\Models\PulseEntry',
        'App\Models\PulseValue',
        'App\Models\Notification',
        'App\Models\Comment',
        'App\Models\Setting',
        'App\Models\WidgetOption',
        'App\Models\Product',
        'App\Models\StockMovement',
        'App\Models\Price',
        'App\Models\PriceChange',

        // مدل‌های موقت و کمکی
        'App\Models\OneTimeCode',
        'App\Models\UserMobileVerify',
        'App\Models\NewSeller',
        'App\Models\BlockedDevice',
        'App\Models\Sms',
        'App\Models\PushSubscription',
        'App\Models\Favorite',
        'App\Models\Like',
        'App\Models\Review',
        'App\Models\ReviewPoint',
        'App\Models\OrderItem',
        'App\Models\Permission',

        // مدل‌های آماری
        'App\Models\Statistic',
        'App\Models\Referral',
    ];

    protected $eventRules = [
        // مدل Search: فقط حذف لاگ شود
        'App\Models\Search' => [
            'created' => false,
            'updated' => false,
            'deleted' => true,
        ],


    ];

    /**
     * فیلدهایی که نباید لاگ شوند
     */
    protected $excludedFields = [
        // فیلدهای پایه (همیشه در همه جداول)
        'id',
        'created_at',
        'updated_at',
        'deleted_at',

        // فیلدهای احراز هویت و امنیتی
        'password',
        'remember_token',
        'email_verified_at',
        'mobile_verification',
        'code_mobile_verification',
        'token',
        'remember_toke',
        'api_token',

        // فیلدهای سیستمی و آمار (که نیازی به لاگ ندارند)
        'view',
        'view_count',
        'views_count',
        'likes_count',
        'dislikes_count',
        'click_count',
        'product_clicks_count',
        'real_views_count',
        'count',

        // فیلدهای IP و User Agent
        'ip',
        'ip_address',
        'user_agent',
        'session_id',
        'device_fingerprint',
        'browser_fingerprint',
        'fingerprint',
        'device_type',
        'browser',
        'platform',
        'os',

        // فیلدهای ردگیری و session
        'last_activity',
        'last_used_at',
        'last_interacted_at',
        'interacted_at',
        'searched_at',
        'submitted_at',
        'read_at',

        // فیلدهای JSON و text طولانی (اختیاری)
        'payload',
        'exception',
        'data',
        'settings',
        'meta_data',
        'additional_data',
        'options',
        'filters',
        'result_ids',
        'attributes',
        'permissions',

        // فیلدهای توکن و کد
        'code',
        'key',
        'hash',
        'batch_uuid',
        'uuid',

        // فیلدهای حساس اطلاعاتی
        'card_number',
        'national_code',
        'identity_card_number',
        'national_identity_number',

        // فیلدهای لاگ خود Activity
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uid',
        'event',
    ];

    /**
     * فاز ۱۳۷ — کش استاتیک وجود ستون‌ها (در سطح ترکیب جدول/فیلد، در هر ریکوئست فقط یک‌بار)
     *
     * قبلاً modelHasField برای «هر رویداد لاگ‌شده» تا ۹ کوئری SHOW COLUMNS روی
     * information_schema می‌زد (برای فیلد name/title/... ) و چون getDisplayName
     * در logActivity دو بار صدا زده می‌شد، تا ۱۸ کوئری در هر لاگ اجرا می‌شد.
     * روی هاست اشتراکی هر کوئری information_schema چند ده میلی‌ثانیه طول می‌کشد؛
     * همین تک‌باگ، ذخیره‌سازی هر رکورد در پنل را چند صد میلی‌ثانیه کند می‌کرد.
     */
    protected static array $columnExistsCache = [];

    /**
     * Handle the created event for all models.
     */
    public function created(Model $model): void
    {
        // بررسی اینکه آیا این مدل باید لاگ شود
        if ($this->shouldExclude($model)) {
            return;
        }

        // بررسی آیا رویداد created برای این مدل مجاز است
        if (!$this->shouldLogEvent($model, 'created')) {
            return;
        }

        $this->logActivity($model, 'created');
    }

    /**
     * Handle the updated event for all models.
     */
    public function updated(Model $model): void
    {
        // بررسی اینکه آیا این مدل باید لاگ شود
        if ($this->shouldExclude($model)) {
            return;
        }

        // بررسی آیا رویداد updated برای این مدل مجاز است
        if (!$this->shouldLogEvent($model, 'updated')) {
            return;
        }

        // اگر تغییری نکرده بود لاگ نگیر
        if (count($model->getChanges()) === 0) {
            return;
        }

        $this->logActivity($model, 'updated');
    }


    /**
     * Handle the deleted event for all models.
     */
    public function deleted(Model $model): void
    {
        // بررسی اینکه آیا این مدل باید لاگ شود
        if ($this->shouldExclude($model)) {
            return;
        }

        // بررسی آیا رویداد deleted برای این مدل مجاز است
        if (!$this->shouldLogEvent($model, 'deleted')) {
            return;
        }

        $this->logActivity($model, 'deleted');
    }
    protected function shouldLogEvent(Model $model, string $event): bool
    {
        $modelClass = get_class($model);

        // اگر قانون خاصی برای این مدل وجود دارد
        if (isset($this->eventRules[$modelClass]) && isset($this->eventRules[$modelClass][$event])) {
            return $this->eventRules[$modelClass][$event];
        }

        // پیشفرض: همه رویدادها لاگ شوند
        return true;
    }

    /**
     * لاگ کردن فعالیت
     */
    protected function logActivity(Model $model, string $event): void
    {
        // دریافت نام مدل و نام نمایشی (فاز ۱۳۷ — فقط یک بار محاسبه می‌شود؛ قبلاً دوبار getDisplayName صدا زده می‌شد)
        $modelName = class_basename($model);
        $displayName = $this->getDisplayName($model);

        // دریافت کاربر انجام دهنده
        $causer = $this->getCauser();
        $changes = [];

        if ($event === 'updated') {
            // فیلتر کردن فیلدهای ناخواسته از مقادیر اصلی و جدید
            $original = $this->filterChanges($model->getOriginal());
            $new = $this->filterChanges($model->getChanges());

            // فقط فیلدهایی که واقعاً تغییر کرده‌اند را نگه دار
            $filteredOld = [];
            $filteredNew = [];

            foreach ($new as $key => $value) {
                // اگر مقدار قدیم و جدید متفاوت است
                if (isset($original[$key]) && $original[$key] != $value) {
                    $filteredOld[$key] = $original[$key];
                    $filteredNew[$key] = $value;
                }
                // اگر فیلد در اصلی نبود (فیلد جدید اضافه شده)
                elseif (!isset($original[$key])) {
                    $filteredOld[$key] = null;
                    $filteredNew[$key] = $value;
                }
            }

            // فقط اگر تغییری وجود داشت ثبت کن
            if (!empty($filteredOld) || !empty($filteredNew)) {
                $changes = [
                    'old' => $filteredOld,
                    'attributes' => $filteredNew
                ];
            }
        }
        elseif ($event === 'created') {
            $changes = [
                'attributes' => $this->filterChanges($model->getAttributes())
            ];
        }
        elseif ($event === 'deleted') {
            // برای حذف، اطلاعات مدل را قبل از حذف بگیر
            $attributes = $model->getAttributes();
            $filteredAttributes = $this->filterChanges($attributes);

            // اضافه کردن نام نمایشی به تغییرات
            $filteredAttributes['_display_name'] = $displayName;

            $changes = [
                'attributes' => $filteredAttributes
            ];
        }

        // اگر تغییری وجود نداشت، لاگ نگیر
        if (empty($changes)) {
            return;
        }

        // ساخت نام انجام دهنده
        $actorName = 'سیستم';
        if ($causer) {
            if ($causer instanceof \App\Models\Admin) {
                $actorName = 'مدیر ' . ($causer->full_name ?? $causer->name);
            } elseif ($causer instanceof \App\Models\User) {
                $actorName = 'کاربر ' . ($causer->full_name ?? $causer->name);
            } elseif ($causer instanceof \App\Models\Seller) {
                $actorName = 'فروشنده ' . ($causer->full_name ?? $causer->name);
            } else {
                $actorName = $causer->full_name ?? $causer->name ?? 'کاربر';
            }
        }

        // ثبت لاگ
        activity()
            ->performedOn($model)
            ->causedBy($causer)
            ->withProperties($changes)
            ->event($event)
            ->log($this->getDescription($event, $modelName, $displayName, $actorName));
    }

    /**
     * بررسی اینکه آیا مدل باید از لاگ حذف شود
     */
    protected function shouldExclude(Model $model): bool
    {
        // حذف مدل‌های خاص
        if (in_array(get_class($model), $this->excludedModels)) {
            return true;
        }

        return false;
    }

    /**
     * فیلتر کردن فیلدهای غیرضروری
     */
    protected function filterChanges(array $data): array
    {
        return array_diff_key($data, array_flip($this->excludedFields));
    }

    /**
     * دریافت نام نمایشی برای مدل
     */
    protected function getDisplayName(Model $model): string
    {
        // آرایه فیلدهای احتمالی برای نام نمایشی
        $possibleFields = ['name', 'title', 'full_name', 'username', 'email', 'mobile', 'keyword', 'comment', 'body'];

        foreach ($possibleFields as $field) {
            // بررسی وجود فیلد در مدل و مقدار داشتن آن
            if ($this->modelHasField($model, $field) && !empty($model->$field)) {
                $value = $model->$field;
                // محدود کردن طول برای فیلدهای طولانی مثل body
                if (in_array($field, ['body', 'comment'])) {
                    return mb_substr($value, 0, 50) . (mb_strlen($value) > 50 ? '...' : '');
                }
                return $value;
            }
        }

        return "#{$model->getKey()}";
    }

    protected function modelHasField(Model $model, string $field): bool
    {
        // روش اول: attributes بارگذاری‌شده — رایگان، بدون هیچ کوئری (فاز ۱۳۷)
        if (array_key_exists($field, $model->getAttributes())) {
            return true;
        }

        // روش دوم: کش استاتیک — برای هر ترکیب جدول/فیلد فقط یک بار در هر ریکوئست
        // کوئری اسکیما می‌زند (قبلاً هر بار SHOW COLUMNS می‌زد)
        $key = $model->getTable().'|'.$field;

        if (array_key_exists($key, self::$columnExistsCache)) {
            return self::$columnExistsCache[$key];
        }

        try {
            return self::$columnExistsCache[$key] = \Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $field);
        } catch (\Exception $e) {
            // اگر خطا رخ داد (اتصال، جدول ناموجود و...) false کش می‌شود تا دوباره تلاش نشود
            return self::$columnExistsCache[$key] = false;
        }
    }

    /**
     * دریافت کاربر انجام دهنده
     */
    protected function getCauser()
    {
        // اولویت: adminPanel > seller > web
        if (Auth::guard('adminPanel')->check()) {
            return Auth::guard('adminPanel')->user();
        }

        if (Auth::guard('seller')->check()) {
            return Auth::guard('seller')->user();
        }

        if (Auth::check()) {
            return Auth::user();
        }

        return null;
    }

    /**
     * دریافت توضیحات رویداد
     */
    /**
     * دریافت توضیحات رویداد با جزییات کامل
     */
    /**
     * دریافت توضیحات رویداد
     */
    protected function getDescription(string $event, string $modelName, string $displayName, string $actorName): string
    {
        // ترجمه نام مدل به فارسی
        $modelTranslations = [
            'Post' => 'مقاله',
            'Product' => 'محصول',
            'User' => 'کاربر',
            'Admin' => 'مدیر',
            'Category' => 'دسته‌بندی',
            'Order' => 'سفارش',
            'Comment' => 'نظر',
            'Search' => 'جستجو',
            'Brand' => 'برند',
            'Slider' => 'اسلایدر',
            'Page' => 'صفحه',
            'Menu' => 'منو',
            'Role' => 'نقش',
            'Permission' => 'دسترسی',
            'Widget' => 'ابزارک',
            'Gateway' => 'درگاه پرداخت',
            'Seller' => 'فروشنده',
            'Warehouse' => 'انبار',
            'Ticket' => 'تیکت',
            'Review' => 'نظر',
            'Coupon' => 'کوپن',
            'Discount' => 'تخفیف',
            'filter_type' => 'نوع فیلتر',
            'productcat' => 'دسته بندی محصول',
        ];

        $persianModelName = $modelTranslations[$modelName] ?? $modelName;

        return match($event) {
            'created' => "{$actorName}، {$persianModelName} «{$displayName}» را ایجاد کرد",
            'updated' => "{$actorName}، {$persianModelName} «{$displayName}» را بروزرسانی کرد",
            'deleted' => "{$actorName}، {$persianModelName} «{$displayName}» را حذف کرد",
            default => "{$actorName}، {$persianModelName} «{$displayName}» را {$event} کرد",
        };
    }
    /**
     * ترجمه نام فیلدها به فارسی
     */
    protected function translateFieldName($key)
    {
        $fieldTitles = [
            // فیلدهای عمومی
            'title' => 'عنوان',
            'name' => 'نام',
            'full_name' => 'نام کامل',
            'username' => 'نام کاربری',
            'email' => 'ایمیل',
            'mobile' => 'شماره موبایل',
            'phone' => 'تلفن',
            'address' => 'آدرس',
            'description' => 'توضیحات',
            'status' => 'وضعیت',
            'type' => 'نوع',
            'image' => 'تصویر',
            'images' => 'تصاویر',
            'slug' => 'نامک',
            'ordering' => 'ترتیب',
            'is_active' => 'فعال',
            'is_published' => 'منتشر شده',
            'published' => 'وضعیت انتشار',

            // فیلدهای محتوا
            'content' => 'محتوا',
            'body' => 'متن',
            'summary' => 'خلاصه',
            'excerpt' => 'چکیده',

            // فیلدهای محصول
            'price' => 'قیمت',
            'stock' => 'موجودی',
            'weight' => 'وزن',
            'brand_id' => 'برند',
            'category_id' => 'دسته‌بندی',
            'seller_id' => 'فروشنده',

            // فیلدهای مقاله
            'post_type' => 'نوع پست',
            'admin_id' => 'نویسنده',
            'created_by' => 'ایجاد شده توسط',
            'is_editor_pick' => 'انتخاب سردبیر',
            'allow_comments' => 'مجوز نظرات',
            'view' => 'تعداد بازدید',

            // فیلدهای کاربر
            'password' => 'رمز عبور',
            'role' => 'نقش',
            'permissions' => 'دسترسی‌ها',
            'last_login' => 'آخرین ورود',
            'remember_token' => 'توکن یادآوری',

            // فیلدهای سفارش
            'order_id' => 'شماره سفارش',
            'total' => 'مجموع',
            'subtotal' => 'جمع جزء',
            'discount' => 'تخفیف',
            'shipping_cost' => 'هزینه ارسال',
            'payment_status' => 'وضعیت پرداخت',
            'shipping_status' => 'وضعیت ارسال',

            // فیلدهای تنظیمات
            'info_site_title' => 'عنوان وبسایت',
            'info_email' => 'ایمیل',
            'info_tel' => 'تلفن',
            'info_fax' => 'فکس',
            'info_address' => 'آدرس',
            'info_primary_color' => 'رنگ اصلی',
            'admin_route_prefix' => 'پیشوند آدرس مدیریت',
            'multi_language_enabled' => 'چند زبانه',

            // فیلدهای سئو
            'meta_title' => 'عنوان سئو',
            'meta_description' => 'توضیحات سئو',
            'meta_keywords' => 'کلمات کلیدی',
            'canonical' => 'آدرس کانونیکال',
            'robots' => 'ربات‌ها',

            // فیلدهای رسانه‌های اجتماعی
            'facebook' => 'فیسبوک',
            'instagram' => 'اینستاگرام',
            'telegram' => 'تلگرام',
            'twitter' => 'توییتر',
            'linkedin' => 'لینکدین',
            'youtube' => 'یوتیوب',
            'whatsapp' => 'واتساپ',
            'aparat' => 'آپارات',
            'productcat' => 'دسته بندی محصول',
            'filter_type' => 'نوع فیلتر',
        ];

        return $fieldTitles[$key] ?? str_replace('_', ' ', $key);
    }

    /**
     * دریافت properties از activity جاری
     * توجه: این متد باید در کلاس تنظیم شود
     */
    protected $currentProperties = null;

    protected function setProperties($properties)
    {
        $this->currentProperties = $properties;
    }

    protected function getProperties()
    {
        return $this->currentProperties;
    }
}

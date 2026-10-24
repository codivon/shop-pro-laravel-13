<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Gateway;
use App\Models\Province;
use App\Models\SellerCommission;
use App\Models\SellerEcontract;
use App\Models\SellerHero;
use App\Models\SellerQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function showInformation()
    {
        $this->authorize('settings.information');

        $provinces = Province::detectLang()->get();

        $info_province = option('info_province_id');

        if ($info_province) {
            $cities = Province::find($info_province)->cities;
        } else {
            $cities = [];
        }

        return view('back.settings.information', compact('provinces', 'cities'));
    }

    public function updateInformation(Request $request)
    {
        $this->authorize('settings.information');

        $this->validate($request, [
            'info_site_title'  => 'required',
            'info_icon'        => 'max:2048',
            'info_logo'        => 'max:2048',
            'info_logo_seller' => 'max:2048',
            'info_logo_panel_seller' => 'max:2048',
            'info_og_image'    => 'max:2048',
            'info_city_id'     => 'exists:cities,id',
            'info_province_id' => 'exists:provinces,id',
        ]);

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // ========== 1. بررسی تغییرات فایل‌ها (آپلود) ==========
        /*$fileInputs = [
            'info_icon' => 'آیکون سایت',
            'info_logo' => 'لوگوی شرکت',
            'info_logo_seller' => 'لوگوی شرکت فروشندگان',
            'info_logo_panel_seller' => 'لوگوی پنل فروشندگان',
            'info_og_image' => 'تصویر OG (اشتراک‌گذاری)',
        ];*/
        $fileInputs=[];
        $fileChanges = [];
        /*foreach ($fileInputs as $key => $title) {
            dd($fileInputs,$request->all());
            if ($request->hasFile($key)) {
                $oldFile = option($key);

                $imageName = time() . '_' . $key . '.' . $request->$key->getClientOriginalExtension();
                $request->$key->move(public_path('uploads/'), $imageName);

                // حذف فایل قدیمی اگر وجود داشت
                if ($oldFile && file_exists(public_path($oldFile))) {
                    unlink(public_path($oldFile));
                }

                $newFile = '/uploads/' . $imageName;
                option_update($key, $newFile);

                $fileChanges[$key] = [
                    'old' => $oldFile ?: 'بدون فایل',
                    'new' => $newFile,
                    'title' => $title
                ];
            }
        }

        if (!empty($fileChanges)) {
            $allChanges['file_uploads'] = $fileChanges;
        }*/

        // ========== 2. بررسی تغییرات env (پیشوند ادمین) ==========
        $admin_route_prefix_changed = false;
        $oldAdminRoutePrefix = env('ADMIN_ROUTE_PREFIX');
        $newAdminRoutePrefix = $request->admin_route_prefix;

        if ($oldAdminRoutePrefix != $newAdminRoutePrefix) {
            $admin_route_prefix_changed = true;
            change_env('ADMIN_ROUTE_PREFIX', $newAdminRoutePrefix);
            Artisan::call('route:cache');

            $allChanges['env_settings'] = [
                'admin_route_prefix' => [
                    'old' => $oldAdminRoutePrefix ?: 'admin',
                    'new' => $newAdminRoutePrefix,
                    'title' => 'پیشوند آدرس مدیریت'
                ]
            ];
        }

        // ========== 3. بررسی تغییرات تنظیمات options ==========
        $optionChanges = [];

        // فیلدهایی که نباید لاگ شوند (فایل‌ها و env قبلاً بررسی شدند)
        $excludeFields = array_keys($fileInputs);
        $excludeFields[] = 'admin_route_prefix';
        $excludeFields[] = '_token';

        foreach ($request->all() as $key => $value) {
            if (in_array($key, $excludeFields)) {
                continue;
            }

            $oldValue = option($key);

            // نرمال کردن مقادیر برای مقایسه
            $normalizedOld = $oldValue;
            $normalizedNew = $value;

            if ($oldValue != $value) {
                $optionChanges[$key] = [
                    'old' => $normalizedOld ?: 'خالی',
                    'new' => $normalizedNew ?: 'خالی',
                    'title' => $this->getInformationFieldTitle($key)
                ];
            }

            option_update($key, $value);
        }

        if (!empty($optionChanges)) {
            $allChanges['option_settings'] = $optionChanges;
        }

        // ========== 4. ثبت لاگ نهایی ==========
        if (!empty($allChanges)) {
            $logMessage = "مدیر {$adminName} تنظیمات اطلاعات کلی سایت را به‌روزرسانی کرد: ";
            $changeDetails = [];

            // تغییرات فایل‌ها
            if (isset($allChanges['file_uploads'])) {
                foreach ($allChanges['file_uploads'] as $change) {
                    $changeDetails[] = "{$change['title']} آپلود شد";
                }
            }

            // تغییرات env
            if (isset($allChanges['env_settings'])) {
                foreach ($allChanges['env_settings'] as $change) {
                    $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
                }
            }

            // نمایش همه تغییرات option ها (تک تک)
            if (isset($allChanges['option_settings']) && !empty($allChanges['option_settings'])) {
                foreach ($allChanges['option_settings'] as $key => $change) {
                    $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
                }
            }

            $logMessage .= implode('، ', $changeDetails);

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات اطلاعات کلی سایت را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => $request->ip()
                ])
                ->log($logMessage);
        }

        return response()->json([
            'admin_route_prefix' => $newAdminRoutePrefix,
            'admin_route_prefix_changed' => $admin_route_prefix_changed
        ]);
    }

    /**
     * دریافت عنوان فارسی فیلدهای تنظیمات اطلاعات کلی
     */
    private function getInformationFieldTitle($key)
    {
        $titles = [
            // ========== اطلاعات پایه (Basic Info) ==========
            'info_site_title' => 'عنوان وبسایت',
            'info_email' => 'ایمیل',
            'info_tel' => 'تلفن',
            'info_fax' => 'فکس',
            'info_postal_code' => 'کد پستی',
            'info_support_phone' => 'شماره پشتیبانی',
            'info_working_hours' => 'ساعات کاری',
            'info_primary_color' => 'رنگ اصلی سایت',
            'info_primary_color_text' => 'رنگ اصلی سایت (متن)',
            'info_address' => 'آدرس',
            'info_province_id' => 'استان',
            'info_city_id' => 'شهر',

            // ========== تصاویر و لوگو (Images & Logos) ==========
            'info_icon' => 'آیکون سایت',
            'info_logo' => 'لوگوی شرکت',
            'info_logo_seller' => 'لوگوی شرکت فروشندگان',
            'info_logo_panel_seller' => 'لوگوی پنل فروشندگان',
            'info_og_image' => 'تصویر OG (اشتراک‌گذاری)',

            // ========== سئو و متا (SEO & Meta) ==========
            'info_tags' => 'کلمات کلیدی',
            'info_canonical' => 'آدرس کانونیکال',
            'info_short_description' => 'توضیحات کوتاه (Meta Description)',
            'info_footer_text' => 'متن فوتر',
            'info_robots_txt' => 'Robots.txt',

            // ========== اسکریپت‌ها و کدها (Scripts & Codes) ==========
            'info_gtm_id' => 'شناسه Google Tag Manager',
            'info_meta_pixel' => 'شناسه Meta Pixel',
            'info_header_codes' => 'کدهای هدر',
            'info_scripts' => 'اسکریپت‌های اضافه',

            // ========== نمادهای اعتماد (Trust Seals) ==========
            'info_enamad' => 'اسکریپت نماد اعتماد',
            'info_samandehi' => 'کد ساماندهی',

            // ========== نقشه (Map Settings) ==========
            'info_latitude' => 'عرض جغرافیایی (Latitude)',
            'info_Longitude' => 'طول جغرافیایی (Longitude)',
            'info_map_zoom' => 'سطح زوم نقشه',
            'map_api' => 'کلید API نقشه (map.ir)',
            'info_map_type' => 'نوع نقشه',

            // ========== پیکربندی پیشرفته (Advanced Config) ==========
            'admin_route_prefix' => 'پیشوند آدرس ورود به بخش مدیریت',
            'multi_language_enabled' => 'فعال کردن سایت چند زبانه',

            // ========== سایر تنظیمات (Other Settings) ==========
            'info_copyright_text' => 'متن کپی‌رایت',
            'info_terms_url' => 'لینک قوانین و مقررات',
            'info_privacy_url' => 'لینک حریم خصوصی',
            'info_about_us' => 'درباره ما',
            'info_contact_us' => 'تماس با ما',
        ];

        return $titles[$key] ?? $this->humanReadableKey($key);
    }

    /**
     * تبدیل کلید به فرمت خوانا (برای فیلدهایی که در لیست بالا نیستند)
     */
    private function humanReadableKey($key)
    {
        // حذف پیشوند info_ اگر وجود داشت
        $cleanKey = str_replace('info_', '', $key);

        // جایگزینی زیرخط با فاصله
        $cleanKey = str_replace('_', ' ', $cleanKey);

        // تبدیل به حروف بزرگ اول کلمات
        $cleanKey = ucwords($cleanKey);

        return $cleanKey;
    }

    public function showSocials()
    {
        $this->authorize('settings.socials');

        return view('back.settings.socials');
    }

    public function updateSocials(Request $request)
    {
        $this->authorize('settings.socials');

        $socials = $request->all();
        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // دریافت لیست شبکه‌های اجتماعی از کانفیگ
        $socialsList = config('front.socials', []);
        $socialNames = [];
        foreach ($socialsList as $social) {
            $socialNames[$social['key']] = $social['name'];
        }

        foreach ($socials as $social => $value) {
            $oldValue = option($social);

            // فقط در صورت تغییر، لاگ ثبت کن
            if ($oldValue != $value) {
                $socialTitle = $socialNames[$social] ?? $social;

                $allChanges[$social] = [
                    'old' => $oldValue ?: 'خالی',
                    'new' => $value ?: 'خالی',
                    'title' => $socialTitle
                ];
            }

            option_update($social, $value);
        }

        // ثبت لاگ در صورت وجود تغییرات
        if (!empty($allChanges)) {
            $changeTexts = [];
            foreach ($allChanges as $change) {
                $changeTexts[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
            }

            $logMessage = "مدیر {$adminName} تنظیمات شبکه‌های اجتماعی را به‌روزرسانی کرد: " . implode('، ', $changeTexts);

            // اگر خیلی طولانی شد، خلاصه کن
            if (mb_strlen($logMessage) > 500) {
                $logMessage = "مدیر {$adminName} تنظیمات شبکه‌های اجتماعی را در " . count($allChanges) . " مورد به‌روزرسانی کرد";
            }

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات شبکه‌های اجتماعی را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => $request->ip()
                ])
                ->log($logMessage);
        }

        return response('success');
    }

    public function showGateways()
    {
        $this->authorize('settings.gateway');

        foreach (config('general.supported_gateways') as $key => $name) {
            Gateway::firstOrCreate(
                [
                    'key' => $key
                ],
                [
                    'name' => $name
                ]
            );
        }

        $gateways = Gateway::get();

        return view('back.settings.gateways', compact('gateways'));
    }

    public function updateGateways(Request $request)
    {
        $this->authorize('settings.gateway');

        $active_ids = [];
        $allChanges = []; // برای ذخیره تمام تغییرات

        if ($request->gateways) {
            foreach ($request->gateways as $id => $request_gateway) {
                if (!isset($request_gateway['is_active'])) {
                    continue;
                }

                $active_ids[] = $id;
                $gateway = Gateway::find($id);

                if (!$gateway) {
                    continue;
                }

                // ذخیره مقادیر قدیمی
                $oldData = [
                    'name' => $gateway->name,
                    'ordering' => $gateway->ordering,
                    'is_active' => $gateway->is_active,
                    'configs' => []
                ];

                // ذخیره مقادیر قدیمی configs
                foreach ($gateway->configs as $config) {
                    $oldData['configs'][$config->key] = $config->value;
                }

                // مقادیر جدید
                $newData = [
                    'name' => $request_gateway['name'],
                    'ordering' => $request_gateway['ordering'],
                    'is_active' => true,
                    'configs' => $request_gateway['configs'] ?? []
                ];

                // بررسی تغییرات
                $gatewayChanges = [];

                if ($oldData['name'] != $newData['name']) {
                    $gatewayChanges['name'] = ['old' => $oldData['name'], 'new' => $newData['name']];
                }

                if ($oldData['ordering'] != $newData['ordering']) {
                    $gatewayChanges['ordering'] = ['old' => $oldData['ordering'], 'new' => $newData['ordering']];
                }

                if ($oldData['is_active'] != $newData['is_active']) {
                    $gatewayChanges['is_active'] = ['old' => $oldData['is_active'] ? 'فعال' : 'غیرفعال', 'new' => $newData['is_active'] ? 'فعال' : 'غیرفعال'];
                }

                // بررسی تغییرات configs
                foreach ($newData['configs'] as $key => $newValue) {
                    $oldValue = $oldData['configs'][$key] ?? null;
                    if ($oldValue != $newValue) {
                        $gatewayChanges['configs'][$key] = ['old' => $oldValue, 'new' => $newValue];
                    }
                }

                // بررسی configs حذف شده
                foreach ($oldData['configs'] as $key => $oldValue) {
                    if (!isset($newData['configs'][$key])) {
                        $gatewayChanges['configs'][$key] = ['old' => $oldValue, 'new' => 'حذف شده'];
                    }
                }

                // اگر تغییری وجود داشت، ذخیره کن
                if (!empty($gatewayChanges)) {
                    $allChanges[$gateway->name] = $gatewayChanges;
                }

                // انجام آپدیت
                $gateway->update([
                    'name' => $request_gateway['name'],
                    'ordering' => $request_gateway['ordering'],
                    'is_active' => true,
                ]);

                foreach ($request_gateway['configs'] as $key => $value) {
                    $gateway->configs()->updateOrCreate(
                        ['key' => $key],
                        ['value' => $value]
                    );
                }
            }
        }

        // غیرفعال کردن درگاه‌هایی که در لیست نیستند
        $deactivatedGateways = Gateway::whereNotIn('id', $active_ids)->get();
        foreach ($deactivatedGateways as $gateway) {
            if ($gateway->is_active) {
                $allChanges[$gateway->name] = [
                    'is_active' => ['old' => 'فعال', 'new' => 'غیرفعال']
                ];
            }
        }

        Gateway::whereNotIn('id', $active_ids)->update([
            'is_active' => false
        ]);

        // ثبت لاگ کامل
        if (!empty($allChanges)) {
            $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

            // ساخت متن لاگ کامل
            $logDetails = [];
            foreach ($allChanges as $gatewayName => $changes) {
                $changeText = "درگاه «{$gatewayName}»: ";
                $fieldChanges = [];

                if (isset($changes['name'])) {
                    $fieldChanges[] = "نام از «{$changes['name']['old']}» به «{$changes['name']['new']}»";
                }
                if (isset($changes['ordering'])) {
                    $fieldChanges[] = "ترتیب از {$changes['ordering']['old']} به {$changes['ordering']['new']}";
                }
                if (isset($changes['is_active'])) {
                    $fieldChanges[] = "وضعیت از {$changes['is_active']['old']} به {$changes['is_active']['new']}";
                }
                if (isset($changes['configs'])) {
                    foreach ($changes['configs'] as $configKey => $configChange) {
                        $configNames = [
                            'merchantId' => 'کد درگاه',
                            'terminalId' => 'کد پایانه',
                            'acceptorId' => 'کد پذیرنده',
                            'username' => 'نام کاربری',
                            'password' => 'رمز عبور',
                            'key' => 'کلید',
                            'shop_slug' => 'شناسه فروشگاه',
                            'auth_code' => 'کد احراز هویت',
                            'pubKey' => 'کلید عمومی',
                        ];
                        $configName = $configNames[$configKey] ?? $configKey;
                        $fieldChanges[] = "{$configName} از «{$configChange['old']}» به «{$configChange['new']}»";
                    }
                }

                $logDetails[] = $changeText . implode('، ', $fieldChanges);
            }

            $fullLogMessage = "مدیر {$adminName} تنظیمات درگاه‌های پرداخت را به‌روزرسانی کرد: " . implode('; ', $logDetails);

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات درگاه‌های پرداخت را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => $request->ip()
                ])
                ->log($fullLogMessage);
        }

        return response('success');
    }
    public function showOthers()
    {
        $this->authorize('settings.others');

        $currencies = Currency::latest()->get();

        return view('back.settings.others', compact('currencies'));
    }

    public function updateOthers(Request $request)
    {
        $this->authorize('settings.others');

        $env_options = [
            'PUSHER_APP_ID',
            'PUSHER_APP_KEY',
            'PUSHER_APP_SECRET',
            'PUSHER_APP_CLUSTER',
            'MAIL_TRANSPORT',
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_ENCRYPTION',
        ];

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // ========== 1. بررسی تغییرات تنظیمات env ==========
        $env = $request->only($env_options);
        $envChanges = [];

        foreach ($env as $key => $value) {
            $oldValue = env($key);
            if ($oldValue != $value) {
                $envChanges[$key] = [
                    'old' => $oldValue ?: 'خالی',
                    'new' => $value ?: 'خالی',
                    'title' => $this->getOthersFieldTitle($key)
                ];
            }
            change_env($key, $value);
        }

        // بررسی BROADCAST_DRIVER
        $oldBroadcastDriver = env('BROADCAST_DRIVER');
        if ($request->PUSHER_APP_ID && $request->PUSHER_APP_KEY && $request->PUSHER_APP_SECRET) {
            change_env('BROADCAST_DRIVER', 'pusher');
            if ($oldBroadcastDriver != 'pusher') {
                $envChanges['BROADCAST_DRIVER'] = [
                    'old' => $oldBroadcastDriver ?: 'خالی',
                    'new' => 'pusher',
                    'title' => 'درایور پخش'
                ];
            }
        } else {
            change_env('BROADCAST_DRIVER', 'log');
            if ($oldBroadcastDriver != 'log') {
                $envChanges['BROADCAST_DRIVER'] = [
                    'old' => $oldBroadcastDriver ?: 'خالی',
                    'new' => 'log',
                    'title' => 'درایور پخش'
                ];
            }
        }

        // بررسی AI_TOKEN_KEY
        if ($request->AI_TOKEN_KEY) {
            $oldAiToken = env('AI_TOKEN_KEY');
            if ($oldAiToken != $request->AI_TOKEN_KEY) {
                $envChanges['AI_TOKEN_KEY'] = [
                    'old' => $oldAiToken ?: 'خالی',
                    'new' => '••••••', // مخفی کردن توکن
                    'title' => 'توکن هوش مصنوعی'
                ];
            }
            change_env('AI_TOKEN_KEY', $request->AI_TOKEN_KEY);
        }

        if (!empty($envChanges)) {
            $allChanges['env_settings'] = $envChanges;
        }

        // ========== 2. بررسی تغییرات فایل‌ها (آپلود) ==========
        $file_inputs = [
            'factor_logo' => 'لوگو فاکتور',
            'watermarkImage' => 'تصویر واترمارک',
        ];

        $fileChanges = [];
        foreach ($file_inputs as $key => $title) {
            if ($request->hasFile($key)) {
                $oldFile = option($key);

                $imageOptions = [
                    'size' => 100,
                    'watermark' => false,
                    'path' => "uploads/others/",
                    'field' => "option_value",
                    'format' => "png",
                ];
                $filename = uploadOptimizedImage($request->file($key), 'options', $key, $imageOptions);
                option_update($key, $filename);

                $fileChanges[$key] = [
                    'old' => $oldFile ?: 'بدون فایل',
                    'new' => $filename,
                    'title' => $title
                ];
            }
        }

        if (!empty($fileChanges)) {
            $allChanges['file_uploads'] = $fileChanges;
        }

        // ========== 3. بررسی تغییرات تنظیمات دیگر (options) ==========
        $others = $request->except(array_merge($env_options, $file_inputs, ['AI_TOKEN_KEY']));

        $optionChanges = [];
        foreach ($others as $key => $value) {
            $oldValue = option($key);

            // تبدیل مقادیر boolean برای مقایسه بهتر
            $normalizedOld = $oldValue;
            $normalizedNew = $value;

            if ($key === 'multi_vendor_system_status') {
                $normalizedOld = $oldValue === 'true' ? 'فعال' : 'غیرفعال';
                $normalizedNew = $value === 'true' ? 'فعال' : 'غیرفعال';
            }

            if ($oldValue != $value) {
                $optionChanges[$key] = [
                    'old' => $normalizedOld ?: 'خالی',
                    'new' => $normalizedNew ?: 'خالی',
                    'title' => $this->getOthersFieldTitle($key)
                ];
            }
            option_update($key, $value);
        }

        if (!empty($optionChanges)) {
            $allChanges['option_settings'] = $optionChanges;
        }

        // ========== 4. ثبت لاگ نهایی ==========
        if (!empty($allChanges)) {
            $logMessage = "مدیر {$adminName} تنظیمات عمومی سایت را به‌روزرسانی کرد: ";
            $changeGroups = [];

            if (isset($allChanges['env_settings']) && !empty($allChanges['env_settings'])) {
                $envCount = count($allChanges['env_settings']);
                $changeGroups[] = "{$envCount} تنظیم محیطی";
            }

            if (isset($allChanges['file_uploads']) && !empty($allChanges['file_uploads'])) {
                $fileCount = count($allChanges['file_uploads']);
                $changeGroups[] = "{$fileCount} فایل آپلودی";
            }

            if (isset($allChanges['option_settings']) && !empty($allChanges['option_settings'])) {
                $optionCount = count($allChanges['option_settings']);
                $changeGroups[] = "{$optionCount} تنظیم دیگر";
            }

            $logMessage .= implode('، ', $changeGroups);

            // اضافه کردن جزئیات مهم (مثل تغییر وضعیت چند فروشندگی)
            if (isset($allChanges['option_settings']['multi_vendor_system_status'])) {
                $change = $allChanges['option_settings']['multi_vendor_system_status'];
                $logMessage .= " (سیستم چند فروشندگی: {$change['old']} → {$change['new']})";
            }

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات عمومی سایت را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => $request->ip()
                ])
                ->log($logMessage);
        }

        return response('success');
    }

    /**
     * دریافت عنوان فارسی فیلدهای تنظیمات دیگر
     */
    private function getOthersFieldTitle($key)
    {
        $titles = [
            // تنظیمات env
            'PUSHER_APP_ID' => 'شناسه اپلیکیشن Pusher',
            'PUSHER_APP_KEY' => 'کلید اپلیکیشن Pusher',
            'PUSHER_APP_SECRET' => 'رمز اپلیکیشن Pusher',
            'PUSHER_APP_CLUSTER' => 'خوشه Pusher',
            'BROADCAST_DRIVER' => 'درایور پخش',
            'AI_TOKEN_KEY' => 'توکن هوش مصنوعی',

            // تنظیمات چند فروشندگی
            'multi_vendor_system_status' => 'سیستم چند فروشندگی',

            // تنظیمات قیمت
            'default_currency_id' => 'ارز پیش فرض',
            'default_rounding_amount' => 'مقدار گرد کردن',
            'default_rounding_type' => 'نحوه گرد کردن',

            // تنظیمات فاکتور
            'factor_logo' => 'لوگو فاکتور',
            'factor_title' => 'عنوان فاکتور',
            'factor_seller_name' => 'نام فروشنده در فاکتور',
            'factor_national_code' => 'شناسه ملی',
            'factor_registeration_id' => 'شناسه ثبت',
            'factor_economical_number' => 'شماره اقتصادی',

            // تنظیمات کاربران
            'user_register_gift_credit' => 'اعتبار هدیه ثبت نام کاربر',
            'user_referrals_enable' => 'امکان معرفی افراد',
            'owner_referrals_amount' => 'تخفیف معرفی کننده',
            'user_referrals_amount' => 'تخفیف معرفی شونده',

            // تنظیمات تصاویر
            'optimizeImage' => 'درصد بهینه سازی تصاویر',
            'changePhotoFormat' => 'فرمت تبدیل تصاویر',
            'watermarkStatus' => 'وضعیت واترمارک',
            'watermarkImage' => 'تصویر واترمارک',
            'watermarkImagePosition' => 'موقعیت واترمارک',
        ];

        return $titles[$key] ?? $key;
    }
    public function showSms()
    {
        $this->authorize('settings.sms');

        return view('back.settings.sms');
    }

    public function updateSms(Request $request)
    {
        $this->authorize('settings.sms');

        $except = [
            'sms_on_user_register',
            'sms_on_seller_register',
            'sms_to_verify_user',
            'forgot_password_link',
            'sms_on_order_paid',
            'seller_sms_on_order_paid',
            'login_with_code',
            'user_sms_on_order_paid',
            'wallet_increase_sms',
            'wallet_decrease_sms',
            'sms_on_order_cancelled',        // اضافه شده
            'seller_sms_on_order_cancelled', // اضافه شده
            'user_sms_on_order_cancelled',   // اضافه شده
            'wallet_refund_sms',             // اضافه شده
        ];

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // بررسی تغییرات در تنظیمات اصلی (sms_panel_provider, admin_mobile_number, etc.)
        $sms = $request->except($except);

        foreach ($sms as $key => $value) {
            $oldValue = option($key);
            if ($oldValue != $value) {
                $allChanges[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                    'title' => $this->getSmsFieldTitle($key)
                ];
            }
            option_update($key, $value);
        }

        // بررسی تغییرات در چک‌باکس‌ها
        foreach ($except as $option) {
            $newValue = $request->$option ? 'on' : 'off';
            $oldValue = option($option);

            if ($oldValue != $newValue) {
                $allChanges[$option] = [
                    'old' => $oldValue == 'on' ? 'فعال' : 'غیرفعال',
                    'new' => $newValue == 'on' ? 'فعال' : 'غیرفعال',
                    'title' => $this->getSmsFieldTitle($option)
                ];
            }

            option_update($option, $newValue);
        }

        // ثبت لاگ در صورت وجود تغییرات
        if (!empty($allChanges)) {
            // ساخت متن لاگ خوانا
            $changeTexts = [];
            foreach ($allChanges as $key => $change) {
                $changeTexts[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
            }

            $logMessage = "مدیر {$adminName} تنظیمات پیامک را به‌روزرسانی کرد: " . implode('، ', $changeTexts);

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'update_sms_settings',
                    'changes' => $allChanges,
                    'ip' => $request->ip()
                ])
                ->log($logMessage);
        }

        return response('success');
    }

    /**
     * دریافت عنوان فارسی فیلدهای تنظیمات پیامک
     */
    private function getSmsFieldTitle($key)
    {
        $titles = [
            // تنظیمات اصلی
            'sms_panel_provider' => 'ارائه دهنده پنل پیامک',
            'admin_mobile_number' => 'شماره تلفن مدیر',

            // چک‌باکس‌ها
            'sms_on_user_register' => 'ارسال پیامک هنگام ثبت‌نام کاربر',
            'sms_on_seller_register' => 'ارسال پیامک هنگام ثبت‌نام فروشنده',
            'sms_to_verify_user' => 'تایید با شماره همراه',
            'forgot_password_link' => 'بازیابی رمز با کد تایید',
            'login_with_code' => 'ورود با رمز یکبار مصرف',
            'sms_on_order_paid' => 'ارسال پیامک به مدیر هنگام پرداخت سفارش',
            'sms_on_order_cancelled' => 'ارسال پیامک به مدیر هنگام لغو سفارش',
            'seller_sms_on_order_paid' => 'ارسال پیامک به فروشنده هنگام پرداخت سفارش',
            'seller_sms_on_order_cancelled' => 'ارسال پیامک به فروشنده هنگام لغو سفارش',
            'user_sms_on_order_paid' => 'ارسال پیامک به کاربر هنگام پرداخت سفارش',
            'user_sms_on_order_cancelled' => 'ارسال پیامک به کاربر هنگام لغو سفارش',
            'wallet_increase_sms' => 'ارسال پیامک افزایش موجودی کیف پول',
            'wallet_decrease_sms' => 'ارسال پیامک کاهش موجودی کیف پول',
            'wallet_refund_sms' => 'ارسال پیامک برگشت وجه به کیف پول',

            // تنظیمات ippanel
            'ippanel_api_key' => 'API Key پنل ippanel',
            'ippanel_sender_number' => 'شماره فرستنده ippanel',

            // تنظیمات کاوه نگار
            'kavenegar_api_key' => 'API Key کاوه نگار',
            'kavenegar_sender_number' => 'شماره فرستنده کاوه نگار',

            // تنظیمات ملی پیامک
            'melipayamak_username' => 'نام کاربری ملی پیامک',
            'melipayamak_password' => 'رمز عبور ملی پیامک',
            'melipayamak_sender_number' => 'شماره فرستنده ملی پیامک',

            // تنظیمات ایده پردازان
            'idehpardazan_api_key' => 'API Key ایده پردازان',
            'idehpardazan_sender_number' => 'شماره فرستنده ایده پردازان',

            // تنظیمات فراز اس ام اس
            'farazsms_api_key' => 'API Key فراز اس ام اس',
            'farazsms_sender_number' => 'شماره فرستنده فراز اس ام اس',
        ];

        return $titles[$key] ?? $key;
    }
    public function seller_hero()
    {
        $sellers_heroes=SellerHero::all();
        return view('back.settings.sellers.seller-hero-index',compact('sellers_heroes'));
    }
    public function seller_hero_create()
    {
        return view('back.settings.sellers.seller-hero-create');
    }
    public function seller_hero_store(Request $request)
    {
        $this->authorize('settings.others');
        $data = $this->validate($request, [
            'title'         => 'required',
            'description'  => 'nullable|string',
            'icon'  => 'nullable',
        ]);
        SellerHero::create($data);

        session()->put('toast-success','با موفقیت ایجاد شد.');
        return response("success");
    }
    public function seller_hero_edit(SellerHero $sellerHero)
    {
        return view('back.settings.sellers.seller-hero-edit',compact('sellerHero'));
    }
    public function seller_hero_update(SellerHero $sellerHero, Request $request)
    {
        $this->authorize('settings.others');
        $data = $this->validate($request, [
            'title'         => 'required',
            'description'  => 'nullable|string',
            'icon'  => 'nullable',
        ]);

        $sellerHero->update($data);
        session()->put('toast-success','با موفقیت ویرایش شد.');
        return response("success");
    }
    public function seller_hero_destroy(SellerHero $sellerHero)
    {
        $this->authorize('settings.others');

        $sellerHero->delete();
        return response('success');
    }

    public function seller_commission()
    {
        $sellers_commissions=SellerCommission::all();
        return view('back.settings.sellers.seller-commission-index',compact('sellers_commissions'));
    }
    public function seller_commission_create()
    {
        return view('back.settings.sellers.seller-commission-create');
    }
    public function seller_commission_store(Request $request)
    {
        $this->authorize('settings.others');
        $data = $this->validate($request, [
            'title'         => 'required',
            'description'  => 'nullable|string',
            'icon'  => 'nullable',
        ]);
        SellerCommission::create($data);

        session()->put('toast-success','با موفقیت ایجاد شد.');
        return response("success");
    }
    public function seller_commission_edit(SellerCommission $sellerCommission)
    {
        return view('back.settings.sellers.seller-commission-edit',compact('sellerCommission'));
    }
    public function seller_commission_update(SellerCommission $sellerCommission, Request $request)
    {
        $this->authorize('settings.others');
        $data = $this->validate($request, [
            'title'         => 'required',
            'description'  => 'nullable|string',
            'icon'  => 'nullable',
        ]);

        $sellerCommission->update($data);

        session()->put('toast-success','با موفقیت ویرایش شد.');
        return response("success");
    }
    public function seller_commission_destroy(SellerCommission $sellerCommission)
    {
        $this->authorize('settings.others');

        $sellerCommission->delete();
        return response('success');
    }

    public function seller_question()
    {
        $sellers_questions=SellerQuestion::all();
        return view('back.settings.sellers.seller-question-index',compact('sellers_questions'));
    }
    public function seller_question_create()
    {
        return view('back.settings.sellers.seller-question-create');
    }
    public function seller_question_store(Request $request)
    {
        $data = $this->validate($request, [
            'question'         => 'required',
            'answer'  => 'required|string',
        ]);
        SellerQuestion::create($data);
        session()->put('toast-success','با موفقیت ایجاد شد.');
        return response("success");
    }
    public function seller_question_edit(SellerQuestion $sellerQuestion)
    {
        return view('back.settings.sellers.seller-question-edit',compact('sellerQuestion'));
    }
    public function seller_question_update(SellerQuestion $sellerQuestion, Request $request)
    {
        $data = $this->validate($request, [
            'question'         => 'required',
            'answer'  => 'required|string',
        ]);

        $sellerQuestion->update($data);

        session()->put('toast-success','با موفقیت ویرایش شد.');
        return response("success");
    }
    public function seller_question_destroy(SellerQuestion $sellerQuestion)
    {

        $sellerQuestion->delete();
        return response('success');
    }

    public function seller_econtract()
    {
        $econtract=SellerEcontract::find(1);
        return view('back.settings.sellers.seller-econtract',compact('econtract'));
    }
    public function seller_econtract_store(Request $request)
    {
        $this->authorize('settings.others');

        if ($request->input('content')==null){
            session()->put('toast-error', 'متن اصلی قرار داد نمیتواند خالی باشد.');
            return redirect()->back();
        }

        $econtract=SellerEcontract::find(1);
        $econtract->header=$request->header;
        $econtract->content=$request->input('content');
        $econtract->save();

        session()->put('toast-success','با موفقیت ذخیره شد.');
        return redirect()->route('admin.settings.seller-econtract');
    }

    public function pictures()
    {

    }
}

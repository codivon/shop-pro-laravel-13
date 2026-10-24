<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Madnest\Madzipper\Madzipper;

class ThemeController extends Controller
{
    public function index()
    {
        $this->authorize('themes.index');

        $themes = Storage::disk('themes')->directories();

        foreach ($themes as $key => $theme) {
            $themes[$key] = [
                'name'   => $theme,
                'config' => customConfig(base_path() . '/themes/' . $theme . '/config/general.php')
            ];
        }

        return view('back.themes.index', compact('themes'));
    }

    public function store(Request $request)
    {
        $this->authorize('themes.create');

        $request->validate([
            'file' => 'required|file|mimes:zip'
        ]);

        $uuid = uniqid();
        $path = Storage::disk('public')->path('uploads/tmp/');

        $file = $request->file;
        $name = $uuid . '.' . $file->getClientOriginalExtension();
        $request->file->storeAs('tmp', $name);

        /*
         * امنیتی (Zip-Slip): قبل از استخراج، نام تمام ورودی‌های zip بررسی می‌شود؛
         * ورودی‌های دارای ../ یا مسیر مطلق یا symlink ⇒ رد.
         */
        $zipPath = $path . $name;
        $zipArchive = new \ZipArchive;
        if ($zipArchive->open($zipPath) === true) {
            for ($i = 0; $i < $zipArchive->numFiles; $i++) {
                $entryName = (string) $zipArchive->getNameIndex($i);
                $stat      = $zipArchive->statIndex($i);

                $isUnsafe = $entryName === ''
                    || str_contains($entryName, '..')
                    || str_starts_with($entryName, '/')
                    || preg_match('/^[A-Za-z]:[\\\/]/', $entryName)
                    // ورودی‌های symlink (0xA000) — دایرکتوری/فایل عادی مجاز است
                    || (isset($stat['attr']) && ((($stat['attr'] >> 16) & 0xF000) === 0xA000));

                if ($isUnsafe) {
                    $zipArchive->close();
                    File::delete($zipPath);

                    return response(['errors' => ['theme' => ['فایل zip حاوی ورودی‌های غیرمجاز است.']]], 422);
                }
            }
            $zipArchive->close();
        }

        $zipper = new Madzipper;
        $zipper->make($path . $name)->extractTo($path . $uuid);
        $zipper->close();

        File::delete($path . $name);

        $themes = Storage::disk('themes')->directories();
        $new_theme = Storage::disk('public')->directories('uploads/tmp/' . $uuid);
        $new_theme = substr($new_theme[0], strrpos($new_theme[0], '/') + 1);

        // امنیتی: نام قالب استخراج‌شده از zip باید معتبر باشد (جلوگیری از traversal/تزریق env/آرگومان artisan)
        if (!is_safe_extension_name($new_theme)) {
            File::deleteDirectory($path . $uuid);

            return response(
                [
                    'errors' => [
                        'theme' => ["نام قالب داخل zip معتبر نیست."]
                    ]
                ],
                422
            );
        }

        if (in_array($new_theme, $themes)) {

            File::deleteDirectory($path . $uuid);

            return response(
                [
                    'errors' => [
                        'theme' => ["این قالب قبلا آپلود شده است."]
                    ]
                ],
                422
            );
        }

        File::moveDirectory($path . $uuid . '/' . $new_theme, Storage::disk('themes')->path($new_theme));
        File::deleteDirectory($path . $uuid);

        change_env('CURRENT_THEME', $new_theme);
        Artisan::call('dump-autoload');

        AppServiceProvider::loadTheme();

        if (is_safe_extension_name($new_theme)) {
            Artisan::call('vendor:publish --tag="' . $new_theme . '" --force');
        }
        Artisan::call('shop:link');

        return response()->json(['name' => $new_theme]);
    }

    public function create()
    {
        $this->authorize('themes.create');

        return view('back.themes.create');
    }

    public function update($theme)
    {
        $this->authorize('themes.update');

        // امنیتی: جلوگیری از path-traversal در نام قالب
        if (!is_safe_extension_name((string) $theme)) {
            return response(['errors' => ['theme' => ["نام قالب معتبر نیست."]]], 422);
        }

        if (!Storage::disk('themes')->exists($theme)) {
            return response(
                [
                    'errors' => [
                        'theme' => ["قالب پیدا نشد."]
                    ]
                ],
                422
            );
        }

        $targetPath = base_path("themes/{$theme}/src/resources/assets"); // مسیر اصلی فایل‌ها
        $linkPath = public_path("themes/{$theme}");       // مسیر شورت‌کات

        // امنیتی: مطمئن می‌شویم مسیر حل‌شده واقعاً داخل themes/ است
        if (!str_starts_with(realpath(dirname($targetPath)) ?: '', realpath(base_path('themes')))) {
            return response(['errors' => ['theme' => ["مسیر قالب معتبر نیست."]]], 422);
        }

        // فاز ۶۸ — گارد ساختار قالب (ریشهٔ ۴۰۴ خام صفحه اصلی): پوشهٔ تغییرنام‌یافتهٔ دستی
        // با فضای‌نام قدیمی = provider پیدا نمی‌شود = هیچ روت فرانتی = صفحه اصلی ۴۰۴.
        try {
            \App\Services\ThemeStructureGuard::assertActivatable($theme, base_path('themes'));
        } catch (\Throwable $e) {
            report($e);

            return response(['errors' => ['theme' => [$e->getMessage()]]], 422);
        }

        // فاز ۶۵ — زنجیرهٔ امن symlink→exec→کپی (File::link روی هاست‌های بدون symlink و exec کرش می‌کرد)
        \App\Services\ThemeAssetLinker::linkOrCopy($targetPath, $linkPath);


        change_env('CURRENT_THEME', $theme);

        // فاز ۶۸ — پاک‌سازی کش‌ها تا سوییچ قالب بلافاصله اثر کند
        foreach (['config:clear', 'view:clear', 'route:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response('success');
    }

    public function destroy($theme)
    {
        $this->authorize('themes.delete');

        // امنیتی: جلوگیری از path-traversal در نام قالب
        if (!is_safe_extension_name((string) $theme)) {
            return response(['errors' => ['theme' => ["نام قالب معتبر نیست."]]], 422);
        }

        if (!Storage::disk('themes')->exists($theme)) {
            return response(
                [
                    'errors' => [
                        'theme' => ["قالب پیدا نشد."]
                    ]
                ],
                422
            );
        }

        $config = customConfig(base_path() . '/themes/' . $theme . '/config/general.php');
        Storage::disk('themes')->deleteDirectory($theme);

        // امنیتی: جلوگیری از پاک‌شدن کل دیسک وقتی asset_path خالی/نامعتبر است
        $assetPath = is_array($config) ? ($config['asset_path'] ?? '') : '';
        if (is_string($assetPath) && $assetPath !== '' && !str_contains($assetPath, '..')) {
            Storage::disk('public')->deleteDirectory($assetPath);
        }

        return response('success');
    }

    public function showSettings()
    {
        $this->authorize('themes.settings');

        return view('back.themes.settings');
    }

    public function updateSettings(Request $request)
    {
        $this->authorize('themes.settings');

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // دریافت تنظیمات از کانفیگ
        $settingsFields = config('front.settings.fields', []);

        // فیلدهای فایل
        $fileFields = [];
        foreach ($settingsFields as $setting) {
            if (isset($setting['input-type']) && $setting['input-type'] === 'file') {
                $fileFields[$setting['key']] = $setting['title'] ?? $setting['key'];
            }
        }

        // ========== 1. بررسی تغییرات فایل‌ها ==========
        $fileChanges = [];
        foreach ($fileFields as $key => $title) {
            if ($request->hasFile("settings.{$key}")) {
                $file = $request->file("settings.{$key}");

                // امنیتی: اعتبارسنجی نوع/حجم فایل آپلودی (تصویر) — جلوگیری از آپلود فایل اجرایی
                validator(
                    ['file' => $file],
                    ['file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'extensions:jpg,jpeg,png,webp,gif,svg', 'max:8192']]
                )->validate();

                $oldFile = option($key);

                $imageName = time() . '_' . $key . '.' . strtolower($file->getClientOriginalExtension());
                $file->move(public_path('uploads/themes/'), $imageName);

                if ($oldFile && file_exists(public_path($oldFile))) {
                    unlink(public_path($oldFile));
                }

                $newFile = '/uploads/themes/' . $imageName;
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
        }

        // ========== 2. بررسی تغییرات تنظیمات ==========
        $settings = $request->input('settings', []);
        $optionChanges = [];

        foreach ($settings as $key => $value) {
            // اگر فیلد فایل است، قبلاً بررسی شده
            if (isset($fileFields[$key])) {
                continue;
            }

            $oldValue = option($key);

            if ($oldValue != $value) {
                $optionChanges[$key] = [
                    'old' => $oldValue ?: 'خالی',
                    'new' => $value ?: 'خالی',
                    'title' => $this->getThemeFieldTitle($key, $settingsFields)
                ];
            }

            option_update($key, $value);
        }

        if (!empty($optionChanges)) {
            $allChanges['option_settings'] = $optionChanges;
        }

        // ========== 3. ثبت لاگ ==========
        $changeDetails = [];

        // تغییرات فایل‌ها
        if (isset($allChanges['file_uploads'])) {
            foreach ($allChanges['file_uploads'] as $change) {
                $changeDetails[] = "{$change['title']} آپلود شد";
            }
        }

        // تغییرات تنظیمات - همه به صورت جداگانه
        if (isset($allChanges['option_settings'])) {
            foreach ($allChanges['option_settings'] as $change) {
                $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
            }
        }

        // ثبت لاگ
        if (!empty($changeDetails)) {
            $logMessage = "مدیر {$adminName} تنظیمات قالب را به‌روزرسانی کرد: " . implode('، ', $changeDetails);

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات قالب را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => $request->ip()
                ])
                ->log($logMessage);
        }

        return response('success');
    }

    /**
     * دریافت عنوان فارسی فیلدهای تنظیمات قالب
     */
    private function getThemeFieldTitle($key, $settingsFields)
    {
        foreach ($settingsFields as $setting) {
            if (isset($setting['key']) && $setting['key'] === $key) {
                return $setting['title'] ?? $key;
            }
        }

        // ترجمه فیلدهای رایج
        $titles = [
            // تنظیمات نمایش
            'dt_show_price_change_chart' => 'نمایش نمودار تغییرات قیمت محصول',
            'show_product_share_links' => 'نمایش لینک‌های اشتراک‌گذاری محصول',
            'dt_product_reviews_description' => 'متن توضیحات بخش نظرات محصول',

            // تنظیمات پاپ آپ
            'dt_index_popup_type' => 'نوع پاپ آپ صفحه اصلی',
            'dt_index_popup_image' => 'تصویر پاپ آپ',
            'dt_index_popup_image_mobile' => 'تصویر پاپ آپ (نسخه موبایل)',
            'dt_index_popup_link' => 'لینک تصویر پاپ آپ',
            'dt_index_popup_text' => 'متن پاپ آپ',

            // تنظیمات صفحه تماس
            'dt_show_form_in_contact' => 'نمایش فرم تماس',
            'dt_show_map_in_contact' => 'نمایش نقشه در صفحه تماس',
            'dt_show_contact_top_description' => 'نمایش توضیحات بالای صفحه تماس',
            'dt_show_contact_bottom_description' => 'نمایش توضیحات پایین صفحه تماس',
            'dt_contact_bottom_description' => 'متن توضیحات پایین صفحه تماس',

            // تنظیمات هدر و فوتر
            'dt_show_top_banner' => 'نمایش بنر بالای صفحه',
            'dt_show_bottom_banner' => 'نمایش بنر پایین صفحه',
            'dt_header_style' => 'استایل هدر',
            'dt_footer_style' => 'استایل فوتر',

            // تنظیمات رنگ
            'dt_primary_color' => 'رنگ اصلی قالب',
            'dt_secondary_color' => 'رنگ ثانویه قالب',
            'dt_header_color' => 'رنگ هدر',
            'dt_footer_color' => 'رنگ فوتر',
        ];

        return $titles[$key] ?? str_replace('_', ' ', $key);
    }

    private function getRequestSettings($request)
    {
        $settings = [];

        foreach (config('front.settings.fields') as $setting) {
            switch ($setting['input-type']) {
                case 'input':
                case 'editor':
                case 'inline-editor':
                case 'select': {
                    $settings[$setting['key']] = $request->input('settings.' . $setting['key']);
                    break;
                }

                case 'file': {
                    $file = $request->file('settings.' . $setting['key']);

                    if ($file) {
                        // امنیتی: اعتبارسنجی نوع/حجم فایل آپلودی — جلوگیری از آپلود فایل اجرایی
                        validator(
                            ['file' => $file],
                            ['file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'extensions:jpg,jpeg,png,webp,gif,svg', 'max:8192']]
                        )->validate();

                        $name = uniqid() . '_' . time() . '.' . strtolower($file->getClientOriginalExtension());
                        $path = $file->storeAs('themes', $name);
                        $settings[$setting['key']] = '/uploads/' . $path;
                    } else {
                        $settings[$setting['key']] = option($setting['key']);
                    }

                    break;
                }
            }
        }

        return $settings;
    }
}

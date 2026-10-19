<?php

namespace App\Livewire\Admin\Widgets\Concerns;

use App\Models\Category;
use App\Models\Widget;
use App\Services\WidgetTemplateRegistry;
use Illuminate\Support\Facades\Storage;

/**
 * فاز ۱۱ — منطق مشترک options ابزارک بین WidgetForm / WidgetBuilder / WidgetIndex (مودال‌ها)
 *
 * پورت ۱:۱ از back/widgets (getRequestOptions + saveWidgetOptions) که قبلاً
 * در WidgetForm و WidgetBuilder کپی شده بود؛ الان یک‌جاست.
 */
trait InteractsWithWidgetTemplates
{
    /* ================================================================
     *  مقادیر options رکورد موجود → فرم
     * ================================================================ */

    /**
     * پر کردن optionValues از رکورد (پورت WidgetForm::loadOptionValues).
     * فایل‌ها در optionValues نمی‌آیند (مثل فرم قدیمی که file input فقط فایل جدید را submit می‌کند).
     */
    protected function loadOptionValuesFor(?Widget $widget): void
    {
        $this->optionValues = [];
        $this->fileUploads = [];

        $template = $this->templateForCurrentKey();

        if (! $template || empty($template['options'])) {
            return;
        }

        foreach ($template['options'] as $option) {
            $optionKey = $option['key'];

            if (in_array($option['input-type'], ['post_categories', 'product_categories'], true)) {
                $widgetOption = $widget?->options()->where('key', $optionKey)->first();

                $this->optionValues[$optionKey] = $widgetOption
                    ? $widgetOption->categories()->pluck('categories.id')->map(fn ($id) => (string) $id)->toArray()
                    : [];
            } elseif ($option['input-type'] !== 'file') {
                $this->optionValues[$optionKey] = (string) ($widget?->option($optionKey, $option['default'] ?? '') ?? $option['default'] ?? '');
            }
        }
    }

    /**
     * مقدار پیش‌فرض optionValues برای یک نوع تازه انتخاب‌شده (حالت ایجاد).
     * input → default قالب، select → اولین گزینه (مثل انتخاب پیش‌فرض مرورگر).
     */
    protected function initOptionValuesForNew(string $key): void
    {
        $this->optionValues = [];
        $this->fileUploads = [];

        $template = WidgetTemplateRegistry::findConfigForPage($this->widgetPage(), $key);

        if (! $template || empty($template['options'])) {
            return;
        }

        foreach ($template['options'] as $option) {
            $optionKey = $option['key'];

            if (in_array($option['input-type'], ['post_categories', 'product_categories'], true)) {
                $this->optionValues[$optionKey] = [];
            } elseif ($option['input-type'] === 'select') {
                $this->optionValues[$optionKey] = $option['options'][0]['value'] ?? null;
            } elseif ($option['input-type'] !== 'file') {
                $this->optionValues[$optionKey] = $option['default'] ?? '';
            }
        }
    }

    /** template متناظر با کلید جاری (هر کامپوننت پیاده‌سازی می‌کند) */
    abstract protected function templateForCurrentKey(): ?array;

    /** صفحه‌ی ابزارک ('home' یا 'posts') — برای دسته‌بندی‌ها و registry */
    abstract public function widgetPage(): string;

    /* ================================================================
     *  فرم → آرایه options (پورت getRequestOptions قدیمی)
     * ================================================================ */

    protected function getRequestOptions(array $template, Widget $widget): array
    {
        $options = [];

        foreach ($template['options'] as $index => $option) {
            $optionKey = $option['key'];

            switch ($option['input-type']) {
                case 'input': {
                    $options[$index]['input-type'] = $option['input-type'];
                    $options[$index]['key'] = $optionKey;
                    $options[$index]['value'] = $this->optionValues[$optionKey] ?? null;
                    break;
                }

                case 'file': {
                    if (! file_exists(public_path('/uploads/widgets'))) {
                        Storage::disk('public')->makeDirectory('/uploads/widgets');
                    }

                    $file = $this->fileUploads[$optionKey] ?? null;

                    if ($file) {
                        $name = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('widgets', $name);
                        $options[$index]['value'] = '/uploads/' . $path;
                    } else {
                        // بدون آپلود جدید → نگه‌داشتن مقدار قبلی
                        $options[$index]['value'] = $widget->option($optionKey);
                    }

                    $options[$index]['input-type'] = $option['input-type'];
                    $options[$index]['key'] = $optionKey;

                    break;
                }

                case 'select': {
                    $options[$index]['input-type'] = $option['input-type'];
                    $options[$index]['key'] = $optionKey;
                    $options[$index]['value'] = $this->optionValues[$optionKey] ?? null;
                    break;
                }

                case 'post_categories':
                case 'product_categories': {
                    $options[$index]['input-type'] = $option['input-type'];
                    $options[$index]['key'] = $optionKey;
                    $options[$index]['value'] = $this->optionValues[$optionKey] ?? null;
                    break;
                }
            }
        }

        return $options;
    }

    /* ================================================================
     *  ذخیره options (پورت saveWidgetOptions قدیمی)
     *  categories → value='on'/'off' + sync پیوت
     * ================================================================ */

    protected function saveWidgetOptions(Widget $widget, array $options): void
    {
        foreach ($options as $option) {
            switch ($option['input-type']) {
                case 'post_categories':
                case 'product_categories': {
                    $value = is_array($option['value']) && ! empty($option['value']) ? 'on' : 'off';

                    $insertedOption = $widget->options()->create([
                        'key'   => $option['key'],
                        'value' => $value,
                    ]);

                    $insertedOption->categories()->sync(is_array($option['value']) ? $option['value'] : []);

                    break;
                }

                default: {
                    $widget->options()->create([
                        'key'   => $option['key'],
                        'value' => $option['value'],
                    ]);
                }
            }
        }
    }

    /* ================================================================
     *  اعتبارسنجی options (پورت Validator::make($request->options, rules))
     *  + شبیه‌سازی submit فرم HTML برای فیلدهای خالی
     * ================================================================ */

    /**
     * آماده‌سازی + اعتبارسنجی optionValues با rules قالب.
     * خروجی: آرایه خطاها به شکل ['optionValues.KEY' => message] (خالی = معتبر).
     */
    protected function validateOptionValues(array $template): array
    {
        // شبیه‌سازی submit فرم قدیمی: همه optionها مثل فرم HTML مقدار می‌گیرند
        foreach (($template['options'] ?? []) as $option) {
            $optionKey = $option['key'];

            if ($option['input-type'] === 'select' && (($this->optionValues[$optionKey] ?? '') === '')) {
                $this->optionValues[$optionKey] = $option['options'][0]['value'] ?? null;
            } elseif ($option['input-type'] === 'input' && ! array_key_exists($optionKey, $this->optionValues)) {
                $this->optionValues[$optionKey] = $option['default'] ?? null;
            }
        }

        $validator = \Illuminate\Support\Facades\Validator::make($this->optionValues, $template['rules'] ?? []);

        if ($validator->fails()) {
            return collect($validator->errors()->toArray())
                ->mapWithKeys(fn ($messages, $optionKey) => ['optionValues.' . $optionKey => $messages[0]])
                ->all();
        }

        return [];
    }

    /* ================================================================
     *  لاگ تغییرات (پورت ساختار activity log قدیمی)
     * ================================================================ */

    protected function formatOptionValue($optionKey, $value)
    {
        if (is_null($value)) {
            return 'خالی';
        }

        if (is_array($value)) {
            if (empty($value)) {
                return 'هیچ موردی انتخاب نشده';
            }

            if ($optionKey === 'post_categories' || $optionKey === 'product_categories') {
                try {
                    $categoryNames = [];
                    $categories = Category::query()->whereIn('id', $value)->get();

                    foreach ($categories as $category) {
                        if (isset($category->title) && $category->title) {
                            $categoryNames[] = $category->title;
                        } elseif (isset($category->name) && $category->name) {
                            $categoryNames[] = $category->name;
                        } else {
                            $categoryNames[] = "#{$category->id}";
                        }
                    }

                    return ! empty($categoryNames) ? implode('، ', $categoryNames) : implode('، ', $value);
                } catch (\Exception) {
                    return implode('، ', $value);
                }
            }

            return implode('، ', $value);
        }

        if (is_bool($value)) {
            return $value ? 'بله' : 'خیر';
        }

        return $value ?: 'خالی';
    }

    protected function getOptionFieldTitle(string $optionKey): string
    {
        $titles = [
            'title'            => 'عنوان',
            'link'             => 'لینک',
            'link_title'       => 'عنوان لینک',
            'limit'            => 'تعداد نمایش',
            'order_by'         => 'ترتیب نمایش',
            'sort_by'          => 'مرتب‌سازی بر اساس',
            'show_more'        => 'نمایش دکمه بیشتر',
            'show_pagination'  => 'نمایش صفحه‌بندی',
            'ordering'         => 'ترتیب نمایش',
            'background_color' => 'رنگ پس‌زمینه',
            'text_color'       => 'رنگ متن',
            'button_text'      => 'متن دکمه',
            'button_link'      => 'لینک دکمه',
            'image'            => 'تصویر',
            'image_mobile'     => 'تصویر موبایل',
            'banner_link'      => 'لینک بنر',
            'alt_text'         => 'متن جایگزین تصویر',
            'slides'           => 'اسلایدها',
            'autoplay'         => 'پخش خودکار',
            'autoplay_speed'   => 'سرعت پخش خودکار',
            'dots'             => 'نقاط ناوبری',
            'arrows'           => 'فلش‌های ناوبری',
            'post_categories'    => 'دسته‌بندی‌های مقالات',
            'product_categories' => 'دسته‌بندی‌های محصولات',
            'product_ids'      => 'محصولات خاص',
            'show_price'       => 'نمایش قیمت',
            'show_rating'      => 'نمایش امتیاز',
            'show_quick_view'  => 'نمایش دکمه مشاهده سریع',
            'post_ids'         => 'مقالات خاص',
            'show_excerpt'     => 'نمایش خلاصه',
            'show_author'      => 'نمایش نویسنده',
            'show_date'        => 'نمایش تاریخ',
            'banner_position'  => 'موقعیت بنر',
            'banner_width'     => 'عرض بنر',
            'banner_height'    => 'ارتفاع بنر',
            'number'           => 'تعداد استوری',
            'group'            => 'گروه',
        ];

        return $titles[$optionKey] ?? str_replace('_', ' ', $optionKey);
    }

    /** عکس فوری options قبل از بازنویسی (برای لاگ) */
    protected function snapshotWidgetOptions(Widget $widget): array
    {
        $old = [];

        foreach ($widget->options as $option) {
            $old[$option->key] = in_array($option->key, ['post_categories', 'product_categories'], true)
                ? $option->categories->pluck('id')->toArray()
                : $option->value;
        }

        return $old;
    }

    /** مقایسه options قبل/بعد و ساخت آرایه تغییرات (برای لاگ) */
    protected function widgetOptionChanges(Widget $widget, array $oldOptions): array
    {
        $widget->refresh();

        $newOptions = [];

        foreach ($widget->options as $option) {
            $newOptions[$option->key] = in_array($option->key, ['post_categories', 'product_categories'], true)
                ? $option->categories->pluck('id')->toArray()
                : $option->value;
        }

        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($oldOptions), array_keys($newOptions)));

        foreach ($allKeys as $optionKey) {
            $oldValue = $oldOptions[$optionKey] ?? null;
            $newValue = $newOptions[$optionKey] ?? null;

            if ($oldValue != $newValue) {
                $changes[$optionKey] = [
                    'old'   => $this->formatOptionValue($optionKey, $oldValue),
                    'new'   => $this->formatOptionValue($optionKey, $newValue),
                    'title' => $this->getOptionFieldTitle($optionKey),
                ];
            }
        }

        return $changes;
    }

    /** ثبت لاگ یکسان با ساختار قدیمی */
    protected function logWidgetActivity(Widget $widget, array $allChanges, string $actionLabel, string $pageLabel): void
    {
        if (empty($allChanges)) {
            return;
        }

        $adminName = auth('adminPanel')->user()?->full_name ?? auth('adminPanel')->user()?->name ?? 'مدیر';
        $changeDetails = [];

        foreach ($allChanges as $change) {
            if (is_array($change) && isset($change['title'])) {
                $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
            }
        }

        $logMessage = "مدیر {$adminName} ابزارک {$pageLabel} «{$widget->title}» را ویرایش کرد: " . implode('، ', $changeDetails);

        activity()
            ->performedOn($widget)
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action'       => $actionLabel,
                'widget_title' => $widget->title,
                'widget_id'    => $widget->id,
                'page'         => $this->widgetPage(),
                'changes'      => $allChanges,
                'ip'           => request()->ip(),
            ])
            ->log($logMessage);
    }
}

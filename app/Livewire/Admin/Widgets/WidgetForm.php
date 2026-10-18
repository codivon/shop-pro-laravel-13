<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\Category;
use App\Models\Widget;
use App\Services\WidgetTemplateRegistry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('فرم ابزارک')]
class WidgetForm extends Component
{
    use WithFileUploads;

    /** ابزارک در حال ویرایش (null = ایجاد) */
    public ?Widget $widget = null;

    public bool $isEditing = false;

    /** صفحه‌ی ابزارک: 'home' (کلاس PostWidgetForm مقدار posts ست می‌کند) */
    public string $page = 'home';

    /* ---------------- فیلدهای اصلی ---------------- */

    public string $title = '';

    public string $key = '';

    public bool $is_active = true;

    /** مقادیر options به‌صورت [option-key => value] — پورت request->options قدیمی */
    public array $optionValues = [];

    /** فایل‌های آپلودی جدید به‌صورت [option-key => TemporaryUploadedFile] */
    public array $fileUploads = [];

    public function mount(?Widget $widget = null, string $page = 'home'): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $this->page = $page;

        /* انحراف مستند: کنترلر قدیمی در سازنده با registry خالی 404 می‌داد؛
         * پنل جدید حالت خالی دوستانه رندر می‌کند (تصمیم تأییدشده‌ی کارفرما). */

        if ($widget && $widget->exists) {
            $this->isEditing = true;
            $this->widget = $widget;
            $this->page = $widget->page ?: $page;

            $this->title = (string) $widget->title;
            $this->key = (string) $widget->key;
            $this->is_active = (bool) $widget->is_active;

            $this->loadOptionValues();
        }
    }

    /* ---------------- templates (پورت mergedForPage / findConfigForPage) ---------------- */

    #[Computed]
    public function templates(): array
    {
        return WidgetTemplateRegistry::mergedForPage($this->page);
    }

    /** template انتخاب‌شده‌ی فعلی */
    #[Computed]
    public function template(): ?array
    {
        if ($this->key === '') {
            return null;
        }

        return WidgetTemplateRegistry::findConfigForPage($this->page, $this->key);
    }

    public function isRegistryEmpty(): bool
    {
        return empty(WidgetTemplateRegistry::allKeysForPage($this->page));
    }

    /** پر کردن مقادیر اولیه‌ی options از رکورد (پورت رفتار back/widgets/template.blade.php) */
    protected function loadOptionValues(): void
    {
        $this->optionValues = [];
        $this->fileUploads = [];

        $template = $this->template;

        if (! $template || empty($template['options'])) {
            return;
        }

        foreach ($template['options'] as $option) {
            $optionKey = $option['key'];

            if (in_array($option['input-type'], ['post_categories', 'product_categories'], true)) {
                $widgetOption = $this->widget->options()->where('key', $optionKey)->first();

                $this->optionValues[$optionKey] = $widgetOption
                    ? $widgetOption->categories()->pluck('categories.id')->map(fn ($id) => (string) $id)->toArray()
                    : [];
            } elseif ($option['input-type'] !== 'file') {
                // فایل‌ها در optionValues نمی‌آیند (مثل فرم قدیمی که file input فقط فایل جدید را submit می‌کند)
                $this->optionValues[$optionKey] = (string) $this->widget->option($optionKey, $option['default'] ?? '');
            }
        }
    }

    /** مقدار فعلی یک option (برای نمایش در فرم/پیش‌نمایش فایل) */
    public function optionValue(string $optionKey): ?string
    {
        if (array_key_exists($optionKey, $this->optionValues) && $this->optionValues[$optionKey] !== '') {
            return (string) $this->optionValues[$optionKey];
        }

        return $this->widget ? $this->widget->option($optionKey) : null;
    }

    /* ---------------- دسته‌بندی‌ها (پورت template() قدیمی) ---------------- */

    #[Computed]
    public function productCategories()
    {
        return Category::query()->detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
    }

    #[Computed]
    public function postCategories()
    {
        return Category::query()->detectLang()->where('type', 'postcat')->orderBy('ordering')->get();
    }

    /* ---------------- ذخیره (پورت ۱:۱ store/update + saveWidgetOptions قدیمی) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        // قدیمی: "required|in:$keys" — کلیدهای مجاز صفحه
        $allowedKeys = WidgetTemplateRegistry::allKeysForPage($this->page);

        $this->validate([
            'title'     => 'required|string|max:255',
            'key'       => 'required|string|in:' . implode(',', $allowedKeys),
            'is_active' => 'boolean',
        ], [], [
            'title' => 'عنوان ابزارک',
            'key'   => 'نوع ابزارک',
        ]);

        $template = WidgetTemplateRegistry::findConfigForPage($this->page, $this->key);

        if (! $template) {
            throw ValidationException::withMessages([
                'key' => 'ویجت یافت نشد.',
            ]);
        }

        // شبیه‌سازی submit فرم قدیمی: همه‌ی optionها مثل فرم HTML مقدار می‌گیرند
        // (input → default قالب، select → اولین گزینه مثل انتخاب پیش‌فرض مرورگر)
        foreach (($template['options'] ?? []) as $option) {
            $optionKey = $option['key'];

            if ($option['input-type'] === 'select' && (($this->optionValues[$optionKey] ?? '') === '')) {
                $this->optionValues[$optionKey] = $option['options'][0]['value'] ?? null;
            } elseif ($option['input-type'] === 'input' && ! array_key_exists($optionKey, $this->optionValues)) {
                $this->optionValues[$optionKey] = $option['default'] ?? null;
            }
        }

        // پورت Validator::make($request->options, $template['rules'])->validate()
        $optionValidator = Validator::make($this->optionValues, $template['rules'] ?? []);

        if ($optionValidator->fails()) {
            throw ValidationException::withMessages(
                collect($optionValidator->errors()->toArray())
                    ->mapWithKeys(fn ($messages, $optionKey) => ['optionValues.' . $optionKey => $messages[0]])
                    ->all()
            );
        }

        if ($this->isEditing) {
            $this->updateWidget($template);
        } else {
            $this->storeWidget($template);
        }
    }

    protected function storeWidget(array $template): void
    {
        // پورت store قدیمی — برای home «page» ذخیره نمی‌شد (پیش‌فرض home در DB)
        $widget = Widget::create($this->creationPayload());

        $options = $this->getRequestOptions($template, $widget);

        $this->saveWidgetOptions($widget, $options);

        session()->flash('np-toast', ['type' => 'success', 'message' => 'ابزارک با موفقیت ایجاد شد.']);

        $this->redirect($this->indexRoute(), navigate: true);
    }

    /** پورت store — برای home بدون page؛ PostWidgetForm رونویسی می‌کند */
    protected function creationPayload(): array
    {
        return [
            'title'     => $this->title,
            'key'       => $this->key,
            'is_active' => $this->is_active,
            'theme'     => current_theme_name(),
            'lang'      => app()->getLocale(),
        ];
    }

    protected function updateWidget(array $template): void
    {
        $widget = $this->widget;

        $allChanges = [];
        $adminName = auth('adminPanel')->user()?->full_name ?? auth('adminPanel')->user()?->name ?? 'مدیر';
        $widgetTitle = $widget->title;

        /* ========== 1. بررسی تغییرات فیلدهای اصلی ========== */
        $mainFields = ['title', 'key', 'is_active'];
        $mainChanges = [];

        foreach ($mainFields as $field) {
            $oldValue = $widget->$field;
            $newValue = $this->$field;

            if ($oldValue != $newValue) {
                $fieldTitles = [
                    'title'     => 'عنوان ابزارک',
                    'key'       => 'نوع ابزارک',
                    'is_active' => 'وضعیت',
                ];

                if ($field === 'is_active') {
                    $oldValue = $oldValue ? 'فعال' : 'غیرفعال';
                    $newValue = $newValue ? 'فعال' : 'غیرفعال';
                }

                if ($field === 'key') {
                    $allWidgets = WidgetTemplateRegistry::mergedForPage($this->page);
                    $oldValue = $allWidgets[$oldValue]['title'] ?? $oldValue;
                    $newValue = $allWidgets[$newValue]['title'] ?? $newValue;
                }

                $mainChanges[$field] = [
                    'old'   => $oldValue ?: 'خالی',
                    'new'   => $newValue ?: 'خالی',
                    'title' => $fieldTitles[$field] ?? $field,
                ];
            }
        }

        if (! empty($mainChanges)) {
            $allChanges['main_fields'] = $mainChanges;
        }

        /* ========== 2. دریافت options قدیمی ========== */
        $oldOptions = [];
        foreach ($widget->options as $option) {
            if ($option->key === 'post_categories' || $option->key === 'product_categories') {
                $oldOptions[$option->key] = $option->categories->pluck('id')->toArray();
            } else {
                $oldOptions[$option->key] = $option->value;
            }
        }

        /* ========== 3. به‌روزرسانی ویجت ========== */
        $widget->update($this->updatePayload());

        /* ========== 4. ذخیره options جدید ========== */
        $options = $this->getRequestOptions($template, $widget);
        $widget->options()->delete();
        $this->saveWidgetOptions($widget, $options);

        /* ========== 5. دریافت options جدید ========== */
        $widget->refresh();
        $newOptions = [];
        foreach ($widget->options as $option) {
            if ($option->key === 'post_categories' || $option->key === 'product_categories') {
                $newOptions[$option->key] = $option->categories->pluck('id')->toArray();
            } else {
                $newOptions[$option->key] = $option->value;
            }
        }

        /* ========== 6. بررسی تغییرات options ========== */
        $optionChanges = [];
        $allKeys = array_unique(array_merge(array_keys($oldOptions), array_keys($newOptions)));

        foreach ($allKeys as $optionKey) {
            $oldValue = $oldOptions[$optionKey] ?? null;
            $newValue = $newOptions[$optionKey] ?? null;

            if ($oldValue != $newValue) {
                $optionChanges[$optionKey] = [
                    'old'   => $this->formatOptionValue($optionKey, $oldValue),
                    'new'   => $this->formatOptionValue($optionKey, $newValue),
                    'title' => $this->getOptionFieldTitle($optionKey),
                ];
            }
        }

        if (! empty($optionChanges)) {
            $allChanges['options'] = $optionChanges;
        }

        /* ========== 7. ثبت لاگ (پورت ساختار دقیق activity log) ========== */
        if (! empty($allChanges)) {
            $changeDetails = [];

            if (isset($allChanges['main_fields'])) {
                foreach ($allChanges['main_fields'] as $change) {
                    $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
                }
            }

            if (isset($allChanges['options'])) {
                foreach ($allChanges['options'] as $change) {
                    $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
                }
            }

            $logMessage = "مدیر {$adminName} {$this->logSubject()} «{$widgetTitle}» را ویرایش کرد: " . implode('، ', $changeDetails);

            activity()
                ->performedOn($widget)
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action'       => $this->logAction(),
                    'widget_title' => $widgetTitle,
                    'widget_id'    => $widget->id,
                    'page'         => $this->page,
                    'changes'      => $allChanges,
                    'ip'           => request()->ip(),
                ])
                ->log($logMessage);
        }

        session()->flash('np-toast', ['type' => 'success', 'message' => 'ابزارک با موفقیت ویرایش شد.']);

        $this->redirect($this->indexRoute(), navigate: true);
    }

    /** پورت update — برای home page تغییر نمی‌کند؛ PostWidgetForm رونویسی می‌کند */
    protected function updatePayload(): array
    {
        return [
            'title'     => $this->title,
            'key'       => $this->key,
            'is_active' => $this->is_active,
        ];
    }

    /* ---------------- پورت getRequestOptions قدیمی ---------------- */

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

    /* ---------------- پورت saveWidgetOptions قدیمی ---------------- */

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

    /* ---------------- پورت formatOptionValue + عنوان‌های فارسی (home + posts) ---------------- */

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

    /** نگاشت فارسی option ها — ادغام نگاشت‌های getHomeOptionFieldTitle و getOptionFieldTitle قدیمی */
    protected function getOptionFieldTitle(string $optionKey): string
    {
        $titles = [
            // تنظیمات عمومی
            'title'            => 'عنوان',
            'link'             => 'لینک',
            'link_title'       => 'عنوان لینک',
            'limit'            => 'تعداد نمایش',
            'order_by'         => 'ترتیب نمایش',
            'sort_by'          => 'مرتب‌سازی بر اساس',
            'show_more'        => 'نمایش دکمه بیشتر',
            'show_pagination'  => 'نمایش صفحه‌بندی',
            'ordering'         => 'ترتیب نمایش',

            // تنظیمات ظاهری
            'background_color' => 'رنگ پس‌زمینه',
            'text_color'       => 'رنگ متن',
            'button_text'      => 'متن دکمه',
            'button_link'      => 'لینک دکمه',

            // تنظیمات تصاویر
            'image'            => 'تصویر',
            'image_mobile'     => 'تصویر موبایل',
            'banner_link'      => 'لینک بنر',
            'alt_text'         => 'متن جایگزین تصویر',

            // تنظیمات اسلایدر
            'slides'           => 'اسلایدها',
            'autoplay'         => 'پخش خودکار',
            'autoplay_speed'   => 'سرعت پخش خودکار',
            'dots'             => 'نقاط ناوبری',
            'arrows'           => 'فلش‌های ناوبری',

            // تنظیمات دسته‌بندی
            'post_categories'     => 'دسته‌بندی‌های مقالات',
            'product_categories'  => 'دسته‌بندی‌های محصولات',

            // تنظیمات محصولات
            'product_ids'      => 'محصولات خاص',
            'show_price'       => 'نمایش قیمت',
            'show_rating'      => 'نمایش امتیاز',
            'show_quick_view'  => 'نمایش دکمه مشاهده سریع',

            // تنظیمات مقالات
            'post_ids'         => 'مقالات خاص',
            'show_excerpt'     => 'نمایش خلاصه',
            'show_author'      => 'نمایش نویسنده',
            'show_date'        => 'نمایش تاریخ',

            // تنظیمات بنر
            'banner_position'  => 'موقعیت بنر',
            'banner_width'     => 'عرض بنر',
            'banner_height'    => 'ارتفاع بنر',

            // تنظیمات استوری (ماژول)
            'number'           => 'تعداد استوری',
        ];

        return $titles[$optionKey] ?? str_replace('_', ' ', $optionKey);
    }

    /* ---------------- رشته‌های لاگ/مسیر (PostWidgetForm رونویسی می‌کند) ---------------- */

    protected function logSubject(): string
    {
        return 'ابزارک صفحه اصلی';
    }

    protected function logAction(): string
    {
        return 'ابزارک  صفحه اصلی را ویرایش کرد'; // فاصله‌ی دوتایی عیناً مثل قدیمی
    }

    public function indexRoute(): string
    {
        return route('np.widgets.index');
    }

    /* ---------------- ابزار ---------------- */

    public function render()
    {
        return view('admin-panel::livewire.admin.widgets.widget-form')
            ->layout('admin-panel::layout');
    }
}

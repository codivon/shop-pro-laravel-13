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

/**
 * ویجت‌ساز تمام‌صفحه (فاز ۹-ه) — پنل ابزارک‌ها + پیش‌نمایش زنده iframe
 *
 * پورت منطق داده از:
 *   - WidgetController::store / update  → createFromTemplate / saveOptions (با getRequestOptions + saveWidgetOptions)
 *   - WidgetController::sort            → reorder
 *   - WidgetController::destroy         → delete
 *   - WidgetIndex::toggleActive         → toggleActive
 */
#[Title('ویجت‌ساز صفحه اصلی')]
class WidgetBuilder extends Component
{
    use WithFileUploads;

    /** صفحه‌ی ابزارک‌ها (فعلاً فقط home مطابق اسپک) */
    public string $page = 'home';

    /** id ابزارکی که فرم تنظیماتش باز است (فقط یکی) — null = همه بسته */
    public ?int $editingId = null;

    /** id ابزارک در مودال تایید حذف */
    public ?int $confirmingDelete = null;

    /** مودال «افزودن ابزارک» (گرید کارت‌های template) */
    public bool $showAddModal = false;

    /** مقادیر options ابزارک باز — پورت request->options قدیمی */
    public array $optionValues = [];

    /** فایل‌های آپلودی جدید — [option-key => TemporaryUploadedFile] */
    public array $fileUploads = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);
    }

    /* ================================================================
     *  لیست + templates
     * ================================================================ */

    /** پورت index قدیمی — فقط theme/page جاری + detectLang */
    #[Computed]
    public function widgets()
    {
        return Widget::query()
            ->detectLang()
            ->where('theme', get_current_theme()['name'] ?? '')
            ->where('page', $this->page)
            ->orderBy('ordering')
            ->get();
    }

    /** پورت mergedForPage — configهای core قالب + ویجت‌های ماژول‌ها */
    #[Computed]
    public function templates(): array
    {
        return WidgetTemplateRegistry::mergedForPage($this->page);
    }

    /** ابزارکی که فرم تنظیماتش باز است */
    #[Computed]
    public function editingWidget(): ?Widget
    {
        if (! $this->editingId) {
            return null;
        }

        return $this->widgets->firstWhere('id', $this->editingId);
    }

    /** template ابزارک باز */
    #[Computed]
    public function editingTemplate(): ?array
    {
        $widget = $this->editingWidget;

        if (! $widget) {
            return null;
        }

        return WidgetTemplateRegistry::findConfigForPage($this->page, (string) $widget->key);
    }

    /** مقدار فعلی یک option برای نمایش در فرم (پورت WidgetForm::optionValue) */
    public function optionValue(string $optionKey): ?string
    {
        $widget = $this->editingWidget;

        if (array_key_exists($optionKey, $this->optionValues) && $this->optionValues[$optionKey] !== '') {
            return (string) $this->optionValues[$optionKey];
        }

        return $widget ? $widget->option($optionKey) : null;
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

    /* ================================================================
     *  فرم تنظیمات (باز/بسته)
     * ================================================================ */

    /** باز کردن فرم تنظیمات یک ابزارک (فقط یکی باز — کلیک روی آیتم باز، همان باز می‌ماند) */
    public function openEditor(int $widgetId): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $widget = $this->widgets->firstWhere('id', $widgetId);

        if (! $widget) {
            return;
        }

        $this->editingId = $widgetId;
        $this->reset('fileUploads');
        $this->loadOptionValues($widget);

        // هایلایت + اسکرول به ابزارک داخل پیش‌نمایش (پس از load در JS ارسال می‌شود)
        $this->dispatch('ap-highlight-widget', id: $widgetId);
    }

    public function closeEditor(): void
    {
        $this->editingId = null;
        $this->reset('optionValues', 'fileUploads');
    }

    /** پر کردن مقادیر اولیه‌ی options از رکورد (پورت WidgetForm::loadOptionValues) */
    protected function loadOptionValues(Widget $widget): void
    {
        $this->optionValues = [];

        $template = WidgetTemplateRegistry::findConfigForPage($this->page, (string) $widget->key);

        if (! $template || empty($template['options'])) {
            return;
        }

        foreach ($template['options'] as $option) {
            $optionKey = $option['key'];

            if (in_array($option['input-type'], ['post_categories', 'product_categories'], true)) {
                $widgetOption = $widget->options()->where('key', $optionKey)->first();

                $this->optionValues[$optionKey] = $widgetOption
                    ? $widgetOption->categories()->pluck('categories.id')->map(fn ($id) => (string) $id)->toArray()
                    : [];
            } elseif ($option['input-type'] !== 'file') {
                // فایل‌ها در optionValues نمی‌آیند (مثل فرم قدیمی)
                $this->optionValues[$optionKey] = (string) $widget->option($optionKey, $option['default'] ?? '');
            }
        }
    }

    /* ================================================================
     *  افزودن (پورت store قدیمی با رفتار ویجت‌ساز)
     * ================================================================ */

    public function createFromTemplate(string $key): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        // پورت validate "required|in:$keys" — کلیدهای مجاز صفحه
        if (! in_array($key, WidgetTemplateRegistry::allKeysForPage($this->page), true)) {
            $this->dispatch('toast', message: 'ویجت یافت نشد.', type: 'error');

            return;
        }

        $template = WidgetTemplateRegistry::findConfigForPage($this->page, $key);

        if (! $template) {
            $this->dispatch('toast', message: 'ویجت یافت نشد.', type: 'error');

            return;
        }

        $maxOrdering = (int) $this->widgets->max('ordering');

        $widget = Widget::create([
            'title'     => $template['title'] ?? $key,
            'key'       => $key,
            'is_active' => false, // تا تکمیل تنظیمات غیرفعال (مطابق اسپک)
            'theme'     => current_theme_name(),
            'lang'      => app()->getLocale(),
            'ordering'  => $maxOrdering + 1,
        ]);

        $this->showAddModal = false;

        // انتخاب و باز شدن فرم تنظیماتش
        $this->editingId = $widget->id;
        $this->reset('fileUploads');
        $this->loadOptionValues($widget);
        $this->dispatch('ap-highlight-widget', id: $widget->id);

        unset($this->widgets);

        $this->dispatch('toast', message: 'ابزارک «' . ($template['title'] ?? $key) . '» اضافه شد؛ تنظیمات را تکمیل و ذخیره کنید.', type: 'success');
        $this->dispatch('ap-refresh-preview');
    }

    /* ================================================================
     *  ذخیره تنظیمات (پورت update قدیمی — حذف و بازساخت options)
     * ================================================================ */

    public function saveOptions(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $widget = $this->editingWidget;

        if (! $widget) {
            $this->dispatch('toast', message: 'ابتدا یک ابزارک را انتخاب کنید.', type: 'error');

            return;
        }

        $template = WidgetTemplateRegistry::findConfigForPage($this->page, (string) $widget->key);

        if (! $template) {
            throw ValidationException::withMessages([
                'optionValues' => 'ویجت یافت نشد.',
            ]);
        }

        /* شبیه‌سازی submit فرم قدیمی: همه‌ی optionها مثل فرم HTML مقدار می‌گیرند
         * (input → default قالب، select → اولین گزینه مثل انتخاب پیش‌فرض مرورگر) */
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

        /* ========== پورت update قدیمی (بخش options + لاگ) ========== */

        $allChanges = [];
        $adminName = auth('adminPanel')->user()?->full_name ?? auth('adminPanel')->user()?->name ?? 'مدیر';
        $widgetTitle = $widget->title;

        // 1. دریافت options قدیمی
        $oldOptions = [];
        foreach ($widget->options as $option) {
            if ($option->key === 'post_categories' || $option->key === 'product_categories') {
                $oldOptions[$option->key] = $option->categories->pluck('id')->toArray();
            } else {
                $oldOptions[$option->key] = $option->value;
            }
        }

        // 2. ذخیره options جدید (حذف و بازساخت — رفتار قدیمی)
        $options = $this->getRequestOptions($template, $widget);
        $widget->options()->delete();
        $this->saveWidgetOptions($widget, $options);

        // 3. دریافت options جدید
        $widget->refresh();
        $newOptions = [];
        foreach ($widget->options as $option) {
            if ($option->key === 'post_categories' || $option->key === 'product_categories') {
                $newOptions[$option->key] = $option->categories->pluck('id')->toArray();
            } else {
                $newOptions[$option->key] = $option->value;
            }
        }

        // 4. بررسی تغییرات options
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

        // 5. ثبت لاگ (پورت ساختار دقیق activity log قدیمی)
        if (! empty($allChanges)) {
            $changeDetails = [];

            if (isset($allChanges['options'])) {
                foreach ($allChanges['options'] as $change) {
                    $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
                }
            }

            $logMessage = "مدیر {$adminName} ابزارک صفحه اصلی «{$widgetTitle}» را ویرایش کرد: " . implode('، ', $changeDetails);

            activity()
                ->performedOn($widget)
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action'       => 'ابزارک  صفحه اصلی را ویرایش کرد', // فاصله‌ی دوتایی عیناً مثل قدیمی
                    'widget_title' => $widgetTitle,
                    'widget_id'    => $widget->id,
                    'page'         => $this->page,
                    'changes'      => $allChanges,
                    'ip'           => request()->ip(),
                ])
                ->log($logMessage);
        }

        // 6. بازخوانی فرم با مقادیر تازه + پاک‌سازی فایل‌ها
        $this->reset('fileUploads');
        $this->loadOptionValues($widget);
        unset($this->widgets);

        $this->dispatch('toast', message: 'ذخیره شد.', type: 'success');
        $this->dispatch('ap-refresh-preview');
    }

    /* ================================================================
     *  وضعیت + ترتیب + حذف
     * ================================================================ */

    /** toggle سریع فعال/غیرفعال (پورت WidgetIndex::toggleActive) */
    public function toggleActive(int $widgetId): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $widget = Widget::query()->where('id', $widgetId)->where('page', $this->page)->first();

        if ($widget) {
            $widget->update(['is_active' => ! $widget->is_active]);

            unset($this->widgets);

            $this->dispatch('toast', message: $widget->is_active ? 'ابزارک فعال شد.' : 'ابزارک غیرفعال شد.', type: 'success');
            $this->dispatch('ap-refresh-preview');
        }
    }

    /**
     * پورت WidgetController::sort — آرایه‌ی id ها به ترتیب جدید → ordering = 1..n
     * امنیت: فقط ابزارک‌های همان theme/page جاری آپدیت می‌شوند.
     */
    public function reorder(array $widgetIds): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        if (! is_array($widgetIds) || $widgetIds === []) {
            return;
        }

        $theme = get_current_theme()['name'] ?? '';
        $i = 1;

        foreach ($widgetIds as $widgetId) {
            Widget::query()
                ->where('id', (int) $widgetId)
                ->where('theme', $theme)
                ->where('page', $this->page)
                ->update(['ordering' => $i++]);
        }

        unset($this->widgets);

        $this->dispatch('toast', message: 'ترتیب ذخیره شد.', type: 'success');
        $this->dispatch('ap-refresh-preview');
    }

    /* ---------------- حذف (پورت destroy قدیمی — options با FK cascade) ---------------- */

    public function confirmDelete(int $widgetId): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $this->confirmingDelete = $widgetId;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $deletedId = $this->confirmingDelete;

        $widget = Widget::query()
            ->where('id', $this->confirmingDelete)
            ->where('page', $this->page)
            ->first();

        if ($widget) {
            // قدیمی با FK cascade (on delete cascade) حذف می‌شد؛ برای قطعیت در همه
            // درایورها (SQLite FK pragma خاموش) حذف صریح options انجام می‌شود.
            $widget->options()->delete();
            $widget->delete();
        }

        // اگر ابزارک باز، همان بود فرمش بسته شود
        if ($deletedId && $this->editingId === $deletedId) {
            $this->closeEditor();
        }

        $this->confirmingDelete = null;
        unset($this->widgets);

        $this->dispatch('toast', message: 'ابزارک با موفقیت حذف شد.', type: 'success');
        $this->dispatch('ap-refresh-preview');
    }

    /* ================================================================
     *  پورت getRequestOptions قدیمی (۱:۱ مثل WidgetForm)
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
     *  پورت saveWidgetOptions قدیمی (۱:۱ مثل WidgetForm)
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
     *  پورت formatOptionValue + عنوان‌های فارسی (مثل WidgetForm)
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
            'post_categories'    => 'دسته‌بندی‌های مقالات',
            'product_categories' => 'دسته‌بندی‌های محصولات',

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

    /* ================================================================
     *  ویو (layout تمام‌صفحه‌ی مخصوص builder)
     * ================================================================ */

    public function render()
    {
        return view('admin-panel::livewire.admin.widgets.builder')
            ->layout('admin-panel::layouts.builder');
    }
}

<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\Category;
use App\Models\Widget;
use App\Livewire\Admin\Widgets\Concerns\InteractsWithWidgetTemplates;
use App\Services\WidgetTemplateRegistry;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * ویجت‌ساز تمام‌صفحه (فاز ۹-ه + بازطراحی فاز ۱۱) — پنل ابزارک‌ها + پیش‌نمایش زنده iframe
 *
 * فاز ۱۱:
 *  - افزودن دو مرحله‌ای: انتخاب نوع (مودال ۱) → مشخصات (مودال ۲ مطابق اسکرین‌شات:
 *    نوع/عنوان/وضعیت/ترتیب نمایش/options) → دکمه «ایجاد»
 *  - ویرایش با مودال (به‌جای فرم inline)
 *  - پشتیبانی از صفحه «posts» (ابزارک‌های مقالات) با PostWidgetBuilder
 *
 * پورت منطق داده از:
 *   - WidgetController::store / update  → createWidget / saveOptions (با getRequestOptions + saveWidgetOptions)
 *   - WidgetController::sort            → reorder
 *   - WidgetController::destroy         → delete
 */
#[Title('ویجت‌ساز')]
class WidgetBuilder extends Component
{
    use WithFileUploads;
    use InteractsWithWidgetTemplates;

    /** صفحه‌ی ابزارک‌ها: 'home' یا 'posts' (PostWidgetBuilder مقدار posts ست می‌کند) */
    public string $page = 'home';

    /** مودال انتخاب نوع (مرحله ۱ افزودن) */
    public bool $showAddModal = false;

    /** مودال مشخصات ابزارک (مرحله ۲ افزودن یا ویرایش) */
    public bool $showFormModal = false;

    /** حالت مودال مشخصات: false = ساخت ابزارک جدید (draftKey)، true = ویرایش (modalWidgetId) */
    public bool $isModalEditing = false;

    public ?int $modalWidgetId = null;

    /** نوع انتخاب‌شده در مرحله ۱ (حالت ایجاد) */
    public string $draftKey = '';

    /* ---------------- فیلدهای مودال مشخصات (مطابق اسکرین‌شات فاز ۱۱) ---------------- */

    public string $modalTitle = '';

    public bool $modalActive = true;

    /** ترتیب نمایش — خالی = آخر لیست */
    public $modalOrdering = '';

    /** id ابزارک در مودال تایید حذف */
    public ?int $confirmingDelete = null;

    /** مقادیر options ابزارک باز — پورت request->options قدیمی */
    public array $optionValues = [];

    /** فایل‌های آپلودی جدید — [option-key => TemporaryUploadedFile] */
    public array $fileUploads = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);
    }

    /* ================================================================
     *  صفحه / عنوان‌ها (PostWidgetBuilder رونویسی می‌کند)
     * ================================================================ */

    public function widgetPage(): string
    {
        return $this->page;
    }

    public function pageTitle(): string
    {
        return 'ویجت‌ساز صفحه اصلی';
    }

    public function pageLabel(): string
    {
        return 'صفحه اصلی';
    }

    /** آدرس پیش‌نمایش: خانه = / ، مقالات = /blog */
    public function previewBaseUrl(): string
    {
        return url('/');
    }

    public function indexRoute(): string
    {
        return route('np.widgets.index');
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

    /** template متناظر با کلید جاری مودال (ایجاد: draftKey / ویرایش: key ابزارک) */
    protected function templateForCurrentKey(): ?array
    {
        if ($this->isModalEditing) {
            $widget = $this->modalWidget;

            return $widget
                ? WidgetTemplateRegistry::findConfigForPage($this->page, (string) $widget->key)
                : null;
        }

        if ($this->draftKey === '') {
            return null;
        }

        return WidgetTemplateRegistry::findConfigForPage($this->page, $this->draftKey);
    }

    #[Computed]
    public function modalWidget(): ?Widget
    {
        if (! $this->modalWidgetId) {
            return null;
        }

        return $this->widgets->firstWhere('id', $this->modalWidgetId);
    }

    /** مقدار فعلی یک option برای نمایش در فرم (پورت WidgetForm::optionValue) */
    public function optionValue(string $optionKey): ?string
    {
        if (array_key_exists($optionKey, $this->optionValues) && $this->optionValues[$optionKey] !== '') {
            return (string) $this->optionValues[$optionKey];
        }

        $widget = $this->isModalEditing ? $this->modalWidget : null;

        return $widget ? $widget->option($optionKey) : null;
    }

    /* ---------------- دسته‌بندی‌ها (پورت template() قدیمی — برای partial options) ---------------- */

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
     *  فاز ۱۱ — جریان افزودن دو مرحله‌ای
     * ================================================================ */

    /**
     * مرحله ۱ → ۲: انتخاب نوع (از مودال گرید کارت‌ها) — هنوز چیزی در DB ساخته نمی‌شود.
     */
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

        $this->isModalEditing = false;
        $this->modalWidgetId = null;
        $this->draftKey = $key;
        $this->modalTitle = (string) ($template['title'] ?? $key);
        $this->modalActive = false; // تا تکمیل تنظیمات غیرفعال (مطابق اسپک فاز ۹)
        $this->modalOrdering = (int) $this->widgets->max('ordering') + 1;
        $this->initOptionValuesForNew($key);

        $this->showAddModal = false;
        $this->showFormModal = true;
    }

    /**
     * مرحله ۲: دکمه «ایجاد» — پورت store قدیمی (Widget::create + saveWidgetOptions).
     */
    public function createWidget(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        if ($this->draftKey === '' || ! in_array($this->draftKey, WidgetTemplateRegistry::allKeysForPage($this->page), true)) {
            $this->dispatch('toast', message: 'ابتدا نوع ابزارک را انتخاب کنید.', type: 'error');

            return;
        }

        $template = WidgetTemplateRegistry::findConfigForPage($this->page, $this->draftKey);

        if (! $template) {
            $this->dispatch('toast', message: 'ویجت یافت نشد.', type: 'error');

            return;
        }

        // عنوان اجباری (پورت validate عنوان در فرم قدیمی)
        $this->validate([
            'modalTitle' => 'required|string|max:191',
        ], [
            'modalTitle.required' => 'عنوان ابزارک الزامی است.',
        ], [
            'modalTitle' => 'عنوان ابزارک',
        ]);

        $errors = $this->validateOptionValues($template);

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $maxOrdering = (int) $this->widgets->max('ordering');

        $widget = Widget::create([
            'title'     => $this->modalTitle,
            'key'       => $this->draftKey,
            'is_active' => $this->modalActive,
            'theme'     => current_theme_name(),
            'lang'      => app()->getLocale(),
            'ordering'  => (int) $this->modalOrdering > 0 ? (int) $this->modalOrdering : $maxOrdering + 1,
        ]);

        $options = $this->getRequestOptions($template, $widget);
        $this->saveWidgetOptions($widget, $options);

        // لاگ ایجاد (سبک‌تر از ویرایش — فقط ثبت عمل)
        activity()
            ->performedOn($widget)
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action'    => 'ابزارک ' . $this->pageLabel() . ' را ایجاد کرد',
                'widget_id' => $widget->id,
                'page'      => $this->page,
                'ip'        => request()->ip(),
            ])
            ->log("مدیر ابزارک {$this->pageLabel()} «{$widget->title}» را ایجاد کرد");

        $this->closeFormModal();
        unset($this->widgets);

        $this->dispatch('toast', message: 'ابزارک «' . $widget->title . '» ایجاد شد.', type: 'success');
        $this->dispatch('ap-highlight-widget', id: $widget->id);
        $this->dispatch('ap-refresh-preview');
    }

    /* ================================================================
     *  ویرایش (مودال مشخصات) — پورت update قدیمی
     * ================================================================ */

    /** باز کردن مودال مشخصات برای ویرایش ابزارک موجود */
    public function openEditor(int $widgetId): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $widget = $this->widgets->firstWhere('id', $widgetId);

        if (! $widget) {
            return;
        }

        $this->isModalEditing = true;
        $this->modalWidgetId = $widgetId;
        $this->draftKey = '';
        $this->modalTitle = (string) $widget->title;
        $this->modalActive = (bool) $widget->is_active;
        $this->modalOrdering = (int) ($widget->ordering ?? 0);
        $this->loadOptionValuesFor($widget);

        $this->showFormModal = true;

        // هایلایت + اسکرول به ابزارک داخل پیش‌نمایش (پس از load در JS ارسال می‌شود)
        $this->dispatch('ap-highlight-widget', id: $widgetId);
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->isModalEditing = false;
        $this->modalWidgetId = null;
        $this->draftKey = '';
        $this->modalTitle = '';
        $this->modalActive = true;
        $this->modalOrdering = '';
        $this->reset('optionValues', 'fileUploads');
    }

    /** باز کردن انتخاب نوع از داخل ویرایش (مودال ۱ روی مودال ۲) */
    public function openTypePickerForEdit(): void
    {
        // ویرایش: فقط نمایش — نوع ابزارک ثابت است (تغییر نوع = options ناسازگار)
        $this->dispatch('toast', message: 'برای تغییر نوع، ابزارک را حذف و ابزارک جدیدی بسازید.', type: 'info');
    }

    /**
     * ذخیره مودال مشخصات — پورت update قدیمی (مشخصات + بازساخت options + لاگ)
     */
    public function saveOptions(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $widget = $this->modalWidget;

        if (! $widget || ! $this->isModalEditing) {
            $this->dispatch('toast', message: 'ابتدا یک ابزارک را انتخاب کنید.', type: 'error');

            return;
        }

        $template = WidgetTemplateRegistry::findConfigForPage($this->page, (string) $widget->key);

        if (! $template) {
            throw ValidationException::withMessages([
                'optionValues' => 'ویجت یافت نشد.',
            ]);
        }

        $this->validate([
            'modalTitle' => 'required|string|max:191',
        ], [
            'modalTitle.required' => 'عنوان ابزارک الزامی است.',
        ], [
            'modalTitle' => 'عنوان ابزارک',
        ]);

        $errors = $this->validateOptionValues($template);

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        /* ========== پورت update قدیمی (مشخصات + options + لاگ) ========== */

        $oldOptions = $this->snapshotWidgetOptions($widget);
        $oldTitle = $widget->title;
        $oldActive = (bool) $widget->is_active;
        $oldOrdering = (int) ($widget->ordering ?? 0);

        // 1. مشخصات اصلی
        $widget->update([
            'title'     => $this->modalTitle,
            'is_active' => $this->modalActive,
            'ordering'  => (int) $this->modalOrdering > 0 ? (int) $this->modalOrdering : $widget->ordering,
        ]);

        // 2. ذخیره options جدید (حذف و بازساخت — رفتار قدیمی)
        $options = $this->getRequestOptions($template, $widget);
        $widget->options()->delete();
        $this->saveWidgetOptions($widget, $options);

        // 3. لاگ (ساختار قدیمی)
        $allChanges = [];

        if ($oldTitle !== $widget->title) {
            $allChanges['title'] = [
                'title' => 'عنوان ابزارک',
                'old'   => $oldTitle,
                'new'   => $widget->title,
            ];
        }

        if ($oldActive !== $widget->is_active) {
            $allChanges['is_active'] = [
                'title' => 'وضعیت',
                'old'   => $oldActive ? 'فعال' : 'غیرفعال',
                'new'   => $widget->is_active ? 'فعال' : 'غیرفعال',
            ];
        }

        if ($oldOrdering !== (int) $widget->ordering) {
            $allChanges['ordering'] = [
                'title' => 'ترتیب نمایش',
                'old'   => $oldOrdering,
                'new'   => $widget->ordering,
            ];
        }

        $optionChanges = $this->widgetOptionChanges($widget, $oldOptions);

        if (! empty($optionChanges)) {
            $allChanges['options'] = $optionChanges;
        }

        $this->logWidgetActivity($widget, $allChanges, 'ابزارک  ' . $this->pageLabel() . ' را ویرایش کرد', $this->pageLabel());

        // 4. بازخوانی فرم با مقادیر تازه + پاک‌سازی فایل‌ها
        $this->reset('fileUploads');
        $this->loadOptionValuesFor($widget->refresh());
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
            // قدیمی با FK cascade حذف می‌شد؛ برای قطعیت در همه درایورها حذف صریح options
            $widget->options()->delete();
            $widget->delete();
        }

        // اگر مودال ابزارک حذف‌شده باز بود، بسته شود
        if ($deletedId && $this->modalWidgetId === $deletedId) {
            $this->closeFormModal();
        }

        $this->confirmingDelete = null;
        unset($this->widgets);

        $this->dispatch('toast', message: 'ابزارک با موفقیت حذف شد.', type: 'success');
        $this->dispatch('ap-refresh-preview');
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

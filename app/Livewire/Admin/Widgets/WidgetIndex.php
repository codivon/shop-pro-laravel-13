<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\Widget;
use App\Livewire\Admin\Widgets\Concerns\InteractsWithWidgetTemplates;
use App\Services\WidgetTemplateRegistry;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('ابزارک‌ها')]
class WidgetIndex extends Component
{
    use WithFileUploads;
    use InteractsWithWidgetTemplates;

    /** صفحه‌ی ابزارک‌ها: 'home' یا 'posts' (کلاس PostWidgetIndex مقدار posts ست می‌کند) */
    public string $page = 'home';

    public ?int $confirmingDelete = null;

    /* ================================================================
     *  فاز ۱۱ — مودال‌های ایجاد/ویرایش (لیست به‌جای کارت + درگ + مودال)
     * ================================================================ */

    /** مودال انتخاب نوع ابزارک (گرید کارت‌ها) */
    public bool $showTypeModal = false;

    /** مودال مشخصات ابزارک (ایجاد یا ویرایش) */
    public bool $showFormModal = false;

    /** false = ایجاد، true = ویرایش */
    public bool $isModalEditing = false;

    public ?int $modalWidgetId = null;

    public string $modalKey = '';

    public string $modalTitle = '';

    public bool $modalActive = true;

    /** ترتیب نمایش — خالی = آخر لیست */
    public $modalOrdering = '';

    /** مقادیر options — پورت request->options قدیمی */
    public array $optionValues = [];

    /** فایل‌های آپلودی جدید — [option-key => TemporaryUploadedFile] */
    public array $fileUploads = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        /* انحراف مستند از قدیمی:
         * WidgetController قدیمی در سازنده اگر registry صفحه خالی بود abort(404) می‌کرد؛
         * تصمیم تأییدشده‌ی کارفرما: پنل جدید به‌جای 404 حالت خالی دوستانه رندر می‌کند و
         * وقتی ماژول‌ها (یا config قالب) ویجتی ثبت کنند همان صفحه به‌صورت کامل کار می‌کند.
         */
    }

    /* ================================================================
     *  صفحه (PostWidgetIndex رونویسی می‌کند)
     * ================================================================ */

    public function widgetPage(): string
    {
        return $this->page;
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function widgets()
    {
        // قدیمی: Widget::detectLang()->where('theme', ...)->where('page', ...)->orderBy('ordering')->get()
        return Widget::query()
            ->detectLang()
            ->where('theme', get_current_theme()['name'] ?? '')
            ->where('page', $this->page)
            ->orderBy('ordering')
            ->get();
    }

    /* ---------------- ابزارک‌های ثبت‌شده (config قالب + ماژول‌ها) ---------------- */

    /** کلیدهای مجاز برای این صفحه — وقتی خالی است یعنی هیچ نوع ابزارکی ثبت نشده */
    public function registryKeys(): array
    {
        return WidgetTemplateRegistry::allKeysForPage($this->page);
    }

    /** عنوان خوانا برای هر نوع ابزارک */
    public function typeTitle(string $key): string
    {
        $templates = WidgetTemplateRegistry::mergedForPage($this->page);

        return $templates[$key]['title'] ?? $key;
    }

    public function isRegistryEmpty(): bool
    {
        return empty($this->registryKeys());
    }

    /* ================================================================
     *  مودال نوع → مشخصات (فاز ۱۱ — غیر سلکتی)
     * ================================================================ */

    /** template متناظر با کلید جاری مودال */
    protected function templateForCurrentKey(): ?array
    {
        if ($this->modalKey === '') {
            return null;
        }

        return WidgetTemplateRegistry::findConfigForPage($this->page, $this->modalKey);
    }

    #[Computed]
    public function modalWidget(): ?Widget
    {
        if (! $this->modalWidgetId) {
            return null;
        }

        return $this->widgets->firstWhere('id', $this->modalWidgetId);
    }

    /** مقدار فعلی یک option (برای نمایش در فرم) */
    public function optionValue(string $optionKey): ?string
    {
        if (array_key_exists($optionKey, $this->optionValues) && $this->optionValues[$optionKey] !== '') {
            return (string) $this->optionValues[$optionKey];
        }

        $widget = $this->modalWidget;

        return $widget ? $widget->option($optionKey) : null;
    }

    /* ---------------- دسته‌بندی‌ها (پورت template() قدیمی — برای partial options) ---------------- */

    #[Computed]
    public function productCategories()
    {
        return \App\Models\Category::query()->detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
    }

    #[Computed]
    public function postCategories()
    {
        return \App\Models\Category::query()->detectLang()->where('type', 'postcat')->orderBy('ordering')->get();
    }

    /* ---------------- جریان ایجاد: انتخاب نوع → مشخصات → ایجاد ---------------- */

    /** گام ۱: باز کردن مودال انتخاب نوع (ایجاد) */
    public function openAddFlow(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $this->isModalEditing = false;
        $this->modalWidgetId = null;
        $this->modalKey = '';
        $this->modalTitle = '';
        $this->modalActive = true;
        $this->modalOrdering = '';
        $this->reset('optionValues', 'fileUploads');

        $this->showTypeModal = true;
    }

    /** انتخاب نوع از مودال (هم در جریان ایجاد، هم تغییر نوع هنگام ویرایش) */
    public function pickType(string $key): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        if (! in_array($key, $this->registryKeys(), true)) {
            $this->dispatch('toast', message: 'ویجت یافت نشد.', type: 'error');

            return;
        }

        $template = WidgetTemplateRegistry::findConfigForPage($this->page, $key);

        if (! $template) {
            $this->dispatch('toast', message: 'ویجت یافت نشد.', type: 'error');

            return;
        }

        $wasCreating = ! $this->isModalEditing;

        $this->modalKey = $key;

        // عنوان پیشنهادی: فقط در جریان ایجاد (ویرایش: عنوان کاربر حفظ شود)
        if ($wasCreating && $this->modalTitle === '') {
            $this->modalTitle = (string) ($template['title'] ?? $key);
        }

        if ($wasCreating) {
            $this->modalActive = false; // تا تکمیل تنظیمات غیرفعال (مطابق اسپک)
            $this->modalOrdering = (int) $this->widgets->max('ordering') + 1;
            $this->initOptionValuesForNew($key);
        } else {
            // تغییر نوع در ویرایش: options از default نوع جدید
            $this->initOptionValuesForNew($key);
        }

        $this->showTypeModal = false;
        $this->showFormModal = true;
    }

    /** گام ۲: دکمه «ایجاد» — پورت store قدیمی */
    public function saveModal(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $isEditing = $this->isModalEditing;

        // عنوان اجباری + نوع اجباری
        $this->validate([
            'modalTitle' => 'required|string|max:191',
            'modalKey'   => $isEditing ? 'nullable' : 'required',
        ], [
            'modalTitle.required' => 'عنوان ابزارک الزامی است.',
            'modalKey.required'   => 'نوع ابزارک را انتخاب کنید.',
        ], [
            'modalTitle' => 'عنوان ابزارک',
            'modalKey'   => 'نوع ابزارک',
        ]);

        $template = WidgetTemplateRegistry::findConfigForPage($this->page, $this->modalKey);

        if (! $template) {
            $this->dispatch('toast', message: 'ویجت یافت نشد.', type: 'error');

            return;
        }

        $errors = $this->validateOptionValues($template);

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        if ($isEditing) {
            /* ========== پورت update قدیمی ========== */
            $widget = $this->modalWidget;

            if (! $widget) {
                $this->dispatch('toast', message: 'ابزارک یافت نشد.', type: 'error');

                return;
            }

            $oldOptions = $this->snapshotWidgetOptions($widget);
            $oldTitle = $widget->title;
            $oldActive = (bool) $widget->is_active;
            $oldOrdering = (int) ($widget->ordering ?? 0);

            $widget->update([
                'title'     => $this->modalTitle,
                'key'       => $this->modalKey, // تغییر نوع مجاز (مثل select فرم قدیمی)
                'is_active' => $this->modalActive,
                'ordering'  => (int) $this->modalOrdering > 0 ? (int) $this->modalOrdering : $widget->ordering,
            ]);

            $options = $this->getRequestOptions($template, $widget);
            $widget->options()->delete();
            $this->saveWidgetOptions($widget, $options);

            // لاگ (ساختار قدیمی)
            $allChanges = [];

            if ($oldTitle !== $widget->title) {
                $allChanges['title'] = ['title' => 'عنوان ابزارک', 'old' => $oldTitle, 'new' => $widget->title];
            }

            if ($oldActive !== $widget->is_active) {
                $allChanges['is_active'] = [
                    'title' => 'وضعیت',
                    'old'   => $oldActive ? 'فعال' : 'غیرفعال',
                    'new'   => $widget->is_active ? 'فعال' : 'غیرفعال',
                ];
            }

            if ($oldOrdering !== (int) $widget->ordering) {
                $allChanges['ordering'] = ['title' => 'ترتیب نمایش', 'old' => $oldOrdering, 'new' => $widget->ordering];
            }

            $optionChanges = $this->widgetOptionChanges($widget, $oldOptions);

            if (! empty($optionChanges)) {
                $allChanges['options'] = $optionChanges;
            }

            $this->logWidgetActivity($widget, $allChanges, 'ابزارک  ' . $this->pageLabel() . ' را ویرایش کرد', $this->pageLabel());

            $message = 'تغییرات ابزارک «' . $widget->title . '» ذخیره شد.';
        } else {
            /* ========== پورت store قدیمی ========== */
            $maxOrdering = (int) $this->widgets->max('ordering');

            $widget = Widget::create([
                'title'     => $this->modalTitle,
                'key'       => $this->modalKey,
                'is_active' => $this->modalActive,
                'theme'     => current_theme_name(),
                'lang'      => app()->getLocale(),
                'ordering'  => (int) $this->modalOrdering > 0 ? (int) $this->modalOrdering : $maxOrdering + 1,
            ]);

            $options = $this->getRequestOptions($template, $widget);
            $this->saveWidgetOptions($widget, $options);

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

            $message = 'ابزارک «' . $widget->title . '» ایجاد شد.';
        }

        $this->closeModals();
        unset($this->widgets);

        $this->dispatch('toast', message: $message, type: 'success');
    }

    /* ---------------- ویرایش ---------------- */

    /** باز کردن مودال مشخصات برای ویرایش */
    public function openEditModal(int $widgetId): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $widget = $this->widgets->firstWhere('id', $widgetId);

        if (! $widget) {
            return;
        }

        $this->isModalEditing = true;
        $this->modalWidgetId = $widgetId;
        $this->modalKey = (string) $widget->key;
        $this->modalTitle = (string) $widget->title;
        $this->modalActive = (bool) $widget->is_active;
        $this->modalOrdering = (int) ($widget->ordering ?? 0);
        $this->loadOptionValuesFor($widget);

        $this->showFormModal = true;
    }

    public function closeModals(): void
    {
        $this->showTypeModal = false;
        $this->showFormModal = false;
        $this->isModalEditing = false;
        $this->modalWidgetId = null;
        $this->modalKey = '';
        $this->modalTitle = '';
        $this->modalActive = true;
        $this->modalOrdering = '';
        $this->reset('optionValues', 'fileUploads');
    }

    public function pageLabel(): string
    {
        return 'صفحه اصلی';
    }

    /* ---------------- toggle سریع وضعیت (بهبود کوچک — مستند) ---------------- */

    public function toggleActive(int $widgetId): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $widget = Widget::query()->where('id', $widgetId)->where('page', $this->page)->first();

        if ($widget) {
            $widget->update(['is_active' => ! $widget->is_active]);

            $this->dispatch('toast', message: $widget->is_active ? 'ابزارک فعال شد.' : 'ابزارک غیرفعال شد.', type: 'success');
        }
    }

    /* ---------------- ترتیب (پورت sort قدیمی + درگ لیست) ---------------- */

    /**
     * پورت WidgetController::sort — آرایه‌ی id ها به ترتیب جدید → ordering = 1..n
     */
    public function reorderWidgets(array $widgetIds): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

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
    }

    public function moveUp(int $widgetId): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $ordered = $this->widgets->pluck('id')->values();
        $current = $ordered->search($widgetId);

        if ($current === false || $current === 0) {
            return;
        }

        [$ordered[$current], $ordered[$current - 1]] = [$ordered[$current - 1], $ordered[$current]];

        $this->reorderWidgets($ordered->all());
    }

    public function moveDown(int $widgetId): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $ordered = $this->widgets->pluck('id')->values();
        $current = $ordered->search($widgetId);

        if ($current === false || $current === $ordered->count() - 1) {
            return;
        }

        [$ordered[$current], $ordered[$current + 1]] = [$ordered[$current + 1], $ordered[$current]];

        $this->reorderWidgets($ordered->all());
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

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
            // پورت WidgetController::destroy — حذف صریح options + رکورد
            $widget->options()->delete();
            $widget->delete();
        }

        // اگر مودال ابزارک حذف‌شده باز بود بسته شود
        if ($deletedId && $this->modalWidgetId === $deletedId) {
            $this->closeModals();
        }

        $this->confirmingDelete = null;
        unset($this->widgets);

        $this->dispatch('toast', message: 'ابزارک با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- روت‌ها (PostWidgetIndex رونویسی می‌کند) ---------------- */

    public function routePrefix(): string
    {
        return 'np.widgets';
    }

    public function pageTitle(): string
    {
        return 'ابزارک‌های صفحه اصلی';
    }

    public function builderRoute(): ?string
    {
        try {
            return route('np.widgets.builder');
        } catch (\Throwable) {
            return null;
        }
    }

    public function createRoute(): string
    {
        return route($this->routePrefix() . '.create');
    }

    public function editRoute(Widget $widget): string
    {
        return route($this->routePrefix() . '.edit', ['widget' => $widget]);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.widgets.widgets')
            ->layout('admin-panel::layout');
    }
}

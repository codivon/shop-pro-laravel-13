<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\Widget;
use App\Services\WidgetTemplateRegistry;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('ابزارک‌ها')]
class WidgetIndex extends Component
{
    /** صفحه‌ی ابزارک‌ها: 'home' یا 'posts' (کلاس PostWidgetIndex مقدار posts ست می‌کند) */
    public string $page = 'home';

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        /* انحراف مستند از قدیمی:
         * WidgetController قدیمی در سازنده اگر registry صفحه خالی بود abort(404) می‌کرد؛
         * تصمیم تأییدشده‌ی کارفرما: پنل جدید به‌جای 404 حالت خالی دوستانه رندر می‌کند و
         * وقتی ماژول‌ها (یا config قالب) ویجتی ثبت کنند همان صفحه به‌صورت کامل کار می‌کند.
         */
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function widgets()
    {
        // قدیمی: Widget::detectLang()->where('theme', get_current_theme()['name'] ?? '')->where('page', ...)->orderBy('ordering')->get()
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

    /* ---------------- ترتیب (پورت sort قدیمی + جابجایی ↑↓) ---------------- */

    /**
     * پورت WidgetController::sort — آرایه‌ی id ها به ترتیب جدید → ordering = 1..n
     */
    public function reorderWidgets(array $widgetIds): void
    {
        auth('adminPanel')->user()?->can('themes.widgets') ?: abort(403);

        $i = 1;

        foreach ($widgetIds as $widgetId) {
            Widget::query()
                ->where('id', $widgetId)
                ->where('page', $this->page)
                ->update(['ordering' => $i++]);
        }

        unset($this->widgets);
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

        // پورت WidgetController::destroy — فقط حذف رکورد (options با FK cascade)
        Widget::query()
            ->where('id', $this->confirmingDelete)
            ->where('page', $this->page)
            ->first()
            ?->delete();

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'ابزارک با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- روتیک view (PostWidgetIndex رونویسی می‌کند) ---------------- */

    public function routePrefix(): string
    {
        return 'np.widgets';
    }

    public function pageTitle(): string
    {
        return 'ابزارک‌های صفحه اصلی';
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

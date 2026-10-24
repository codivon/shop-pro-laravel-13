<?php

namespace App\Livewire\Admin\Sliders;

use App\Models\Slider;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * لیست اسلایدرها — پورت ۱:۱ SliderController@index (نمای back/sliders/index با کارت‌های گروه‌بندی‌شده بر اساس صفحه)
 * + مرتب‌سازی ↑↓ (پورت sort قدیمی با بازنویسی ۱..n) و حذف تکی (پورت destroy).
 */
#[Title('مدیریت اسلایدرها')]
class SliderIndex extends Component
{
    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('sliders.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function sliders()
    {
        // پورت: Slider::detectLang()->orderBy('ordering')->get() — با orderBy('id') برای قطعیت ترتیبِ برابرها
        //
        // فاز ۳۰ — رفع «اسلایدرها در پنل نمایش داده نمی‌شوند»: ردیف‌هایی که در دیتابیس lang = NULL
        // دارند (ایمپورت دستی/دیتای قدیمی) با detectLang ساده (lang = locale) حذف می‌شدند، در حالی‌که
        // فرانت همین ردیف‌ها را نشان می‌دهد (کوئری‌های فرانت فیلتر lang ندارند). الان ردیف‌های
        // بدون زبان هم مثل بقیه در لیست پنل می‌آیند.
        return Slider::query()
            ->where(fn ($q) => $q->where('lang', app()->getLocale())->orWhereNull('lang'))
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /** آمار سربرگ (پورت بخش آمار ویوی قدیمی) */
    #[Computed]
    public function stats(): array
    {
        $all = $this->sliders;

        return [
            'total' => $all->count(),
            'published' => $all->where('published', true)->count(),
            'drafts' => $all->where('published', false)->count(),
            'pagesCovered' => $all->flatMap(fn ($slider) => $slider->pages ?: [])->unique()->count(),
        ];
    }

    /**
     * کاتالوگ گروه‌ها (لیبل/ابعاد فارسی از متد static خودِ مدل).
     */
    #[Computed]
    public function groupCatalog(): array
    {
        return Slider::availableGroups();
    }

    /* ---------------- مرتب‌سازی (پورت sort قدیمی: بازنویسی ۱..n) ---------------- */

    public function moveUp(int $id): void
    {
        $this->move($id, -1);
    }

    public function moveDown(int $id): void
    {
        $this->move($id, 1);
    }

    protected function move(int $id, int $direction): void
    {
        auth('adminPanel')->user()?->can('sliders.update') ?: abort(403);

        // انحراف مستند: به‌جای درگ‌اند‌دراپ (jquery-ui قدیمی)، جابه‌جایی با همسایه + بازنویسی
        // کامل ordering از ۱ تا n — دقیقاً همان نتیجه‌ی endpoint های sort قدیمی.
        $ordered = $this->sliders->pluck('id')->values();

        $current = $ordered->search($id);

        if ($current === false) {
            return;
        }

        $target = $current + $direction;

        if (! $ordered->offsetExists($target)) {
            return; // لبه‌ی لیست
        }

        [$ordered[$current], $ordered[$target]] = [$ordered[$target], $ordered[$current]];

        foreach ($ordered as $index => $sliderId) {
            Slider::query()->whereKey($sliderId)->update(['ordering' => $index + 1]);
        }

        unset($this->sliders, $this->stats);

        $this->dispatch('toast', message: 'ترتیب اسلایدرها به‌روزرسانی شد.', type: 'success');
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('sliders.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('sliders.delete') ?: abort(403);

        // پورت ۱:۱ destroy قدیمی: فقط حذف رکورد (فایل تصویر مثل قدیمی حذف نمی‌شود)
        if ($slider = Slider::find($this->confirmingDelete)) {
            $slider->delete();
        }

        $this->confirmingDelete = null;

        unset($this->sliders, $this->stats);

        $this->dispatch('toast', message: 'اسلایدر با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- نمایش ---------------- */

    public function render()
    {
        return view('admin-panel::livewire.admin.sliders.sliders')
            ->layout('admin-panel::layout');
    }
}

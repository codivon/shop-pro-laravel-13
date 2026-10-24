<?php

namespace App\Livewire\Admin\Banners;

use App\Models\Banner;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * لیست بنرها — پورت ۱:۱ BannerController@index (نمای back/banners/index با کارت‌های گروه‌بندی‌شده بر اساس صفحه)
 * + مرتب‌سازی ↑↓ (پورت sort قدیمی با بازنویسی ۱..n) و حذف تکی (پورت destroy).
 *
 * تفاوت با اسلایدر (پورت دقیق قدیمی): بدون detectLang (جدول banners ستون lang ندارد).
 */
#[Title('مدیریت بنرها')]
class BannerIndex extends Component
{
    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('banners.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function banners()
    {
        // پورت: Banner::orderBy('ordering')->get() — با orderBy('id') برای قطعیت ترتیبِ برابرها
        return Banner::query()
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /** آمار سربرگ (پورت بخش آمار ویوی قدیمی) */
    #[Computed]
    public function stats(): array
    {
        $all = $this->banners;

        return [
            'total'        => $all->count(),
            'published'    => $all->where('published', true)->count(),
            'drafts'       => $all->where('published', false)->count(),
            'pagesCovered' => $all->flatMap(fn ($banner) => $banner->pages ?: [])->unique()->count(),
        ];
    }

    /** کاتالوگ‌های گروه/موقعیت با لیبل فارسی از خود مدل */
    #[Computed]
    public function catalogs(): array
    {
        return [
            'groups' => Banner::availableGroups(),
            'places' => Banner::availablePlaces(),
        ];
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
        auth('adminPanel')->user()?->can('banners.update') ?: abort(403);

        // انحراف مستند: به‌جای درگ‌اند‌دراپ (jquery-ui قدیمی)، جابه‌جایی با همسایه + بازنویسی
        // کامل ordering از ۱ تا n — دقیقاً همان نتیجه‌ی endpoint های sort قدیمی.
        $ordered = $this->banners->pluck('id')->values();

        $current = $ordered->search($id);

        if ($current === false) {
            return;
        }

        $target = $current + $direction;

        if (! $ordered->offsetExists($target)) {
            return; // لبه‌ی لیست
        }

        [$ordered[$current], $ordered[$target]] = [$ordered[$target], $ordered[$current]];

        foreach ($ordered as $index => $bannerId) {
            Banner::query()->whereKey($bannerId)->update(['ordering' => $index + 1]);
        }

        unset($this->banners, $this->stats);

        $this->dispatch('toast', message: 'ترتیب بنرها به‌روزرسانی شد.', type: 'success');
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('banners.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('banners.delete') ?: abort(403);

        // پورت ۱:۱ destroy قدیمی: فقط حذف رکورد (فایل تصویر مثل قدیمی حذف نمی‌شود)
        if ($banner = Banner::find($this->confirmingDelete)) {
            $banner->delete();
        }

        $this->confirmingDelete = null;

        unset($this->banners, $this->stats);

        $this->dispatch('toast', message: 'بنر با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- نمایش ---------------- */

    public function render()
    {
        return view('admin-panel::livewire.admin.banners.banners')
            ->layout('admin-panel::layout');
    }
}

<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\SizeType;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * لیست راهنمای سایزها — پورت SizeTypeController@index + create/store
 * دسترسی: products.sizetypes
 */
#[Title('راهنمای سایز')]
class SizeTypeIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- فرم ایجاد سریع (کارت جاسازی‌شده — پورت create/store قدیمی) ---------------- */

    public bool $showCreate = false;

    public string $title = '';

    public string $description = '';

    /** ردیف‌های سایز (ریپیتر متن ساده) */
    public array $newSizes = [''];

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.sizetypes') ?: abort(403);
    }

    public function updatedShowCreate(): void
    {
        $this->resetPage();
    }

    /* ---------------- لیست ---------------- */

    #[Computed]
    public function sizetypes()
    {
        // پورت: SizeType::detectLang()->latest()->paginate(15)
        return SizeType::query()
            ->withCount('sizes')
            ->detectLang()
            ->latest()
            ->paginate($this->perPage);
    }

    /* ---------------- ایجاد (پورت store) ---------------- */

    public function store(): void
    {
        auth('adminPanel')->user()?->can('products.sizetypes') ?: abort(403);

        $this->validate([
            'title'       => ['required', 'unique:size_types,title'],
            'newSizes'    => ['required', 'array', 'min:1'],
            'newSizes.*'  => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ], [
            'title.required'      => 'عنوان الزامی است.',
            'title.unique'        => 'این عنوان قبلاً ثبت شده است.',
            'newSizes.required'   => 'حداقل یک مشخصه سایز لازم است.',
            'newSizes.min'        => 'حداقل یک مشخصه سایز لازم است.',
            'newSizes.*.required' => 'عنوان همه مشخصه‌های سایز باید پر شود.',
        ]);

        $sizetype = SizeType::create([
            'title'       => $this->title,
            'description' => $this->description ?: null,
            'lang'        => app()->getLocale(),
        ]);

        $ordering = 1;

        foreach ($this->newSizes as $title) {
            $sizetype->sizes()->create([
                'title'    => $title,
                'ordering' => $ordering++,
            ]);
        }

        $this->reset('title', 'description', 'newSizes', 'showCreate');
        $this->newSizes = [''];

        $this->dispatch('toast', message: 'راهنمای سایز با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ریپیتر سایزها ---------------- */

    public function addSizeRow(): void
    {
        $this->newSizes[] = '';
    }

    public function removeSizeRow(int $index): void
    {
        unset($this->newSizes[$index]);
        $this->newSizes = array_values($this->newSizes);
    }

    /* ---------------- حذف (پورت destroy) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('products.sizetypes') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('products.sizetypes') ?: abort(403);

        if ($sizetype = SizeType::find($this->confirmingDelete)) {
            // مثل قدیمی: حذف نرم؛ سایزها/مقادیر با cascade دیتابیس پاک می‌شوند
            $sizetype->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'راهنمای سایز حذف شد.', type: 'success');
    }

    /* ---------------- نمایش ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.sizetypes')
            ->layout('admin-panel::layout');
    }
}

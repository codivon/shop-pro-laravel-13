<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست برندها')]
class BrandIndex extends Component
{
    use WithPagination;
    use WithFileUploads;

    /* ---------------- فیلتر (State در URL) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 15;

    /* ---------------- کارت «برند جدید» (پورت create/store قدیمی) ---------------- */

    public string $createName = '';

    public string $createSlug = '';

    public string $createDescription = '';

    /** تصویر برند جدید (Livewire TemporaryUploadedFile) */
    public $createImage = null;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function brands()
    {
        return Brand::query()
            ->detectLang()
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function totalCount(): int
    {
        return Brand::detectLang()->count();
    }

    public function applyFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /* ---------------- تولید خودکار اسلاگ ---------------- */

    public function generateCreateSlug(): void
    {
        // فاز ۲۵ — اسلاگ فارسی (Str::slug حروف فارسی را حذف می‌کرد)
        $this->createSlug = sluggable_helper_function($this->createName) ?: trim($this->createName);
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createBrand(): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $data = $this->validate([
            'createName'        => 'required|unique:brands,name',
            'createSlug'        => 'required|unique:brands,slug',
            'createDescription' => 'nullable|string',
            'createImage'       => 'nullable|image|max:2048',
        ], [], [
            'createName'        => 'نام',
            'createSlug'        => 'آدرس (slug)',
            'createDescription' => 'توضیحات',
            'createImage'       => 'تصویر',
        ]);

        $data['name'] = $this->createName;
        $data['slug'] = $this->createSlug;
        $data['description'] = $this->createDescription ?: null;

        // الگوی ذخیره قدیمی حفظ شد: public/uploads/brands/{uniqid}_{time}.{ext} + مسیر /uploads/brands/ در DB
        if ($this->createImage) {
            $name = uniqid() . '_' . time() . '.' . $this->createImage->getClientOriginalExtension();
            $this->createImage->storeAs('brands', $name);

            $data['image'] = '/uploads/brands/' . $name;
        }

        $data['lang'] = app()->getLocale();

        Brand::create($data);

        $this->reset(['createName', 'createSlug', 'createDescription', 'createImage']);

        $this->dispatch('toast', message: 'برند با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $brand = Brand::find($this->confirmingDelete);

        if ($brand) {
            if ($brand->image) {
                Storage::disk('public')->delete(ltrim($brand->image, '/'));
            }

            // برابری FK قدیمی (products.brand_id onDelete set null) — قابل‌حمل بین SQLite/MySQL
            Product::where('brand_id', $brand->id)->update(['brand_id' => null]);

            $brand->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'برند با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.brands')
            ->layout('admin-panel::layout');
    }
}

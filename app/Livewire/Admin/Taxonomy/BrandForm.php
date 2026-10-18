<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Brand;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('admin-panel::layout')]
#[Title('ویرایش برند')]
class BrandForm extends Component
{
    use WithFileUploads;

    public Brand $brand;

    /* ---------------- فیلدهای فرم ---------------- */

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    /** تصویر جدید (اختیاری — در صورت انتخاب، تصویر قبلی حذف می‌شود مثل قدیمی) */
    public $image = null;

    public function mount(Brand $brand): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $this->brand = $brand;
        $this->name = (string) $brand->name;
        $this->slug = (string) $brand->slug;
        $this->description = (string) $brand->description;
    }

    public function generateSlug(): void
    {
        $this->slug = Str::slug($this->name) ?: trim($this->name);
    }

    /* ---------------- ذخیره (پورت ۱:۱ update قدیمی) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('products.brands') ?: abort(403);

        $data = $this->validate([
            'name'        => 'required|unique:brands,name,' . $this->brand->id,
            'slug'        => 'required|unique:brands,slug,' . $this->brand->id,
            'description' => 'nullable|string',
        ], [], [
            'name'        => 'نام',
            'slug'        => 'آدرس (slug)',
            'description' => 'توضیحات',
        ]);

        $data['description'] = $this->description ?: null;

        if ($this->image) {
            // حذف تصویر قبلی مثل قدیمی (با نرمال‌سازی اسلش ابتدای مسیر)
            if ($this->brand->image) {
                Storage::disk('public')->delete(ltrim($this->brand->image, '/'));
            }

            // الگوی ذخیره قدیمی حفظ شد: public/uploads/brands/{uniqid}_{time}.{ext} + مسیر /uploads/brands/ در DB
            $name = uniqid() . '_' . time() . '.' . $this->image->getClientOriginalExtension();
            $this->image->storeAs('brands', $name);

            $data['image'] = '/uploads/brands/' . $name;
        }

        $this->brand->update($data);

        session()->flash('np-toast', ['type' => 'success', 'message' => 'برند با موفقیت ویرایش شد.']);

        $this->redirectIntended(route('np.brands.index', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.brand-form');
    }
}

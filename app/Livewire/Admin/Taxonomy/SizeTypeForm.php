<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\SizeType;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * ویرایش راهنمای سایز — پورت SizeTypeController@edit/update
 * {sizetype} با کلید id بایند می‌شود.
 * دسترسی: products.sizetypes
 */
#[Title('ویرایش راهنمای سایز')]
class SizeTypeForm extends Component
{
    public SizeType $sizetype;

    public string $title = '';

    public string $description = '';

    /**
     * ردیف‌های سایز — انحراف از قدیمی:
     * قدیمی دو آرایه موازی sizes[] و sizes_id[] می‌فرستاد؛
     * در اینجا هر ردیف آبجکتی {id, title} است (id برای سایز موجود).
     *
     * @var array<int, array{id: ?int, title: string}>
     */
    public array $sizes = [];

    public function mount(SizeType $sizetype): void
    {
        auth('adminPanel')->user()?->can('products.sizetypes') ?: abort(403);

        $this->sizetype = $sizetype;
        $this->title = (string) $sizetype->title;
        $this->description = (string) $sizetype->description;

        $this->sizes = $sizetype->sizes()
            ->get()
            ->map(fn ($size) => ['id' => (int) $size->id, 'title' => (string) $size->title])
            ->values()
            ->all();
    }

    /* ---------------- ریپیتر ---------------- */

    public function addSizeRow(): void
    {
        $this->sizes[] = ['id' => null, 'title' => ''];
    }

    public function removeSizeRow(int $index): void
    {
        unset($this->sizes[$index]);
        $this->sizes = array_values($this->sizes);
    }

    /* ---------------- ذخیره (پورت update) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('products.sizetypes') ?: abort(403);

        $this->validate([
            'title'           => ['required', 'unique:size_types,title,' . $this->sizetype->id],
            'sizes'           => ['required', 'array', 'min:1'],
            'sizes.*.title'   => ['required', 'string', 'max:255'],
            'sizes.*.id'      => ['nullable', 'integer'],
            'description'     => ['nullable', 'string'],
        ], [
            'title.required'        => 'عنوان الزامی است.',
            'title.unique'          => 'این عنوان قبلاً ثبت شده است.',
            'sizes.required'        => 'حداقل یک مشخصه سایز لازم است.',
            'sizes.min'             => 'حداقل یک مشخصه سایز لازم است.',
            'sizes.*.title.required' => 'عنوان همه مشخصه‌های سایز باید پر شود.',
        ]);

        // پورت ۱:۱ SizeTypeController@update
        $this->sizetype->update([
            'title'       => $this->title,
            'description' => $this->description ?: null,
        ]);

        $ordering = 1;
        $sizes = [];

        foreach ($this->sizes as $row) {
            $id = $row['id'] ?? null;

            if ($id) {
                // سایز موجود این راهنما: آپدیت به همان ترتیب
                $size = $this->sizetype->sizes()->where('id', $id)->first();

                if ($size) {
                    $size->update([
                        'title'    => $row['title'],
                        'ordering' => $ordering++,
                    ]);
                } else {
                    // رفع باگ قدیمی: id نامعتبر باعث خطا می‌شد → به‌جای کرش، ردیف جدید ساخته می‌شود
                    $size = $this->sizetype->sizes()->create([
                        'title'    => $row['title'],
                        'ordering' => $ordering++,
                    ]);
                }
            } else {
                $size = $this->sizetype->sizes()->create([
                    'title'    => $row['title'],
                    'ordering' => $ordering++,
                ]);
            }

            $sizes[] = $size->id;
        }

        // حذف سایزهای حذف‌شده از فرم
        $this->sizetype->sizes()->whereNotIn('id', $sizes)->delete();

        session()->flash('np-toast', ['type' => 'success', 'message' => 'راهنمای سایز با موفقیت ویرایش شد.']);

        $this->redirect(route('np.sizetypes.index'), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.sizetype-form')
            ->layout('admin-panel::layout');
    }
}

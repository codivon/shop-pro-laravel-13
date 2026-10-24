<?php

namespace App\Livewire\Admin\Sellers\Settings;

use App\Models\SellerHero as SellerHeroModel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('تنظیمات فروشندگان — چرا ما')]
class SellerHero extends Component
{
    public bool $modal = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $icon = '';

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        // فاز ۲۷ — مثل پنل قدیمی (@can('sellers')) — این صفحات بخش تنظیمات فروشندگان‌اند
        auth('adminPanel')->user()?->can('sellers') ?: abort(403);
    }

    #[Computed]
    public function items()
    {
        return SellerHeroModel::latest('id')->get();
    }

    public function openModal(): void
    {
        $this->reset(['editingId', 'title', 'description', 'icon']);
        $this->modal = true;
    }

    public function edit(int $id): void
    {
        $item = SellerHeroModel::findOrFail($id);

        $this->editingId = $item->id;
        $this->title = (string) $item->title;
        $this->description = (string) ($item->description ?? '');
        $this->icon = (string) ($item->icon ?? '');
        $this->modal = true;
    }

    protected function rules(): array
    {
        return [
            'title' => 'required',
            'description' => 'nullable|string',
            'icon' => 'nullable',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'عنوان الزامی است.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'icon' => $this->icon ?: null,
        ];

        if ($this->editingId) {
            SellerHeroModel::find($this->editingId)?->update($data);
            $message = 'با موفقیت ویرایش شد.';
        } else {
            SellerHeroModel::create($data);
            $message = 'با موفقیت ایجاد شد.';
        }

        $this->modal = false;
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function delete(): void
    {
        SellerHeroModel::find($this->confirmingDelete)?->delete();

        $this->confirmingDelete = null;
        $this->dispatch('toast', message: 'رکورد با موفقیت حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.settings.hero')
            ->layout('admin-panel::layout');
    }
}

<?php

namespace App\Livewire\Admin\Sellers\Settings;

use App\Models\SellerEcontract as SellerEcontractModel;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('تنظیمات فروشندگان — قرارداد الکترونیک')]
class SellerEcontract extends Component
{
    public string $header = '';

    public string $content = '';

    public function mount(): void
    {
        // فاز ۲۷ — مثل پنل قدیمی (@can('sellers')) — این صفحات بخش تنظیمات فروشندگان‌اند
        auth('adminPanel')->user()?->can('sellers') ?: abort(403);

        $econtract = SellerEcontractModel::first();

        $this->header = (string) ($econtract->header ?? '');
        $this->content = (string) ($econtract->content ?? '');
    }

    protected function rules(): array
    {
        return [
            'content' => 'required',
        ];
    }

    protected function messages(): array
    {
        return [
            'content.required' => 'متن اصلی قرار داد نمیتواند خالی باشد.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        // سطر واحد — در صورت نبود ساخته می‌شود (رفع خطای find(1) قدیمی)
        $econtract = SellerEcontractModel::first() ?: new SellerEcontractModel();
        $econtract->header = $this->header ?: null;
        $econtract->content = $this->content;
        $econtract->save();

        $this->dispatch('toast', message: 'با موفقیت ذخیره شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.settings.econtract')
            ->layout('admin-panel::layout');
    }
}

<?php

namespace App\Livewire\Admin\Sellers\Settings;

use App\Models\SellerQuestion as SellerQuestionModel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('تنظیمات فروشندگان — سوالات پر تکرار')]
class SellerQuestion extends Component
{
    public bool $modal = false;

    public ?int $editingId = null;

    public string $question = '';

    public string $answer = '';

    public ?int $confirmingDelete = null;

    #[Computed]
    public function items()
    {
        return SellerQuestionModel::latest('id')->get();
    }

    public function openModal(): void
    {
        $this->reset(['editingId', 'question', 'answer']);
        $this->modal = true;
    }

    public function edit(int $id): void
    {
        $item = SellerQuestionModel::findOrFail($id);

        $this->editingId = $item->id;
        $this->question = (string) $item->question;
        $this->answer = (string) ($item->answer ?? '');
        $this->modal = true;
    }

    protected function rules(): array
    {
        return [
            'question' => 'required',
            'answer' => 'required|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'question.required' => 'متن سوال الزامی است.',
            'answer.required' => 'متن پاسخ الزامی است.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'question' => $this->question,
            'answer' => $this->answer,
        ];

        if ($this->editingId) {
            SellerQuestionModel::find($this->editingId)?->update($data);
            $message = 'با موفقیت ویرایش شد.';
        } else {
            SellerQuestionModel::create($data);
            $message = 'با موفقیت ایجاد شد.';
        }

        $this->modal = false;
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function delete(): void
    {
        SellerQuestionModel::find($this->confirmingDelete)?->delete();

        $this->confirmingDelete = null;
        $this->dispatch('toast', message: 'رکورد با موفقیت حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.settings.question')
            ->layout('admin-panel::layout');
    }
}

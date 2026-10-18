<?php

namespace App\Livewire\Admin\Faqs;

use App\Models\Faq;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * سوالات متداول — پورت ۱:۱ FaqController (index/store/edit/update/destroy/multipleDestroy)
 * به‌صورت جدول + مودال ایجاد/ویرایش + حذف تکی و گروهی + مرتب‌سازی ↑↓.
 *
 * انحرافات مستند:
 *  - order خالی → بزرگ‌ترین order + ۱ (قدیمی خالی را null/0 ذخیره می‌کرد) — بهبود مستند اسپک.
 *  - مرتب‌سازی ↑↓ با بازنویسی order از ۱ تا n (قدیمی endpoint مرتب‌سازی نداشت — افزودنی طبق اسپک).
 *  - published مثل فرم قدیمی با سلکت «فعال/غیرفعال» (۱/۰) می‌آید تا قانون required|boolean عیناً پورت شود.
 */
#[Title('سوالات متداول')]
class FaqIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- مودال ایجاد/ویرایش (پورت store/update/edit قدیمی) ---------------- */

    public bool $showFormModal = false;

    /** null = حالت ایجاد */
    public ?int $faqId = null;

    public string $question = '';

    public string $answer = '';

    /** مثل سلکت فرم قدیمی: '1' فعال / '0' غیرفعال */
    public string $published = '1';

    public ?int $order = null;

    /* ---------------- انتخاب گروهی (پورت multipleDestroy) ---------------- */

    public bool $selectAll = false;

    public array $selected = [];

    /* ---------------- حذف تکی ---------------- */

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('faqs.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function faqs()
    {
        // پورت: Faq::orderBy('order', 'asc')->paginate(20) — صفحه‌بندی ۱۵ تایی طبق اسپک پنل
        return Faq::query()
            ->orderBy('order', 'asc')
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    /* ---------------- مودال ---------------- */

    public function openCreateModal(): void
    {
        auth('adminPanel')->user()?->can('faqs.create') ?: abort(403);

        $this->resetForm();

        $this->showFormModal = true;
    }

    /** پورت edit قدیمی (JSON سوال) — پیش‌بارگذاری مودال ویرایش */
    public function openEditModal(int $id): void
    {
        auth('adminPanel')->user()?->can('faqs.update') ?: abort(403);

        $faq = Faq::findOrFail($id);

        $this->faqId = $faq->id;
        $this->question = (string) $faq->question;
        $this->answer = (string) $faq->answer;
        $this->published = (string) (int) $faq->published;
        $this->order = $faq->order !== null ? (int) $faq->order : null;
        $this->resetErrorBag();

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->faqId = null;
        $this->question = '';
        $this->answer = '';
        $this->published = '1';
        $this->order = null;
        $this->resetErrorBag();
    }

    /* ---------------- ذخیره (پورت ۱:۱ store/update قدیمی) ---------------- */

    public function save(): void
    {
        $isEditing = $this->faqId !== null;

        auth('adminPanel')->user()?->can($isEditing ? 'faqs.update' : 'faqs.create') ?: abort(403);

        $data = $this->validate([
            'question'  => 'required|string|max:255',
            'answer'    => 'required|string',
            'published' => 'required|boolean',
            'order'     => 'nullable|integer',
        ], [], [
            'question'  => 'سوال',
            'answer'    => 'جواب',
            'published' => 'وضعیت',
            'order'     => 'ترتیب نمایش',
        ]);

        // انحراف مستند: order خالی → بزرگ‌ترین order فعلی + ۱ (قدیمی null می‌گذاشت)
        $order = is_numeric($this->order) ? (int) $this->order : (int) Faq::max('order') + 1;

        $payload = [
            'question'  => $data['question'],
            'answer'    => $data['answer'],
            'published' => (int) $this->published, // ۱/۰ مثل سلکت فرم قدیمی
            'order'     => $order,
        ];

        if ($isEditing) {
            $faq = Faq::findOrFail($this->faqId);
            $faq->update($payload);

            $message = 'سوال متداول با موفقیت ویرایش شد.';
        } else {
            Faq::create($payload);

            $message = 'سوال متداول با موفقیت ایجاد شد.';
        }

        $this->showFormModal = false;
        $this->resetForm();

        unset($this->faqs);

        $this->dispatch('toast', message: $message, type: 'success');
    }

    /* ---------------- مرتب‌سازی (افزودنی طبق اسپک — بازنویسی order از ۱ تا n) ---------------- */

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
        auth('adminPanel')->user()?->can('faqs.update') ?: abort(403);

        $ordered = Faq::query()->orderBy('order', 'asc')->orderBy('id')->pluck('id')->values();

        $current = $ordered->search($id);

        if ($current === false) {
            return;
        }

        $target = $current + $direction;

        if (! $ordered->offsetExists($target)) {
            return; // لبه‌ی لیست
        }

        [$ordered[$current], $ordered[$target]] = [$ordered[$target], $ordered[$current]];

        foreach ($ordered as $index => $faqId) {
            Faq::query()->whereKey($faqId)->update(['order' => $index + 1]);
        }

        unset($this->faqs);

        $this->dispatch('toast', message: 'ترتیب سوالات به‌روزرسانی شد.', type: 'success');
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->faqs->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف (پورت destroy/multipleDestroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('faqs.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('faqs.delete') ?: abort(403);

        if ($faq = Faq::find($this->confirmingDelete)) {
            $faq->delete();
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        unset($this->faqs);

        $this->dispatch('toast', message: 'سوال متداول با موفقیت حذف شد.', type: 'success');
    }

    public function confirmBulkDelete(): void
    {
        auth('adminPanel')->user()?->can('faqs.delete') ?: abort(403);

        if (count($this->selected) === 0) {
            // پورت پاسخ ۴۰۰ قدیمی «موردی انتخاب نشده است.»
            $this->dispatch('toast', message: 'موردی انتخاب نشده است.', type: 'error');

            return;
        }

        $this->confirmingBulkDelete = true;
    }

    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('faqs.delete') ?: abort(403);

        // پورت ۱:۱ multipleDestroy قدیمی
        $hasSelection = count($this->selected) > 0;

        if ($hasSelection) {
            Faq::whereIn('id', $this->selected)->delete();

            $message = 'سوالات انتخاب شده با موفقیت حذف شدند.';
            $type = 'success';
        } else {
            $message = 'موردی انتخاب نشده است.';
            $type = 'error';
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        unset($this->faqs);

        $this->dispatch('toast', message: $message, type: $type);
    }

    /* ---------------- نمایش ---------------- */

    public function render()
    {
        return view('admin-panel::livewire.admin.faqs.faqs')
            ->layout('admin-panel::layout');
    }
}

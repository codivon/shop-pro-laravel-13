<?php

namespace App\Livewire\Admin\Forms;

use App\Models\Form;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست فرم‌ها')]
class FormIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    public bool $selectAll = false;

    /** @var array<int, string> */
    public array $selected = [];

    public bool $confirmingBulkDelete = false;

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('forms.index') ?: abort(403);
    }

    /** تیک «انتخاب همه» → انتخاب/خالی‌کردن ردیف‌های صفحه جاری */
    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->forms->map(fn ($form) => (string) $form->id)->values()->all()
            : [];
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function forms()
    {
        // قدیمی: Form::withCount('submissions')->orderBy('created_at', 'desc')->paginate(20)
        return Form::query()
            ->withCount('submissions')
            ->withCount('fields')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /* ---------------- حذف تکی (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('forms.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    /**
     * پورت FormController::destroy — detach تگ‌ها و حذف فرم.
     * (فیلدها/پاسخ‌ها با FK cascade حذف می‌شوند مثل قدیمی)
     */
    public function delete(): void
    {
        auth('adminPanel')->user()?->can('forms.delete') ?: abort(403);

        $form = Form::find($this->confirmingDelete);

        if ($form) {
            $form->tags()->detach();
            $form->delete();
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'فرم با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- حذف گروهی ---------------- */

    /**
     * پورت FormController::multipleDestroy — دقت: گیت قدیمی «filds.delete» است
     * (کلید عجیب مربوط به فیلدهای اختصاصی)؛ عیناً حفظ شد (۱:۱).
     */
    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('filds.delete') ?: abort(403);

        $this->validate([
            'selected'   => 'required|array',
            'selected.*' => 'exists:forms,id',
        ], [], [
            'selected' => 'موارد انتخاب‌شده',
        ]);

        foreach ($this->selected as $id) {
            $form = Form::find($id);

            if ($form) {
                $form->tags()->detach();
                $form->delete();
            }
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'فرم‌های انتخاب‌شده حذف شدند.', type: 'success');
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
        return view('admin-panel::livewire.admin.forms.forms')
            ->layout('admin-panel::layout');
    }
}

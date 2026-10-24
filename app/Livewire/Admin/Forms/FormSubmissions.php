<?php

namespace App\Livewire\Admin\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('پاسخ‌های فرم')]
class FormSubmissions extends Component
{
    use WithPagination;

    public Form $form;

    /** پاسخ در حال مشاهده در مودال جزئیات */
    public ?int $viewingSubmission = null;

    /* ---------------- انتخاب گروهی ---------------- */

    public bool $selectAll = false;

    /** @var array<int, string> */
    public array $selected = [];

    public bool $confirmingBulkDelete = false;

    public ?int $confirmingDelete = null;

    public int $perPage = 15;

    public function mount(Form $form): void
    {
        // پورت submissions قدیمی (فرم باید موجود باشد)
        auth('adminPanel')->user()?->can('forms.index') ?: abort(403);

        $this->form = $form;
    }

    /** تیک «انتخاب همه» → انتخاب/خالی‌کردن ردیف‌های صفحه جاری */
    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->submissions->map(fn ($submission) => (string) $submission->id)->values()->all()
            : [];
    }

    /* ---------------- لیست (پورت submissions قدیمی) ---------------- */

    #[Computed]
    public function submissions()
    {
        return $this->form->submissions()->orderBy('submitted_at', 'desc')->paginate($this->perPage);
    }

    /* ---------------- خلاصه‌ی data (json) برای ستون لیست ---------------- */

    public function submissionSummary(FormSubmission $submission): string
    {
        $data = json_decode((string) $submission->data, true);

        if (! is_array($data)) {
            return '—';
        }

        $values = collect($data)
            ->filter(fn ($value, $key) => $key !== 'image' && ! is_array($value))
            ->take(2)
            ->map(fn ($value) => is_string($value) ? \Illuminate\Support\Str::limit($value, 30) : (string) $value)
            ->implode(' | ');

        return $values !== '' ? $values : '—';
    }

    /* ---------------- مودال جزئیات (پورت showSubmission → submission-detail partial) ---------------- */

    #[Computed]
    public function viewingData()
    {
        if (! $this->viewingSubmission) {
            return null;
        }

        $submission = $this->form->submissions()->where('id', $this->viewingSubmission)->first();

        if (! $submission) {
            return null;
        }

        return [
            'submission' => $submission,
            // پورت json_decode($submission->data) در partial قدیمی
            'data'       => json_decode((string) $submission->data, true) ?: [],
        ];
    }

    public function openDetail(int $submissionId): void
    {
        // مشاهده‌ی پاسخ‌ها — گیت spec
        auth('adminPanel')->user()?->can('forms.submissions') ?: abort(403);

        $this->viewingSubmission = $submissionId;
    }

    public function closeDetail(): void
    {
        $this->viewingSubmission = null;
    }

    /* ---------------- حذف تکی (پورت ۱:۱ deleteSubmission قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('forms.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    /**
     * پورت FormController::deleteSubmission —
     * اگر data->image موجود و has_photo بود، فایل از دیسک public حذف می‌شود.
     */
    protected function destroySubmission(FormSubmission $submission): void
    {
        $image = json_decode((string) $submission->data);

        if (($image->image ?? null) != null && $submission->has_photo) {
            Storage::disk('public')->delete($image->image);
        }

        $submission->delete();
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('forms.delete') ?: abort(403);

        $submission = $this->form->submissions()->where('id', $this->confirmingDelete)->first();

        if ($submission) {
            $this->destroySubmission($submission);
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'پاسخ با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- حذف گروهی (پورت ۱:۱ multipleDestroySubmission قدیمی) ---------------- */

    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('forms.delete') ?: abort(403);

        $this->validate([
            'selected'   => 'required|array',
            'selected.*' => 'exists:form_submissions,id',
        ], [], [
            'selected' => 'موارد انتخاب‌شده',
        ]);

        foreach ($this->selected as $id) {
            $submission = FormSubmission::find($id);

            if ($submission) {
                // قدیمی: فرمِ پاسخ از DB خوانده و حذف با همان مسیر deleteSubmission انجام می‌شد
                $form = Form::find($submission->form_id);

                if ($form) {
                    $this->destroySubmission($submission);
                }
            }
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'پاسخ‌های انتخاب‌شده حذف شدند.', type: 'success');
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
        return view('admin-panel::livewire.admin.forms.form-submissions')
            ->layout('admin-panel::layout');
    }
}

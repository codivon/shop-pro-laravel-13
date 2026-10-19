<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Redirect;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('لیست ریدایرکت‌ها')]
class Redirects extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- مودال ایجاد (پورت create/store قدیمی) ---------------- */

    public bool $showCreateModal = false;

    public $createType = '301';

    public string $createFrom = '';

    public string $createTo = '';

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingId = null;

    public $editType = '301';

    public string $editFrom = '';

    public string $editTo = '';

    /* ---------------- حذف تکی/گروهی (پورت destroy / multipleDestroy قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public ?string $confirmingBulkDelete = null;

    public array $selected = [];

    public function mount(): void
    {
        // پورت $this->authorize('redirects.index') قدیمی
        auth('adminPanel')->user()?->can('redirects.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function rows()
    {
        return Redirect::query()
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createRedirect(): void
    {
        auth('adminPanel')->user()?->can('redirects.create') ?: abort(403);

        // رول‌ها + پیام‌های فارسی دقیق store قدیمی
        $data = $this->validate([
            'createFrom' => 'required|string|max:512|unique:redirects,from',
            'createTo'   => 'required|string|max:2048',
            'createType' => 'required|integer|in:301,302,303,403,410,503',
        ], [
            'createFrom.required' => 'آدرس مبدأ الزامی است.',
            'createFrom.unique'   => 'این آدرس مبدأ قبلاً ثبت شده است.',
            'createTo.required'   => 'آدرس مقصد الزامی است.',
            'createType.in'       => 'نوع ریدایرکت باید یکی از مقادیر 301, 302, 303, 403, 410, 503 باشد.',
        ], [
            'createFrom' => 'آدرس مبدأ',
            'createTo'   => 'آدرس مقصد',
            'createType' => 'نوع ریدایرکت',
        ]);

        Redirect::create([
            'from' => $data['createFrom'],
            'to'   => $data['createTo'],
            'type' => $data['createType'],
        ]);

        $this->reset(['showCreateModal', 'createFrom', 'createTo']);
        $this->createType = '301';

        $this->dispatch('toast', message: 'ریدایرکت با موفقیت اضافه شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ edit/update قدیمی) ---------------- */

    public function editRedirect(int $id): void
    {
        auth('adminPanel')->user()?->can('redirects.update') ?: abort(403);

        $redirect = Redirect::find($id);

        if (! $redirect) {
            return;
        }

        $this->editingId = $redirect->id;
        $this->editType  = $redirect->type;
        $this->editFrom  = (string) $redirect->from;
        $this->editTo    = (string) $redirect->to;
    }

    public function updateRedirect(): void
    {
        auth('adminPanel')->user()?->can('redirects.update') ?: abort(403);

        $redirect = $this->editingId ? Redirect::find($this->editingId) : null;

        if (! $redirect) {
            return;
        }

        // رول‌ها + پیام‌های فارسی دقیق update قدیمی (unique با ignore خود ردیف)
        $data = $this->validate([
            'editFrom' => 'required|string|max:512|unique:redirects,from,' . $redirect->id,
            'editTo'   => 'required|string|max:2048',
            'editType' => 'required|integer|in:301,302,303,403,410,503',
        ], [
            'editFrom.required' => 'آدرس مبدأ الزامی است.',
            'editFrom.unique'   => 'این آدرس مبدأ قبلاً ثبت شده است.',
            'editTo.required'   => 'آدرس مقصد الزامی است.',
            'editType.in'       => 'نوع ریدایرکت باید یکی از مقادیر 301, 302, 303, 403, 410, 503 باشد.',
        ], [
            'editFrom' => 'آدرس مبدأ',
            'editTo'   => 'آدرس مقصد',
            'editType' => 'نوع ریدایرکت',
        ]);

        $redirect->update([
            'from' => $data['editFrom'],
            'to'   => $data['editTo'],
            'type' => $data['editType'],
        ]);

        $this->reset(['editingId', 'editFrom', 'editTo']);

        $this->dispatch('toast', message: 'ریدایرکت با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- حذف تکی (پورت ۱:۱ destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('redirects.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('redirects.delete') ?: abort(403);

        $redirect = Redirect::find($this->confirmingDelete);

        if ($redirect) {
            $redirect->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'ریدایرکت با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- حذف گروهی (پورت ۱:۱ multipleDestroy قدیمی) ---------------- */

    public function confirmBulkDelete(): void
    {
        auth('adminPanel')->user()?->can('redirects.delete') ?: abort(403);

        if (count($this->selected)) {
            $this->confirmingBulkDelete = 'open';
        }
    }

    public function multipleDestroy(): void
    {
        auth('adminPanel')->user()?->can('redirects.delete') ?: abort(403);

        // پورت validate قدیمی: ids => required|array | ids.* => exists:redirects,id
        $this->validate([
            'selected'   => 'required|array',
            'selected.*' => 'required|exists:redirects,id',
        ], [], ['selected' => 'شناسه‌ها']);

        // پورت حلقه destroy قدیمی
        foreach ($this->selected as $id) {
            if ($redirect = Redirect::find($id)) {
                $redirect->delete();
            }
        }

        $this->selected = [];
        $this->confirmingBulkDelete = null;

        $this->dispatch('toast', message: 'ریدایرکت‌های انتخاب شده با موفقیت حذف شدند', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public function toggleSelectAll(): void
    {
        $ids = $this->rows->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->all();

        if (count(array_diff($ids, $this->selected)) === 0 && count($this->selected) > 0) {
            $this->selected = array_values(array_diff($this->selected, $ids));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
        }
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        try {
            return \Morilog\Jalali\Jalalian::fromDateTime($date)->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.redirects')
            ->layout('admin-panel::layout');
    }
}

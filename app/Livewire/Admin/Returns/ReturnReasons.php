<?php

namespace App\Livewire\Admin\Returns;

use App\Models\ReturnReason;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('دلایل مرجوعی')]
class ReturnReasons extends Component
{
    use WithPagination;

    /* ---------------- فرم افزودن ---------------- */

    public string $title = '';

    public string $description = '';

    public bool $isActive = true;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('returns.reasons') ?: abort(403);
    }

    /* ---------------- لیست (پورت reasonsIndex قدیمی) ---------------- */

    #[Computed]
    public function reasons()
    {
        return ReturnReason::query()
            ->withCount('returnRequests')
            ->latest()
            ->paginate(15);
    }

    /* ---------------- ایجاد (پورت reasonsStore قدیمی) ---------------- */

    public function create(): void
    {
        auth('adminPanel')->user()?->can('returns.reasons.store') ?: abort(403);

        $this->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        ReturnReason::create([
            'title'       => $this->title,
            'description' => $this->description ?: null,
            'is_active'   => $this->isActive,
            'ordering'    => (int) ReturnReason::max('ordering') + 1,
        ]);

        $this->reset(['title', 'description']);
        $this->isActive = true;

        $this->dispatch('toast', message: 'دلیل مرجوعی ایجاد شد.', type: 'success');
    }

    /* ---------------- تغییر وضعیت (پورت reasonsToggle قدیمی) ---------------- */

    public function toggle(int $id): void
    {
        auth('adminPanel')->user()?->can('returns.reasons.toggle') ?: abort(403);

        $reason = ReturnReason::find($id);

        if (! $reason) {
            $this->dispatch('toast', message: 'دلیل مرجوعی یافت نشد.', type: 'error');

            return;
        }

        $reason->update(['is_active' => ! $reason->is_active]);

        $this->dispatch('toast', message: 'وضعیت دلیل مرجوعی تغییر کرد.', type: 'success');
    }

    /* ---------------- حذف (پورت reasonsDestroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('returns.reasons.destroy') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('returns.reasons.destroy') ?: abort(403);

        $reason = ReturnReason::find($this->confirmingDelete);

        if ($reason) {
            $reason->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'دلیل مرجوعی حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.returns.reasons')
            ->layout('admin-panel::layout');
    }
}

<?php

namespace App\Livewire\Admin\Tickets;

use App\Livewire\Admin\Tickets\Concerns\InteractsWithTicketFiles;
use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست تیکت‌ها')]
class TicketIndex extends Component
{
    use WithPagination;
    use InteractsWithTicketFiles;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    public int $perPage = 15;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('tickets.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'status', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function tickets()
    {
        return Ticket::query()
            ->with([
                'user:id,first_name,last_name,username,mobile',
                'seller:id,slug,first_name,last_name',
            ])
            ->when($this->search !== '', fn ($query) => $query->where('subject', 'like', '%'.$this->search.'%'))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate($this->perPage);
    }

    public function applyFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('tickets.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('tickets.delete') ?: abort(403);

        $ticket = Ticket::find($this->confirmingDelete);

        if ($ticket) {
            // پورت destroy قدیمی: پاک‌سازی فایل‌های همه‌ی پیام‌ها از دیسک + حذف (متد مشترک trait)
            $this->deleteTicketFiles($ticket);

            // پورت FK cascade قدیمی (ticket_messages) — حذف صریح برای محیط بدون FK
            $ticket->messages()->delete();

            $ticket->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'تیکت با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- برچسب‌های فارسی (پورت priorityText/statusText مدل) ---------------- */

    public static function priorityLabel(?string $priority): string
    {
        // «hight» تایپوی حفظ‌شده از قدیمی (enum دیتابیس همین است)
        return match ($priority) {
            'hight' => 'زیاد',
            'medium' => 'متوسط',
            default => 'کم',
        };
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending'  => 'در انتظار پاسخ',
            'answered' => 'پاسخ داده شده',
            'open'     => 'باز',
            'close'    => 'بسته',
            default    => (string) $status,
        };
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.tickets.tickets')
            ->layout('admin-panel::layout');
    }
}

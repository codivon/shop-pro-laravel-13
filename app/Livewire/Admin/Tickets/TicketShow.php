<?php

namespace App\Livewire\Admin\Tickets;

use App\Livewire\Admin\Tickets\Concerns\InteractsWithTicketFiles;
use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

#[Title('مشاهده تیکت')]
class TicketShow extends Component
{
    use WithFileUploads;
    use InteractsWithTicketFiles;

    public Ticket $ticket;

    public string $status = 'pending';

    public string $reply = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $files = [];

    public function mount(Ticket $ticket): void
    {
        auth('adminPanel')->user()?->can('tickets.show') ?: abort(403);

        $this->ticket = $ticket;
        $this->status = $ticket->status ?? 'pending';
    }

    /* ---------------- گفتگو (پورت show قدیمی) ---------------- */

    #[Computed]
    public function messages()
    {
        return $this->ticket->messages()
            ->with(['files', 'admin:id,first_name,last_name'])
            ->oldest()
            ->get();
    }

    /**
     * نویسنده‌ی هر پیام: ادمین / کاربر / فروشنده (پورت شرط admin_id ویوی قدیمی).
     */
    public function messageAuthor($message): array
    {
        if ($message->admin_id) {
            return [
                'type'   => 'admin',
                'label'  => trim(($message->admin->first_name ?? '') . ' ' . ($message->admin->last_name ?? '')) ?: 'پشتیبانی',
                'avatar' => 'ادم',
            ];
        }

        // برچسب طرف: کاربر با fullname، فروشنده از sellers_info (accessor قدیمی روی
        // فروشنده‌ی بدون sellers_info کرش می‌کرد — نال‌ایمن شد)
        if ($this->ticket->user_id) {
            $type = 'user';
            $name = trim((string) ($this->ticket->user?->fullname ?? ''));
            $fallback = 'کاربر سایت';
        } else {
            $type = 'seller';
            $info = $this->ticket->seller?->seller_info;
            $name = trim((string) $info?->first_name.' '.(string) $info?->last_name);
            $fallback = 'فروشنده';
        }

        return [
            'type'   => $type,
            'label'  => $name !== '' ? $name : $fallback,
            'avatar' => mb_substr($name !== '' ? $name : '؟', 0, 2),
        ];
    }

    /* ---------------- تغییر وضعیت سریع (پورت type قدیمی) ---------------- */

    public function updatedStatus(?string $value): void
    {
        auth('adminPanel')->user()?->can('tickets.update') ?: abort(403);

        // قدیمی بدون اعتبارسنجی بود؛ اینجا مقادیر مجاز سلکت چک می‌شود (بهبود کوچک مستند)
        if (! in_array($value, ['pending', 'answered', 'open', 'close'])) {
            $this->status = $this->ticket->status;

            $this->dispatch('toast', message: 'وضعیت انتخاب شده معتبر نیست.', type: 'error');

            return;
        }

        $this->ticket->update(['status' => $value]);

        $this->dispatch('toast', message: 'وضعیت تیکت با موفقیت تغییر کرد.', type: 'success');
    }

    /* ---------------- ارسال پاسخ (پورت update قدیمی) ---------------- */

    public function sendReply(): void
    {
        auth('adminPanel')->user()?->can('tickets.update') ?: abort(403);

        $data = $this->validate(
            array_merge(['reply' => 'required|string'], $this->ticketFileRules()),
            array_merge([
                'reply.required' => 'متن پیام الزامی است.',
                'reply.string'   => 'متن پیام نامعتبر است.',
            ], $this->ticketFileMessages()),
            array_merge(['reply' => 'متن پیام'], $this->ticketFileAttributes()),
        );

        // پورت update قدیمی: ایجاد message با admin_id + وضعیت تیکت → answered
        $message = $this->ticket->messages()->create([
            'admin_id' => auth('adminPanel')->user()->id,
            'message'  => $data['reply'],
        ]);

        $this->ticket->update(['status' => 'answered']);
        $this->status = 'answered';

        $this->persistTicketFiles($message);

        $this->reset('reply', 'files');

        $this->dispatch('toast', message: 'پاسخ با موفقیت ثبت شد.', type: 'success');
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);

        $this->files = array_values($this->files);
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
        return view('admin-panel::livewire.admin.tickets.ticket-show')
            ->layout('admin-panel::layout');
    }
}

<?php

namespace App\Livewire\Admin\Tickets;

use App\Livewire\Admin\Tickets\Concerns\InteractsWithTicketFiles;
use App\Models\Seller;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('ایجاد تیکت')]
class TicketCreate extends Component
{
    use WithFileUploads;
    use InteractsWithTicketFiles;

    public string $subject = '';

    public string $priority = 'low';

    public string $message = '';

    /** نوع طرف تیکت: user یا seller (قدیمی فقط کاربر داشت — طبق اسپک فروشنده هم اضافه شد) */
    public string $recipientType = 'user';

    public ?int $recipientId = null;

    public string $recipientLabel = '';

    /** جستجوی زنده کاربر/فروشنده */
    public string $search = '';

    public array $results = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $files = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('tickets.create') ?: abort(403);
    }

    /* ---------------- جستجوی زنده کاربر/فروشنده (جایگزین select2 قدیمی) ---------------- */

    public function updatedSearch(): void
    {
        $term = trim($this->search);

        if (mb_strlen($term) < 2) {
            $this->results = [];

            return;
        }

        $like = '%'.$term.'%';

        $users = User::query()
            ->where(function ($query) use ($like) {
                $query->where('username', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->limit(10)
            ->get(['id', 'username', 'first_name', 'last_name', 'mobile'])
            ->map(fn (User $user) => [
                'id'    => (int) $user->id,
                'type'  => 'user',
                'label' => trim((string) ($user->fullname ?? '')) ?: $user->username,
                'meta'  => 'کاربر سایت'.($user->mobile ? ' — '.$user->mobile : ''),
            ]);

        // جستجوی فروشندگان با join sellers_info (ستون‌های نام روی sellers_info هستند؛
        // ستون‌های first/last خود sellers اغلب خالی‌اند — الگوی searchRecipients قدیمی)
        $sellers = Seller::query()
            ->join('sellers_info', 'sellers.id', '=', 'sellers_info.seller_id')
            ->select('sellers.id', 'sellers.slug', 'sellers.mobile', 'sellers_info.first_name', 'sellers_info.last_name')
            ->where(function ($query) use ($like) {
                $query->where('sellers_info.first_name', 'like', $like)
                    ->orWhere('sellers_info.last_name', 'like', $like)
                    ->orWhere('sellers.slug', 'like', $like)
                    ->orWhere('sellers.mobile', 'like', $like);
            })
            ->limit(5)
            ->get()
            ->map(fn (Seller $seller) => [
                'id'    => (int) $seller->id,
                'type'  => 'seller',
                'label' => trim((string) $seller->first_name.' '.(string) $seller->last_name) ?: (string) $seller->slug,
                'meta'  => 'فروشنده'.($seller->slug ? ' — '.$seller->slug : ''),
            ]);

        $this->results = array_slice(array_merge($users->all(), $sellers->all()), 0, 15);
    }

    public function selectRecipient(int $id, string $type): void
    {
        $found = collect($this->results)->first(fn (array $row) => $row['id'] === $id && $row['type'] === $type);

        if (! $found) {
            return;
        }

        $this->recipientType = $type;
        $this->recipientId = $id;
        $this->recipientLabel = $found['label'];
        $this->search = '';
        $this->results = [];
    }

    public function removeRecipient(): void
    {
        $this->recipientType = 'user';
        $this->recipientId = null;
        $this->recipientLabel = '';
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);

        $this->files = array_values($this->files);
    }

    /* ---------------- ذخیره (پورت ۱:۱ store قدیمی) ---------------- */

    public function save()
    {
        auth('adminPanel')->user()?->can('tickets.create') ?: abort(403);

        $rules = array_merge([
            'subject'  => 'required|string',
            'priority' => 'required|string|in:low,medium,hight',
            'message'  => 'required|string',
        ], $this->ticketFileRules());

        // قدیمی: user_id => required|exists:users,id — با پشتیبانی فروشنده در پنل جدید
        if ($this->recipientType === 'seller') {
            $rules['recipientId'] = 'required|exists:sellers,id';
        } else {
            $rules['recipientId'] = 'required|exists:users,id';
        }

        $data = $this->validate($rules, array_merge([
            'subject.required'   => 'موضوع الزامی است.',
            'priority.required'  => 'اولویت الزامی است.',
            'priority.in'        => 'اولویت انتخاب شده معتبر نیست.',
            'message.required'   => 'متن پیام الزامی است.',
            'recipientId.required' => 'انتخاب کاربر مربوطه الزامی است.',
            'recipientId.exists' => 'کاربر مربوطه انتخاب شده معتبر نیست.',
        ], $this->ticketFileMessages()), array_merge([
            'subject'     => 'موضوع',
            'priority'    => 'اولویت',
            'message'     => 'پیام',
            'recipientId' => 'کاربر مربوطه',
        ], $this->ticketFileAttributes()));

        // پورت store قدیمی — کلید message روی جدول tickets وجود ندارد و Eloquent
        // آن را نادیده می‌گیرد (رفتار عیناً مثل قدیمی)؛ متن پیام فقط در اولین TicketMessage ذخیره می‌شود.
        $ticket = Ticket::create([
            'subject'   => $data['subject'],
            'priority'  => $data['priority'],
            'message'   => $data['message'],
            'user_id'   => $this->recipientType === 'user' ? $data['recipientId'] : null,
            'seller_id' => $this->recipientType === 'seller' ? $data['recipientId'] : null,
        ]);

        $message = $ticket->messages()->create([
            'admin_id' => auth('adminPanel')->user()->id,
            'message'  => $data['message'],
        ]);

        $this->persistTicketFiles($message);

        $this->reset('subject', 'message', 'files', 'recipientId', 'recipientLabel');

        $this->dispatch('toast', message: 'تیکت با موفقیت ایجاد شد.', type: 'success');

        $this->redirectIntended(route('np.tickets.show', ['ticket' => $ticket->id], absolute: false), navigate: true);

        return null;
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.tickets.ticket-create')
            ->layout('admin-panel::layout');
    }
}

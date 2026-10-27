<?php

namespace App\Livewire\Seller\Tickets;

use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — مشاهده درخواست پشتیبانی + پاسخ (پورت SellerTicketController@show + update).
 *
 * گارد مالکیت: عیناً قدیم — if ($ticket->seller_id != sellerID()) abort(404).
 *
 * فرم پاسخ: کلاسیک PUT به seller.tickets.update با همان فیلدهای قدیمی
 * (message / upload_files[]) — کنترلر قدیمی response('success') می‌دهد پس
 * ارسال با fetch و در موفقیت رفرش همین صفحه انجام می‌شود
 * (هم‌الگوی data-redirect در show.js قدیمی).
 */
#[Layout('sellers.layouts.panel')]
#[Title('جزئیات درخواست')]
class Show extends Component
{
    public Ticket $ticket;

    public function mount(Ticket $ticket): void
    {
        if ($ticket->seller_id != sellerID()) {
            abort(404);
        }

        $this->ticket = $ticket;
    }

    public function render()
    {
        $ticket = $this->ticket->load([
            'messages' => fn ($q) => $q->oldest()->with(['admin:id,first_name,last_name', 'user:id,first_name,last_name,username', 'seller:id,email', 'files']),
        ]);

        return view('sellers.panel.tickets.show', [
            'ticket' => $ticket,
            'indexUrl' => route('seller.tickets.index'),
            'updateUrl' => route('seller.tickets.update', ['ticket' => $ticket]),
        ]);
    }
}

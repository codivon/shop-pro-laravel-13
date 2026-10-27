<?php

namespace App\Livewire\Seller\Tickets;

use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — درخواست‌های پشتیبانی فروشنده (پورت SellerTicketController@index).
 *
 * مبنای کوئری عیناً قدیم: Ticket::where('seller_id', sellerID())->latest()->paginate(20).
 */
#[Layout('sellers.layouts.panel')]
#[Title('درخواست پشتیبانی')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $tickets = Ticket::where('seller_id', sellerID())
            ->latest()
            ->paginate(20);

        return view('sellers.panel.tickets.index', [
            'tickets' => $tickets,
            'createUrl' => route('seller.tickets.create'),
        ]);
    }
}

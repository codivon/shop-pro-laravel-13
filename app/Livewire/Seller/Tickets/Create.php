<?php

namespace App\Livewire\Seller\Tickets;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — ثبت درخواست پشتیبانی (پورت SellerTicketController@create).
 *
 * فرم کلاسیک POST به seller.tickets.store با عیناً همان فیلدهای ویوی قدیمی
 * (subject / priority / message / upload_files[]) — کنترلر قدیمی response('success')
 * می‌دهد پس ارسال با fetch و در موفقیت بازگشت به فهرست انجام می‌شود
 * (هم‌الگوی data-redirect="seller.tickets.index" در create.js قدیمی).
 */
#[Layout('sellers.layouts.panel')]
#[Title('ثبت درخواست پشتیبانی')]
class Create extends Component
{
    public function render()
    {
        return view('sellers.panel.tickets.create', [
            'storeUrl' => route('seller.tickets.store'),
            'indexUrl' => route('seller.tickets.index'),
        ]);
    }
}

<?php

namespace Themes\SaniarTheme\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Ticket;
use App\Notifications\Ticket\TicketCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class SellerTicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('seller_id', sellerID())
            ->latest()
            ->paginate(20);

        return view('front::sellers.panel.tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('front::sellers.panel.tickets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'        => 'required|string',
            'priority'       => 'required|string',
            'message'        => 'required|string',
            'upload_files'   => 'array',
            'upload_files.*' => 'file|max:204800|mimes:png,jpeg,jpg,zip'
        ]);

        // سانیار فاز J: ستون message روی جدول tickets وجود ندارد (باگ لاتنت WeblakShop → SQL 500)؛
        // متن اولین پیام در TicketMessage ثبت می‌شود.
        $ticket = Ticket::create([
            'subject'   => $request->subject,
            'priority'  => $request->priority,
            'seller_id' => sellerID()
        ]);

        $message = $ticket->messages()->create([
            'seller_id' => sellerID(),
            'message'   => $request->message
        ]);

        if ($request->has('upload_files')) {
            foreach ($request->upload_files as $uploadFile) {
                $message->files()->create([
                    'file' => $this->storeTicketFile($uploadFile),
                ]);
            }
        }

        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new TicketCreated($ticket));

        // سانیار فاز J: فلش توست بعد از ریدایرکت JS (پشتیبانِ حالت بدون-JS)
        session()->put('toast-success', 'تیکت شما با موفقیت ثبت شد.');

        return response('success');
    }

    public function show(Ticket $ticket)
    {
        if ($ticket->seller_id != sellerID()) {
            abort(404);
        }

        return view('front::sellers.panel.tickets.show', compact('ticket'));
    }

    public function update(Ticket $ticket, Request $request)
    {
        if ($ticket->seller_id != sellerID()) {
            abort(404);
        }

        $request->validate([
            'message'        => 'required|string',
            'upload_files'   => 'array',
            'upload_files.*' => 'file|max:204800|mimes:png,jpeg,jpg,zip'
        ]);

        $message = $ticket->messages()->create([
            'seller_id' => sellerID(),
            'message'   => $request->message
        ]);

        $ticket->update([
            'status' => 'pending'
        ]);

        if ($request->has('upload_files')) {
            foreach ($request->upload_files as $uploadFile) {
                $message->files()->create([
                    'file' => $this->storeTicketFile($uploadFile),
                ]);
            }
        }

        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new TicketCreated($ticket));

        // سانیار فاز J: فلش توست بعد از ریدایرکت JS (پشتیبانِ حالت بدون-JS)
        session()->put('toast-success', 'پاسخ شما با موفقیت ثبت شد.');

        return response('success');
    }

    /**
     * سانیار فاز J — ذخیره پیوست تیکت:
     * تصاویر با uploadOptimizedImage؛ سایر فایل‌ها (zip) خام روی دیسک public
     * (باگ لاتنت WeblakShop: zip به Intervention Image می‌رسید → کرش).
     */
    private function storeTicketFile($file)
    {
        $path = 'uploads/tickets/' . jdate()->format('Y-m-d');
        if (str_starts_with($file->getMimeType(), 'image/')) {
            return uploadOptimizedImage($file, 'tickets/' . jdate()->format('Y-m-d'));
        }
        $name = 'tickets-' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        Storage::disk('public')->put($path . '/' . $name, file_get_contents($file->getRealPath()));

        return $path . '/' . $name;
    }
}

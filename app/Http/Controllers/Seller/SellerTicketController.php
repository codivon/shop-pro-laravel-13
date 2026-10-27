<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Ticket;
use App\Notifications\Ticket\TicketCreated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class SellerTicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('seller_id', sellerID())
            ->latest()
            ->paginate(20);
        return view('sellers-old.panel.tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('sellers-old.panel.tickets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'         => 'required|string',
            'priority'        => 'required|string',
            'message'         => 'required|string',
            'upload_files'    => 'array',
            'upload_files.*'  => 'file|max:204800|mimes:png,jpeg,jpg,zip'
        ]);


        $ticket = Ticket::create([
            'subject'  => $request->subject,
            'priority' => $request->priority,
            'message'  => $request->message,
            'seller_id'  => sellerID()
        ]);

        $message = $ticket->messages()->create([
            'seller_id' => sellerID(),
            'message' => $request->message
        ]);


        if ($request->has('upload_files')) {

            foreach ($request->upload_files as $upload_files) {
                $storagePatch = 'tickets/' . jdate()->format('Y-m-d');

                $name = uploadOptimizedImage($upload_files, $storagePatch);

                $message->files()->create([
                    'file' => $name,
                ]);
            }
        }


        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new TicketCreated($ticket));

        return response('success');
    }

    public function show(Ticket $ticket)
    {
        if ($ticket->seller_id != sellerID()) {
            abort(404);
        }
        return view('sellers-old.panel.tickets.show', compact('ticket'));
    }

    public function update(Ticket $ticket, Request $request)
    {

        if ($ticket->seller_id != sellerID()) {
            abort(404);
        }

        $request->validate([
            'message'         => 'required|string',
            'upload_files'    => 'array',
            'upload_files.*'  => 'file|max:204800|mimes:png,jpeg,jpg,zip'
        ]);


        $message = $ticket->messages()->create([
            'seller_id' => sellerID(),
            'message' => $request->message
        ]);

        $ticket->update([
            'status' => 'pending'
        ]);


        if ($request->has('upload_files')) {

            foreach ($request->upload_files as $upload_files) {
                $storagePatch = 'tickets/' . jdate()->format('Y-m-d');

                $name = uploadOptimizedImage($upload_files, $storagePatch);

                $message->files()->create([
                    'file' => $name,
                ]);
            }
        }

        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new TicketCreated($ticket));

        return response('success');
    }
}

<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Ticket;
use App\Notifications\Ticket\TicketCreated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('user_id', auth()->user()->id)
            ->latest()
            ->paginate(20);
        $active = "tickets";
        return view('front::user.tickets.index', compact('tickets', 'active'));
    }

    public function create()
    {
        $active = "tickets";
        return view('front::user.tickets.create', compact('active'));
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
            'user_id'  => auth()->user()->id
        ]);

        $message = $ticket->messages()->create([
            'user_id' => auth()->user()->id,
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
        if ($ticket->user_id != auth()->user()->id) {
            abort(404);
        }
        $active = "tickets";
        return view('front::user.tickets.show', compact('ticket', 'active'));
    }

    public function update(Ticket $ticket, Request $request)
    {
        if ($ticket->user_id != auth()->user()->id) {
            abort(404);
        }

        $request->validate([
            'message'         => 'required|string',
            'upload_files'    => 'array',
            'upload_files.*'  => 'file|max:204800|mimes:png,jpeg,jpg,zip'
        ]);

        $message = $ticket->messages()->create([
            'user_id' => auth()->user()->id,
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

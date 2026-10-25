<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ticket::class, 'ticket');
    }

    public function index()
    {
        $tickets = Ticket::latest()->paginate(20);

        return view('back.tickets.index', compact('tickets'));
    }

    public function show(Ticket $ticket)
    {
        return view('back.tickets.show', compact('ticket'));
    }

    public function create()
    {
        $users = User::where('id', '!=', auth('adminPanel')->user()->id)->get();

        return view('back.tickets.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'  => 'required|string',
            'priority' => 'required|string',
            'message'  => 'required|string',
            'user_id'  => 'required|exists:users,id'
        ]);

        $ticket = Ticket::create([
            'subject'  => $request->subject,
            'priority' => $request->priority,
            'message'  => $request->message,
            'user_id'  => $request->user_id
        ]);

        $message = $ticket->messages()->create([
            'admin_id' => auth('adminPanel')->user()->id,
            'message' => $request->message
        ]);

        if ($request->upload_files) {
            $upload_files = explode(',', $request->upload_files);

            foreach ($upload_files as $upload_file) {

                if (Storage::disk('public')->exists($upload_file)) {
                    $new_upload_file = explode('/', $upload_file);
                    $upload_fileCount = count($new_upload_file);
                    $lastElement = $new_upload_file[$upload_fileCount - 1];

                    $newPatch = '/uploads/tickets/' . jdate()->format('Y-m-d') . '/' . $lastElement;
                    Storage::disk('public')->move($upload_file, $newPatch);

                    $message->files()->create([
                        'file' => $newPatch,
                    ]);

                    //delete tmp
                    Storage::delete($upload_file);
                }
            }
        }

        return response('success');
    }

    public function type(Ticket $ticket, Request $request)
    {
        $ticket->update([
            'status' => $request->status
        ]);
    }

    public function update(Ticket $ticket, Request $request)
    {
        $request->validate([
            'message'  => 'required|string'
        ]);


        $message = $ticket->messages()->create([
            'admin_id' => auth('adminPanel')->user()->id,
            'message' => $request->message
        ]);

        $ticket->update([
            'status' => 'answered'
        ]);

        if ($request->upload_files) {
            $upload_files = explode(',', $request->upload_files);

            foreach ($upload_files as $upload_file) {

                if (Storage::disk('public')->exists($upload_file)) {
                    $new_upload_file = explode('/', $upload_file);
                    $upload_fileCount = count($new_upload_file);
                    $lastElement = $new_upload_file[$upload_fileCount - 1];

                    $newPatch = '/uploads/tickets/' . jdate()->format('Y-m-d') . '/' . $lastElement;
                    Storage::disk('public')->move($upload_file, $newPatch);

                    $message->files()->create([
                        'file' => $newPatch,
                    ]);
                    //delete tmp
                    Storage::delete($upload_file);
                }
            }
        }

        return response('success');
    }

    public function destroy(Ticket $ticket)
    {
        foreach ($ticket->messages as $message) {
            foreach ($message->files as $file) {
                Storage::disk('public')->delete($file->file);
                $file->delete();
            }
        }

        $ticket->delete();

        return response('success');
    }

    public function storeFile(Request $request)
    {
        $this->authorize('tickets');

        $request->validate([
            'file' => 'required|file|max:204800|mimes:png,jpeg,jpg,zip'
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (!file_exists(public_path("/uploads/tmp/" . $date))) {
            Storage::disk('public')->makeDirectory("/uploads/tmp/" . $date);
        }

        $name = uploadOptimizedImage($request->file, 'tmp');

        return response($name);
    }

    public function destoryFile(Request $request)
    {
        $this->authorize('tickets');

        $request->validate([
            'filename' => 'required'
        ]);

        // امنیتی: جلوگیری از path-traversal (مثل ../../.env)
        $filename = basename((string) $request->filename);
        abort_if($filename === '' || $filename === '.' || $filename === '..', 422);
        abort_unless(hash_equals((string) $request->filename, $filename), 422, 'نام فایل نامعتبر است.');

        Storage::delete('tmp/' . $filename);

        return response('success');
    }
}

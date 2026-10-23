<?php

namespace Themes\Saniar\src\Controllers;

use App\Models\Admin;
use App\Models\Contact;
use App\Events\ContactCreated as EventsContactCreated;
use App\Http\Controllers\Controller;
use App\Notifications\Contact\ContactCreated;
use App\Notifications\Contact\TicketCreated;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    public function index()
    {
        return view('front::contact');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'    => 'required|string|max:191',
            'email'   => 'required|string|email|max:191',
            'subject' => 'required|string|max:191',
            'captcha' => ['required', 'captcha'],
            'message' => 'required|string|max:2000',
        ]);

        $contact = Contact::create([
            'name'    => $request->name,
            'email'   => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new ContactCreated($contact));

        event(new EventsContactCreated($contact));

        return response(['message' => 'success']);
    }
}

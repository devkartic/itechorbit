<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Jobs\ContactMailerJob;
use App\Models\Contact;
use App\Models\User;

class InquiryController extends Controller
{
    public function store(ContactRequest $request)
    {

        $user = User::where('portfolio_id', $request->input('portfolio_id'))->where('portfolio_status', 1)->where('status', 1)->first();

        if (! $user) {
            abort(404);
        }

        $contact = Contact::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
        ]);

        if ($contact) {
            //          Mail send functionality with job, queue and work
            ContactMailerJob::dispatch($user, $contact, true)->delay(now()->addMinute());
        }

        return response()->json(['success' => 'Message sent successfully']);
    }
}

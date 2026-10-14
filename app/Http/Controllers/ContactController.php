<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Requests\ContactRequest;
use App\Jobs\ContactMailerJob;
use App\Models\Contact;
use App\Models\User;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $this->middleware('auth');
        PermissionController::permission_verify();

        return app(AdminPresentation::class)->render('contacts', 'index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
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
            ContactMailerJob::dispatch($user, $contact)->delay(now()->addMinute());
        }

        return response()->json(['success' => 'Message sent successfully']);
    }

    private function create_validator($request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Contact $contact)
    {
        $this->middleware('auth');
        PermissionController::permission_verify();
        User::verify_ownership($contact->user_id);

        $contact->read_status = 1;
        $contact->save();
        $email = $contact;

        return app(AdminPresentation::class)->render('contacts', 'show', compact('email'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Contact $contact)
    {
        $this->middleware('auth');
        PermissionController::permission_verify();
        User::verify_ownership($contact->user_id);
        $contact->delete();

        return response()->json(['success' => 'Message deleted successfully']);
    }
}

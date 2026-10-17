<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use App\Services\Admin\AdminPresentation;
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

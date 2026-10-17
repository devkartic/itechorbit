<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ContactInfo;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContactInfoController extends Controller
{
    /**
     * Check permission for routs, roles, links.
     *
     * @return Response or exception
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            PermissionController::permission_verify();

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return app(AdminPresentation::class)->render('contact-info', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('contact-info', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);

        ContactInfo::create([
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'descriptions' => $request->input('descriptions'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Contact created successfully']);
    }

    private function create_validator($request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'subtitle' => ['required', 'string', 'max:255'],
            'descriptions' => ['required', 'string'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(ContactInfo $contactInfo)
    {
        return app(AdminPresentation::class)->render('contact-info', 'show', compact('contactInfo'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(ContactInfo $contactInfo)
    {
        return app(AdminPresentation::class)->render('contact-info', 'edit', compact('contactInfo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, ContactInfo $contactInfo)
    {
        $this->update_validator($request);
        $contactInfo->title = $request->input('title');
        $contactInfo->subtitle = $request->input('subtitle');
        $contactInfo->descriptions = $request->input('descriptions');
        $contactInfo->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $contactInfo->save();

        return response()->json(['success' => 'Contact updated successfully']);
    }

    private function update_validator($request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'subtitle' => ['required', 'string', 'max:255'],
            'descriptions' => ['required', 'string'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(ContactInfo $contactInfo)
    {
        $contactInfo->delete();

        return response()->json(['success' => 'Contact deleted successfully']);
    }
}

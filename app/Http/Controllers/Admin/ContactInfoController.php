<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ContactInfo;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

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
        ContactInfo::create($this->validated($request));

        return response()->json(['success' => 'Contact created successfully']);
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
        $contactInfo->update($this->validated($request));

        return response()->json(['success' => 'Contact updated successfully']);
    }

    private function validated(Request $request): array
    {
        $values = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'subtitle' => ['required', 'string', 'max:255'],
            'descriptions' => ['nullable', 'string'],
            'website_url' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50', 'regex:/^\+?[0-9 () .-]+$/', 'regex:/\d/'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:50', 'regex:/^\+?[0-9 () .-]+$/', 'regex:/\d/'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_checked' => ['sometimes', Rule::in([true, false, 0, 1, '0', '1', 'true', 'false'])],
        ]);
        unset($values['is_checked']);

        return [...$values, 'descriptions' => $values['descriptions'] ?? '', 'status' => $request->boolean('is_checked')];
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

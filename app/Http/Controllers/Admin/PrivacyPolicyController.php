<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\PrivacyPolicy;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrivacyPolicyController extends Controller
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
        return app(AdminPresentation::class)->render('privacy-policies', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('privacy-policies', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);

        PrivacyPolicy::create([
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'descriptions' => $request->input('descriptions'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Privacy & policy created successfully']);
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
    public function show(PrivacyPolicy $privacyPolicy)
    {
        return app(AdminPresentation::class)->render('privacy-policies', 'show', compact('privacyPolicy'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(PrivacyPolicy $privacyPolicy)
    {
        return app(AdminPresentation::class)->render('privacy-policies', 'edit', compact('privacyPolicy'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, PrivacyPolicy $privacyPolicy)
    {
        $this->update_validator($request);
        $privacyPolicy->title = $request->input('title');
        $privacyPolicy->subtitle = $request->input('subtitle');
        $privacyPolicy->descriptions = $request->input('descriptions');
        $privacyPolicy->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $privacyPolicy->save();

        return response()->json(['success' => 'Privacy & policy updated successfully']);
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
    public function destroy(PrivacyPolicy $privacyPolicy)
    {
        $privacyPolicy->delete();

        return response()->json(['success' => 'Privacy & policy deleted successfully']);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SocialLink;
use App\Models\User;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SocialLinkController extends Controller
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
        return app(AdminPresentation::class)->render('social-links', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('social-links', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $user_id = Auth::user()->id;
        $this->create_validator($request, $user_id);

        SocialLink::create([
            'user_id' => $user_id,
            'name' => $request->input('name'),
            'social_url' => $request->input('social_url'),
            'fa_icon' => $request->input('fa_icon'),
            'order_number' => $request->input('order_number'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Social link created successfully']);
    }

    private function create_validator($request, $user_id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('social_links')
                ->where('name', $request->input('name'))
                ->where('user_id', $user_id)],
            'social_url' => ['required', 'string', 'max:255'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(SocialLink $socialLink)
    {
        User::verify_ownership($socialLink->user_id);

        return app(AdminPresentation::class)->render('social-links', 'show', compact('socialLink'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(SocialLink $socialLink)
    {
        User::verify_ownership($socialLink->user_id);

        return app(AdminPresentation::class)->render('social-links', 'edit', compact('socialLink'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, SocialLink $socialLink)
    {
        User::verify_ownership($socialLink->user_id);

        $user_id = Auth::user()->id;
        $this->update_validator($request, $socialLink, $user_id);

        $socialLink->name = $request->input('name');
        $socialLink->social_url = $request->input('social_url');
        $socialLink->fa_icon = $request->input('fa_icon');
        $socialLink->order_number = $request->input('order_number');
        $socialLink->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $socialLink->save();

        return response()->json(['success' => 'Social link updated successfully']);
    }

    private function update_validator($request, $socialLink, $user_id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('social_links')
                ->where('name', $request->input('name'))
                ->where('user_id', $user_id)
                ->whereNot('id', $socialLink->id)],
            'social_url' => ['required', 'string', 'max:255'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(SocialLink $socialLink)
    {
        User::verify_ownership($socialLink->user_id);

        $socialLink->delete();

        return response()->json(['success' => 'Social link deleted successfully']);
    }
}

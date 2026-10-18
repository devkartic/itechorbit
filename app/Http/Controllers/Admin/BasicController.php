<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Basic;
use App\Models\User;
use App\Services\Admin\AdminPresentation;
use App\Services\Shared\MediaFiles;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BasicController extends Controller
{
    private const UPLOADS = [
        'uploaded_banner_image' => ['column' => 'banner_image_path', 'directory' => 'uploads/profiles/images/banner_images'],
        'uploaded_profile_image' => ['column' => 'profile_image_path', 'directory' => 'uploads/profiles/images/profile_images'],
        'uploaded_cv' => ['column' => 'cv_path', 'directory' => 'uploads/profiles/curriculum_vitae'],
    ];

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
        return app(AdminPresentation::class)->render('basics', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $this->duplicate_validator();

        return app(AdminPresentation::class)->render('basics', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->duplicate_validator();
        $this->create_validator($request);
        MediaFiles::save(new Basic, [
            'user_id' => Auth::user()->id,
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'designation' => $request->input('designation'),
            'mobile' => $request->input('mobile'),
            'career_objective' => $request->input('career_objective'),
            'self_introduction' => $request->input('self_introduction'),
            'additional_information' => $request->input('additional_information'),
            'cv_public_status' => filter_var($request->input('cv_public_status'), FILTER_VALIDATE_BOOLEAN),
            'service_offering_title' => $request->input('service_offering_title'),
            'address' => $request->input('address'),
            'birth_date' => date('Y-m-d', strtotime($request->input('birth_date'))),
            'experience_year' => $request->input('experience_year'),
            'gender' => $request->input('gender'),
            'marital_status' => $request->input('marital_status'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ], $request, self::UPLOADS);

        return response()->json(['success' => 'Basic information created successfully']);
    }

    private function duplicate_validator(): bool
    {
        $basic = Basic::where('user_id', Auth::user()->id)->first();
        if ($basic) {
            abort(403, 'Access Forbidden!');
        }

        return true;
    }

    private function create_validator($request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'max:20'],
            'career_objective' => ['required', 'string', 'max:255'],
            'self_introduction' => ['required', 'string', 'max:255'],
            'service_offering_title' => ['required', 'string', 'max:255'],
            'additional_information' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'experience_year' => ['required', 'int', 'max:20'],
            'gender' => ['required', 'string', 'max:10'],
            'marital_status' => ['required', 'string', 'max:10'],
            'uploaded_banner_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'uploaded_profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'uploaded_cv' => ['nullable', 'file', 'mimes:pdf', 'max:5000'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Basic  $basic
     * @return Response
     */
    public function show(Basic $basic)
    {
        User::verify_ownership($basic->user_id);

        return app(AdminPresentation::class)->render('basics', 'show', compact('basic'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Basic  $basic
     * @return Response
     */
    public function edit(Basic $basic)
    {
        User::verify_ownership($basic->user_id);

        return app(AdminPresentation::class)->render('basics', 'edit', compact('basic'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\Basic  $basic
     * @return Response
     */
    public function update(Request $request, Basic $basic)
    {
        User::verify_ownership($basic->user_id);

        $this->update_validator($request);

        $basic->first_name = $request->input('first_name');
        $basic->last_name = $request->input('last_name');
        $basic->designation = $request->input('designation');
        $basic->mobile = $request->input('mobile');
        $basic->career_objective = $request->input('career_objective');
        $basic->self_introduction = $request->input('self_introduction');
        $basic->additional_information = $request->input('additional_information');
        $basic->cv_public_status = filter_var($request->input('cv_public_status'), FILTER_VALIDATE_BOOLEAN);
        $basic->service_offering_title = $request->input('service_offering_title');
        $basic->address = $request->input('address');
        $basic->birth_date = date('Y-m-d', strtotime($request->input('birth_date')));
        $basic->experience_year = $request->input('experience_year');
        $basic->gender = $request->input('gender');
        $basic->marital_status = $request->input('marital_status');
        $basic->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        MediaFiles::save($basic, $basic->getDirty(), $request, self::UPLOADS);

        return response()->json(['success' => 'Basic information updated successfully']);
    }

    private function update_validator($request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'max:20'],
            'career_objective' => ['required', 'string', 'max:255'],
            'self_introduction' => ['required', 'string', 'max:255'],
            'service_offering_title' => ['required', 'string', 'max:255'],
            'additional_information' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'experience_year' => ['required', 'int', 'max:20'],
            'gender' => ['required', 'string', 'max:10'],
            'marital_status' => ['required', 'string', 'max:10'],
            'uploaded_banner_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'uploaded_profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'uploaded_cv' => ['nullable', 'file', 'mimes:pdf', 'max:5000'],
        ]);
    }

    /**
     * Remove the specified file resource from storage.
     *
     * @param  \App\Models\Basic  $basic
     * @return Response
     */
    public function delete_file(Request $request): object
    {
        $request->validate(['id' => ['required', 'integer'], 'field_name' => ['required', Rule::in(['banner_image_path', 'profile_image_path', 'cv_path'])]]);
        $basic_id = $request->input('id');
        $field_name = $request->input('field_name');
        $basic = Basic::findOrFail($basic_id);

        User::verify_ownership($basic->user_id);

        MediaFiles::save($basic, [$field_name => null], $request, self::UPLOADS);

        return response()->json(['success' => 'File deleted successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Basic  $basic
     * @return Response
     */
    public function destroy(Basic $basic)
    {
        User::verify_ownership($basic->user_id);
        MediaFiles::destroy($basic, ['banner_image_path', 'profile_image_path', 'cv_path']);

        return response()->json(['success' => 'Basic information deleted successfully']);
    }
}

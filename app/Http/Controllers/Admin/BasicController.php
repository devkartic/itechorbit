<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Basic;
use App\Models\User;
use App\Services\AdminPresentation;
use App\Services\MediaFiles;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BasicController extends Controller
{
    protected ?string $destination_path_banner;

    protected ?string $destination_path_profile;

    protected ?string $destination_path_cv;

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
        $this->destination_path_banner = public_path('uploads/profiles/images/banner_images');
        $this->destination_path_profile = public_path('uploads/profiles/images/profile_images');
        $this->destination_path_cv = public_path('uploads/profiles/curriculum_vitae');
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
        /* Banner image upload */
        $banner_image_path = null;
        if ($request->file('uploaded_banner_image') != null && $request->file('uploaded_banner_image')->isValid()) {
            try {
                $file = $request->file('uploaded_banner_image');
                $tempName = strtolower(str_replace(' ', '', $request->input('uploaded_banner_image')));
                $banner_image = (string) Str::uuid().'.'.$file->extension();
                $banner_image_path = 'uploads/profiles/images/banner_images/'.$banner_image;
                $file->move($this->destination_path_banner, $banner_image);
            } catch (FileNotFoundException $e) {

            }
        }
        /* Profile image upload */
        $profile_image_path = null;
        if ($request->file('uploaded_profile_image') != null && $request->file('uploaded_profile_image')->isValid()) {
            try {
                $file = $request->file('uploaded_profile_image');
                $tempName = strtolower(str_replace(' ', '', $request->input('uploaded_profile_image')));
                $profile_image = (string) Str::uuid().'.'.$file->extension();
                $profile_image_path = 'uploads/profiles/images/profile_images/'.$profile_image;
                $file->move($this->destination_path_profile, $profile_image);
            } catch (FileNotFoundException $e) {

            }
        }

        /* CV upload */
        $cv_path = null;
        if ($request->file('uploaded_cv') != null && $request->file('uploaded_cv')->isValid()) {
            try {
                $file = $request->file('uploaded_cv');
                $tempName = strtolower(str_replace(' ', '', $request->input('uploaded_cv')));
                $cv_pdf = (string) Str::uuid().'.'.$file->extension();
                $cv_path = 'uploads/profiles/curriculum_vitae/'.$cv_pdf;
                $file->move($this->destination_path_cv, $cv_pdf);
            } catch (FileNotFoundException $e) {

            }
        }

        Basic::create([
            'user_id' => Auth::user()->id,
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'designation' => $request->input('designation'),
            'mobile' => $request->input('mobile'),
            'career_objective' => $request->input('career_objective'),
            'self_introduction' => $request->input('self_introduction'),
            'additional_information' => $request->input('additional_information'),
            'banner_image_path' => $banner_image_path,
            'profile_image_path' => $profile_image_path,
            'cv_path' => $cv_path,
            'cv_public_status' => filter_var($request->input('cv_public_status'), FILTER_VALIDATE_BOOLEAN),
            'service_offering_title' => $request->input('service_offering_title'),
            'address' => $request->input('address'),
            'birth_date' => date('Y-m-d', strtotime($request->input('birth_date'))),
            'experience_year' => $request->input('experience_year'),
            'gender' => $request->input('gender'),
            'marital_status' => $request->input('marital_status'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

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
            'uploaded_cv' => ['mimes:pdf', 'max:5000'],
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
        //
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

        /* Banner image Upload */
        $old_file_banner_image = $basic->banner_image_path;
        $banner_image_path = null;
        if ($request->file('uploaded_banner_image') != null && $request->file('uploaded_banner_image')->isValid()) {
            try {
                $file = $request->file('uploaded_banner_image');
                $tempName = strtolower(str_replace(' ', '', $request->input('uploaded_banner_image')));
                $banner_image = (string) Str::uuid().'.'.$file->extension();
                $banner_image_path = 'uploads/profiles/images/banner_images/'.$banner_image;
                $file->move($this->destination_path_banner, $banner_image);
                if ($old_file_banner_image) {
                    MediaFiles::delete($old_file_banner_image);
                }
            } catch (FileNotFoundException $e) {

            }
        }

        /* Profile image Upload */
        $old_file_profile_image = $basic->profile_image_path;
        $profile_image_path = null;
        if ($request->file('uploaded_profile_image') != null && $request->file('uploaded_profile_image')->isValid()) {
            try {
                $file = $request->file('uploaded_profile_image');
                $tempName = strtolower(str_replace(' ', '', $request->input('uploaded_profile_image')));
                $profile_image = (string) Str::uuid().'.'.$file->extension();
                $profile_image_path = 'uploads/profiles/images/profile_images/'.$profile_image;
                $file->move($this->destination_path_profile, $profile_image);
                if ($old_file_profile_image) {
                    MediaFiles::delete($old_file_profile_image);
                }
            } catch (FileNotFoundException $e) {

            }
        }
        /* CV Upload */
        $old_file_cv = $basic->cv_path;
        $cv_path = null;
        if ($request->file('uploaded_cv') != null && $request->file('uploaded_cv')->isValid()) {
            try {
                $file = $request->file('uploaded_cv');
                $tempName = strtolower(str_replace(' ', '', $request->input('uploaded_cv')));
                $cv_pdf = (string) Str::uuid().'.'.$file->extension();
                $cv_path = 'uploads/profiles/curriculum_vitae/'.$cv_pdf;
                $file->move($this->destination_path_cv, $cv_pdf);
                if ($old_file_cv) {
                    MediaFiles::delete($old_file_cv);
                }
            } catch (FileNotFoundException $e) {

            }
        }

        $basic->first_name = $request->input('first_name');
        $basic->last_name = $request->input('last_name');
        $basic->designation = $request->input('designation');
        $basic->mobile = $request->input('mobile');
        $basic->career_objective = $request->input('career_objective');
        $basic->self_introduction = $request->input('self_introduction');
        $basic->additional_information = $request->input('additional_information');
        if (! empty($banner_image_path)) {
            $basic->banner_image_path = $banner_image_path;
        }
        if (! empty($profile_image_path)) {
            $basic->profile_image_path = $profile_image_path;
        }
        if (! empty($cv_path)) {
            $basic->cv_path = $cv_path;
        }
        $basic->cv_public_status = filter_var($request->input('cv_public_status'), FILTER_VALIDATE_BOOLEAN);
        $basic->service_offering_title = $request->input('service_offering_title');
        $basic->address = $request->input('address');
        $basic->birth_date = date('Y-m-d', strtotime($request->input('birth_date')));
        $basic->experience_year = $request->input('experience_year');
        $basic->gender = $request->input('gender');
        $basic->marital_status = $request->input('marital_status');
        $basic->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $basic->save();

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
            'uploaded_cv' => ['mimes:pdf', 'max:5000'],
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

        if ($basic) {
            $old_file = $basic->$field_name;
            if ($old_file) {
                MediaFiles::delete($old_file);
            }
            $basic->$field_name = null;
            $basic->save();
        }

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
        //
    }
}

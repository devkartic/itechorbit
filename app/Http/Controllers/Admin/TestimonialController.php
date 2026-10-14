<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Testimonial;
use App\Models\User;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TestimonialController extends Controller
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
        return app(AdminPresentation::class)->render('testimonials', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('testimonials', 'create');
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

        Testimonial::create([
            'user_id' => $user_id,
            'name' => $request->input('name'),
            'designation' => $request->input('designation'),
            'organization' => $request->input('organization'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'ratings' => $request->input('ratings'),
            'order_number' => $request->input('order_number'),
            'comment' => $request->input('comment'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Testimonial created successfully']);
    }

    private function create_validator($request, $user_id)
    {

        $rules = Rule::unique('testimonials')
            ->where('designation', $request->input('designation'))
            ->where('organization', $request->input('organization'))
            ->where('user_id', $user_id);

        $request->validate([
            'name' => ['required', 'string', 'max:255', $rules],
            'designation' => ['required', 'string', 'max:255', $rules],
            'organization' => ['required', 'string', 'max:255', $rules],
            'email' => ['required', 'email', 'max:100'],
            'mobile' => ['required', 'string', 'max:20'],
            'ratings' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Testimonial $testimonial)
    {
        User::verify_ownership($testimonial->user_id);

        return app(AdminPresentation::class)->render('testimonials', 'show', compact('testimonial'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Testimonial $testimonial)
    {
        User::verify_ownership($testimonial->user_id);

        return app(AdminPresentation::class)->render('testimonials', 'edit', compact('testimonial'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Testimonial $testimonial)
    {
        User::verify_ownership($testimonial->user_id);

        $user_id = Auth::user()->id;
        $this->update_validator($request, $testimonial, $user_id);

        $testimonial->name = $request->input('name');
        $testimonial->designation = $request->input('designation');
        $testimonial->organization = $request->input('organization');
        $testimonial->email = $request->input('email');
        $testimonial->mobile = $request->input('mobile');
        $testimonial->ratings = $request->input('ratings');
        $testimonial->order_number = $request->input('order_number');
        $testimonial->comment = $request->input('comment');
        $testimonial->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $testimonial->save();

        return response()->json(['success' => 'Testimonial updated successfully']);
    }

    private function update_validator($request, $testimonial, $user_id)
    {
        $rules = Rule::unique('testimonials')
            ->where('name', $request->input('name'))
            ->where('designation', $request->input('designation'))
            ->where('organization', $request->input('organization'))
            ->where('user_id', $user_id)
            ->whereNot('id', $testimonial->id);

        $request->validate([
            'name' => ['required', 'string', 'max:255', $rules],
            'designation' => ['required', 'string', 'max:255', $rules],
            'organization' => ['required', 'string', 'max:255', $rules],
            'email' => ['required', 'email', 'max:100'],
            'mobile' => ['required', 'string', 'max:20'],
            'ratings' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Testimonial $testimonial)
    {
        User::verify_ownership($testimonial->user_id);

        $testimonial->delete();

        return response()->json(['success' => 'Testimonial deleted successfully']);
    }
}

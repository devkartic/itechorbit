<?php

namespace App\Http\Controllers\Admin;

use App\CustomHelpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Experience;
use App\Models\User;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExperienceController extends Controller
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
        return app(AdminPresentation::class)->render('experiences', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('experiences', 'create');
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

        Experience::create([
            'user_id' => Auth::user()->id,
            'designation' => $request->input('designation'),
            'fa_icon' => $request->input('fa_icon'),
            'organization' => $request->input('organization'),
            'responsibilities' => $request->input('responsibilities'),
            'descriptions' => $request->input('descriptions'),
            'join_date' => Helper::date_format($request->input('join_date')),
            'resign_date' => filter_var($request->input('is_continuing'), FILTER_VALIDATE_BOOLEAN) ? null : Helper::date_format($request->input('resign_date')),
            'order_number' => $request->input('order_number'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Experience created successfully']);
    }

    private function create_validator($request, $user_id)
    {

        $rules = Rule::unique('experiences')
            ->where('designation', $request->input('designation'))
            ->where('organization', $request->input('organization'))
            ->where('user_id', $user_id);

        $resign_date_rule = 'required';

        if (filter_var($request->input('is_continuing'), FILTER_VALIDATE_BOOLEAN)) {
            $resign_date_rule = 'nullable';
        }

        $request->validate([
            'designation' => ['required', 'string', 'max:255', $rules],
            'organization' => ['required', 'string', 'max:255', $rules],
            'join_date' => ['required', 'date'],
            'resign_date' => [$resign_date_rule, 'date', 'after_or_equal:join_date'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Experience $experience)
    {
        User::verify_ownership($experience->user_id);

        return app(AdminPresentation::class)->render('experiences', 'show', compact('experience'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Experience $experience)
    {
        User::verify_ownership($experience->user_id);

        return app(AdminPresentation::class)->render('experiences', 'edit', compact('experience'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Experience $experience)
    {
        User::verify_ownership($experience->user_id);

        $user_id = Auth::user()->id;
        $this->update_validator($request, $experience, $user_id);

        $experience->designation = $request->input('designation');
        $experience->fa_icon = $request->input('fa_icon');
        $experience->organization = $request->input('organization');
        $experience->responsibilities = $request->input('responsibilities');
        $experience->descriptions = $request->input('descriptions');
        $experience->join_date = Helper::date_format($request->input('join_date'));
        $resign_date = Helper::date_format($request->input('resign_date'));
        if (filter_var($request->input('is_continuing'), FILTER_VALIDATE_BOOLEAN)) {
            $resign_date = null;
        }
        $experience->resign_date = $resign_date;
        $experience->order_number = $request->input('order_number');
        $experience->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $experience->save();

        return response()->json(['success' => 'Experience updated successfully']);
    }

    private function update_validator($request, $experience, $user_id)
    {

        $rules = Rule::unique('experiences')
            ->where('designation', $request->input('designation'))
            ->where('organization', $request->input('organization'))
            ->where('user_id', $user_id)
            ->whereNot('id', $experience->id);

        $resign_date_rule = 'required';

        if (filter_var($request->input('is_continuing'), FILTER_VALIDATE_BOOLEAN)) {
            $resign_date_rule = 'nullable';
        }

        $request->validate([
            'designation' => ['required', 'string', 'max:255', $rules],
            'organization' => ['required', 'string', 'max:255', $rules],
            'join_date' => ['required', 'date'],
            'resign_date' => [$resign_date_rule, 'date', 'after_or_equal:join_date'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Experience $experience)
    {
        User::verify_ownership($experience->user_id);

        $experience->delete();

        return response()->json(['success' => 'Experience deleted successfully']);
    }
}

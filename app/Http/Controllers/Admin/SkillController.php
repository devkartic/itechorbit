<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Skill;
use App\Models\User;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SkillController extends Controller
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
        return app(AdminPresentation::class)->render('skills', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('skills', 'create');
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

        Skill::create([
            'user_id' => $user_id,
            'name' => $request->input('name'),
            'fa_icon' => $request->input('fa_icon'),
            'order_number' => $request->input('order_number'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Skill created successfully']);
    }

    private function create_validator($request, $user_id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('skills')
                ->where('name', $request->input('name'))
                ->where('user_id', $user_id)],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Skill $skill)
    {
        User::verify_ownership($skill->user_id);

        return app(AdminPresentation::class)->render('skills', 'show', compact('skill'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Skill $skill)
    {
        User::verify_ownership($skill->user_id);

        return app(AdminPresentation::class)->render('skills', 'edit', compact('skill'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Skill $skill)
    {
        User::verify_ownership($skill->user_id);

        $user_id = Auth::user()->id;
        $this->update_validator($request, $skill, $user_id);

        $skill->name = $request->input('name');
        $skill->fa_icon = $request->input('fa_icon');
        $skill->order_number = $request->input('order_number');
        $skill->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $skill->save();

        return response()->json(['success' => 'Skill updated successfully']);
    }

    private function update_validator($request, $skill, $user_id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('skills')
                ->where('name', $request->input('name'))
                ->where('user_id', $user_id)
                ->whereNot('id', $skill->id)],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Skill $skill)
    {
        User::verify_ownership($skill->user_id);

        abort_if($skill->projects()->exists(), 422, 'Reassign or remove related records before deleting this record.');
        $skill->delete();

        return response()->json(['success' => 'Skill deleted successfully']);
    }
}

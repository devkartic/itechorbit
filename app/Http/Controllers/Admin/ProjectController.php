<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\Skill;
use App\Models\User;
use App\Services\Admin\AdminPresentation;
use App\Services\Shared\MediaFiles;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const UPLOADS = [
        'uploaded_image' => ['column' => 'image_path', 'directory' => 'uploads/projects/images'],
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
        return app(AdminPresentation::class)->render('projects', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $skills = Auth::user()->skills->sortBy('name');

        return app(AdminPresentation::class)->render('projects', 'create', compact('skills'));
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

        MediaFiles::save(new Project, [
            'user_id' => $user_id,
            'name' => $request->input('name'),
            'project_category' => $request->input('project_category'),
            'client_name' => $request->input('client_name'),
            'skill_id' => $request->input('skill_id'),
            'jobs' => $request->input('jobs'),
            'technology_used' => Skill::skills_to_json($request->input('technology_used')),
            'played_role' => $request->input('played_role'),
            'project_url' => $request->input('project_url'),
            'order_number' => $request->input('order_number'),
            'about_project' => $request->input('about_project'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ], $request, self::UPLOADS);

        return response()->json(['success' => 'Project created successfully']);
    }

    private function create_validator($request, $user_id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('projects')
                ->where('name', $request->input('name'))
                ->where('client_name', $request->input('client_name'))
                ->where('user_id', $user_id)],
            'client_name' => ['required', 'string', 'max:255'],
            'skill_id' => ['required', 'integer', Rule::exists('skills', 'id')->where('user_id', $user_id)],
            'about_project' => ['required', 'string', 'max:255'],
            'uploaded_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'project_url' => ['nullable', 'url:http,https', 'max:255'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Project $project)
    {
        User::verify_ownership($project->user_id);

        return app(AdminPresentation::class)->render('projects', 'show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Project $project)
    {
        User::verify_ownership($project->user_id);
        $skills = Auth::user()->skills->sortBy('name');

        return app(AdminPresentation::class)->render('projects', 'edit', compact('skills', 'project'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Project $project)
    {
        User::verify_ownership($project->user_id);
        $user_id = $project->user_id;
        $this->update_validator($request, $project, $user_id);

        $project->name = $request->input('name');
        $project->project_category = $request->input('project_category');
        $project->client_name = $request->input('client_name');
        $project->skill_id = $request->input('skill_id');
        $project->jobs = $request->input('jobs');
        $project->technology_used = Skill::skills_to_json($request->input('technology_used'));
        $project->played_role = $request->input('played_role');
        $project->project_url = $request->input('project_url');
        $project->order_number = $request->input('order_number');
        $project->about_project = $request->input('about_project');
        $project->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        MediaFiles::save($project, $project->getDirty(), $request, self::UPLOADS);

        return response()->json(['success' => 'Project updated successfully']);
    }

    private function update_validator($request, $project, $user_id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('projects')
                ->where('name', $request->input('name'))
                ->where('client_name', $request->input('client_name'))
                ->where('user_id', $user_id)
                ->whereNot('id', $project->id)],
            'client_name' => ['required', 'string', 'max:255'],
            'skill_id' => ['required', 'integer', Rule::exists('skills', 'id')->where('user_id', $user_id)],
            'about_project' => ['required', 'string', 'max:255'],
            'uploaded_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'project_url' => ['nullable', 'url:http,https', 'max:255'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified file resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return Response
     */
    public function delete_file(Request $request): object
    {
        $request->validate(['id' => ['required', 'integer'], 'field_name' => ['required', Rule::in(['image_path'])]]);
        $project_id = $request->input('id');
        $field_name = $request->input('field_name');
        $project = Project::findOrFail($project_id);

        User::verify_ownership($project->user_id);

        MediaFiles::save($project, [$field_name => null], $request, self::UPLOADS);

        return response()->json(['success' => 'File deleted successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Project $project)
    {
        User::verify_ownership($project->user_id);
        MediaFiles::destroy($project, ['image_path']);

        return response()->json(['success' => 'Project deleted successfully']);
    }
}

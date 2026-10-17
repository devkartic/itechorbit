<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Link;
use App\Models\Admin\Module;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LinkController extends Controller
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
        return app(AdminPresentation::class)->render('links', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $modules = Module::all();

        return app(AdminPresentation::class)->render('links', 'create', compact('modules'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);

        Link::create([
            'name' => $request->input('name'),
            'url' => $request->input('link_url'),
            'fa_icon' => $request->input('fa_icon'),
            'module_id' => $request->input('module_id'),
            'order_number' => $request->input('order_number'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Module created successfully']);
    }

    private function create_validator($request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:links,name'],
            'link_url' => ['required', 'string', 'max:255', 'regex:#^[a-z][a-z0-9-]*(?:/[a-z0-9-]+)*$#D'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Link $link)
    {
        return app(AdminPresentation::class)->render('links', 'show', compact('link'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Link $link)
    {
        $modules = Module::all();

        return app(AdminPresentation::class)->render('links', 'edit', compact('modules', 'link'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Link $link)
    {
        $this->update_validator($request, $link);

        $link->name = $request->input('name');
        $link->url = $request->input('link_url');
        $link->fa_icon = $request->input('fa_icon');
        $link->module_id = $request->input('module_id');
        $link->order_number = $request->input('order_number');
        $link->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $link->save();

        return response()->json(['success' => 'Link updated successfully']);
    }

    private function update_validator($request, $link)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', "unique:links,name, $link->id"],
            'link_url' => ['required', 'string', 'max:255', 'regex:#^[a-z][a-z0-9-]*(?:/[a-z0-9-]+)*$#D'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Link $link)
    {
        $link->delete();

        return response()->json(['success' => 'Link deleted successfully']);
    }
}

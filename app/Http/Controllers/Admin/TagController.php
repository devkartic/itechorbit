<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Tag;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller
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
        return app(AdminPresentation::class)->render('tags', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('tags', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);

        Tag::create([
            'name' => $request->input('name'),
            'order_number' => $request->input('order_number'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Tag created successfully']);
    }

    private function create_validator($request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tags,name'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Tag $tag)
    {
        return app(AdminPresentation::class)->render('tags', 'show', compact('tag'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Tag $tag)
    {
        return app(AdminPresentation::class)->render('tags', 'edit', compact('tag'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Tag $tag)
    {
        $this->update_validator($request, $tag);

        $tag->name = $request->input('name');
        $tag->order_number = $request->input('order_number');
        $tag->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $tag->save();

        return response()->json(['success' => 'Tag updated successfully']);
    }

    private function update_validator($request, $tag)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', "unique:tags,name, $tag->id"],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json(['success' => 'Tag deleted successfully']);
    }
}

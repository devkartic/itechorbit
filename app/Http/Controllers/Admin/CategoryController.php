<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
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
        return app(AdminPresentation::class)->render('categories', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('categories', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);

        Category::create([
            'name' => $request->input('name'),
            'title' => $request->input('title'),
            'fa_icon' => $request->input('fa_icon'),
            'order_number' => $request->input('order_number'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Category created successfully']);

    }

    private function create_validator($request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:categories,name'],
            'title' => ['required', 'string', 'max:255'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Category $category)
    {
        return app(AdminPresentation::class)->render('categories', 'show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Category $category)
    {
        return app(AdminPresentation::class)->render('categories', 'edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Category $category)
    {
        $this->update_validator($request, $category);

        $category->name = $request->input('name');
        $category->title = $request->input('title');
        $category->fa_icon = $request->input('fa_icon');
        $category->order_number = $request->input('order_number');
        $category->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $category->save();

        return response()->json(['success' => 'Category updated successfully']);
    }

    private function update_validator($request, $category)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', "unique:categories,name, $category->id"],
            'title' => ['required', 'string', 'max:255'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Category $category)
    {
        abort_if($category->posts()->exists(), 422, 'Reassign or remove related records before deleting this record.');
        $category->delete();

        return response()->json(['success' => 'Category deleted successfully']);
    }
}

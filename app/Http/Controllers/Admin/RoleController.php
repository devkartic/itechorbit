<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Role;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleController extends Controller
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
        return app(AdminPresentation::class)->render('roles', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return app(AdminPresentation::class)->render('roles', 'create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);

        Role::create([
            'name' => $request->input('name'),
            'order_number' => $request->input('order_number'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json(['success' => 'Role created successfully']);

    }

    private function create_validator($request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Role $role)
    {
        return app(AdminPresentation::class)->render('roles', 'show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Role $role)
    {
        return app(AdminPresentation::class)->render('roles', 'edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Role $role)
    {
        $this->update_validator($request, $role);

        $role->name = $request->input('name');
        $role->order_number = $request->input('order_number');
        $role->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $role->save();

        return response()->json(['success' => 'Role updated successfully']);
    }

    private function update_validator($request, $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', "unique:roles,name, $role->id"],
            'order_number' => ['integer', 'nullable', 'max:100'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Role $role)
    {
        abort_if($role->users()->exists(), 422, 'Reassign or remove related records before deleting this record.');
        $role->delete();

        return response()->json(['success' => 'Role deleted successfully']);
    }
}

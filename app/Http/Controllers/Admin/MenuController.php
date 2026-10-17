<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Menu;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => PermissionController::permission_verify() ? $next($request) : abort(403));
    }

    public function index()
    {
        return app(AdminPresentation::class)->render('menus', 'index');
    }

    public function create()
    {
        return app(AdminPresentation::class)->render('menus', 'create');
    }

    public function show(Menu $menu)
    {
        return app(AdminPresentation::class)->render('menus', 'show', compact('menu'));
    }

    public function edit(Menu $menu)
    {
        return app(AdminPresentation::class)->render('menus', 'edit', compact('menu'));
    }

    public function store(Request $request)
    {
        Menu::create($this->validated($request));

        return response()->json(['success' => 'Navigation group created.']);
    }

    public function update(Request $request, Menu $menu)
    {
        $menu->update($this->validated($request, $menu));

        return response()->json(['success' => 'Navigation group updated.']);
    }

    private function validated(Request $request, ?Menu $menu = null): array
    {
        $values = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('menus')->ignore($menu)], 'fa_icon' => ['nullable', 'string', 'max:255'], 'order_number' => ['nullable', 'integer', 'min:0', 'max:100'], 'is_checked' => ['nullable', 'in:true,false,1,0']]);

        return [...$values, 'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN)];
    }

    public function destroy(Menu $menu)
    {
        abort_if($menu->modules()->exists(), 422, 'Move or remove the modules in this group first.');
        $menu->delete();

        return response()->json(['success' => 'Navigation group deleted.']);
    }
}

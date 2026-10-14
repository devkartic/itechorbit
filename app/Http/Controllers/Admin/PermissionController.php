<?php

namespace App\Http\Controllers\Admin;

use App\CacheRepositories\Menus;
use App\Http\Controllers\Controller;
use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\Admin\Route;
use App\Models\User;
use App\Services\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PermissionController extends Controller
{
    /**
     * Check permission for routs, roles, links.
     *
     * @return Response or exception
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            self::permission_verify();

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
        $request = request();
        $validated = $request->validate(['role_id' => ['nullable', 'integer', 'exists:roles,id']]);
        $roles = Role::orderBy('id')->get(['id', 'name']);
        $roleId = (int) ($validated['role_id'] ?? $roles->first()?->id ?? 0);

        return Inertia::render('Admin/Permissions', [
            'navigation' => app(AdminPresentation::class)->navigation(),
            'roles' => $roles, 'roleId' => $roleId,
            'links' => $this->permissionLinks($roleId),
        ]);
    }

    /**
     * Get the specified resource from storage.
     *
     * @param  Permission  $permission
     * @return Response
     */
    public function get_permissions(Request $request)
    {
        $request->validate(['role_id' => ['required', 'integer', 'exists:roles,id']]);

        return response()->json(['links' => $this->permissionLinks((int) $request->input('role_id'))]);
    }

    private function permissionLinks(int $roleId): array
    {
        return Link::orderBy('id')->get()->map(function ($link) use ($roleId) {
            $item = $link->only(['id', 'name']);
            foreach (['index', 'create', 'edit', 'destroy'] as $action) {
                $item[$action] = $roleId ? Permission::has_permission($link->id, $action, $roleId) : false;
            }

            return $item;
        })->all();
    }

    /**
     * Create or remove the specified permission for specified roles, links and routes.
     *
     * @param  Permission  $permission
     * @return Response
     */
    public function permission_handler(Request $request)
    {
        $input = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'link_id' => ['required', 'integer', 'exists:links,id'],
            'route_name' => ['required', Rule::in(['index', 'create', 'edit', 'destroy'])],
            'is_checked' => ['required', 'boolean'],
        ]);
        abort_if((int) $input['role_id'] === Role::$default_admin, 403, 'Super-admin permissions cannot be changed.');
        $route = Route::where('name', $input['route_name'])->firstOrFail();
        $keys = ['role_id' => $input['role_id'], 'link_id' => $input['link_id'], 'route_id' => $route->id];
        if ($input['is_checked']) {
            Permission::firstOrCreate($keys);
        } else {
            Permission::where($keys)->delete();
        }
        Menus::cache_forget($input['role_id']);

        return response()->json(['success' => 'Permission updated.']);
    }

    /**
     * Verify the specified permission for specified roles, links and routes.
     *
     * @param  Permission  $permission
     * @return Response
     */
    public static function permission_verify(): bool
    {
        self::initial_verification(Auth::user());
        $link_id = null;
        $route_resource_name_array = explode('.', request()->route()->getName());
        $link_name = $route_resource_name_array[0];
        $has_link = Link::where('url', $link_name)->first();
        if ($has_link) {
            $link_id = $has_link->id;
        }
        $route_name = self::routes_verify($route_resource_name_array[1]);
        $hasPermission = Permission::has_permission($link_id, $route_name);
        if ($hasPermission) {
            return true;
        }
        abort(403, 'Access Forbidden!');
    }

    public static function routes_verify($route_name): string
    {
        return match ($route_name) {
            'show', 'view' => 'index',
            'store', 'store_or_destroy' => 'create',
            'update', 'delete-files' => 'edit',
            default => $route_name,
        };
    }

    public static function initial_verification($user): object
    {
        if (! $user) {
            abort(401, 'Unauthenticated');
        }
        if (empty($user->status)) {
            abort(403, 'Profile has been disabled.');
        }
        if (User::verify_default_admin()) {
            return $user;
        }
        if (empty(Auth::user()->email_verified_at)) {
            abort(403, 'Access Unauthorized! Email not verified.');
        }
        if (empty(Auth::user()->status)) {
            abort(403, 'Access Forbidden! Profile has been disabled.');
        }

        return $user;
    }
}

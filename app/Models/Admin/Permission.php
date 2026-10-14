<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'link_id',
        'route_id',
    ];

    /**
     * Get the role for the permission.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the route for the permission.
     */
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public static function has_permission($link_id, $route_name, $role_id = null): bool
    {
        try {
            if (empty($role_id)) {
                $role_id = Auth::user()->role_id;
            }
        } catch (\Throwable $ex) {
            redirect('admin.auth.login');
        }

        if ($role_id == Role::$default_admin) {
            return true;
        }

        $permission = Permission::select([
            'permissions.*',
            'routes.name as route',
        ])->leftjoin('routes', 'routes.id', '=', 'permissions.route_id')
            ->Where('permissions.link_id', '=', $link_id)
            ->Where('permissions.role_id', '=', $role_id)
            ->Where('routes.name', '=', $route_name)
            ->first();

        if ($permission) {
            return true;
        }

        return false;
    }
}

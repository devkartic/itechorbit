<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'fa_icon',
        'order_number',
        'status',
    ];

    /**
     * Get the modules for the menu.
     */
    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Get the verified menu for logged in user
     */
    public static function sidebar_verified_menu($order_by): object
    {
        $collections = self::with('modules.links')->orderBy($order_by)->get();
        foreach ($collections as $menu) {
            $menu->has_permission = false;
            $modules = $menu->modules;
            foreach ($modules as $module) {
                $module->has_permission = false;
                $links = $module->links;
                foreach ($links as $link) {
                    $link->has_permission = Permission::has_permission($link->id, 'index');
                    if (($menu->has_permission === false || $module->has_permission === false) && $link->has_permission) {
                        $menu->has_permission = $module->has_permission = true;
                    }
                }
            }
        }

        return $collections;
    }
}

<?php

namespace App\CacheRepositories;

use App\Models\Admin\Menu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Menus extends CacheHandler
{
    public static string $key = 'FOR-ROLE';

    public static function verified_menus($order_by): object
    {
        $items = Cache::remember(self::get_cache_key(), now()->addMinutes(5), function () use ($order_by) {
            return Menu::sidebar_verified_menu($order_by)->toArray();
        });

        return collect(json_decode(json_encode($items)));

    }

    public static function get_cache_key($role_id = null): string
    {
        if (empty($role_id)) {
            $role_id = Auth::user()->role_id;
        }

        return self::cache_key(self::$key."-$role_id");
    }

    public static function cache_forget($role_id)
    {
        Cache::forget(self::get_cache_key($role_id));
    }
}

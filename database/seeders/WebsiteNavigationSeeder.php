<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WebsiteNavigationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            $module = DB::table('modules')->where('name', 'Site Control')->value('id');
            if (! $module) {
                $menu = DB::table('menus')->where('name', 'Blog Section')->value('id')
                    ?? DB::table('menus')->insertGetId(['name' => 'Blog Section', 'status' => 1, 'created_at' => $now, 'updated_at' => $now]);
                $module = DB::table('modules')->insertGetId(['menu_id' => $menu, 'name' => 'Site Control', 'status' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
            foreach (['service-info' => 'Services', 'product-info' => 'Products', 'website-technologies' => 'Technologies'] as $path => $label) {
                $link = DB::table('links')->whereIn('url', [$path, '/'.$path])->first();
                $data = ['module_id' => $module, 'name' => $label, 'updated_at' => $now];
                if ($link) {
                    // Preserve link IDs, permission assignments and enabled state.
                    DB::table('links')->where('id', $link->id)->update($data);
                } else {
                    DB::table('links')->insert([...$data, 'url' => $path, 'status' => 1, 'created_at' => $now]);
                }
            }
            // Remove only the old empty containers; unrelated links and modules are retained.
            foreach (DB::table('menus')->where('name', 'Website')->pluck('id') as $menu) {
                foreach (DB::table('modules')->where('menu_id', $menu)->where('name', 'Offerings')->pluck('id') as $oldModule) {
                    if (! DB::table('links')->where('module_id', $oldModule)->exists()) {
                        DB::table('modules')->where('id', $oldModule)->delete();
                    }
                }
                if (! DB::table('modules')->where('menu_id', $menu)->exists()) {
                    DB::table('menus')->where('id', $menu)->delete();
                }
            }
        });
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiProNavigationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $menu = DB::table('menus')->where('name', 'AI Section')->value('id')
                ?? DB::table('menus')->insertGetId(['name' => 'AI Section', 'fa_icon' => 'globe', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $module = DB::table('modules')->where('menu_id', $menu)->where('name', 'iTechOrbit AI')->value('id')
                ?? DB::table('modules')->insertGetId(['menu_id' => $menu, 'name' => 'iTechOrbit AI', 'fa_icon' => 'globe', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $pages = ['overview' => ['Overview', 'house'], 'websites' => ['Websites', 'globe'], 'knowledge' => ['Knowledge', 'file-lines'],
                'synchronization' => ['Synchronization', 'gears'], 'conversations' => ['Conversations', 'envelope'], 'installation' => ['Installation & Providers', 'cog']];
            foreach ($pages as $order => [$label, $icon]) {
                $path = 'workspace/ai-pro/'.$order;
                $link = DB::table('links')->whereIn('url', [$path, '/'.$path])->first();
                if (! $link && $order === 'overview') {
                    $link = DB::table('links')->whereIn('url', ['workspace/ai-pro', '/workspace/ai-pro'])->first();
                }
                $values = ['module_id' => $module, 'name' => $label, 'url' => $path, 'fa_icon' => $icon,
                    'order_number' => array_search($order, array_keys($pages), true), 'updated_at' => now()];
                if ($link) {
                    DB::table('links')->where('id', $link->id)->update($values);
                } else {
                    DB::table('links')->insert([...$values, 'status' => 1, 'created_at' => now()]);
                }
            }
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('links')->whereIn('url', ['website-technologies', '/website-technologies'])->exists()) {
            return;
        }
        $module = DB::table('links')->whereIn('url', ['service-info', '/service-info'])->value('module_id')
            ?? DB::table('modules')->where('name', 'Site Control')->value('id');
        if (! $module) {
            $menu = DB::table('menus')->where('name', 'Website')->value('id')
                ?? DB::table('menus')->insertGetId(['name' => 'Website', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $module = DB::table('modules')->insertGetId(['menu_id' => $menu, 'name' => 'Site Control', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('links')->insert(['module_id' => $module, 'name' => 'Technologies', 'url' => 'website-technologies',
            'fa_icon' => 'code', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Retain administrator-managed navigation and permission assignments.
    }
};

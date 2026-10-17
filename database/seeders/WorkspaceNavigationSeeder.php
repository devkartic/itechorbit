<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkspaceNavigationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $firstOrder = max(0, (int) (DB::table('menus')->min('order_number') ?? 1) - 1);
            $workspace = $this->menu('Workspace', $firstOrder);
            $general = $this->module($workspace, 'General', 0);
            $this->link($general, 'Overview', 'home', 0, 'house');
            $this->link($general, 'Portfolio templates', 'workspace/portfolio-templates', 1, 'palette');

            $administration = DB::table('menus')->whereRaw('LOWER(name) IN (?, ?)', ['administration', 'administrative'])->value('id')
                ?? $this->menu('Administration', $firstOrder + 1);
            $email = $this->module($administration, 'Email notices', 0);
            foreach (['compose' => ['Compose', 'paper-plane'], 'templates' => ['Templates', 'file-lines'], 'history' => ['Send history', 'envelope']] as $page => [$label, $icon]) {
                $this->link($email, $label, 'workspace/email-notices/'.$page, array_search($page, ['compose', 'templates', 'history'], true), $icon);
            }

            // Backfill only absent links previously synthesized from config for Super Admin.
            // Never rename/move existing entries or modify permission assignments.
            foreach ([...config('admin'), 'permissions' => ['title' => 'Roles & permissions']] as $path => $config) {
                if (DB::table('links')->whereIn('url', [$path, '/'.$path])->exists()) {
                    continue;
                }
                $group = in_array($path, ['posts', 'categories', 'tags', 'about-info', 'service-info', 'contact-info', 'privacy-policies']) ? 'Content'
                    : (in_array($path, ['menus', 'modules', 'links', 'roles', 'users', 'permissions']) ? 'Administration' : 'Portfolio & inquiries');
                $menu = $group === 'Administration' ? $administration : $this->menu($group, null);
                $module = $this->module($menu, 'Manage', null);
                $this->link($module, $config['title'], $path, null);
            }
        });
    }

    private function menu(string $name, ?int $order): int
    {
        return DB::table('menus')->where('name', $name)->value('id') ?? DB::table('menus')->insertGetId([
            'name' => $name, 'order_number' => $order, 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function module(int $menu, string $name, ?int $order): int
    {
        return DB::table('modules')->where('menu_id', $menu)->where('name', $name)->value('id') ?? DB::table('modules')->insertGetId([
            'menu_id' => $menu, 'name' => $name, 'order_number' => $order, 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function link(int $module, string $name, string $path, ?int $order, ?string $icon = null): void
    {
        if (! DB::table('links')->whereIn('url', [$path, '/'.$path])->exists()) {
            DB::table('links')->insert(['module_id' => $module, 'name' => $name, 'url' => $path, 'order_number' => $order, 'fa_icon' => $icon,
                'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}

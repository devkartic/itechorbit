<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use App\Models\Admin\Menu;
use App\Models\Admin\Module;
use App\Models\Admin\Permission;
use App\Models\Admin\Route;
use App\Services\AdminPresentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function navigation(array $entries): array
    {
        $this->actingAs($this->user());
        $route = Route::create(['name' => 'index', 'status' => 1]);
        foreach ($entries as [$menuName, $menuOrder, $moduleName, $moduleOrder, $resource, $linkOrder]) {
            $menu = Menu::firstOrCreate(['name' => $menuName], ['order_number' => $menuOrder, 'status' => 1]);
            $module = Module::firstOrCreate(['menu_id' => $menu->id, 'name' => $moduleName], ['order_number' => $moduleOrder, 'status' => 1]);
            $link = Link::create(['name' => $resource, 'url' => $resource, 'module_id' => $module->id, 'order_number' => $linkOrder, 'status' => 1]);
            Permission::create(['role_id' => 4, 'link_id' => $link->id, 'route_id' => $route->id]);
        }

        return app(AdminPresentation::class)->navigation();
    }

    public function test_each_sidebar_level_uses_numeric_order_then_alphabetical_fallback(): void
    {
        // Deliberately insert out of order; child orders must never override parent order.
        $navigation = $this->navigation([
            ['zeta unordered', null, 'Manage', null, 'links', 0],
            ['alpha unordered', null, 'Manage', null, 'menus', 0],
            ['alpha numbered', 2, 'Manage', 0, 'modules', 0],
            ['Zulu numbered', 0, 'zeta unordered', null, 'roles', 0],
            ['Zulu numbered', 0, 'alpha unordered', null, 'users', 0],
            ['Zulu numbered', 0, 'alpha numbered', 2, 'permissions', 0],
            ['Zulu numbered', 0, 'Zulu numbered', 0, 'tags', null],
            ['Zulu numbered', 0, 'Zulu numbered', 0, 'categories', null],
            ['Zulu numbered', 0, 'Zulu numbered', 0, 'posts', 2],
            ['Zulu numbered', 0, 'Zulu numbered', 0, 'projects', 0],
        ]);

        $this->assertSame(['/projects', '/posts', '/categories', '/tags', '/permissions', '/users', '/roles', '/modules', '/menus', '/links'], array_column($navigation, 'url'));
        $this->assertSame(0, $navigation[0]['order']);
        $this->assertNull($navigation[2]['order']);
        $this->assertArrayNotHasKey('_ordering', $navigation[0]);
    }

    public function test_equal_order_numbers_use_case_insensitive_natural_alphabetical_order(): void
    {
        $navigation = $this->navigation([
            ['Menu 10', 1, 'Manage', null, 'links', null],
            ['menu 2', 1, 'Module 10', 1, 'users', null],
            ['menu 2', 1, 'module 2', 1, 'tags', 3],
            ['menu 2', 1, 'module 2', 1, 'posts', 3],
            ['Alpha', 1, 'Manage', null, 'categories', null],
        ]);

        $this->assertSame(['/categories', '/posts', '/tags', '/users', '/links'], array_column($navigation, 'url'));
    }
}

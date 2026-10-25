<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use Database\Seeders\WebsiteNavigationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class WebsiteProductMigrationTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_website_navigation_preserves_service_link_and_portfolio_link(): void
    {
        $this->permissionResources();
        $website = Link::where('url', 'service-info')->firstOrFail();
        $portfolio = Link::where('url', 'services')->firstOrFail()->getAttributes();
        $now = now();
        $menu = DB::table('menus')->insertGetId(['name' => 'Website', 'status' => 1]);
        $oldModule = DB::table('modules')->insertGetId(['name' => 'Offerings', 'menu_id' => $menu, 'status' => 1]);
        $siteControl = DB::table('modules')->where('name', 'Site Control')->value('id')
            ?? DB::table('modules')->insertGetId(['name' => 'Site Control', 'menu_id' => $website->module->menu_id, 'status' => 1]);
        DB::table('links')->whereIn('url', ['service-info', 'product-info'])->update(['module_id' => $oldModule]);
        DB::table('links')->where('id', $website->id)->update(['status' => 0]);
        $role = DB::table('roles')->insertGetId(['name' => 'Website editor', 'status' => 1]);
        $route = DB::table('routes')->where('name', 'index')->value('id');
        DB::table('permissions')->insert(['role_id' => $role, 'link_id' => $website->id, 'route_id' => $route, 'created_at' => $now, 'updated_at' => $now]);
        $permissions = DB::table('permissions')->orderBy('id')->get()->toJson();
        $this->seed(WebsiteNavigationSeeder::class);
        $this->seed(WebsiteNavigationSeeder::class);
        $this->assertSame($website->id, Link::where('url', 'service-info')->sole()->id);
        $this->assertSame('Site Control', $website->fresh()->module->name);
        $this->assertSame('Site Control', Link::where('url', 'product-info')->sole()->module->name);
        $this->assertSame($siteControl, $website->fresh()->module_id);
        $this->assertEquals(0, $website->fresh()->status);
        $this->assertSame($permissions, DB::table('permissions')->orderBy('id')->get()->toJson());
        $this->assertDatabaseMissing('modules', ['id' => $oldModule]);
        $this->assertDatabaseMissing('menus', ['id' => $menu]);
        $this->assertSame(1, DB::table('modules')->where('name', 'Site Control')->count());
        $this->assertSame($portfolio, Link::where('url', 'services')->sole()->getAttributes());
    }
}

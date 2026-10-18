<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use Database\Seeders\WebsiteNavigationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class WebsiteProductMigrationTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_split_and_rollback_preserve_records_and_leave_portfolio_services_unchanged(): void
    {
        $migration = require database_path('migrations/2026_10_18_000004_separate_website_products.php');
        $migration->down();
        $portfolio = DB::table('services')->get()->toJson();
        $record = ['id' => 82, 'slug' => 'existing-product', 'title' => 'Existing product', 'subtitle' => 'Summary',
            'descriptions' => '<p>Existing HTML</p>', 'service_type' => 'product', 'price' => 125.50,
            'currency' => 'USD', 'delivery_time' => 'One week', 'image_path' => 'uploads/website-services/existing.png',
            'deliverables' => '["Setup","Handover"]', 'status' => 1,
            'created_at' => '2025-01-01 12:00:00', 'updated_at' => '2025-02-01 12:00:00'];
        DB::table('website_services')->insert($record);
        $before = (array) DB::table('website_services')->find(82);
        $migration->up();
        $expected = $before;
        unset($expected['service_type']);
        $this->assertEquals($expected, (array) DB::table('website_products')->find(82));
        $this->assertNull(DB::table('website_services')->find(82));
        foreach (['service_type', 'price', 'currency', 'delivery_time'] as $column) {
            $this->assertFalse(Schema::hasColumn('website_services', $column));
        }
        $this->assertFalse(Schema::hasColumn('website_products', 'service_type'));
        $this->assertSame($portfolio, DB::table('services')->get()->toJson());
        $migration->down();
        $this->assertEquals($before, (array) DB::table('website_services')->find(82));
        $migration->up();
    }

    public function test_rollback_conflicts_fail_before_changing_tables(): void
    {
        $service = DB::table('website_services')->first();
        DB::table('website_products')->insert(['id' => $service->id, 'slug' => 'conflicting-product', 'title' => 'Product', 'subtitle' => 'Summary', 'descriptions' => 'Details']);
        $migration = require database_path('migrations/2026_10_18_000004_separate_website_products.php');
        try {
            $migration->down();
            $this->fail('Expected conflicting IDs to block rollback.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('overlapping', $exception->getMessage());
        }
        $this->assertTrue(Schema::hasTable('website_products'));
        $this->assertFalse(Schema::hasColumn('website_services', 'service_type'));
        $this->assertDatabaseHas('website_products', ['id' => $service->id]);
    }

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

<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use App\Models\Admin\Menu;
use App\Models\Admin\Module;
use App\Models\Admin\Permission;
use App\Models\Admin\Route;
use App\Services\Admin\AdminPresentation;
use Database\Seeders\WorkspaceNavigationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            $link = Link::updateOrCreate(['url' => $resource], ['name' => $resource, 'module_id' => $module->id, 'order_number' => $linkOrder, 'status' => 1]);
            Permission::create(['role_id' => 4, 'link_id' => $link->id, 'route_id' => $route->id]);
        }

        return array_values(array_filter(app(AdminPresentation::class)->navigation(), fn ($item) => in_array(ltrim($item['url'], '/'), array_column($entries, 4), true)));
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

    public function test_sidebar_uses_saved_names_placement_icons_and_active_status_without_changing_page_access(): void
    {
        $this->actingAs($this->user(1));
        $menu = Menu::create(['name' => 'Editorial', 'order_number' => -10, 'status' => 1, 'fa_icon' => '<i class="fa-solid fa-folder"></i>']);
        $module = Module::create(['menu_id' => $menu->id, 'name' => 'Publishing', 'order_number' => 0, 'status' => 1]);
        $link = Link::where('url', 'posts')->firstOrFail();
        $link->update(['name' => 'Writing desk', 'module_id' => $module->id, 'fa_icon' => '<i class="fa-solid fa-file-lines"></i>']);
        $items = app(AdminPresentation::class)->navigation();
        $this->assertSame('/posts', $items[0]['url']);
        $this->assertSame('Writing desk', $items[0]['label']);
        $this->assertSame('Editorial', $items[0]['group']);
        $this->assertSame('Publishing', $items[0]['module']);
        $this->assertSame('file-lines', $items[0]['icon']);
        $this->assertSame('folder', $items[0]['groupIcon']);
        foreach ([$link, $module, $menu] as $record) {
            $record->update(['status' => 0]);
            $this->assertFalse(collect(app(AdminPresentation::class)->navigation())->contains('url', '/posts'));
            $this->get('/posts')->assertOk();
            $record->update(['status' => 1]);
        }
        $this->assertTrue(collect(app(AdminPresentation::class)->navigation())->contains('url', '/posts'));
    }

    public function test_dynamic_links_preserve_view_permissions_and_cannot_grant_email_notice_access(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $view = Route::firstOrCreate(['name' => 'index'], ['status' => 1]);
        $post = Link::where('url', 'posts')->firstOrFail();
        $notice = Link::where('url', 'workspace/email-notices/compose')->firstOrFail();
        foreach ([$post, $notice] as $link) {
            Permission::create(['role_id' => $user->role_id, 'link_id' => $link->id, 'route_id' => $view->id]);
        }
        $items = collect(app(AdminPresentation::class)->navigation());
        $this->assertTrue($items->contains('url', '/posts'));
        $this->assertFalse($items->contains('url', '/users'));
        $this->assertFalse($items->contains('url', '/workspace/email-notices/compose'));
        $this->get('/posts')->assertOk();
        $this->get('/posts/create')->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->get('/workspace/email-notices/compose')->assertForbidden();
        $this->get('/workspace/portfolio-templates')->assertOk();
        Permission::where('link_id', $post->id)->delete();
        $this->assertFalse(collect(app(AdminPresentation::class)->navigation())->contains('url', '/posts'));
        $this->get('/posts')->assertForbidden();
    }

    public function test_navigation_registration_is_repeatable_and_preserves_existing_configuration_and_permissions(): void
    {
        $link = Link::where('url', 'workspace/email-notices/compose')->firstOrFail();
        $link->update(['name' => 'Send an announcement', 'status' => 0, 'order_number' => 15]);
        $snapshot = $link->fresh()->getAttributes();
        $user = $this->user();
        $view = Route::firstOrCreate(['name' => 'index'], ['status' => 1]);
        Permission::create(['role_id' => $user->role_id, 'link_id' => $link->id, 'route_id' => $view->id]);
        $permissions = DB::table('permissions')->orderBy('id')->get()->toJson();
        $counts = [Menu::count(), Module::count(), Link::count()];
        (new WorkspaceNavigationSeeder)->run();
        (new WorkspaceNavigationSeeder)->run();
        $this->assertSame($counts, [Menu::count(), Module::count(), Link::count()]);
        $this->assertSame($snapshot, $link->fresh()->getAttributes());
        $this->assertSame($permissions, DB::table('permissions')->orderBy('id')->get()->toJson());
    }

    public function test_nested_navigation_paths_can_be_edited_and_unsafe_urls_are_rejected(): void
    {
        $this->actingAs($this->user(1));
        $link = Link::where('url', 'workspace/email-notices/compose')->firstOrFail();
        $data = ['name' => 'Write a notice', 'link_url' => $link->url, 'module_id' => $link->module_id, 'order_number' => 3, 'is_checked' => true];
        $this->putJson('/links/'.$link->id, $data)->assertOk();
        $this->assertSame('Write a notice', $link->fresh()->name);
        foreach (['https://example.test', '//example.test', 'javascript:alert(1)', 'workspace/../users'] as $url) {
            $this->putJson('/links/'.$link->id, [...$data, 'link_url' => $url])->assertUnprocessable()->assertJsonValidationErrors('link_url');
        }
    }
}

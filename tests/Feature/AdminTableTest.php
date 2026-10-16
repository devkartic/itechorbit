<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\Admin\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class AdminTableTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_ajax_tables_require_authentication_permissions_and_ownership(): void
    {
        $this->getJson('/workspace/data/posts')->assertUnauthorized();
        $owner = $this->user();
        $other = $this->user();
        $this->permissionResources();
        $own = $this->article([], $owner);
        $this->article([], $other);
        $this->actingAs($owner)->getJson('/workspace/data/posts')->assertForbidden();
        Permission::create(['role_id' => 4, 'link_id' => Link::where('url', 'posts')->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
        $this->getJson('/workspace/data/posts')->assertOk()->assertJsonCount(1, 'records.data')->assertJsonPath('records.data.0.id', $own->id)->assertJsonMissingPath('records.data.0.body');
        $this->getJson('/workspace/data/users')->assertForbidden();
        Permission::create(['role_id' => 4, 'link_id' => Link::where('url', 'users')->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
        $this->getJson('/workspace/data/users')->assertOk()->assertJsonCount(1, 'records.data')->assertJsonPath('records.data.0.id', $owner->id)->assertJsonMissingPath('records.data.0.password');
        $this->getJson('/workspace/data/unknown')->assertNotFound();
        $this->get('/home')->assertOk()->assertInertia(fn (Assert $page) => $page->has('navigation', 2)->where('navigation.0.group', 'Test workspace')->where('navigation.0.module', 'Management')->where('navigation.0.url', '/posts')->where('canCreateArticle', false));
        $owner->update(['status' => 0]);
        $this->getJson('/workspace/data/posts')->assertForbidden();
    }

    public function test_ajax_search_sort_status_and_pagination_are_processed_on_the_server(): void
    {
        $admin = $this->user(1);
        $this->actingAs($admin);
        for ($i = 0; $i < 25; $i++) {
            $this->article(['title' => sprintf('Engineering %02d', $i), 'status' => 1], $admin);
        }
        $this->article(['title' => 'Engineering inactive', 'status' => 0], $admin);
        $this->getJson('/workspace/data/posts?per_page=10&sort=title&direction=asc&status=1&page=2')->assertOk()
            ->assertJsonPath('records.total', 25)->assertJsonPath('records.current_page', 2)->assertJsonCount(10, 'records.data')
            ->assertJsonPath('records.data.0.title', 'Engineering 10')->assertJsonPath('records.data.9.title', 'Engineering 19');
        $this->getJson('/workspace/data/posts?key=inactive&status=0')->assertOk()->assertJsonCount(1, 'records.data')->assertJsonPath('records.total', 1);
        $this->getJson('/workspace/data/posts?key=missing')->assertOk()->assertJsonCount(0, 'records.data');
        foreach (array_keys(config('admin')) as $resource) {
            $this->getJson('/workspace/data/'.$resource)->assertOk();
        }
        $this->getJson('/workspace/data/users')->assertJsonMissingPath('records.data.0.password')->assertJsonMissingPath('records.data.0.remember_token')->assertJsonMissingPath('records.data.0.provider_id');
        foreach (['sort=password', 'sort=title%3BDROP%20TABLE%20posts', 'direction=random', 'per_page=10000', 'page=0', 'status=2'] as $query) {
            $this->getJson('/workspace/data/posts?'.$query)->assertUnprocessable();
        }
    }

    public function test_super_admin_and_admin_can_list_all_public_portfolios_without_exposing_private_fields(): void
    {
        (new Role)->forceFill(['id' => 2, 'name' => 'Admin', 'status' => 1])->save();
        $super = $this->user(1);
        $admin = $this->user(2);
        $visible = $this->user(4, ['portfolio_id' => 'PUBLIC PROFILE', 'portfolio_status' => 1]);
        $hidden = $this->user(4, ['portfolio_id' => 'HIDDEN', 'portfolio_status' => 0]);
        $inactive = $this->user(4, ['portfolio_id' => 'INACTIVE', 'portfolio_status' => 1, 'status' => 0]);
        $missing = $this->user(4, ['portfolio_id' => null, 'portfolio_status' => 1]);
        $this->permissionResources();
        Permission::create(['role_id' => 2, 'link_id' => Link::where('url', 'users')->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
        foreach ([$super, $admin] as $viewer) {
            $this->actingAs($viewer)->get('/users')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('columns', fn ($columns) => in_array('portfolio_status', $columns->all()) && ! in_array('public_portfolio', $columns->all()))
                ->has('records.data', 6));
            $response = $this->getJson('/workspace/data/users?sort=name&direction=asc')->assertOk()->assertJsonCount(6, 'records.data');
            $rows = collect($response->json('records.data'))->keyBy('id');
            $this->assertSame('/portfolios/PUBLIC%20PROFILE', $rows[$visible->id]['public_portfolio']);
            $this->assertSame('/portfolios/HIDDEN', $rows[$hidden->id]['public_portfolio']);
            $this->assertSame('/portfolios/INACTIVE', $rows[$inactive->id]['public_portfolio']);
            $this->assertNull($rows[$missing->id]['public_portfolio']);
            foreach ([$hidden, $inactive] as $user) {
                $this->get('/portfolios/'.$user->portfolio_id)->assertOk()
                    ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                    ->assertHeader('Cache-Control', 'no-store, private')
                    ->assertInertia(fn (Assert $page) => $page->where('adminPreview', true)->where('seo.noindex', true)->missing('profile.email'));
            }
            foreach ($rows as $row) {
                foreach (['password', 'remember_token', 'provider_id', 'portfolio_id'] as $private) {
                    $this->assertArrayNotHasKey($private, $row);
                }
            }
            $this->getJson('/workspace/data/users?sort=public_portfolio')->assertUnprocessable();
            $this->getJson('/workspace/data/users?key='.$visible->email)->assertOk()->assertJsonCount(1, 'records.data')
                ->assertJsonPath('records.data.0.public_portfolio', '/portfolios/PUBLIC%20PROFILE');
        }

        Permission::create(['role_id' => 4, 'link_id' => Link::where('url', 'users')->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
        $this->actingAs($visible)->get('/users')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('columns', fn ($columns) => ! in_array('public_portfolio', $columns->all()))->has('records.data', 1));
        $this->getJson('/workspace/data/users')->assertOk()->assertJsonCount(1, 'records.data')->assertJsonMissingPath('records.data.0.public_portfolio');
        $this->get('/portfolios/HIDDEN')->assertForbidden();
        $this->get('/portfolios/INACTIVE')->assertForbidden();
        $this->actingAs($admin);
        Permission::where('role_id', 2)->delete();
        $this->get('/portfolios/HIDDEN')->assertForbidden();
        $this->actingAs($super);
        $super->update(['status' => 0]);
        $this->get('/portfolios/HIDDEN')->assertForbidden();
        auth()->logout();
        $this->get('/portfolios/HIDDEN')->assertForbidden();
        $this->get('/portfolios/INACTIVE')->assertForbidden();
    }

    public function test_rich_content_is_preserved_for_editing_and_sanitized_for_admin_preview(): void
    {
        $admin = $this->user(1);
        $html = '<h2>Engineering</h2><p><strong>Important</strong> details</p><script id="admin-preview-xss">alert(1)</script>';
        $post = $this->article(['body' => $html], $admin);
        $this->actingAs($admin)->get('/posts/'.$post->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page->where('record.body', $html));
        $this->get('/posts/'.$post->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('previews.body', fn ($value) => str_contains($value, '<strong>Important</strong>') && ! str_contains($value, '<script')));
        $this->assertSame($html, $post->fresh()->body);
    }
}

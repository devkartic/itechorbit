<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use App\Models\Admin\Permission;
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

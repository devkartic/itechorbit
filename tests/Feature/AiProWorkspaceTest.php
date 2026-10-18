<?php

namespace Tests\Feature;

use App\AiPro\Http\AdminController;
use App\AiPro\Knowledge;
use App\AiPro\Models\Conversation;
use App\AiPro\Models\Message;
use App\AiPro\Models\Site;
use App\AiPro\SafePageFetcher;
use App\Models\Admin\Link;
use App\Models\Admin\Menu;
use App\Models\Admin\Module;
use App\Models\Admin\Permission;
use App\Models\Admin\Route;
use App\Services\Admin\SidebarNavigation;
use Database\Seeders\AiProNavigationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class AiProWorkspaceTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function site(string $key = 'local'): Site
    {
        return Site::create(['public_id' => $key, 'name' => $key, 'url' => 'https://example.test', 'origins' => ['https://example.test']]);
    }

    public function test_navigation_moves_legacy_link_preserves_permissions_and_is_repeatable(): void
    {
        $admin = $this->user(1);
        $menu = Menu::create(['name' => 'Original section', 'status' => 1]);
        $module = Module::create(['name' => 'General', 'menu_id' => $menu->id, 'status' => 1]);
        Link::where('url', 'workspace/ai-pro/overview')->delete();
        $old = Link::create(['name' => 'SiteSage AI', 'url' => 'workspace/ai-pro', 'module_id' => $module->id, 'status' => 1]);
        $other = Link::create(['name' => 'Unrelated', 'url' => 'unrelated', 'module_id' => $module->id, 'status' => 1]);
        $route = Route::create(['name' => 'index', 'status' => 1]);
        Permission::create(['link_id' => $old->id, 'role_id' => $admin->role_id, 'route_id' => $route->id]);
        $this->seed(AiProNavigationSeeder::class);
        $this->seed(AiProNavigationSeeder::class);
        $this->assertSame('workspace/ai-pro/overview', $old->fresh()->url);
        $this->assertDatabaseHas('permissions', ['link_id' => $old->id, 'role_id' => $admin->role_id, 'route_id' => $route->id]);
        $this->assertSame($module->id, $other->fresh()->module_id);
        $this->assertSame(6, Link::where('url', 'like', 'workspace/ai-pro/%')->count());
        $this->assertSame(1, Module::where('name', 'iTechOrbit AI')->count());
        $this->actingAs($admin);
        $links = collect(app(SidebarNavigation::class)->items())->filter(fn ($item) => str_starts_with($item['url'], '/workspace/ai-pro/'));
        $this->assertCount(6, $links);
        $this->assertSame(['AI Section'], $links->pluck('group')->unique()->values()->all());
        $this->assertSame(['iTechOrbit AI'], $links->pluck('module')->unique()->values()->all());
        $this->assertSame(['Overview', 'Websites', 'Knowledge', 'Synchronization', 'Conversations', 'Installation & Providers'], $links->pluck('label')->values()->all());
        $this->actingAs($this->user());
        $this->assertEmpty(array_filter(app(SidebarNavigation::class)->items(), fn ($item) => str_starts_with($item['url'], '/workspace/ai-pro/')));
    }

    public function test_six_distinct_pages_preserve_selection_and_do_not_expose_credentials(): void
    {
        $site = $this->site();
        $this->actingAs($this->user(1));
        $this->get('/workspace/ai-pro?site='.$site->id)->assertRedirect('/workspace/ai-pro/overview?site='.$site->id);
        foreach (AdminController::SECTIONS as $section) {
            $this->get('/workspace/ai-pro/'.$section.'?site='.$site->id.'&page=2')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AiPro/'.ucfirst($section))->where('selection.site', (string) $site->id)->where('selection.page', '2')->missing('api_key')->missing('embedding_api_key'));
        }
        $this->get('/workspace/ai-pro/knowledge?site=99999')->assertNotFound();
        $this->actingAs($this->user());
        foreach (AdminController::SECTIONS as $section) {
            $this->get('/workspace/ai-pro/'.$section)->assertForbidden();
        }
    }

    public function test_each_page_loads_only_its_data_and_lists_are_paginated(): void
    {
        $site = $this->site();
        $other = $this->site('other');
        foreach (range(1, 21) as $number) {
            app(Knowledge::class)->save($site, (string) $number, 'faq', ['title' => 'FAQ '.$number, 'url' => $site->url, 'content' => 'Approved text']);
        }
        app(Knowledge::class)->save($other, 'other', 'faq', ['title' => 'Other website source', 'url' => $other->url, 'content' => 'Other']);
        $this->actingAs($this->user(1));
        $base = '/workspace/ai-pro/data?site='.$site->id;
        $this->getJson($base.'&section=knowledge&page=2')->assertOk()->assertJsonPath('sources.total', 21)->assertJsonCount(1, 'sources.data')
            ->assertJsonMissingPath('conversations')->assertJsonMissingPath('runs');
        $this->getJson($base.'&section=overview')->assertJsonPath('counts.sources', 21)->assertJsonMissingPath('sources')->assertJsonMissingPath('conversations');
        $this->getJson($base.'&section=websites')->assertJsonMissingPath('sources')->assertJsonMissingPath('runs');
        $this->getJson($base.'&section=installation')->assertJsonMissingPath('sources')->assertJsonMissingPath('conversations');
        $this->getJson('/workspace/ai-pro/data?site=99999')->assertNotFound();
        $session = Conversation::create(['site_id' => $site->id, 'public_id' => 'conversation', 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->addDay()]);
        Message::create(['site_id' => $site->id, 'conversation_id' => $session->id, 'question' => 'Question', 'answer' => 'Answer', 'status' => 'answered']);
        $this->getJson($base.'&section=conversations')->assertJsonPath('conversations.total', 1)->assertJsonMissingPath('sources');
        $this->getJson('/workspace/ai-pro/sites/'.$other->id.'/conversations/'.$session->id)->assertNotFound();
        $this->getJson('/workspace/ai-pro/sites/'.$site->id.'/conversations/'.$session->id)->assertJsonPath('data.0.answer', 'Answer');
    }

    public function test_crawler_prefers_the_new_brand_and_keeps_legacy_robots_support(): void
    {
        $fetcher = app(SafePageFetcher::class);
        $this->assertFalse($fetcher->allowedByRobots("User-agent: SiteSageAI\nDisallow: /", '/services'));
        $this->assertTrue($fetcher->allowedByRobots("User-agent: SiteSageAI\nDisallow: /\nUser-agent: iTechOrbitAI\nAllow: /", '/services'));
    }
}

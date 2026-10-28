<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class AdminErrorPageTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_missing_admin_records_and_unknown_workspace_urls_use_the_admin_layout(): void
    {
        $user = $this->user(1);
        $this->permissionResources();
        $this->actingAs($user);

        foreach (['/posts/999999/edit', '/workspace/not-a-real-page', '/posts/not/a/real/page'] as $url) {
            $this->get($url, ['Accept' => 'text/html'])->assertNotFound()
                ->assertInertia(fn (Assert $page) => $page->component('Admin/Error')
                    ->where('status', 404)->where('auth.user.name', $user->name)
                    ->has('navigation', fn (Assert $navigation) => $navigation->each(fn (Assert $item) => $item->has('url')->etc())));
        }
    }

    public function test_forbidden_admin_pages_keep_permission_filtered_navigation_and_status(): void
    {
        $this->permissionResources();
        $this->actingAs($this->user());

        $this->get('/users', ['Accept' => 'text/html'])->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Error')
                ->where('status', 403)->where('navigation', fn ($items) => ! $items->contains('url', '/users')));
        $this->get('/users', ['Accept' => 'text/html', 'X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion()])
            ->assertForbidden()->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Admin/Error')->assertJsonPath('props.status', 403);
        $this->getJson('/users')->assertForbidden()->assertJsonMissingPath('component');
    }

    public function test_public_errors_keep_the_website_layout_even_when_signed_in(): void
    {
        $this->actingAs($this->user(1));
        foreach (['/not-a-real-page', '/blogs/missing-article', '/portfolios/MISSING'] as $url) {
            $this->get($url, ['Accept' => 'text/html'])->assertNotFound()
                ->assertInertia(fn (Assert $page) => $page->component('Shared/Error')->where('status', 404));
        }
        $this->user(4, ['portfolio_id' => 'DISABLED', 'portfolio_status' => 0]);
        $this->get('/portfolios/DISABLED', ['Accept' => 'text/html'])->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->component('Shared/Error')->where('status', 403));
    }

    public function test_guests_and_json_requests_do_not_receive_private_layout_data(): void
    {
        $this->get('/workspace/missing', ['Accept' => 'text/html'])->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Shared/Error')->where('auth.user', null)->missing('navigation'));
        $this->actingAs($this->user(1))->getJson('/workspace/missing')->assertNotFound()->assertJsonMissingPath('component');
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    public function test_missing_pages_files_and_directories_return_the_themed_page_at_the_original_url(): void
    {
        foreach (['/not-a-real-page', '/not-a-real-directory/nested/', '/storage/missing-photo.png', '/uploads/missing-resume.pdf', '/build/assets/missing.js', '/missing-script.php'] as $url) {
            $this->get($url, ['Accept' => 'text/html'])->assertNotFound()->assertInertia(fn (Assert $page) => $page
                ->component('Shared/Error')->where('status', 404)->where('seo.noindex', true)
                ->where('site.name', 'iTechOrbit')->where('auth.user', null)
                ->where('seo.title', 'Page not found | iTechOrbit')
                ->where('seo.canonical', rtrim(config('app.url'), '/').rtrim($url, '/')));
        }
    }

    public function test_inertia_navigation_receives_a_404_page_response(): void
    {
        $this->get('/not-a-real-page', ['X-Inertia' => 'true', 'Accept' => 'text/html'])
            ->assertNotFound()->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Shared/Error')->assertJsonPath('props.status', 404);
    }

    public function test_json_errors_remain_json_instead_of_a_page(): void
    {
        foreach (['/not-a-real-page', '/storage/missing.png', '/build/assets/missing.js'] as $url) {
            $this->getJson($url)->assertNotFound()->assertHeader('Content-Type', 'application/json')->assertJsonMissingPath('component');
        }
    }

    public function test_missing_subresources_do_not_receive_a_themed_html_document(): void
    {
        foreach (['/build/assets/missing.js' => 'script', '/build/assets/missing.css' => 'style', '/storage/missing.png' => 'image', '/uploads/missing.png' => 'image', '/missing-font.woff2' => 'font'] as $url => $destination) {
            $response = $this->get($url, ['Accept' => '*/*', 'Sec-Fetch-Dest' => $destination])->assertNotFound();
            $this->assertSame('', $response->getContent());
        }
    }

    public function test_forbidden_page_preserves_status_and_json_responses(): void
    {
        Route::middleware('web')->get('/_test/forbidden', fn () => abort(403));
        $this->get('/_test/forbidden', ['Accept' => 'text/html'])->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->component('Shared/Error')
                ->where('status', 403)->where('seo.title', 'Access forbidden | iTechOrbit')->where('seo.noindex', true));
        $this->get('/_test/forbidden', ['Accept' => 'text/html', 'X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion()])->assertForbidden()
            ->assertJsonPath('props.status', 403);
        $this->getJson('/_test/forbidden')->assertForbidden()->assertJsonMissingPath('component');
    }

    public function test_other_error_statuses_and_protected_resources_are_not_changed_to_404(): void
    {
        config(['app.debug' => false]);
        foreach ([403, 500, 503] as $status) {
            Route::middleware('web')->get('/_test/error/'.$status, fn () => abort($status));
            $this->get('/_test/error/'.$status, ['Accept' => 'text/html'])->assertStatus($status)
                ->assertInertia(fn (Assert $page) => $page->component('Shared/Error')->where('status', $status));
        }
    }
}

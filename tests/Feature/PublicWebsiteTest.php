<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Jobs\ContactMailerJob;
use App\Models\Admin\Project;
use App\Models\Admin\Skill;
use App\Models\Contact;
use App\Services\Shared\PublicContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_public_pages_have_components_and_canonical_metadata(): void
    {
        foreach (['/' => 'Website/Home', '/blogs' => 'Website/Blog', '/work' => 'Website/Work', '/about/info' => 'Website/About', '/service/info' => 'Website/Services', '/contact/info' => 'Website/Contact', '/privacy-n-policy' => 'Website/Content', '/services/laravel-development' => 'Website/Service', '/services/ai-workflow-integration' => 'Website/Service'] as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component)->where('seo.canonical', rtrim(config('app.url'), '/').$url)->has('seo.description'));
        }
        $this->get('/about')->assertRedirect('/about/info')->assertStatus(301);
        $this->get('/contact')->assertStatus(301);
        $this->get('/?page=2')->assertStatus(301)->assertRedirect('/blogs?page=2');
        $this->get('/services/create')->assertRedirect('/login');
        $this->get('/services/unknown-service')->assertNotFound();
    }

    public function test_published_article_urls_sanitization_reading_time_toc_and_search(): void
    {
        $article = $this->article(['tags' => json_encode(['Laravel']), 'body' => '<h2>Domain model</h2><p>engineering needle</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a><img src="x" onerror="alert(1)">']);
        $this->article(['is_private' => 1]);
        $this->get('/blogs/'.$article->id)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Website/Article')->where('article.id', $article->id)->where('article.readingMinutes', 1)->where('article.toc.0.title', 'Domain model')->where('article.body', fn ($body) => ! str_contains($body, '<script') && ! str_contains($body, 'onerror') && ! str_contains($body, 'javascript:')));
        $this->assertSame(1, $article->fresh()->view_times);
        $this->get('/blogs?key=needle')->assertOk()->assertInertia(fn (Assert $page) => $page->has('articles.data', 1)->where('seo.noindex', true));
        $this->post('/blogs/search', ['key' => 'needle'])->assertOk();
        $this->get('/blogs?tag=Laravel')->assertOk()->assertInertia(fn (Assert $page) => $page->has('articles.data', 1));
        $this->get('/blogs/category/'.$article->category_id)->assertOk();
        $this->get('/blogs/category/999999')->assertNotFound();
        $this->get('/blogs?page=0')->assertSessionHasErrors('page');
    }

    public function test_pagination_and_related_content_stay_public(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 13; $i++) {
            $this->article(['title' => 'Public article '.$i], $user);
        }
        $this->article(['status' => 0], $user);
        $this->get('/blogs?page=2')->assertOk()->assertInertia(fn (Assert $page) => $page->has('articles.data', 1)->where('articles.total', 13)->where('seo.canonical', rtrim(config('app.url'), '/').'/blogs?page=2'));
        $this->get('/blogs?page=999')->assertNotFound();
        $this->get('/work?page=999')->assertNotFound();
    }

    public function test_portfolios_only_publish_explicitly_public_fields(): void
    {
        $user = $this->user(4, ['portfolio_id' => 'TESTPROFILE', 'portfolio_status' => 1]);
        $skill = Skill::create(['name' => 'Laravel', 'user_id' => $user->id, 'status' => 1]);
        $project = Project::create(['user_id' => $user->id, 'name' => 'Test application', 'client_name' => 'Private client', 'skill_id' => $skill->id, 'about_project' => 'Published project overview.', 'status' => 1]);
        $this->get('/portfolios/TESTPROFILE')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portfolio/Show')->where('profile.name', $user->name)->missing('profile.email')->missing('profile.password')->missing('profile.birth_date')->missing('profile.mobile'));
        $this->get('/work/'.$project->id)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Website/Project')->missing('project.client_name'));
        $user->update(['portfolio_status' => 0]);
        $this->get('/work/'.$project->id)->assertNotFound();
        $this->get('/portfolios/TESTPROFILE')->assertForbidden();
    }

    public function test_inquiry_is_validated_saved_and_queued_without_claiming_delivery(): void
    {
        Queue::fake();
        $owner = $this->user(1);
        config(['site.owner_id' => $owner->id]);
        $data = ['name' => 'Prospective client', 'email' => 'client@example.test', 'subject' => 'Modernization', 'message' => 'Please review our application.'];
        $this->from('/contact/info')->post('/inquiries', $data)->assertRedirect('/contact/info')->assertSessionHas('success');
        $this->assertDatabaseHas('contacts', ['user_id' => $owner->id, 'email' => $data['email'], 'sent_mail_status' => 0]);
        Queue::assertPushed(ContactMailerJob::class);
        $this->postJson('/inquiries', [...$data, 'website' => 'spam'])->assertUnprocessable()->assertJsonValidationErrors('website');
        $this->postJson('/inquiries', [...$data, 'email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame(1, Contact::count());
    }

    public function test_profile_inquiries_reach_the_selected_public_profile_owner(): void
    {
        Queue::fake();
        $owner = $this->user(1);
        config(['site.owner_id' => $owner->id]);
        $recipient = $this->user(4, ['portfolio_id' => 'RECIPIENT', 'portfolio_status' => 1]);
        $this->get('/contact/info?portfolio_id=RECIPIENT')->assertRedirect('/portfolios/RECIPIENT/contact');
        $this->get('/portfolios/RECIPIENT/contact')->assertOk()->assertInertia(fn (Assert $page) => $page->where('recipient.name', $recipient->name)->missing('recipient.email'));
        $data = ['portfolio_id' => 'RECIPIENT', 'name' => 'Client', 'email' => 'client@example.test', 'subject' => 'Profile inquiry', 'message' => 'A project opportunity.'];
        $this->from('/contact/info?portfolio_id=RECIPIENT')->post('/inquiries', $data)->assertRedirect();
        $this->assertDatabaseHas('contacts', ['user_id' => $recipient->id, 'subject' => 'Profile inquiry']);
        $this->assertDatabaseMissing('contacts', ['user_id' => $owner->id, 'subject' => 'Profile inquiry']);
        $recipient->update(['portfolio_status' => 0]);
        $this->post('/inquiries', $data)->assertForbidden();
        $this->get('/contact/info?portfolio_id=RECIPIENT')->assertForbidden();
    }

    public function test_sitemap_and_robots_exclude_private_content_and_protected_urls(): void
    {
        $public = $this->article();
        $private = $this->article(['is_private' => 1]);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/sitemaps/articles-1.xml', false);
        $xml = $this->get('/sitemaps/articles-1.xml')->assertOk()->assertSee('/blogs/'.$public->id, false)->assertDontSee('/blogs/'.$private->id, false);
        $this->assertNotFalse(simplexml_load_string($xml->getContent()));
        $this->get('/sitemaps/pages.xml')->assertOk()->assertSee('/services/laravel-development', false)->assertDontSee('<loc>'.rtrim(config('app.url'), '/').'/posts</loc>', false);
        $this->get('/sitemaps/pages.xml')->assertOk()->assertSee('/services/ai-workflow-integration', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:', false)->assertSee('Disallow: /posts', false)->assertSee('Allow: /services/laravel-development', false);
        $this->get('/sitemaps/articles-999.xml')->assertNotFound();
    }

    public function test_public_security_headers_and_error_pages(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $this->get('/not-a-real-page')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('Shared/Error')->where('seo.noindex', true));
        $this->assertNull(app(PublicContent::class)->externalUrl('javascript:alert(1)'));
    }

    public function test_history_encryption_is_optional_locally_and_can_be_enabled_for_secure_deployments(): void
    {
        $this->assertFalse(config('inertia.history.encrypt'));
        $headers = ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request())];
        $this->get('/blogs', $headers)->assertOk()->assertJsonMissingPath('encryptHistory');
        config(['inertia.history.encrypt' => true]);
        $this->get('/blogs', $headers)->assertOk()->assertJsonPath('encryptHistory', true);
    }
}

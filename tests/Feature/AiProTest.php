<?php

namespace Tests\Feature;

use App\AiPro\Contracts\AiProvider;
use App\AiPro\DatabaseRetriever;
use App\AiPro\Jobs\SyncSite;
use App\AiPro\Knowledge;
use App\AiPro\LocalContent;
use App\AiPro\Models\Chunk;
use App\AiPro\Models\Conversation;
use App\AiPro\Models\Message;
use App\AiPro\Models\Site;
use App\AiPro\Models\Source;
use App\AiPro\Models\SyncRun;
use App\AiPro\OpenAiProvider;
use App\AiPro\SafePageFetcher;
use App\Services\Admin\SidebarNavigation;
use Database\Seeders\WorkspaceNavigationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\Fakes\AiProProvider;
use Tests\TestCase;

class AiProTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private AiProProvider $ai;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai_pro.enabled' => true, 'ai_pro.api_key' => 'synthetic-test-key', 'ai_pro.embedding_api_key' => 'synthetic-test-key']);
        Queue::fake();
        Http::preventStrayRequests();
        $this->ai = new AiProProvider;
        $this->app->instance(AiProvider::class, $this->ai);
    }

    private function site(array $overrides = []): Site
    {
        return Site::create([...['public_id' => 'site-'.Site::count(), 'name' => 'Test website', 'url' => 'https://example.test', 'origins' => ['https://example.test'], 'enabled' => true, 'contact_url' => 'https://example.test/contact'], ...$overrides]);
    }

    private function source(Site $site, string $text = 'We build web applications.'): Source
    {
        $source = app(Knowledge::class)->save($site, 'faq-'.Source::count(), 'faq', ['title' => 'Services', 'url' => $site->url.'/services', 'content' => $text]);
        app(Knowledge::class)->index($source);

        return $source->fresh();
    }

    private function path(Site $site, string $endpoint): string
    {
        return '/api/ai-pro/v1/sites/'.$site->public_id.'/'.$endpoint;
    }

    private function chatSession(Site $site): array
    {
        return $this->withHeader('Origin', $site->origins[0])->postJson($this->path($site, 'conversations'))->assertCreated()->json();
    }

    private function ask(Site $site, array $session, string $question = 'What services do you offer?')
    {
        return $this->withHeaders(['Origin' => $site->origins[0], 'Authorization' => 'Bearer '.$session['token']])->postJson($this->path($site, 'messages'), ['conversation_id' => $session['conversation_id'], 'question' => $question]);
    }

    public function test_prefixed_tables_and_repeatable_local_setup_start_disabled(): void
    {
        foreach (['sites', 'sources', 'chunks', 'conversations', 'messages', 'sync_runs'] as $suffix) {
            $this->assertTrue(Schema::hasTable('ai_pro_'.$suffix));
        }
        $this->artisan('ai-pro:setup')->assertSuccessful();
        $this->artisan('ai-pro:setup')->assertSuccessful();
        $this->assertSame(1, Site::count());
        $this->assertFalse(Site::first()->enabled);
        $this->getJson('/ai-pro/local-config')->assertJson(['site' => null]);
        $this->assertSame(0, $this->ai->embedCalls);
    }

    public function test_only_active_verified_administrators_manage_the_product_and_navigation(): void
    {
        $this->get('/workspace/ai-pro')->assertRedirect('/login');
        $this->actingAs($this->user())->get('/workspace/ai-pro')->assertForbidden();
        $this->actingAs($this->user(1, ['email_verified_at' => null]))->get('/workspace/ai-pro')->assertRedirect();
        $admin = $this->user(1);
        $this->actingAs($admin)->get('/workspace/ai-pro')->assertRedirect('/workspace/ai-pro/overview');
        $this->get('/workspace/ai-pro/overview')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/AiPro/Overview')->where('installed', true));
        $this->seed(WorkspaceNavigationSeeder::class);
        $this->assertContains('/workspace/ai-pro/overview', array_column(app(SidebarNavigation::class)->items(), 'url'));
        $admin->update(['status' => 0]);
        $this->getJson('/workspace/ai-pro/data')->assertForbidden();
    }

    public function test_admin_can_onboard_sites_and_manage_faqs_without_cross_site_access(): void
    {
        $this->actingAs($this->user(1));
        $payload = ['name' => 'Client website', 'url' => 'https://client.test', 'origins' => ['https://client.test'], 'enabled' => false,
            'welcome' => 'Welcome!', 'color' => '#123abc', 'contact_url' => 'https://client.test/contact', 'minute_limit' => 10, 'daily_limit' => 500];
        $id = $this->postJson('/workspace/ai-pro/sites', $payload)->assertOk()->json('id');
        $site = Site::findOrFail($id);
        $faq = ['kind' => 'faq', 'title' => 'Delivery', 'url' => null, 'content' => 'Delivery depends on agreed scope.', 'enabled' => true];
        $sourceId = $this->postJson('/workspace/ai-pro/sites/'.$id.'/sources', $faq)->assertOk()->json('id');
        $this->assertDatabaseHas('ai_pro_sources', ['id' => $sourceId, 'url' => $site->url]);
        $this->putJson('/workspace/ai-pro/sites/'.$id.'/sources/'.$sourceId, [...$faq, 'content' => 'Updated approved text.'])->assertOk();
        $this->assertDatabaseHas('ai_pro_sources', ['id' => $sourceId, 'content' => 'Updated approved text.', 'status' => 'pending']);
        $other = $this->site();
        $this->patchJson('/workspace/ai-pro/sites/'.$other->id.'/sources/'.$sourceId, ['enabled' => false])->assertNotFound();
        $this->deleteJson('/workspace/ai-pro/sites/'.$other->id.'/sources/'.$sourceId)->assertNotFound();
        $this->postJson('/workspace/ai-pro/sites', [...$payload, 'origins' => ['https://client.test/path']])->assertUnprocessable();
        $this->postJson('/workspace/ai-pro/sites/'.$id.'/sources', [...$faq, 'url' => 'javascript:alert(1)'])->assertUnprocessable();
        $this->getJson('/workspace/ai-pro/data?site='.$id)->assertOk()->assertJsonPath('selected', $id);
        $this->deleteJson('/workspace/ai-pro/sites/'.$id.'/sources/'.$sourceId)->assertOk();
        $this->assertDatabaseMissing('ai_pro_sources', ['id' => $sourceId]);
    }

    public function test_origin_preflights_are_exact_and_do_not_relax_site_security_headers(): void
    {
        $site = $this->site();
        $this->withHeaders(['Origin' => 'https://example.test', 'Access-Control-Request-Method' => 'POST'])->options($this->path($site, 'messages'))
            ->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'https://example.test')->assertHeaderMissing('Access-Control-Allow-Credentials');
        $this->withHeader('Origin', 'https://evil.test')->getJson($this->path($site, 'config'))->assertForbidden()->assertHeaderMissing('Access-Control-Allow-Origin');
        $this->withHeader('Origin', 'https://example.test.evil.test')->getJson($this->path($site, 'config'))->assertForbidden();
        $this->withHeader('Origin', 'https://example.test')->getJson($this->path($site, 'config'))->assertOk()->assertJsonMissingPath('api_key')->assertHeader('X-Frame-Options', 'DENY');
        $site->update(['enabled' => false]);
        $this->getJson($this->path($site, 'config'))->assertForbidden();
    }

    public function test_local_http_widget_configuration_uses_an_exact_same_origin_referrer(): void
    {
        $site = $this->site(['url' => 'http://localhost', 'origins' => ['http://localhost']]);
        $this->withHeader('Referer', 'http://localhost/')->getJson('http://localhost'.$this->path($site, 'config'))
            ->assertOk()->assertHeader('Access-Control-Allow-Origin', 'http://localhost');
        $this->withHeader('Referer', 'http://localhost.evil.test/')->getJson('http://localhost'.$this->path($site, 'config'))->assertForbidden();
        $this->withHeaders(['Referer' => 'http://localhost/', 'Origin' => 'http://evil.test'])->getJson('http://localhost'.$this->path($site, 'config'))->assertForbidden();
        $this->withHeaders(['Referer' => 'http://localhost/', 'Origin' => 'http://evil.test'])->postJson('http://localhost'.$this->path($site, 'conversations'))->assertForbidden();
    }

    public function test_conversation_tokens_expire_and_cannot_access_another_website(): void
    {
        $site = $this->site();
        $other = $this->site();
        $session = $this->chatSession($site);
        $conversation = Conversation::first();
        $this->assertSame(hash('sha256', $session['token']), $conversation->token_hash);
        $this->assertArrayNotHasKey('token_hash', $conversation->toArray());
        $this->ask($other, $session)->assertForbidden();
        $this->ask($site, [...$session, 'token' => 'wrong-token'])->assertForbidden();
        $conversation->update(['expires_at' => now()->subSecond()]);
        $this->ask($site, $session)->assertForbidden();
        $this->assertSame(0, Message::count());
    }

    public function test_answers_are_grounded_in_only_the_requested_websites_knowledge_and_support_followups(): void
    {
        $site = $this->site();
        $other = $this->site();
        $source = $this->source($site);
        $this->source($other, 'OTHER WEBSITE SECRET');
        $session = $this->chatSession($site);
        $this->ask($site, $session)->assertOk()->assertJsonPath('status', 'answered')->assertJsonPath('sources.0.url', $source->url);
        $this->assertCount(1, $this->ai->context);
        $this->assertStringNotContainsString('OTHER WEBSITE SECRET', json_encode($this->ai->context));
        $this->ask($site, $session, 'Tell me more about that.')->assertOk();
        $this->assertCount(1, $this->ai->history);
        $this->ask($site, $session, 'কী সেবা প্রদান করেন?')->assertOk()->assertJsonPath('answer', 'ওয়েব অ্যাপ্লিকেশন তৈরি করা হয়।');
    }

    public function test_unknown_sources_and_empty_knowledge_return_an_honest_fallback(): void
    {
        $site = $this->site();
        $session = $this->chatSession($site);
        $this->ask($site, $session)->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('sources', []);
        $this->assertSame(0, $this->ai->answerCalls);
        $this->source($site);
        $this->ai->citations = [999999];
        $this->ask($site, $session, 'অজানা প্রশ্ন')->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonPath('sources', []);
    }

    public function test_provider_failure_is_clear_and_does_not_expose_secrets(): void
    {
        $site = $this->site();
        $this->source($site);
        $session = $this->chatSession($site);
        $this->ai->fail = true;
        $this->ask($site, $session)->assertStatus(503)->assertJsonPath('status', 'unavailable')->assertDontSee('synthetic-test-key')->assertHeader('Access-Control-Allow-Origin', 'https://example.test');
        $this->assertSame('unavailable', Message::first()->status);
    }

    public function test_validation_minute_and_daily_limits_precede_provider_calls(): void
    {
        $site = $this->site(['daily_limit' => 1]);
        $session = $this->chatSession($site);
        $this->ask($site, $session, str_repeat('x', 2001))->assertUnprocessable();
        $this->assertSame(0, Message::count());
        $this->ask($site, $session)->assertOk();
        $this->ask($site, $session)->assertTooManyRequests();
        $site->update(['daily_limit' => 500, 'minute_limit' => 1]);
        $this->ask($site, $session)->assertTooManyRequests();
        $this->assertSame(1, Message::count());
        $this->assertSame(0, $this->ai->embedCalls);
    }

    public function test_unpublished_private_changed_deleted_and_disabled_sources_are_excluded_immediately(): void
    {
        $site = $this->site(['is_local' => true]);
        $post = $this->article(['body' => '<p>Public engineering details.</p>']);
        $this->article(['title' => 'Private secret', 'is_private' => true]);
        $snapshot = app(LocalContent::class)->snapshot($site);
        $this->assertStringNotContainsString('Private secret', json_encode($snapshot));
        $key = 'post:'.$post->id;
        $source = app(Knowledge::class)->save($site, $key, 'local', $snapshot[$key]);
        app(Knowledge::class)->index($source);
        $retriever = app(DatabaseRetriever::class);
        $this->assertNotEmpty($retriever->search($site, 'Engineering'));
        $post->update(['is_private' => true]);
        $this->assertSame([], $retriever->search($site, 'Engineering'));
        $post->update(['is_private' => false, 'body' => 'Changed text']);
        $this->assertSame([], $retriever->search($site, 'Engineering'));
        $post->update(['body' => '<p>Public engineering details.</p>']);
        $this->assertNotEmpty($retriever->search($site, 'Engineering'));
        $source->update(['enabled' => false]);
        $this->assertSame([], $retriever->search($site, 'Engineering'));
        $source->update(['enabled' => true]);
        $post->delete();
        $this->assertSame([], $retriever->search($site, 'Engineering'));
    }

    public function test_indexing_is_repeatable_bounded_and_atomic_on_provider_failure(): void
    {
        $site = $this->site();
        $source = $this->source($site);
        $calls = $this->ai->embedCalls;
        app(Knowledge::class)->index($source);
        $this->assertSame($calls, $this->ai->embedCalls);
        $changed = app(Knowledge::class)->save($site, $source->source_key, 'faq', ['title' => 'New text', 'url' => $source->url, 'content' => 'New approved knowledge.']);
        $this->ai->fail = true;
        try {
            app(Knowledge::class)->index($changed);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic provider failure', $e->getMessage());
        }
        $this->assertSame(1, Chunk::count());
        $this->assertSame('pending', $changed->fresh()->status);
        $this->ai->fail = false;
        app(Knowledge::class)->index($changed);
        $this->assertSame(1, Chunk::count());
        config(['ai_pro.max_chunks' => 1]);
        $next = app(Knowledge::class)->save($site, 'too-large', 'faq', ['title' => 'Large', 'url' => $site->url, 'content' => str_repeat('a', 2000)]);
        $calls = $this->ai->embedCalls;
        try {
            app(Knowledge::class)->index($next);
            $this->fail('Expected limit');
        } catch (\RuntimeException) {
            $this->assertSame($calls, $this->ai->embedCalls);
        }
    }

    public function test_sync_records_progress_and_retries_failed_sources_without_resetting_unchanged_pages(): void
    {
        $site = $this->site();
        $map = app(Knowledge::class)->save($site, 'map', 'sitemap', ['title' => 'Sitemap', 'url' => $site->url.'/sitemap.xml', 'content' => '']);
        $fetcher = $this->mock(SafePageFetcher::class);
        $fetcher->shouldReceive('validate')->andReturn(['example.test', ['93.184.216.34']]);
        $fetcher->shouldReceive('fetch')->withArgs(fn ($s, $url) => str_ends_with($url, '.xml'))->andReturn('<urlset><url><loc>https://example.test/services</loc></url></urlset>');
        $fetcher->shouldReceive('fetch')->withArgs(fn ($s, $url) => str_ends_with($url, '/services'))->andReturn('<html><title>Services</title><main>We build applications.</main></html>');
        $job = new SyncSite($site->id);
        $job->handle(app(Knowledge::class), app(LocalContent::class), $fetcher);
        $this->assertSame('completed', SyncRun::latest('id')->first()->status);
        $this->assertSame('ready', $map->fresh()->status);
        $this->assertSame(1, Chunk::count());
        $calls = $this->ai->embedCalls;
        $job->handle(app(Knowledge::class), app(LocalContent::class), $fetcher);
        $this->assertSame($calls, $this->ai->embedCalls);
        $source = Source::where('kind', 'page')->first();
        $source->update(['status' => 'failed']);
        $this->ai->fail = true;
        $job->handle(app(Knowledge::class), app(LocalContent::class), $fetcher);
        $this->assertSame('partial', SyncRun::latest('id')->first()->status);
        $this->ai->fail = false;
        $job->handle(app(Knowledge::class), app(LocalContent::class), $fetcher);
        $this->assertSame('ready', $source->fresh()->status);
    }

    public function test_imports_reject_private_networks_other_hosts_and_robots_exclusions(): void
    {
        $fetcher = app(SafePageFetcher::class);
        $site = $this->site(['url' => 'https://127.0.0.1']);
        foreach (['https://127.0.0.1/admin', 'https://evil.test/', 'http://127.0.0.1/', 'https://user:pass@127.0.0.1/'] as $url) {
            try {
                $fetcher->validate($site, $url);
                $this->fail('Unsafe import accepted');
            } catch (\RuntimeException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $rules = "User-agent: *\nDisallow: /private\nAllow: /private/public\nUser-agent: OtherBot\nDisallow: /";
        $this->assertFalse($fetcher->allowedByRobots($rules, '/private/page'));
        $this->assertTrue($fetcher->allowedByRobots($rules, '/private/public/page'));
        $this->assertTrue($fetcher->allowedByRobots($rules, '/services'));
        $this->assertTrue($fetcher->allowedByRobots("User-agent: *\nDisallow: /\nUser-agent: SiteSageAI\nAllow: /", '/services'));
    }

    public function test_provider_http_contract_uses_server_credentials_and_validates_structured_answers(): void
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['data' => [['index' => 0, 'embedding' => [1, 0]]]]),
            'api.openai.com/v1/responses' => Http::response(['output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => '{"answer":"Website answer","source_ids":[1]}']]]], 'usage' => ['output_tokens' => 10]]),
        ]);
        $provider = new OpenAiProvider;
        $this->assertSame([[1, 0]], $provider->embed(['Website content']));
        $result = $provider->answer('What do you offer?', [['id' => 1, 'text' => 'Website source']], []);
        $this->assertSame([1], $result['source_ids']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/responses') && $request['store'] === false && $request['text']['format']['strict'] === true && $request->hasHeader('Authorization', 'Bearer synthetic-test-key'));
    }

    public function test_maintenance_purges_expired_chats_and_coalesces_sync_requests(): void
    {
        $site = $this->site();
        $session = $this->chatSession($site);
        $this->ask($site, $session)->assertOk();
        Conversation::first()->update(['expires_at' => now()->subDay()]);
        $first = SyncSite::enqueue($site->id);
        $second = SyncSite::enqueue($site->id);
        $this->assertSame($first->id, $second->id);
        $this->artisan('ai-pro:maintain')->assertSuccessful();
        $this->assertSame(0, Conversation::count());
        $this->assertSame(0, Message::count());
        $this->assertSame(1, SyncRun::count());
    }

    public function test_content_changes_during_sync_schedule_a_followup_run(): void
    {
        $site = $this->site();
        $this->source($site);
        $run = SyncSite::enqueue($site->id);
        $knowledge = $this->mock(Knowledge::class);
        $knowledge->shouldReceive('index')->once()->andReturnUsing(function () use ($site) {
            SyncSite::enqueue($site->id);
        });
        (new SyncSite($site->id, $run->id))->handle($knowledge, app(LocalContent::class), app(SafePageFetcher::class));
        $this->assertSame('completed', $run->fresh()->status);
        $this->assertSame(1, SyncRun::where('site_id', $site->id)->where('status', 'queued')->count());
    }

    public function test_api_errors_are_json_even_without_an_accept_header(): void
    {
        $site = $this->site();
        $this->withHeader('Origin', 'https://example.test')->post($this->path($site, 'messages'), ['conversation_id' => 'missing', 'question' => 'Hello'])
            ->assertForbidden()->assertHeader('Content-Type', 'application/json')->assertHeader('Access-Control-Allow-Origin', 'https://example.test');
        $fetcher = app(SafePageFetcher::class);
        foreach (['127.0.0.1', '10.0.0.1', '100.64.0.1', '169.254.169.254', '192.168.1.1', '198.18.0.1', '::1', '::ffff:127.0.0.1', 'fc00::1', '2002:7f00:1::'] as $ip) {
            $this->assertFalse($fetcher->publicAddress($ip));
        }
        $this->assertTrue($fetcher->publicAddress('8.8.8.8'));
        $this->assertTrue($fetcher->publicAddress('2606:4700:4700::1111'));
    }

    public function test_global_disable_and_missing_installation_leave_public_pages_available(): void
    {
        $site = $this->site();
        config(['ai_pro.enabled' => false]);
        $this->withHeader('Origin', $site->origins[0])->getJson($this->path($site, 'config'))->assertStatus(503);
        $this->getJson('/ai-pro/local-config')->assertOk()->assertJson(['site' => null]);
        $this->get('/')->assertOk();
    }
}

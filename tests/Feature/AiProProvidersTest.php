<?php

namespace Tests\Feature;

use App\AiPro\AnswerFormat;
use App\AiPro\Contracts\AiProvider;
use App\AiPro\GeminiProvider;
use App\AiPro\Jobs\SyncSite;
use App\AiPro\Knowledge;
use App\AiPro\LocalContent;
use App\AiPro\Models\Site;
use App\AiPro\Models\Source;
use App\AiPro\Models\SyncRun;
use App\AiPro\ProviderFailure;
use App\AiPro\Providers;
use App\AiPro\SafePageFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class AiProProvidersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
        config(['ai_pro.provider' => 'gemini', 'ai_pro.embedding_provider' => 'gemini', 'ai_pro.model' => 'gemini-3.8-flash',
            'ai_pro.embedding_model' => 'gemini-embedding-2', 'ai_pro.api_key' => 'synthetic-google-key', 'ai_pro.embedding_api_key' => 'synthetic-embedding-key']);
    }

    private function answerResponse(): array
    {
        return ['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"answer":"We build web applications.","source_ids":[7]}']]]]],
            'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 20]];
    }

    public function test_gemini_creates_one_embedding_per_text_with_document_and_query_prefixes(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['embedding' => ['values' => [0.5, 0.5]]])]);
        $provider = app(AiProvider::class);
        $this->assertInstanceOf(Providers::class, $provider);
        $this->assertSame([[0.5, 0.5], [0.5, 0.5]], $provider->embed(["Services\nWe build applications.", 'Contact us.']));
        $provider->embed(['What services do you offer?'], 'query');
        Http::assertSentCount(3);
        Http::assertSent(fn ($r) => $r->hasHeader('x-goog-api-key', 'synthetic-embedding-key') && $r['outputDimensionality'] === 768 && $r['content']['parts'][0]['text'] === 'title: Services | text: We build applications.');
        Http::assertSent(fn ($r) => $r['content']['parts'][0]['text'] === 'task: question answering | query: What services do you offer?');
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'synthetic-') || str_contains($r->url(), 'asyncBatch'));
    }

    public function test_gemini_uses_structured_json_and_preserves_grounding_instructions(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->answerResponse())]);
        $result = app(AiProvider::class)->answer('What services?', [['id' => 7, 'text' => 'We build applications.']], []);
        $this->assertSame([7], $result['source_ids']);
        $this->assertSame('We build web applications.', $result['answer']);
        Http::assertSent(fn ($r) => $r->hasHeader('x-goog-api-key', 'synthetic-google-key') && $r['generationConfig']['responseJsonSchema'] === AnswerFormat::schema()
            && str_contains($r['systemInstruction']['parts'][0]['text'], 'untrusted data') && ! isset($r['tools']));
    }

    public function test_chat_and_embedding_providers_have_separate_credentials_and_endpoints(): void
    {
        config(['ai_pro.embedding_provider' => 'openai', 'ai_pro.embedding_model' => 'text-embedding-3-small']);
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response(['data' => [['index' => 0, 'embedding' => [1, 0]]]]),
            'generativelanguage.googleapis.com/*' => Http::response($this->answerResponse()),
        ]);
        $provider = app(AiProvider::class);
        $provider->embed(['Website source']);
        $provider->answer('Question', [], []);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.openai.com') && $r->hasHeader('Authorization', 'Bearer synthetic-embedding-key') && ! $r->hasHeader('x-goog-api-key'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'googleapis.com') && $r->hasHeader('x-goog-api-key', 'synthetic-google-key') && ! $r->hasHeader('Authorization'));
    }

    public function test_compatible_services_use_configured_base_url_and_can_use_separate_gemini_embeddings(): void
    {
        config(['ai_pro.provider' => 'compatible', 'ai_pro.compatible_base_url' => 'https://provider.example.test/v1', 'ai_pro.model' => 'custom-chat']);
        Http::fake([
            'provider.example.test/v1/chat/completions' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"answer":"A sourced answer.","source_ids":[7]}']]]]),
            'generativelanguage.googleapis.com/*' => Http::response(['embedding' => ['values' => [1, 0]]]),
        ]);
        $provider = app(AiProvider::class);
        $this->assertSame([7], $provider->answer('Question', [], [])['source_ids']);
        $provider->embed(['A source']);
        Http::assertSent(fn ($r) => $r->url() === 'https://provider.example.test/v1/chat/completions' && $r['model'] === 'custom-chat' && $r['response_format']['type'] === 'json_object');
        config(['ai_pro.embedding_provider' => 'compatible', 'ai_pro.embedding_model' => 'custom-embedding']);
        Http::fake(['provider.example.test/v1/embeddings' => Http::response(['data' => [['index' => 1, 'embedding' => [0, 1]], ['index' => 0, 'embedding' => [1, 0]]]])]);
        $this->assertSame([[1, 0], [0, 1]], $provider->embed(['First', 'Second']));
    }

    public function test_provider_readiness_checks_selected_models_and_keys_and_unknown_providers_fail_closed(): void
    {
        $this->assertTrue(Providers::ready());
        config(['ai_pro.embedding_api_key' => '']);
        $this->assertFalse(Providers::ready());
        config(['ai_pro.embedding_api_key' => 'synthetic', 'ai_pro.provider' => 'unsupported']);
        $this->assertFalse(Providers::ready());
        $this->expectException(RuntimeException::class);
        app(AiProvider::class)->answer('Question', [], []);
    }

    public function test_invalid_credentials_stop_sync_after_one_request_and_do_not_publish_provider_details(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'synthetic secret provider body']], 403)]);
        [$site, $run] = $this->pendingSite();
        (new SyncSite($site->id, $run->id))->handle(app(Knowledge::class), app(LocalContent::class), app(SafePageFetcher::class));
        $this->assertSame('failed', $run->fresh()->status);
        $this->assertStringNotContainsString('synthetic secret', json_encode($run->fresh()->errors));
        $this->assertSame(1, Source::where('status', 'failed')->count());
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'openai.com'));
        Queue::assertNothingPushed();
    }

    public function test_google_invalid_key_errors_are_credentials_errors_even_when_status_is_400(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['details' => [['reason' => 'API_KEY_INVALID']]]], 400)]);
        try {
            (new GeminiProvider)->embed(['Source']);
            $this->fail('Expected credential failure.');
        } catch (ProviderFailure $error) {
            $this->assertSame('credentials', $error->reason);
            $this->assertNull($error->retryAfter);
        }
    }

    public function test_quota_errors_schedule_a_bounded_delayed_retry_without_fallback(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['details' => [['retryDelay' => '90s']]]], 429, ['Retry-After' => '80'])]);
        [$site, $run] = $this->pendingSite();
        (new SyncSite($site->id, $run->id))->handle(app(Knowledge::class), app(LocalContent::class), app(SafePageFetcher::class));
        $this->assertSame('queued', $run->fresh()->status);
        Queue::assertPushed(SyncSite::class, fn ($job) => $job->runId === $run->id && $job->providerRetries === 1 && $job->delay->greaterThanOrEqualTo(now()->addSeconds(89)));
        Http::assertSentCount(1);
        (new SyncSite($site->id, $run->id, 3))->handle(app(Knowledge::class), app(LocalContent::class), app(SafePageFetcher::class));
        $this->assertSame('failed', $run->fresh()->status);
        Queue::assertPushed(SyncSite::class, 1);
    }

    public function test_malformed_embeddings_truncated_answers_and_invalid_source_ids_are_rejected(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['embedding' => ['values' => []]])]);
        try {
            (new GeminiProvider)->embed(['Source']);
            $this->fail('Expected invalid embedding');
        } catch (RuntimeException $e) {
            $this->assertSame('Invalid provider embedding.', $e->getMessage());
        }
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'MAX_TOKENS']]])]);
        try {
            (new GeminiProvider)->answer('Q', [], []);
            $this->fail('Expected incomplete answer');
        } catch (RuntimeException $e) {
            $this->assertSame('Gemini did not return a complete answer.', $e->getMessage());
        }
        $this->expectException(RuntimeException::class);
        AnswerFormat::parse('{"answer":"Bad citations","source_ids":["7"]}');
    }

    public function test_compatible_embedding_spaces_are_isolated_when_endpoint_changes(): void
    {
        config(['ai_pro.embedding_provider' => 'compatible', 'ai_pro.embedding_model' => 'shared-name', 'ai_pro.compatible_base_url' => 'https://first.test/v1']);
        $first = Providers::embeddingIdentity();
        config(['ai_pro.compatible_base_url' => 'https://second.test/v1']);
        $this->assertNotSame($first, Providers::embeddingIdentity());
        config(['ai_pro.embedding_provider' => 'gemini', 'ai_pro.embedding_model' => 'gemini-embedding-2']);
        $this->assertSame('gemini-embedding-2', Providers::embeddingIdentity());
    }

    private function pendingSite(): array
    {
        $site = Site::create(['public_id' => 'provider-test', 'name' => 'Website', 'url' => 'https://example.test', 'origins' => ['https://example.test'], 'is_local' => false]);
        foreach (['First', 'Second'] as $key) {
            app(Knowledge::class)->save($site, $key, 'faq', ['title' => $key, 'url' => $site->url, 'content' => 'Approved public content.']);
        }

        return [$site, SyncRun::create(['site_id' => $site->id])];
    }
}

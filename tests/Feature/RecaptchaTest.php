<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class RecaptchaTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['recaptcha.version' => 'v2', 'recaptcha.enabled' => true, 'recaptcha.site_key' => 'public-test-key', 'recaptcha.secret_key' => 'private-test-secret', 'recaptcha.hostnames' => ['example.test']]);
        Http::preventStrayRequests();
        Queue::fake();
    }

    public function test_all_guest_submission_endpoints_require_a_token_before_side_effects(): void
    {
        $owner = $this->user(1, ['portfolio_id' => 'ENGINEER', 'portfolio_status' => 1]);
        config(['site.owner_id' => $owner->id]);
        foreach (['/inquiries', '/contacts', '/portfolios/ENGINEER/inquiries', '/newsletters', '/login', '/register', '/password/email', '/password/reset'] as $path) {
            $this->postJson($path, ['name' => 'Guest', 'email' => 'guest@example.test', 'subject' => 'Project', 'message' => 'A useful project.', 'password' => 'password', 'password_confirmation' => 'password'])
                ->assertUnprocessable()->assertJsonValidationErrors('g-recaptcha-response');
        }
        $this->assertSame(0, Contact::count());
        $this->assertSame(0, Newsletter::count());
        $this->assertDatabaseMissing('users', ['email' => 'guest@example.test']);
        $this->assertGuest();
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_valid_challenge_allows_inquiry_and_never_exposes_the_secret(): void
    {
        $owner = $this->user(1);
        config(['site.owner_id' => $owner->id]);
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true, 'hostname' => 'example.test'])]);
        $this->post('/inquiries', ['name' => 'Guest', 'email' => 'guest@example.test', 'subject' => 'Project', 'message' => 'A useful project.', 'g-recaptcha-response' => 'single-use-token'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, Contact::count());
        Http::assertSent(fn ($request) => $request['secret'] === 'private-test-secret' && $request['response'] === 'single-use-token');
        $response = $this->get('/contact/info')->assertInertia(fn (Assert $page) => $page->where('recaptcha.enabled', true)->where('recaptcha.siteKey', 'public-test-key'));
        $response->assertDontSee('private-test-secret');
        $response->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString('https://www.google.com/recaptcha/', $response->headers->get('Content-Security-Policy'));
    }

    public function test_invalid_expired_reused_and_wrong_hostname_tokens_are_rejected(): void
    {
        $results = [['success' => false, 'error-codes' => ['timeout-or-duplicate']], ['success' => true, 'hostname' => 'attacker.test'], ['success' => true], ['success' => 'true', 'hostname' => 'example.test']];
        $sequence = Http::sequence();
        foreach ($results as $result) {
            $sequence->push($result);
        }
        Http::fake(['www.google.com/recaptcha/api/siteverify' => $sequence]);
        foreach ($results as $result) {
            $this->postJson('/newsletters', ['email' => 'guest@example.test', 'g-recaptcha-response' => 'invalid-token'])->assertUnprocessable()->assertJsonValidationErrors('g-recaptcha-response');
        }
        $this->assertSame(0, Newsletter::count());
    }

    public function test_outage_and_incomplete_configuration_fail_closed(): void
    {
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::failedConnection()]);
        $this->postJson('/newsletters', ['email' => 'guest@example.test', 'g-recaptcha-response' => 'token'])->assertUnprocessable()->assertJsonValidationErrors('g-recaptcha-response');
        config(['recaptcha.secret_key' => '']);
        Http::fake();
        $this->postJson('/newsletters', ['email' => 'guest@example.test', 'g-recaptcha-response' => 'token'])->assertUnprocessable()->assertJsonValidationErrors('g-recaptcha-response');
        Http::assertNothingSent();
        $this->assertSame(0, Newsletter::count());
    }

    public function test_v3_requires_the_expected_action_and_a_valid_score(): void
    {
        config(['recaptcha.version' => 'v3', 'recaptcha.minimum_score' => 0.5]);
        $results = [['success' => true, 'hostname' => 'example.test', 'score' => 0.4, 'action' => 'newsletter'],
            ['success' => true, 'hostname' => 'example.test', 'score' => 0.9, 'action' => 'login'],
            ['success' => true, 'hostname' => 'example.test', 'action' => 'newsletter'],
            ['success' => true, 'hostname' => 'example.test', 'score' => '0.9', 'action' => 'newsletter']];
        $sequence = Http::sequence();
        foreach ($results as $result) {
            $sequence->push($result);
        }
        $sequence->push(['success' => true, 'hostname' => 'example.test', 'score' => 0.5, 'action' => 'newsletter']);
        Http::fake(['www.google.com/recaptcha/api/siteverify' => $sequence]);
        foreach ($results as $result) {
            $this->postJson('/newsletters', ['email' => 'v3@example.test', 'g-recaptcha-response' => 'token'])->assertUnprocessable()->assertJsonValidationErrors('g-recaptcha-response');
        }
        $this->assertSame(0, Newsletter::count());
        $this->postJson('/newsletters', ['email' => 'v3@example.test', 'g-recaptcha-response' => 'new-token'])->assertOk();
        $this->assertSame(1, Newsletter::count());
    }

    public function test_disabled_integration_preserves_forms_and_does_not_contact_google(): void
    {
        config(['recaptcha.enabled' => false]);
        $this->postJson('/newsletters', ['email' => 'guest@example.test'])->assertOk();
        $this->get('/blogs?key=engineering')->assertOk();
        Http::assertNothingSent();
    }
}

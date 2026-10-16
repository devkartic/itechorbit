<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialProviders;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as ProviderUser;
use Mockery;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private object $socialiteManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->socialiteManager = Socialite::getFacadeRoot();
        $this->user(); // Seed normal application roles.
        foreach (['google', 'github', 'linkedin-openid'] as $driver) {
            config(['services.'.$driver => ['client_id' => 'test-client', 'client_secret' => 'test-secret', 'redirect' => 'http://localhost/login/'.($driver === 'linkedin-openid' ? 'linkedin' : $driver).'/callback']]);
        }
    }

    private function context(string $provider, ?User $user = null): array
    {
        return ['social_login' => ['provider' => $provider, 'connect' => $user !== null, 'user_id' => $user?->id, 'expires' => now()->addMinutes(10)->timestamp]];
    }

    private function remote(string $id = 'provider-42', ?string $email = 'social@example.test', bool $verified = true): ProviderUser
    {
        return (new ProviderUser)->setRaw(['email_verified' => $verified])->map(['id' => $id, 'name' => 'Social Engineer', 'email' => $email]);
    }

    private function mockUser(string $provider, ProviderUser $user): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->once()->andReturn($user);
        Socialite::shouldReceive('driver')->with(app(SocialProviders::class)->driver($provider))->andReturn($driver);
    }

    public function test_auth_buttons_only_receive_complete_public_configuration_and_wait_for_migration(): void
    {
        foreach (['/login', '/register'] as $url) {
            $this->get($url)->assertInertia(fn (Assert $page) => $page->has('socialProviders', 3)->where('socialProviders.0.name', 'Google')->missing('socialProviders.0.client_secret'));
        }
        config(['services.github.client_secret' => null]);
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->has('socialProviders', 2));
        $this->get('/login/github')->assertRedirect('/login')->assertSessionHasErrors('social');
        $this->get('/login/unsupported')->assertNotFound();
        Schema::drop('social_accounts');
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->has('socialProviders', 0));
        $this->get('/login/google')->assertRedirect('/login')->assertSessionHasErrors('social');
    }

    public function test_all_providers_register_regular_users_and_returning_identity_login_does_not_duplicate(): void
    {
        foreach (['google', 'linkedin', 'github'] as $provider) {
            $this->mockUser($provider, $this->remote($provider.'-id', $provider.'@example.test'));
            $this->withSession($this->context($provider))->get('/login/'.$provider.'/callback')->assertRedirect('/home');
            $user = User::where('email', $provider.'@example.test')->firstOrFail();
            $this->assertAuthenticatedAs($user);
            $this->assertSame(4, (int) $user->role_id);
            $this->assertSame(0, (int) $user->portfolio_status);
            $this->assertNotNull($user->email_verified_at);
            $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => $provider, 'provider_user_id' => $provider.'-id']);
            $this->assertFalse(session()->has('social_login'));
            $session = session()->getId();
            Auth::logout();
            Socialite::swap($this->socialiteManager);
            // A returning connection is resolved by identity even if the provider's email changes or disappears.
            $this->mockUser($provider, $this->remote($provider.'-id', null, false));
            $this->withSession($this->context($provider))->get('/login/'.$provider.'/callback')->assertRedirect('/home');
            $this->assertAuthenticatedAs($user);
            $this->assertNotSame($session, session()->getId());
            $this->assertSame(1, User::where('email', $provider.'@example.test')->count());
            $this->assertSame(1, SocialAccount::where('provider', $provider)->count());
            Auth::logout();
            Socialite::swap($this->socialiteManager);
        }
    }

    public function test_existing_email_is_not_automatically_linked_and_explicit_connection_preserves_account(): void
    {
        $user = $this->user(1, ['email' => 'owner@example.test', 'portfolio_status' => 1]);
        $this->mockUser('google', $this->remote('google-id', 'OWNER@example.test'));
        $this->withSession($this->context('google'))->get('/login/google/callback')->assertRedirect('/login')->assertSessionHasErrors('social');
        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
        Socialite::swap($this->socialiteManager);
        $this->mockUser('google', $this->remote('google-id', 'different@example.test'));
        $this->actingAs($user)->withSession($this->context('google', $user))->get('/login/google/callback')->assertRedirect('/account/settings')->assertSessionHas('success');
        $this->assertSame('owner@example.test', $user->fresh()->email);
        $this->assertSame(1, (int) $user->fresh()->role_id);
        $this->assertSame(1, (int) $user->fresh()->portfolio_status);
        $this->get('/account/settings')->assertInertia(fn (Assert $page) => $page->where('socialProviders.0.connected', true)->missing('socialProviders.0.provider_user_id')->missing('socialProviders.0.client_secret'));
    }

    public function test_connections_require_authenticated_active_user_and_cannot_be_reassigned_or_replaced(): void
    {
        $this->post('/account/connections/github')->assertRedirect('/login');
        $disabled = $this->user(4, ['status' => 0]);
        $this->actingAs($disabled)->post('/account/connections/github')->assertForbidden();
        $owner = $this->user();
        SocialAccount::create(['user_id' => $owner->id, 'provider' => 'github', 'provider_user_id' => 'existing']);
        $other = $this->user();
        $this->mockUser('github', $this->remote('existing'));
        $this->actingAs($other)->withSession($this->context('github', $other))->get('/login/github/callback')->assertRedirect('/account/settings')->assertSessionHasErrors('social');
        $this->assertDatabaseCount('social_accounts', 1);
        Socialite::swap($this->socialiteManager);
        $this->mockUser('github', $this->remote('new-id'));
        $this->actingAs($owner)->withSession($this->context('github', $owner))->get('/login/github/callback')->assertSessionHasErrors('social');
        $this->assertDatabaseHas('social_accounts', ['user_id' => $owner->id, 'provider_user_id' => 'existing']);
    }

    public function test_new_accounts_require_verified_email_and_disabled_connections_cannot_login(): void
    {
        foreach ([['google', null, true], ['google', 'test@example.test', false], ['linkedin', 'test@example.test', false], ['github', null, true]] as [$provider, $email, $verified]) {
            $this->mockUser($provider, $this->remote('new-id', $email, $verified));
            $this->withSession($this->context($provider))->get('/login/'.$provider.'/callback')->assertRedirect('/login')->assertSessionHasErrors('social');
            $this->assertGuest();
            Socialite::swap($this->socialiteManager);
        }
        $this->assertDatabaseCount('social_accounts', 0);
        $disabled = $this->user(4, ['status' => 0]);
        SocialAccount::create(['user_id' => $disabled->id, 'provider' => 'google', 'provider_user_id' => 'disabled-id']);
        $this->mockUser('google', $this->remote('disabled-id'));
        $this->withSession($this->context('google'))->get('/login/google/callback')->assertForbidden();
        $this->assertGuest();
    }

    public function test_expiry_cancellation_callback_replay_and_account_switch_fail_safely(): void
    {
        $this->get('/login/google/callback')->assertRedirect('/login')->assertSessionHasErrors('social');
        $expired = $this->context('google');
        $expired['social_login']['expires'] = now()->subMinute()->timestamp;
        $this->withSession($expired)->get('/login/google/callback')->assertSessionHasErrors('social');
        $this->withSession($this->context('google'))->get('/login/github/callback')->assertSessionHasErrors('social');
        $this->withSession($this->context('google'))->get('/login/google/callback?error=access_denied')->assertSessionHasErrors('social');
        $this->get('/login/google/callback')->assertSessionHasErrors('social');
        $first = $this->user();
        $second = $this->user();
        $this->actingAs($second)->withSession($this->context('google', $first))->get('/login/google/callback')->assertRedirect('/login')->assertSessionHasErrors('social');
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_oauth_state_and_provider_failures_are_handled_without_exposing_tokens(): void
    {
        foreach ([new InvalidStateException, new \RuntimeException('secret-token-provider-response')] as $exception) {
            $driver = Mockery::mock(Provider::class);
            $driver->shouldReceive('user')->once()->andThrow($exception);
            Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
            $this->withSession($this->context('google'))->get('/login/google/callback')->assertRedirect('/login')->assertSessionHasErrors('social');
            $this->assertStringNotContainsString('secret-token', session('errors')->first('social'));
            Socialite::swap($this->socialiteManager);
        }
    }

    public function test_real_provider_redirects_keep_state_and_linkedin_uses_openid_scopes(): void
    {
        foreach (['google', 'linkedin', 'github'] as $provider) {
            $response = $this->get('/login/'.$provider)->assertRedirect()->assertSessionHas('social_login.provider', $provider)->assertSessionHas('state');
            parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
            $this->assertSame(session('state'), $query['state']);
            if ($provider === 'linkedin') {
                $this->assertSame('openid profile email', $query['scope']);
            }
        }
        $user = $this->user();
        $this->actingAs($user)->post('/account/connections/google', [], ['X-Inertia' => 'true'])->assertStatus(409)->assertHeader('X-Inertia-Location')->assertSessionHas('social_login.user_id', $user->id);
    }

    public function test_github_uses_verified_primary_email_instead_of_public_profile_email(): void
    {
        $driver = Socialite::driver('github');
        $handler = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'synthetic-token', 'token_type' => 'bearer'])),
            new Response(200, [], json_encode(['id' => 1234, 'node_id' => 'node-1234', 'avatar_url' => 'https://example.test/avatar.png', 'name' => 'GitHub Engineer', 'email' => 'untrusted@example.test', 'login' => 'engineer'])),
            new Response(200, [], json_encode([
                ['email' => 'unverified@example.test', 'primary' => true, 'verified' => false],
                ['email' => 'secondary@example.test', 'primary' => false, 'verified' => true],
                ['email' => 'verified@example.test', 'primary' => true, 'verified' => true],
            ])),
        ]);
        $driver->setHttpClient(new Client(['handler' => HandlerStack::create($handler)]));
        $callback = Request::create('/login/github/callback', 'GET', ['state' => 'synthetic-state', 'code' => 'synthetic-code']);
        $callback->setLaravelSession(app('session')->driver());
        $driver->setRequest($callback);
        $this->withSession([...$this->context('github'), 'state' => 'synthetic-state'])->get('/login/github/callback?state=synthetic-state&code=synthetic-code')->assertRedirect('/home');
        $this->assertSame('verified@example.test', Auth::user()->email);
        $this->assertDatabaseMissing('users', ['email' => 'untrusted@example.test']);
    }

    public function test_migration_preserves_legacy_google_identity_and_database_prevents_duplicate_connections(): void
    {
        Schema::drop('social_accounts');
        $user = $this->user();
        $user->forceFill(['provider_id' => 'legacy-google-id'])->save();
        $migration = require database_path('migrations/2026_10_17_000002_create_social_accounts_table.php');
        $migration->up();
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'legacy-google-id']);
        $this->assertSame('legacy-google-id', $user->fresh()->provider_id);
        $other = $this->user();
        foreach ([[$other->id, 'legacy-google-id'], [$user->id, 'different-id']] as [$userId, $identity]) {
            try {
                DB::transaction(fn () => SocialAccount::create(['user_id' => $userId, 'provider' => 'google', 'provider_user_id' => $identity]));
                $this->fail('Duplicate provider identity or provider connection was accepted.');
            } catch (QueryException $exception) {
                $this->assertDatabaseCount('social_accounts', 1);
            }
        }
        $this->mockUser('google', $this->remote('legacy-google-id'));
        $this->withSession($this->context('google'))->get('/login/google/callback')->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }
}

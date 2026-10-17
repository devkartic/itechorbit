<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_account_pages_require_authentication_and_reject_disabled_users(): void
    {
        $this->get('/account/profile')->assertRedirect('/login');
        $this->get('/account/settings')->assertRedirect('/login');
        $this->patch('/account/settings', [])->assertRedirect('/login');
        $this->actingAs($this->user(4, ['status' => 0]))->get('/account/settings')->assertForbidden();
    }

    public function test_members_can_manage_their_own_account_without_admin_permissions(): void
    {
        $user = $this->user(4, ['portfolio_status' => 1, 'portfolio_id' => 'OWNPROFILE']);
        $other = $this->user();
        $this->actingAs($user)->get('/account/profile')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertInertia(fn (Assert $page) => $page->component('Account/Index')->where('account.email', $user->email)->missing('account.password')->missing('account.role_id')->where('auth.user.publicProfileUrl', '/portfolios/OWNPROFILE'));
        $this->get('/account/settings')->assertOk();
        $this->patch('/account/settings', ['name' => 'Updated member', 'email' => $user->email, 'user_id' => $other->id, 'role_id' => 1, 'status' => 0])->assertRedirect('/account/settings');
        $this->assertSame('Updated member', $user->fresh()->name);
        $this->assertSame(4, $user->fresh()->role_id);
        $this->assertSame(1, $user->fresh()->status);
        $this->assertSame($other->name, $other->fresh()->name);
    }

    public function test_email_changes_require_current_password_and_reset_verification(): void
    {
        $user = $this->user(4, ['password' => Hash::make('Existing-password-42')]);
        $other = $this->user();
        $this->actingAs($user)->patch('/account/settings', ['name' => $user->name, 'email' => 'new@example.test'])->assertSessionHasErrors('current_password');
        $this->patch('/account/settings', ['name' => $user->name, 'email' => $other->email, 'current_password' => 'Existing-password-42'])->assertSessionHasErrors('email');
        $this->patch('/account/settings', ['name' => $user->name, 'email' => 'new@example.test', 'current_password' => 'Existing-password-42'])->assertRedirect('/account/settings');
        $this->assertSame('new@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->get('/account/settings')->assertOk();
        $this->get('/home')->assertRedirect('/email/verify');
    }

    public function test_password_update_validates_current_password_and_hashes_the_new_password(): void
    {
        $user = $this->user(4, ['password' => Hash::make('Existing-password-42'), 'remember_token' => 'old-token']);
        $this->actingAs($user)->put('/account/password', ['current_password' => 'wrong', 'password' => 'New-password-42', 'password_confirmation' => 'New-password-42'])->assertSessionHasErrors('current_password');
        $this->put('/account/password', ['current_password' => 'Existing-password-42', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->put('/account/password', ['current_password' => 'Existing-password-42', 'password' => 'New-password-42', 'password_confirmation' => 'New-password-42'])->assertRedirect('/account/settings');
        $this->assertTrue(Hash::check('New-password-42', $user->fresh()->password));
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_ends_the_session_and_clears_inertia_history(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post('/logout')->assertRedirect('/')->assertSessionHas(SessionKey::CLEAR_HISTORY, true);
        $this->assertGuest();
        $this->get('/account/profile')->assertRedirect('/login');
    }
}

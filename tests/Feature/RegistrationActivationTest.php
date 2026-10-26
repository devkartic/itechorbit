<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class RegistrationActivationTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function registerMember(): User
    {
        $this->user(1);
        Notification::fake();
        $this->post('/register', ['name' => 'New member', 'email' => 'new@example.test',
            'password' => 'Registration-test-42', 'password_confirmation' => 'Registration-test-42',
            'status' => 1, 'role_id' => 1, 'email_verified_at' => now(), 'pending_email_activation' => false])
            ->assertRedirect('/email/verify');

        return User::where('email', 'new@example.test')->firstOrFail();
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);
    }

    public function test_form_registration_stays_disabled_until_signed_verification(): void
    {
        $user = $this->registerMember();
        $this->assertSame(0, (int) $user->status);
        $this->assertSame(4, (int) $user->role_id);
        $this->assertTrue($user->pending_email_activation);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/email/verify')->assertOk();
        $this->get('/account/settings')->assertForbidden();
        $this->post('/email/resend')->assertRedirect();
        $this->get($this->verificationUrl($user))->assertRedirect('/home');
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame(1, (int) $user->status);
        $this->assertFalse($user->pending_email_activation);
        $this->get('/home')->assertOk();
    }

    public function test_pending_user_can_sign_back_in_to_verify_but_admin_disabled_user_cannot(): void
    {
        $user = $this->registerMember();
        Auth::logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'Registration-test-42'])->assertRedirect('/email/verify');
        $this->assertAuthenticatedAs($user);
        $user->forceFill(['pending_email_activation' => false])->save();
        Auth::logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'Registration-test-42'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_disabling_pending_registration_prevents_activation_even_with_existing_link(): void
    {
        Mail::fake();
        $user = $this->registerMember();
        $url = $this->verificationUrl($user);
        $admin = User::where('role_id', 1)->firstOrFail();
        $this->actingAs($admin)->postJson('/user-enable-or-disable', ['user_id' => $user->id, 'is_enable' => false])->assertOk();
        $this->assertFalse($user->fresh()->pending_email_activation);
        $this->actingAs($user->fresh())->get($url)->assertRedirect('/home');
        $this->assertSame(0, (int) $user->fresh()->status);
        $this->get('/home')->assertForbidden();
    }

    public function test_replaying_verification_after_admin_disable_never_reenables_account(): void
    {
        Mail::fake();
        $user = $this->registerMember();
        $url = $this->verificationUrl($user);
        $this->get($url)->assertRedirect('/home');
        $admin = User::where('role_id', 1)->firstOrFail();
        $this->actingAs($admin)->postJson('/user-enable-or-disable', ['user_id' => $user->id, 'is_enable' => false])->assertOk();
        $this->actingAs($user->fresh())->get($url)->assertRedirect('/home');
        $this->assertSame(0, (int) $user->fresh()->status);
    }

    public function test_invalid_or_expired_verification_does_not_activate_registration(): void
    {
        $user = $this->registerMember();
        $this->get($this->verificationUrl($user).'tampered')->assertForbidden();
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($expired)->assertForbidden();
        $this->assertSame(0, (int) $user->fresh()->status);
        $this->assertNull($user->fresh()->email_verified_at);
    }
}

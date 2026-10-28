<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class PasswordResetVerificationTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function resetData(User $user, string $token): array
    {
        return ['email' => $user->email, 'token' => $token,
            'password' => 'Reset-test-password-42', 'password_confirmation' => 'Reset-test-password-42'];
    }

    public function test_valid_reset_verifies_email_and_goes_directly_to_dashboard(): void
    {
        Event::fake([Verified::class, PasswordReset::class]);
        Notification::fake();
        $user = $this->user(4, ['email_verified_at' => null]);
        $remember = $user->remember_token;
        $this->from('/password/reset')->post('/password/email', ['email' => $user->email])->assertRedirect('/password/reset');
        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post('/password/reset', $this->resetData($user, $token))->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('Reset-test-password-42', $user->password));
        $this->assertNotSame($remember, $user->remember_token);
        $this->assertFalse(Password::broker()->tokenExists($user, $token));
        $this->get('/home')->assertOk();
        Event::assertDispatched(Verified::class, fn ($event) => $event->user->id === $user->id);
        Event::assertDispatched(PasswordReset::class, fn ($event) => $event->user->id === $user->id);
        Notification::assertNotSentTo($user, VerifyEmail::class);
        Notification::assertCount(1);

        Auth::logout();
        $this->postJson('/password/reset', $this->resetData($user, $token))->assertUnprocessable();
        $this->assertGuest();
        Event::assertDispatchedTimes(Verified::class, 1);
        Event::assertDispatchedTimes(PasswordReset::class, 1);
    }

    public function test_reset_can_complete_pending_registration_without_publishing_portfolio(): void
    {
        $user = $this->user(4, ['status' => 0, 'email_verified_at' => null, 'portfolio_status' => 0]);
        $user->forceFill(['pending_email_activation' => true])->save();
        $token = Password::broker()->createToken($user);

        $this->post('/password/reset', $this->resetData($user, $token))->assertRedirect('/home');
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame(1, (int) $user->status);
        $this->assertFalse($user->pending_email_activation);
        $this->assertSame(0, (int) $user->portfolio_status);
        $this->assertSame(4, (int) $user->role_id);
        $this->get('/home')->assertOk();
    }

    public function test_already_verified_users_keep_their_verification_timestamp(): void
    {
        Event::fake([Verified::class, PasswordReset::class]);
        $user = $this->user(4, ['email_verified_at' => now()->subMonth()]);
        $verifiedAt = $user->email_verified_at->toDateTimeString();
        $token = Password::broker()->createToken($user);
        $this->post('/password/reset', $this->resetData($user, $token))->assertRedirect('/home');
        $this->assertSame($verifiedAt, $user->fresh()->email_verified_at->toDateTimeString());
        Event::assertNotDispatched(Verified::class);
        $this->get('/home')->assertOk();
    }

    public function test_invalid_expired_or_wrong_email_tokens_cannot_verify_or_change_password(): void
    {
        Event::fake([Verified::class, PasswordReset::class]);
        $user = $this->user(4, ['email_verified_at' => null]);
        $other = $this->user(4, ['email_verified_at' => null]);
        $password = $user->password;
        $token = Password::broker()->createToken($user);
        $this->postJson('/password/reset', $this->resetData($user, 'invalid-token'))->assertUnprocessable();
        $this->postJson('/password/reset', $this->resetData($other, $token))->assertUnprocessable();
        $this->postJson('/password/reset', [...$this->resetData($user, $token), 'password_confirmation' => 'different'])->assertUnprocessable();
        $this->postJson('/password/reset', [...$this->resetData($user, $token), 'password' => 'short', 'password_confirmation' => 'short'])->assertUnprocessable();
        DB::table('password_resets')->where('email', $user->email)->update(['created_at' => now()->subMinutes(61)]);
        $this->postJson('/password/reset', $this->resetData($user, $token))->assertUnprocessable();
        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
        $this->assertSame($password, $user->fresh()->password);
        Event::assertNotDispatched(Verified::class);
        Event::assertNotDispatched(PasswordReset::class);
    }

    public function test_admin_disabled_accounts_stay_disabled_even_with_a_valid_reset_token(): void
    {
        Event::fake([Verified::class, PasswordReset::class]);
        foreach ([null, now()] as $verifiedAt) {
            $user = $this->user(4, ['status' => 0, 'email_verified_at' => $verifiedAt]);
            $password = $user->password;
            $token = Password::broker()->createToken($user);
            $this->postJson('/password/reset', $this->resetData($user, $token))
                ->assertUnprocessable()->assertJsonValidationErrors('email');
            $this->assertGuest();
            $this->assertSame(0, (int) $user->fresh()->status);
            $this->assertSame($password, $user->fresh()->password);
            $this->assertSame($verifiedAt !== null, $user->fresh()->hasVerifiedEmail());
        }
        Event::assertNotDispatched(Verified::class);
        Event::assertNotDispatched(PasswordReset::class);
    }

    public function test_admin_disable_after_token_issue_cancels_pending_activation(): void
    {
        $user = $this->user(4, ['status' => 0, 'email_verified_at' => null]);
        $user->forceFill(['pending_email_activation' => true])->save();
        $token = Password::broker()->createToken($user);
        $user->forceFill(['pending_email_activation' => false])->save();
        $this->postJson('/password/reset', $this->resetData($user, $token))->assertUnprocessable();
        $this->assertGuest();
        $this->assertSame(0, (int) $user->fresh()->status);
        $this->assertNull($user->fresh()->email_verified_at);
    }
}

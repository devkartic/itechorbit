<?php

namespace Tests\Feature;

use App\Mail\SendAccountStatusNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class AccountStatusMailTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_each_actual_account_and_portfolio_toggle_queues_one_accurate_email(): void
    {
        Mail::fake();
        $admin = $this->user(1);
        $user = $this->user(4, ['status' => 0, 'portfolio_status' => 0, 'portfolio_id' => null]);
        $this->actingAs($admin);
        foreach (['user-enable-or-disable' => 'account', 'portfolio-enable-or-disable' => 'portfolio'] as $endpoint => $kind) {
            foreach ([true, false] as $enabled) {
                $payload = ['user_id' => $user->id, 'is_enable' => $enabled];
                $this->postJson('/'.$endpoint, $payload)->assertOk();
                Mail::assertQueued(SendAccountStatusNotification::class, fn ($mail) => $mail->hasTo($user->email)
                    && $mail->kind === $kind && $mail->enabled === $enabled);
                $this->postJson('/'.$endpoint, $payload)->assertOk();
            }
        }
        Mail::assertQueued(SendAccountStatusNotification::class, 4);
        $this->assertNotEmpty($user->fresh()->portfolio_id);
        $queued = Mail::queued(SendAccountStatusNotification::class);
        $this->assertTrue($queued[0]->enabled);
        $this->assertFalse($queued[1]->enabled);
        $this->assertStringContainsString('has been enabled', $queued[0]->render());
        $this->assertStringContainsString('has been disabled', $queued[1]->render());
        foreach ($queued as $mail) {
            $this->assertStringContainsString('data-mail-theme="itechorbit"', $mail->render());
        }
    }

    public function test_editing_account_status_also_notifies_but_ordinary_edits_do_not(): void
    {
        Mail::fake();
        $admin = $this->user(1);
        $user = $this->user();
        $data = ['name' => $user->name, 'email' => $user->email, 'is_checked' => false];
        $this->actingAs($admin)->putJson('/users/'.$user->id, $data)->assertOk();
        $this->putJson('/users/'.$user->id, [...$data, 'name' => 'Changed name'])->assertOk();
        Mail::assertQueued(SendAccountStatusNotification::class, 1);
        Mail::assertQueued(SendAccountStatusNotification::class, fn ($mail) => ! $mail->enabled && $mail->kind === 'account');
    }

    public function test_unauthorized_and_invalid_toggles_never_queue_email(): void
    {
        Mail::fake();
        $member = $this->user();
        $this->actingAs($member)->postJson('/user-enable-or-disable', ['user_id' => $member->id, 'is_enable' => false])->assertForbidden();
        $admin = $this->user(1);
        $this->actingAs($admin)->postJson('/user-enable-or-disable', ['user_id' => $admin->id, 'is_enable' => false])->assertForbidden();
        $this->postJson('/portfolio-enable-or-disable', ['user_id' => $member->id, 'is_enable' => 'invalid'])->assertUnprocessable();
        Mail::assertNothingOutgoing();
    }

    public function test_reset_password_request_sends_the_shared_theme_with_a_working_reset_token(): void
    {
        Notification::fake();
        $user = $this->user(4, ['name' => '<script>Member</script>']);
        $this->post('/password/email', ['email' => $user->email])->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $html = view($mail->view['html'], $mail->viewData)->render();
            $this->assertStringContainsString('data-mail-theme="itechorbit"', $html);
            $this->assertStringContainsString('&lt;script&gt;Member&lt;/script&gt;', $html);
            $this->assertStringContainsString('expires in 60 minutes', $html);
            $url = $mail->viewData['actionUrl'];
            $this->assertStringContainsString('/password/reset/'.$notification->token, $url);
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame($user->email, $query['email']);
            $this->post('/password/reset', ['token' => $notification->token, 'email' => $user->email,
                'password' => 'New-password-for-test-42', 'password_confirmation' => 'New-password-for-test-42'])->assertSessionHasNoErrors();

            return true;
        });
    }

    public function test_verification_keeps_signed_url_and_shared_theme(): void
    {
        $user = $this->user();
        $mail = (new VerifyEmail)->toMail($user);
        $html = view($mail->view['html'], $mail->viewData)->render();
        $this->assertStringContainsString('data-mail-theme="itechorbit"', $html);
        $this->assertStringContainsString('Verify email address', $html);
        $this->assertTrue(URL::hasValidSignature(Request::create($mail->viewData['actionUrl'])));
        $this->assertStringContainsString($mail->viewData['actionUrl'], view($mail->view['text'], $mail->viewData)->render());
    }
}

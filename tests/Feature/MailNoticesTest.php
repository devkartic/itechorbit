<?php

namespace Tests\Feature;

use App\Jobs\SendNoticeRecipient;
use App\Mail\SendMailNotice;
use App\Models\Admin\Role;
use App\Models\MailNoticeCampaign;
use App\Models\MailNoticeRecipient;
use App\Models\MailNoticeTemplate;
use App\Services\Admin\AdminPresentation;
use App\Services\Admin\MailNotices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use RuntimeException;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class MailNoticesTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function configured(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'smtp', 'mail.from.address' => 'notices@example.test', 'mail.from.name' => 'iTechOrbit']);
    }

    private function payload(array $values = []): array
    {
        return ['submission_key' => (string) Str::uuid(), 'subject' => 'Service update', 'body' => '<h2>News</h2><p>A safe update &amp; details.</p>', 'audience' => 'all', ...$values];
    }

    private function campaign(array $data = []): MailNoticeCampaign
    {
        return MailNoticeCampaign::create(['submission_key' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'subject' => 'Update', 'body' => '<p>News</p>', 'audience' => 'selected',
            'mailer' => 'smtp', 'from_address' => 'notices@example.test', 'from_name' => 'iTechOrbit', ...$data]);
    }

    private function recipient(MailNoticeCampaign $campaign, array $data = []): MailNoticeRecipient
    {
        return MailNoticeRecipient::create(['campaign_id' => $campaign->id, 'name' => 'Synthetic user', 'email' => 'recipient@example.test', ...$data]);
    }

    public function test_only_active_verified_admins_and_super_admins_can_access_all_notice_endpoints(): void
    {
        $this->get('/workspace/email-notices')->assertRedirect('/login');
        (new Role)->forceFill(['id' => 2, 'name' => 'Admin', 'status' => 1])->save();
        $template = MailNoticeTemplate::create(['name' => 'Test', 'subject' => 'Update', 'body' => '<p>News</p>']);
        $campaign = $this->campaign();
        $endpoints = [['get', ''], ['get', '/compose'], ['get', '/history'], ['get', '/data/templates'], ['get', '/recipients'], ['get', '/templates'], ['get', '/templates/'.$template->id], ['post', '/templates'],
            ['put', '/templates/'.$template->id], ['delete', '/templates/'.$template->id], ['post', '/preview'], ['post', '/send'], ['get', '/campaigns'], ['get', '/campaigns/'.$campaign->id], ['post', '/campaigns/'.$campaign->id.'/retry']];
        foreach ([$this->user(4), $this->user(1, ['status' => 0])] as $user) {
            $this->actingAs($user);
            foreach ($endpoints as [$method, $path]) {
                $this->json(strtoupper($method), '/workspace/email-notices'.$path)->assertForbidden();
            }
            $this->assertFalse(collect(app(AdminPresentation::class)->navigation())->contains(fn ($link) => str_starts_with($link['url'], '/workspace/email-notices/')));
        }
        $this->actingAs($this->user(1, ['email_verified_at' => null]))->get('/workspace/email-notices')->assertRedirect('/email/verify');
        foreach ([1, 2] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get('/workspace/email-notices')->assertRedirect('/workspace/email-notices/compose');
            foreach (['compose', 'templates', 'history'] as $section) {
                $this->get('/workspace/email-notices/'.$section)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/EmailNotices/'.ucfirst($section))->where('section', $section)->where('installed', true));
            }
            $link = collect(app(AdminPresentation::class)->navigation())->firstWhere('url', '/workspace/email-notices/compose');
            $this->assertSame('Administration', $link['group']);
            $this->assertSame('Email notices', $link['module']);
            $this->getJson('/workspace/email-notices/recipients')->assertOk();
            $this->getJson('/workspace/email-notices/campaigns')->assertOk();
        }
    }

    public function test_notice_page_deep_links_load_templates_and_selected_campaigns(): void
    {
        $this->actingAs($this->user(1));
        $template = MailNoticeTemplate::create(['name' => 'Reusable update', 'subject' => 'Notice subject', 'body' => '<p>Saved message</p>']);
        $campaign = $this->campaign();
        $this->get('/workspace/email-notices/compose?template='.$template->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/EmailNotices/Compose')->where('initialTemplate.id', $template->id)->where('initialTemplate.body', '<p>Saved message</p>'));
        $this->get('/workspace/email-notices/history?campaign='.$campaign->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/EmailNotices/History')->where('selectedCampaignId', $campaign->id));
        $this->get('/workspace/email-notices/compose?template=999999')->assertNotFound();
        $this->get('/workspace/email-notices/history?campaign=999999')->assertNotFound();
        $links = collect(app(AdminPresentation::class)->navigation())->where('module', 'Email notices')->values();
        $this->assertSame(['Compose', 'Templates', 'Send history'], $links->pluck('label')->all());
    }

    public function test_template_editor_and_preview_sanitize_content_without_executing_template_expressions(): void
    {
        $this->actingAs($this->user(1));
        $body = '<p>Hello {{ system("unsafe") }}</p><script>alert(1)</script><a href="javascript:alert(1)">Unsafe</a><a href="https://example.test/update">Details</a>';
        $data = ['name' => 'Monthly update', 'subject' => 'News', 'body' => $body];
        $this->postJson('/workspace/email-notices/templates', $data)->assertOk();
        $template = MailNoticeTemplate::firstOrFail();
        $this->assertStringNotContainsString('<script', $template->body);
        $this->assertStringNotContainsString('javascript:', $template->body);
        $this->assertStringContainsString('{{ system', $template->body);
        $this->getJson('/workspace/email-notices/data/templates?key=Monthly')->assertJsonPath('total', 1);
        $this->getJson('/workspace/email-notices/templates/'.$template->id)->assertJsonPath('name', 'Monthly update');
        $this->putJson('/workspace/email-notices/templates/'.$template->id, [...$data, 'name' => 'Renamed update'])->assertOk();
        $preview = $this->postJson('/workspace/email-notices/preview', $data)->assertOk()->json('html');
        $this->assertStringContainsString('Dear iTechOrbit member,', $preview);
        $this->assertStringContainsString('Kind regards,', $preview);
        $this->assertStringContainsString('The iTechOrbit Team', $preview);
        $this->assertStringNotContainsString('Recipient name', $preview);
        $this->assertStringContainsString('{{ system', $preview);
        $this->assertStringNotContainsString('<script', $preview);
        $this->postJson('/workspace/email-notices/preview', ['subject' => 'Empty', 'body' => '<p>&nbsp;</p>'])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->postJson('/workspace/email-notices/templates', [...$data, 'subject' => "Subject\r\nBcc: another@example.test"])->assertUnprocessable();
        $this->deleteJson('/workspace/email-notices/templates/'.$template->id)->assertOk();
        $this->assertDatabaseCount('mail_notice_templates', 0);
    }

    public function test_recipient_search_and_pagination_exclude_disabled_unverified_and_invalid_email_accounts(): void
    {
        $this->actingAs($this->user(1));
        $this->user(4, ['name' => 'Visible candidate', 'email' => 'visible@example.test']);
        $this->user(4, ['name' => 'Disabled candidate', 'status' => 0]);
        $this->user(4, ['name' => 'Unverified candidate', 'email_verified_at' => null]);
        $this->user(4, ['name' => 'Invalid candidate', 'email' => 'not-an-email']);
        $this->getJson('/workspace/email-notices/recipients?key=candidate')->assertJsonPath('total', 1)->assertJsonPath('data.0.email', 'visible@example.test');
        for ($i = 0; $i < 22; $i++) {
            $this->user(4, ['name' => 'Page candidate '.$i]);
        }
        $this->getJson('/workspace/email-notices/recipients?key=Page%20candidate&page=2')->assertJsonPath('total', 22)->assertJsonCount(2, 'data');
    }

    public function test_selected_send_snapshots_content_and_addresses_queues_individually_and_is_idempotent(): void
    {
        $this->configured();
        $admin = $this->user(1);
        $first = $this->user();
        $second = $this->user();
        $other = $this->user();
        $template = MailNoticeTemplate::create(['name' => 'Saved', 'subject' => 'Original', 'body' => '<p>Original</p>']);
        $data = $this->payload(['audience' => 'selected', 'user_ids' => [$first->id, $second->id], 'template_id' => $template->id]);
        $this->actingAs($admin)->postJson('/workspace/email-notices/send', $data)->assertOk();
        $campaign = MailNoticeCampaign::firstOrFail();
        $this->assertSame(2, MailNoticeRecipient::count());
        $this->assertDatabaseMissing('mail_notice_recipients', ['user_id' => $other->id]);
        $this->assertSame(2, DB::table('jobs')->where('queue', 'mail-notices')->count());
        $this->postJson('/workspace/email-notices/send', $data)->assertOk()->assertJsonPath('campaign_id', $campaign->id);
        $this->assertDatabaseCount('mail_notice_campaigns', 1);
        $this->assertDatabaseCount('jobs', 2);
        $this->postJson('/workspace/email-notices/send', [...$data, 'subject' => 'Changed'])->assertUnprocessable();
        $email = $first->email;
        $first->update(['name' => 'New name', 'email' => 'changed@example.test']);
        $template->update(['body' => '<p>Changed template</p>']);
        $template->delete();
        $this->assertSame($data['body'], $campaign->fresh()->body);
        $this->assertNull($campaign->fresh()->template_id);
        $this->assertDatabaseHas('mail_notice_recipients', ['user_id' => $first->id, 'email' => strtolower($email)]);
        $this->getJson('/workspace/email-notices/campaigns/'.$campaign->id)->assertJsonPath('counts.queued', 2)->assertJsonPath('counts.total', 2);
    }

    public function test_all_send_includes_only_eligible_unique_addresses_and_rejects_ineligible_selected_users(): void
    {
        $this->configured();
        $admin = $this->user(1, ['email' => 'admin@example.test']);
        $this->actingAs($admin);
        $member = $this->user(4, ['email' => 'member@example.test']);
        $this->user(4, ['email' => 'MEMBER@example.test']);
        $inactive = $this->user(4, ['status' => 0]);
        $this->user(4, ['email_verified_at' => null]);
        $this->user(4, ['email' => 'invalid']);
        $this->postJson('/workspace/email-notices/send', $this->payload())->assertOk();
        $this->assertDatabaseCount('mail_notice_recipients', 2);
        $this->assertDatabaseHas('mail_notice_recipients', ['user_id' => $admin->id]);
        $this->assertDatabaseMissing('mail_notice_recipients', ['user_id' => $inactive->id]);
        $this->postJson('/workspace/email-notices/send', $this->payload(['audience' => 'selected', 'user_ids' => [$member->id, $inactive->id]]))->assertUnprocessable()->assertJsonValidationErrors('user_ids');
        $this->assertDatabaseCount('mail_notice_campaigns', 1);
    }

    public function test_sending_is_disabled_for_test_mailers_sync_queues_and_failover_to_log(): void
    {
        $this->actingAs($this->user(1));
        $this->configured();
        foreach ([['log', 'database'], ['array', 'database'], ['smtp', 'sync'], ['smtp', 'null'], ['failover', 'database']] as [$mailer, $queue]) {
            config(['mail.default' => $mailer, 'queue.default' => $queue]);
            $this->postJson('/workspace/email-notices/send', $this->payload())->assertUnprocessable();
            $this->get('/workspace/email-notices/compose')->assertInertia(fn (Assert $page) => $page->where('sendingReady', false));
        }
        $this->assertDatabaseCount('mail_notice_campaigns', 0);
        $this->postJson('/workspace/email-notices/preview', $this->payload())->assertOk();
    }

    public function test_worker_sends_one_email_per_recipient_without_cc_and_never_resends_accepted_recipients(): void
    {
        $this->configured();
        $campaign = $this->campaign();
        $recipient = $this->recipient($campaign);
        $mailer = Mockery::mock();
        $pending = Mockery::mock();
        Mail::shouldReceive('mailer')->once()->with('smtp')->andReturn($mailer);
        $mailer->shouldReceive('to')->once()->with($recipient->email, $recipient->name)->andReturn($pending);
        $pending->shouldReceive('send')->once()->with(Mockery::on(fn ($mail) => $mail instanceof SendMailNotice && $mail->cc === [] && $mail->bcc === []))->andReturn(Mockery::mock(SentMessage::class));
        $job = new SendNoticeRecipient($recipient->id, 1);
        $job->handle(app(MailNotices::class));
        $this->assertSame('sent', $recipient->fresh()->status);
        $this->assertSame(1, $recipient->fresh()->attempts);
        $this->assertNotNull($recipient->fresh()->sent_at);
        $job->handle(app(MailNotices::class));
        $this->assertSame(1, $recipient->fresh()->attempts);
        $built = (new SendMailNotice($campaign, $recipient))->build();
        $this->assertSame($campaign->subject, $built->subject);
        $this->assertSame('mail.notice', $built->view);
        $html = view('mail.notice', compact('campaign', 'recipient'))->render();
        $this->assertStringContainsString('Dear Synthetic user,', $html);
        $this->assertStringContainsString('News', $html);
        $plain = view('mail.notice-text', compact('campaign', 'recipient'))->render();
        $this->assertStringContainsString('News', $plain);
        $this->assertStringNotContainsString('<p>', $plain);
    }

    public function test_notice_greetings_and_signatures_match_in_html_and_plain_text_without_changing_snapshots(): void
    {
        $campaign = $this->campaign(['from_name' => 'Previous sender']);
        $campaign->refresh();
        $recipient = $this->recipient($campaign, ['name' => '  Kartic & Team  ']);
        $snapshot = $campaign->getAttributes();
        $html = view('mail.notice', compact('campaign', 'recipient'))->render();
        $plain = view('mail.notice-text', compact('campaign', 'recipient'))->render();
        $this->assertStringContainsString('Dear Kartic &amp; Team,', $html);
        $this->assertStringContainsString('Dear Kartic & Team,', $plain);
        foreach ([$html, $plain] as $content) {
            $this->assertStringContainsString('Kind regards,', $content);
            $this->assertStringContainsString('The iTechOrbit Team', $content);
            $this->assertStringNotContainsString('Previous sender', $content);
        }
        foreach (['', '   ', null] as $name) {
            $recipient->name = $name;
            foreach (['mail.notice', 'mail.notice-text'] as $view) {
                $this->assertStringContainsString('Dear iTechOrbit member,', view($view, compact('campaign', 'recipient'))->render());
            }
        }
        $this->assertSame($snapshot, $campaign->fresh()->getAttributes());
        $mail = (new SendMailNotice($campaign, $recipient))->build();
        $this->assertSame('Previous sender', $mail->from[0]['name']);
    }

    public function test_known_rejections_retry_with_a_limit_while_unknown_results_are_not_resent(): void
    {
        $this->configured();
        $campaign = $this->campaign();
        $recipient = $this->recipient($campaign);
        $mailer = Mockery::mock();
        $pending = Mockery::mock();
        Mail::shouldReceive('mailer')->times(3)->andReturn($mailer);
        $mailer->shouldReceive('to')->times(3)->andReturn($pending);
        $pending->shouldReceive('send')->times(3)->andThrow(new RuntimeException('private transport details', 550));
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                (new SendNoticeRecipient($recipient->id, 1))->handle(app(MailNotices::class));
                $this->fail('Expected a retryable rejection.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Mail notice rejected by transport.', $exception->getMessage());
            }
        }
        $this->assertSame('failed', $recipient->fresh()->status);
        $this->assertSame(3, $recipient->fresh()->attempts);
        $this->assertStringNotContainsString('private', $recipient->fresh()->last_error);
    }

    public function test_unknown_outcomes_timeouts_and_null_acceptance_are_unconfirmed(): void
    {
        $this->configured();
        $campaign = $this->campaign();
        $recipient = $this->recipient($campaign);
        $mailer = Mockery::mock();
        $pending = Mockery::mock();
        Mail::shouldReceive('mailer')->twice()->andReturn($mailer);
        $mailer->shouldReceive('to')->twice()->andReturn($pending);
        $pending->shouldReceive('send')->once()->andThrow(new RuntimeException('timeout with credentials'));
        $pending->shouldReceive('send')->once()->andReturnNull();
        $job = new SendNoticeRecipient($recipient->id, 1);
        $job->handle(app(MailNotices::class));
        $job->handle(app(MailNotices::class));
        $this->assertSame('unconfirmed', $recipient->fresh()->status);
        $this->assertSame(1, $recipient->fresh()->attempts);
        $noConfirmation = $this->recipient($campaign, ['email' => 'no-confirmation@example.test']);
        $unconfirmedJob = new SendNoticeRecipient($noConfirmation->id, 1);
        $unconfirmedJob->handle(app(MailNotices::class));
        $unconfirmedJob->handle(app(MailNotices::class));
        $this->assertSame('unconfirmed', $noConfirmation->fresh()->status);
        $this->assertSame(1, $noConfirmation->fresh()->attempts);
        $stale = $this->recipient($campaign, ['email' => 'stale@example.test', 'status' => 'sending', 'sending_at' => now()->subMinutes(6)]);
        app(MailNotices::class)->expireInterrupted();
        $this->assertSame('unconfirmed', $stale->fresh()->status);
        $timedOut = $this->recipient($campaign, ['email' => 'timeout@example.test', 'status' => 'sending']);
        (new SendNoticeRecipient($timedOut->id, 1))->failed(new RuntimeException('Worker stopped'));
        $this->assertSame('unconfirmed', $timedOut->fresh()->status);
    }

    public function test_retry_targets_only_failed_recipients_and_old_jobs_cannot_process_new_generation(): void
    {
        $this->configured();
        $this->actingAs($this->user(1));
        $campaign = $this->campaign();
        $failed = $this->recipient($campaign, ['status' => 'failed', 'attempts' => 3]);
        $sent = $this->recipient($campaign, ['email' => 'sent@example.test', 'status' => 'sent']);
        $uncertain = $this->recipient($campaign, ['email' => 'uncertain@example.test', 'status' => 'unconfirmed']);
        $this->postJson('/workspace/email-notices/campaigns/'.$campaign->id.'/retry')->assertOk();
        $this->assertSame(2, $failed->fresh()->generation);
        $this->assertSame('queued', $failed->fresh()->status);
        (new SendNoticeRecipient($failed->id, 1))->handle(app(MailNotices::class));
        $this->assertSame(0, $failed->fresh()->attempts);
        $this->assertSame('sent', $sent->fresh()->status);
        $this->assertSame('unconfirmed', $uncertain->fresh()->status);
        $this->postJson('/workspace/email-notices/campaigns/'.$campaign->id.'/retry')->assertOk();
        $this->assertDatabaseCount('jobs', 1);
        $this->getJson('/workspace/email-notices/campaigns/'.$campaign->id.'?status=unconfirmed')->assertJsonPath('recipients.total', 1);
    }

    public function test_dispatcher_recovers_pending_outbox_entries_and_respects_pacing(): void
    {
        $this->configured();
        $campaign = $this->campaign();
        $recipient = $this->recipient($campaign);
        $this->artisan('notices:dispatch')->assertSuccessful();
        $this->assertNotNull($recipient->fresh()->dispatched_at);
        $this->artisan('notices:dispatch')->assertSuccessful();
        $this->assertDatabaseCount('jobs', 1);
        $limiter = RateLimiter::limiter('mail-notices');
        $limit = $limiter(new SendNoticeRecipient($recipient->id, 1));
        $this->assertSame(30, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
    }

    public function test_missing_migration_renders_setup_message_and_prevents_template_mutations(): void
    {
        $this->actingAs($this->user(1));
        Schema::drop('mail_notice_recipients');
        $this->get('/workspace/email-notices/compose')->assertOk()->assertInertia(fn (Assert $page) => $page->where('installed', false)->where('sendingReady', false));
        $this->postJson('/workspace/email-notices/templates', ['name' => 'Test', 'subject' => 'Test', 'body' => '<p>Test</p>'])->assertStatus(409);
    }
}

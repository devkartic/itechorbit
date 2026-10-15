<?php

namespace Tests\Feature;

use App\Jobs\ContactMailerJob;
use App\Mail\SendContactNotifications;
use App\Mail\SendPortfolioInquiry;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class PortfolioContactTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_personal_contact_is_bound_to_the_portfolio_and_not_the_business_owner(): void
    {
        Queue::fake();
        $business = $this->user(1);
        config(['site.owner_id' => $business->id]);
        $owner = $this->user(4, ['name' => 'Portfolio Engineer', 'portfolio_id' => 'PERSONAL', 'portfolio_status' => 1]);
        $this->get('/portfolios/PERSONAL/contact')->assertOk()->assertInertia(fn (Assert $page) => $page->component('PortfolioContact')->where('recipient.name', $owner->name)->missing('recipient.email')->where('seo.noindex', true));
        $data = ['name' => 'Recruiter', 'email' => 'recruiter@example.test', 'subject' => 'Engineering role', 'message' => "Hello!\nLet us discuss a role.", 'portfolio_id' => 'SOMEONE-ELSE', 'user_id' => $business->id, 'recipient_email' => $business->email];
        $this->from('/portfolios/PERSONAL')->post('/portfolios/PERSONAL/inquiries', $data)->assertRedirect('/portfolios/PERSONAL')->assertSessionHas('success');
        $this->assertDatabaseHas('contacts', ['user_id' => $owner->id, 'subject' => $data['subject']]);
        $this->assertDatabaseMissing('contacts', ['user_id' => $business->id]);
        Queue::assertPushed(ContactMailerJob::class, fn ($job) => $job->personal && $job->user->is($owner));
        $owner->update(['portfolio_status' => 0]);
        $this->get('/portfolios/PERSONAL/contact')->assertNotFound();
        $this->post('/portfolios/PERSONAL/inquiries', $data)->assertNotFound();
        Queue::assertPushed(ContactMailerJob::class, 1);
    }

    public function test_personal_inquiry_validation_and_spam_protection_do_not_fall_back_to_business_contact(): void
    {
        Queue::fake();
        $this->user(4, ['portfolio_id' => 'PERSONAL', 'portfolio_status' => 1]);
        $this->postJson('/portfolios/PERSONAL/inquiries', ['email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
        $this->postJson('/portfolios/PERSONAL/inquiries', ['name' => 'Visitor', 'email' => 'visitor@example.test', 'subject' => 'Hello', 'message' => 'Hello', 'website' => 'spam'])->assertUnprocessable()->assertJsonValidationErrors('website');
        $this->assertDatabaseCount('contacts', 0);
        Queue::assertNothingPushed();
    }

    public function test_business_notification_has_the_new_html_and_plain_text_body(): void
    {
        Mail::fake();
        $owner = $this->user(1);
        $contact = Contact::create(['user_id' => $owner->id, 'name' => 'Client', 'email' => 'client@example.test', 'subject' => 'Application development', 'message' => "Requirements & context\nSecond line"]);
        (new ContactMailerJob($owner, $contact))->handle();
        Mail::assertSent(SendContactNotifications::class, function ($mail) use ($owner) {
            $mail->build();

            return $mail->hasTo($owner->email) && $mail->hasReplyTo('client@example.test');
        });
        $html = (new SendContactNotifications($owner, $contact))->render();
        $this->assertStringContainsString('iTechOrbit', $html);
        $this->assertStringContainsString('Requirements &amp; context', $html);
        $this->assertStringContainsString('Second line', $html);
        $plain = view('mail.business-inquiry-text', ['owner' => $owner, 'inquiry' => $contact])->render();
        $this->assertStringContainsString($contact->message, $plain);
        $this->assertStringNotContainsString('personal portfolio', $plain);
    }

    public function test_notification_goes_only_to_owner_with_full_escaped_body_and_visitor_reply_to(): void
    {
        Mail::fake();
        $owner = $this->user(4, ['name' => 'Portfolio Engineer']);
        $contact = Contact::create(['user_id' => $owner->id, 'name' => 'Recruiter', 'email' => 'recruiter@example.test', 'subject' => 'Engineering role', 'message' => "First line & context\n<script>alert(1)</script>"]);
        (new ContactMailerJob($owner, $contact, true))->handle();
        Mail::assertSent(SendPortfolioInquiry::class, function ($mail) use ($owner) {
            $mail->build();

            return $mail->hasTo($owner->email) && $mail->hasReplyTo('recruiter@example.test') && count($mail->to) === 1 && $mail->cc === [] && $mail->bcc === [];
        });
        $mail = new SendPortfolioInquiry($owner, $contact);
        $html = $mail->render();
        $this->assertStringContainsString('First line &amp; context', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('iTechOrbit', $html);
        $this->assertStringContainsString('Reply to Recruiter', $html);
        $plain = view('mail.portfolio-inquiry-text', ['owner' => $owner, 'inquiry' => $contact])->render();
        $this->assertStringContainsString($contact->message, $plain);
        $this->assertSame(1, (int) $contact->fresh()->sent_mail_status);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin\ContactInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebsiteContactContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_intro_uses_the_active_record_and_preserves_plain_text_values(): void
    {
        ContactInfo::create(['title' => 'Hidden title', 'subtitle' => 'Hidden subtitle', 'descriptions' => '<p>Hidden details.</p>', 'status' => false]);
        $record = ContactInfo::create(['title' => '<script>Example title</script>', 'subtitle' => 'Your ideas & opportunities', 'descriptions' => '<p>Existing details.</p>', 'status' => true]);
        $this->get('/contact/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('contactTitle', $record->title)->where('contactSubtitle', $record->subtitle)
            ->where('existingContent', '<p>Existing details.</p>'));
        $record->update(['title' => 'Updated title', 'subtitle' => 'Updated subtitle']);
        $this->get('/contact/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('contactTitle', 'Updated title')->where('contactSubtitle', 'Updated subtitle'));
    }

    public function test_contact_intro_falls_back_when_there_is_no_active_record(): void
    {
        ContactInfo::create(['title' => 'Hidden title', 'subtitle' => 'Hidden subtitle', 'descriptions' => '<p>Hidden details.</p>', 'status' => false]);
        $this->get('/contact/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('contactTitle', 'What are you working on?')
            ->where('contactSubtitle', 'Share your idea, challenge, or next opportunity.'));
    }
}

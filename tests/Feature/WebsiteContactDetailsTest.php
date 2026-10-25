<?php

namespace Tests\Feature;

use App\Models\Admin\ContactInfo;
use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class WebsiteContactDetailsTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function payload(): array
    {
        return ['title' => 'Contact us', 'subtitle' => 'Share your idea', 'descriptions' => '<p>Introduction.</p>',
            'website_url' => 'https://example.test', 'email' => 'hello@example.test', 'phone' => '+880 (2) 123-456',
            'mobile' => '+880 1712 345678', 'address' => "Office 12\nDhaka, Bangladesh", 'is_checked' => 'true'];
    }

    public function test_existing_contact_crud_manages_content_and_details_together(): void
    {
        $this->actingAs($this->user(1));
        $this->get('/contact-info/create')->assertOk();
        $this->postJson('/contact-info', $this->payload())->assertOk();
        $record = ContactInfo::firstOrFail();
        $this->assertFalse(Schema::hasTable('website_contact_details'));
        $this->get('/contact-info')->assertOk();
        $this->get('/contact-info/'.$record->id)->assertOk();
        $this->get('/contact-info/'.$record->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('record.email', 'hello@example.test')->where('record.title', 'Contact us')->missing('record.legacy_contact_details_backup'));
        $this->get('/contact/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('contactDetails', 5)->where('contactTitle', 'Contact us')->where('existingContent', '<p>Introduction.</p>')
            ->where('contactDetails.0.href', 'https://example.test')->where('contactDetails.1.href', 'mailto:hello@example.test')
            ->where('contactDetails.2.href', 'tel:+8802123456')->where('contactDetails.3.href', 'tel:+8801712345678')
            ->where('contactDetails.4.value', "Office 12\nDhaka, Bangladesh")->where('contactDetails.4.href', null));
        $this->putJson('/contact-info/'.$record->id, [...$this->payload(), 'title' => 'Updated contact', 'email' => 'updated@example.test'])->assertOk();
        $this->get('/contact/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('contactTitle', 'Updated contact')->where('contactDetails.1.value', 'updated@example.test'));
        $this->putJson('/contact-info/'.$record->id, [...$this->payload(), 'is_checked' => false])->assertOk();
        $this->get('/contact/info')->assertOk()->assertInertia(fn (Assert $page) => $page->where('contactDetails', []));
        $this->deleteJson('/contact-info/'.$record->id)->assertOk();
        $this->assertDatabaseMissing('website_contact', ['id' => $record->id]);
    }

    public function test_invalid_values_are_rejected_and_optional_fields_can_be_cleared(): void
    {
        $this->actingAs($this->user(1));
        $this->postJson('/contact-info', [...$this->payload(), 'website_url' => 'javascript:alert(1)', 'email' => 'bad-email',
            'phone' => 'not a number', 'mobile' => '++123', 'is_checked' => 'invalid'])->assertUnprocessable()
            ->assertJsonValidationErrors(['website_url', 'email', 'phone', 'mobile', 'is_checked']);
        $this->postJson('/contact-info', [...$this->payload(), 'descriptions' => null])->assertOk();
        $record = ContactInfo::firstOrFail();
        // Older clients that omit the new fields must not clear existing values.
        $this->putJson('/contact-info/'.$record->id, ['title' => 'Legacy update', 'subtitle' => 'Subtitle', 'is_checked' => true])->assertOk();
        $this->assertSame('hello@example.test', $record->fresh()->email);
        $this->putJson('/contact-info/'.$record->id, [...$this->payload(), 'website_url' => null, 'phone' => null, 'mobile' => null, 'address' => null])->assertOk();
        $this->get('/contact/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('contactDetails', 1)->where('contactDetails.0.label', 'Email'));
        $record->update(['website_url' => 'javascript:alert(1)', 'phone' => '123?body=unsafe']);
        $this->assertNull($record->publicDetails()[0]['href']);
        $this->assertNull($record->publicDetails()[2]['href']);
    }

    public function test_contact_details_come_from_the_same_active_record_as_the_heading(): void
    {
        ContactInfo::create(['title' => 'Hidden', 'subtitle' => 'Hidden', 'descriptions' => '', 'email' => 'hidden@example.test', 'status' => false]);
        ContactInfo::create(['title' => 'Active', 'subtitle' => 'Active', 'descriptions' => '', 'email' => 'active@example.test', 'status' => true]);
        ContactInfo::create(['title' => 'Later', 'subtitle' => 'Later', 'descriptions' => '', 'email' => 'later@example.test', 'status' => true]);
        $this->get('/contact/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('contactTitle', 'Active')->has('contactDetails', 1)->where('contactDetails.0.value', 'active@example.test'));
    }

    public function test_existing_permissions_control_read_and_edit(): void
    {
        $this->get('/contact-info')->assertRedirect('/login');
        $this->postJson('/contact-info', $this->payload())->assertUnauthorized();
        $this->permissionResources();
        $member = $this->user();
        $this->actingAs($member);
        $record = ContactInfo::create(['title' => 'Contact', 'subtitle' => 'Intro', 'descriptions' => '', 'status' => true]);
        $this->get('/contact-info')->assertForbidden();
        $this->putJson('/contact-info/'.$record->id, $this->payload())->assertForbidden();
        $link = Link::where('url', 'contact-info')->firstOrFail();
        Permission::create(['role_id' => $member->role_id, 'link_id' => $link->id, 'route_id' => Route::where('name', 'index')->value('id')]);
        $this->get('/contact-info')->assertOk();
        $this->get('/contact-info/'.$record->id.'/edit')->assertForbidden();
        Permission::create(['role_id' => $member->role_id, 'link_id' => $link->id, 'route_id' => Route::where('name', 'edit')->value('id')]);
        $this->putJson('/contact-info/'.$record->id, $this->payload())->assertOk();
    }
}

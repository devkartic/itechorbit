<?php

namespace Tests\Feature;

use App\Models\Admin\Basic;
use App\Models\Admin\Category;
use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Post;
use App\Models\Admin\PrivacyPolicy;
use App\Models\Admin\Project;
use App\Models\Admin\Route;
use App\Models\Admin\Skill;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class AdminWorkspaceTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_admin_module_pages_and_forms_use_react_without_exposing_passwords(): void
    {
        $admin = $this->user(1);
        $this->permissionResources();
        $this->actingAs($admin);
        $this->get('/home')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));
        foreach (array_keys(config('admin')) as $resource) {
            $this->get('/'.$resource)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Resource')->where('resource', $resource));
            if (! in_array($resource, ['contacts', 'users'])) {
                $this->get('/'.$resource.'/create')->assertOk();
            }
        }
        $this->get('/users/'.$admin->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page->missing('record.password')->missing('record.remember_token'));
        $this->get('/permissions')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Permissions'));
        $this->get('/posts')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_article_crud_preserves_ids_and_existing_validation(): void
    {
        $admin = $this->user(1);
        $this->actingAs($admin);
        $category = Category::create(['name' => 'Test', 'title' => 'Testing', 'status' => 1]);
        $data = ['title' => 'API design', 'category_id' => $category->id, 'body' => '<p>API contract</p>', 'is_checked' => 'true', 'is_private' => 'false'];
        $this->postJson('/posts', $data)->assertOk();
        $post = Post::firstOrFail();
        $this->assertSame($admin->id, $post->user_id);
        $this->get('/posts/'.$post->id)->assertOk();
        $this->get('/posts/'.$post->id.'/edit')->assertOk();
        $this->putJson('/posts/'.$post->id, [...$data, 'title' => 'API design revised'])->assertOk();
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'API design revised']);
        $this->postJson('/posts', [...$data, 'category_id' => 999])->assertUnprocessable();
        $this->deleteJson('/categories/'.$category->id)->assertUnprocessable();
        $this->deleteJson('/posts/'.$post->id)->assertOk();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_large_privacy_policies_save_and_oversized_input_has_a_field_error(): void
    {
        $this->actingAs($this->user(1));
        $paragraph = '<p>At iTechOrbit.com, one of our main priorities is the privacy of our visitors. If you have additional questions, do not hesitate to contact us.</p>';
        $html = '<h2>Privacy Policy</h2>'.str_repeat($paragraph, 600)."<h2>Children's Information</h2><p>We protect children's privacy.</p>";
        $this->assertGreaterThan(65535, strlen($html));
        $data = ['title' => 'Privacy policy', 'subtitle' => 'How we handle personal information', 'descriptions' => $html, 'is_checked' => 'true'];
        $this->postJson('/privacy-policies', $data)->assertOk();
        $policy = PrivacyPolicy::firstOrFail();
        $this->assertSame($html, $policy->descriptions);
        $this->get('/privacy-n-policy')->assertOk()->assertInertia(fn (Assert $page) => $page->where('content', fn ($content) => html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $html));

        $updated = $html.'<h2>Consent</h2><p>By using our website, you consent to our Privacy Policy.</p>';
        $this->putJson('/privacy-policies/'.$policy->id, [...$data, 'descriptions' => $updated])->assertOk();
        $this->assertSame($updated, $policy->fresh()->descriptions);
        $this->putJson('/privacy-policies/'.$policy->id, [...$data, 'descriptions' => str_repeat('x', 1000001)])->assertUnprocessable()->assertJsonValidationErrors('descriptions');
        $this->assertSame($updated, $policy->fresh()->descriptions);
        $this->postJson('/privacy-policies', [...$data, 'descriptions' => ''])->assertUnprocessable()->assertJsonValidationErrors('descriptions');
    }

    public function test_cv_upload_accepts_pdf_and_optional_uploads_preserve_the_existing_file(): void
    {
        $this->actingAs($this->user(1));
        $data = ['first_name' => 'Test', 'last_name' => 'Engineer', 'designation' => 'Engineer', 'mobile' => '0123456789',
            'career_objective' => 'Build software.', 'self_introduction' => 'A test profile.', 'service_offering_title' => 'Engineering',
            'additional_information' => 'Test details.', 'address' => 'Test address', 'birth_date' => '1990-01-01',
            'experience_year' => 5, 'gender' => 'Other', 'marital_status' => 'Single', 'is_checked' => 'true', 'cv_public_status' => 'false'];
        $this->postJson('/basics', [...$data, 'uploaded_cv' => null])->assertOk();
        $basic = Basic::firstOrFail();
        $this->assertNull($basic->cv_path);
        $pdf = UploadedFile::fake()->createWithContent('Kartic-Resume-SP.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF");
        $upload = new UploadedFile($pdf->getRealPath(), 'Kartic-Resume-SP.pdf', 'application/octet-stream', null, true);
        $this->put('/basics/'.$basic->id, [...$data, 'uploaded_cv' => $upload], ['Accept' => 'application/json'])->assertOk();
        $path = $basic->fresh()->cv_path;
        $this->assertNotNull($path);
        $this->assertStringEndsWith('.pdf', $path);
        $this->assertFileExists(Storage::disk('public')->path($path));
        try {
            $this->putJson('/basics/'.$basic->id, [...$data, 'uploaded_cv' => null])->assertOk();
            $this->assertSame($path, $basic->fresh()->cv_path);
            $this->putJson('/basics/'.$basic->id, $data)->assertOk();
            $this->assertSame($path, $basic->fresh()->cv_path);
            $fake = UploadedFile::fake()->createWithContent('renamed.pdf', 'This is plain text, not a PDF document.');
            $fakeUpload = new UploadedFile($fake->getRealPath(), 'renamed.pdf', 'application/pdf', null, true);
            $this->put('/basics/'.$basic->id, [...$data, 'uploaded_cv' => $fakeUpload], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('uploaded_cv');
            $this->assertSame($path, $basic->fresh()->cv_path);
            $this->put('/basics/'.$basic->id, [...$data, 'uploaded_cv' => UploadedFile::fake()->create('large.pdf', 5001, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('uploaded_cv');
        } finally {
            @unlink(Storage::disk('public')->path($path));
        }
    }

    public function test_permissions_and_ownership_apply_to_existing_actions(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $this->permissionResources();
        $post = $this->article([], $other);
        $link = Link::where('url', 'posts')->firstOrFail();
        foreach (['index', 'edit'] as $action) {
            Permission::create(['role_id' => 4, 'link_id' => $link->id, 'route_id' => Route::where('name', $action)->firstOrFail()->id]);
        }
        $this->actingAs($owner)->get('/posts')->assertOk()->assertInertia(fn (Assert $page) => $page->has('records.data', 0));
        $this->get('/posts/'.$post->id.'/edit')->assertForbidden();
        $this->putJson('/posts/'.$post->id, ['title' => 'Hijacked'])->assertForbidden();
        $this->get('/users')->assertForbidden();
        $owner->update(['status' => 0]);
        $this->get('/posts')->assertForbidden();
    }

    public function test_project_upload_rules_and_media_field_allowlist(): void
    {
        $admin = $this->user(1);
        $this->actingAs($admin);
        $skill = Skill::create(['user_id' => $admin->id, 'name' => 'Laravel', 'status' => 1]);
        $data = ['name' => 'Upload test', 'client_name' => 'Test', 'skill_id' => $skill->id, 'about_project' => 'Test project', 'is_checked' => 'true'];
        $this->post('/projects', [...$data, 'uploaded_image' => UploadedFile::fake()->create('attack.php', 1, 'application/x-httpd-php')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->postJson('/projects', [...$data, 'project_url' => 'javascript:alert(1)'])->assertUnprocessable();
        $this->postJson('/projects', $data)->assertOk();
        $project = Project::firstOrFail();
        $this->postJson('/project-file-delete', ['id' => $project->id, 'field_name' => 'user_id'])->assertUnprocessable();
        $this->postJson('/project-file-delete', ['id' => $project->id, 'field_name' => 'image_path'])->assertOk();
        $this->assertSame($admin->id, $project->fresh()->user_id);
    }

    public function test_inbox_is_owned_and_read_status_updates(): void
    {
        $admin = $this->user(1);
        $this->actingAs($admin);
        $contact = Contact::create(['user_id' => $admin->id, 'name' => 'Client', 'email' => 'client@example.test', 'subject' => 'Inquiry', 'message' => 'Project context']);
        $this->get('/contacts/'.$contact->id)->assertOk();
        $this->assertSame(1, $contact->fresh()->read_status);
        $this->deleteJson('/contacts/'.$contact->id)->assertOk();
    }

    public function test_permission_changes_are_validated_and_idempotent(): void
    {
        $admin = $this->user(1);
        $this->user(4);
        $this->permissionResources();
        $this->actingAs($admin);
        $link = Link::where('url', 'posts')->firstOrFail();
        $payload = ['role_id' => 4, 'link_id' => $link->id, 'route_name' => 'index', 'is_checked' => true];
        $this->postJson('/cr-permission', $payload)->assertOk();
        $this->postJson('/cr-permission', $payload)->assertOk();
        $this->assertSame(1, Permission::count());
        $this->postJson('/cr-permission', [...$payload, 'role_id' => 1])->assertForbidden();
        $this->postJson('/cr-permission', [...$payload, 'is_checked' => false])->assertOk();
        $this->assertSame(0, Permission::count());
    }
}

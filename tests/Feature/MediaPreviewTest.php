<?php

namespace Tests\Feature;

use App\Models\Admin\Basic;
use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Route;
use App\Models\Admin\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class MediaPreviewTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function basicData(): array
    {
        return ['first_name' => 'Test', 'last_name' => 'Engineer', 'designation' => 'Engineer', 'mobile' => '0123456789',
            'career_objective' => 'Build software.', 'self_introduction' => 'Test profile.', 'service_offering_title' => 'Engineering',
            'additional_information' => 'Test details.', 'address' => 'Test address', 'birth_date' => '1990-01-01',
            'experience_year' => 5, 'gender' => 'Other', 'marital_status' => 'Single', 'is_checked' => 'true', 'cv_public_status' => 'false'];
    }

    public function test_all_image_fields_validate_content_and_size_and_supply_saved_previews(): void
    {
        $admin = $this->user(1);
        $this->actingAs($admin);
        $post = $this->article([], $admin);
        $skill = Skill::create(['name' => 'PHP', 'user_id' => $admin->id, 'status' => 1]);
        $project = Project::create(['user_id' => $admin->id, 'skill_id' => $skill->id, 'name' => 'Upload QA', 'client_name' => 'Synthetic', 'about_project' => 'Overview', 'status' => 1]);
        $this->postJson('/basics', $this->basicData())->assertOk();
        $basic = Basic::firstOrFail();
        $cases = [
            ['posts', $post, ['title' => $post->title, 'category_id' => $post->category_id, 'body' => $post->body], 'uploaded_image', 'image_path'],
            ['projects', $project, ['name' => $project->name, 'client_name' => $project->client_name, 'skill_id' => $skill->id, 'about_project' => 'Overview'], 'uploaded_image', 'image_path'],
            ['basics', $basic, $this->basicData(), 'uploaded_banner_image', 'banner_image_path'],
            ['basics', $basic, $this->basicData(), 'uploaded_profile_image', 'profile_image_path'],
        ];
        $paths = [];
        try {
            foreach ($cases as [$resource, $record, $data, $upload, $storage]) {
                $url = '/'.$resource.'/'.$record->id;
                $this->put($url, [...$data, $upload => UploadedFile::fake()->create('renamed.png', 1, 'text/plain')], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors($upload);
                $this->put($url, [...$data, $upload => UploadedFile::fake()->image('oversized.png')->size(2049)], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors($upload);
                $this->put($url, [...$data, $upload => UploadedFile::fake()->image('valid.png')->size(1500)], ['Accept' => 'application/json'])->assertOk();
                $paths[] = public_path($record->fresh()->{$storage});
                $preview = '/workspace/media/'.$resource.'/'.$record->id.'/'.$storage;
                $this->get($url.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page->where('attachments.'.$storage.'.type', 'image')->where('attachments.'.$storage.'.url', $preview));
                $this->get($preview)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('Cache-Control', 'no-store, private');
            }
        } finally {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
    }

    public function test_cv_preview_requires_permission_and_ownership_without_changing_public_visibility(): void
    {
        $owner = $this->user(1);
        $this->actingAs($owner);
        $this->postJson('/basics', $this->basicData())->assertOk();
        $basic = Basic::firstOrFail();
        $this->put('/basics/'.$basic->id, [...$this->basicData(), 'uploaded_cv' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF")], ['Accept' => 'application/json'])->assertOk();
        $path = public_path($basic->fresh()->cv_path);
        $preview = '/workspace/media/basics/'.$basic->id.'/cv_path';
        try {
            $this->get('/basics/'.$basic->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page->where('attachments.cv_path.type', 'pdf')->where('attachments.cv_path.url', $preview));
            $this->get($preview)->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $this->assertSame(0, (int) $basic->fresh()->cv_public_status);
            $this->get('/workspace/media/basics/'.$basic->id.'/password')->assertNotFound();
            auth()->logout();
            $this->get($preview)->assertRedirect('/login');
            $other = $this->user();
            $this->permissionResources();
            Permission::create(['role_id' => 4, 'link_id' => Link::where('url', 'basics')->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
            $this->actingAs($other)->get($preview)->assertForbidden();
            $this->actingAs($owner);
            $basic->update(['cv_path' => '../composer.json']);
            $this->get($preview)->assertNotFound();
        } finally {
            @unlink($path);
        }
    }
}

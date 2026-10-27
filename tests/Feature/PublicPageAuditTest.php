<?php

namespace Tests\Feature;

use App\Models\Admin\AboutInfo;
use App\Models\Admin\Basic;
use App\Models\Admin\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class PublicPageAuditTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_database_copy_and_seo_edits_reach_public_pages_and_editor_permissions_are_enforced(): void
    {
        $record = WebsitePage::where('page_key', 'services')->firstOrFail();
        $this->get('/public-pages/'.$record->id.'/edit')->assertRedirect('/login');
        $this->permissionResources();
        $member = $this->user();
        $values = ['heading' => 'Reviewed engineering services', 'introduction' => 'Useful applications for your business.', 'meta_title' => 'Reviewed services', 'meta_description' => 'Focused engineering.', 'cta_label' => 'Start here', 'cta_url' => '/contact/info', 'share_image' => 'https://example.test/preview.png', 'is_checked' => true];
        $this->actingAs($member)->putJson('/public-pages/'.$record->id, $values)->assertForbidden();
        $admin = $this->user(1);
        $this->actingAs($admin)->putJson('/public-pages/'.$record->id, $values)->assertOk();
        $this->get('/service/info')->assertInertia(fn (Assert $page) => $page->where('publicPage.heading', $values['heading'])->where('seo.title', 'Reviewed services | iTechOrbit')->where('seo.image', $values['share_image']));
        $this->putJson('/public-pages/'.$record->id, [...$values, 'cta_url' => '//attacker.test'])->assertUnprocessable();
        $this->putJson('/public-pages/'.$record->id, [...$values, 'share_image' => 'javascript:alert(1)'])->assertUnprocessable();
        $this->get('/public-pages/create')->assertNotFound();
    }

    public function test_category_metadata_is_distinct_and_unpublished_categories_are_hidden(): void
    {
        $post = $this->article();
        $this->get('/blogs/category/'.$post->category_id)->assertInertia(fn (Assert $page) => $page->where('seo.title', 'Laravel articles | iTechOrbit')->where('publicPage.heading', 'Laravel engineering'));
        $post->category->update(['status' => 0]);
        $this->get('/blogs/category/'.$post->category_id)->assertNotFound();
        $this->get('/blogs')->assertInertia(fn (Assert $page) => $page->has('categories', 0));
        $this->get('/sitemaps/pages.xml')->assertDontSee('/blogs/category/'.$post->category_id);
    }

    public function test_pagination_filters_descriptions_and_social_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/posts/article.jpg', 'test-image');
        $post = $this->article(['image_path' => 'uploads/posts/article.jpg']);
        $this->get('/blogs/'.$post->id)->assertInertia(fn (Assert $page) => $page->where('seo.image', config('app.url').'/storage/uploads/posts/article.jpg'));
        $this->get('/work?category=Examples')->assertInertia(fn (Assert $page) => $page->where('seo.noindex', true)->where('seo.canonical', config('app.url').'/work'));
        $this->get('/blogs/search')->assertInertia(fn (Assert $page) => $page->where('seo.noindex', true));
        for ($i = 0; $i < 12; $i++) {
            $this->article(['title' => 'Article '.$i], $post->user);
        }
        $this->get('/blogs?page=2')->assertInertia(fn (Assert $page) => $page->where('seo.title', 'Software Engineering Articles & Tutorials — Page 2 | iTechOrbit')->where('seo.canonical', config('app.url').'/blogs?page=2'));
    }

    public function test_corrections_preview_then_back_up_apply_and_preserve_later_editorial_changes(): void
    {
        $record = AboutInfo::create(['title' => 'About Us', 'subtitle' => 'Thanks for being here...', 'descriptions' => '<p>Already reviewed by an editor.</p>', 'status' => 1]);
        $originalStorage = storage_path();
        $temporary = sys_get_temp_dir().'/itechorbit-content-test-'.uniqid();
        app()->useStoragePath($temporary);
        try {
            $this->artisan('website:correct-public-content', ['--dry-run' => true])->assertSuccessful();
            $this->assertSame('About Us', $record->fresh()->title);
            $this->assertDirectoryDoesNotExist($temporary.'/app/private/public-content-backups');
            $this->artisan('website:correct-public-content', ['--apply' => true])->assertSuccessful();
            $this->assertSame('About iTechOrbit', $record->fresh()->title);
            $this->assertSame('<p>Already reviewed by an editor.</p>', $record->fresh()->descriptions);
            $files = glob($temporary.'/app/private/public-content-backups/*.json');
            $this->assertCount(1, $files);
            $this->assertSame('About Us', json_decode(file_get_contents($files[0]), true)['website_about'][$record->id]['title']);
            $record->update(['title' => 'Editor’s title']);
            $this->artisan('website:correct-public-content', ['--apply' => true])->assertSuccessful();
            $this->assertSame('Editor’s title', $record->fresh()->title);
            $this->assertCount(1, glob($temporary.'/app/private/public-content-backups/*.json'));
        } finally {
            app()->useStoragePath($originalStorage);
            File::deleteDirectory($temporary);
        }
    }

    public function test_portfolio_alias_and_metadata_use_the_actual_profession(): void
    {
        $user = $this->user(4, ['name' => 'Example Professional', 'portfolio_status' => 1, 'portfolio_id' => 'PUBLIC-PROFILE']);
        config(['site.owner_id' => $user->id]);
        Basic::create(['user_id' => $user->id, 'first_name' => 'Example', 'last_name' => 'Professional', 'designation' => 'Account executive', 'mobile' => '', 'address' => '', 'service_offering_title' => '', 'career_objective' => '', 'experience_year' => 0, 'birth_date' => '1990-01-01', 'gender' => '', 'marital_status' => '', 'status' => 1, 'self_introduction' => '<p>Customer relationships and account planning.</p>']);
        $this->get('/portfolios')->assertInertia(fn (Assert $page) => $page->where('seo.canonical', config('app.url').'/portfolios/PUBLIC-PROFILE')->where('seo.title', 'Example Professional — Account executive | iTechOrbit')->where('seo.description', 'Customer relationships and account planning.'));
        $this->get('/sitemaps/pages.xml')->assertDontSee('<loc>'.config('app.url').'/portfolios</loc>', false);
    }

    public function test_article_views_do_not_falsely_refresh_content_modification_dates(): void
    {
        $post = $this->article();
        DB::table('posts')->where('id', $post->id)->update(['updated_at' => '2026-01-01 00:00:00']);
        $this->get('/blogs/'.$post->id)->assertOk();
        $this->assertSame('2026-01-01 00:00:00', $post->fresh()->updated_at->format('Y-m-d H:i:s'));
    }
}

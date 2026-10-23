<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Route;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteTechnology;
use App\Services\Shared\MediaFiles;
use App\Support\Database\WebsiteCatalogUpgrade;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class WebsiteServicesTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Application launch package', 'slug' => 'application-launch',
            'subtitle' => 'A defined launch package.', 'descriptions' => '<p>Implementation and handover.</p>',
            'deliverables' => "Application setup\nDeployment guide", 'technology_ids' => WebsiteTechnology::whereIn('slug', ['laravel', 'react'])->pluck('id')->all(),
            'approach' => "Discovery\nImplementation",
            'display_order' => 0, 'is_checked' => 'true',
        ], $overrides);
    }

    public function test_services_support_admin_crud_and_public_database_content(): void
    {
        $this->actingAs($this->user(1));
        $this->postJson('/service-info', $this->payload())->assertOk();
        $product = ServiceInfo::where('slug', 'application-launch')->firstOrFail();
        $this->assertSame(['Application setup', 'Deployment guide'], $product->deliverables);
        $this->get('/service-info/'.$product->id)->assertOk();
        $this->get('/service-info/'.$product->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('record.deliverables', "Application setup\nDeployment guide")->missing('options.service_type'));
        $this->get('/services/application-launch')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->missing('service.serviceType')->missing('service.price'));
        $this->get('/service/info')->assertOk()->assertInertia(fn (Assert $page) => $page->where('services.0.slug', 'application-launch')->missing('services.0.description')->missing('services.0.approach'));
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('services.0.slug', 'application-launch')->missing('services.0.description')->missing('services.0.approach'));
        $this->get('/sitemaps/pages.xml')->assertSee('/services/application-launch', false);
        $this->get('/robots.txt')->assertSee('Allow: /services/application-launch', false);

        $this->putJson('/service-info/'.$product->id, $this->payload([
            'slug' => 'client-workflow', 'descriptions' => '<p>Client workflow</p><script>alert(1)</script>',
        ]))->assertOk();
        $this->get('/services/application-launch')->assertNotFound();
        $this->get('/services/client-workflow')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('service.description', '<p>Client workflow</p>'));
        $this->putJson('/service-info/'.$product->id, $this->payload(['slug' => 'client-workflow', 'is_checked' => false]))->assertOk();
        $this->get('/services/client-workflow')->assertNotFound();
        $this->get('/sitemaps/pages.xml')->assertDontSee('/services/client-workflow', false);
        $this->get('/robots.txt')->assertDontSee('Allow: /services/client-workflow', false);
        $this->deleteJson('/service-info/'.$product->id)->assertOk();
        $this->assertDatabaseMissing('website_services', ['id' => $product->id]);
    }

    public function test_validation_rejects_unsafe_slugs_invalid_types_and_package_data(): void
    {
        $this->actingAs($this->user(1));
        foreach (['create', '123', '../bad', 'Two Words'] as $slug) {
            $this->postJson('/service-info', $this->payload(['slug' => $slug]))->assertUnprocessable()->assertJsonValidationErrors('slug');
        }
        $this->postJson('/service-info', $this->payload(['service_type' => 'invalid', 'display_order' => -1, 'is_checked' => 'oops']))
            ->assertUnprocessable()->assertJsonValidationErrors(['service_type', 'display_order', 'is_checked']);
        $this->postJson('/service-info', $this->payload(['price' => -1, 'currency' => 'usd', 'use_cases' => 'Missing description']))
            ->assertUnprocessable()->assertJsonValidationErrors(['price', 'currency', 'use_cases.0.description']);
        $this->postJson('/service-info', $this->payload(['slug' => 'laravel-development']))->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_existing_permissions_protect_service_writes_and_reads(): void
    {
        $this->get('/service-info')->assertRedirect('/login');
        $this->postJson('/service-info', $this->payload())->assertUnauthorized();
        $this->permissionResources();
        $member = $this->user();
        $this->actingAs($member)->get('/service-info')->assertForbidden();
        $this->postJson('/service-info', $this->payload())->assertForbidden();
        $this->putJson('/service-info/'.ServiceInfo::firstOrFail()->id, $this->payload())->assertForbidden();
        $this->deleteJson('/service-info/'.ServiceInfo::firstOrFail()->id)->assertForbidden();
        Permission::create(['role_id' => $member->role_id, 'link_id' => Link::where('url', 'service-info')->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
        $this->get('/service-info')->assertOk();
        $this->postJson('/service-info', $this->payload())->assertForbidden();
    }

    public function test_service_images_are_validated_preserved_replaced_and_removed(): void
    {
        $this->actingAs($this->user(1));
        $image = fn () => UploadedFile::fake()->createWithContent('cover.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3ioAAAAASUVORK5CYII='));
        $this->post('/service-info', [...$this->payload(), 'uploaded_image' => $image()], ['Accept' => 'application/json'])->assertOk();
        $service = ServiceInfo::where('slug', 'application-launch')->firstOrFail();
        $first = $service->image_path;
        try {
            $this->assertFileExists(public_path($first));
            $this->get('/services/application-launch')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('service.imageWidth', 1)->where('service.imageHeight', 1));
            $this->get('/workspace/media/service-info/'.$service->id.'/image_path')->assertOk();
            $this->putJson('/service-info/'.$service->id, $this->payload())->assertOk();
            $this->assertSame($first, $service->fresh()->image_path);
            $invalid = UploadedFile::fake()->createWithContent('fake.png', 'invalid');
            $this->put('/service-info/'.$service->id, [...$this->payload(), 'uploaded_image' => new UploadedFile($invalid->getRealPath(), 'fake.png', 'image/png', null, true)], ['Accept' => 'application/json'])
                ->assertUnprocessable()->assertJsonValidationErrors('uploaded_image');
            $this->put('/service-info/'.$service->id, [...$this->payload(), 'uploaded_image' => $image()], ['Accept' => 'application/json'])->assertOk();
            $second = $service->fresh()->image_path;
            $this->assertNotSame($first, $second);
            $this->assertFileDoesNotExist(public_path($first));
            $this->putJson('/service-info/'.$service->id, $this->payload(['remove_image' => true]))->assertOk();
            $this->assertNull($service->fresh()->image_path);
            $this->assertFileDoesNotExist(public_path($second));
            $this->put('/service-info/'.$service->id, [...$this->payload(), 'uploaded_image' => $image()], ['Accept' => 'application/json'])->assertOk();
            $third = $service->fresh()->image_path;
            $this->deleteJson('/service-info/'.$service->id)->assertOk();
            $this->assertFileDoesNotExist(public_path($third));
        } finally {
            foreach (array_filter([$first, $second ?? null, $third ?? null, $service->fresh()?->image_path]) as $path) {
                MediaFiles::delete($path);
            }
        }
    }

    public function test_unpublishing_services_removes_static_fallbacks_and_featured_links(): void
    {
        $ai = ServiceInfo::where('slug', 'ai-workflow-integration')->firstOrFail();
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('site.aiService.slug', $ai->slug));
        ServiceInfo::query()->update(['status' => false]);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('services', 0)->where('site.aiService', null));
        $this->get('/service/info')->assertOk()->assertInertia(fn (Assert $page) => $page->has('services', 0));
        $this->get('/services/ai-workflow-integration')->assertNotFound();
    }

    public function test_migration_preserves_legacy_records_and_imports_static_services(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.service_migration_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('service_migration_test');
        try {
            Schema::create('service_infos', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subtitle');
                $table->text('descriptions');
                $table->tinyInteger('status')->default(0);
                $table->timestamps();
            });
            DB::table('service_infos')->insert(['id' => 42, 'title' => 'Existing service', 'subtitle' => 'Existing summary', 'descriptions' => '<h2>Existing HTML</h2>', 'status' => 1]);
            $migration = new WebsiteCatalogUpgrade;
            $migration->expandServices();
            $this->assertFalse(Schema::hasTable('service_infos'));
            $this->assertSame('<h2>Existing HTML</h2>', DB::table('website_services')->where('id', 42)->value('descriptions'));
            $this->assertSame('custom', DB::table('website_services')->where('id', 42)->value('service_type'));
            $this->assertSame(10, DB::table('website_services')->count());
            $this->assertSame('ai-workflow-integration', DB::table('website_services')->where('highlight', 1)->value('slug'));

        } finally {
            DB::setDefaultConnection($original);
            DB::purge('service_migration_test');
        }
    }
}

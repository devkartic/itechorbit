<?php

namespace Tests\Feature;

use App\Models\Admin\ServiceInfo;
use App\Support\Database\WebsiteCatalogUpgrade;
use Database\Seeders\WebsiteGrowthServicesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebsiteGrowthServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_services_are_published_with_details_and_preserve_existing_records(): void
    {
        $existing = ServiceInfo::orderBy('id')->get()->toArray();
        $services = require database_path('data/website_growth_services.php');
        $this->seed(WebsiteGrowthServicesSeeder::class);

        $this->assertSame(count($existing) + 5, ServiceInfo::count());
        $this->assertSame($existing, ServiceInfo::whereIn('id', array_column($existing, 'id'))->orderBy('id')->get()->toArray());
        $this->get('/service/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('services', count($existing) + 5)->where('services.0.slug', 'website-speed-optimization')
            ->where('seo.title', 'Website & software engineering services | iTechOrbit'));

        foreach ($services as $slug => $expected) {
            $service = ServiceInfo::where('slug', $slug)->firstOrFail();
            $this->assertTrue($service->status);
            $this->assertGreaterThanOrEqual(5, count($service->deliverables));
            $this->assertCount(5, $service->approach);
            $this->assertCount(3, $service->use_cases);
            $this->assertLessThanOrEqual(255, mb_strlen($service->subtitle));
            $this->get('/services/'.$slug)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Website/Service')->where('service.title', $expected['title'])
                ->where('service.description', $expected['descriptions'])->has('service.deliverables')
                ->where('seo.canonical', rtrim(config('app.url'), '/').'/services/'.$slug));
            $this->get('/sitemaps/pages.xml')->assertSee('/services/'.$slug, false);
        }
    }

    public function test_rerunning_the_seeder_preserves_admin_edits_publication_and_order(): void
    {
        $this->seed(WebsiteGrowthServicesSeeder::class);
        $count = ServiceInfo::count();
        $service = ServiceInfo::where('slug', 'website-speed-optimization')->firstOrFail();
        $service->update(['title' => 'Edited speed service', 'descriptions' => '<p>Custom offer.</p>', 'status' => false, 'display_order' => 1]);

        $this->seed(WebsiteGrowthServicesSeeder::class);

        $this->assertSame($count, ServiceInfo::count());
        $this->assertSame('Edited speed service', $service->fresh()->title);
        $this->assertSame('<p>Custom offer.</p>', $service->fresh()->descriptions);
        $this->assertFalse($service->fresh()->status);
        $this->assertSame(1, $service->fresh()->display_order);
        $this->get('/services/website-speed-optimization')->assertNotFound();
    }

    public function test_marketing_refresh_keeps_identity_media_and_publication_choices(): void
    {
        $service = ServiceInfo::where('slug', 'laravel-development')->firstOrFail();
        $service->update(['subtitle' => 'Outdated copy', 'status' => false, 'image_path' => 'uploads/existing-cover.png']);
        ServiceInfo::where('slug', 'api-development')->delete();
        $count = ServiceInfo::count();
        $migration = new WebsiteCatalogUpgrade;
        $migration->refreshMarketing();

        $service->refresh();
        $this->assertSame($count, ServiceInfo::count());
        $this->assertSame('laravel-development', $service->slug);
        $this->assertSame('uploads/existing-cover.png', $service->image_path);
        $this->assertFalse($service->status);
        $this->assertStringContainsString('business', $service->subtitle);
        $this->assertCount(5, $service->deliverables);
        $this->assertCount(3, $service->use_cases);
        $this->assertDatabaseMissing('website_services', ['slug' => 'api-development']);
        $this->assertSame($service->descriptions, $service->fresh()->descriptions);
    }
}

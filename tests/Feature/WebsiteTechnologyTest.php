<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Route;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteTechnology;
use App\Services\Shared\PublicContent;
use App\Services\Shared\WebsiteTechnologyImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class WebsiteTechnologyTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [...['title' => 'Technology test service', 'slug' => 'technology-test-service', 'subtitle' => 'Summary',
            'descriptions' => '<p>Details</p>', 'is_checked' => true, 'technology_ids' => []], ...$overrides];
    }

    public function test_catalog_filters_and_legacy_links_use_the_same_database_records(): void
    {
        $seo = WebsiteTechnology::where('slug', 'seo')->firstOrFail();
        ServiceInfo::published()->firstOrFail()->websiteTechnologies()->syncWithoutDetaching([$seo->id]);
        $this->assertSame(15, WebsiteTechnology::count());
        $this->assertDatabaseMissing('website_technologies', ['name' => 'Google Search Console']);
        $this->get('/service/info?group=seo')->assertStatus(301)->assertRedirect('/service/info?technology=seo');
        $this->get('/service/info?technology=google-search-console')->assertStatus(301)->assertRedirect('/service/info?technology=seo');
        $this->get('/service/info?group=databases')->assertRedirect('/service/info?technology=sql');
        $this->get('/service/info?group=unknown')->assertNotFound();
        $this->get('/service/info?technology=seo')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('selectedTechnology.name', 'SEO')->where('seo.noindex', true)
            ->where('seo.canonical', rtrim(config('app.url'), '/').'/service/info')
            ->where('technologies', fn ($items) => collect($items)->contains('name', 'SEO') && ! collect($items)->contains('name', 'Google Analytics 4')));
        $seo = WebsiteTechnology::where('slug', 'seo')->firstOrFail();
        $service = ServiceInfo::published()->firstOrFail();
        $service->websiteTechnologies()->syncWithoutDetaching([$seo->id]);
        $this->get('/service/info?technology=seo')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('services', fn ($items) => collect($items)->pluck('slug')->unique()->count() === count($items)));
        $seo->update(['status' => false]);
        $this->get('/service/info?technology=google-search-console')->assertNotFound();
    }

    public function test_catalog_visibility_is_controlled_independently_by_admin(): void
    {
        $custom = WebsiteTechnology::create(['name' => 'Custom technology', 'slug' => 'custom-technology', 'status' => true, 'show_on_homepage' => true, 'show_in_filters' => false]);
        WebsiteTechnology::create(['name' => 'Unused technology', 'slug' => 'unused-technology', 'status' => true, 'show_on_homepage' => true]);
        ServiceInfo::published()->firstOrFail()->websiteTechnologies()->attach($custom);
        $this->get('/service/info')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('selectedTechnology', null)->where('seo.noindex', false)
            ->where('technologies', fn ($items) => ! collect($items)->contains('name', 'Custom technology') && ! collect($items)->contains('name', 'Unused technology')));
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('toolkit', fn ($items) => collect($items)->contains('id', $custom->id)));
        $this->actingAs($this->user(1))->putJson('/website-technologies/'.$custom->id, ['name' => $custom->name, 'slug' => $custom->slug, 'is_checked' => true, 'show_on_homepage' => false, 'show_in_filters' => true])->assertOk();
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('toolkit', fn ($items) => ! collect($items)->contains('id', $custom->id)));
        $this->get('/service/info')->assertOk()->assertInertia(fn (Assert $page) => $page->where('technologies', fn ($items) => collect($items)->contains('id', $custom->id)));
    }

    public function test_admin_merge_transfers_assignments_aliases_and_preserves_target_settings(): void
    {
        $this->actingAs($this->user(1));
        $source = WebsiteTechnology::create(['name' => 'Old tool', 'slug' => 'old-tool', 'status' => true]);
        $target = WebsiteTechnology::where('slug', 'laravel')->firstOrFail();
        $service = ServiceInfo::published()->firstOrFail();
        $service->websiteTechnologies()->syncWithoutDetaching([$source->id, $target->id]);
        $settings = $target->toArray();
        $this->putJson('/website-technologies/'.$source->id, ['name' => 'Renamed tool', 'slug' => 'renamed-tool', 'is_checked' => true])->assertOk();
        $this->get('/service/info?technology=old-tool')->assertRedirect('/service/info?technology=renamed-tool');
        $this->postJson('/website-technologies/'.$source->id.'/merge', ['target_id' => $source->id])->assertUnprocessable();
        $this->postJson('/website-technologies/'.$source->id.'/merge', ['target_id' => $target->id])->assertOk();
        $this->assertModelMissing($source);
        $this->assertSame($settings, $target->fresh()->toArray());
        $this->assertSame(1, $service->websiteTechnologies()->where('website_technologies.id', $target->id)->count());
        $this->get('/service/info?technology=old-tool')->assertRedirect('/service/info?technology=laravel');
        $this->get('/service/info?technology=renamed-tool')->assertRedirect('/service/info?technology=laravel');
        $this->postJson('/website-technologies', ['name' => 'Old tool', 'slug' => 'different'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/website-technologies', ['name' => 'Different tool', 'slug' => 'old-tool'])->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_consolidation_preserves_existing_services_and_deduplicates_relationships(): void
    {
        $source = WebsiteTechnology::create(['name' => 'Google Search Console', 'slug' => 'google-search-console', 'status' => true]);
        $target = WebsiteTechnology::where('slug', 'seo')->firstOrFail();
        $service = ServiceInfo::published()->firstOrFail();
        $service->websiteTechnologies()->syncWithoutDetaching([$source->id, $target->id]);
        $snapshot = $service->fresh()->toArray();
        \Illuminate\Support\Facades\Schema::drop('website_technology_aliases');
        \Illuminate\Support\Facades\Schema::table('website_technologies', fn ($table) => $table->dropColumn('show_in_filters'));
        $migration = require database_path('migrations/2026_10_22_000003_consolidate_technology_catalog.php');
        $migration->up();
        $this->assertSame(15, WebsiteTechnology::count());
        $this->assertSame($snapshot, $service->fresh()->toArray());
        $this->assertSame(1, $service->websiteTechnologies()->where('website_technologies.id', $target->id)->count());
        $this->assertModelMissing($source);
        $this->get('/service/info?technology=google-search-console')->assertRedirect('/service/info?technology=seo');
    }

    public function test_import_normalizes_names_without_changing_legacy_service_data(): void
    {
        $service = ServiceInfo::create(['title' => 'Import test', 'slug' => 'import-test', 'subtitle' => 'Summary', 'descriptions' => '<p>Keep this.</p>',
            'status' => true, 'technologies' => [' laravel ', 'LARAVEL', 'REST', 'REST APIs', 'MySQL', 'SQL', 'Special Tool']]);
        WebsiteTechnology::create(['name' => 'Special Tool', 'slug' => 'special-tool', 'status' => true]);
        $snapshot = $service->fresh()->toArray();
        WebsiteTechnologyImport::service($service);
        WebsiteTechnologyImport::service($service);
        $this->assertSame($snapshot, $service->fresh()->toArray());
        $this->assertEqualsCanonicalizing(['Laravel', 'REST APIs', 'SQL', 'Special Tool'], $service->websiteTechnologies()->pluck('name')->all());
        $this->assertSame(1, WebsiteTechnology::whereRaw('LOWER(name) = ?', ['laravel'])->count());
        $laravel = WebsiteTechnology::where('slug', 'laravel')->firstOrFail();
        $laravel->update(['status' => false, 'show_on_homepage' => false]);
        WebsiteTechnologyImport::service($service);
        $this->assertFalse($laravel->fresh()->status);
        $this->assertFalse($laravel->fresh()->show_on_homepage);
    }

    public function test_import_reports_unknown_labels_without_creating_records_or_partial_assignments(): void
    {
        $service = ServiceInfo::create(['title' => 'Unknown import', 'slug' => 'unknown-import', 'subtitle' => 'Summary', 'descriptions' => '<p>Details</p>', 'status' => true, 'technologies' => ['Laravel', 'Unknown tool']]);
        try {
            WebsiteTechnologyImport::service($service);
            $this->fail('Expected an unknown technology error.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Unknown tool', $error->getMessage());
        }
        $this->assertSame(15, WebsiteTechnology::count());
        $this->assertSame(0, $service->websiteTechnologies()->count());
    }

    public function test_public_filters_hide_unpublished_services_and_inactive_technologies(): void
    {
        $technology = WebsiteTechnology::where('slug', 'laravel')->firstOrFail();
        $ids = $technology->services()->where('status', true)->pluck('website_services.slug')->sort()->values()->all();
        $this->get('/service/info?technology=laravel')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('selectedTechnology.id', $technology->id)->where('seo.noindex', true)
            ->where('seo.canonical', rtrim(config('app.url'), '/').'/service/info')
            ->where('services', fn ($services) => collect($services)->pluck('slug')->sort()->values()->all() === $ids));
        $service = $technology->services()->firstOrFail();
        $this->get('/services/'.$service->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('service.technologies', fn ($names) => in_array('Laravel', $names->all()))
            ->where('service.technologyLinks', fn ($links) => collect($links)->contains('url', '/service/info?technology=laravel')));
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('toolkit.0.name', 'Laravel'));
        $technology->update(['status' => false]);
        $this->get('/service/info?technology=laravel')->assertNotFound();
        $this->get('/service/info?technology=does-not-exist')->assertNotFound();
        $this->get('/service/info?technology=0')->assertNotFound();
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('toolkit', fn ($items) => ! collect($items)->contains('slug', 'laravel')));
        $this->assertNotContains('Laravel', app(PublicContent::class)->service($service->fresh())['technologies']);
        $this->get('/service/info')->assertOk()->assertInertia(fn (Assert $page) => $page->where('seo.noindex', false));
    }

    public function test_empty_active_technology_filter_and_homepage_visibility_rules(): void
    {
        $technology = WebsiteTechnology::create(['name' => 'Unused tool', 'slug' => 'unused-tool', 'status' => true, 'show_on_homepage' => true]);
        $this->get('/service/info?technology=unused-tool')->assertOk()->assertInertia(fn (Assert $page) => $page->has('services', 0));
        ServiceInfo::query()->update(['status' => false]);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('toolkit', 0));
        $service = ServiceInfo::firstOrFail();
        $service->update(['status' => true]);
        $service->websiteTechnologies()->sync([$technology->id]);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('toolkit', 1)->where('toolkit.0.slug', 'unused-tool'));
        $technology->update(['show_on_homepage' => false]);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('toolkit', 0));
    }

    public function test_admin_can_manage_technologies_and_cannot_delete_attached_records(): void
    {
        $this->actingAs($this->user(1));
        $payload = ['name' => 'Example tool', 'slug' => 'example-tool', 'is_checked' => 'true', 'show_on_homepage' => 'true', 'display_order' => 15];
        $this->postJson('/website-technologies', $payload)->assertOk();
        $technology = WebsiteTechnology::where('slug', 'example-tool')->firstOrFail();
        $this->assertTrue($technology->show_on_homepage);
        $this->getJson('/workspace/data/website-technologies?sort=services_count&direction=desc')->assertOk()->assertJsonStructure(['records' => ['data' => [['services_count', 'show_in_filters']]]]);
        $this->postJson('/website-technologies', [...$payload, 'name' => 'EXAMPLE TOOL', 'slug' => 'another-tool'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->putJson('/website-technologies/'.$technology->id, [...$payload, 'name' => 'Updated tool', 'show_on_homepage' => false])->assertOk();
        $this->get('/website-technologies/'.$technology->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page->where('record.name', 'Updated tool'));
        $service = ServiceInfo::firstOrFail();
        $service->websiteTechnologies()->attach($technology->id);
        $this->deleteJson('/website-technologies/'.$technology->id)->assertUnprocessable()->assertJsonValidationErrors('technology');
        $this->assertDatabaseHas('website_service_technology', ['website_service_id' => $service->id, 'website_technology_id' => $technology->id]);
        $service->websiteTechnologies()->detach($technology->id);
        $this->deleteJson('/website-technologies/'.$technology->id)->assertOk();
        $this->assertModelMissing($technology);
    }

    public function test_service_assignments_save_clear_and_reject_invalid_or_new_inactive_choices(): void
    {
        $this->actingAs($this->user(1));
        $technology = WebsiteTechnology::where('slug', 'laravel')->firstOrFail();
        $this->postJson('/service-info', $this->payload(['technology_ids' => [$technology->id]]))->assertOk();
        $service = ServiceInfo::where('slug', 'technology-test-service')->firstOrFail();
        $this->assertSame([$technology->id], $service->websiteTechnologies()->pluck('website_technologies.id')->all());
        $technology->update(['status' => false]);
        $this->putJson('/service-info/'.$service->id, $this->payload(['technology_ids' => [$technology->id]]))->assertOk();
        $this->get('/service-info/'.$service->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('record.technology_ids', [(string) $technology->id])
            ->where('options.technology_ids', fn ($choices) => collect($choices)->contains('label', 'Laravel (inactive)')));
        $this->putJson('/service-info/'.$service->id, $this->payload(['technology_ids' => []]))->assertOk();
        $this->assertSame(0, $service->websiteTechnologies()->count());
        $this->putJson('/service-info/'.$service->id, $this->payload(['title' => 'Must not save', 'technology_ids' => [$technology->id]]))->assertUnprocessable();
        $this->assertSame('Technology test service', $service->fresh()->title);
        $this->putJson('/service-info/'.$service->id, $this->payload(['technology_ids' => [999999]]))->assertUnprocessable();
    }

    public function test_relationship_failure_rolls_back_service_fields(): void
    {
        $this->actingAs($this->user(1));
        $technology = WebsiteTechnology::where('slug', 'laravel')->firstOrFail();
        DB::statement("CREATE TRIGGER fail_technology_attach BEFORE INSERT ON website_service_technology BEGIN SELECT RAISE(ABORT, 'Simulated assignment failure'); END");
        try {
            $this->postJson('/service-info', $this->payload(['technology_ids' => [$technology->id]]))->assertServerError();
            $this->assertDatabaseMissing('website_services', ['slug' => 'technology-test-service']);
        } finally {
            DB::statement('DROP TRIGGER fail_technology_attach');
        }
    }

    public function test_technology_permissions_and_navigation_follow_existing_controls(): void
    {
        $member = $this->user();
        $this->permissionResources();
        $this->actingAs($member)->get('/website-technologies')->assertForbidden();
        $link = Link::where('url', 'website-technologies')->firstOrFail();
        Permission::create(['role_id' => $member->role_id, 'link_id' => $link->id, 'route_id' => Route::where('name', 'index')->value('id')]);
        $this->get('/website-technologies')->assertOk();
        $this->get('/workspace/data/website-technologies')->assertOk();
        $this->postJson('/website-technologies', ['name' => 'Denied', 'slug' => 'denied'])->assertForbidden();
        $this->postJson('/website-technologies/'.WebsiteTechnology::firstOrFail()->id.'/merge', ['target_id' => WebsiteTechnology::latest('id')->firstOrFail()->id])->assertForbidden();
        $this->deleteJson('/website-technologies/'.WebsiteTechnology::firstOrFail()->id)->assertForbidden();
        $this->actingAs($this->user(1))->get('/website-technologies')->assertOk();
    }
}

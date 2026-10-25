<?php

namespace Tests\Feature;

use App\Support\Database\WebsiteCatalogUpgrade;
use App\Support\Database\WebsiteContentUpgrade;
use App\Support\Database\WebsiteTechnologyUpgrade;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsolidatedWebsiteMigrationsTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.consolidation_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('consolidation_test');
        $paths = array_values(array_filter(glob(database_path('migrations/*.php')), fn ($path) => basename($path) < '2026_10_18'));
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'consolidation_test', '--path' => $paths, '--realpath' => true, '--force' => true]));
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('consolidation_test');
        parent::tearDown();
    }

    private function migrate(): void
    {
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'consolidation_test', '--force' => true]));
    }

    private function history(string $name): void
    {
        DB::table('migrations')->insert(['migration' => $name, 'batch' => 2]);
    }

    private function legacyContact(): int
    {
        return DB::table('contact_infos')->insertGetId(['title' => 'Existing heading', 'subtitle' => 'Existing subtitle',
            'descriptions' => '<p>Existing introduction.</p>', 'status' => 1, 'created_at' => '2025-01-01 12:00:00', 'updated_at' => '2025-02-01 12:00:00']);
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach (['website_services', 'website_products', 'website_contact', 'website_about', 'website_technologies',
            'website_service_technology', 'website_technology_aliases', 'links', 'permissions', 'ai_pro_sites'] as $table) {
            $snapshot[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $snapshot;
    }

    public function test_upgrade_from_october_17_preserves_content_and_repeated_migrate_does_nothing(): void
    {
        $contactId = $this->legacyContact();
        $contact = (array) DB::table('contact_infos')->find($contactId);
        DB::table('service_infos')->insert(['id' => 42, 'title' => 'Legacy service', 'subtitle' => 'Summary',
            'descriptions' => '<p>Original service HTML.</p>', 'status' => 0]);
        DB::table('about_infos')->insert(['id' => 42, 'title' => 'About', 'subtitle' => 'Original', 'descriptions' => '<p>About HTML.</p>', 'status' => 1]);
        $this->migrate();
        $this->assertSame($contact, (array) DB::table('website_contact')->select(array_keys($contact))->find($contactId));
        $this->assertSame('<p>Original service HTML.</p>', DB::table('website_services')->where('id', 42)->value('descriptions'));
        $this->assertSame(0, DB::table('website_services')->where('id', 42)->value('status'));
        $this->assertSame('<p>About HTML.</p>', DB::table('website_about')->where('id', 42)->value('descriptions'));
        $this->assertSame(15, DB::table('website_technologies')->count());
        $this->assertTrue(Schema::hasTable('website_products'));
        $this->assertTrue(Schema::hasTable('ai_pro_messages'));
        $this->assertFalse(Schema::hasTable('website_contact_details'));
        $this->assertFalse(DB::table('links')->where('url', 'website-contact-details')->exists());
        $this->assertFalse(Schema::hasColumn('website_services', 'service_type'));
        $before = $this->snapshot();
        $history = DB::table('migrations')->get()->toJson();
        $this->migrate();
        $this->assertSame($before, $this->snapshot());
        $this->assertSame($history, DB::table('migrations')->get()->toJson());
    }

    public function test_partial_upgrade_preserves_product_records_permissions_and_completed_marketing_edits(): void
    {
        $contactId = $this->legacyContact();
        $upgrade = new WebsiteCatalogUpgrade;
        $upgrade->expandServices();
        $this->history('2026_10_18_000001_create_website_services_from_service_infos');
        $product = ['id' => 82, 'slug' => 'existing-product', 'title' => 'Existing product', 'subtitle' => 'Summary',
            'descriptions' => '<p>Product HTML.</p>', 'service_type' => 'product', 'price' => 125.50,
            'currency' => 'USD', 'delivery_time' => 'One week', 'image_path' => 'uploads/original.png', 'status' => 0];
        DB::table('website_services')->insert($product);
        $upgrade->refreshMarketing();
        $this->history('2026_10_21_000001_refresh_website_service_marketing');
        DB::table('website_services')->where('slug', 'laravel-development')->update(['subtitle' => 'Admin-edited copy', 'display_order' => 777, 'status' => 0]);
        Schema::rename('contact_infos', 'website_contact');
        Schema::rename('about_infos', 'website_about');
        $this->history('2026_10_18_000003_rename_website_content_tables');
        $this->temporaryContactProfile(['email' => 'saved@example.test', 'address' => "Office\nDhaka", 'legacy_content_backup' => json_encode(['id' => $contactId, 'original' => '<p>Original backup</p>'])]);
        $contactLink = DB::table('links')->where('url', 'contact-info')->first();
        $role = DB::table('roles')->insertGetId(['name' => 'Editor', 'status' => 1]);
        $route = DB::table('routes')->insertGetId(['name' => 'index', 'status' => 1]);
        DB::table('permissions')->insert(['role_id' => $role, 'route_id' => $route, 'link_id' => $contactLink->id]);
        $oldLink = DB::table('links')->insertGetId(['module_id' => $contactLink->module_id, 'name' => 'Temporary details', 'url' => 'website-contact-details', 'status' => 1]);
        DB::table('permissions')->insert(['role_id' => $role, 'route_id' => $route, 'link_id' => $oldLink]);
        $oldHistory = DB::table('migrations')->get()->map(fn ($row) => (array) $row)->all();
        $this->migrate();
        $moved = (array) DB::table('website_products')->find(82);
        foreach ($product as $key => $value) {
            if ($key !== 'service_type') {
                $this->assertEquals($value, $moved[$key]);
            }
        }
        $this->assertNull(DB::table('website_services')->find(82));
        $this->assertSame('Admin-edited copy', DB::table('website_services')->where('slug', 'laravel-development')->value('subtitle'));
        $this->assertSame(777, DB::table('website_services')->where('slug', 'laravel-development')->value('display_order'));
        $contact = DB::table('website_contact')->find($contactId);
        $this->assertSame('saved@example.test', $contact->email);
        $this->assertSame('<p>Existing introduction.</p>', $contact->descriptions);
        $this->assertNotNull($contact->legacy_contact_details_backup);
        $this->assertTrue(DB::table('permissions')->where('link_id', $contactLink->id)->exists());
        $this->assertFalse(DB::table('permissions')->where('link_id', $oldLink)->exists());
        foreach ($oldHistory as $row) {
            $this->assertSame($row, (array) DB::table('migrations')->find($row['id']));
        }
    }

    public function test_fully_migrated_legacy_installation_keeps_admin_changes_and_existing_history(): void
    {
        $this->legacyContact();
        $this->migrate();
        // Represent the fully applied, superseded migration chain on the same final schema.
        DB::table('migrations')->where('migration', 'like', '2026_10_24_%')->delete();
        foreach (['2026_10_18_000001_create_website_services_from_service_infos', '2026_10_18_000003_rename_website_content_tables',
            '2026_10_18_000004_separate_website_products', '2026_10_21_000001_refresh_website_service_marketing',
            '2026_10_22_000001_create_website_technologies', '2026_10_22_000002_register_website_technologies_navigation',
            '2026_10_22_000003_consolidate_technology_catalog', '2026_10_23_000001_create_website_contact_details_table',
            '2026_10_23_000002_register_website_contact_details_navigation', '2026_10_23_000003_merge_contact_details_into_website_contact'] as $name) {
            $this->history($name);
        }
        DB::table('website_services')->where('slug', 'laravel-development')->update(['subtitle' => 'Edited by admin']);
        DB::table('website_contact')->update(['email' => 'edited@example.test', 'descriptions' => '<h3>Email</h3><p>editorial@example.test</p>']);
        DB::table('website_technologies')->where('slug', 'seo')->update(['name' => 'Edited SEO', 'show_on_homepage' => false, 'status' => false]);
        $snapshot = $this->snapshot();
        $history = DB::table('migrations')->get()->map(fn ($row) => (array) $row)->all();
        $this->migrate();
        $this->assertSame($snapshot, $this->snapshot());
        foreach ($history as $row) {
            $this->assertSame($row, (array) DB::table('migrations')->find($row['id']));
        }
    }

    public function test_existing_technology_assignments_and_navigation_permissions_survive_partial_upgrade(): void
    {
        $upgrade = new WebsiteCatalogUpgrade;
        $upgrade->expandServices();
        $upgrade->separateProducts();
        $technologyUpgrade = new WebsiteTechnologyUpgrade;
        $technologyUpgrade->createCatalog();
        $technologyUpgrade->registerNavigation();
        DB::table('website_technologies')->where('name', 'Laravel')->update(['status' => false, 'display_order' => 123]);
        $technologyId = DB::table('website_technologies')->where('name', 'Laravel')->value('id');
        $assignments = DB::table('website_service_technology')->where('website_technology_id', $technologyId)->pluck('website_service_id')->all();
        $link = DB::table('links')->where('url', 'website-technologies')->first();
        DB::table('links')->where('id', $link->id)->update(['status' => 0, 'name' => 'Admin menu label']);
        $this->migrate();
        $this->assertSame($assignments, DB::table('website_service_technology')->where('website_technology_id', $technologyId)->pluck('website_service_id')->all());
        $this->assertSame(0, DB::table('website_technologies')->where('id', $technologyId)->value('status'));
        $this->assertSame(123, DB::table('website_technologies')->where('id', $technologyId)->value('display_order'));
        $this->assertSame('Admin menu label', DB::table('links')->where('id', $link->id)->value('name'));
        $this->assertSame(0, DB::table('links')->where('id', $link->id)->value('status'));
    }

    public function test_contact_merge_fills_only_empty_fields_and_preserves_original_snapshot(): void
    {
        $contactId = $this->legacyContact();
        $this->migrate();
        $this->temporaryContactProfile(['email' => 'old@example.test', 'address' => "Old office\nDhaka"]);
        DB::table('website_contact')->where('id', $contactId)->update(['email' => 'current@example.test', 'legacy_contact_details_backup' => '{"original":"keep"}']);
        (new WebsiteContentUpgrade)->run();
        $contact = DB::table('website_contact')->find($contactId);
        $this->assertSame('current@example.test', $contact->email);
        $this->assertSame("Old office\nDhaka", $contact->address);
        $this->assertSame('{"original":"keep"}', $contact->legacy_contact_details_backup);
    }

    public function test_contact_details_are_extracted_directly_without_creating_temporary_table(): void
    {
        $contactId = $this->legacyContact();
        $original = '<p>Introduction.</p><h3>Email</h3><p>hello@example.test</p><h3>Address</h3><p>Office<br>Dhaka</p>';
        DB::table('contact_infos')->where('id', $contactId)->update(['descriptions' => $original]);
        $this->migrate();
        $contact = DB::table('website_contact')->find($contactId);
        $this->assertSame('hello@example.test', $contact->email);
        $this->assertSame("Office\nDhaka", $contact->address);
        $this->assertSame('<p>Introduction.</p>', $contact->descriptions);
        $this->assertSame($original, json_decode($contact->legacy_contact_details_backup, true)['original']);
        $this->assertFalse(Schema::hasTable('website_contact_details'));
    }

    public function test_consolidated_rollback_stops_before_removing_data(): void
    {
        $this->migrate();
        $before = $this->snapshot();
        foreach (glob(database_path('migrations/2026_10_24_*.php')) as $path) {
            $migration = require $path;
            try {
                $migration->down();
                $this->fail('Expected the consolidation rollback boundary.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('pre-upgrade database backup', $exception->getMessage());
            }
        }
        $this->assertSame($before, $this->snapshot());
    }

    private function temporaryContactProfile(array $attributes): void
    {
        Schema::create('website_contact_details', function (Blueprint $table) {
            $table->id();
            $table->string('profile_key')->default('business')->unique();
            $table->string('website_url', 2048)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('status')->default(false);
            $table->json('legacy_content_backup')->nullable();
            $table->timestamps();
        });
        DB::table('website_contact_details')->insert(['status' => true, ...$attributes]);
    }
}

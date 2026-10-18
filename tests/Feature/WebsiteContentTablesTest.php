<?php

namespace Tests\Feature;

use App\AiPro\LocalContent;
use App\AiPro\Models\Site;
use App\Models\Admin\AboutInfo;
use App\Models\Admin\ContactInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class WebsiteContentTablesTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_renaming_and_rollback_preserve_every_content_field(): void
    {
        $migration = require database_path('migrations/2026_10_18_000003_rename_website_content_tables.php');
        $migration->down();
        $record = ['id' => 42, 'title' => 'Existing content', 'subtitle' => 'Original subtitle',
            'descriptions' => '<p>Original content.</p>', 'status' => 1,
            'created_at' => '2025-01-01 12:00:00', 'updated_at' => '2025-02-01 12:00:00'];
        foreach (['contact_infos', 'about_infos'] as $table) {
            DB::table($table)->insert($record);
        }

        $migration->up();
        foreach (['website_contact', 'website_about'] as $table) {
            $this->assertSame($record, (array) DB::table($table)->find(42));
        }
        $this->assertFalse(Schema::hasTable('contact_infos'));
        $this->assertFalse(Schema::hasTable('about_infos'));
        $this->assertSame('Existing content', ContactInfo::findOrFail(42)->title);
        $this->assertSame('Existing content', AboutInfo::findOrFail(42)->title);

        $migration->down();
        foreach (['contact_infos', 'about_infos'] as $table) {
            $this->assertSame($record, (array) DB::table($table)->find(42));
        }
        $this->assertFalse(Schema::hasTable('website_contact'));
        $this->assertFalse(Schema::hasTable('website_about'));
        $migration->up();
    }

    public function test_admin_public_pages_and_ai_read_the_renamed_tables(): void
    {
        $this->actingAs($this->user(1));
        $site = new Site(['name' => 'Test website', 'url' => 'https://example.test']);
        foreach (['about' => AboutInfo::class, 'contact' => ContactInfo::class] as $type => $model) {
            $payload = ['title' => ucfirst($type).' content', 'subtitle' => 'Website subtitle',
                'descriptions' => '<p>Published '.$type.' content.</p>', 'is_checked' => true];
            $this->postJson('/'.$type.'-info', $payload)->assertOk();
            $record = $model::firstOrFail();
            $this->assertDatabaseHas('website_'.$type, ['id' => $record->id, 'title' => $payload['title']]);
            $this->get('/'.$type.'-info')->assertOk();
            $this->get('/'.$type.'-info/'.$record->id)->assertOk();
            $this->get('/'.$type.'-info/'.$record->id.'/edit')->assertOk();
            $this->putJson('/'.$type.'-info/'.$record->id, [...$payload, 'descriptions' => '<p>Updated '.$type.' content.</p>'])->assertOk();
            $this->get('/'.$type.'/info')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('existingContent', '<p>Updated '.$type.' content.</p>'));
            $snapshot = app(LocalContent::class)->snapshot($site);
            $this->assertSame('Updated '.$type.' content.', trim(str_replace('Website subtitle', '', $snapshot[$type]['content'])));
            $this->deleteJson('/'.$type.'-info/'.$record->id)->assertOk();
            $this->assertDatabaseMissing('website_'.$type, ['id' => $record->id]);
        }
        $this->assertTrue(Schema::hasTable('contacts'));
    }
}

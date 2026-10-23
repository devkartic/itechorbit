<?php

namespace Tests\Feature;

use App\AiPro\LocalContent;
use App\AiPro\Models\Site;
use App\Models\Admin\AboutInfo;
use App\Models\Admin\ContactInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class WebsiteContentTablesTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

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

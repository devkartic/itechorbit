<?php

namespace Tests\Feature;

use App\AiPro\LocalContent;
use App\AiPro\Models\Site;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteProduct;
use Database\Seeders\ITechOrbitAiProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ITechOrbitAiProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_is_published_and_available_in_public_pages_and_ai_knowledge(): void
    {
        $this->seed(ITechOrbitAiProductSeeder::class);
        $product = WebsiteProduct::where('slug', 'itechorbit-ai-assistant')->sole();
        $this->assertTrue($product->status);
        $this->assertNull($product->price);
        $this->assertNull($product->currency);
        $this->assertNull($product->delivery_time);
        foreach (['/', '/products'] as $url) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('products.0.slug', 'itechorbit-ai-assistant')
                ->missing('products.0.serviceType'));
        }
        $this->get('/services/itechorbit-ai-assistant')->assertStatus(301)->assertRedirect('/products/itechorbit-ai-assistant');
        $this->get('/service/info')->assertOk()->assertInertia(fn (Assert $page) => $page->where('services', fn ($services) => ! collect($services)->contains('slug', 'itechorbit-ai-assistant')));
        $this->get('/products/itechorbit-ai-assistant')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Website/Product')->where('product.title', 'iTechOrbit AI Assistant')
            ->where('product.price', null)->has('product.deliverables', 7)->has('product.useCases', 3));
        $this->get('/sitemaps/pages.xml')->assertOk()->assertSee('/products/itechorbit-ai-assistant', false);
        $items = app(LocalContent::class)->snapshot(new Site(['name' => 'iTechOrbit', 'url' => 'https://example.test']));
        $item = collect($items)->firstWhere('url', 'https://example.test/products/itechorbit-ai-assistant');
        $this->assertNotNull($item);
        $this->assertStringContainsString('iTechOrbit AI Assistant', $item['content']);
        $this->assertSame('ai-workflow-integration', ServiceInfo::where('highlight', true)->sole()->slug);
    }

    public function test_unpublished_products_are_hidden_from_public_pages_and_ai(): void
    {
        $this->seed(ITechOrbitAiProductSeeder::class);
        WebsiteProduct::query()->update(['status' => false]);
        $this->get('/products')->assertOk()->assertInertia(fn (Assert $page) => $page->has('products', 0));
        $this->get('/products/itechorbit-ai-assistant')->assertNotFound();
        $this->get('/services/itechorbit-ai-assistant')->assertNotFound();
        $this->get('/sitemaps/pages.xml')->assertDontSee('/products/itechorbit-ai-assistant', false);
        $items = app(LocalContent::class)->snapshot(new Site(['name' => 'Test', 'url' => 'https://example.test']));
        $this->assertNull(collect($items)->firstWhere('url', 'https://example.test/products/itechorbit-ai-assistant'));
    }

    public function test_rerunning_the_seeder_preserves_admin_edits_without_duplicates(): void
    {
        $this->seed(ITechOrbitAiProductSeeder::class);
        $product = WebsiteProduct::where('slug', 'itechorbit-ai-assistant')->sole();
        $product->update(['title' => 'Edited product name', 'status' => false, 'price' => '125.00', 'currency' => 'USD']);
        $this->seed(ITechOrbitAiProductSeeder::class);
        $this->assertSame(1, WebsiteProduct::where('slug', 'itechorbit-ai-assistant')->count());
        $this->assertSame('Edited product name', $product->fresh()->title);
        $this->assertFalse($product->fresh()->status);
        $this->assertSame('125.00', $product->fresh()->price);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Route;
use App\Models\Admin\WebsiteProduct;
use App\Services\Shared\MediaFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class WebsiteProductsTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Application launch package', 'slug' => 'application-launch',
            'subtitle' => 'A defined launch package.', 'descriptions' => '<p>Implementation and handover.</p>',
            'deliverables' => "Application setup\nDeployment guide", 'technologies' => "Laravel\nReact",
            'approach' => "Discovery\nImplementation",
            'price' => '1250.50', 'currency' => 'USD', 'delivery_time' => 'Two weeks', 'display_order' => 0, 'is_checked' => 'true',
        ], $overrides);
    }

    public function test_services_support_admin_crud_and_public_database_content(): void
    {
        $this->actingAs($this->user(1));
        $this->postJson('/product-info', $this->payload())->assertOk();
        $product = WebsiteProduct::where('slug', 'application-launch')->firstOrFail();
        $this->assertSame(['Application setup', 'Deployment guide'], $product->deliverables);
        $this->get('/product-info/'.$product->id)->assertOk();
        $this->get('/product-info/'.$product->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('record.deliverables', "Application setup\nDeployment guide")->missing('options.service_type'));
        $this->get('/products/application-launch')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->missing('product.serviceType')->where('product.price', '1250.50')->where('product.deliveryTime', 'Two weeks'));
        $this->get('/products')->assertOk()->assertInertia(fn (Assert $page) => $page->where('products.0.slug', 'application-launch'));
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('products.0.slug', 'application-launch'));
        $this->get('/sitemaps/pages.xml')->assertSee('/products/application-launch', false);
        $this->get('/robots.txt')->assertDontSee('Disallow: /products', false);

        $this->putJson('/product-info/'.$product->id, $this->payload([
            'slug' => 'client-workflow', 'descriptions' => '<p>Client workflow</p><script>alert(1)</script>',
        ]))->assertOk();
        $this->get('/products/application-launch')->assertNotFound();
        $this->get('/products/client-workflow')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('product.description', '<p>Client workflow</p>'));
        $this->putJson('/product-info/'.$product->id, $this->payload(['slug' => 'client-workflow', 'is_checked' => false]))->assertOk();
        $this->get('/products/client-workflow')->assertNotFound();
        $this->get('/sitemaps/pages.xml')->assertDontSee('/products/client-workflow', false);
        $this->get('/robots.txt')->assertDontSee('Allow: /products/client-workflow', false);
        $this->deleteJson('/product-info/'.$product->id)->assertOk();
        $this->assertDatabaseMissing('website_products', ['id' => $product->id]);
    }

    public function test_validation_rejects_unsafe_slugs_invalid_types_and_package_data(): void
    {
        $this->actingAs($this->user(1));
        foreach (['create', '123', '../bad', 'Two Words'] as $slug) {
            $this->postJson('/product-info', $this->payload(['slug' => $slug]))->assertUnprocessable()->assertJsonValidationErrors('slug');
        }
        $this->postJson('/product-info', $this->payload(['service_type' => 'invalid', 'display_order' => -1, 'is_checked' => 'oops']))
            ->assertUnprocessable()->assertJsonValidationErrors(['service_type', 'display_order', 'is_checked']);
        $this->postJson('/product-info', $this->payload(['price' => -1, 'currency' => 'usd', 'use_cases' => 'Missing description']))
            ->assertUnprocessable()->assertJsonValidationErrors(['price', 'currency', 'use_cases.0.description']);
        $this->postJson('/product-info', $this->payload(['currency' => null]))->assertUnprocessable()->assertJsonValidationErrors('currency');
        $this->postJson('/product-info', $this->payload(['slug' => 'laravel-development']))->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_existing_permissions_protect_service_writes_and_reads(): void
    {
        $this->get('/product-info')->assertRedirect('/login');
        $this->postJson('/product-info', $this->payload())->assertUnauthorized();
        $this->permissionResources();
        $member = $this->user();
        $this->actingAs($member)->get('/product-info')->assertForbidden();
        $this->postJson('/product-info', $this->payload())->assertForbidden();
        $this->putJson('/product-info/'.WebsiteProduct::firstOrCreate(['title' => 'Private product', 'slug' => 'private-product', 'subtitle' => 'Summary', 'descriptions' => 'Details'])->id, $this->payload())->assertForbidden();
        $this->deleteJson('/product-info/'.WebsiteProduct::firstOrCreate(['title' => 'Private product', 'slug' => 'private-product', 'subtitle' => 'Summary', 'descriptions' => 'Details'])->id)->assertForbidden();
        Permission::create(['role_id' => $member->role_id, 'link_id' => Link::where('url', 'product-info')->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
        $this->get('/product-info')->assertOk();
        $this->postJson('/product-info', $this->payload())->assertForbidden();
    }

    public function test_service_permissions_do_not_grant_product_access(): void
    {
        $this->permissionResources();
        $member = $this->user();
        Permission::create(['role_id' => $member->role_id, 'link_id' => Link::where('url', 'service-info')->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
        $this->actingAs($member)->get('/service-info')->assertOk();
        $this->get('/product-info')->assertForbidden();
        $this->postJson('/product-info', $this->payload())->assertForbidden();
    }

    public function test_service_images_are_validated_preserved_replaced_and_removed(): void
    {
        $this->actingAs($this->user(1));
        $image = fn () => UploadedFile::fake()->createWithContent('cover.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3ioAAAAASUVORK5CYII='));
        $this->post('/product-info', [...$this->payload(), 'uploaded_image' => $image()], ['Accept' => 'application/json'])->assertOk();
        $service = WebsiteProduct::where('slug', 'application-launch')->firstOrFail();
        $first = $service->image_path;
        try {
            $this->assertFileExists(public_path($first));
            $this->get('/workspace/media/product-info/'.$service->id.'/image_path')->assertOk();
            $this->putJson('/product-info/'.$service->id, $this->payload())->assertOk();
            $this->assertSame($first, $service->fresh()->image_path);
            $invalid = UploadedFile::fake()->createWithContent('fake.png', 'invalid');
            $this->put('/product-info/'.$service->id, [...$this->payload(), 'uploaded_image' => new UploadedFile($invalid->getRealPath(), 'fake.png', 'image/png', null, true)], ['Accept' => 'application/json'])
                ->assertUnprocessable()->assertJsonValidationErrors('uploaded_image');
            $this->put('/product-info/'.$service->id, [...$this->payload(), 'uploaded_image' => $image()], ['Accept' => 'application/json'])->assertOk();
            $second = $service->fresh()->image_path;
            $this->assertNotSame($first, $second);
            $this->assertFileDoesNotExist(public_path($first));
            $this->putJson('/product-info/'.$service->id, $this->payload(['remove_image' => true]))->assertOk();
            $this->assertNull($service->fresh()->image_path);
            $this->assertFileDoesNotExist(public_path($second));
            $this->put('/product-info/'.$service->id, [...$this->payload(), 'uploaded_image' => $image()], ['Accept' => 'application/json'])->assertOk();
            $third = $service->fresh()->image_path;
            $this->deleteJson('/product-info/'.$service->id)->assertOk();
            $this->assertFileDoesNotExist(public_path($third));
        } finally {
            foreach (array_filter([$first, $second ?? null, $third ?? null, $service->fresh()?->image_path]) as $path) {
                MediaFiles::delete($path);
            }
        }
    }
}

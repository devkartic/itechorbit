<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleIntegrationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.tag_manager_id' => 'GTM-TEST123',
            'site.analytics_id' => 'G-TEST123',
            'site.verification' => 'search-console-test-token',
        ]);

        Route::middleware('web')->get('/_test/google-integrations/{area}', fn (string $area) => Inertia::render(match ($area) {
            'Website' => 'Website/Home',
            'Portfolio' => 'Portfolio/Show',
            'Admin' => 'Admin/Dashboard',
            default => $area.'/Index',
        }));
    }

    public static function publicAreas(): array
    {
        return [['Website'], ['Portfolio']];
    }

    #[DataProvider('publicAreas')]
    public function test_public_pages_render_integrations_in_the_correct_places(string $area): void
    {
        $response = $this->get('/_test/google-integrations/'.$area)->assertOk();
        $html = $response->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);

        $this->assertSame(1, $xpath->query('//head/script[contains(text(), "gtm.start")]')->length);
        $this->assertSame(1, $xpath->query('//head/script[contains(@src, "gtag/js?id=G-TEST123")]')->length);
        $this->assertSame(1, $xpath->query('//head/script[contains(text(), "gtag(\'config\'")]')->length);
        $this->assertSame(1, $xpath->query('//meta[@name="google-site-verification"]')->length);
        $this->assertSame('search-console-test-token', $xpath->query('//head/meta[@name="google-site-verification"]')->item(0)->getAttribute('content'));
        $this->assertSame('noscript', $xpath->query('//body/*[1]')->item(0)->nodeName);
        $this->assertStringContainsString('https://www.googletagmanager.com/ns.html?id=GTM-TEST123', $html);
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('https://*.google-analytics.com', $csp);
        $this->assertStringContainsString("frame-src 'self' https://www.googletagmanager.com", $csp);
        foreach ($xpath->query('//head/script[contains(text(), "gtm.start") or contains(text(), "gtag(") or contains(@src, "gtag/js")]') as $script) {
            $nonce = $script->getAttribute('nonce');
            $this->assertNotSame('', $nonce);
            $this->assertStringContainsString("'nonce-".$nonce."'", $csp);
        }
    }

    public static function privateAreas(): array
    {
        return [['Auth'], ['Account'], ['Admin']];
    }

    #[DataProvider('privateAreas')]
    public function test_private_pages_omit_integrations(string $area): void
    {
        $response = $this->get('/_test/google-integrations/'.$area)->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('google-site-verification', false)
            ->assertDontSee('GTM-TEST123', false)
            ->assertDontSee('G-TEST123', false);
        $this->assertStringNotContainsString('googletagmanager.com', $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('google-analytics.com', $response->headers->get('Content-Security-Policy'));
    }

    public function test_blank_configuration_omits_all_integrations(): void
    {
        config(['site.tag_manager_id' => '  ', 'site.analytics_id' => '', 'site.verification' => null]);

        $this->get('/_test/google-integrations/Website')->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('google-site-verification', false);
    }

    public function test_each_integration_can_be_enabled_independently(): void
    {
        config(['site.tag_manager_id' => null, 'site.analytics_id' => null]);
        $this->get('/_test/google-integrations/Website')->assertOk()
            ->assertSee('google-site-verification', false)->assertDontSee('googletagmanager.com', false);

        config(['site.tag_manager_id' => 'GTM-TEST123', 'site.verification' => null]);
        $this->get('/_test/google-integrations/Website')->assertOk()
            ->assertSee('gtm.js', false)->assertSee('ns.html?id=GTM-TEST123', false)
            ->assertDontSee('gtag/js', false)->assertDontSee('google-site-verification', false);

        config(['site.tag_manager_id' => null, 'site.analytics_id' => 'G-TEST123']);
        $this->get('/_test/google-integrations/Website')->assertOk()
            ->assertSee('gtag/js?id=G-TEST123', false)->assertDontSee('gtm.js', false)
            ->assertDontSee('ns.html', false)->assertDontSee('google-site-verification', false);
    }

    public function test_configuration_values_cannot_inject_markup_or_javascript(): void
    {
        $payload = '</script><script id="injected">alert(1)</script>';
        config(['site.tag_manager_id' => $payload, 'site.analytics_id' => $payload, 'site.verification' => '"><script id="injected">alert(1)</script>']);

        $this->get('/_test/google-integrations/Website')->assertOk()
            ->assertDontSee('<script id="injected">', false)
            ->assertSee(rawurlencode($payload), false)
            ->assertSee('\\u003C', false);
    }
}

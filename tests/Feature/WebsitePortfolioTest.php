<?php

namespace Tests\Feature;

use App\Services\Portfolio\PortfolioPresentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebsitePortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_service_is_public_and_uses_the_existing_template_catalog(): void
    {
        $this->get(route('public.portfolio_service'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Website/PortfolioService')
            ->where('seo.title', 'Professional Portfolio Service | iTechOrbit')
            ->where('seo.canonical', rtrim(config('app.url'), '/').'/portfolio_service')
            ->where('seo.noindex', false)
            ->has('seo.description')
            ->where('templates', array_map(fn ($key, $template) => ['key' => $key, ...$template], array_keys(PortfolioPresentation::TEMPLATES), array_values(PortfolioPresentation::TEMPLATES))));
        $this->get('/sitemaps/pages.xml')->assertOk()->assertSee('<loc>'.e(rtrim(config('app.url'), '/').'/portfolio_service').'</loc>', false);
    }
}

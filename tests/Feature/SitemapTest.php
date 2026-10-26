<?php

namespace Tests\Feature;

use App\AiPro\SafePageFetcher;
use App\Models\Admin\Project;
use App\Models\Admin\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_robots_blocks_workspace_and_searches_regardless_of_parameter_order(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        $parser = app(SafePageFetcher::class);
        foreach (['/workspace/ai-pro/overview', '/workspace/email-notices/compose', '/workspace/portfolio-templates',
            '/blogs?key=laravel', '/blogs?page=2&key=laravel', '/blogs?page=2&tag=php&key=laravel'] as $url) {
            $this->assertFalse($parser->allowedByRobots($robots, $url), $url.' should be blocked.');
        }
        foreach (['/', '/blogs', '/blogs?page=2', '/blogs?monkey=test', '/services/laravel-development', '/products', '/work', '/portfolios/PUBLIC'] as $url) {
            $this->assertTrue($parser->allowedByRobots($robots, $url), $url.' should remain crawlable.');
        }
    }

    public function test_portfolio_sitemap_encodes_identifiers_and_remains_valid_xml(): void
    {
        $identifiers = ['AUDIT PROFILE', 'AUDIT&PROFILE', 'AUDIT?PROFILE', 'AUDIT#PROFILE', 'AUDIT-ঢাকা'];
        foreach ($identifiers as $identifier) {
            $this->user(4, ['portfolio_id' => $identifier, 'portfolio_status' => 1]);
        }
        $xml = simplexml_load_string($this->get('/sitemaps/portfolios-1.xml')->assertOk()->getContent());
        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urls = array_map(fn ($loc) => (string) $loc, $xml->xpath('//s:url/s:loc'));
        $this->assertSame(array_map(fn ($identifier) => rtrim(config('app.url'), '/').'/portfolios/'.rawurlencode($identifier), $identifiers), $urls);
    }

    public function test_sitemaps_exclude_private_articles_disabled_portfolios_and_unpublished_projects(): void
    {
        $visible = $this->user(4, ['portfolio_id' => 'AUDIT-PUBLIC', 'portfolio_status' => 1]);
        $disabled = $this->user(4, ['portfolio_id' => 'AUDIT-DISABLED', 'portfolio_status' => 1, 'status' => 0]);
        $hidden = $this->user(4, ['portfolio_id' => 'AUDIT-HIDDEN', 'portfolio_status' => 0]);
        $publicPost = $this->article([], $visible);
        $privatePost = $this->article(['is_private' => 1], $visible);
        $draft = $this->article(['status' => 0], $visible);
        $this->get('/sitemaps/articles-1.xml')->assertOk()->assertSee('<loc>'.url('/blogs/'.$publicPost->id).'</loc>', false)
            ->assertDontSee('<loc>'.url('/blogs/'.$privatePost->id).'</loc>', false)->assertDontSee('<loc>'.url('/blogs/'.$draft->id).'</loc>', false);
        $this->get('/sitemaps/portfolios-1.xml')->assertOk()->assertSee('AUDIT-PUBLIC', false)->assertDontSee('AUDIT-DISABLED', false)->assertDontSee('AUDIT-HIDDEN', false);
        $skill = Skill::create(['user_id' => $visible->id, 'name' => 'Audit skill', 'status' => 1]);
        $projects = [];
        foreach ([[$visible, 1], [$visible, 0], [$disabled, 1], [$hidden, 1]] as [$owner, $status]) {
            $projects[] = Project::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'name' => 'Audit project', 'client_name' => 'Synthetic client', 'about_project' => 'Synthetic audit content', 'status' => $status]);
        }
        $response = $this->get('/sitemaps/projects-1.xml')->assertOk()->assertSee('<loc>'.url('/work/'.$projects[0]->id).'</loc>', false);
        foreach (array_slice($projects, 1) as $project) {
            $response->assertDontSee('<loc>'.url('/work/'.$project->id).'</loc>', false);
        }
    }
}

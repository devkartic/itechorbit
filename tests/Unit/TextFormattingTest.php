<?php

namespace Tests\Unit;

use App\Models\Admin\Project;
use App\Services\Shared\PublicContent;
use PHPUnit\Framework\TestCase;

class TextFormattingTest extends TestCase
{
    public function test_safe_font_options_and_links_survive_sanitization(): void
    {
        $html = (new PublicContent)->html('<p><span data-font-size="20" data-text-font="serif"><strong>Engineering</strong></span> <a href="https://example.com">Work</a></p>');
        $this->assertStringContainsString('data-font-size="20"', $html);
        $this->assertStringContainsString('data-text-font="serif"', $html);
        $this->assertStringContainsString('<strong>Engineering</strong>', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
    }

    public function test_project_contributions_preserve_plain_text_and_sanitize_formatted_html(): void
    {
        $project = new Project;
        $project->about_project = '<p>Project overview</p>';
        $project->jobs = "Use x < 10 & y > 1\nAPI ownership";
        $plain = (new PublicContent)->project($project, true)['contributions'];
        $this->assertStringContainsString('Use x &lt; 10 &amp; y &gt; 1', $plain);
        $this->assertStringContainsString('<br', $plain);
        $project->jobs = '<p><strong>API ownership</strong><img src="x" onerror="alert(1)"></p>';
        $html = (new PublicContent)->project($project, true)['contributions'];
        $this->assertStringContainsString('<strong>API ownership</strong>', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_arbitrary_css_and_unsafe_formatting_values_are_removed(): void
    {
        $html = (new PublicContent)->html('<span data-font-size="9999" data-text-font="evil" style="position:fixed" onclick="alert(1)">Text</span><a href="javascript:alert(1)">Link</a><script>alert(1)</script>');
        foreach (['9999', 'evil', 'style=', 'onclick', 'javascript:', '<script'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, $html);
        }
        $this->assertStringContainsString('Text', $html);
    }
}

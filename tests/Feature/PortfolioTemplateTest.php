<?php

namespace Tests\Feature;

use App\Models\Admin\Basic;
use App\Services\PortfolioPresentation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class PortfolioTemplateTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    public function test_pending_migration_keeps_previews_available_and_returns_a_validation_error_on_save(): void
    {
        $user = $this->user();
        // RefreshDatabase confines this schema change to the disposable test database.
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('portfolio_template'));
        $this->actingAs($user)->get('/workspace/portfolio-templates')->assertOk()->assertInertia(fn (Assert $page) => $page->where('storageReady', false)->where('template', 'studio'));
        $this->patch('/workspace/portfolio-templates', ['template' => 'spotlight'])->assertSessionHasErrors('template');
    }

    public function test_template_studio_requires_an_active_verified_account(): void
    {
        $this->get('/workspace/portfolio-templates')->assertRedirect('/login');
        $this->patch('/workspace/portfolio-templates', ['template' => 'studio'])->assertRedirect('/login');
        $user = $this->user(4, ['status' => 0]);
        $this->actingAs($user)->get('/workspace/portfolio-templates')->assertForbidden();
        $this->patch('/workspace/portfolio-templates', ['template' => 'studio'])->assertForbidden();
        $user->update(['status' => 1]);
        $user->forceFill(['email_verified_at' => null])->save();
        $this->get('/workspace/portfolio-templates')->assertRedirect('/email/verify');
        $this->patch('/workspace/portfolio-templates', ['template' => 'studio'])->assertRedirect('/email/verify');
    }

    public function test_unpublished_profile_can_preview_sanitized_own_content_without_becoming_public(): void
    {
        $user = $this->user(4, ['portfolio_status' => 0, 'portfolio_id' => 'PRIVATE']);
        Basic::create(['first_name' => 'Test', 'last_name' => 'Engineer', 'designation' => 'Engineer', 'mobile' => '', 'address' => '', 'service_offering_title' => '', 'career_objective' => 'Building thoughtful software.', 'experience_year' => 0, 'birth_date' => '1990-01-01', 'gender' => '', 'marital_status' => '', 'user_id' => $user->id, 'status' => 1, 'self_introduction' => '<p>My engineering story</p><script>alert(1)</script>']);
        $other = $this->user(4, ['name' => 'Other owner']);
        $this->actingAs($user)->get('/workspace/portfolio-templates?user_id='.$other->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/PortfolioTemplates')->has('templates', 5)->where('template', 'studio')
            ->where('profile.name', $user->name)->where('publishedUrl', null)
            ->where('profile.introduction', fn ($value) => str_contains($value, 'My engineering story') && ! str_contains($value, '<script'))
            ->missing('profile.email')->missing('profile.password'));
        $this->patch('/workspace/portfolio-templates', ['template' => 'blueprint', 'user_id' => $other->id])->assertRedirect('/workspace/portfolio-templates');
        $this->assertSame('blueprint', $user->fresh()->portfolio_template);
        $this->assertNull($other->fresh()->portfolio_template);
        $this->assertSame(0, (int) $user->fresh()->portfolio_status);
        $this->get('/portfolios/PRIVATE')->assertForbidden();
    }

    public function test_all_five_choices_persist_and_drive_existing_public_portfolio_urls(): void
    {
        $user = $this->user(4, ['portfolio_status' => 1, 'portfolio_id' => 'DESIGNS']);
        config(['site.owner_id' => $user->id]);
        foreach (array_keys(PortfolioPresentation::TEMPLATES) as $template) {
            $this->actingAs($user)->patch('/workspace/portfolio-templates', ['template' => $template])->assertSessionHasNoErrors()->assertRedirect('/workspace/portfolio-templates');
            $this->get('/workspace/portfolio-templates')->assertInertia(fn (Assert $page) => $page->where('template', $template)->where('publishedUrl', '/portfolios/DESIGNS'));
            $this->get('/portfolios/DESIGNS')->assertInertia(fn (Assert $page) => $page->component('Portfolio')->where('template', $template)->where('seo.noindex', false)->where('seo.canonical', config('app.url').'/portfolios/DESIGNS'));
            $this->get('/portfolios')->assertInertia(fn (Assert $page) => $page->where('template', $template));
        }
    }

    public function test_invalid_templates_are_rejected_and_unknown_stored_values_fall_back_safely(): void
    {
        $user = $this->user(4, ['portfolio_status' => 1, 'portfolio_id' => 'FALLBACK']);
        $user->forceFill(['portfolio_template' => 'old-retired-template'])->save();
        $this->actingAs($user)->patch('/workspace/portfolio-templates', ['template' => '../../unsafe'])->assertSessionHasErrors('template');
        $this->get('/portfolios/FALLBACK')->assertInertia(fn (Assert $page) => $page->where('template', 'studio'));
        $this->assertSame('old-retired-template', $user->fresh()->portfolio_template);
    }
}

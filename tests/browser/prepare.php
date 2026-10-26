<?php

use App\AiPro\Models\Site;
use App\Models\Admin\Basic;
use App\Models\Admin\Category;
use App\Models\Admin\ContactInfo;
use App\Models\Admin\Experience;
use App\Models\Admin\Link;
use App\Models\Admin\Menu;
use App\Models\Admin\Module;
use App\Models\Admin\Post;
use App\Models\Admin\Project;
use App\Models\Admin\Role;
use App\Models\Admin\Route;
use App\Models\Admin\Service;
use App\Models\Admin\Skill;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Portfolio\PortfolioPresentation;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

// Synthetic browser fixtures. Refuse any valuable or configured application database.
require __DIR__.'/../../vendor/autoload.php';
$path = getenv('DB_DATABASE');
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite' || basename(dirname($path)) === '.' || ! str_starts_with(basename(dirname($path)), 'itechorbit-browser-') || filesize($path) !== 0) {
    throw new RuntimeException('Browser fixtures require a newly created isolated SQLite database.');
}
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Artisan::call('migrate', ['--force' => true]);
ContactInfo::create(['title' => 'What are you working on?', 'subtitle' => 'Share your idea, challenge, or next opportunity.', 'descriptions' => '', 'website_url' => 'https://example.test', 'email' => 'hello@example.test', 'phone' => '+880 (2) 123-456', 'mobile' => '+880 1712 345678', 'address' => "Example office\nDhaka, Bangladesh", 'status' => true]);
foreach ([1 => 'Administrator', 4 => 'Member'] as $id => $name) {
    (new Role)->forceFill(['id' => $id, 'name' => $name, 'status' => 1])->save();
}
$user = User::create(['name' => 'Test Engineer', 'email' => 'admin@example.test', 'password' => Hash::make('Browser-test-password-42'), 'role_id' => 1, 'status' => 1, 'portfolio_status' => 1, 'portfolio_id' => 'TESTPROFILE']);
$user->forceFill(['email_verified_at' => now()])->save();
foreach (['studio', 'blueprint'] as $recipient) {
    $noticeUser = User::create(['name' => 'Notice recipient '.ucfirst($recipient), 'email' => 'notice-'.$recipient.'@example.test', 'portfolio_id' => 'PRIVATE-'.strtoupper($recipient), 'portfolio_status' => 0, 'password' => Hash::make('Synthetic-only-password-42'), 'role_id' => 4, 'status' => 1]);
    $noticeUser->forceFill(['email_verified_at' => now()])->save();
}
SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'browser-google-identity']);
Basic::create(['user_id' => $user->id, 'first_name' => 'Test', 'last_name' => 'Engineer', 'designation' => 'Full-stack software engineer', 'mobile' => '', 'address' => '', 'service_offering_title' => 'Software engineering', 'career_objective' => 'Thoughtful architecture. Reliable applications. Software built to make a difference.', 'self_introduction' => '<p>I turn complex requirements into clear, maintainable applications. My work connects domain thinking, reliable APIs and carefully considered user experiences.</p>', 'additional_information' => '<p>I value readable code, practical architecture and clear communication throughout delivery.</p>', 'experience_year' => 0, 'birth_date' => '1990-01-01', 'gender' => '', 'marital_status' => '', 'status' => 1]);
Experience::create(['user_id' => $user->id, 'designation' => 'Software engineer', 'organization' => 'Synthetic engineering team', 'join_date' => '2022-01-01', 'responsibilities' => '<p>Application architecture, API delivery and code review.</p>', 'descriptions' => '<p>A synthetic experience entry for portfolio layout verification.</p>', 'status' => 1]);
Service::create(['user_id' => $user->id, 'name' => 'Application development', 'title' => 'From requirements to reliable software', 'about_service' => '<p>Web applications and APIs with a focus on maintainability.</p>', 'status' => 1]);
$menu = Menu::create(['name' => 'Test workspace', 'status' => 1]);
$module = Module::create(['name' => 'Management', 'menu_id' => $menu->id, 'status' => 1]);
foreach ([...array_keys(config('admin')), 'permissions'] as $slug) {
    Link::updateOrCreate(['url' => $slug], ['name' => config('admin.'.$slug.'.title', 'Roles & permissions'), 'module_id' => $module->id, 'status' => 1]);
}
foreach (['index', 'create', 'edit', 'destroy'] as $action) {
    Route::create(['name' => $action, 'status' => 1]);
}
$category = Category::create(['name' => 'Laravel', 'title' => 'Engineering', 'status' => 1]);
for ($i = 1; $i <= 13; $i++) {
    Post::create(['user_id' => $user->id, 'category_id' => $category->id, 'title' => $i === 1 ? 'Designing maintainable Laravel applications' : ($i === 13 ? '</title><script id="title-injection">alert(1)</script>' : 'Engineering note '.$i), 'body' => '<h2>Start with the domain</h2><p>A synthetic article used only for browser verification.</p><h3>Validate the boundaries</h3><pre><code class="language-php">echo "hello";</code></pre>', 'tags' => json_encode(['Laravel']), 'status' => 1, 'is_private' => 0]);
}
Post::create(['user_id' => $user->id, 'category_id' => $category->id, 'title' => 'Private fixture must never appear', 'body' => 'Private fixture', 'status' => 1, 'is_private' => 1]);
foreach (['PHP', 'React', 'TypeScript', 'MySQL', 'Redis'] as $technology) {
    Skill::create(['name' => $technology, 'user_id' => $user->id, 'status' => 1]);
}
$skill = Skill::create(['name' => 'Laravel', 'user_id' => $user->id, 'status' => 1]);
Project::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'name' => 'Synthetic project for testing', 'client_name' => 'Synthetic client', 'project_category' => 'Web application', 'about_project' => 'A synthetic project used only for automated UI verification.', 'played_role' => 'Engineering', 'technology_used' => json_encode(['Laravel', 'React']), 'status' => 1]);
echo "Isolated synthetic fixtures prepared.\n";

foreach ([['Synthetic API platform', 'API platform', 'API architecture'], ['Synthetic developer workspace', 'Developer tooling', 'Full-stack engineering'], ['Synthetic data integration', 'Data integration', 'Integration engineering']] as [$name, $category, $role]) {
    Project::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'name' => $name, 'client_name' => 'Synthetic client', 'project_category' => $category, 'about_project' => 'A synthetic portfolio entry for gallery and category filter verification.', 'played_role' => $role, 'technology_used' => json_encode(['Laravel', 'TypeScript', 'Redis']), 'status' => 1]);
}

foreach (array_keys(PortfolioPresentation::TEMPLATES) as $template) {
    $sparse = User::create(['name' => 'Minimal Engineer', 'email' => 'sparse-'.$template.'@example.test', 'password' => Hash::make('Synthetic-only-password-42'), 'role_id' => 4, 'status' => 1, 'portfolio_status' => 1, 'portfolio_id' => 'SPARSE-'.strtoupper($template)]);
    $sparse->forceFill(['portfolio_template' => $template])->save();
}

// The assistant has no provider key and no indexed knowledge in browser fixtures.
// Tests cover real transport/fallback behavior and intercept generation separately.
Artisan::call('ai-pro:setup');
Site::where('public_id', 'itechorbit')->update(['enabled' => true]);
Site::create(['public_id' => 'browser-remote', 'name' => 'Embedded test website', 'url' => 'http://127.0.0.1:8011',
    'origins' => ['http://127.0.0.1:8011'], 'enabled' => true, 'contact_url' => 'http://127.0.0.1:8011/contact']);
Artisan::call('db:seed', ['--class' => 'WorkspaceNavigationSeeder', '--force' => true]);
Artisan::call('db:seed', ['--class' => 'WebsiteGrowthServicesSeeder', '--force' => true]);

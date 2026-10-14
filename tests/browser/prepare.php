<?php

use App\Models\Admin\Category;
use App\Models\Admin\Link;
use App\Models\Admin\Menu;
use App\Models\Admin\Module;
use App\Models\Admin\Post;
use App\Models\Admin\Project;
use App\Models\Admin\Role;
use App\Models\Admin\Route;
use App\Models\Admin\Skill;
use App\Models\User;
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
foreach ([1 => 'Administrator', 4 => 'Member'] as $id => $name) {
    (new Role)->forceFill(['id' => $id, 'name' => $name, 'status' => 1])->save();
}
$user = User::create(['name' => 'Test Engineer', 'email' => 'admin@example.test', 'password' => Hash::make('Browser-test-password-42'), 'role_id' => 1, 'status' => 1, 'portfolio_status' => 1, 'portfolio_id' => 'TESTPROFILE']);
$user->forceFill(['email_verified_at' => now()])->save();
$menu = Menu::create(['name' => 'Test workspace', 'status' => 1]);
$module = Module::create(['name' => 'Management', 'menu_id' => $menu->id, 'status' => 1]);
foreach ([...array_keys(config('admin')), 'permissions'] as $slug) {
    Link::create(['name' => $slug, 'url' => $slug, 'module_id' => $module->id, 'status' => 1]);
}
foreach (['index', 'create', 'edit', 'destroy'] as $action) {
    Route::create(['name' => $action, 'status' => 1]);
}
$category = Category::create(['name' => 'Laravel', 'title' => 'Engineering', 'status' => 1]);
for ($i = 1; $i <= 13; $i++) {
    Post::create(['user_id' => $user->id, 'category_id' => $category->id, 'title' => $i === 1 ? 'Designing maintainable Laravel applications' : ($i === 13 ? '</title><script id="title-injection">alert(1)</script>' : 'Engineering note '.$i), 'body' => '<h2>Start with the domain</h2><p>A synthetic article used only for browser verification.</p><h3>Validate the boundaries</h3><pre><code class="language-php">echo "hello";</code></pre>', 'tags' => json_encode(['Laravel']), 'status' => 1, 'is_private' => 0]);
}
Post::create(['user_id' => $user->id, 'category_id' => $category->id, 'title' => 'Private fixture must never appear', 'body' => 'Private fixture', 'status' => 1, 'is_private' => 1]);
$skill = Skill::create(['name' => 'Laravel', 'user_id' => $user->id, 'status' => 1]);
Project::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'name' => 'Synthetic project for testing', 'client_name' => 'Synthetic client', 'project_category' => 'Web application', 'about_project' => 'A synthetic project used only for automated UI verification.', 'played_role' => 'Engineering', 'technology_used' => json_encode(['Laravel', 'React']), 'status' => 1]);
echo "Isolated synthetic fixtures prepared.\n";

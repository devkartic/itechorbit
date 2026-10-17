<?php

namespace Tests\Concerns;

use App\Models\Admin\Category;
use App\Models\Admin\Link;
use App\Models\Admin\Menu;
use App\Models\Admin\Module;
use App\Models\Admin\Post;
use App\Models\Admin\Role;
use App\Models\Admin\Route;
use App\Models\User;

trait BuildsTestData
{
    protected function user(int $role = 4, array $attributes = []): User
    {
        foreach ([1, 4] as $id) {
            if (! Role::find($id)) {
                (new Role)->forceFill(['id' => $id, 'name' => $id === 1 ? 'Administrator' : 'Member', 'status' => 1])->save();
            }
        }

        return User::factory()->create(['role_id' => $role, 'status' => 1, ...$attributes]);
    }

    protected function article(array $attributes = [], ?User $user = null): Post
    {
        $user ??= $this->user();
        $category = Category::firstOrCreate(['name' => 'Laravel'], ['title' => 'Laravel engineering', 'status' => 1]);

        return Post::create(['title' => 'Laravel architecture', 'body' => '<h2>Architecture</h2><p>Practical Laravel engineering.</p><pre><code class="language-php">echo "hello";</code></pre>', 'user_id' => $user->id, 'category_id' => $category->id, 'status' => 1, 'is_private' => 0, ...$attributes]);
    }

    protected function permissionResources(): void
    {
        $menu = Menu::firstOrCreate(['name' => 'Test workspace'], ['status' => 1]);
        $module = Module::firstOrCreate(['name' => 'Management'], ['menu_id' => $menu->id, 'status' => 1]);
        foreach ([...array_keys(config('admin')), 'permissions'] as $slug) {
            Link::updateOrCreate(['url' => $slug], ['name' => config('admin.'.$slug.'.title', 'Roles & permissions'), 'module_id' => $module->id, 'status' => 1]);
        }
        foreach (['index', 'create', 'edit', 'destroy'] as $name) {
            Route::firstOrCreate(['name' => $name], ['status' => 1]);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin\Category;
use App\Models\Admin\Post;
use App\Models\Admin\Role;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function writer(int $role = 4): User
    {
        if (! Role::find($role)) {
            (new Role)->forceFill(['id' => $role, 'name' => 'Test role', 'status' => 1])->save();
        }

        return User::factory()->create(['role_id' => $role, 'status' => 1]);
    }

    private function article(array $attributes = []): Post
    {
        $user = $this->writer();
        $category = Category::create(['name' => 'Category '.uniqid(), 'title' => 'Engineering', 'status' => 1]);

        return Post::create(array_merge(['user_id' => $user->id, 'category_id' => $category->id,
            'title' => 'Public article', 'body' => 'needle engineering', 'status' => 1, 'is_private' => 0], $attributes));
    }

    public function test_search_never_exposes_private_or_unpublished_articles(): void
    {
        $public = $this->article();
        $this->article(['is_private' => 1]);
        $this->article(['status' => 0]);
        $this->assertSame([$public->id], Blog::search_posts('needle')->pluck('id')->all());
    }

    public function test_article_detail_returns_404_for_private_draft_and_missing_records(): void
    {
        foreach ([['is_private' => 1], ['status' => 0]] as $state) {
            $this->get('/blogs/'.$this->article($state)->id)->assertNotFound();
        }
        $this->get('/blogs/999999')->assertNotFound();
    }

    public function test_guest_cannot_access_admin_or_reply_actions(): void
    {
        foreach (['/posts', '/users', '/contacts', '/projects', '/permissions'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $this->postJson('/reply', ['comment' => 'text', 'post_id' => 1])->assertUnauthorized();
    }

    public function test_user_one_with_non_admin_role_cannot_read_other_users_records(): void
    {
        $owner = $this->writer();
        $other = $this->writer();
        $this->actingAs($owner);
        $this->assertSame([$owner->id], User::filtered_users()->pluck('users.id')->all());
        $this->assertFalse(User::filtered_users()->where('users.id', $other->id)->exists());
    }

    public function test_missing_portfolio_is_a_404(): void
    {
        $this->get('/portfolios/not-a-real-key')->assertNotFound();
    }

    public function test_contact_and_newsletter_reject_invalid_email_addresses(): void
    {
        $this->postJson('/contacts', ['name' => 'Test', 'email' => 'invalid', 'subject' => 'Hello', 'message' => 'Help'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/newsletters', ['email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}

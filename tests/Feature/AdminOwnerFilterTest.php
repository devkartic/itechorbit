<?php

namespace Tests\Feature;

use App\Models\Admin\Basic;
use App\Models\Admin\Link;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\Admin\Role;
use App\Models\Admin\Route;
use App\Models\Admin\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTestData;
use Tests\TestCase;

class AdminOwnerFilterTest extends TestCase
{
    use BuildsTestData, RefreshDatabase;

    private function content(User $user, string $name): array
    {
        $post = $this->article(['title' => $name], $user);
        $skill = Skill::create(['user_id' => $user->id, 'name' => $name, 'status' => 1]);
        $project = Project::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'name' => $name, 'client_name' => 'Test', 'about_project' => 'Test', 'status' => 1]);
        $basic = Basic::create(['user_id' => $user->id, 'first_name' => $name, 'last_name' => 'Engineer', 'designation' => 'Engineer', 'mobile' => '', 'address' => '', 'service_offering_title' => 'Engineering', 'career_objective' => 'Engineering', 'experience_year' => 0, 'birth_date' => '1990-01-01', 'gender' => '', 'marital_status' => '', 'status' => 1]);

        return ['posts' => $post->id, 'skills' => $skill->id, 'projects' => $project->id, 'basics' => $basic->id];
    }

    public function test_super_admin_defaults_to_own_records_and_can_select_a_user_or_all(): void
    {
        $admin = $this->user(1);
        $other = $this->user(4, ['name' => 'Other engineer']);
        $own = $this->content($admin, 'Own record');
        $others = $this->content($other, 'Other record');
        $this->permissionResources();
        $this->actingAs($admin);

        foreach ($own as $resource => $id) {
            $this->get('/'.$resource)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('filters.user_id', 'mine')->has('records.data', 1)->where('records.data.0.id', $id)
                ->has('filterUsers', 2)->where('filterUsers', fn ($users) => $users->every(fn ($user) => array_keys($user) === ['id', 'name'])));
            $this->getJson('/workspace/data/'.$resource)->assertOk()->assertJsonCount(1, 'records.data')->assertJsonPath('records.data.0.id', $id);
            $this->getJson('/workspace/data/'.$resource.'?user_id='.$other->id)->assertOk()->assertJsonCount(1, 'records.data')->assertJsonPath('records.data.0.id', $others[$resource]);
            $this->get('/'.$resource.'?user_id='.$other->id)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('filters.user_id', (string) $other->id)->where('records.data.0.id', $others[$resource]));
            $this->getJson('/workspace/data/'.$resource.'?user_id=all')->assertOk()->assertJsonCount(2, 'records.data');
            $this->getJson('/workspace/data/'.$resource.'?user_id=all&key=Other&status=1&per_page=10')->assertOk()->assertJsonCount(1, 'records.data')->assertJsonPath('records.data.0.id', $others[$resource]);
        }
        // Every user-owned module offers the same filter; shared configuration tables do not.
        foreach (config('admin') as $resource => $module) {
            $response = $this->get('/'.$resource)->assertOk();
            $response->assertInertia(fn (Assert $page) => $module['owner']
                ? $page->where('filters.user_id', 'mine')->has('filterUsers', 2)
                : $page->missing('filters.user_id')->has('filterUsers', 0));
        }
    }

    public function test_other_roles_cannot_use_the_filter_or_access_other_owners(): void
    {
        $admin = $this->user(1);
        $this->content($admin, 'Private admin record');
        $this->permissionResources();
        foreach ([2, 4] as $role) {
            if (! Role::find($role)) {
                (new Role)->forceFill(['id' => $role, 'name' => 'Role '.$role, 'status' => 1])->save();
            }
            $member = $this->user($role);
            $own = $this->content($member, 'Own record');
            foreach ($own as $resource => $id) {
                Permission::firstOrCreate(['role_id' => $role, 'link_id' => Link::where('url', $resource)->value('id'), 'route_id' => Route::where('name', 'index')->value('id')]);
                $this->actingAs($member)->get('/'.$resource)->assertOk()->assertInertia(fn (Assert $page) => $page
                    ->missing('filters.user_id')->has('filterUsers', 0)->has('records.data', 1)->where('records.data.0.id', $id));
                $this->getJson('/workspace/data/'.$resource)->assertOk()->assertJsonCount(1, 'records.data')->assertJsonPath('records.data.0.id', $id);
                foreach (['all', 'mine', (string) $admin->id] as $owner) {
                    $this->getJson('/workspace/data/'.$resource.'?user_id='.$owner)->assertUnprocessable()->assertJsonValidationErrors('user_id');
                }
            }
        }
    }

    public function test_invalid_owner_values_are_rejected_without_changing_shared_tables(): void
    {
        $this->actingAs($this->user(1));
        foreach (['-1', '0', '999999', 'bad', '1.5', 'all%27', '%5B1%5D'] as $owner) {
            $this->getJson('/workspace/data/posts?user_id='.$owner)->assertUnprocessable()->assertJsonValidationErrors('user_id');
        }
        $this->getJson('/workspace/data/categories?user_id=all')->assertUnprocessable()->assertJsonValidationErrors('user_id');
    }
}

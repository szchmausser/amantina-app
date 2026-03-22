<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create basic roles and permissions
        Permission::create(['name' => 'users.view']);
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo('users.view');

        Role::create(['name' => 'alumno']);
    }

    public function test_admin_can_view_any_user_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create(['name' => 'Target User']);
        $targetUser->assignRole('alumno');

        $response = $this->actingAs($admin)->get(route('admin.users.show', $targetUser));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/show')
            ->where('user.name', 'Target User')
            ->where('user.email', $targetUser->email)
            ->has('user.roles')
            ->has('user.permissions')
        );
    }

    public function test_user_can_view_their_own_details(): void
    {
        $user = User::factory()->create(['name' => 'Self User']);
        $user->assignRole('alumno');

        $response = $this->actingAs($user)->get(route('admin.users.show', $user));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/show')
            ->where('user.name', 'Self User')
        );
    }

    public function test_user_without_permission_cannot_view_others_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('alumno');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('alumno');

        $response = $this->actingAs($user)->get(route('admin.users.show', $otherUser));

        $response->assertStatus(403);
    }

    public function test_view_shows_correct_direct_and_inherited_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $permission1 = Permission::create(['name' => 'test.permission1']);
        $permission2 = Permission::create(['name' => 'test.permission2']);

        $role = Role::create(['name' => 'test-role']);
        $role->givePermissionTo($permission1);

        $targetUser = User::factory()->create();
        $targetUser->assignRole($role);
        $targetUser->givePermissionTo($permission2);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $targetUser));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('user.roles.0.permissions.0.name', 'test.permission1')
            ->where('user.permissions.0.name', 'test.permission2')
        );
    }
}

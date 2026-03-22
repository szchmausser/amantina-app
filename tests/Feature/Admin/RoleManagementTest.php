<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            ValidateCsrfToken::class,
        ]);

        $this->seed(RoleAndPermissionSeeder::class);
    }

    /**
     * Admin can view the roles list.
     */
    public function test_admin_can_view_roles_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('admin/roles/index'));
    }

    /**
     * Non-admin without roles.view cannot access the roles list.
     */
    public function test_user_without_roles_view_permission_cannot_view_roles(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        $response = $this->actingAs($alumno)->get(route('admin.roles.index'));

        $response->assertStatus(403);
    }

    /**
     * Non-admin with direct roles.view permission can access roles list.
     */
    public function test_user_with_direct_roles_view_permission_can_view_roles(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');
        $profesor->givePermissionTo('roles.view');

        $response = $this->actingAs($profesor)->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('admin/roles/index'));
    }

    /**
     * Admin can view the edit role page.
     */
    public function test_admin_can_view_edit_role_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::findByName('profesor');

        $response = $this->actingAs($admin)->get(route('admin.roles.edit', $role->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/roles/edit')
            ->has('role')
            ->has('allPermissions')
        );
    }

    /**
     * Admin can update role permissions.
     */
    public function test_admin_can_update_role_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::findByName('profesor');

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $role->id), [
            'permissions' => ['users.view', 'users.create', 'roles.view'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertTrue($role->hasPermissionTo('users.create'));
        $this->assertTrue($role->hasPermissionTo('roles.view'));
        $this->assertFalse($role->hasPermissionTo('users.delete'));
    }

    /**
     * Admin can clear all permissions from a role.
     */
    public function test_admin_can_clear_role_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::findByName('profesor');
        $this->assertTrue($role->hasPermissionTo('users.view'));

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $role->id), [
            'permissions' => [],
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertCount(0, $role->permissions);
    }

    /**
     * Non-admin without roles.edit cannot update role.
     */
    public function test_user_without_roles_edit_permission_cannot_update_role(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        $role = Role::findByName('profesor');

        $response = $this->actingAs($alumno)->put(route('admin.roles.update', $role->id), [
            'permissions' => ['users.view'],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Invalid permissions are rejected.
     */
    public function test_invalid_permissions_are_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::findByName('profesor');

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $role->id), [
            'permissions' => ['nonexistent.permission'],
        ]);

        $response->assertSessionHasErrors(['permissions.0']);
    }

    /**
     * Admin can view specific role details.
     */
    public function test_admin_can_view_role_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::findByName('profesor');

        $response = $this->actingAs($admin)->get(route('admin.roles.show', $role->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/roles/show')
            ->where('role.name', 'profesor')
            ->has('role.permissions')
        );
    }

    /**
     * Non-admin without roles.view cannot access role details.
     */
    public function test_user_without_permission_cannot_view_role_details(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        $role = Role::findByName('admin');

        $response = $this->actingAs($alumno)->get(route('admin.roles.show', $role->id));

        $response->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
    }

    /**
     * Admin cannot remove permissions from a role they currently have.
     */
    public function test_admin_cannot_remove_permissions_from_their_own_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::findByName('admin');
        $initialCount = $role->permissions->count();

        // Try to remove all permissions
        $response = $this->actingAs($admin)->put(route('admin.roles.update', $role->id), [
            'permissions' => [],
        ]);

        $response->assertSessionHasErrors(['permissions']);
        $role->refresh();
        $this->assertEquals($initialCount, $role->permissions->count());
    }

    /**
     * Admin can still add permissions to their own role.
     */
    public function test_admin_can_add_permissions_to_their_own_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create a new permission that's not assigned to anyone yet
        Permission::create(['name' => 'new.test.permission']);

        $role = Role::findByName('admin');
        $currentPermissions = $role->permissions->pluck('name')->toArray();
        $newPermissions = array_merge($currentPermissions, ['new.test.permission']);

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $role->id), [
            'permissions' => $newPermissions,
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('new.test.permission'));
    }

    /**
     * Admin can remove permissions from a role they DO NOT have.
     */
    public function test_admin_can_remove_permissions_from_other_roles(): void
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
}

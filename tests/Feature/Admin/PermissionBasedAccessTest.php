<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionBasedAccessTest extends TestCase
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
     * A user with a role that has a permission inherits that permission.
     */
    public function test_user_inherits_permissions_from_role(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        // Profesor role has 'users.view' via seeder
        $this->assertTrue($profesor->hasPermissionTo('users.view'));
        $this->assertFalse($profesor->hasPermissionTo('users.create'));
    }

    /**
     * A user can have direct permissions independent of their role.
     */
    public function test_user_can_have_direct_permissions(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        // Alumno doesn't have users.view by default
        $this->assertFalse($alumno->hasPermissionTo('users.view'));

        // Assign directly
        $alumno->givePermissionTo('users.view');
        $this->assertTrue($alumno->hasPermissionTo('users.view'));
    }

    /**
     * A user with direct permission can access protected resource.
     */
    public function test_user_with_direct_permission_can_access_resource(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');
        $alumno->givePermissionTo('users.view');

        $response = $this->actingAs($alumno)->get(route('admin.users.index'));

        $response->assertStatus(200);
    }

    /**
     * A user without the required permission cannot access protected resource.
     */
    public function test_user_without_permission_cannot_access_resource(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        $response = $this->actingAs($alumno)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    /**
     * Admin can assign direct permissions to a user via update.
     */
    public function test_admin_can_assign_direct_permissions_to_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('alumno');

        $this->assertFalse($user->hasPermissionTo('roles.view'));

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user->id), [
            'cedula' => $user->cedula,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => ['alumno'],
            'direct_permissions' => ['roles.view', 'permissions.view'],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertTrue($user->hasDirectPermission('roles.view'));
        $this->assertTrue($user->hasDirectPermission('permissions.view'));
    }

    /**
     * Admin can remove direct permissions from a user via update.
     */
    public function test_admin_can_remove_direct_permissions_from_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('alumno');
        $user->givePermissionTo(['roles.view', 'permissions.view']);

        $this->assertTrue($user->hasDirectPermission('roles.view'));
        $this->assertTrue($user->hasDirectPermission('permissions.view'));

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user->id), [
            'cedula' => $user->cedula,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => ['alumno'],
            'direct_permissions' => [],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertFalse($user->hasDirectPermission('roles.view'));
        $this->assertFalse($user->hasDirectPermission('permissions.view'));
    }

    /**
     * Role-inherited permissions are not affected by direct permission sync.
     */
    public function test_role_permissions_persist_after_direct_permission_sync(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('profesor');

        // Profesor has users.view from role
        $this->assertTrue($user->hasPermissionTo('users.view'));

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user->id), [
            'cedula' => $user->cedula,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '04121234567',
            'address' => 'Test Address',
            'roles' => ['profesor'],
            'direct_permissions' => ['roles.view'],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        // Role-inherited permission still works
        $this->assertTrue($user->hasPermissionTo('users.view'));
        // Direct permission was added
        $this->assertTrue($user->hasDirectPermission('roles.view'));
    }
}

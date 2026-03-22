<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
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
     * Admin can view the permissions list.
     */
    public function test_admin_can_view_permissions_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.permissions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('admin/permissions/index'));
    }

    /**
     * Non-admin without permissions.view cannot access.
     */
    public function test_user_without_permission_cannot_view_permissions(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        $response = $this->actingAs($alumno)->get(route('admin.permissions.index'));

        $response->assertStatus(403);
    }

    /**
     * Non-admin with direct permissions.view can access.
     */
    public function test_user_with_direct_permission_can_view_permissions(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');
        $profesor->givePermissionTo('permissions.view');

        $response = $this->actingAs($profesor)->get(route('admin.permissions.index'));

        $response->assertStatus(200);
    }

    /**
     * Permissions list contains all seeded permissions.
     */
    public function test_permissions_list_contains_seeded_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.permissions.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('admin/permissions/index')
            ->has('permissions', Permission::count())
        );
    }
}

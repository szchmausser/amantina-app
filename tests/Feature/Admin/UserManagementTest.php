<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            ValidateCsrfToken::class,
        ]);

        $this->seed(RoleAndPermissionSeeder::class);
    }

    /**
     * Admin can view the users list.
     */
    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('admin/users/index'));
    }

    /**
     * Non-admin cannot view the users list.
     */
    public function test_alumno_cannot_view_user_list(): void
    {
        $alumno = User::factory()->create();
        $alumno->assignRole('alumno');

        $response = $this->actingAs($alumno)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    /**
     * Admin can create an Alumno with null contact info.
     */
    public function test_admin_can_create_alumno_with_minimal_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $userData = [
            'cedula' => 'V-12345678',
            'name' => 'Test Alumno',
            'email' => 'alumno@test.com',
            'role' => 'alumno',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => null,
            'address' => null,
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'alumno@test.com',
            'cedula' => 'V-12345678',
        ]);

        $user = User::where('email', 'alumno@test.com')->first();
        $this->assertTrue($user->hasRole('alumno'));
    }

    /**
     * Admin cannot create a Profesor with null contact info.
     */
    public function test_admin_cannot_create_profesor_with_null_contact_info(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $userData = [
            'cedula' => 'V-87654321',
            'name' => 'Test Profesor',
            'email' => 'profesor@test.com',
            'role' => 'profesor',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => null, // Required for profesor
            'address' => null, // Required for profesor
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors(['phone', 'address']);
    }

    /**
     * Admin can update a user and sync roles.
     */
    public function test_admin_can_update_user_and_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('alumno');

        $updateData = [
            'cedula' => $user->cedula,
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'role' => 'profesor',
            'phone' => '04121234567',
            'address' => 'Updated Address',
        ];

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user->id), $updateData);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('profesor'));
        $this->assertFalse($user->hasRole('alumno'));
    }

    /**
     * Admin can delete a user (soft delete).
     */
    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user->id));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}

<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que el seeder crea los roles básicos.
     */
    public function test_seeder_creates_base_roles(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'profesor']);
        $this->assertDatabaseHas('roles', ['name' => 'alumno']);
        $this->assertDatabaseHas('roles', ['name' => 'representante']);
    }

    /**
     * Verifica que un usuario puede tener un rol asignado.
     */
    public function test_user_can_be_assigned_a_role(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('profesor');

        $this->assertTrue($user->hasRole('profesor'));
        $this->assertFalse($user->hasRole('admin'));
    }
}

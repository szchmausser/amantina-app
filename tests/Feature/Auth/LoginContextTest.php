<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_login_without_context_assigns_role_by_hierarchy(): void
    {
        // Usuario con roles 'profesor' y 'representante'
        $user = User::factory()->create();
        $user->assignRole(['representante', 'profesor']);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // Según jerarquía: profesor > representante
        $this->assertEquals('profesor', session('active_role'));
    }

    public function test_login_with_valid_context_respects_selection(): void
    {
        // Usuario con roles 'profesor' y 'representante'
        $user = User::factory()->create();
        $user->assignRole(['profesor', 'representante']);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'context' => 'representante',
        ]);

        $this->assertAuthenticated();
        $this->assertEquals('representante', session('active_role'));
    }

    public function test_login_with_admin_priority(): void
    {
        // Usuario con todos los roles
        $user = User::factory()->create();
        $user->assignRole(['admin', 'profesor', 'alumno', 'representante']);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertEquals('admin', session('active_role'));
    }

    public function test_login_with_invalid_context_throws_error(): void
    {
        $user = User::factory()->create();
        $user->assignRole('alumno');

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'context' => 'admin', // Intento no autorizado
        ]);

        $response->assertSessionHasErrors('context');
        $this->assertGuest();
    }

    public function test_new_registration_gets_default_role(): void
    {
        // Simulamos registro
        $response = $this->post(route('register'), [
            'cedula' => '12345678',
            'name' => 'Nuevo Alumno',
            'email' => 'nuevo@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '04121112233',
            'address' => 'Casa 1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user = User::where('email', 'nuevo@test.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('alumno'));
        $this->assertEquals('alumno', session('active_role'));
    }

    public function test_middleware_restores_context_on_session_loss(): void
    {
        $user = User::factory()->create();
        $user->assignRole('profesor');

        $this->actingAs($user);

        // Simulamos pérdida de sesión del rol activo
        session()->forget('active_role');
        $this->assertFalse(session()->has('active_role'));

        // Al acceder a cualquier ruta (ej. dashboard), el middleware debería restaurarlo
        // Primero nos aseguramos de que el dashboard cargue sin Vite
        $this->withoutVite();
        $this->get(route('dashboard'));

        $this->assertEquals('profesor', session('active_role'));
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyFeature(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $this->withoutVite();
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        // Asegurarnos de que la institución y roles existen para el test
        $this->seed([InstitutionSeeder::class, RoleAndPermissionSeeder::class]);

        $response = $this->post(route('register.store'), [
            'cedula' => '12345678',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '04121234567',
            'address' => 'Test Address',
            'is_transfer' => '0',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'cedula' => '12345678',
            'is_active' => true,
            'is_transfer' => false,
            'institution_origin' => 'Amantina de Sucre', // Verificamos auto-asignación desde Institution
        ]);
    }

    public function test_registration_requires_mandatory_fields()
    {
        $response = $this->post(route('register.store'), []);

        $response->assertSessionHasErrors(['cedula', 'name', 'email', 'phone', 'address', 'password']);
    }

    public function test_registration_as_transfer_requires_institution_origin()
    {
        $response = $this->post(route('register.store'), [
            'cedula' => '12345678',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '04121234567',
            'address' => 'Test Address',
            'is_transfer' => '1',
            'institution_origin' => '', // Vacío siendo transferido
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['institution_origin']);
    }

    public function test_registration_as_transfer_with_institution_origin_success()
    {
        $this->seed([RoleAndPermissionSeeder::class]);

        $response = $this->post(route('register.store'), [
            'cedula' => '12345678',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '04121234567',
            'address' => 'Test Address',
            'is_transfer' => '1',
            'institution_origin' => 'Otra Institución',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_transfer' => true,
            'institution_origin' => 'Otra Institución',
        ]);
    }

    public function test_new_user_has_alumno_role()
    {
        $this->seed([InstitutionSeeder::class, RoleAndPermissionSeeder::class]);

        $this->post(route('register.store'), [
            'cedula' => '12345678',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '04121234567',
            'address' => 'Test Address',
            'is_transfer' => '0',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('alumno'));
    }
}

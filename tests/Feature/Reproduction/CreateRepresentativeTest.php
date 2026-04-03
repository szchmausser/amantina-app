<?php

namespace Tests\Feature\Reproduction;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class CreateRepresentativeTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_create_representative_user_with_empty_institution_origin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $userData = [
            'cedula' => 'V-99999999',
            'name' => 'Test Representante',
            'email' => 'representante@test.com',
            'roles' => ['representante'], // Array de roles, no string singular
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '04121234567',
            'address' => 'Some address',
            'institution_origin' => '', // Empty string as sent by frontend
            'is_transfer' => false,
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'representante@test.com',
        ]);

        $user = User::where('email', 'representante@test.com')->first();
        $this->assertTrue($user->hasRole('representante'));
    }
}

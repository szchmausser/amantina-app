<?php

namespace Tests\Feature\Admin;

use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();
    }

    public function test_admin_can_view_locations_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Location::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.locations.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/locations/index')
            ->has('locations.data', 3)
        );
    }

    public function test_admin_can_create_location(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.locations.store'), [
            'name' => 'Huerto escolar norte',
            'description' => 'Huerto ubicado en la parte norte del plantel',
        ]);

        $response->assertRedirect(route('admin.locations.index'));
        $this->assertDatabaseHas('locations', [
            'name' => 'Huerto escolar norte',
        ]);
    }

    public function test_cannot_create_location_with_duplicate_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Location::factory()->create(['name' => 'Huerto escolar']);

        $response = $this->actingAs($admin)->post(route('admin.locations.store'), [
            'name' => 'Huerto escolar',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_location(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $location = Location::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.locations.update', $location), [
            'name' => 'Ubicación Actualizada',
            'description' => 'Nueva descripción',
        ]);

        $response->assertRedirect(route('admin.locations.index'));
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Ubicación Actualizada',
        ]);
    }

    public function test_admin_can_delete_location(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $location = Location::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.locations.destroy', $location));

        $response->assertRedirect(route('admin.locations.index'));
        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_profesor_can_manage_locations(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        // View
        Location::factory()->count(2)->create();
        $response = $this->actingAs($profesor)->get(route('admin.locations.index'));
        $response->assertStatus(200);

        // Create
        $response = $this->actingAs($profesor)->post(route('admin.locations.store'), [
            'name' => 'Nueva Ubicación',
        ]);
        $response->assertRedirect(route('admin.locations.index'));

        // Update
        $location = Location::first();
        $response = $this->actingAs($profesor)->put(route('admin.locations.update', $location), [
            'name' => 'Ubicación Editada',
        ]);
        $response->assertRedirect(route('admin.locations.index'));

        // Delete
        $response = $this->actingAs($profesor)->delete(route('admin.locations.destroy', $location));
        $response->assertRedirect(route('admin.locations.index'));
    }

    public function test_non_admin_without_permission_cannot_manage_locations(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.locations.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->post(route('admin.locations.store'), ['name' => 'Test']);
        $response->assertStatus(403);
    }
}
